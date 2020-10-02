<?php
/*
Plugin Name: YOURLS: WordPress to Twitter
Plugin URI: http://planetozh.com/blog/my-projects/yourls-wordpress-to-twitter-a-short-url-plugin/
Description: Create short URLs for posts with <a href="http://yourls.org/" title="Your Own URL Shortener">YOURLS</a>
Author: Ozh
Author URI: http:/ozh.org/
Version: 1.6.1
*/

/* Release History :
 * 1.0:       Initial release
 * 1.1:       Fixed: template tag makes post previews die (more generally, plugin wasn't properly initiated when triggered from the public part of the blog). Thanks moggy!
 * 1.2:       Added: ping.fm support, unused at the moment because those fucktards from ping.fm just don't approve the api key.
              Added: template tag wp_ozh_yourls_raw_url()
			  Added: uninstall procedure
			  Added: "get url" button as on wp.com
			  Improved: using internal WP_Http class instead of cURL for posting to Twitter
			  Fixed: short URLs generated on pages or posts even if option unchecked in settings (thanks Viper007Bond for noticing)
			  Fixed: PEAR class was included without checking existence first, conflicting with Twitter Tools for instance (thanks Doug Stewart for noticing)
 * 1.2.1:     Fixed: oops, forgot to remove a test hook
 * 1.3:       Fixed: Don't generate short URLs on preview pages
              Fixed: Tweet when posting scheduled post or using the XMLRPC API
 * 1.3.1:     Added: option to add <link> in <real>
 * 1.3.2:     Fixed: compat with YOURLS 1.4
 * 1.3.3:     Fixed: compat with WP 2.9 & wp.me integration
 * 1.3.4:     Fixed: compat with WP 3.0, YOURLS 1.4.2
 * 1.4:       Fixed: compat with WP 3.0, YOURLS 1.4.3 & YOURLS 1.5
              Removed: support with YOURLS 1.3. Upgrade.
              Added: Ajax checks for YOURLS config, super cool.
              Added: OAuth support. Curse you, Twitter.
			  Added: Support for custom post type
			  Added: filters everywhere so you can hack without hacking
			  Added: lots of tweet template tokens
 * 1.4.1:     Fixed: notices when no or just one tag/category
              Fixed: don't load twitter oauth classes if already there
			  Added: filter for admin notice
 * 1.4.2:     Fixed: Application name on Twitter was not unique
 * 1.4.3:     Added: Built-in support for custom keyword with post custom field 'yourls-keyword'
 * 1.4.4:     Added: Both 'yourls-keyword' and 'yourls_keyword'
 * 1.4.5:     Fixed: Possible wrong shorturl when not on singular pages. Thanks Otto for the fix!
 * 1.4.6:     Changed: Logic to connect to Twitter. No one pass, should be simpler.
              Fixed (hopefully): Creating duplicates URL with YOURLS
			  Fixed: the "Show letters" toggable password fields on Chrome
			  Fixed: ressource now loaded in compliance with SSL pref
 * 1.4.7:     Added: More actions and filters
 * 1.4.8:     Added: More actions and filters
 * 1.4.9:     Removed: JavaScript bits on the options page, causing passwords to reset
              Removed: Unneeded JSON class
 * 1.5:	      Added: BuddyPress support (member and group URLs)
              Added: Localization support
              Fixed: A variety of PHP warnings
 * 1.5.1:     Fixed: Remove spaces from tags & categories before making hashtags. Thanks Milan Petrovic!
              Fixed: Compatibility with P2 theme. Thanks Milan Petrovic!
 * 1.5.2:     Added: Spanish translation. Thanks myhosting.com team!
 * 1.5.3:     Fixed: metaboxes now appear on custom post type pages
 * 1.5.4:     Fixed: two sprintf issues for the meta box rendering
              Fixed: Using post type singular label in the meta box
              Fixed: Not starting session if already started
			  Added: two more tags: %X{taxonomy} and %Y{taxonomy}.
			  Improved: tag replacement (when parsing %A, first check if tag is used, and than get's author data and replace)
			  All fixes in 1.5.4 from Milan Petrovic, thanks a bunch!
 * 1.5.5:     Fixed: Tweets now auto-fire correctly when posting via XML-RPC
 * 1.6:       Removed: Twitter functions
              Announced: End Of Life for this plugin
 * 1.6.1:     Fixed: Partial SVN commit, doh.
 */

/********************* DO NOT EDIT *********************/

global $wp_ozh_yourls;
if( !isset( $_SESSION ) )
	session_start();
require_once( dirname(__FILE__).'/inc/core.php' );


/******************** TEMPLATE TAGS ********************/

// Template tag: echo short URL for current post
function wp_ozh_yourls_url() {
	global $id;
	$short = esc_url( apply_filters( 'ozh_yourls_shorturl', wp_ozh_yourls_geturl( $id ) ) );
	if ($short) {
		$rel    = esc_attr( apply_filters( 'ozh_yourls_shorturl_rel', 'nofollow alternate shorturl shortlink' ) );
		$title  = esc_attr( apply_filters( 'ozh_yourls_shorturl_title', 'Short URL' ) );
		$anchor = esc_html( apply_filters( 'ozh_yourls_shorturl_anchor', $short ) );
		echo "<a href=\"$short\" rel=\"$rel\" title=\"$title\">$anchor</a>";
	}
}

