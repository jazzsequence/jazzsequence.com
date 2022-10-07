<?php
/*
Plugin Name: HM MU Plugin Loader
Description: Loads the MU plugins required to run the site
Author: Human Made Limited
Author URI: http://hmn.md/
Version: 1.0
*/

if ( ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) ) {
	return;
}

/**
 * Fix cookie domain stuff.
 */
add_action( 'muplugins_loaded', function() {
	global $current_blog, $current_site;

	if ( false === stripos( $current_blog->domain, $current_site->cookie_domain ) ) {
		$current_site->cookie_domain = $current_blog->domain;
	}
} );

// Require the Composer autoloader.
require_once dirname( __FILE__, 3 ) . '/vendor/autoload.php';

$hm_mu_plugins = array(
	'cmb2/init.php',
	'dashboard-changelog/plugin.php',
);

foreach ( $hm_mu_plugins as $file ) {
	require_once dirname( __FILE__ ) . '/' . $file;
}
unset( $file );

add_action( 'pre_current_active_plugins', function() use ( $hm_mu_plugins ) {
	global $plugins, $wp_list_table;

	// Add our own mu-plugins to the page
	foreach ( $hm_mu_plugins as $plugin_file ) {
		$plugin_data = get_plugin_data( WPMU_PLUGIN_DIR . "/$plugin_file", false, false ); // Do not apply markup/translate as it'll be cached.

		if ( empty( $plugin_data['Name'] ) ) {
			$plugin_data['Name'] = $plugin_file;
		}

		$plugins['mustuse'][ $plugin_file ] = $plugin_data;
	}

	// Recount totals
	$GLOBALS['totals']['mustuse'] = count( $plugins['mustuse'] );

	// Only apply the rest if we're actually looking at the page
	if ( 'mustuse' !== $GLOBALS['status'] ) {
		return;
	}

	// Reset the list table's data
	$wp_list_table->items = $plugins['mustuse'];
	foreach ( $wp_list_table->items as $plugin_file => $plugin_data ) {
		$wp_list_table->items[ $plugin_file ] = _get_plugin_data_markup_translate( $plugin_file, $plugin_data, false, true );
	}

	$total_this_page = $GLOBALS['totals']['mustuse'];

	if ( $GLOBALS['orderby'] ) {
		uasort( $wp_list_table->items, array( $wp_list_table, '_order_callback' ) );
	}

	// Force showing all plugins
	// See https://core.trac.wordpress.org/ticket/27110
	$plugins_per_page = $total_this_page;

	$wp_list_table->set_pagination_args( array(
		'total_items' => $total_this_page,
		'per_page'    => $plugins_per_page,
	) );
} );

add_action( 'network_admin_plugin_action_links', function( $actions, $plugin_file, $plugin_data, $context ) use ( $hm_mu_plugins ) {
	if ( $context !== 'mustuse' || ! in_array( $plugin_file, $hm_mu_plugins, true ) ) {
		return;
	}

	$actions[] = sprintf( '<span style="color:#333">File: <code>%s</code></span>', $plugin_file );

	return $actions;
}, 10, 4 );

/**
 * Use the Altis logo on the login page.
 */
add_filter( 'jazzsequence.get_config', function( $config ) {
	$config['login-logo'] = 'wp-content/mu-plugins/altis-cms/assets/logo.svg';

	return $config;
}, 1, 1 );
