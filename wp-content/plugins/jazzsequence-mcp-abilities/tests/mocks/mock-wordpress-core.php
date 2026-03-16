<?php
/**
 * Mock WordPress core functions for testing.
 *
 * @package JazzSequence\MCP_Abilities\Tests
 */

// Global hooks registry.
global $wp_filter, $wp_actions;
$wp_filter = [];
$wp_actions = [];

/**
 * Mock add_action function.
 *
 * @param string $hook_name Hook name.
 * @param callable $callback Callback function.
 * @param int $priority Priority.
 * @param int $accepted_args Number of arguments.
 * @return true
 */
function add_action( string $hook_name, $callback, int $priority = 10, int $accepted_args = 1 ): bool {
	global $wp_filter;

	if ( ! isset( $wp_filter[ $hook_name ] ) ) {
		$wp_filter[ $hook_name ] = [];
	}

	if ( ! isset( $wp_filter[ $hook_name ][ $priority ] ) ) {
		$wp_filter[ $hook_name ][ $priority ] = [];
	}

	$wp_filter[ $hook_name ][ $priority ][] = [
		'function' => $callback,
		'accepted_args' => $accepted_args,
	];

	return true;
}

/**
 * Mock do_action function.
 *
 * @param string $hook_name Hook name.
 * @param mixed ...$args Additional arguments.
 */
function do_action( string $hook_name, ...$args ): void {
	global $wp_filter, $wp_actions;

	if ( ! isset( $wp_actions[ $hook_name ] ) ) {
		$wp_actions[ $hook_name ] = 0;
	}
	$wp_actions[ $hook_name ]++;

	if ( ! isset( $wp_filter[ $hook_name ] ) ) {
		return;
	}

	ksort( $wp_filter[ $hook_name ] );

	foreach ( $wp_filter[ $hook_name ] as $priority => $callbacks ) {
		foreach ( $callbacks as $callback_data ) {
			call_user_func_array( $callback_data['function'], $args );
		}
	}
}

/**
 * Mock add_filter function.
 *
 * @param string $hook_name Hook name.
 * @param callable $callback Callback function.
 * @param int $priority Priority.
 * @param int $accepted_args Number of arguments.
 * @return true
 */
function add_filter( string $hook_name, $callback, int $priority = 10, int $accepted_args = 1 ): bool {
	return add_action( $hook_name, $callback, $priority, $accepted_args );
}

/**
 * Mock apply_filters function.
 *
 * @param string $hook_name Hook name.
 * @param mixed $value Value to filter.
 * @param mixed ...$args Additional arguments.
 * @return mixed
 */
function apply_filters( string $hook_name, $value, ...$args ) {
	global $wp_filter;

	if ( ! isset( $wp_filter[ $hook_name ] ) ) {
		return $value;
	}

	ksort( $wp_filter[ $hook_name ] );

	foreach ( $wp_filter[ $hook_name ] as $priority => $callbacks ) {
		foreach ( $callbacks as $callback_data ) {
			$value = call_user_func_array( $callback_data['function'], array_merge( [ $value ], $args ) );
		}
	}

	return $value;
}

/**
 * Mock has_filter function.
 *
 * @param string $hook_name Hook name.
 * @return int Number of callbacks registered.
 */
function has_filter( string $hook_name ): int {
	global $wp_filter;

	if ( ! isset( $wp_filter[ $hook_name ] ) ) {
		return 0;
	}

	$count = 0;
	foreach ( $wp_filter[ $hook_name ] as $priority => $callbacks ) {
		$count += count( $callbacks );
	}

	return $count;
}

/**
 * Mock esc_html__ function.
 *
 * @param string $text Text to translate.
 * @param string $domain Text domain.
 * @return string
 */
function esc_html__( string $text, string $domain = 'default' ): string {
	return $text;
}

/**
 * Mock wp_die function.
 *
 * @param string $message Error message.
 * @param string $title Error title.
 * @param array  $args Additional arguments.
 * @throws Exception Always throws exception with message.
 */
function wp_die( string $message = '', string $title = '', array $args = [] ): void {
	// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Mock function for testing.
	throw new Exception( $message );
}

/**
 * Mock wp_kses_post function.
 *
 * @param string $data Data to sanitize.
 * @return string
 */
function wp_kses_post( string $data ): string {
	return $data;
}

/**
 * Mock plugin_dir_path function.
 *
 * @param string $file Plugin file path.
 * @return string
 */
function plugin_dir_path( string $file ): string {
	return trailingslashit( dirname( $file ) );
}

/**
 * Mock plugin_dir_url function.
 *
 * @param string $file Plugin file path.
 * @return string
 */
function plugin_dir_url( string $file ): string {
	return 'http://example.com/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
}

/**
 * Mock trailingslashit function.
 *
 * @param string $string String to add trailing slash.
 * @return string
 */
function trailingslashit( string $string ): string {
	return rtrim( $string, '/\\' ) . '/';
}
