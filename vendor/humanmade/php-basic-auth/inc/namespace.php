<?php
/**
 * Basic Authentication
 *
 * Handles the WordPress hooks and callbacks for the admin override of the basic authentication as defined by the environment settings.
 *
 * @package HM\BasicAuth
 */

namespace HM\BasicAuth;

/**
 * Kick everything off.
 */
function bootstrap() {
	add_action( 'admin_init', __NAMESPACE__ . '\\register_settings' );
	add_action( 'init', __NAMESPACE__ . '\\require_auth' );
}

/**
 * Checks if we're in a development environment.
 *
 * @return bool Returns true if we are in a dev or staging environment, false if not.
 */
function is_development_environment() : bool {
	/**
	 * Allow an action to be hooked just before we check if we're in a dev
	 * environment.
	 *
	 * This allows us to define the constants externally if using the filter is
	 * too late.
	 *
	 * @since 1.1.3
	 */
	do_action( 'hmauth_action_before_dev_env_check' );

	$hm_dev        = defined( 'HM_DEV' ) && HM_DEV;
	$altis_dev     = defined( 'HM_ENV_TYPE' ) && in_array( HM_ENV_TYPE, [ 'development', 'staging' ] );

	/**
	 * Allow our environments to be filtered outside of the plugin.
	 *
	 * @param bool
	 */
	$other_dev = apply_filters( 'hmauth_filter_dev_env', false );

	$creds_defined = defined( 'HM_BASIC_AUTH_USER' ) && defined( 'HM_BASIC_AUTH_PW' );

	// Bail early if the credentials are missing.
	if ( ! $creds_defined ) {
		return false;
	}

	// Don't require auth for AJAX requests.
	$exclude_ajax       = ! defined( 'DOING_AJAX' ) || false === DOING_AJAX;
	// Don't require auth if we're in wp-cli.
	$exclude_wp_cli     = ! defined( 'WP_CLI' ) || false === WP_CLI;
	// Don't require auth when running cron jobs.
	$exclude_cron       = ! defined( 'DOING_CRON' ) || false === DOING_CRON;
	// Don't require auth when installing or doing Altis health checks.
	$exclude_installing = ! defined( 'WP_INSTALLING' ) || false === WP_INSTALLING;

	$exclude = $exclude_ajax && $exclude_wp_cli && $exclude_cron && $exclude_installing;

	if (
		// WordPress exclusions.
		$exclude &&
		// If any of the environment checks are true, we're in a dev environment.
		( $hm_dev || $altis_dev || $other_dev )
	) {
		return true;
	}

	return false;
}

/**
 * Enable settings on production.
 *
 * @return bool
 */
function enable_auth(): bool {
	if ( ! is_super_admin() && defined( 'HM_ENV_TYPE' ) && HM_ENV_TYPE === 'production' ) {
		return false;
	}

	return true;
}

/**
 * Register the basic auth setting and the new settings field, but only if we're in a dev environment.
 * We don't want basic auth in production.
 */
function register_settings() {
	if ( ! is_development_environment() || ! enable_auth() ) {
		return;
	}

	register_setting( 'general', 'hm-basic-auth', [ 'sanitize_callback' => __NAMESPACE__ . '\\basic_auth_sanitization_callback' ] );

	add_settings_field(
		'hm-basic-auth',
		__( 'Enable Basic Authentication', 'hm-basic-auth' ),
		__NAMESPACE__ . '\\basic_auth_setting_callback',
		'general',
		'default',
		[ 'label_for' => 'hm-basic-auth' ]
	);
}

/**
 * The basic auth override setting.
 */
function basic_auth_setting_callback() {
	$hm_dev  = is_development_environment() ? 'on' : 'off';
	$checked = get_option( 'hm-basic-auth' ) ?: $hm_dev;
	?>
	<input type="checkbox" name="hm-basic-auth" value="on" <?php checked( $checked, 'on' ); ?> />
	<span class="description">
		<?php esc_html_e( 'When checked, Basic Authentication will be required for this environment. The default is for this to be active on dev and staging environments.', 'hm-basic-auth' ); ?>
	</span>
	<?php
}

/**
 * Sanitization callback for the hm-basic-auth setting.
 *
 * Explicitly stores "off" if the setting has been turned off, and "on" if it has been activated.
 *
 * @param string $value "off" or "on" based on whether the checkbox was ticked.
 */
function basic_auth_sanitization_callback( $value ) : string {
	if ( empty( $value ) ) {
		return 'off';
	}

	return 'on';
}

/**
 * Require PHP Basic authentication.
 *
 * Basic auth username and password should be set wherever constants for the site are defined.
 */
function require_auth() {
	$basic_auth = get_option( 'hm-basic-auth' );

	// Bail if basic auth has been disabled or if the development environment isn't defined or explicitly false.
	if ( 'off' === $basic_auth || ! is_development_environment() ) {
		return;
	}

	// Check for a basic auth user and password.
	if ( defined( 'HM_BASIC_AUTH_PW' ) && defined( 'HM_BASIC_AUTH_USER' ) ) {
		header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
		$site_name = get_option( 'blogname' );

		$has_supplied_credentials = ! empty( $_SERVER['PHP_AUTH_USER'] ) && ! empty( $_SERVER['PHP_AUTH_PW'] );
		$is_not_authenticated     = (
			! $has_supplied_credentials ||
			$_SERVER['PHP_AUTH_USER'] !== HM_BASIC_AUTH_USER ||
			$_SERVER['PHP_AUTH_PW'] !== HM_BASIC_AUTH_PW
		);

		if ( $is_not_authenticated ) {
			header( 'HTTP/1.1 401 Authorization Required' );
			header( "WWW-Authenticate: Basic realm=\"$site_name development site login\"" );
			exit;
		}
	}
}
