<?php

class StoryFTW_StoryPages extends StoryFTW_CPT {

	public function __construct( $cpts ) {
		$this->cpts = $cpts;
		parent::__construct( $this->cpts->stories_pages_args[0], $this->cpts->stories_pages_args[1] );
	}

	public function hooks() {

		add_filter( 'cmb2_meta_boxes', array( $this, 'child_stories_box' ) );
		add_filter( 'cmb2_select_attributes', array( $this, 'opt_groups' ), 10, 4 );
		add_filter( 'get_sample_permalink_html', array( $this, 'maybe_modify_permalink_html' ), 10, 4 );

		if ( $this->is_story_page_single() ) {

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
			add_action( 'edit_form_after_title', array( $this, 'slug_div' ) );
			add_action( 'edit_form_top', array( $this, 'edit_form_top' ) );
			add_action( 'add_meta_boxes', array( $this, 'remove_page_attr_box' ) );
			// Add the storyftw styles to the editor
			add_action( 'admin_init', array( $this, 'add_editor_style' ) );

			if ( empty( $_GET['post'] ) ) {
				// If creating a new story page, we want the menu_order to be set high so that it goes to the end of the list.
				add_action( 'edit_form_top', array( $this, 'set_high_menu_order' ) );
			}

			// @todo fix
			add_action( 'admin_enqueue_scripts', array( $this, 'story_page_script' ) );

		}

	}

	public function maybe_modify_permalink_html( $html, $id, $new_title, $new_slug ) {
		if ( $this->post_type != get_post_type( $id ) ) {
			return $html;
		}
		$story_id = $this->get_story_id();
		$story_id = $story_id ? $story_id : $this->get_story_id_from_meta( $id );
		if ( ! ( $host = $this->get_story_host( $story_id ) ) ) {
			return $html;
		}

		$post = get_post( $id );
		$newpermalink = get_permalink( $host );
		$newpermalink .= '#';

		list( $permalink, $post_name ) = get_sample_permalink( $id );
		$permalink = str_ireplace( array( '%pagename%/', '%pagename%' ), '', $permalink );

		$new_with_trailing = trailingslashit( $newpermalink . $post_name );

		$html = str_ireplace(
			array( $permalink, $new_with_trailing, '&lrm;', '</span>/</span>' ),
			array( $newpermalink, untrailingslashit( $new_with_trailing ), '', '</span></span>' ),
			$html
		);

		return $html;
	}

	public function slug_div( $post ) {
		if ( empty( $post ) || ! ( $host = $this->get_story_host() ) ) {
			return;
		}

		$sample_permalink_html = get_sample_permalink_html( $post->ID );
		$has_sample_permalink = $sample_permalink_html && 'auto-draft' != $post->post_status;
		if ( ! $has_sample_permalink ) {
			return;
		}

		echo '
		<div id="edit-slug-box" class="hide-if-no-js">
			'. $sample_permalink_html .'
		</div>
		';
	}

	public function enqueue_styles() {
		$StoryFTW = StoryFTW::start();
		$version = ! $StoryFTW->minnified_suffix ? time() : StoryFTW::VERSION;

		wp_enqueue_style( 'story-page-edit', StoryFTW::url( "assets/css/story-page-edit{$StoryFTW->minnified_suffix}.css" ), array(), $version );
	}

	public function edit_form_top() {
		echo '<h3><strong>'. $this->cpts->stories_args[0][0] .'</strong>: <a href="'. esc_html( get_edit_post_link( $this->story->ID ) ) .'">'. esc_html( $this->story->post_title ) .'</a></h3>';
	}

	public function set_high_menu_order() {
		$pages = $this->pages( array(
			'numberposts' => 1,
			'order' => 'DESC',
			'fields' => 'all',
		) );
		$order = ! empty( $pages ) && isset( $pages[0]->menu_order ) ? $pages[0]->menu_order + 1 : 998;

		echo '<input name="menu_order" type="hidden" id="menu_order" value="'. absint( $order ) .'">';
	}

