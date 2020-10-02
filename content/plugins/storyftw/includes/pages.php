<?php

class StoryFTW_Pages {

	protected static $posts = array();

	public function __construct( $post_id, $cpt ) {
		// global $post;

		$this->cpt      = $cpt;
		$this->story_id = $cpt->get_story_id();

		if ( ! is_object( $post_id ) ) {
			$post = get_post( $post_id, OBJECT, 'raw' );
			// setup_postdata( $post );
		} else {
			$post = $post_id;
		}
		$this->post = clone $post;
		self::$posts[ $this->post->ID ] = $this->post;
	}

	public function get_data() {

		$this->whitelist = array(
			'ID',
			'menu_order',
			'title',
			'permalink',
			'edit_link',
			'trash_link',
			'trash_label',
			'trash_helper_text',
			'delete_link',
			'delete_label',
			'delete_helper_text',
			'untrash_link',
			'status',
			'excerpt',
		);

		$this
			->add_permalink()
			->add_edit_link()
			->add_delete_links()
			->maybe_create_excerpt();

		return wp_array_slice_assoc( (array) $this->de_prefix_params( $this->post ), $this->whitelist );
	}

	public function de_prefix_params( $post ) {
		foreach ( $post as $param => $value ) {
			if ( 0 === stripos( $param, 'post_' ) ) {
				unset( $post->{$param} );

				$param = str_ireplace( 'post_', '', $param );
				$post->{$param} = $value;
			}
		}

		return $post;
	}

	public function add_permalink() {
		$this->post->permalink = get_permalink( $this->cpt->get_story_host() ) .'#'. $this->post->post_name;
		return $this;
	}

	public function add_edit_link() {
		$this->post->edit_link = get_edit_post_link( $this->post->ID );
		return $this;
	}

	public function add_delete_links() {
		$this->post->trash_helper_text = __( 'Move this item to the Trash', 'storyftw' );
		$this->post->trash_label = __( 'Trash', 'storyftw' );
		$this->post->delete_helper_text = __( 'Delete this item permanently', 'storyftw' );
		$this->post->delete_label = __( 'Delete Permanently', 'storyftw' );

		$this->post->trash_link = get_delete_post_link( $this->post->ID );
		$this->post->delete_link = get_delete_post_link( $this->post->ID, '', true );

		$post_type_object = get_post_type_object( $this->post->post_type );

		$this->post->untrash_link = wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=untrash', $this->post->ID ) ), 'untrash-post_' . $this->post->ID );

		return $this;
	}

	public function maybe_create_excerpt() {
		if ( ! empty( $this->post->post_excerpt ) ) {
			return $this;
		}

		$text           = strip_shortcodes( $this->post->post_content );
		$text           = apply_filters( 'the_content', $text );
		$text           = str_replace( ']]>', ']]&gt;', $text );
		$excerpt_length = apply_filters( 'excerpt_length', 55 );
		$excerpt_more   = apply_filters( 'excerpt_more', ' ' . '&hellip;' );
		$this->post->post_excerpt = wp_trim_words( $text, $excerpt_length, $excerpt_more );

		return $this;
	}

	public static function get_modified_post_objects() {
		return self::$posts;
	}

}
