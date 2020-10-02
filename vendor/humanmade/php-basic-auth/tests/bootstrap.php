<?php
/**
 * PHPUnit bootstrap file
 *
 * @package HM\BasicAuth
 */

// Define HM_BASIC_AUTH_USER and HM_BASIC_AUTH_PW.
defined( 'HM_BASIC_AUTH_USER' ) or define( 'HM_BASIC_AUTH_USER', 'admin' );
defined( 'HM_BASIC_AUTH_PW' ) or define( 'HM_BASIC_AUTH_PW', 'password' );

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you run .bin/install-wp-tests.sh ?" . PHP_EOL; // WPCS: XSS ok.
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the files being tested.
 */
require dirname( dirname( __FILE__ ) ) . '/inc/namespace.php';

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
