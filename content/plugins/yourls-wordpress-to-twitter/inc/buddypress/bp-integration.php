<?php

/**
 * BuddyPress functions for YOURLS
 */

// Require the Members integration 
require_once( dirname( __FILE__ ) . '/bp-members.php' );

// Require the Groups integration, if necessary
if ( bp_is_active( 'groups' ) )
	require_once( dirname( __FILE__ ) . '/bp-groups.php' );

// Require the admin functions
if ( is_admin() )
	require_once( dirname( __FILE__ ) . '/bp-admin.php' );

/**
 * Catch page requests, and fetch any shorturls that are called for by the current settings
 *
 * @package YOURLS WordPress to Twitter
 * @since 1.5
 */
function wp_ozh_yourls_maybe_create_url() {
	global $bp;
	
	$ozh_yourls = get_option( 'ozh_yourls' ); 
	
	// Members
	if ( isset( $ozh_yourls['bp_members'] ) ) {
		if ( $user_id = bp_displayed_user_id() ) {
			$shorturl = get_user_meta( $user_id, 'yourls_shorturl', true );
			
			// Check to see whether it's already created
			if ( trim( $shorturl ) ) {
				$bp->displayed_user->shorturl = trim( $shorturl );	
			} else {	
				$type = isset( $ozh_yourls['bp_members_pretty'] ) ? 'pretty' : false;
				
				$shorturl = wp_ozh_yourls_create_bp_member_url( $user_id, $type );
				$bp->displayed_user->shorturl = $shorturl;
			}
		}
	}
	
	// Groups
	if ( isset( $ozh_yourls['bp_groups'] ) ) {
		if ( bp_is_group() ) {
			// Check to see whether it's already created
			$shorturl = groups_get_groupmeta( $bp->groups->current_group->id, 'yourls_shorturl' );
			
			if ( trim( $shorturl ) ) {
				$bp->groups->current_group->shorturl = trim( $shorturl );
			} else {
				$ozh_yourls = get_option( 'ozh_yourls' ); 
				$type = isset( $ozh_yourls['bp_groups_pretty'] ) ? 'pretty' : false;
				
				$shorturl = wp_ozh_yourls_create_bp_group_url( $bp->groups->current_group->id, $type );
				$bp->groups->current_group->shorturl = $shorturl;
			}
		}
	}
}
add_action( 'wp', 'wp_ozh_yourls_maybe_create_url', 1 );

/**
 * Can the current user edit the short URL of the current object?
 *
 * @package YOURLS WordPress to Twitter
 * @since 1.5
 *
 * @return bool
 */
function wp_ozh_user_can_edit_url() {
	// Some services do not allow for custom URLs
	if ( !wp_ozh_yourls_service_allows_custom_urls() )
		return false;
	
	$ozh_yourls = get_option('ozh_yourls');

	// Next checks depend on the component
	if ( bp_is_group() ) {		
		if ( !isset( $ozh_yourls['bp_groups_can_edit'] ) )
			return false;

		if ( !bp_group_is_admin() )
			return false;
	}
	
	// Members component
	if ( bp_displayed_user_id() ) {		
		// Check to see whether the admin has allowed editing
		if ( !isset( $ozh_yourls['bp_members_can_edit'] ) )
			return false;
	
		// Access control
		if ( !is_super_admin() && !bp_is_my_profile() )
			return false;
	}

	return true;
}

/**
 * Check to see whether a given slug is available for a certain user or group
 *
 * Here's the logic behind this function. On existing installations of BuddyPress, group and user
 * shorturls will be created gradually, as their pages are visited. This happens automatically, and
 * will generally ensure unique shorturls. But if a user tries to request a custom shorturl, he
 * should be prevented from taking the natural URL of a user or group whose shorturl has not been
 * created yet. So this function is used to test whether or not the requested shorturl should be
 * reserved for a user or group who has not yet had their shorturl created.
 *
 * There is some funny business involved here, regarding the issue of hyphens. BuddyPress slugs,
 * especially for groups, often contain hyphens. Hyphens are not allowed by YOURLS, but WP doesn't
 * know about that until after the URL is already created. In order to prevent someone from stealing
 * the URL 'booneiscool' from the group 'boone-is-cool', therefore, we have to do a bit of funky
 * regex on the groups and users databases, to see if there are any existing entities that might
 * want that URL in the future.
 *
 * @package YOURLS WordPress to Twitter
 * @since 1.5
 *
 * @param str $slug The slug being checked for availability
 * @param int $item_id The group id, or the user id, of the slug requester
 * @return bool Returns true if the slug is available
 */
function wp_ozh_yourls_bp_slug_is_available( $slug, $item_id ) {
	global $wpdb, $bp;
	
	/**
	 * The easy cases first: exact matches
	 */
	 
	// Groups: is there a group with this exact slug, which is not the requester?
	$group_id = BP_Groups_Group::group_exists( $slug );
	if ( !empty( $group_id ) && $item_id != $group_id )
		return false;
	
	// Members: is there a member with this exact username, which is not the requester?
	$user_id = username_exists( $slug );
	if ( !empty( $user_id ) && $item_id != $user_id )
		return false;
	
	/**
	 * The hard cases: stripping hyphens
	 */
	
	// We'll make a regex with optional hyphens between each letter
	$slug_array = preg_split( '/-?/', $slug, -1, PREG_SPLIT_NO_EMPTY );
	$regex = '-?';
	
	foreach( $slug_array as $letter ) {
		$regex .= $letter . '-?';	
	}

	// Groups
	$maybe_groups = $wpdb->get_results( $wpdb->prepare( "SELECT id, slug FROM {$bp->groups->table_name} WHERE slug RLIKE '" . like_escape( $regex ) . "'" ) );
		
	// If there are any matches other than the requesting item, this slug is unavailable
	foreach( (array)$maybe_groups as $maybe_group ) {
		if ( $item_id != $maybe_group->id )
			return false;		
	}
	
	// Members
	$maybe_users = $wpdb->get_results( $wpdb->prepare( "SELECT ID, user_login FROM {$wpdb->users} WHERE user_login RLIKE '" . like_escape( $regex ) . "'" ) );
		
	// If there are any matches other than the requesting item, this slug is unavailable
	foreach( (array)$maybe_users as $maybe_user ) {
		if ( $item_id != $maybe_user->id )
			return false;		
	}
	
	// If we've gotten here, then the slug is available. Phew!
	return true;
}

/**
 * Print styles to the head of the document
 *
 * Hooked to wp_head to save an HTTP request. So sue me.
 *
 * @package YOURLS WordPress to Twitter
 * @since 1.5
 */
function wp_ozh_yoruls_print_bp_styles() {
	
	if ( !bp_is_group() && !bp_displayed_user_id() )
		return;
		
	?>
	
<style type="text/css">
span.shorturl { white-space: nowrap; display: inline-block; }
</style>
	
	<?php
}
add_action( 'wp_head', 'wp_ozh_yoruls_print_bp_styles' );


?>