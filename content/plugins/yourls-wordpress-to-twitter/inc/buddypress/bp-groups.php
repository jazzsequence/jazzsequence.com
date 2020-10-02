<?php

/**
 * YOURLS BP Groups functions
 */
 
 /**
 * Create a shorturl for a BP group
 *
 * @package YOURLS WordPress to Twitter
 * @since 1.5
 *
 * @param int $group_id The ID of the group whose page should be the URL for the shorturl
 * @param str $type 'pretty' if you want the shorturl slug to be created from the group slug,
 *   or from the $keyword param. Otherwise 'normal' will create a randomly generated URL, as per
 *   the service's API. Note that $type and $keyword only do anything with YOURLS (not bit.ly, etc)
 * @param str $keyword The desired 'keyword' or slug of the shorturl. Note that this param is
 *   ignored if $type is not set to 'pretty'. Defaults to the group slug if $type == 'pretty'
 */
function wp_ozh_yourls_create_bp_group_url( $group_id, $type = 'normal', $keyword = false ) {
	global $bp;	
	
	// Check plugin is configured
	$service = wp_ozh_yourls_service();
	if( !$service )
		return 'Plugin not configured: cannot find which URL shortening service to use';
	
	// Mark this post as "I'm currently fetching the page to get its title"
	if( $group_id && !groups_get_groupmeta( $group_id, 'yourls_shorturl' ) ) {
		groups_update_groupmeta( $group_id, 'yourls_fetching', 1 );
		groups_update_groupmeta( $group_id, 'yourls_shorturl', '&nbsp;' ); // temporary empty title to avoid loop on creating short URL
	}
	
	// Avoid a DB query if we can
	if ( isset( $bp->groups->current_group->id ) && $bp->groups->current_group->id == $group_id ) {
		$group = $bp->groups->current_group;
	} else {
		$group = new BP_Groups_Group( $group_id );
	}
	
	if ( empty( $group ) )
		return false;
	
	$url 	  = bp_get_group_permalink( $group );
	$title    = $group->name;
	
	// Only send a keyword if this is a pretty URL
	if ( 'pretty' == $type ) {
		if ( !$keyword )
			$keyword = $group->slug;
	} else {
		$keyword = false;
	}
	
	// Remove the limitation on duplicate shorturls
	// This is a temporary workaround
	add_filter( 'yourls_remote_params', 'wp_ozh_yourls_remote_allow_dupes' );
	$shorturl = wp_ozh_yourls_api_call( $service, $url, $keyword, $title );
	remove_filter( 'yourls_remote_params', 'wp_ozh_yourls_remote_allow_dupes' );
	
	// Remove fetching flag
	if( $group_id )
		groups_delete_groupmeta( $group_id, 'yourls_fetching' );

	// Store short URL in a custom field
	if ( $group_id && $shorturl ) {
		groups_update_groupmeta( $group_id, 'yourls_shorturl', $shorturl );

		if ( $keyword )
			groups_update_groupmeta( $group_id, 'yourls_shorturl_name', $keyword );
	}

	return $shorturl;
}

/**
 * Outputs the current group's shorturl in the header
 *
 * Don't like the way this looks? Put the following in your theme's functions.php:
 *
 *   remove_action( 'bp_before_group_header_meta', 'wp_ozh_yourls_display_group_url' );
 *
 * and then use the template tags wp_ozh_yourls_get_displayed_user_url() and
 * wp_ozh_yourls_edit_link() to create your own markup in your theme.
 *
 * @package YOURLS WordPress to Twitter
 * @since 1.5
 */
function wp_ozh_yourls_display_group_url() {
	$shorturl = wp_ozh_yourls_get_current_group_url();
	
	if ( $shorturl ) {
	?>
		<span class="highlight shorturl">
			<?php printf( __( 'Short URL: <code>%s</code>', 'wp-ozh-yourls' ), $shorturl ) ?> <?php if ( wp_ozh_user_can_edit_url() ) : ?>&nbsp;<?php wp_ozh_yourls_group_edit_link() ?><?php endif ?>
		</span>
	<?php
	}
}
add_action( 'bp_before_group_header_meta', 'wp_ozh_yourls_display_group_url' );

/**
 * Echo the content of wp_ozh_yourls_get_current_group_url()
 *
 * @package YOURLS WordPress to Twitter
 * @since 1.5
 */
function wp_ozh_yourls_current_group_url() {
	echo wp_ozh_yourls_get_current_group_url();
}
	/**
	 * Return the current group's shorturl
	 *
	 * @package YOURLS WordPress to Twitter
	 * @since 1.5
	 *
	 * @return str $url The shorturt
	 */
	function wp_ozh_yourls_get_current_group_url() {
		global $bp;
		
		$url = isset( $bp->groups->current_group->shorturl ) ? $bp->groups->current_group->shorturl : '';
		
		return $url;
	}

/**
 * Echo the content of wp_ozh_yourls_get_group_edit_link()
 *
 * @package YOURLS WordPress to Twitter
 * @since 1.5
 *
 * @param int $user_id The id of the user. Defaults to the displayed user, then to the loggedin user
 * @param str $return 'html' to return a full link, otherwise just retrieve the URL
 */
