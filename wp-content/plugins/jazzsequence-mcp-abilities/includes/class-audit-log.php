<?php
/**
 * Audit logging for MCP abilities.
 *
 * Tracks all AI actions for accountability and debugging.
 *
 * @package JazzSequence\MCP_Abilities
 */

declare( strict_types=1 );

namespace JazzSequence\MCP_Abilities\Audit_Log;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Initialize audit logging.
 *
 * @since 0.1.0
 */
function init(): void {
	// Hook into ability executions to log them.
	add_action( 'wp_ability_executed', __NAMESPACE__ . '\log_ability_execution', 10, 3 );
}

/**
 * Log ability execution.
 *
 * @since 0.1.0
 *
 * @param string $ability_name Ability name.
 * @param mixed  $result       Execution result.
 * @param array  $args         Ability arguments.
 */
function log_ability_execution( string $ability_name, $result, array $args ): void {
	// Only log our abilities.
	if ( ! str_starts_with( $ability_name, JSMCP_ABILITY_CATEGORY . '/' ) ) {
		return;
	}

	$user = wp_get_current_user();

	$log_entry = array(
		'timestamp'    => current_time( 'mysql' ),
		'user_id'      => $user->ID,
		'user_login'   => $user->user_login,
		'ability'      => $ability_name,
		'args'         => wp_json_encode( $args ),
		'success'      => ! is_wp_error( $result ),
		'error'        => is_wp_error( $result ) ? $result->get_error_message() : null,
		'ip_address'   => get_client_ip(),
		'user_agent'   => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
	);

	/**
	 * Filter the audit log entry before saving.
	 *
	 * @since 0.1.0
	 *
	 * @param array  $log_entry The log entry data.
	 * @param string $ability_name Ability name.
	 * @param mixed  $result Execution result.
	 */
	$log_entry = apply_filters( 'jsmcp_audit_log_entry', $log_entry, $ability_name, $result );

	// Store in custom table or as post meta (for now, use option for simplicity).
	save_log_entry( $log_entry );

	/**
	 * Action fired after logging an ability execution.
	 *
	 * @since 0.1.0
	 *
	 * @param array  $log_entry The log entry data.
	 * @param string $ability_name Ability name.
	 */
	do_action( 'jsmcp_ability_logged', $log_entry, $ability_name );
}

/**
 * Save a log entry.
 *
 * @since 0.1.0
 *
 * @param array $log_entry Log entry data.
 */
function save_log_entry( array $log_entry ): void {
	global $wpdb;

	// Use WordPress transients for temporary storage.
	// In production, this should use a custom table.
	$logs = get_option( 'jsmcp_audit_logs', array() );

	// Keep only last 1000 entries to prevent database bloat.
	if ( count( $logs ) >= 1000 ) {
		array_shift( $logs );
	}

	$logs[] = $log_entry;

	update_option( 'jsmcp_audit_logs', $logs, false );
}

/**
 * Get audit logs.
 *
 * @since 0.1.0
 *
 * @param array $args Query arguments.
 * @return array Array of log entries.
 */
function get_logs( array $args = array() ): array {
	$defaults = array(
		'limit'   => 100,
		'offset'  => 0,
		'user_id' => null,
		'ability' => null,
		'success' => null,
	);

	$args = wp_parse_args( $args, $defaults );

	$logs = get_option( 'jsmcp_audit_logs', array() );

	// Filter logs.
	if ( $args['user_id'] ) {
		$logs = array_filter(
			$logs,
			function ( $log ) use ( $args ) {
				return $log['user_id'] === $args['user_id'];
			}
		);
	}

	if ( $args['ability'] ) {
		$logs = array_filter(
			$logs,
			function ( $log ) use ( $args ) {
				return $log['ability'] === $args['ability'];
			}
		);
	}

	if ( null !== $args['success'] ) {
		$logs = array_filter(
			$logs,
			function ( $log ) use ( $args ) {
				return $log['success'] === $args['success'];
			}
		);
	}

	// Sort by timestamp descending.
	usort(
		$logs,
		function ( $a, $b ) {
			return strcmp( $b['timestamp'], $a['timestamp'] );
		}
	);

	// Apply limit and offset.
	$logs = array_slice( $logs, $args['offset'], $args['limit'] );

	return $logs;
}

/**
 * Clear audit logs.
 *
 * @since 0.1.0
 *
 * @return bool True on success.
 */
function clear_logs(): bool {
	return delete_option( 'jsmcp_audit_logs' );
}

/**
 * Get client IP address.
 *
 * @since 0.1.0
 *
 * @return string Client IP address.
 */
function get_client_ip(): string {
	$ip_keys = array(
		'HTTP_CF_CONNECTING_IP', // Cloudflare.
		'HTTP_X_FORWARDED_FOR',  // Proxy.
		'HTTP_X_REAL_IP',        // Nginx proxy.
		'REMOTE_ADDR',           // Direct connection.
	);

	foreach ( $ip_keys as $key ) {
		if ( isset( $_SERVER[ $key ] ) && filter_var( wp_unslash( $_SERVER[ $key ] ), FILTER_VALIDATE_IP ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
		}
	}

	return '0.0.0.0';
}
