<?php
/**
 * PHPUnit bootstrap file for jazzsequence-mcp-abilities tests.
 *
 * @package JazzSequence\MCP_Abilities
 */

// Composer autoloader.
require_once dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) . '/vendor/autoload.php';

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
