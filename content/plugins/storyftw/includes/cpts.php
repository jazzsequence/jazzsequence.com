<?php

require_once 'cpt.php';

class StoryFTW_CPTs {

	public function __construct() {

		$this->stories_slug = 'storyftw_stories';
		$this->stories_pages_slug = 'storyftw_story_pages';

		$this->stories_args = array(
			array(
				__( 'Story', 'storyftw' ),
				__( 'Stories', 'storyftw' ),
				$this->stories_slug,
			),
			array(
				'supports'           => array( 'title', /*'editor', 'thumbnail', 'excerpt'*/ ),
				'public'             => false,
				'publicly_queryable' => false,
				'has_archive'        => false,
				'show_ui'            => true,
				'capability_type'    => 'page',
				'hierarchical'       => false,
				'query_var'          => false,
				'menu_icon'          => 'dashicons-book',
				'labels'             => array( 'menu_name' => 'story|ftw' ),
			),
		);

		$this->stories_pages_args = array(
			array(
				__( 'Story Page', 'storyftw' ),
				__( 'Story Pages', 'storyftw' ),
				$this->stories_pages_slug,
			),
			array(
				'supports'        => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
				'public'             => false,
				'publicly_queryable' => false,
				'has_archive'        => false,
				'show_ui'            => true,
				'show_in_menu'       => false,
				'capability_type'    => 'page',
				'hierarchical'       => false,
				'query_var'          => false,
			),
		);

		$this->stories = new StoryFTW_Stories( $this );

		$this->story_pages = new StoryFTW_StoryPages( $this );
	}

	public function args( ) {

	}

	public function hooks() {
		$this->stories->hooks();
		$this->story_pages->hooks();

		// Enable Jetpack markdown support
		add_post_type_support( $this->stories->post_type, 'wpcom-markdown' );
		add_post_type_support( $this->story_pages->post_type, 'wpcom-markdown' );
		add_filter( 'cmb2_show_on', array( $this, 'maybe_show_mb' ), 10, 3 );
		if ( $this->stories->get_palettes() ) {
			add_filter( 'cmb2_localized_data', array( $this, 'update_color_picker_defaults' ) );
		}

	}

	public function maybe_show_mb( $show, $meta_box, $cmb ) {
		if ( false === stripos( $cmb->cmb_id, 'storyftw' ) || 'storyftw_story_picker' == $cmb->cmb_id ) {
			return $show;
		}

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return true;
		}

		$post_type = isset( $_GET['post_type'] ) ? strip_tags( $_GET['post_type'] ) : get_post_type( $cmb->object_id() );

		if ( ! in_array( $post_type, array( $this->stories_slug, $this->stories_pages_slug ), true ) ) {
			return false;
		}

		return $show;
	}

	public function update_color_picker_defaults( $l10n ) {
		$l10n['defaults']['color_picker'] = array(
			'palettes' => $this->stories->get_palettes(),
		);
		return $l10n;
	}

}
