<?php
/**<?php
/*
Plugin Name: jazzsequence Hide Instagram from Home
Author: Chris Reynolds
Author URI: https://jazzsequence.com
Version: 1.0
 */

/*
	Copyright (C) 2017  Chris Reynolds  chris@jazzsequence.com

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * Removes division of pressed grams from home page
 *
 * @param  object $query WP_Query object.
 * @return object        Filtered object.
 * @link   https://www.wpmayor.com/how-to-hide-or-remove-categories-from-a-wordpress-homepage/
 */
function js_exclude_division_of_pressed_grams( $query ) {
	if ( $query->is_home() || is_post_type_archive( 'post' ) && ! is_archive() ) { // phpcs:ignore Generic.CodeAnalysis.RequireExplicitBooleanOperatorPrecedence.MissingParentheses
		$query->set( 'cat', '-3089' );
	}
	return $query;
}

add_filter( 'pre_get_posts', 'js_exclude_division_of_pressed_grams' );
