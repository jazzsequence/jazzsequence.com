<?php
/**
 * Loads the WordPress environment and template.
 *
 * @package WordPress
 */

if ( !isset($wp_did_header) ) {

	$wp_did_header = true;

    ob_start();//2010-09-18 gofunnow.com added, it will Fix rss feed error "Error on line 2: The processing instruction target matching "[xX][mM][lL]" is not allowed." while burn feed from feedburner.com

	require_once( dirname(__FILE__) . '/wp-load.php' );

    ob_end_clean();//2010-09-18 gofunnow.com added, it will Fix rss feed error "Error on line 2: The processing instruction target matching "[xX][mM][lL]" is not allowed." while burn feed from feedburner.com

	wp();

	require_once( ABSPATH . WPINC . '/template-loader.php' );


}

?>