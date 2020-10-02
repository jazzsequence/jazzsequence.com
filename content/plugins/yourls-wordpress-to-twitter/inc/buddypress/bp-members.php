<?php

/**
 * Create a shorturl for a BP member profile
 *
 * @package YOURLS WordPress to Twitter
 * @since 1.5
 *
 * @param int $user_id The ID of the user whose profile should be the URL for the shorturl
 * @param str $type 'pretty' if you want the shorturl slug to be created from the user's nicename,
 *   or from the $keyword param. Otherwise 'normal' will create a randomly generated URL, as per
 *   the service's API. Note that $type and $keyword only do anything with YOURLS (not bit.ly, etc)
 * @param str $keyword The desired 'keyword' or slug of the shorturl. Note that this param is
 *   ignored if $type is not set to 'pretty'. Defaults to the user's user_login if $type == 'pretty'
 */
function wp_ozh_yourls_create_bp_member_url( $user_id, $type = 'normal', $keyword = false ) {
	
	// Check plugin is configured
	$service = wp_ozh_yourls_service();
	if( !$service )
		return 'Plugin not configured: cannot find which URL shortening service to use';
	
	// Mark this post as "I'm currently fetching the page to get its title"
	if( $user_id && !get_user_meta( $user_id, 'yourls_shorturl', true ) ) {
		update_user_meta( $user_id, 'yourls_fetching', 1 );
		update_user_meta( $user_id, 'yourls_shorturl', '' ); // temporary empty title to avoid loop on creating short URL
	}
	
	$url 	  = bp_core_get_user_domain( $user_id );
	$userdata = get_userdata( $user_id );
	$title    = bp_core_get_user_displayname( $user_id );
	
	// Only send a keyword if this is a pretty URL
	if ( 'pretty' == $type ) {
		if ( !$keyword )
			$keyword = $userdata->user_login;
	} else {
		$keyword = false;
	}
	
	// Get short URL
	// Remove the limitation on duplicate shorturls
	// This is a temporary workaround
	add_filter( 'yourls_remote_params', 'wp_ozh_yourls_remote_allow_dupes' );
	$shorturl = wp_ozh_yourls_api_call( $service, $url, $keyword, $title );
	remove_filter( 'yourls_remote_params', 'wp_ozh_yourls_remote_allow_dupes' );
	
	// Remove fetching flag
	if( $user_id )
		delete_user_meta( $user_id, 'yourls_fetching' );

	// Store short URL in a custom field
	if ( $user_id && $shorturl ) {
		update_user_meta( $user_id, 'yourls_shorturl', $shorturl );
	
		if ( $keyword )
			update_user_meta( $user_id, 'yourls_shorturl_name', $keyword );
	}

	return $shorturl;
}

/**
 * Outputs the displayed user's shorturl in the header
 *
 * Don't like the way this looks? Put the following in your theme's functions.php:
 *
 *   remove_action( 'bp_before_member_header_meta', 'wp_ozh_yourls_display_user_url' );
 *
 * and then use the template tags wp_ozh_yourls_get_displayed_user_url() and
 * wp_ozh_yourls_edit_link() to create your own markup in your theme.
 *
 * @package YOURLS WordPress to Twitter
 * @since 1.5
 */
function wp_ozh_yourls_display_user_url() {
	$shorturl = wp_ozh_yourls_get_displayed_user_url();
	
	if ( $shorturl ) {
	?>
		<span class="highlight shorturl">
			<?php printf( __( 'Short URL: <code>%s</code>', 'wp-ozh-yourls' ), $shorturl ) ?> <?php if ( wp_ozh_user_can_edit_url() ) : ?>&nbsp;<?php wp_ozh_yourls_user_edit_link() ?><?php endif ?>
		</span>
	<?php
	}
}
add_action( 'bp_before_member_header_meta', 'wp_ozh_yourls_display_user_url' );

/**
 * Echo the content of wp_ozh_yourls_get_displayed_user_url()
 *
 * @package YOURLS WordPress to Twitter
 * @since 1.5
 */
function wp_ozh_yourls_displayed_user_url() {
	echo wp_ozh_yourls_get_displayed_user_url();
}
	/**
	 * Return the displayed user's shorturl
	 *
	 * @package YOURLS WordPress to Twitter
	 * @since 1.5
	 *
	 * @return str $url The shorturt
	 */
	function wp_ozh_yourls_get_displayed_user_url() {
		global $bp;
		
		$url = isset( $bp->displayed_user->shorturl ) ? $bp->displayed_user->shorturl : '';
		
		return $url;
	}

