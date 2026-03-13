<?php
/**
 * System Management.
 *
 * Provides cache clearing, cron management, and system operations.
 *
 * @package JazzSequence\MCP_Abilities
 */

declare( strict_types=1 );

namespace JazzSequence\MCP_Abilities\Abilities\System;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * System Manager class.
 *
 * @since 0.1.0
 */
class System_Manager {

	/**
	 * Clear various caches.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Cache clearing arguments.
	 * @return array Result of cache clearing operations.
	 */
	public function clear_cache( array $args ) {
		$cache_types = $args['types'] ?? [ 'object', 'transients', 'rewrite' ];
		$results     = [];

		foreach ( $cache_types as $type ) {
			switch ( $type ) {
				case 'object':
					wp_cache_flush();
					$results['object'] = true;
					break;

				case 'transients':
					delete_expired_transients();
					$results['transients'] = true;
					break;

				case 'rewrite':
					// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules -- Not a VIP site.
					flush_rewrite_rules();
					$results['rewrite'] = true;
					break;
			}
		}

		return [
			'cleared' => $results,
			'message' => __( 'Cache cleared successfully.', 'jazzsequence-mcp-abilities' ),
		];
	}

	/**
	 * Run a cron job immediately.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Cron job arguments (requires hook).
	 * @return array|\WP_Error Execution result or error.
	 */
	public function run_cron( array $args ) {
		if ( empty( $args['hook'] ) ) {
			return new \WP_Error(
				'missing_hook',
				__( 'Cron hook name is required.', 'jazzsequence-mcp-abilities' )
			);
		}

		$hook = sanitize_text_field( $args['hook'] );

		// Check if the hook exists in cron.
		$crons = _get_cron_array();
		$found = false;

		foreach ( $crons as $timestamp => $cron ) {
			if ( isset( $cron[ $hook ] ) ) {
				$found = true;
				break;
			}
		}

		if ( ! $found ) {
			return new \WP_Error(
				'hook_not_found',
				sprintf(
					/* translators: %s: Hook name */
					__( 'Cron hook "%s" not found.', 'jazzsequence-mcp-abilities' ),
					$hook
				)
			);
		}

		// Trigger the cron hook.
		do_action( $hook );

		return [
			'executed' => true,
			'hook'     => $hook,
			'message'  => sprintf(
				/* translators: %s: Hook name */
				__( 'Cron hook "%s" executed successfully.', 'jazzsequence-mcp-abilities' ),
				$hook
			),
		];
	}

	/**
	 * Schedule a new cron job.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Cron scheduling arguments.
	 * @return array|\WP_Error Scheduling result or error.
	 */
	public function schedule_cron( array $args ) {
		if ( empty( $args['hook'] ) ) {
			return new \WP_Error(
				'missing_hook',
				__( 'Cron hook name is required.', 'jazzsequence-mcp-abilities' )
			);
		}

		if ( empty( $args['timestamp'] ) && empty( $args['recurrence'] ) ) {
			return new \WP_Error(
				'missing_schedule',
				__( 'Either timestamp or recurrence is required.', 'jazzsequence-mcp-abilities' )
			);
		}

		$hook      = sanitize_text_field( $args['hook'] );
		$args_data = $args['args'] ?? [];

		if ( ! empty( $args['recurrence'] ) ) {
			// Recurring event.
			$timestamp  = $args['timestamp'] ?? time();
			$recurrence = sanitize_text_field( $args['recurrence'] );

			$result = wp_schedule_event( $timestamp, $recurrence, $hook, $args_data );
		} else {
			// Single event.
			$timestamp = absint( $args['timestamp'] );
			$result    = wp_schedule_single_event( $timestamp, $hook, $args_data );
		}

		if ( is_wp_error( $result ) || false === $result ) {
			return new \WP_Error(
				'schedule_failed',
				__( 'Failed to schedule cron job.', 'jazzsequence-mcp-abilities' )
			);
		}

		return [
			'scheduled'  => true,
			'hook'       => $hook,
			'timestamp'  => $timestamp,
			'recurrence' => $args['recurrence'] ?? 'single',
			'message'    => __( 'Cron job scheduled successfully.', 'jazzsequence-mcp-abilities' ),
		];
	}

	/**
	 * Unschedule a cron job.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Unscheduling arguments.
	 * @return array|\WP_Error Unscheduling result or error.
	 */
	public function unschedule_cron( array $args ) {
		if ( empty( $args['hook'] ) ) {
			return new \WP_Error(
				'missing_hook',
				__( 'Cron hook name is required.', 'jazzsequence-mcp-abilities' )
			);
		}

		$hook      = sanitize_text_field( $args['hook'] );
		$args_data = $args['args'] ?? [];

		if ( ! empty( $args['timestamp'] ) ) {
			// Unschedule specific event.
			$timestamp = absint( $args['timestamp'] );
			$result    = wp_unschedule_event( $timestamp, $hook, $args_data );
		} else {
			// Unschedule all events for this hook.
			$result = wp_clear_scheduled_hook( $hook, $args_data );
		}

		if ( is_wp_error( $result ) || false === $result ) {
			return new \WP_Error(
				'unschedule_failed',
				__( 'Failed to unschedule cron job.', 'jazzsequence-mcp-abilities' )
			);
		}

		return [
			'unscheduled' => true,
			'hook'        => $hook,
			'message'     => __( 'Cron job unscheduled successfully.', 'jazzsequence-mcp-abilities' ),
		];
	}
}
