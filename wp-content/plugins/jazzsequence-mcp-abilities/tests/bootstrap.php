<?php
/**
 * PHPUnit bootstrap file for jazzsequence-mcp-abilities tests.
 *
 * Supports two environments:
 * - Local development: plugin is installed inside a full WordPress project,
 *   so the root vendor/autoload.php (4 levels up) is used.
 * - CI: plugin runs standalone; falls back to the plugin's own vendor/autoload.php.
 *
 * @package JazzSequence\MCP_Abilities
 */

// Composer autoloader — try root project first, fall back to plugin's own vendor.
$root_autoload   = dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) . '/vendor/autoload.php';
$plugin_autoload = dirname( __DIR__ ) . '/vendor/autoload.php';

if ( file_exists( $root_autoload ) ) {
	require_once $root_autoload;
} elseif ( file_exists( $plugin_autoload ) ) {
	require_once $plugin_autoload;
} else {
	exit( 'No autoloader found. Run composer install.' . PHP_EOL );
}

// WordPress test environment.
$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

/**
 * Load plugin for testing.
 */
function _manually_load_plugin() {
	// Load the plugin.
	require dirname( __DIR__ ) . '/jazzsequence-mcp-abilities.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';