	public function remove_page_attr_box() {
		remove_meta_box( 'pageparentdiv', $this->name, 'side' );
		remove_meta_box( 'postimagediv', $this->name, 'side' );
		// add_meta_box( 'postimagediv', __( 'Background Image', 'storyftw' ), 'post_thumbnail_meta_box', $this->name, 'side', 'low' );
	}

	public function story_page_script() {
		$StoryFTW = StoryFTW::start();


		$version = ! $StoryFTW->minnified_suffix ? time() : StoryFTW::VERSION;
		wp_enqueue_script( 'story-pages', StoryFTW::url( "assets/js/story-page{$StoryFTW->minnified_suffix}.js" ), array( 'jquery' ), $version );

		wp_localize_script( 'story-pages', 'StoryFTW_l10n', array(
			'debug'     => ! $StoryFTW->minnified_suffix,
			'storyID'   => $this->get_story_id(),
			'toReplace' => 'post_type=' . $this->post_type,
		) );
	}

	public function child_stories_box( array $meta_boxes ) {
		$StoryFTW = StoryFTW::start();

		$fields = array();

		if ( ! $this->get_story_id() ) {

			$fields[] = array(
				'name'    => __( 'Associate with Story', 'storyftw' ),
				'desc'    => sprintf( __( 'Whoops! This %s is not associated with a %s yet.', 'storyftw' ), $this->singular, $StoryFTW->cpts->stories->singular ),
				'id'      => $this->prefix . 'story_id',
				'type'    => 'select',
				'options' => $this->get_story_options(),
			);
		} else {
			$fields[] = array(
				'name' => 'hidden',
				'id'   => $this->prefix . 'story_id',
				'type' => 'hidden',
				'escape_cb' => array( $this, 'set_default_story_id' ),
			);
		}

		$fields = array_merge( $fields, array(
			array(
				'name' => __( 'Story Page Background Color', 'storyftw' ),
				'id'   => $this->prefix . 'background',
				'type' => 'colorpicker',
			),
			array(
				'name'    => __( 'Content Position', 'storyftw' ),
				'id'      => $this->prefix . 'content_position',
				'default' => 'middle',
				'type'    => 'select',
				'options' => array(
					'middle' => __( 'Middle', 'storyftw' ),
					'top'    => __( 'Top', 'storyftw' ),
					'bottom' => __( 'Bottom', 'storyftw' ),
				),
			),
			array(
				'name'    => __( 'Text Color', 'storyftw' ),
				'id'      => $this->prefix . 'text_color',
				'default' => 'light',
				'type'    => 'select',
				'options' => array(
					'light' => __( 'White', 'storyftw' ),
					'black' => __( 'Black', 'storyftw' ),
					'dark'  => __( 'Gray', 'storyftw' ),
				),
			),
			array(
				'name'    => __( 'Text Align', 'storyftw' ),
				'id'      => $this->prefix . 'text_align',
				'default' => 'center',
				'type'    => 'select',
				'options' => array(
					'center'      => __( 'Centered', 'storyftw' ),
					'left-align'  => __( 'Left-aligned', 'storyftw' ),
					'right-align' => __( 'Right-aligned', 'storyftw' ),
				),
			),
			// array(
			// 	'name' => __( 'Content Positioning', 'storyftw' ),
			// 	'id'   => $this->prefix . 'content_positioning',
			// 	'default' => 'table',
			// 	'type' => 'select',
			// 	'options' => array(
			// 		'table'      => __( 'Centered to middle', 'storyftw' ),
			// 		'absolute-fill'  => __( 'Left-aligned', 'storyftw' ),
			// 		'right-align' => __( 'Right-aligned', 'storyftw' ),
			// 	),
			// ),
			array(
				'name' => __( 'Exclude from Navigation', 'storyftw' ),
				'desc'    => __( 'By default, all pages will be added to the Table of Contents navigation section.', 'storyftw' ),
				'id'   => $this->prefix . 'exclude_nav',
				'type' => 'checkbox',
				'before_row' => $this->advanced_open,
			),
			array(
				'name' => __( 'Page Footer', 'storyftw' ),
				'id'   => $this->prefix . 'footer',
				'type' => 'wysiwyg',
				'options' => array(
					'textarea_rows' => 5,
				),
			),
			array(
				'name' => __( 'Story Page Classes', 'storyftw' ),
				'desc' => __( 'Optionally add additional html classes to this story page. (separate by a space)', 'storyftw' ),
				'id'   => $this->prefix . 'story_page_classes',
				'type' => 'text',
			),

			array(
				'name'    => __( 'Story Page Footer Overrides', 'storyftw' ),
				'desc'    => sprintf( __( 'The options you choose here will override the options set in the %s.', 'storyftw' ), '<a href="'. get_edit_post_link( $this->get_story_id() ) .'">' . __( 'Story settings', 'storyftw' ) . '</a>' ),
				'id'      => $this->prefix . 'title',
				'type'    => 'title',
			),
			array(
				'name'    => __( 'Social Sharing', 'storyftw' ),
				'desc'    => __( 'Include social share buttons?', 'storyftw' ),
				'id'      => $this->prefix . 'social',
				'type'    => 'multicheck',
				'select_all_button' => false,
				// 'default' => array( 'twitter', 'facebook' ),
				'options' => array(
					'twitter'  => __( 'Twitter', 'storyftw' ),
					'facebook' => __( 'Facebook', 'storyftw' ),
				),
			),
			array(
				'name'      => __( 'Table of Contents button', 'storyftw' ),
				'id'        => $this->prefix . 'enable_toc',
				'type'      => 'checkbox',
			),
			array(
				'name'      => __( 'Story Title/Logo', 'storyftw' ),
				'id'        => $this->prefix . 'footer_title',
				'type'      => 'checkbox',
			),
			array(
				'name'      => __( 'Color Overrides', 'storyftw' ),
				'id'        => 'color_overrides',
				'type'      => 'title',
			),
			array(
				'name'      => __( '"Start" button text & navigation arrows color', 'storyftw' ),
				'id'        => $this->prefix . 'arrow_color',
				'type'      => 'colorpicker',
			),
			array(
				'name'      => __( 'Link color', 'storyftw' ),
				'id'        => $this->prefix . 'link_color',
				'type'      => 'colorpicker',
			),
			array(
				'name'      => __( 'Footer title color', 'storyftw' ),
				'id'        => $this->prefix . 'footer_text_color',
				'type'      => 'colorpicker',
			),
			array(
				'name'      => __( 'Footer button text color', 'storyftw' ),
				'id'        => $this->prefix . 'footer_button_text_color',
				'type'      => 'colorpicker',
				'after_row' => $this->advanced_close,
				'after_row' => $this->advanced_close,
			),
		) );


		$meta_boxes['storyftw_child_stories_box'] = array(
			'id'           => 'storyftw_child_stories_box',
			'title'        => __( 'Story Page Settings', 'storyftw' ),
			'object_types' => array( $this->post_type ),
			'priority'     => 'high',
			'fields'       => $fields,
		);

		$meta_boxes['storyftw_child_stories_thumb_box'] = array(
			'id'           => 'storyftw_child_stories_thumb_box',
			'title'        => __( 'Background Image', 'storyftw' ),
			'object_types' => array( $this->post_type ),
			'priority'     => 'low',
			'context'     => 'side',
			'show_names'   => false,
			'fields'       => array(
				array(
					'id'           => '_thumbnail',
					'type'         => 'file',
					'preview_size' => array( 243, 243 ),
					'options'      => array(
						'url' => false,
					),
				),
				array(
					'name' => '<span class="dashicons-before dashicons-editor-expand"></span> ' . __( 'Stretch Image to fill', 'storyftw' ),
					'id'   => $this->prefix . 'bgcover',
					'type' => 'checkbox',
					'before_row' => $this->advanced_open,
				),
				array(
					'name' => __( 'Image Position', 'storyftw' ),
					'id'   => $this->prefix . 'img_options',
					'type' => 'select',
					'default' => 'middle',
					'options' => array(
						// 'middle' => __( 'Center Image (default)', 'storyftw' ),
					)
				),
				array(
					'name' => __( 'Photo Credit', 'storyftw' ),
					'desc' => __( 'Add a photo credit line. (optional)', 'storyftw' ),
					'id'   => $this->prefix . 'photo_credit',
					'type' => 'text',
				),
				array(
					'name' => __( 'Photo Credit URL', 'storyftw' ),
					'desc' => __( '(optional)', 'storyftw' ),
					'id'   => $this->prefix . 'photo_credit_url',
					'type' => 'text_url',
				),
				array(
					'name'    => __( 'Photo Credit Position', 'storyftw' ),
					'id'      => $this->prefix . 'photo_credit_position',
					'type'    => 'select',
					'default' => 'top-right',
					'options' => array(
						'top-right'    => __( 'Top Right', 'storyftw' ),
						'top-left'     => __( 'Top Left', 'storyftw' ),
						'bottom-right' => __( 'Bottom Right', 'storyftw' ),
						'bottom-left'  => __( 'Bottom Left', 'storyftw' ),
					),
					'after_row' => $this->advanced_close,
				),
			),
		);

		$vid_options = array(
			'add_upload_file_text' => __( 'Add or upload video file', 'storyftw' ),
		);
		$meta_boxes['storyftw_child_stories_videos'] = array(
			'id'           => 'storyftw_child_stories_videos',
			'title'        => __( 'Background Video', 'storyftw' ),
			'object_types' => array( $this->post_type ),
			'priority'     => 'low',
			'context'     => 'side',
			'show_names'   => false,
			'fields'       => array(
				array(
					'name' => __( '.mp4 Source URL', 'storyftw' ),
					'id'   => $this->prefix . 'video_mp4',
					'type' => 'file',
					'options' => $vid_options,
				),
				array(
					'name' => __( '.webm Source URL', 'storyftw' ),
					'id'   => $this->prefix . 'video_webm',
					'type' => 'file',
					'options' => $vid_options,
				),
				array(
					'name' => __( '.ogv Source URL', 'storyftw' ),
					'id'   => $this->prefix . 'video_ogv',
					'type' => 'file',
					'options' => $vid_options,
				),
			),
		);
		// - bg-cover (auto when BG image) float image centered
		// 	- bg-cover-top      float image to top
		// 	- bg-cover-bottom   float image to bottom
		// 	- bg-center ?

		return $meta_boxes;
	}

