<?php
/**
 * PHPUnit bootstrap for the REST cache headers mu-plugin tests.
 *
 * @package JazzSequence\Tests\RestCache
 */

// Autoloader — root vendor directory.
$autoload = dirname( dirname( __DIR__ ) ) . '/vendor/autoload.php';

if ( file_exists( $autoload ) ) {
	require_once $autoload;
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
 * Load the REST cache headers mu-plugin.
 */
function _load_rest_cache_plugin(): void {
	require dirname( dirname( __DIR__ ) ) . '/wp-content/mu-plugins/rest-cache-headers.php';
}

tests_add_filter( 'muplugins_loaded', '_load_rest_cache_plugin' );

/**
 * Register a private post type and a non-public taxonomy.
 *
 * The allowlist is derived from what WordPress has registered, so the tests need
 * something that must NOT qualify in order to prove the derivation excludes it.
 * Without these, a test suite could only ever confirm that public things pass.
 */
function _register_rest_cache_fixtures(): void {
	register_post_type(
		'secret_thing',
		[
			'public'       => false,
			'show_in_rest' => true,
			'rest_base'    => 'secret-things',
		]
	);

	register_taxonomy(
		'secret_tax',
		'post',
		[
			'public'       => false,
			'show_in_rest' => true,
			'rest_base'    => 'secret-tax',
		]
	);
}

tests_add_filter( 'init', '_register_rest_cache_fixtures' );

require $_tests_dir . '/includes/bootstrap.php';
