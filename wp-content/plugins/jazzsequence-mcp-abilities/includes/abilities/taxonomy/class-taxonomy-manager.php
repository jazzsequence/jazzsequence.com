<?php
/**
 * Taxonomy and Term Management.
 *
 * Provides create, update, delete operations for taxonomies and terms.
 *
 * @package JazzSequence\MCP_Abilities
 */

declare( strict_types=1 );

namespace JazzSequence\MCP_Abilities\Abilities\Taxonomy;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Taxonomy Manager class.
 *
 * @since 0.1.0
 */
class Taxonomy_Manager {

	/**
	 * Create a new term.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Term creation arguments.
	 * @return array|\WP_Error Term data or error.
	 */
	public function create_term( array $args ) {
		if ( empty( $args['name'] ) ) {
			return new \WP_Error(
				'missing_name',
				__( 'Term name is required.', 'jazzsequence-mcp-abilities' )
			);
		}

		if ( empty( $args['taxonomy'] ) ) {
			return new \WP_Error(
				'missing_taxonomy',
				__( 'Taxonomy is required.', 'jazzsequence-mcp-abilities' )
			);
		}

		if ( ! taxonomy_exists( $args['taxonomy'] ) ) {
			return new \WP_Error(
				'invalid_taxonomy',
				sprintf(
					/* translators: %s: Taxonomy name */
					__( 'Taxonomy "%s" does not exist.', 'jazzsequence-mcp-abilities' ),
					$args['taxonomy']
				)
			);
		}

		$term_args = [];

		if ( ! empty( $args['description'] ) ) {
			$term_args['description'] = $args['description'];
		}

		if ( ! empty( $args['slug'] ) ) {
			$term_args['slug'] = $args['slug'];
		}

		if ( ! empty( $args['parent'] ) ) {
			$term_args['parent'] = absint( $args['parent'] );
		}

		$result = wp_insert_term( $args['name'], $args['taxonomy'], $term_args );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $this->get_term(
			[
				'term_id'  => $result['term_id'],
				'taxonomy' => $args['taxonomy'],
			]
		);
	}

	/**
	 * Update an existing term.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Term update arguments.
	 * @return array|\WP_Error Updated term data or error.
	 */
	public function update_term( array $args ) {
		if ( empty( $args['term_id'] ) ) {
			return new \WP_Error(
				'missing_term_id',
				__( 'Term ID is required.', 'jazzsequence-mcp-abilities' )
			);
		}

		if ( empty( $args['taxonomy'] ) ) {
			return new \WP_Error(
				'missing_taxonomy',
				__( 'Taxonomy is required.', 'jazzsequence-mcp-abilities' )
			);
		}

		$term_id  = absint( $args['term_id'] );
		$taxonomy = $args['taxonomy'];

		$term = get_term( $term_id, $taxonomy );

		if ( ! $term || is_wp_error( $term ) ) {
			return new \WP_Error(
				'term_not_found',
				sprintf(
					/* translators: %d: Term ID */
					__( 'Term with ID %d not found.', 'jazzsequence-mcp-abilities' ),
					$term_id
				)
			);
		}

		$update_args = [];

		if ( isset( $args['name'] ) ) {
			$update_args['name'] = $args['name'];
		}

		if ( isset( $args['slug'] ) ) {
			$update_args['slug'] = $args['slug'];
		}

		if ( isset( $args['description'] ) ) {
			$update_args['description'] = $args['description'];
		}

		if ( isset( $args['parent'] ) ) {
			$update_args['parent'] = absint( $args['parent'] );
		}

		$result = wp_update_term( $term_id, $taxonomy, $update_args );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $this->get_term(
			[
				'term_id'  => $result['term_id'],
				'taxonomy' => $taxonomy,
			]
		);
	}

	/**
	 * Delete a term.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Deletion arguments.
	 * @return array|\WP_Error Deletion result or error.
	 */
	public function delete_term( array $args ) {
		if ( empty( $args['term_id'] ) ) {
			return new \WP_Error(
				'missing_term_id',
				__( 'Term ID is required.', 'jazzsequence-mcp-abilities' )
			);
		}

		if ( empty( $args['taxonomy'] ) ) {
			return new \WP_Error(
				'missing_taxonomy',
				__( 'Taxonomy is required.', 'jazzsequence-mcp-abilities' )
			);
		}

		$term_id  = absint( $args['term_id'] );
		$taxonomy = $args['taxonomy'];

		$term = get_term( $term_id, $taxonomy );

		if ( ! $term || is_wp_error( $term ) ) {
			return new \WP_Error(
				'term_not_found',
				sprintf(
					/* translators: %d: Term ID */
					__( 'Term with ID %d not found.', 'jazzsequence-mcp-abilities' ),
					$term_id
				)
			);
		}

		$term_data = $this->format_term( $term );

		$result = wp_delete_term( $term_id, $taxonomy );

		if ( is_wp_error( $result ) || ! $result ) {
			return new \WP_Error(
				'deletion_failed',
				__( 'Failed to delete term.', 'jazzsequence-mcp-abilities' )
			);
		}

		return [
			'deleted' => true,
			'term'    => $term_data,
		];
	}

	/**
	 * Get term by ID.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Query arguments.
	 * @return array|\WP_Error Term data or error.
	 */
	public function get_term( array $args ) {
		if ( empty( $args['term_id'] ) ) {
			return new \WP_Error(
				'missing_term_id',
				__( 'Term ID is required.', 'jazzsequence-mcp-abilities' )
			);
		}

		if ( empty( $args['taxonomy'] ) ) {
			return new \WP_Error(
				'missing_taxonomy',
				__( 'Taxonomy is required.', 'jazzsequence-mcp-abilities' )
			);
		}

		$term = get_term( absint( $args['term_id'] ), $args['taxonomy'] );

		if ( ! $term || is_wp_error( $term ) ) {
			return new \WP_Error(
				'term_not_found',
				sprintf(
					/* translators: %d: Term ID */
					__( 'Term with ID %d not found.', 'jazzsequence-mcp-abilities' ),
					absint( $args['term_id'] )
				)
			);
		}

		return $this->format_term( $term );
	}

	/**
	 * Format term data for response.
	 *
	 * @since 0.1.0
	 *
	 * @param \WP_Term $term Term object.
	 * @return array Formatted term data.
	 */
	private function format_term( \WP_Term $term ): array {
		return [
			'term_id'     => $term->term_id,
			'name'        => $term->name,
			'slug'        => $term->slug,
			'description' => $term->description,
			'parent'      => $term->parent,
			'count'       => $term->count,
			'taxonomy'    => $term->taxonomy,
			'link'        => get_term_link( $term ),
		];
	}
}
