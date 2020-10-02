<?php

// Display notice prompting for settings
function wp_ozh_yourls_admin_notice() {
	global $plugin_page;
	if( $plugin_page == 'ozh_yourls' ) {
		$message = __( '<strong>YOURLS - WordPress</strong> configuration incomplete', 'wp-ozh-yourls' );
	} else {
		$url = menu_page_url( 'ozh_yourls', false );
		$message = sprintf( __( 'Please configure <strong>YOURLS - WordPress</strong> <a href="%s">settings</a> now', 'wp-ozh-yourls' ), $url );
	}
	$notice = <<<NOTICE
	<div class="error"><p>$message</p></div>
NOTICE;
	echo apply_filters( 'ozh_yourls_notice', $notice );
}

// Add page to menu
function wp_ozh_yourls_add_page() {
	// Loading CSS & JS *only* where needed. Do it this way too, goddamnit.
	$page = add_options_page( __( 'YOURLS WordPress', 'wp-ozh-yourls' ), 'YOURLS', 'manage_options', 'ozh_yourls', 'wp_ozh_yourls_do_page' );
	add_action("load-$page", 'wp_ozh_yourls_add_css_js_plugin');
	add_action("load-$page", 'wp_ozh_yourls_handle_action_links');
	// Add the JS & CSS for the char counter. This is too early to check wp_ozh_yourls_generate_on('post') or ('page')
	add_action('load-post.php', 'wp_ozh_yourls_add_css_js_post');
	add_action('load-post-new.php', 'wp_ozh_yourls_add_css_js_post');
	add_action('load-page.php', 'wp_ozh_yourls_add_css_js_post');
	add_action('load-page-new.php', 'wp_ozh_yourls_add_css_js_post');
}

// Add style & JS on the plugin page
function wp_ozh_yourls_add_css_js_plugin() {
	add_thickbox();
	$plugin_url = wp_ozh_yourls_pluginurl();
	wp_enqueue_script('yourls_js', $plugin_url.'res/yourls.js');
	wp_enqueue_script('wp-ajax-response');
	wp_enqueue_style('yourls_css', $plugin_url.'res/yourls.css');
}

// Add style & JS on the Post/Page Edit page
function wp_ozh_yourls_add_css_js_post() {
	global $pagenow;
	$current = str_replace( array('-new.php', '.php'), '', $pagenow);
	if ( wp_ozh_yourls_generate_on($current) ) {
		$plugin_url = wp_ozh_yourls_pluginurl();
		wp_enqueue_script('yourls_js', $plugin_url.'res/post.js');
		wp_enqueue_style('yourls_css', $plugin_url.'res/post.css');
	}
}

// Sanitize & validate options that are submitted
function wp_ozh_yourls_sanitize( $in ) {
	global $wp_ozh_yourls;
	
	// all options: sanitized strings
	$in = array_map( 'esc_attr', $in);
	
	// 0 or 1 for generate_on_*, tweet_on_*, link_on_*
	foreach( $in as $key=>$value ) {
		if( preg_match( '/^(generate|tweet)_on_/', $key ) ) {
			$in[$key] = ( $value == 1 ? 1 : 0 );
		}
	}

	// Get the shortener base URL based, on the new settings
	$in['shortener_base_url'] = wp_ozh_yourls_determine_base_url( $in );
	
	return $in;
}

// Check if plugin seems configured. Param: 'overall' return one single bool, otherwise return details
function wp_ozh_yourls_settings_are_ok( $check = 'overall' ) {
	global $wp_ozh_yourls;

	$check_yourls    = ( isset( $wp_ozh_yourls['location'] ) && !empty( $wp_ozh_yourls['location'] ) ? true : false );
	
	$check_buddypress = true; // We don't really care if BP is set up
	
	if( $check == 'overall' ) {
		$overall = $check_yourls && $check_buddypress ;
		return $overall;
	} else {
		return array( 'check_yourls' => $check_yourls, 'check_buddypress' => $check_buddypress );
	}
}

