<?php

require_once 'cpt_core.php';

class StoryFTW_CPT extends storyftw_cpt_core {

	public $story = false;
	public $is_new = false;
	public $story_id = 0;
	public $story_page = false;
	public $is_story_page = false;
	public $is_story_parent = false;
	public $is_new_story = false;
	public $is_new_story_page = false;
	public $story_page_post_type = 'storyftw_story_pages';
	public $story_post_type = 'storyftw_stories';

	/**
	 * Meta prefix
	 *
	 * @var string
	 */
	public $prefix = '_storyftw_';
	public $minnified_suffix = '';

	public function __construct( array $cpt, $arg_overrides = array() ) {
		global $pagenow;

		parent::__construct( $cpt, $arg_overrides );

		$this->advanced_open = '
		<div class="advanced-toggle advanced-toggle-wrap closed">

		<div class="handlediv" title="' . __( 'Click to toggle', 'storyftw' ) . '"><br></div>
		<small class="toggle-label"><span>' . __( 'Toggle Advanced Options', 'storyftw' ) . '</span></small>
		<div class="inside">
		';
		$this->advanced_close = '</div></div>';

		$post = isset( $_GET['post'] ) ? get_post( absint( $_GET['post'] ) ) : false;

		if ( ! $post || is_wp_error( $post ) ) {

			$this->is_new = $pagenow && 'post-new.php' == $pagenow && isset( $_GET['post_type'] ) && in_array( $_GET['post_type'], array( $this->story_post_type, $this->story_page_post_type ), true );

			if ( $this->is_new ) {
				$this->is_new_story = $this->story_post_type == $_GET['post_type'];
				$this->is_new_story_page = $this->story_page_post_type == $_GET['post_type'] && isset( $_GET['associated_story_id'] );
				$this->story_id = $this->is_new_story_page && isset( $_GET['associated_story_id'] ) ? absint( $_GET['associated_story_id'] ) : 0;
				$this->story = $this->story_id ? get_post( $this->story_id ) : $this->story;
			}
			return;
		}

		if ( $this->story_post_type == $post->post_type ) {

			$this->is_story_parent = true;
			$this->story = $post;
			$this->story_id = $post->ID;

		} elseif ( $this->story_page_post_type == $post->post_type ) {

			$this->is_story_page = true;
			$this->story_page = $post;
			$this->story_id   = $this->get_story_id_from_meta( $post->ID );
			$this->story      = get_post( $this->get_story_id() );

		}

	}

	/**
	 * Aliases for retrieving $story
	 *
	 * @since  0.1.0
	 *
	 * @return WP_Post object | false
	 */
	public function get_story_parent() { return $this->story; }
	public function get_story() { return $this->story; }

	/**
	 * Aliases for retrieving $story_page
	 *
	 * @since  0.1.0
	 *
	 * @return WP_Post object | false
	 */
	public function get_story_page() { return $this->story_page; }
	public function get_story_child() { return $this->story_page; }

	/**
	 * Get parent story ID
	 *
	 * @since  0.1.0
	 *
	 * @return int Story ID or 0
	 */
	public function get_story_id() {
		return $this->story_id;
	}

	/**
	 * Get parent story ID
	 *
	 * @since  0.1.0
	 *
	 * @return int Story ID or 0
	 */
	public function get_story_id_from_meta( $post_id ) {
		return absint( get_post_meta( $post_id, $this->prefix . 'story_id', 1 ) );
	}

	/**
	 * Anywhere in either Story CPT
	 *
	 * @since  0.1.0
	 *
	 * @return boolean
	 */
	public function is_stories() {
		return $this->is_single() || $this->is_listing();
	}

	/**
	 * Either CPT edit/new page
	 *
	 * @since  0.1.0
	 *
	 * @return boolean
	 */
	public function is_single() {
		return $this->is_story_page_single() || $this->is_story_single();
	}

	/**
	 * Either CPT listing page
	 *
	 * @since  0.1.0
	 *
	 * @return boolean
	 */
	public function is_listing() {
		return $this->is_story_listing() || $this->is_story_page_listing();
	}

	/**
	 * A story CPT edit/new page
	 *
	 * @since  0.1.0
	 *
	 * @return boolean
	 */
	public function is_story_single() {
		return $this->is_story_parent || $this->is_new_story;
	}

	/**
	 * A story-page CPT edit/new page
	 *
	 * @since  0.1.0
	 *
	 * @return boolean
	 */
	public function is_story_page_single() {
		return $this->is_story_page || $this->is_new_story_page;
	}

	/**
	 * A story CPT listing page
	 *
	 * @since  0.1.0
	 *
	 * @return boolean
	 */
	public function is_story_listing() {
		global $pagenow;
		return 'edit.php' == $pagenow && isset( $_GET['post_type'] ) && $this->story_post_type == $_GET['post_type'];
	}

