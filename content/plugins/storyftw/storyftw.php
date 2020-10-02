<?php
/**
 * Plugin Name: story|ftw
 * Plugin URI:  http://storyftw.com
 * Description: A full screen, mobile first, storytelling plugin for WordPress. Eliminate distractions and focus your reader on your story.
 * Version:     0.1.4
 * Author:      story|ftw
 * Author URI:  http://storyftw.com
 * Donate link: http://storyftw.com
 * License:     GPLv2+
 * Text Domain: storyftw
 * Domain Path: /languages
 */

/**
 * Copyright (c) 2014 story|ftw (email : justin@storyftw.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * Autoloads files with classes when needed
 * @since  0.1.0
 * @param  string $class_name Name of the class being requested
 */
function storyftw_autoload_classes( $class_name ) {
	if ( class_exists( $class_name, false ) || false === stripos( $class_name, 'StoryFTW_' ) ) {
		return;
	}

	$filename = strtolower( str_ireplace( 'StoryFTW_', '', $class_name ) );

	StoryFTW::include_file( $filename );
}
spl_autoload_register( 'storyftw_autoload_classes' );

/**
 * Main initiation class
 */
class StoryFTW {

	const VERSION = '0.1.4';
	public $minnified_suffix = '';
	public $cpts;
	public $admin;
	public $frontend;
	public $metaboxes;
	public $shortcodes;

	public static $single_instance = null;

	/**
	 * Creates or returns an instance of this class.
	 * @since  0.1.0
	 * @return StoryFTW A single instance of this class.
	 */
	public static function start() {
		if ( null === self::$single_instance ) {
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}

	/**
	 * Sets up our plugin
	 * @since  0.1.0
	 */
	private function __construct() {
		$this->minnified_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET['debug'] ) ? '' : '.min';

		StoryFTW::include_file( 'libraries/cmb/init' );

		$this->cpts = new StoryFTW_CPTs();
		// $this->admin = new StoryFTW_Admin( $this->cpts->stories );
		$this->frontend = new StoryFTW_Frontend( $this->cpts );
		$this->metaboxes = new StoryFTW_Metaboxes( $this->cpts );
		$this->shortcodes = new StoryFTW_Shortcodes( $this->frontend );

		$this->hooks();
	}

	public function hooks() {

		register_activation_hook( __FILE__, array( $this, '_activate' ) );
		register_deactivation_hook( __FILE__, array( $this, '_deactivate' ) );
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_init', array( $this, 'admin_hooks' ) );

		add_image_size( 'storyftw_bg', 1920, 1280 );

		$this->cpts->hooks();
		// $this->admin->hooks();
		$this->shortcodes->hooks();
		$this->metaboxes->hooks();

		if ( ! is_admin() ) {
			$this->frontend->hooks();
		}
	}

	/**
	 * Activate the plugin
	 */
	function _activate() {
		// Make sure any rewrite functionality has been loaded
		flush_rewrite_rules();
	}

	/**
	 * Deactivate the plugin
	 * Uninstall routines should be in uninstall.php
	 */
	function _deactivate() {

	}

	/**
	 * Init hooks
	 * @since  0.1.0
	 * @return null
	 */
	public function init() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'storyftw' );
		load_textdomain( 'storyftw', WP_LANG_DIR . '/storyftw/storyftw-' . $locale . '.mo' );
		load_plugin_textdomain( 'storyftw', false, self::dir( '/languages/' ) );
	}

	/**
	 * Hooks for the Admin
	 * @since  0.1.0
	 * @return null
	 */
	public function admin_hooks() {
	}

	/**
	 * Autoloads files with classes when needed
	 * @since  0.1.0
	 * @param  string $filename Name of the file to be included
	 */
	public static function include_file( $filename ) {
		$file = self::dir( 'includes/'. $filename .'.php' );
		if ( file_exists( $file ) ) {
			return include_once( $file );
		}
	}

	public static function dir( $path = '' ) {
		static $dir;
		$dir = $dir ? $dir : trailingslashit( dirname( __FILE__ ) );
		return $dir . $path;
	}

	public static function url( $path = '' ) {
		static $url;
		$url = $url ? $url : trailingslashit( plugin_dir_url( __FILE__ ) );
		return $url . $path;
	}

}

// init our class
StoryFTW::start();