/**
 * Echo the content of wp_ozh_yourls_get_user_edit_link()
 *
 * @package YOURLS WordPress to Twitter
 * @since 1.5
 *
 * @param int $user_id The id of the user. Defaults to the displayed user, then to the loggedin user
 * @param str $return 'html' to return a full link, otherwise just retrieve the URL
 */
function wp_ozh_yourls_user_edit_link( $user_id = false, $return = 'html' ) {
	echo wp_ozh_yourls_get_user_edit_link( $user_id, $return );
}
	/**
	 * Return the URL to a user's General Settings screen, where he can edit his shorturl
	 *
	 * @package YOURLS WordPress to Twitter
	 * @since 1.5
	 *
	 * @param int $user_id The id of the user. Defaults to the displayed user, then to the
	 *     loggedin user
	 * @param str $return 'html' to return a full link, otherwise just retrieve the URL
	 * @return str $link The link
	 */
	 function wp_ozh_yourls_get_user_edit_link( $user_id = false, $return = 'html' ) {
	 	global $bp;
	 	
	 	// If no user_id is passed, first try to default to the displayed user
	 	if ( !$user_id ) {
	 		$user_id = !empty( $bp->displayed_user->id ) ? $bp->displayed_user->id : false;
	 		$domain = !empty( $bp->displayed_user->domain ) ? $bp->displayed_user->domain : false;
	 	}
	 	
	 	// If there's still no user_id, get the logged in user
	 	if ( !$user_id ) {
	 		$user_id = !empty( $bp->loggedin_user->id ) ? $bp->loggedin_user->id : false;
	 		$domain = !empty( $bp->loggedin_user->domain ) ? $bp->loggedin_user->domain : false;
	 	}
	 	
	 	// If there's *still* no displayed user, bail
	 	if ( !$user_id ) {
	 		return false;
	 	}
	 	
	 	// If a $user_id was passed manually to the function, we'll need to set $domain
	 	if ( !isset( $domain ) ) {
	 		$domain = bp_core_get_user_domain( $user_id );
	 	}
	 	
	 	// Create the URL to the settings page
	 	$link = $domain . BP_SETTINGS_SLUG . '/shorturl';
	 	
	 	// Add the markup if necessary
	 	if ( 'html' == $return ) {
	 		$link = sprintf( '<a href="%1$s">%2$s</a>', $link, __( 'Edit', 'wp-ozh-yourls' ) );	
	 	}
	 	
	 	return $link;
	 }
	
/**
 * USER SHORTURL EDITING
 */

/**
 * Hook into bp_setup_nav and add our Settings submenu
 *
 * @package YOURLS WordPress to Twitter
 * @since 1.5
 */
function wp_ozh_yourls_add_user_edit_tab() {
	global $bp;
	
	$settings_link = $bp->displayed_user->domain . $bp->settings->slug . '/';
	
	bp_core_new_subnav_item( array( 'name' => __( 'Short URL', 'buddypress' ), 'slug' => 'shorturl', 'parent_url' => $settings_link, 'parent_slug' => $bp->settings->slug, 'screen_function' => 'wp_ozh_yourls_add_user_edit_field', 'position' => 10, 'user_has_access' => wp_ozh_user_can_edit_url() ) );
}
add_action( 'bp_setup_nav', 'wp_ozh_yourls_add_user_edit_tab' );

/**
 * Catch the form submit, and hook the function that renders the page
 *
 * @package YOURLS WordPress to Twitter
 * @since 1.5
 */
function wp_ozh_yourls_add_user_edit_field() {
	global $current_user;

	if ( $_POST['submit'] ) {
		wp_ozh_yourls_save_user_edit();
	}

	add_action( 'bp_template_content', 'wp_ozh_yourls_render_user_edit_field' );

	bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
}

/**
 * Renders the Edit field on the General Settings page
 *
 * @package YOURLS WordPress to Twitter
 * @since 1.5
 */
