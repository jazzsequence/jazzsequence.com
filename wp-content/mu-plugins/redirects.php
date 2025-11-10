<?php
/**
 * Plugin Name: Redirects
 * Description: Adds domains allowed for redirects
 * Version: 0.1
 * Author: Chris Reynolds
 * Author URI: https://github.com/jazzsequence/jazzsequence.com
 * License: MIT
 */

namespace jz\Redirects;

/**
 * Adds allowed redirect hosts.
 *
 * @param array $hosts Existing allowed hosts.
 * @return array Filtered allowed hosts.
 */
function allowed_redirect_hosts( $hosts ) {
	$jz_allowed_hosts = [
		'www.dropbox.com',
	];
	return array_merge( $hosts, $jz_allowed_hosts );
}
add_filter( 'allowed_redirect_hosts', __NAMESPACE__ . '\\allowed_redirect_hosts' );
