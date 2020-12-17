<?php
/**
 * Autoload files that aren't being autoloaded by composer, but are still required.
 */

$hm_autoload = [
	WP_PLUGIN_DIR . '/browser-security/inc/namespace.php',
];

foreach ( $hm_autoload as $plugin ) {
	require_once $plugin;
}
