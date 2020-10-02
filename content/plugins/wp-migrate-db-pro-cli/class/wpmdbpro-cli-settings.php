<?php

/**
 * Wrapper for handling WP Migrate DB Pro options via the CLI
 */

require_once $GLOBALS['wpmdb_meta']['wp-migrate-db-pro']['abspath'] . '/class/wpmdb-cli.php';

class WPMDBPro_CLI_Settings extends WPMDB_CLI {

	protected $allowed_actions;
	protected $allowed_settings;
	protected $allowed_push_pull_values;
	protected $options_map;
	protected $wpmdbpro;

	function __construct( $plugin_file_path ) {
		$this->doing_cli_migration = true;
		$this->is_addon            = true;
		parent::__construct( $plugin_file_path );
		$this->allowed_actions          = array( 'get', 'update' );
		$this->allowed_settings         = array( 'license', 'push', 'pull', 'connection-key' );
		$this->allowed_push_pull_values = array( 'off', 'on' );

		//Map command line args to option keys
		$this->options_map = array(
			'push'           => 'allow_push',
			'pull'           => 'allow_pull',
			'connection-key' => 'key',
			'license'        => 'licence',
		);

		global $wpmdbpro;
		$this->wpmdb    = &$this->wpmdbpro;
		$this->wpmdbpro = $wpmdbpro;
	}

	/**
	 *
	 * Main method for handling the getting and updating of settings
	 *
	 * @param $args
	 *
	 * @return bool|void
	 */
	public function handle_setting( $args ) {
		/**
		 *
		 * $arg[0] = update | get
		 * $arg[1] = <push|pull|license|key>
		 * $arg[2] = <new value> - optional if 'update' is action
		 *
		 */
		$current_settings         = $this->settings;
		$allowed_actions          = $this->allowed_actions;
		$allowed_settings         = $this->allowed_settings;
		$allowed_push_pull_values = $this->allowed_push_pull_values;
		$options_map              = $this->options_map;

		// Either the action or setting name aren't passed
		if ( ! isset( $args[0] ) || ! isset( $args[1] ) ) {
			return false;
		}

		if ( ! in_array( $args[0], $allowed_actions ) ) {
			WP_CLI::error( sprintf( __( 'Invalid action parameter - `%s`', 'wp-migrate-db-pro-cli' ), $args[0] ) );

			return;
		}

		if ( ! in_array( $args[1], $allowed_settings ) ) {
			WP_CLI::error( sprintf( __( 'Invalid setting parameter - `%s`', 'wp-migrate-db-pro-cli' ), $args[1] ) );

			return;
		}

		// Handle updating of settings
		if ( 'update' == $args[0] ) {
			// $args[2] is the value to update the settings object with. If it's not set, stop.
			if ( ! isset( $args[2] ) ) {
				WP_CLI::error( __( 'Please pass a value to update.', 'wp-migrate-db-pro-cli' ) );
			}

			if ( 'push' == $args[1] || 'pull' == $args[1] ) {
				// Only allow valid push/pull values
				if ( ! in_array( $args[2], $allowed_push_pull_values ) ) {
					WP_CLI::error( sprintf( __( 'Invalid parameter for push/push settings. Value must be `on` or `off`.', 'wp-migrate-db-pro-cli' ), $args[1] ) );
					return;
				}

				$option_name = $options_map[ $args[1] ];
				$update      = $this->_cli_save_setting( $option_name, $args[2] );

				if ( $update ) {
					WP_CLI::success( sprintf( __( '%s setting updated.', 'wp-migrate-db-pro-cli' ), $args[1] ) );
				} else {
					WP_CLI::warning( sprintf( __( 'Setting unchanged.', 'wp-migrate-db-pro-cli' ), $args[1] ) );
				}
			} elseif ( 'connection-key' == $args[1] ) {
				WP_CLI::error( __( 'The connection-key cannot be set via the CLI.', 'wp-migrate-db-pro-cli' ) );
			} elseif ( 'license' == $args[1] ) {
				// Validates licence against dbrains api
				$licence_response = $this->_handle_licence( $args[2] );
				if ( true === $licence_response ) {
					WP_CLI::success( __( 'License updated.', 'wp-migrate-db-pro-cli' ) );
				} else if ( is_array( $licence_response ) ) {
					foreach ( $licence_response as $error ) {
						//Strip HTML, convert HTML entities to ASCII...
						WP_CLI::error( self::cleanup_message( $error ) );
					}
				}
			}
		// Handle getting of settings
		} elseif ( 'get' == $args[0] ) {
			// Because the options arguments are different format than the options keys, use the array map to get the option key
			$key = $options_map[ $args[1] ];

			// No need to pass the 3rd positional argument to a get command.
			if ( isset( $args[2] ) ) {
				WP_CLI::error( sprintf( __( 'Too many positional arguments: %s', 'wp-migrate-db-pro-cli' ), $args[2] ) );
			}

			// If there is a value stored for the given key...
			if ( isset( $current_settings[ $key ] ) && '' !== $current_settings[ $key ] ) {
				$setting = $current_settings[ $key ];
				if ( is_bool( $setting ) ) {
					$val = $allowed_push_pull_values[ $setting ];
				} else {
					$val = $setting;
				}
				WP_CLI::log( $val );
			} else {
				WP_CLI::warning( sprintf( __( 'No setting `%s` currently saved in the database.', 'wp-migrate-db-pro-cli' ), $key ) );
			}
		}
	}

	/**
	 *
	 * Save a WP Migrate DB Pro setting.
	 * If passing in a license, be sure to pass the value through _handle_license() first to verify the license.
	 *
	 * @param $setting_name
	 * @param $value
	 *
	 * @return mixed
	 */
	protected function _cli_save_setting( $setting_name, $value ) {
		$settings    = $this->settings;
		$new_setting = $value;

		if ( 'allow_push' === $setting_name || 'allow_pull' === $setting_name ) {
			$new_setting = ( 'on' == $value ) ? true : false;
		}else{
			$new_setting = sanitize_text_field( $new_setting ); //Sanitize value as update_site_option() doesn't sanitize non-core options.
		}

		$settings[ $setting_name ] = $new_setting;
		$update                    = update_site_option( 'wpmdb_settings', $settings );

		return $update;
	}

	/**
	 *
	 * Validates licence against dbrains api
	 *
	 * @param $licence
	 *
	 * @return bool
	 */
	protected function _handle_licence( $licence ) {

		$this->wpmdbpro->set_cli_migration();

		WP_CLI::log( __( 'Checking license key...', 'wp-migrate-db-pro-cli' ) );

		$_POST['action']      = 'wpmdb_activate_licence';
		$_POST['licence_key'] = $licence;
		$_POST['context']     = 'licence';

		//ajax_activate_licence() validates the license against the Delicious Brains API and sets the option in the database if valid. Returns an error otherwise.
		$licence_response = $this->wpmdbpro->ajax_activate_licence();

		$decoded_response = json_decode( $licence_response, true );

		if ( ( ! isset( $decoded_response['masked_licence'] ) && isset( $decoded_response['errors'] ) ) || ( isset( $decoded_response['masked_licence'] ) && isset( $decoded_response['errors'] ) ) ) {
			return $decoded_response['errors'];
		}

		return true;
	}
}