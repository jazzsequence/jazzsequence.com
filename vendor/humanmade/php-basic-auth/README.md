<img src="https://humanmade.com/content/themes/humanmade/lib/hm-pattern-library/assets/images/logos/logo-red.svg" width="100" alt="Human Made Logo" />

# PHP Basic Auth
[![GitHub release (latest by date)](https://img.shields.io/github/v/release/humanmade/PHPBasicAuth)](https://github.com/humanmade/PHPBasicAuth/releases) [![GitHub](https://img.shields.io/github/license/humanmade/PHPBasicAuth)](https://github.com/humanmade/PHPBasicAuth/blob/master/LICENSE) [![Build Status](https://travis-ci.com/humanmade/PHPBasicAuth.svg?branch=master)](https://travis-ci.com/humanmade/PHPBasicAuth)

Basic PHP authentication for Human Made Dev and Staging environments.

![screenshot of prompt in google chrome](https://p94.f3.n0.cdn.getcloudapp.com/items/ApujKOdA/Screenshot%202020-04-08%2009.30.46.png?v=0ac5da96009ca70458e433b9be9a0897)
Authentication prompt in Google Chrome

![screenshot of prompt in firefox](https://p94.f3.n0.cdn.getcloudapp.com/items/YEu1qdEP/Screenshot%202020-04-08%2009.30.11.png?v=51f9afefaf5b269ec5facbd105f96928)
Authentication prompt in Firefox

## Installation & Setup
The composer file is set up to assume you want to install this package with other WordPress must-use vendor plugins. These setup instructions assume that all your composer-required must-use plugins are stored in a main `/mu-plugins/vendor` directory and that you are using a `loader.php` file to require them. You may need to adjust the configuration if your environment is different.

After installation and setup, an option to override the default basic auth setting (detected by environment) will exist on the General settings page. This option allows you to disable the basic auth on dev or staging environments from the WordPress application. By default the option will detect the environment and be checked if no setting is saved.

![screenshot of new setting](https://p94.f3.n0.cdn.getcloudapp.com/items/P8uY1xqw/Screenshot+2020-01-07+15.55.50.png?v=93007848bc828da7bd3aa512553a1f17)

### Step 1
Install the plugin via `composer`.

```bash
composer require humanmade/php-basic-auth
```

### Step 2
Add `'vendor/php-basic-auth/plugin.php'` to the array of must-use plugins in the `loader.php` file in the root of your `/mu-plugins` directory. Make sure it is the _first_ item in the array.

The final result should look something like this:

```php
<?php
/**
 * Plugin Name: HM MU Plugin Loader
 * Description: Loads the MU plugins required to run the site
 * Author: Human Made Limited
 * Author URI: http://hmn.md/
 * Version: 1.0
 *
 * @package HM
 */

if ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) {
	return;
}

// Plugins to be loaded for any site.
$global_mu_plugins = [
	'vendor/php-basic-auth/plugin.php',
	/* ... other must-use plugins here ... */
];
```

### Step 3
Define a `HM_BASIC_AUTH_USER` and `HM_BASIC_AUTH_PW` wherever constants are defined in your project. This could be your main `wp-config.php` file or a separate `.config/constants.php` file.

**Note:** While not required, it's best to check that you are in a development environment before defining `HM_BASIC_AUTH_USER` and `HM_BASIC_AUTH_PW` to prevent the constant declarations from being defined in all environments. This adds an additional layer of protection against basic auth accidentally being loaded in production.

You may also want to disable basic authentication on local environments.

Your constant declarations should look something like this:

```php
// Check if we're in a dev environment but not local.
if (
	// HM_DEV is defined and true.
	( defined( 'HM_DEV' ) && HM_DEV ) &&
	// HM_LOCAL_DEV is either undefined or false.
	( ! defined( 'HM_LOCAL_DEV' ) || defined( 'HM_LOCAL_DEV' ) && ! HM_LOCAL_DEV )
) {
	// Set Basic Auth user and password for dev environments.
	define( 'HM_BASIC_AUTH_USER', 'myusername' );
	define( 'HM_BASIC_AUTH_PW', 'mypassword' );
}
```

### Step 4 (optional)
If you do not want to load the basic authentication check on local environments, and you have not already defined `HM_LOCAL_DEV` in your `wp-config-local.php` file, you should do that now.

```php
/**
 * Set the environment to local dev.
 */
defined( 'HM_LOCAL_DEV' ) or define( 'HM_LOCAL_DEV', true );
```

You should also add these lines to your `wp-config-local.sample.php`.

## Changelog

### 1.1.6
* Fix unit tests
* Update composer test script to use composer-installed version of phpunit
* Bail early if credentials aren't defined
* Allow production environments to possibly enable auth

### 1.1.5
* Fixed bug where the `hmauth_filter_dev_env` is ignored if credentials are already set.
* Added screenshots of the prompt in Chrome and Firefox to the readme.

### 1.1.4
* Added an exclusion for `WP_INSTALLING` which was resulting in a bug that was failing Altis healthchecks.

### 1.1.3
* Added an action hook to the `is_development_environment` check, to allow actions to be hooked in before checking the environment.

### 1.1.2
* Required `composer/installers` so custom install paths can be defined.

### 1.1.1
* Fixed a bug where the environment settings were getting short-circuited if the option was unset.

### 1.1
* Flipped the logic of the admin setting from checking to _disable_ basic authentication to checking to _enable_ basic authentication, and defaulting to environment-based settings.
* Added a `is_development_environment` function which includes an added check for `HM_ENV_TYPE` as well as arbitrary definitions that could be added by a filter.
* Updated "Basic Realm" to use the site name rather than "Access Denied"
* Disabled basic auth if any of the following WordPress constants are defined and true: `WP_CLI`, `DOING_AJAX`, `DOING_CRON`.
* Added unit tests
* Added Travis CI integration

### 1.0
* Initial release

## Credits

Created by Human Made to force authentication to view development and staging environments while still allowing those environments to be viewed in a logged-out state.

Maintained by [Chris Reynolds](https://github.com/jazzsequence).

---------------------

Made with ❤️ by [Human Made](https://humanmade.com)
