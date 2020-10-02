<?php
/**
 * Altis SSO WordPress OAuth.
 *
 * @package altis/sso
 */

namespace Altis\SSO\WordPress;

use Altis;

/**
 * Set up action hooks.
 *
 * @return void
 */
function bootstrap() {
	add_action( 'plugins_loaded', __NAMESPACE__ . '\\load_plugin' );
}

/**
 * Load Oauth2 plugin.
 *
 * @return void
 */
function load_plugin() {
	$config = wp_parse_args(
		Altis\get_config()['modules']['sso']['wordpress'],
		[
			'server-rest-base' => '',
			'oauth2-client-id' => '',
			'sync-roles'       => '',
			'cookie'           => true,
		]
	);

	if ( ! empty( $config['server-rest-base'] ) ) {
		define( 'HM_DELEGATED_AUTH_REST_BASE', $config['server-rest-base'] );
	}

	if ( ! empty( $config['oauth2-client-id'] ) && ! empty( $config['cookie'] ) ) {
		define( 'HM_DELEGATED_AUTH_CLIENT_ID', $config['oauth2-client-id'] );
	}

	define( 'HM_DELEGATED_AUTH_LOGIN_TEXT', __( 'Login with WordPress SSO', 'altis' ) );
	add_filter( 'delegated_oauth.sync-roles', ( empty( $config['sync-roles'] ) || ! $config['sync-roles'] ) ? '__return_false' : '__return_true' );

	require_once Altis\ROOT_DIR . '/vendor/humanmade/delegated-oauth/plugin.php';
}
