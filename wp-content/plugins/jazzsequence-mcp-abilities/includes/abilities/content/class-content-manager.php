<?php
/**
 * Content Management for all post types.
 *
 * Provides create, read, update, delete operations for all WordPress content.
 *
 * @package JazzSequence\MCP_Abilities
 */

declare( strict_types=1 );

namespace JazzSequence\MCP_Abilities\Abilities\Content;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Content Manager class.
 *
 * @since 0.1.0
 */
class Content_Manager {

	/**
	 * Create a new post.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Post creation arguments.
	 * @return array|\WP_Error Post data or error.
	 */
	public function create_post( array $args ) {
		$defaults = [
			'post_type'    => 'post',
			'post_title'   => '',
			'post_content' => '',
			'post_excerpt' => '',
			'post_status'  => 'draft',
			'post_author'  => get_current_user_id(),
			'meta_input'   => [],
			'tax_input'    => [],
		];

		$args = wp_parse_args( $args, $defaults );

		// Validate post type exists.
		if ( ! post_type_exists( $args['post_type'] ) ) {
			return new \WP_Error(
				'invalid_post_type',
				sprintf(
					/* translators: %s: Post type name */
					__( 'Post type "%s" does not exist.', 'jazzsequence-mcp-abilities' ),
					$args['post_type']
				)
			);
		}

		// Create the post.
		$post_id = wp_insert_post( $args, true );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		// Get the created post.
		$post = get_post( $post_id );

		return $this->format_post( $post );
	}

	/**
	 * Update an existing post.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Post update arguments (must include ID).
	 * @return array|\WP_Error Updated post data or error.
	 */
	public function update_post( array $args ) {
		if ( empty( $args['ID'] ) ) {
			return new \WP_Error(
				'missing_post_id',
				__( 'Post ID is required for updates.', 'jazzsequence-mcp-abilities' )
			);
		}

		$post_id = absint( $args['ID'] );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_Error(
				'post_not_found',
				sprintf(
					/* translators: %d: Post ID */
					__( 'Post with ID %d not found.', 'jazzsequence-mcp-abilities' ),
					$post_id
				)
			);
		}

		// Update the post.
		$result = wp_update_post( $args, true );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Get the updated post.
		$updated_post = get_post( $result );

		return $this->format_post( $updated_post );
	}

	/**
	 * Delete a post.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Deletion arguments (requires ID, optional force_delete).
	 * @return array|\WP_Error Deletion result or error.
	 */
	public function delete_post( array $args ) {
		if ( empty( $args['ID'] ) ) {
			return new \WP_Error(
				'missing_post_id',
				__( 'Post ID is required for deletion.', 'jazzsequence-mcp-abilities' )
			);
		}

		$post_id      = absint( $args['ID'] );
		$force_delete = ! empty( $args['force_delete'] );

		$post = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_Error(
				'post_not_found',
				sprintf(
					/* translators: %d: Post ID */
					__( 'Post with ID %d not found.', 'jazzsequence-mcp-abilities' ),
					$post_id
				)
			);
		}

		// Store post data before deletion.
		$post_data = $this->format_post( $post );

		// Delete the post.
		$result = wp_delete_post( $post_id, $force_delete );

		if ( ! $result ) {
			return new \WP_Error(
				'deletion_failed',
				__( 'Failed to delete post.', 'jazzsequence-mcp-abilities' )
			);
		}

		return [
			'deleted'      => true,
			'force_delete' => $force_delete,
			'post'         => $post_data,
		];
	}

	/**
	 * Get a post by ID.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Query arguments (requires ID).
	 * @return array|\WP_Error Post data or error.
	 */
	public function get_post( array $args ) {
		if ( empty( $args['ID'] ) ) {
			return new \WP_Error(
				'missing_post_id',
				__( 'Post ID is required.', 'jazzsequence-mcp-abilities' )
			);
		}

		$post_id = absint( $args['ID'] );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_Error(
				'post_not_found',
				sprintf(
					/* translators: %d: Post ID */
					__( 'Post with ID %d not found.', 'jazzsequence-mcp-abilities' ),
					$post_id
				)
			);
		}

		return $this->format_post( $post );
	}

	/**
	 * Query posts.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Query arguments.
	 * @return array Query results.
	 */
	public function query_posts( array $args ) {
		$defaults = [
			'post_type'      => 'post',
			'posts_per_page' => 10,
			'paged'          => 1,
			'post_status'    => 'publish',
		];

		$args = wp_parse_args( $args, $defaults );

		$query = new \WP_Query( $args );

		return [
			'posts'       => array_map( [ $this, 'format_post' ], $query->posts ),
			'total'       => $query->found_posts,
			'total_pages' => $query->max_num_pages,
			'page'        => $args['paged'],
			'per_page'    => $args['posts_per_page'],
		];
	}

	/**
	 * Format post data for response.
	 *
	 * @since 0.1.0
	 *
	 * @param \WP_Post $post Post object.
	 * @return array Formatted post data.
	 */
	private function format_post( \WP_Post $post ): array {
		$post_type_obj = get_post_type_object( $post->post_type );

		return [
			'ID'            => $post->ID,
			'post_author'   => $post->post_author,
			'post_date'     => $post->post_date,
			'post_date_gmt' => $post->post_date_gmt,
			'post_content'  => $post->post_content,
			'post_title'    => $post->post_title,
			'post_excerpt'  => $post->post_excerpt,
			'post_status'   => $post->post_status,
			'post_name'     => $post->post_name,
			'post_modified' => $post->post_modified,
			'post_parent'   => $post->post_parent,
			'guid'          => $post->guid,
			'post_type'     => $post->post_type,
			'post_type_label' => $post_type_obj ? $post_type_obj->labels->singular_name : $post->post_type,
			'permalink'     => get_permalink( $post ),
			'edit_link'     => get_edit_post_link( $post, 'raw' ),
			'meta'          => get_post_meta( $post->ID ),
			'taxonomies'    => $this->get_post_taxonomies( $post ),
			'featured_image' => $this->get_featured_image( $post ),
		];
	}

	/**
	 * Get post taxonomies and terms.
	 *
	 * @since 0.1.0
	 *
	 * @param \WP_Post $post Post object.
	 * @return array Taxonomies and terms.
	 */
	private function get_post_taxonomies( \WP_Post $post ): array {
		$taxonomies = get_object_taxonomies( $post->post_type );
		$data       = [];

		foreach ( $taxonomies as $taxonomy ) {
			$terms = wp_get_object_terms( $post->ID, $taxonomy );

			if ( ! is_wp_error( $terms ) ) {
				$data[ $taxonomy ] = array_map(
					function ( $term ) {
						return [
							'id'   => $term->term_id,
							'name' => $term->name,
							'slug' => $term->slug,
						];
					},
					$terms
				);
			}
		}

		return $data;
	}

	/**
	 * Get featured image data.
	 *
	 * @since 0.1.0
	 *
	 * @param \WP_Post $post Post object.
	 * @return array|null Featured image data or null.
	 */
	private function get_featured_image( \WP_Post $post ): ?array {
		$thumbnail_id = get_post_thumbnail_id( $post );

		if ( ! $thumbnail_id ) {
			return null;
		}

		$image = wp_get_attachment_image_src( $thumbnail_id, 'full' );

		if ( ! $image ) {
			return null;
		}

		return [
			'id'     => $thumbnail_id,
			'url'    => $image[0],
			'width'  => $image[1],
			'height' => $image[2],
			'alt'    => get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true ),
		];
	}
}
