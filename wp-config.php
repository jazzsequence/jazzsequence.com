<?php
define( 'WP_CACHE', true ) ;
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wordpress' );

/** MySQL database username */
define( 'DB_USER', 'wordpress' );

/** MySQL database password */
define('DB_PASSWORD', 'a430197fc8ea4597354e7ae6103fd95a0453fad74b14555f');

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY', '?k4P8TgljRFTE{8ChiRb8WJXeK}Hh6R#G(%OkmNzZQ#KDeAKsAEo^@e6TEy+Yfxw');
define('SECURE_AUTH_KEY', '?k4P8TgljRFTE{8ChiRb8WJXeK}Hh6R#G(%OkmNzZQ#KDeAKsAEo^@e6TEy+Yfxw');
define('LOGGED_IN_KEY', '?k4P8TgljRFTE{8ChiRb8WJXeK}Hh6R#G(%OkmNzZQ#KDeAKsAEo^@e6TEy+Yfxw');
define('NONCE_KEY', '?k4P8TgljRFTE{8ChiRb8WJXeK}Hh6R#G(%OkmNzZQ#KDeAKsAEo^@e6TEy+Yfxw');
define('AUTH_SALT', '?k4P8TgljRFTE{8ChiRb8WJXeK}Hh6R#G(%OkmNzZQ#KDeAKsAEo^@e6TEy+Yfxw');
define('SECURE_AUTH_SALT', '?k4P8TgljRFTE{8ChiRb8WJXeK}Hh6R#G(%OkmNzZQ#KDeAKsAEo^@e6TEy+Yfxw');
define('LOGGED_IN_SALT', '?k4P8TgljRFTE{8ChiRb8WJXeK}Hh6R#G(%OkmNzZQ#KDeAKsAEo^@e6TEy+Yfxw');
define('NONCE_SALT', '?k4P8TgljRFTE{8ChiRb8WJXeK}Hh6R#G(%OkmNzZQ#KDeAKsAEo^@e6TEy+Yfxw');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

define( 'MULTISITE', true );
define( 'SUBDOMAIN_INSTALL', true );
$base = '/';
define( 'PATH_CURRENT_SITE', '/' );
define( 'SITE_ID_CURRENT_SITE', 1 );
define( 'BLOG_ID_CURRENT_SITE', 1 );
define( 'SUNRISE', 'on' );

define( 'AS3CF_SETTINGS', serialize( [
    'provider' => 'do',
    'access-key-id' => 'LY234ZTZ22ZPEJDYGRVN',
    'secret-access-key' => 'pafOt6Bql+uLSgF7jBG5bawtuLCrqu8xWco+7kb90GY',
 ] ) );

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_DISPLAY', false );
define( 'WP_DEBUG_LOG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
