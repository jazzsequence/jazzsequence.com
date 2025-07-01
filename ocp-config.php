<?php
/**
 * Object Cache Pro configuration file.
 */

/**
 * Get the OCP license key from the auth.json file.
 * 
 * @return string|null The OCP license key, or null if not found.
 */
function jz_get_token_from_auth_json() {
	// Load the auth.json file and return the token.
	$auth_json_path = __DIR__ . '/auth.json';
	if ( file_exists( $auth_json_path ) ) {
		$auth_data = json_decode( file_get_contents( $auth_json_path ), true );
		return $auth_data['token'] ?? null;
	}
	return null;
}

// Set the Object Cache Pro configuration.
define( 'WP_REDIS_CONFIG', [
	'token' => jz_get_token_from_auth_json(),
	'host' => '127.0.0.1',
	'port' => 6379,
	'database' => 0,
	'password' => null,
	'maxttl' => 86400 * 7, // 7 days
	'timeout' => 0.5,
	'read_timeout' => 0.5,
	'retries' => 3,
	'backoff' => 'smart',
	'split_alloptions' => true,
	'prefetch' => true,
	'serialize' => 'php',
	'compression' => 'gzip',
	'debug' => false,
	'save_commands' => false,
	'analytics' => [
		'enabled' => true,
		'persist' => true,
		'retention' => 3600, // 1 hour
		'footnote' => true,
	],
] );