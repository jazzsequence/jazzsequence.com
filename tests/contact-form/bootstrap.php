<?php
/**
 * PHPUnit bootstrap for the headless contact form mu-plugin tests.
 *
 * @package JazzSequence\Tests\ContactForm
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
 * Load the contact form mu-plugin for testing.
 */
function _load_contact_form_plugin(): void {
	require dirname( dirname( __DIR__ ) ) . '/wp-content/mu-plugins/contact-form.php';
}

tests_add_filter( 'muplugins_loaded', '_load_contact_form_plugin' );

require $_tests_dir . '/includes/bootstrap.php';
