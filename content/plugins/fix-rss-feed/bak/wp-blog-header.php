<?php
/**
 * Loads the WordPress environment and template.
 *
 * @package WordPress
 */

if ( !isset($wp_did_header) ) {

	$wp_did_header = true;

      ob_start();

	require_once( dirname(__FILE__) . '/wp-load.php' );

      ob_end_clean();

	wp();

	require_once( ABSPATH . WPINC . '/template-loader.php' );

}

?>