function wp_ozh_yourls_group_edit_link( $group_id = false, $return = 'html' ) {
	echo wp_ozh_yourls_get_group_edit_link( $group_id, $return );
}
	/**
	 * Return the URL to a group's Admin screen, where he can edit his shorturl
	 *
	 * @package YOURLS WordPress to Twitter
	 * @since 1.5
	 *
	 * @param int $group_id The id of the group. Defaults to the current group id
	 * @param str $return 'html' to return a full link, otherwise just retrieve the URL
	 * @return str $link The link
	 */
	 function wp_ozh_yourls_get_group_edit_link( $group_id = false, $return = 'html' ) {
	 	global $bp;
	 	
	 	// If no group_id is passed, first try to default to the current group
	 	if ( !$group_id ) {
	 		$group_id = !empty( $bp->groups->current_group->id ) ? $bp->groups->current_group->id : false;
	 		$group = !empty( $bp->groups->current_group ) ? $bp->groups->current_group : false;
	 	}
	 	
	 	// If there's no group_id, bail
	 	if ( !$group_id ) {
	 		return false;
	 	}
	 	
	 	// If a group has not been set yet, pull it up
	 	if ( !$group ) {
	 		$group = new BP_Groups_Group( $group_id );
	 	}
	 	
	 	if ( empty( $group ) ) {
	 		return false;
	 	}
	 	
	 	// Create the URL to the admin page
	 	$link = bp_get_group_permalink( $group ) . 'admin/group-settings/';
	 	
	 	// Add the markup if necessary
	 	if ( 'html' == $return ) {
	 		$link = sprintf( '<a href="%1$s">%2$s</a>', $link, __( 'Edit', 'wp-ozh-yourls' ) );	
	 	}
	 	
	 	return $link;
	 }

/**
 * GROUP SHORTURL EDITING
 */

/**
 * Renders the Edit field on the Group Settings page
 *
 * @package YOURLS WordPress to Twitter
 * @since 1.5
 */
function wp_ozh_yourls_render_group_edit_field() {
	if ( !wp_ozh_user_can_edit_url() )
		return;
	
	$shorturl_name = groups_get_groupmeta( bp_get_group_id(), 'yourls_shorturl_name' );

	?>
	
	<label for="shorturl"><?php _e( 'Short URL: ', 'wp-ozh-yourls' ) ?></label>
	<code><?php wp_ozh_yourls_shortener_base_url() ?></code><input type="text" name="shorturl" id="shorturl" value="<?php echo $shorturl_name ?>" class="settings-input" />
	<p class="description"><?php _e( 'Letters and numbers only.', 'wp-ozh-yourls' ) ?></p>
	
	<hr />
	<?php
}
add_action( 'bp_before_group_settings_admin', 'wp_ozh_yourls_render_group_edit_field' );

/**
 * Processes shorturl edits by the member and displays proper success/error messages
 *
 * @package YOURLS WordPress to Twitter
 * @since 1.5
 *
 * @param int $group_id Passed along by the groups_group_settings_edited hook
 */
function wp_ozh_yourls_save_group_edit( $group_id ) {
	global $bp;
	
	if ( isset( $_POST['shorturl'] ) ) {
		$shorturl_name = wp_ozh_yourls_sanitize_slug( untrailingslashit( trim( $_POST['shorturl'] ) ) );
		
		// No need to continue if the name is unchanged
		if ( $current_shorturl_name = groups_get_groupmeta( $group_id, 'yourls_shorturl_name' ) ) {
			if ( $current_shorturl_name == $shorturl_name )
				return;
		}
		
		// Check first to see if the requested shorturl_name has previously belonged to the
		// group
		$expand = wp_ozh_yourls_api_call_expand( wp_ozh_yourls_service(), $shorturl_name );
		$expand = (array)$expand;
		$group = new BP_Groups_Group( $group_id );
		
		$url_belongs_to_group = !empty( $expand['longurl'] ) && $expand['longurl'] == bp_get_group_permalink( $group );
		
		if ( !empty( $expand->longurl ) && !$url_belongs_to_group ) {
			// This URL is already taken
			bp_core_add_message( __( 'That URL is unavailable. Please choose another.', 'wp-ozh-yourls' ), 'error' );
		} else if ( empty( $expand->longurl ) && !wp_ozh_yourls_bp_slug_is_available( $shorturl_name, $group_id ) ) {
			// The URL is not yet taken, but it matches another group/user name, and
			// we don't want it to get snatched
			bp_core_add_message( __( 'That URL is unavailable. Please choose another.', 'wp-ozh-yourls' ), 'error' );	
		} else {
			// The URL appears to be available
		
			if ( !$url_belongs_to_group ) {
				// Remove the limitation on duplicate shorturls
				// This is a temporary workaround
				define( 'YOURLS_UNIQUE_URLS', false );
				add_filter( 'yourls_remote_params', 'wp_ozh_yourls_remote_allow_dupes' );
				
				// Try to create the URL
				$shorturl = wp_ozh_yourls_create_bp_group_url( $group_id, 'pretty', $shorturl_name );
				
				remove_filter( 'yourls_remote_params', 'wp_ozh_yourls_remote_allow_dupes' );
			} else {
				// If the URL already belongs to the group, no need to create a
				// new one
				$shorturl = $expand['shorturl'];
			}
			
			if ( !$shorturl ) {
				// Something has gone wrong. This URL must be off limits
				bp_core_add_message( __( 'That URL is unavailable. Please choose another.tt', 'wp-ozh-yourls' ), 'error' );
			}
			
			if ( $shorturl ) {
				groups_update_groupmeta( $group_id, 'yourls_shorturl', $shorturl );
				groups_update_groupmeta( $group_id, 'yourls_shorturl_name', $shorturl_name );
				
				// Just in case this needs to be refreshed
				$bp->groups->current_group->shorturl = $shorturl;
			}
		}
	}
}
add_action( 'groups_group_settings_edited', 'wp_ozh_yourls_save_group_edit' );


?>