	function opt_groups( $args, $defaults, $field_object, $field_types_object = '' ) {
		if ( empty( $field_types_object ) ) {
			return $args;
		}

	 	if ( $this->prefix . 'img_options' != $field_object->_id() ) {
			return $args;
		}

		$option_array = array(
			__( 'Stick to Top', 'storyftw' ) => array(
				'top' => __( 'Top Center', 'storyftw' ),
				'top-left' => __( 'Top Left', 'storyftw' ),
				'top-right' => __( 'Top Right', 'storyftw' ),
			),
			__( 'Stick to Bottom', 'storyftw' ) => array(
				'bottom' => __( 'Bottom Center', 'storyftw' ),
				'bottom-left' => __( 'Bottom Left', 'storyftw' ),
				'bottom-right' => __( 'Bottom Right', 'storyftw' ),
			),
		);

		$saved_value = $field_object->escaped_value();
		$value       = $saved_value ? $saved_value : $field_object->args( 'default' );

		$options_string = '';
		$options_string .= $field_types_object->option( __( 'Center Image (default)', 'storyftw' ), 'middle', 'middle' == $value );

		foreach ( $option_array as $group_label => $group ) {

			$options_string .= '<optgroup label="'. $group_label .'">';

			foreach ( $group as $key => $label ) {
				$options_string .= $field_types_object->option( $label, $key, $value == $key );
			}
			$options_string .= '</optgroup>';
		}

		$defaults['options'] = $options_string;

		return $defaults;
	}