// Handle action links (reset or unlink)
function wp_ozh_yourls_handle_action_links() {
	$actions = array( 'reset', 'unlink' );
	if( !isset( $_GET['action'] ) or !in_array( $_GET['action'], $actions ) )
		return;

	$action = $_GET['action'];
	$nonce  = $_GET['_wpnonce'];
	
	if ( !wp_verify_nonce( $nonce, $action.'-yourls') )
		wp_die( "Invalid link" );
	
	global $wp_ozh_yourls;
		
	switch( $action ) {
	
		case 'unlink':
			wp_ozh_yourls_session_destroy();
			$wp_ozh_yourls['consumer_key'] =
				$wp_ozh_yourls['consumer_secret'] =
				$wp_ozh_yourls['yourls_acc_token'] = 
				$wp_ozh_yourls['yourls_acc_secret'] = '';
			update_option( 'ozh_yourls', $wp_ozh_yourls );
			break;

		case 'reset':
			wp_ozh_yourls_session_destroy();
			$wp_ozh_yourls = array();
			delete_option( 'ozh_yourls' );
			break;

	}
	
	wp_redirect( menu_page_url( 'ozh_yourls', false ) );
}

// Destroy session
function wp_ozh_yourls_session_destroy() {
	$_SESSION = array();
	if ( isset( $_COOKIE[session_name()] ) ) {
	   setcookie( session_name(), '', time()-42000, '/' );
	}
	session_destroy();
}

