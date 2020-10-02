<?php

class StoryFTW_Stories extends StoryFTW_CPT {

	public function __construct( $cpts ) {
		$this->cpts = $cpts;
		parent::__construct( $this->cpts->stories_args[0], $this->cpts->stories_args[1] );
	}

	public function hooks() {

		add_image_size( 'storyftw-footer-log', 640, 30 );
		add_filter( 'cmb2_meta_boxes', array( $this, 'story_main_box' ) );
		add_action( 'save_post', array( $this, 'save_pages_order' ), 10, 2 );
		add_action( 'wp_ajax_storyftw_get_permalink', array( $this, 'get_permalink' ), 10, 2 );
		add_action( 'attribute_escape', array( $this, 'maybe_add_permalink' ) );
		add_action( 'before_delete_post', array( $this, 'remove_story_pages' ) );

		if ( ! function_exists( 'cmb2_post_search_render_field' ) ) {
			StoryFTW::include_file( 'libraries/cmb2-post-search-field/cmb2_post_search_field' );
		}

		if ( $this->is_story_single() ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			if ( ! $this->is_new_story ) {

				if ( $this->has_pages() ) {
					add_action( 'edit_form_after_title', array( $this, 'view_story_button' ) );
					add_action( 'edit_form_after_editor', array( $this, 'story_pages_listing' ) );
					add_action( 'admin_footer', array( $this, 'add_js_template' ) );
				} else {
					add_action( 'edit_form_after_editor', array( $this, 'add_new_story_page_button' ) );
				}
			}
		}

		if ( $this->is_story_listing() ) {
			add_filter( 'post_row_actions', array( $this, 'add_new_story_page_link' ), 10, 2 );
			add_filter( 'post_row_actions', array( $this, 'maybe_add_view_story_link' ), 10, 2 );
		}
	}

	public function remove_story_pages( $post_id ) {
		if ( $this->post_type != get_post_type( $post_id ) ) {
			return;
		}

		$story_pages = $this->pages( array(
			'post_status' => 'any',
			'meta_value' => $post_id,
		) );

		foreach ( (array) $story_pages as $page_id ) {
			wp_delete_post( $page_id, true );
		}
	}

	// hack the post search ajax to add the permalink as a data param (fragile)
	public function maybe_add_permalink( $text ) {
		if ( ! defined( 'DOING_AJAX' ) ) {
			return $text;
		}

		if ( isset( $_REQUEST['action'], $_REQUEST['cmb2_post_search'] ) && 'find_posts' == $_REQUEST['action'] && is_numeric( $text ) ) {
			$permalink = get_permalink( absint( $text ) );

			if ( $permalink ) {
				return $text . '" data-permalink="' . esc_url( $permalink );
			}
		}

		return $text;
	}

	public function get_permalink() {
		if ( ! array_key_exists( 'post_id', $_REQUEST ) ) {
			wp_send_json_error( $_REQUEST );
		}
		$id = is_array( $_REQUEST['post_id'] ) ? array_pop( $_REQUEST['post_id'] ) : absint( $_REQUEST['post_id'] );
		$permalink = get_permalink( $id );
		if ( $permalink ) {
			wp_send_json_success( esc_url( $permalink ) );
		}

		wp_send_json_error( $_REQUEST );
	}

	public function view_story_button() {
		if ( ! ( $host = $this->get_story_host() ) ) {
			return;
		}

		?>
		<p><span id="view-post-btn" style="display: inline;"><a href="<?php echo esc_url( get_permalink( $host ) ); ?>" class="button button-small"><?php echo $this->labels->view_item; ?></a></span></p>
		<hr>
		<?php
	}

	public function save_pages_order( $post_id, $post ) {
		if ( $this->post_type != $post->post_type ) {
			return;
		}

		if ( ! isset( $_POST['story-page-order'] ) ) {
			return;
		}

		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			error_reporting( 0 );
		}

 		$story_pages = explode( ',', $_POST['story-page-order'] );