	public function set_default_story_id( $value ) {
		if ( $value ) {
			return $value;
		}

		return $this->get_story_id();
	}

	/**
	 * Registers admin columns to display. To be overridden by an extended class.
	 * @since  0.1.0
	 * @param  array  $columns Array of registered column names/labels
	 * @return array           Modified array
	 */
	public function columns( $columns ) {
		$date          = array_splice( $columns, 2 );
		$cb            = array_splice( $columns, 0, 1 );
		$title         = array_splice( $columns, 0, 1 );
		$order         = array( 'page_order' => __( '#', 'storyftw' ) );
		$story_excerpt = array( 'story_excerpt' => __( 'Excerpt', 'storyftw' ) );
		$columns       = array_merge( $cb, $order, $title, $story_excerpt, $columns, $date );
		?>
		<style type="text/css" media="screen">
			#num_pages { width: 50px; }
			#title { width: 22%; }
			#page_order { width: 1.5em; }
		</style>
		<?php
		return $columns;
	}

	public function get_story_page_permalink( $post_id ) {
		if ( ! ( $host = $this->get_story_host() ) ) {
			return '';
		}

		if ( is_a( $post_id, 'WP_Post' ) ) {
			$post = $post_id;
		} else {
			$post_id = $post_id ? absint( $post_id ) : get_the_ID();
			$post = get_post( $post_id );
		}

		if ( empty( $post ) ) {
			return '';
		}

		// $slug = get_post_meta( $post->ID, $this->prefix . 'slide_id', 1 );
		// $permalink .= $slug ? $slug : esc_attr( $post->post_name );
		$permalink = get_permalink( $host );
		$permalink .= '#'. esc_attr( $post->post_name );

		return apply_filters( 'story_page_permalink', $permalink, $post );
	}

