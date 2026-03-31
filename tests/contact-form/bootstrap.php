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
 * Load the contact form mu-plugin and Ninja Forms for testing.
 *
 * Ninja Forms is a Composer dependency of the project and is present at
 * wp-content/plugins/ninja-forms/ — load it so the action pipeline tests run
 * against the real plugin rather than stubs.
 */
function _load_contact_form_plugin(): void {
	require dirname( dirname( __DIR__ ) ) . '/wp-content/mu-plugins/contact-form.php';
}

/**
 * Load Ninja Forms from the project's Composer-managed plugin directory.
 */
function _load_ninja_forms(): void {
	$nf = dirname( dirname( __DIR__ ) ) . '/wp-content/plugins/ninja-forms/ninja-forms.php';
	if ( file_exists( $nf ) ) {
		require_once $nf;
	}
}

tests_add_filter( 'muplugins_loaded', '_load_contact_form_plugin' );
tests_add_filter( 'plugins_loaded', '_load_ninja_forms' );

/**
 * Run NF's activation routine after init so database tables and a test form exist.
 *
 * NF creates nf3_* tables and imports a default contact form during activation.
 * The WordPress test lib skips activation hooks, so we trigger this explicitly.
 */
function _activate_ninja_forms(): void {
	if ( function_exists( 'Ninja_Forms' ) && method_exists( Ninja_Forms(), 'activation' ) ) {
		Ninja_Forms()->activation();
	}
}

tests_add_filter( 'init', '_activate_ninja_forms', 20 );

require $_tests_dir . '/includes/bootstrap.php';