 		foreach ( (array) $story_pages as $order_index => $story_page_id ) {

 			if ( ! $post = get_post( $story_page_id ) ) {
 				continue;
 			}

 			wp_update_post( array(
				'ID'         => $story_page_id,
				'menu_order' => $order_index,
 			) );

 		}

	}

	public function story_main_box( array $meta_boxes ) {

		$pages = $this->pages( 10 );
		$context = ( is_array( $pages ) && count( $pages ) > 10 ) ? 'side' : 'normal';

		$meta_boxes['storyftw_story_colors_box'] = array(
			'id'           => 'storyftw_story_colors_box',
			'title'        => __( 'Story Colors', 'storyftw' ),
			'object_types' => array( $this->post_type ), // Post type
			'context'      => 'side',
			'priority'     => 'low',
			'fields'       => array(
				array(
					'name' => __( 'Default Story Background Color', 'storyftw' ),
					'desc' => __( "Story pages will retain the previous story page's background by default. Setting this color will override that behavior.", 'storyftw' ),
					'id'   => $this->prefix . 'fallback_bg_color',
					'type' => 'colorpicker',
				),
				array(
					'name' => __( 'Story Palette', 'storyftw' ),
					'desc' => __( 'Add some default palette colors for your story here.', 'storyftw' ),
					'id'   => $this->prefix . 'palettes',
					'type' => 'colorpicker',
					'repeatable' => true,
					'options' => array(
						'add_row_text' => __( 'Add Palette', 'storyftw' ),
					),
				),
				array(
					'before_row' => $this->advanced_open,
					'desc'      => __( '"Start" button text & navigation arrows color', 'storyftw' ),
					'id'        => $this->prefix . 'arrow_color',
					'type'      => 'colorpicker',
					'default'   => '#ffffff',
				),
				array(
					'desc'      => __( 'Link color', 'storyftw' ),
					'id'        => $this->prefix . 'link_color',
					'type'      => 'colorpicker',
				),
				array(
					'desc'      => __( 'Footer title color', 'storyftw' ),
					'id'        => $this->prefix . 'footer_text_color',
					'type'      => 'colorpicker',
					'default'   => '#ffffff',
				),
				array(
					'desc'      => __( 'Footer button text color', 'storyftw' ),
					'id'        => $this->prefix . 'footer_button_text_color',
					'type'      => 'colorpicker',
					'default'   => '#ffffff',
					'after_row' => $this->advanced_close,
				),
			),
		);

		$meta_boxes['storyftw_story_main_box'] = array(
			'id'           => 'storyftw_story_main_box',
			'title'        => __( 'Story Settings', 'storyftw' ),
			'object_types' => array( $this->post_type ), // Post type
			'context'      => 'normal',
			'priority'     => 'low',
			'fields'       => array(
				array(
					'id'   => $this->prefix . 'plugin_version',
					'default' => StoryFTW::VERSION,
					'type' => 'hidden',
				),
				array(
					'desc'    => __( 'Story Footer Options', 'storyftw' ),
					'id'      => $this->prefix . 'title',
					'type'    => 'title',
				),
				array(
					'name'    => __( 'Social Sharing', 'storyftw' ),
					'desc'    => __( 'Include social share buttons?', 'storyftw' ),
					'id'      => $this->prefix . 'social',
					'type'    => 'multicheck',
					'select_all_button' => false,
					'escape_cb' => array( $this, 'set_social_default' ),
					// 'default' => array( 'twitter', 'facebook' ),
					'options' => array(
						'twitter'  => __( 'Twitter', 'storyftw' ),
						'facebook' => __( 'Facebook', 'storyftw' ),
					),
				),
				array(
					'name'      => __( 'Table of Contents button', 'storyftw' ),
					// 'desc'   => __( 'Toggle button', 'storyftw' ),
					'id'        => $this->prefix . 'enable_toc',
					'type'      => 'checkbox',
					'escape_cb' => array( $this, 'set_checked_by_default' ),
				),
				array(
					'name'      => __( 'Story Title/Logo', 'storyftw' ),
					'id'        => $this->prefix . 'footer_title',
					'type'      => 'checkbox',
					'escape_cb' => array( $this, 'set_checked_by_default' ),
				),
				array(
					'name'       => __( 'Tweet Text', 'storyftw' ),
					'desc'       => __( 'Default tweet text for sharing.', 'storyftw' ),
					'id'         => $this->prefix . 'tweet_text',
					'type'       => 'textarea_small',
					'before_row' => $this->advanced_open,
				),
				array(
					'name' => __( 'Story Redirect', 'storyftw' ),
					'desc' => __( 'Search for and select a post or page to redirect to after final page.', 'storyftw' ),
					'id'   => $this->prefix . 'story_redirect',
					'type' => 'post_search_text',
				),
				array(
					'name'      => __( '"Start" button text', 'storyftw' ),
					'desc'      => __( 'Displays next to the navigation arrow on the first story page.', 'storyftw' ),
					'id'        => $this->prefix . 'coach_text',
					'default'   => __( 'Start', 'storyftw' ),
					'type'      => 'text',
				),
				array(
					'name' => __( 'Enter scripts or code you would like output to <code>wp_head()</code>', 'storyftw' ),
					'desc' => sprintf( __( 'The <code>wp_head</code> hook executes immediately before the closing %1$s tag in the document source.', 'storyftw' ), '<code>'. htmlentities( '</head>' ) .'</code>' ),
					'id'   => $this->prefix . 'header_scripts',
					'type' => 'textarea_code',
					'show_on_cb' => array( $this, 'check_user' ),
				),
				array(
					'name' => __( 'Enter scripts or code you would like output to <code>wp_footer()</code>', 'storyftw' ),
					'desc' => sprintf( __( 'The <code>wp_footer()</code> hook executes immediately before the closing %1$s tag in the document source.', 'storyftw' ), '<code>'. htmlentities( '</body>' ) .'</code>' ),
					'id'   => $this->prefix . 'footer_scripts',
					'type' => 'textarea_code',
					'show_on_cb' => array( $this, 'check_user' ),
				),
				array(
					'name' => __( "Include your theme's stylesheet?", 'storyftw' ),
					'desc' => __( "There is a possibility that your theme's stylesheet will conflict with the Story's presentation.", 'storyftw' ),
					'id'   => $this->prefix . 'include_theme_style',
					'type' => 'checkbox',
				),
				array(
					'name' => __( 'Use stronger css selectors?', 'storyftw' ),
					'desc' => __( "You may want to enable if you're including your theme's stylesheet.", 'storyftw' ),
					'id'   => $this->prefix . 'css_override',
					'type' => 'checkbox',
				),
				array(
					'name' => __( 'Remove WordPress Head hook from Story\'s head?', 'storyftw' ),
					'desc' => __( 'Enabling this will remove other plugin functionality hooked into <code>wp_head();</code>. You may need to enable this option if other plugins or your theme is conflicting with story pages.', 'storyftw' ),
					'id'   => $this->prefix . 'disable_wp_head',
					'type' => 'checkbox',
				),
				array(
					'name' => __( 'Remove WordPress Footer hook from Story\'s footer?', 'storyftw' ),
					'desc' => __( 'Enabling this will remove other plugin functionality hooked into <code>wp_footer();</code>. You may need to enable this option if other plugins or your theme is conflicting with story pages.', 'storyftw' ),
					'id'   => $this->prefix . 'disable_wp_footer',
					'type' => 'checkbox',
					'after_row' => $this->advanced_close,
				),
			),
		);

		$meta_boxes['storyftw_footer_thumb_box'] = array(
			'id'           => 'storyftw_footer_thumb_box',
			'title'        => __( 'Footer Logo', 'storyftw' ),
			'object_types' => array( $this->post_type ),
			'priority'     => 'low',
			'context'     => 'side',
			'show_names'   => false,
			'fields'       => array(
				array(
					'id'           => $this->prefix . 'footer_logo',
					'desc'         => __( 'Adding a footer logo will replace the Story title in the footer bar.', 'storyftw' ),
					'type'         => 'file',
					'preview_size' => array( 243, 243 ),
					'options'      => array(
						'url' => false,
					),
				),
				array(
					'id'   => $this->prefix . 'footer_url',
					'desc' => __( 'Footer logo/title link', 'storyftw' ),
					'type' => 'post_search_text',
					'attributes' => array(
						'style' => 'width: 88%;',
					),
				),
			),
		);

		return $meta_boxes;
	}

	public function check_user() { return current_user_can( 'manage_options' ); }

	public function add_new_story_page_button() {
		?>
		<h2 class="new-story-page-h2"><a href="<?php echo esc_url( $this->new_story_page_url() ); ?>'" class="new-story-page add-new-h2 dashicons-before dashicons-plus"> <?php _e( 'Add a page to this story', 'storyftw' ); ?></a></h2>
		<?php
	}

	public function enqueue_scripts() {
		$StoryFTW = StoryFTW::start();
		$version = ! $StoryFTW->minnified_suffix ? time() : StoryFTW::VERSION;

		wp_enqueue_script( 'story-pages', StoryFTW::url( "assets/js/story-pages{$StoryFTW->minnified_suffix}.js" ), array( 'jquery', 'wp-backbone' ), $version );

		$l10n = array(
			'debug'           => ! $StoryFTW->minnified_suffix,
			'storyID'         => $this->get_story_id(),
			'newStoryPageURL' => $this->new_story_page_url(),
			'toReplace'       => 'post_type=' . $this->story_page_post_type,
			'pages'           => $this->prepare_pages_for_js(),
		);

		// wp_die( '<xmp>$l10n: '. print_r( $l10n, true ) .'</xmp>' );
		// wp_die( '<xmp>: '. print_r( $StoryFTW->cpts->story_pages->get_arg( 'labels' ), true ) .'</xmp>' );

		wp_localize_script( 'story-pages', 'StoryFTW_l10n', $l10n );

	}

	public function enqueue_styles() {
		$StoryFTW = StoryFTW::start();
		$version = ! $StoryFTW->minnified_suffix ? time() : StoryFTW::VERSION;

		wp_enqueue_style( 'story-pages', StoryFTW::url( "assets/css/story-pages{$StoryFTW->minnified_suffix}.css" ), array(), $version );
	}

	public function story_pages_listing() {
		include_once( 'templates/story-listing.php' );
	}

	public function story_pages_listing_tablenav( $top = true ) {
		include 'templates/story-tablenav.php';
	}

	public function story_pages_listing_head_foot( $top = true ) {
		include 'templates/story-table-head-foot.php';
	}

	public function has_pages() {
		$story_pages = $this->pages( array(
			'numberposts' => 1,
			'post_status' => 'any',
		) );
		return $story_pages && ! empty( $story_pages );
	}

	/**
	 * Registers admin columns to display. To be overridden by an extended class.
	 * @since  0.1.0
	 * @param  array  $columns Array of registered column names/labels
	 * @return array           Modified array
	 */
	public function columns( $columns ) {
		$date = array_splice( $columns, 2 );
		// $columns['story_excerpt']   = __( 'Excerpt', 'storyftw' );
		$columns['assoc_page'] = __( 'Associated Page', 'storyftw' );
		$columns['num_pages'] = __( 'Story Pages', 'storyftw' );
		$columns['title'] = __( 'Story Title', 'storyftw' );
		$columns = array_merge( $columns, $date );

		add_action( 'admin_footer', array( $this, 'column_width' ) );
		return $columns;
	}

	public function column_width() {
		?>
		<style type="text/css" media="screen"> #num_pages { width: 80px; } </style>
		<?php
	}


	function add_new_story_page_link( $actions, WP_Post $post ) {
		if ( $post->post_type != $this->post_type ) {
			return $actions;
		}
		$this->story_id = $post->ID;

		$label = StoryFTW::start()->cpts->story_pages->get_arg( 'labels' )->add_new;

		$actions['new_story_page_link'] = '<a href="'. esc_url( $this->new_story_page_url() ) .'" class="new-story-page"> '. $label .'</a>';

		return $actions;
	}

	function maybe_add_view_story_link( $actions, WP_Post $post ) {
		if ( ! ( $host = $this->get_story_host() ) ) {
			return $actions;
		}

		$label = $this->labels->view_item;
		$actions['view_story_link'] = '<a href="'. esc_url( get_permalink( $host ) ) .'" class="view-story"> '. $label .'</a>';

		return $actions;
	}

	public function get_story_hosts() {
		$hosts = get_posts( array(
			'posts_per_page' => 999,
			'post_type'      => 'any',
			'fields'         => 'ids',
			'meta_query'     => array( array(
				'key'     => $this->prefix . 'story_id',
				'value'   => 0,
				'compare' => '>',
			) ),
		) );

		return $hosts;
	}

	public function add_js_template() {
		?>
		<!-- Undescore Template -->
		<script type="text/template" id="tmpl-rowTemplate">
			<?php include_once 'templates/story-page-backbone-template.php'; ?>
		</script>
		<!-- Underscore Template ### END -->
		<?php

	}

	public function prepare_pages_for_js() {
		$pages = $this->pages( array( 'post_status' => 'any' ) );

		$this->updated_pages = array();
		foreach ( $pages as $page_id ) {
			$page  = new StoryFTW_Pages( $page_id, $this );
			$data  = $page->get_data();
			$order = $this->unique_menu_order( $data['menu_order'] );
			$this->updated_pages[ $order ] = $data;
		}
		wp_reset_postdata();

		return $this->updated_pages;
	}

	public function unique_menu_order( $order ) {
		if ( ! $order || ! array_key_exists( $order, $this->updated_pages ) ) {
			return $order;
		}

		return $this->unique_menu_order( ++$order );
	}

	public function set_social_default( $value, $args, $field ) {

		if ( ! isset( $_GET['post'] ) ) {
			return array( 'twitter', 'facebook' );
		}
		return $value;
	}

	public function set_checked_by_default( $value, $args, $field ) {

		// set them all to true as default
		if ( ! isset( $_GET['post'] ) ) {
			return true;
		}
		return $value;
	}

	/**
	 * Modies CPT based messages to include our CPT labels
	 * @since  0.1.0
	 * @param  array  $messages Array of messages
	 * @return array            Modied messages array
	 */
	public function messages( $messages ) {
		$messages = parent::messages( $messages );

		if ( $this->is_story_single() && ( $host = $this->get_story_host() ) ) {
			$messages[ $this->post_type ][1] = sprintf( __( '%1$s updated. <a href="%2$s">View %1$s</a>', 'storyftw' ), $this->singular, esc_url( get_permalink( $host ) ) );
		}

		return $messages;

	}

}