	/**
	 * Modies CPT based messages to include story page URL (or not)
	 * @since  0.1.0
	 * @param  array  $messages Array of messages
	 * @return array            Modied messages array
	 */
	public function messages( $messages ) {
		global $post;
		$messages = parent::messages( $messages );

		if ( $this->is_story_page_single() ) {

			$permalink = $this->get_story_page_permalink( $post );

			$messages[ $this->post_type ][1] = $permalink
				? sprintf( __( '%1$s updated. <a href="%2$s">View %1$s</a>', 'storyftw' ), $this->singular, esc_url( $permalink ) )
				: sprintf( __( '%1$s updated.', 'storyftw' ), $this->singular );

			$messages[ $this->post_type ][6] = $permalink
				? sprintf( __( '%1$s published. <a href="%2$s">View %1$s</a>', 'storyftw' ), $this->singular, esc_url( $permalink ) )
				: sprintf( __( '%1$s published.', 'storyftw' ), $this->singular );

			$messages[ $this->post_type ][8] = $permalink
				? sprintf( __( '%1$s submitted. <a target="_blank" href="%2$s">Preview %1$s</a>', 'storyftw' ), $this->singular, esc_url( add_query_arg( 'preview', 'true', $permalink ) ) )
				: sprintf( __( '%1$s submitted.', 'storyftw' ), $this->singular );

			$messages[ $this->post_type ][9] = $permalink
				? sprintf( __( '%1$s scheduled for: <strong>%2$s</strong>. <a target="_blank" href="%3$s">Preview %1$s</a>', 'storyftw' ), $this->singular,
					// translators: Publish box date format, see http://php.net/date
					date_i18n( __( 'M j, Y @ G:i', 'storyftw' ), strtotime( $post->post_date ) ), esc_url( $permalink ) )
				: sprintf( __( '%1$s scheduled for: <strong>%2$s</strong>.', 'storyftw' ), $this->singular,
					// translators: Publish box date format, see http://php.net/date
					date_i18n( __( 'M j, Y @ G:i', 'storyftw' ), strtotime( $post->post_date ) ) );

			$messages[ $this->post_type ][10] = $permalink
				? sprintf( __( '%1$s draft updated. <a target="_blank" href="%2$s">Preview %1$s</a>', 'storyftw' ), $this->singular, esc_url( add_query_arg( 'preview', 'true', $permalink ) ) )
				: sprintf( __( '%1$s draft updated', 'storyftw' ), $this->singular );

		}

		return $messages;

	}