// Draw the option page
function wp_ozh_yourls_do_page() {
	$plugin_url = wp_ozh_yourls_pluginurl();
	
	$ozh_yourls = get_option('ozh_yourls'); 
	
	?>

	<div class="wrap">
	
	<?php /** ?>
	<pre><?php print_r(get_option('ozh_yourls')); ?></pre>
	<pre><?php print_r($_SESSION); ?></pre>
	<?php /**/ ?>

	<div class="icon32" id="icon-plugins"><br/></div>
	<h2><?php _e( 'YOURLS - WordPress', 'wp-ozh-yourls' ) ?></h2>
	
	<div id="y_logo">
		<div class="y_logo">
			<a href="http://yourls.org/"><img src="<?php echo $plugin_url; ?>/res/yourls-logo.png"></a>
		</div>
		<div class="y_text">
			<p><?php _e( '<a href="http://yourls.org/">YOURLS</a> is a free URL shortener service you can run on your webhost to have your own personal TinyURL.', 'wp-ozh-yourls' ) ?></p>
			<p><?php _e( 'This plugin is a bridge between <a href="http://yourls.org/">YOURLS</a> and your blog: when you submit a new post or page, your blog will tap into YOURLS to generate a short URL for it', 'wp-ozh-yourls' ) ?></p>
		</div>
	</div>
	
	<form method="post" action="options.php">
	<?php settings_fields('wp_ozh_yourls_options'); ?>

	<h3><?php _e( 'URL Shortener Settings', 'wp-ozh-yourls' ) ?></h3>

	<div class="div_h3" id="div_h3_yourls">
	<table class="form-table">

	<tr valign="top">
	<th scope="row"><?php _e( 'URL Shortener Service', 'wp-ozh-yourls' ) ?><span class="mandatory">*</span></th>
	<td>

	<label for="y_service"><?php _e( 'Your YOURLS installation is', 'wp-ozh-yourls' ) ?></label>
	<select name="ozh_yourls[location]" id="y_location" class="y_toggle">
	<option value="" <?php selected( '', $ozh_yourls['location'] ); ?> ><?php _e( 'Please select...', 'wp-ozh-yourls' ) ?></option>
	<option value="local" <?php selected( 'local', $ozh_yourls['location'] ); ?> ><?php _e( 'local, on the same webserver', 'wp-ozh-yourls' ) ?></option>
	<option value="remote" <?php selected( 'remote', $ozh_yourls['location'] ); ?> ><?php _e( 'remote, on another webserver', 'wp-ozh-yourls' ) ?></option>
	</select>
	
		<?php $hidden = ( $ozh_yourls['location'] == 'local' ? '' : 'y_hidden' ) ; ?>
		<div id="y_show_local" class="<?php echo $hidden; ?> y_location y_level2">
			<label for="y_path">Path to YOURLS <tt>config.php</tt></label> <input type="text" class="y_longfield" id="y_path" name="ozh_yourls[yourls_path]" value="<?php echo $ozh_yourls['yourls_path']; ?>"/> <span id="check_path" class="yourls_check button">check</span><br/>
			<em><?php _e( 'Example:', 'wp-ozh-yourls' ) ?></em> <tt>/home/you/site.com/yourls/includes/config.php</tt>
		</div>
		
		<?php $hidden = ( $ozh_yourls['location'] == 'remote' ? '' : 'y_hidden' ) ; ?>
		<div id="y_show_remote" class="<?php echo $hidden; ?> y_location y_level2">
			<label for="y_url"><?php _e( 'URL to the YOURLS API', 'wp-ozh-yourls' ) ?></label> <input type="text" id="y_url" class="y_longfield" name="ozh_yourls[yourls_url]" value="<?php echo $ozh_yourls['yourls_url']; ?>"/> <span id="check_url" class="yourls_check button">check</span><br/>
			<em><?php _e( 'Example:', 'wp-ozh-yourls' ) ?></em> <tt>http://site.com/yourls-api.php</tt><br/>
			<label for="y_yourls_login"><?php _e( 'YOURLS Login', 'wp-ozh-yourls' ) ?></label> <input type="text" id="y_yourls_login" name="ozh_yourls[yourls_login]" value="<?php echo $ozh_yourls['yourls_login']; ?>"/><br/>
			<label for="y_yourls_passwd"><?php _e( 'YOURLS Password', 'wp-ozh-yourls' ) ?></label> <input type="password" id="y_yourls_passwd" name="ozh_yourls[yourls_password]" value="<?php echo $ozh_yourls['yourls_password']; ?>"/><br/>
			<strong>Tip</strong>: you can create a new YOURLS user, eg <code>myblog</code>, for this. <a href="http://code.google.com/p/yourls/wiki/UsernamePasswords">Guide</a>
		</div>
		<?php
		wp_nonce_field( 'yourls', '_ajax_yourls', false );
		?>
	</div>
	


	</td>
	</tr>
	</table>
	</div><!-- div_h3_yourls -->
	

	<h3><?php _e( 'WordPress settings', 'wp-ozh-yourls' ) ?></h3> 

	<div class="div_h3" id="div_h3_wordpress">

	<h4><?php _e( 'When to generate a short URL', 'wp-ozh-yourls' ) ?></h4> 
	
	<table class="form-table">

	<?php
	$types = get_post_types( array('publicly_queryable' => 1 ), 'objects' );
	foreach( $types as $type=>$object ) {
		$name = $object->labels->singular_name;
		$generate_checked = isset( $ozh_yourls['generate_on_' . $type] ) && 1 == $ozh_yourls['generate_on_' . $type] ? 1 : 0;	
		$tweet_checked = isset( $ozh_yourls['tweet_on_' . $type] ) && 1 == $ozh_yourls['tweet_on_' . $type] ? 1 : 0;
		
		?>
		<tr valign="top">
		<th scope="row"><?php printf( __( 'New <strong>%s</strong> published', 'wp-ozh-yourls' ), $name ) ?></th>
		<td>
		<input class="y_toggle" id="generate_on_<?php echo $type; ?>" name="ozh_yourls[generate_on_<?php echo $type; ?>]" type="checkbox" value="1" <?php checked( '1', $generate_checked ); ?> /><label for="generate_on_<?php echo $type; ?>"> <?php _e( 'Generate short URL', 'wp-ozh-yourls' ) ?></label>
		</td>
		</tr>
	<?php } ?>

	</table>

	</div> <!-- div_h3_wordpress -->
	
	<div>
	<h3>Twitter settings?</h3>
	<p>This plugin does not support Twitter stuff any longer. If you want to auto tweet short URLs, use <a href="https://www.google.com/search?q=auto+tweet+wordpress+post">a plugin for that</a></p>
	</div>
	
	<?php do_action( 'ozh_yourls_admin_sections' ) ?>
	
	<?php
	$reset = add_query_arg( array('action' => 'reset'), menu_page_url( 'ozh_yourls', false ) );
	$reset = wp_nonce_url( $reset, 'reset-yourls' );
	?>

	<p class="submit">
	<input type="submit" class="button-primary y_submit" value="<?php _e('Save Changes') ?>" />
	<?php echo "<a href='$reset' id='reset-yourls' class='submitdelete'>Reset</a> all settings"; ?>
	</p>
	
	</form>

	</div> <!-- wrap -->

	
	<?php	
}