function wp_ozh_yourls_render_user_edit_field() {
	global $bp_settings_updated;
	
	if ( !wp_ozh_user_can_edit_url() )
		return;
	
	$shorturl_name = get_user_meta( bp_displayed_user_id(), 'yourls_shorturl_name', true );

	?>
	
	<?php if ( $bp_settings_updated ) { ?>
		<div id="message" class="updated fade">
			<p><?php _e( 'Changes Saved.', 'buddypress' ) ?></p>
		</div>
	<?php } ?>

	<form action="<?php echo $bp->loggedin_user->domain . BP_SETTINGS_SLUG . '/shorturl' ?>" method="post" id="settings-form" class="standard-form">
		<h3><?php _e( 'Short URL', 'buddypress' ) ?></h3>
		
		<label for="shorturl"><?php _e( 'Short URL: ', 'wp-ozh-yourls' ) ?></label>
		<code><?php wp_ozh_yourls_shortener_base_url() ?></code><input type="text" name="shorturl" id="shorturl" value="<?php echo $shorturl_name ?>" class="settings-input" />
		<p class="description"><?php _e( 'Letters and numbers only.', 'wp-ozh-yourls' ) ?></p>
	
		<div class="submit">
			<input type="submit" name="submit" value="<?php _e( 'Save Changes', 'buddypress' ) ?>" id="submit" class="auto" />
		</div>

		<?php wp_nonce_field( 'bp_settings_shorturl' ) ?>

	</form>
	
	<?php
}

/**
 * Processes shorturl edits by the member and displays proper success/error messages
 *
 * @package YOURLS WordPress to Twitter
 * @since 1.5
 */
function wp_ozh_yourls_save_user_edit() {
	global $bp, $bp_settings_updated;
	
	if ( isset( $_POST['shorturl'] ) ) {
		check_admin_referer( 'bp_settings_shorturl' );
		
		$user_id = bp_displayed_user_id();
	
		$bp_settings_updated = false;
	
		$shorturl_name = wp_ozh_yourls_sanitize_slug( untrailingslashit( trim( $_POST['shorturl'] ) ) );
		
		// No need to continue if the name is unchanged
		if ( $current_shorturl_name = get_user_meta( $user_id, 'yourls_shorturl_name', true ) ) {
			if ( $current_shorturl_name == $shorturl_name ) {
				$bp_settings_updated = true;
				return;
			}
		}
		
		// Check first to see if the requested shorturl_name has previously belonged to the
		// user
		$expand = wp_ozh_yourls_api_call_expand( wp_ozh_yourls_service(), $shorturl_name );
		$expand = (array)$expand;
		
		$url_belongs_to_user = !empty( $expand['longurl'] ) && $expand['longurl'] == $bp->displayed_user->domain;
		
		if ( !empty( $expand->longurl ) && !$url_belongs_to_user ) {
			// This URL is already taken
			bp_core_add_message( __( 'That URL is unavailable. Please choose another.', 'wp-ozh-yourls' ), 'error' );
		} else if ( empty( $expand->longurl ) && !wp_ozh_yourls_bp_slug_is_available( $shorturl_name, $user_id ) ) {
			// The URL is not yet taken, but it matches another group/user name, and
			// we don't want it to get snatched
			bp_core_add_message( __( 'That URL is unavailable. Please choose another.', 'wp-ozh-yourls' ), 'error' );	
		} else {
			// The URL appears to be available
		
			if ( !$url_belongs_to_user ) {
				// Remove the limitation on duplicate shorturls
				// This is a temporary workaround
				add_filter( 'yourls_remote_params', 'wp_ozh_yourls_remote_allow_dupes' );
				
				// Try to create the URL
				$shorturl = wp_ozh_yourls_create_bp_group_url( $user_id, 'pretty', $shorturl_name );
				
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
				update_user_meta( $user_id, 'yourls_shorturl', $shorturl );
				update_user_meta( $user_id, 'yourls_shorturl_name', $shorturl_name );
				
				$bp_settings_updated = true;
				
				// Just in case this needs to be refreshed
				$bp->displayed_user->shorturl = $shorturl;
			}
		}
	}
}
add_action( 'bp_core_general_settings_after_save', 'wp_ozh_yourls_save_user_edit' );

/**
 * Removes the 'source' parameter from remote YOURLS requests
 *
 * There is an exception hardcoded into YOURLS that will never allow multiple shorturls to be
 * created for the same longurl, even if the YOURLS installation has set YOURLS_UNIQUE_URLS to
 * false, if the API request comes from the source 'plugin'. That means that BP users will not
 * be able to edit their auto-created shorturls. This filter removes the 'source' parameter as 
 * a workaround.
 *
 * @package YOURLS WordPress to Twitter
 * @since 1.5
 *
 * @param array $params The API params
 * @return array $params The API params, less 'source'
 */
function wp_ozh_yourls_remote_allow_dupes( $params ) {	
	$params['source'] = '';
	
	return $params;
}

?>