	public function add_editor_style() {

		// No dynamic stuff, per Patrick's request
		return;

		// Add (slightly) modified storyftw stylesheet
		add_editor_style( add_query_arg( 'version', StoryFTW::VERSION, StoryFTW::url( "assets/css/storyftw-editor.css" ) ) );

		if ( ! $this->get_story_id() ) {
			return;
		}

		$editor_css_url = StoryFTW::url( "includes/storyftw-editor-css.php" );

		// Get includes url parts
		$parsed_url = parse_url( includes_url() );

		$url_params = array(
			// Add version param
			'version' => get_bloginfo( 'version' ),
			// Get the css params to append to the custom editor stylesheet URL
			'css' => urlencode( json_encode( $this->get_story_css_params() ) ),
			// Include the relative path to the includes dir
			// Should work even if WP in sub-directory
			'wp_includes_rel_path' => $parsed_url['path'],
		);
		$src = add_query_arg( $url_params, $editor_css_url );

		// echo '<div style="padding: 50px; background: #eee; color: #000;">$src: ';
		// echo popuplinks( make_clickable( $src ) );
		// echo '<xmp>: '. print_r( strlen( $src ), true ) .'</xmp>';
		// echo '</div>';

		add_editor_style( $src );
	}

	public function get_story_css_params() {
		$css_params = array();

		// bump font-size a bit
		if ( $arrow_color = $this->get_story_value( 'arrow_color' ) ) {

			$css_params = array(
				'html #tinymce.content:before, html #tinymce.content:after' => array(
					'color' => $arrow_color,
				),
			);
		}

		if ( $color = $this->get_story_value( 'link_color' ) ) {
			$css_params['html a:not(.btn)'] = array(
				'color' => $color,
			);
		}

		if ( $background_color = $this->get_story_value( 'background', 'fallback_bg_color' ) ) {
			$css_params['html body']['background-color'] = $background_color;
		}

		if ( $text_color = $this->get_story_value( 'text_color', '', 'light' ) ) {
			switch ( $text_color ) {
				case 'light':
					$text_color = '#fff';
					break;

				case 'dark':
					$text_color = '#666';
					break;

				default:
					$text_color = '#000';
					break;
			}

			$css_params['html body']['color'] = $text_color;
		}

		if ( $text_align = $this->get_story_value( 'text_align', '', 'center' ) ) {

			switch ( $text_align ) {
				case 'center':
					$text_align = 'center';
					break;

				case 'right-align':
					$text_align = 'right';
					break;

				default:
					$text_align = 'left';
					break;
			}

			$css_params['html body']['text-align'] = $text_align;
		}

		return (array) apply_filters( 'storyftw_editor_styles', $css_params, $this );
	}

	public function get_story_value( $key, $story_key = '', $default = '' ) {
		$story_page_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;

		$value = get_post_meta( $story_page_id, $this->prefix . $key, 1 );

		if ( ! $value ) {
			$story_key = $story_key ? $story_key : $key;
			$value = get_post_meta( $this->get_story_id(), $this->prefix . $story_key, 1 );
		}

		return $value ? $value : $default;
	}

}
