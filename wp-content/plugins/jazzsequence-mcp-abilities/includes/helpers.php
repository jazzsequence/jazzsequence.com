<?php
/**
 * Helper functions for MCP abilities.
 *
 * @package JazzSequence\MCP_Abilities
 */

declare( strict_types=1 );

namespace JazzSequence\MCP_Abilities;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Convert data to markdown format.
 *
 * @since 0.1.0
 *
 * @param mixed  $data Data to convert.
 * @param string $title Optional title for the markdown.
 * @return string Markdown formatted string.
 */
function to_markdown( $data, string $title = '' ): string {
	$markdown = '';

	if ( $title ) {
		$markdown .= "# {$title}\n\n";
	}

	if ( is_array( $data ) || is_object( $data ) ) {
		$markdown .= array_to_markdown( (array) $data );
	} else {
		$markdown .= (string) $data;
	}

	return $markdown;
}

/**
 * Convert an array to markdown format.
 *
 * @since 0.1.0
 *
 * @param array  $array Array to convert.
 * @param int    $depth Current depth level.
 * @return string Markdown formatted string.
 */
function array_to_markdown( array $array, int $depth = 0 ): string {
	$markdown = '';
	$indent   = str_repeat( '  ', $depth );

	foreach ( $array as $key => $value ) {
		if ( is_array( $value ) || is_object( $value ) ) {
			$markdown .= "{$indent}- **{$key}**:\n";
			$markdown .= array_to_markdown( (array) $value, $depth + 1 );
		} else {
			$formatted_value = format_value_for_markdown( $value );
			$markdown       .= "{$indent}- **{$key}**: {$formatted_value}\n";
		}
	}

	return $markdown;
}

/**
 * Format a value for markdown display.
 *
 * @since 0.1.0
 *
 * @param mixed $value Value to format.
 * @return string Formatted value.
 */
function format_value_for_markdown( $value ): string {
	if ( is_bool( $value ) ) {
		return $value ? 'true' : 'false';
	}

	if ( is_null( $value ) ) {
		return 'null';
	}

	if ( is_numeric( $value ) ) {
		return (string) $value;
	}

	return (string) $value;
}

/**
 * Sanitize ability arguments.
 *
 * @since 0.1.0
 *
 * @param array $args Arguments to sanitize.
 * @return array Sanitized arguments.
 */
function sanitize_ability_args( array $args ): array {
	$sanitized = [];

	foreach ( $args as $key => $value ) {
		if ( is_array( $value ) ) {
			$sanitized[ $key ] = sanitize_ability_args( $value );
		} elseif ( is_string( $value ) ) {
			$sanitized[ $key ] = sanitize_text_field( $value );
		} else {
			$sanitized[ $key ] = $value;
		}
	}

	return $sanitized;
}

/**
 * Check if current user is the MCP user.
 *
 * @since 0.1.0
 *
 * @return bool True if current user is MCP user.
 */
function is_mcp_user(): bool {
	$current_user = wp_get_current_user();

	if ( ! $current_user || ! $current_user->ID ) {
		return false;
	}

	return $current_user->user_login === Security\MCP_USER_LOGIN;
}

/**
 * Get post type labels as array.
 *
 * @since 0.1.0
 *
 * @param \WP_Post_Type $post_type Post type object.
 * @return array Post type labels.
 */
function get_post_type_labels( \WP_Post_Type $post_type ): array {
	$labels = (array) $post_type->labels;

	// Convert all values to strings.
	return array_map( 'strval', $labels );
}

/**
 * Get taxonomy labels as array.
 *
 * @since 0.1.0
 *
 * @param \WP_Taxonomy $taxonomy Taxonomy object.
 * @return array Taxonomy labels.
 */
function get_taxonomy_labels( \WP_Taxonomy $taxonomy ): array {
	$labels = (array) $taxonomy->labels;

	// Convert all values to strings.
	return array_map( 'strval', $labels );
}

/**
 * Format error for response.
 *
 * @since 0.1.0
 *
 * @param \WP_Error $error Error object.
 * @return array Formatted error.
 */
function format_error( \WP_Error $error ): array {
	return [
		'error'   => true,
		'code'    => $error->get_error_code(),
		'message' => $error->get_error_message(),
		'data'    => $error->get_error_data(),
	];
}
