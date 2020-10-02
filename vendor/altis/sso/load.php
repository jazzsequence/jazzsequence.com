<?php
/**
 * Altis SSO Module.
 *
 * @package altis/sso
 */

namespace Altis\SSO; // phpcs:ignore

use Altis;

add_action( 'altis.modules.init', function () {
	$default_settings = [
		'enabled'   => true,
		'saml'      => false,
		'wordpress' => false,
	];
	Altis\register_module( 'sso', __DIR__, 'SSO', $default_settings, function () {
		$config = Altis\get_config()['modules']['sso'];
		if ( $config['saml'] ) {
			SAML\bootstrap();
		}
		if ( $config['wordpress'] ) {
			WordPress\bootstrap();
		}
	} );
} );
