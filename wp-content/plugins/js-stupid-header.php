<?php
/**
 * Plugin Name: Stupid header ASCII art
 * Plugin URI: https://jazzsequence.com
 * Description: Displays a stupid ASCII art in the html header.
 * Version: 1.0
 * Author: Chris Reynolds
 * Author URI: https://github.com/jazzsequence
 * License: GPLv3
 */

/**
 * Copyright (c) 2020 Chris Reynolds (email : me@chrisreynolds.io)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

namespace jazzsequence\ASCII;

function init() {
	add_action( 'wp_head', __NAMESPACE__ . '\\output_ascii' );
}

function get_ascii() {
  $ascii_message = '<!--' . PHP_EOL;
  $ascii_message .= '
   __   ______   ______   ______   ______   ______   ______   __  __   ______   __   __   ______   ______
  /\ \ /\  __ \ /\___  \ /\___  \ /\  ___\ /\  ___\ /\  __ \ /\ \/\ \ /\  ___\ /\ "-.\ \ /\  ___\ /\  ___\
 _\_\ \\\\ \  __ \\\\/_/  /__\/_/  /__\ \___  \\\\ \  __\ \ \ \/\_\\\\ \ \_\ \\\\ \  __\ \ \ \-.  \\\\ \ \____\ \  __\
/\_____\\\\ \_\ \_\ /\_____\ /\_____\\\\/\_____\\\\ \_____\\\\ \___\_\\\\ \_____\\\\ \_____\\\\ \_\\\\"\_\\\\ \_____\\\\ \_____\
\/_____/ \/_/\/_/ \/_____/ \/_____/ \/_____/ \/_____/ \/___/_/ \/_____/ \/_____/ \/_/ \/_/ \/_____/ \/_____/
                                                                                                             ;;
  ' . PHP_EOL;
  $ascii_message .= 'I make websites and things.' . "\n\n";

  $ascii_message .= 'jazzsequence.com is powered by the following technologies:' . "\n\n";

  $ascii_message .= ' * WordPress' . PHP_EOL;
  $ascii_message .= ' * Altis DXP' . PHP_EOL;
  $ascii_message .= ' * Composer' . PHP_EOL;
  $ascii_message .= ' * Litespeed' . PHP_EOL;
  $ascii_message .= ' * Digital Ocean' . PHP_EOL;
  $ascii_message .= ' * Deploy HQ' . "\n\n";

  $ascii_message .= '...if you can read this, you\'re looking too hard.' . PHP_EOL;
  $ascii_message .= '-->';

  return $ascii_message;
}

function output_ascii() {
	echo get_ascii();
}

// Do the stuff.
add_action( 'plugins_loaded', __NAMESPACE__ . '\\init' );
