<?php
/**
 * Plugin Name: Recipe Box
 * Plugin URI:  https://jazzsequence.com
 * Description: Easily store and publish recipes in WordPress.
 * Version:     0.3.2
 * Author:      Chris Reynolds
 * Author URI:  https://jazzsequence.com
 * Donate link: https://jazzsequence.com
 * License:     GPLv3
 * Text Domain: recipe-box
 * Domain Path: /languages
 *
 * @link https://jazzsequence.com
 *
 * @package Recipe Box
 * @version 0.3.2
 */

/**
 * Copyright (c) 2019 Chris Reynolds (email : hello@chrisreynolds.io)
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
 *
 * @since  0.1
 * @param  string $class_name Name of the class being requested.
 */
function rb_autoload_classes( $class_name ) {
	if ( 0 !== strpos( $class_name, 'RB_' ) ) {
		return;
	}

	$filename = strtolower( str_replace(
		'_', '-',
		substr( $class_name, strlen( 'RB_' ) )
	) );

	Recipe_Box::include_file( $filename );
}
spl_autoload_register( 'rb_autoload_classes' );

/**
 * Main initiation class
 *
 * @since  0.1
 */
final class Recipe_Box {

	/**
	 * Current version
	 *
	 * @var  string
	 * @since  0.1
	 */
	const VERSION = '0.3.2';

	/**
	 * URL of plugin directory
	 *
	 * @var string
	 * @since 0.1
	 */
	protected $url = '';

	/**
	 * Path of plugin directory
	 *
	 * @var string
	 * @since 0.1
	 */
	protected $path = '';

	/**
	 * Plugin basename
	 *
	 * @var string
	 * @since 0.1
	 */
	protected $basename = '';

	/**
	 * Singleton instance of plugin
	 *
	 * @var Recipe_Box
	 * @since 0.1
	 */
	protected static $single_instance = null;

	/**
	 * Instance of RB_Public
	 *
	 * @since 0.1
	 * @var RB_Public
	 */
	protected $public;

	/**
	 * Instance of RB_Options
	 *
	 * @since 0.1
	 * @var RB_Options
	 */
	protected $options;

	/**
	 * Instance of RB_Import
	 *
	 * @since 0.2
	 * @var RB_Import
	 */
	protected $import;

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @since  0.1
	 * @return Recipe_Box A single instance of this class.
	 */
	public static function get_instance() {
		if ( null === self::$single_instance ) {
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}

	/**
	 * Sets up our plugin
	 *
	 * @since  0.1
	 */
	protected function __construct() {
		$this->basename = plugin_basename( __FILE__ );
		$this->url      = plugin_dir_url( __FILE__ );
		$this->path     = plugin_dir_path( __FILE__ );

		$this->plugin_classes();
	}

	/**
	 * Attach other plugin classes to the base plugin class.
	 *
	 * @since  0.1
	 */
	public function plugin_classes() {
		// Attach other plugin classes to the base plugin class.
		$this->cpt      = new RB_Recipe( $this );
		$this->taxonomy = new RB_Taxonomies( $this );
		$this->public   = new RB_Public( $this );
		$this->options  = new RB_Options( $this );
		$this->import   = new RB_Import( $this );
	} // END OF PLUGIN CLASSES FUNCTION

	/**
	 * Add hooks and filters
	 *
	 * @since  0.1
	 */
	public function hooks() {
		register_activation_hook( __FILE__, array( rb(), '_activate' ) );
		register_deactivation_hook( __FILE__, array( rb(), '_deactivate' ) );

		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Activate the plugin
	 *
	 * @since  0.1
	 */
	public function _activate() {
		// Make sure any rewrite functionality has been loaded.
		flush_rewrite_rules();
	}

	/**
	 * Deactivate the plugin
	 * Uninstall routines should be in uninstall.php
	 *
	 * @since  0.1
	 */
	public function _deactivate() {}

	/**
	 * Init hooks
	 *
	 * @since  0.1
	 */
	public function init() {
		if ( $this->check_requirements() ) {
			load_plugin_textdomain( 'recipe-box', false, dirname( $this->basename ) . '/languages/' );
		}
	}

	/**
	 * Check if the plugin meets requirements and
	 * disable it if they are not present.
	 *
	 * @since  0.1
	 * @return boolean result of meets_requirements
	 */
	public function check_requirements() {
		if ( ! $this->meets_requirements() ) {

			// Add a dashboard notice.
			add_action( 'all_admin_notices', array( $this, 'requirements_not_met_notice' ) );

			// Deactivate our plugin.
			add_action( 'admin_init', array( $this, 'deactivate_me' ) );

			return false;
		}

		return true;
	}

	/**
	 * Deactivates this plugin, hook this function on admin_init.
	 *
	 * @since  0.1
	 */
	public function deactivate_me() {
		deactivate_plugins( $this->basename );
	}

	/**
	 * Check that all plugin requirements are met
	 *
	 * @since  0.1
	 * @return boolean True if requirements are met.
	 */
	public static function meets_requirements() {
		// Do checks for required classes / functions
		// function_exists('') & class_exists('').
		// We have met all requirements.
		return true;
	}

	/**
	 * Adds a notice to the dashboard if the plugin requirements are not met
	 *
	 * @since  0.1
	 */
	public function requirements_not_met_notice() {
		// Output our error.
		echo '<div id="message" class="error">';
		echo '<p>' . sprintf( __( 'Recipe Box is missing requirements and has been <a href="%s">deactivated</a>. Please make sure all requirements are available.', 'recipe-box' ), admin_url( 'plugins.php' ) ) . '</p>';
		echo '</div>';
	}

	/**
	 * Magic getter for our object.
	 *
	 * @since  0.1
	 * @param  string $field Field to get.
	 * @throws Exception Throws an exception if the field is invalid.
	 * @return mixed
	 */
	public function __get( $field ) {
		switch ( $field ) {
			case 'version':
				return self::VERSION;
			case 'basename':
			case 'url':
			case 'path':
			case 'public':
			case 'options':
			case 'import':
				return $this->$field;
			default:
				throw new Exception( 'Invalid '. __CLASS__ .' property: ' . $field );
		}
	}

	/**
	 * Include a file from the includes directory
	 *
	 * @since  0.1
	 * @param  string $filename Name of the file to be included.
	 * @return bool   Result of include call.
	 */
	public static function include_file( $filename ) {
		$file = self::dir( 'includes/class-'. $filename .'.php' );
		if ( file_exists( $file ) ) {
			return include_once( $file );
		}
		return false;
	}

	/**
	 * This plugin's directory
	 *
	 * @since  0.1
	 * @param  string $path (optional) appended path.
	 * @return string       Directory and path
	 */
	public static function dir( $path = '' ) {
		static $dir;
		$dir = $dir ? $dir : trailingslashit( dirname( __FILE__ ) );
		return $dir . $path;
	}

	/**
	 * This plugin's url
	 *
	 * @since  0.1
	 * @param  string $path (optional) appended path.
	 * @return string       URL and path
	 */
	public static function url( $path = '' ) {
		static $url;
		$url = $url ? $url : trailingslashit( plugin_dir_url( __FILE__ ) );
		return $url . $path;
	}
}

/**
 * Grab the Recipe_Box object and return it.
 * Wrapper for Recipe_Box::get_instance()
 *
 * @since  0.1
 * @return Recipe_Box  Singleton instance of plugin class.
 */
function rb() {
	return Recipe_Box::get_instance();
}

// Kick it off.
add_action( 'plugins_loaded', array( rb(), 'hooks' ) );