	/**
	 * A story-page CPT listing page
	 *
	 * @since  0.1.0
	 *
	 * @return boolean
	 */
	public function is_story_page_listing() {
		global $pagenow;
		return 'edit.php' == $pagenow && isset( $_GET['post_type'] ) && $this->story_page_post_type == $_GET['post_type'];
	}

	public function pages( $args = array() ) {
		if ( ! $this->get_story_id() ) {
			return array();
		}

		$args = wp_parse_args( $args, array(
			'post_type'   => $this->cpts->stories_pages_slug,
			'numberposts' => -1,
			'meta_key'    => $this->prefix . 'story_id',
			'meta_value'  => absint( $this->get_story_id() ),
			'fields'      => 'ids',
			'orderby'     => 'menu_order',
			'order'       => 'ASC',
		) );
		$story_pages = get_posts( $args );

		return $story_pages;
	}

	function stories() {
		if ( isset( $this->all_stories ) ) {
			return $this->all_stories;
		}

		$this->all_stories = get_posts( array(
			'post_type'   => $this->story_post_type,
			'numberposts' => -1,
		) );

		return $this->all_stories;
	}

	/**
	 * Returns an array of story post options
	 *
	 * @return array An array of story posts as options
	 */
	function get_story_options( $all = true, $page_id = 0 ) {

		$stories = $this->stories();

		$story_options = array();
		if ( $stories && ! empty( $stories) ) {
			foreach ( $stories as $story ) {
				if ( ! $all ) {
					$host = $this->get_story_host( $story->ID );
					if ( ! $host || $host == $page_id ) {
						$story_options[ $story->ID ] = $story->post_title;
					}
				} else {
					$story_options[ $story->ID ] = $story->post_title;
				}
			}
		}

		return $story_options;
	}

	public function new_story_page_url() {
		return add_query_arg( array(
			'post_type'           => $this->story_page_post_type,
			'associated_story_id' => $this->get_story_id()
		), admin_url( '/post-new.php' ) );
	}

	public function get_story_host( $story_id = 0 ) {
		static $hosts;
		if ( ! $story_id && ! $this->story_id ) {
			return false;
		}
		$story_id = $story_id ? absint( $story_id ) : $this->story_id;

		if ( is_array( $hosts ) && array_key_exists( $story_id, $hosts ) ) {
			return $hosts[ $story_id ];
		}

		$host_array = get_posts( array(
			'posts_per_page' => 1,
			'post_type'      => 'any',
			'fields'         => 'ids',
			'meta_query'     => array( array(
				'key'     => $this->prefix . 'story_id',
				'value'   => $story_id,
			) ),
		) );

		$host = ! empty( $host_array ) ? $host_array[0] : false;

		if ( empty( $hosts ) ) {
			$hosts = array( $story_id => $host );
		} else {
			$hosts[ $story_id ] = $host;
		}

		return $host;
	}

	/**
	 * Get story color palettes
	 *
	 * @since  0.1.0
	 *
	 * @return array of color palettes
	 */
	public function get_palettes() {
		if ( isset( $this->palettes ) ) {
			return $this->palettes;
		}

		$palettes = get_post_meta( $this->get_story_id(), $this->prefix .'palettes', 1 );
		$this->palettes = is_array( $palettes ) ? $palettes : false;

		return $this->palettes;
	}

	/**
	 * Handles admin column display. To be overridden by an extended class.
	 * @since  0.1.0
	 * @param  array  $column Array of registered column names
	 */
	public function columns_display( $column ) {
		global $post;

		if ( $post->post_type != $this->post_type ) {
			return;
		}

		switch ( $column ) {
			case 'num_pages':
				$this->story_id = $post->ID;
				echo '<a href="'. get_edit_post_link( $post->ID ) .'#story-pages">'. count( $this->pages( array( 'post_status' => 'any' ) ) ) .'</a>';
			break;
			case 'assoc_page':
				if ( ! ( $host = $this->get_story_host() ) ) {
					_e( '&mdash;', 'storyftw' );
					break;
				}

				$label = $this->labels->view_item;
				echo '<strong><a href="'. esc_url( get_edit_post_link( $host ) ) .'" class="view-story"> '. get_the_title( $host ) .'</a></strong>';
				echo '
				<div class="row-actions">
					<span class="edit"><a href="'. esc_url( get_edit_post_link( $host ) ) .'" title="' . __( 'Edit this page', 'storyftw' ) . '">' . __( 'Edit', 'storyftw' ) . '</a> | </span>
					<span class="view_story_link"><a href="'. esc_url( get_permalink( $host ) ) .'" class="view-page"> ' . __( 'View', 'storyftw' ) . '</a></span>
				</div>
				';
			break;
			case 'story_excerpt':
				the_excerpt();
			break;
			case 'page_order':
				echo $post->menu_order;
			break;
		}
	}

}
