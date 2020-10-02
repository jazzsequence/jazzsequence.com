<?php

/**
 * Admin markup
 *
 * @package YOURLS WordPress to Twitter
 * @since 1.5
 */
function wp_ozh_yourls_bp_admin_markup() {
	// Don't show this to anyone but the super admin
	if ( !is_super_admin() )
		return;
	
	// Don't show this on blogs other than the root blog
	if ( BP_ROOT_BLOG != get_current_blog_id() )
		return;
	
	$ozh_yourls = get_option('ozh_yourls'); 
	
	$can_customize = wp_ozh_yourls_service_allows_custom_urls();
	
	// Set some of the checkmark indexes to avoid WP_DEBUG errors
	$indexes = array( 'bp_members', 'bp_members_pretty', 'bp_members_can_edit', 
			  'bp_groups', 'bp_groups_pretty', 'bp_groups_can_edit',
			  'bp_topics' );
			  
	foreach( $indexes as $index ) {
		if ( !isset( $ozh_yourls[$index] ) )
			$ozh_yourls[$index] = '';
	}

	?>
	
	<h3>BuddyPress settings <span class="h3_toggle expand" id="h3_buddypress">+</span> <span id="h3_check_buddypress" class="h3_check">*</span></h3> 

	<div class="div_h3" id="div_h3_buddypress">
	
	<h4><?php _e( 'Members', 'wp-ozh-yourls' ) ?></h4> 

	<table class="form-table">

	<tr valign="top">
	<th scope="row"><?php _e( 'Each member gets a short URL', 'wp-ozh-yourls' ) ?></th>
	<td>
		<input name="ozh_yourls[bp_members]" type="checkbox" <?php checked( $ozh_yourls['bp_members'], 'on' ) ?> />
		<span class="description"><?php _e( 'Each short URL will be created automatically the next time the member profile is viewed', 'wp-ozh-yourls' ) ?></span>
	</td>
	</tr>
	
	<?php if ( $can_customize ) : ?>
		<tr valign="top">
		<th scope="row"><?php _e( 'Create short URLs from usernames', 'wp-ozh-yourls' ) ?></th>
		<td>
			<input name="ozh_yourls[bp_members_pretty]" type="checkbox" <?php checked( $ozh_yourls['bp_members_pretty'], 'on' ) ?> />
			<span class="description"><?php printf( __( 'When checked, member short URLs will look like <code>%s<strong>username</strong></code>, rather than a random string', 'wp-ozh-yourls' ), wp_ozh_yourls_get_shortener_base_url() ) ?></span>
		</td>
		</tr>
		
		<tr valign="top">
		<th scope="row"><?php _e( 'Users can edit their short URLs', 'wp-ozh-yourls' ) ?></th>
		<td>
			<input name="ozh_yourls[bp_members_can_edit]" type="checkbox" <?php checked( $ozh_yourls['bp_members_can_edit'], 'on' ) ?> />
			<span class="description"><?php _e( 'You must set <code>define( \'YOURLS_UNIQUE_URLS\', false );</code> in <a href="http://yourls.org/#Config">your YOURLS configuration file.', 'wp-ozh-yourls' ) ?></span>
		</td>
		</tr>
	<?php endif ?>

	</table>
	
	
	<h4><?php _e( 'Groups', 'wp-ozh-yourls' ) ?></h4> 

	<table class="form-table">

	<tr valign="top">
	<th scope="row"><?php _e( 'Each group gets a short URL', 'wp-ozh-yourls' ) ?></th>
	<td>
		<input name="ozh_yourls[bp_groups]" type="checkbox" <?php checked( $ozh_yourls['bp_groups'], 'on' ) ?> /> 
		<span class="description"><?php _e( 'Each short URL will be created automatically the next time the group page is viewed', 'wp-ozh-yourls' ) ?></span>
	</td>
	</tr>
	
	<?php if ( $can_customize ) : ?>
		<tr valign="top">
		<th scope="row"><?php _e( 'Create short URLs from group slugs', 'wp-ozh-yourls' ) ?></th>
		<td>
			<input name="ozh_yourls[bp_groups_pretty]" type="checkbox" <?php checked( $ozh_yourls['bp_groups_pretty'], 'on' ) ?> />
			<span class="description"><?php printf( __( 'When checked, group short URLs will look like <code>%s<strong>group-name</strong></code>, rather than a random string', 'wp-ozh-yourls' ), wp_ozh_yourls_get_shortener_base_url() ) ?></span>
		</td>
		</tr>
		
		<tr valign="top">
		<th scope="row"><?php _e( 'Users can edit their short URLs', 'wp-ozh-yourls' ) ?></th>
		<td>
			<input name="ozh_yourls[bp_groups_can_edit]" type="checkbox" <?php checked( $ozh_yourls['bp_groups_can_edit'], 'on' ) ?> />
			<span class="description"><?php _e( 'You must set <code>define( \'YOURLS_UNIQUE_URLS\', false );</code> in <a href="http://yourls.org/#Config">your YOURLS configuration file.', 'wp-ozh-yourls' ) ?></span>
		</td>
		</tr>
	<?php endif ?>

	</table>
	
	
	</div> <!-- div_h3_buddypress -->
	
	<?php
}
add_action( 'ozh_yourls_admin_sections', 'wp_ozh_yourls_bp_admin_markup' );


?>