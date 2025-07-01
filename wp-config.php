<?php
/**
 * The base configuration for WordPress
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */
// arbitrary change
// Load public and local server stuff.
require_once __DIR__ . '/server-config.php';

// Define WordPress constants.
define( 'WP_CACHE', true ) ;
define( 'MULTISITE', true );
define( 'SUBDOMAIN_INSTALL', true );
define( 'PATH_CURRENT_SITE', '/' );
define( 'SITE_ID_CURRENT_SITE', 1 );
define( 'BLOG_ID_CURRENT_SITE', 1 );

/**
 * Define Dashboard Changelog repository.
 *
 * @see https://github.com/jazzsequence/dashboard-changelog#jsdc_repository
 */
define( 'JSDC_REPOSITORY', 'jazzsequence/jazzsequence.com' );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
