<?php
/**
 * Plugin Name: Disallow Updates 
 * Description: Prevent update notification for plugin
 * Plugin URI: https://gist.github.com/rniswonger/ee1b30e5fd3693bb5f92fbcfabe1654d
 * Author: Ryan Niswonger 
 * Author URI: https://github.com/rniswonger
 */

namespace jz\DisallowUpdates;

function disable_plugin_updates( $value ) {
  if ( isset($value) && is_object($value) ) {
    // TODO: If we need to disallow updates to other plugins we can create a loop of plugins to go through.
    if ( isset( $value->response['two-factor/two-factor.php'] ) ) {
      unset( $value->response['two-factor/two-factor.php'] );
    }
  }
  return $value;
}
add_filter( 'site_transient_update_plugins', __NAMESPACE__ . '\\disable_plugin_updates' );