// Template tag: echo short URL alternate link in <head> for current post. See http://revcanonical.appspot.com/ && http://shorturl.appjet.net/
function wp_ozh_yourls_head_linkrel() {
	global $post;
	$id = $post->ID;
	$type = get_post_type( $id );
	if( wp_ozh_yourls_generate_on( $type ) ) {
		$short = apply_filters( 'ozh_yourls_shorturl', wp_ozh_yourls_geturl( $id ) );
		if ($short) {
			$rel    = apply_filters( 'ozh_yourls_shorturl_linkrel', 'alternate shorturl shortlink' );
			echo "<link rel=\"$rel\" href=\"$short\" />\n";
		}
	}
}

// Template tag: return/echo short URL with no formatting
function wp_ozh_yourls_raw_url( $echo = false ) {
	global $id;
	$short = apply_filters( 'ozh_yourls_shorturl', wp_ozh_yourls_geturl( $id ) );
	if ($short) {
		if ($echo)
			echo $short;
		return $short;
	}
}

// Get or create the short URL for a post. Input integer (post id), output string(url)
function wp_ozh_yourls_geturl( $id ) {
	do_action( 'yourls_geturl' );
	// Hardcode this const to always poll the shortening service. Debug tests only, obviously.
	if( defined('YOURLS_ALWAYS_FRESH') && YOURLS_ALWAYS_FRESH ) {
		$short = null;
	} else {
		$short = get_post_meta( $id, 'yourls_shorturl', true );
	}

	// short URL never was not created before? let's get it now!
	if ( !$short && !is_preview() && !get_post_custom_values( 'yourls_fetching', $id) ) {
		// Allow plugin to define custom keyword
		$keyword = apply_filters( 'ozh_yourls_custom_keyword', '', $id );
		$short = wp_ozh_yourls_get_new_short_url( get_permalink( $id ), $id, $keyword );
	}

	return $short;
}

// Load the plugin's textdomain
function wp_ozh_yourls_load_plugin_textdomain() {
	load_plugin_textdomain( 'wp-ozh-yourls', false, 'yourls-wordpress-to-twitter/languages/' );
}

// Load BuddyPress functions if BP is active
function wp_ozh_yourls_load_bp_functions() {
	require_once( dirname( __FILE__ ) . '/inc/buddypress/bp-integration.php' );
}
add_action( 'bp_include', 'wp_ozh_yourls_load_bp_functions' );

/************************ HOOKS ************************/

// Check PHP 5 on activation and upgrade settings
register_activation_hook( __FILE__, 'wp_ozh_yourls_activate_plugin' );
function wp_ozh_yourls_activate_plugin() {
	if ( version_compare(PHP_VERSION, '5.0.0', '<') ) {
		deactivate_plugins( basename( __FILE__ ) );
		wp_die( 'This plugin requires PHP5. Sorry!' );
	}
}

// Conditional actions
if (is_admin()) {
	require_once( dirname(__FILE__).'/inc/options.php' );
	// Add menu page, init options, add box on the Post/Edit interface
	add_action('admin_menu', 'wp_ozh_yourls_add_page');
	add_action('admin_init', 'wp_ozh_yourls_admin_init');
	add_action('admin_init', 'wp_ozh_yourls_addbox', 10);
	// Handle AJAX requests
	add_action('wp_ajax_yourls-promote', 'wp_ozh_yourls_promote' );
	add_action('wp_ajax_yourls-reset', 'wp_ozh_yourls_reset_url' );
	add_action('wp_ajax_yourls-check', 'wp_ozh_yourls_check_yourls' );
	// Custom icon & plugin action link
	add_filter( 'plugin_action_links_'.plugin_basename(__FILE__), 'wp_ozh_yourls_plugin_actions', -10);
	add_filter( 'ozh_adminmenu_icon_ozh_yourls', 'wp_ozh_yourls_customicon' );
} else {
	add_action('init', 'wp_ozh_yourls_init', 1 );
}

// Handle new stuff published
add_action('new_to_publish', 'wp_ozh_yourls_newpost', 10, 1);
add_action('draft_to_publish', 'wp_ozh_yourls_newpost', 10, 1);
add_action('auto-draft_to_publish', 'wp_ozh_yourls_newpost', 10, 1);
add_action('pending_to_publish', 'wp_ozh_yourls_newpost', 10, 1);
add_action('future_to_publish', 'wp_ozh_yourls_newpost', 10, 1);

// Shortcut internal shortlink functions
add_filter( 'pre_get_shortlink', 'wp_ozh_yourls_wp_get_shortlink', 10, 3 );

// Load textdomain
add_action( 'init', 'wp_ozh_yourls_load_plugin_textdomain' );

