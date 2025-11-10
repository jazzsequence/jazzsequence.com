<?php
/**
 * Plugin Name: Disallow Updates 
 * Description: Prevent update notification for plugin
 * Plugin URI: https://gist.github.com/rniswonger/ee1b30e5fd3693bb5f92fbcfabe1654d
 * Author: Ryan Niswonger 
 * Author URI: https://github.com/rniswonger
 * License: MIT
 *
 * MIT License

 * Copyright (c) 2022 Chris Reynolds <hello@chrisreynolds.io>

 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in 
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace jz\DisallowUpdates;

/**
 * Disable plugin updates for specific plugins.
 *
 * @param object $value The update transient object.
 * @return object Filtered update transient object.
 */
function disable_plugin_updates( $value ) {
	if ( isset( $value ) && is_object( $value ) ) {
		// TODO: If we need to disallow updates to other plugins we can create a loop of plugins to go through.
		if ( isset( $value->response['two-factor/two-factor.php'] ) ) {
			unset( $value->response['two-factor/two-factor.php'] );
		}
	}
	return $value;
}
add_filter( 'site_transient_update_plugins', __NAMESPACE__ . '\\disable_plugin_updates' );
