<?php

class StoryFTW_Metaboxes {

	public function __construct( $cpts ) {
		$this->cpts = $cpts;
		$this->cpt  = $cpts->stories;
	}

	public function hooks() {
		add_filter( 'cmb2_meta_boxes', array( $this, 'story_to_page_metabox' ) );
	}

	public function story_to_page_metabox( array $meta_boxes ) {

		$post_types = apply_filters( 'storyftw_story_select_post_types', array( 'page' ) );

		// Metabox for selecting a story
		$meta_boxes['storyftw_story_picker'] = array(
			'id'           => 'storyftw_story_picker',
			'title'        => 'story|ftw',
			'object_types' => $post_types,
			'context'      => 'side',
			'priority'     => 'low',
			'fields'       => array(
				array(
					'name'    => __( 'Replace this page with a Story?', 'storyftw' ),
					'id'      => '_storyftw_story_id',
					'type'    => 'select',
					'options' => array( $this, 'get_story_options' ),
					'after' => array( $this, 'check_for_empty' ),
					'attributes' => array(
						'style' => 'width:100%;',
					),
				),
			),
		);

		return $meta_boxes;
	}

	public function get_story_options( $field ) {
		$this->stories = $this->cpt->get_story_options( false, $field->object_id );

		if ( ! empty( $this->stories ) ) {
			return array( 0 => __( 'Select Story', 'storyftw' ) ) + $this->stories;
		}

		return array( 0 => __( 'No Stories to attach', 'storyftw' ) );
	}

	public function check_for_empty() {
		if ( isset( $this->stories ) && empty( $this->stories ) ) {
			echo '<p class="cmb-description">';
			printf( __( '<a href="%s">Create a new story</a> to attach to this page?', 'storyftw' ), admin_url( 'post-new.php?post_type=storyftw_stories' ) );
			echo '</p>';
		}
	}

}