// Add meta boxes to post & page edit
function wp_ozh_yourls_addbox() {
	// What page are we on? (new Post, new Page, new custom post type?)
	if( isset ( $_GET["post"] ) ) {
		$post_id   = (int)$_GET["post"];
		$post_type = get_post_type( $post_id );
	} else {
		$post_type = isset( $_GET['post_type'] ) ? $_GET['post_type'] : 'post' ;
	}
	
	if ( wp_ozh_yourls_generate_on( $post_type ) ) {
		add_meta_box( 'yourlsdiv', 'YOURLS', 'wp_ozh_yourls_drawbox', $post_type, 'side', 'default' );
	}
	
	// TODO: do something with links. Or wait till they're considered custom post types. Yeah, we'll wait.
}


// Draw meta box
function wp_ozh_yourls_drawbox( $post ) {
	$type = $post->post_type;
	$status = $post->post_status;
	$id = $post->ID;
	$title = $post->post_title;
        $post_type = get_post_type_object($type);
        $type_label = $post_type->labels->singular_name;
	
	// Too early, young Padawan
	if ( $status != 'publish' ) {
		_e( '<p>Depending on <a href="options-general.php?page=ozh_yourls">configuration</a>, a short URL will be generated.</p>', 'wp-ozh-yourls' );
		return;
	}
	
	$shorturl = wp_ozh_yourls_geturl( $id );
	
	// Bummer, could not generate a short URL
	if ( !$shorturl ) {
		_e( '<p>Bleh. The URL shortening service you configured could not be reached as of now. This might be a temporary problem, please try again later!</p>', 'wp-ozh-yourls' );
		return;
	}
	
	// YOURLS part:	
	wp_nonce_field( 'yourls', '_ajax_yourls', false );
	echo '
	<input type="hidden" id="yourls_post_id" value="'.$id.'" />
	<input type="hidden" id="yourls_shorturl" value="'.$shorturl.'" />';
	
	echo '<p><strong>' . __( 'Short URL', 'wp-ozh-yourls' ) . '</strong></p>';
	echo '<div id="yourls-shorturl">';
	
	echo "<p>" . sprintf( __( 'This %1$s\'s short URL: <strong><a href="%2$s">%2$s</a></strong>', 'wp-ozh-yourls' ), $type, $shorturl ) . "</p>
	<p>" . __( "You can click Reset to generate another short URL if you picked another URL shortening service in the <a href='options-general.php?page=ozh_yourls'>plugin options</a>", 'wp-ozh-yourls' ) . "</p>";
	echo '<p style="text-align:right"><input class="button" id="yourls_reset" type="submit" value="' . __( 'Reset short URL', 'wp-ozh-yourls' ) . '" /></p>';
	echo '</div>';
}

?>