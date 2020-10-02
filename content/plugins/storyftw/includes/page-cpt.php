<?php

require_once 'cpt_core.php';

class StoryFTW_CPT extends storyftw_cpt_core {

	/**
	 * Current story post parent object (unless is parent)
	 *
	 * @var WP_Post Object
	 */
	public $story;

	/**
	 * Current story post object
	 *
	 * @var WP_Post Object
	 */
	public $story_page;

	/**
	 * Whether on 'Add New Story' page
	 *
	 * @var bool
	 */
	public $is_story_new;

	/**
	 * Whether current story object is a child-post (making it a page)
	 *
	 * @var bool
	 */
	public $is_story_page;

	/**
	 * Whether we're looking at one of the story edit pages
	 *
	 * @var bool
	 */
	public $is_story;

	/**
	 * Meta prefix
	 *
	 * @var string
	 */
	public $prefix = '_storyftw_';

	public function __construct() {
		global $pagenow;

		parent::__construct(
			array(
				'Story',
				'Stories',
				'storyftw_stories',
			),
			array(
				'supports'        => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
				'public'          => false,
				'show_ui'         => true,
				'capability_type' => 'page',
				'hierarchical'    => true,
				'query_var'       => false,
				'menu_icon'       => 'dashicons-book',
			)
		);


		$this->story_page    = isset( $_GET['post'] ) ? get_post( absint( $_GET['post'] ) ) : false;
		$this->is_story_new  = ! $this->story_page && $pagenow && 'post-new.php' == $pagenow && isset( $_GET['post_type'] ) && $this->name = $_GET['post_type'];
		$this->is_story_page = $this->story_page && $this->story_page->post_parent;
		$this->story         = $this->is_story_page ? get_post( $this->story_page->post_parent ) : $this->story_page;
		$this->is_story      = $this->story_page || $this->is_story_new;

		// Story child (page)
		if ( $this->is_story_page ) {
			$this->story_page_hooks();
		}
		// Story Parent
		elseif ( $this->story_page || $this->is_story_new ) {
			$this->story_parent_hooks();
		}

		$this->hooks();
	}

	public function hooks() {
		add_action( 'add_meta_boxes', array( $this, 'remove_page_attr_box' ) );
		add_action( 'add_meta_boxes', array( $this, 'replace_attributes_box' ) );
	}

	public function remove_page_attr_box() {
		remove_meta_box( 'pageparentdiv', $this->name, 'side' );
	}

	public function replace_attributes_box() {

	}

	public function story_parent_hooks() {
		add_filter( 'cmb2_meta_boxes', array( $this, 'story_main_box' ) );
	}

	public function story_page_hooks() {
		add_filter( 'cmb2_meta_boxes', array( $this, 'child_stories_box' ) );
		add_filter( 'get_sample_permalink_html', array( $this, 'see_story_btn' ), 10, 2 );
	}

	public function story_main_box( array $meta_boxes ) {

		$meta_boxes['storyftw_story_main_box'] = array(
			'id'           => 'storyftw_story_main_box',
			'title'        => __( 'Story Settings', 'storyftw' ),
			'object_types' => array( $this->name ), // Post type
			'context'      => 'normal',
			'priority'     => 'high',
			'fields'       => array(
				array(
					'name' => __( "Include your current theme's stylesheet?", 'storyftw' ),
					'desc' => __( "There is a possibility that your theme's stylsheet will conflict with the Story's presentation.", 'storyftw' ),
					'id'   => $this->prefix . 'include_theme_style',
					'type' => 'checkbox',
				),
			),
		);

		return $meta_boxes;
	}

	public function child_stories_box( array $meta_boxes ) {

		$meta_boxes['storyftw_child_stories_box'] = array(
			'id'           => 'storyftw_child_stories_box',
			'title'        => __( 'Story Page Settings', 'storyftw' ),
			'object_types' => array( $this->name ),
			'priority'     => 'high',
			'fields'       => array(
				array(
					'name' => __( 'Story Page Background Color', 'storyftw' ),
					'id'   => $this->prefix . 'background',
					'type' => 'colorpicker',
				),
			),
		);

		return $meta_boxes;
	}

	public function see_story_btn( $html, $id ) {

		if ( ! $this->story_page ) {
			return $html;
		}

		if ( $this->story_page->post_parent)
		$crimeline = get_page_by_title( $terms[0]->name, OBJECT, 'crimeline_post' );
		if ( !$crimeline || is_wp_error( $crimeline ) )
			return $html;

		$permalink = get_permalink( $crimeline->ID );
		$html .= "<span id='view-post-btn'><a href='$permalink' class='button button-small'>View Crimeline</a></span>\n";
		$editlink = get_edit_post_link( $crimeline->ID );
		$html .= "<span id='view-post-btn'><a href='$editlink' class='button button-small'>Edit Crimeline</a></span>\n";

		return $html;
	}

}
