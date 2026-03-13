<?php
/**
 * Media Management.
 *
 * Provides upload, update, delete operations for WordPress media.
 *
 * @package JazzSequence\MCP_Abilities
 */

declare( strict_types=1 );

namespace JazzSequence\MCP_Abilities\Abilities\Media;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Media Manager class.
 *
 * @since 0.1.0
 */
class Media_Manager {

	/**
	 * Upload media from URL.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Upload arguments (requires url, optional title, alt, description).
	 * @return array|\WP_Error Media attachment data or error.
	 */
	public function upload_from_url( array $args ) {
		if ( empty( $args['url'] ) ) {
			return new \WP_Error(
				'missing_url',
				__( 'Media URL is required.', 'jazzsequence-mcp-abilities' )
			);
		}

		$url = esc_url_raw( $args['url'] );

		// Download the file.
		$temp_file = download_url( $url );

		if ( is_wp_error( $temp_file ) ) {
			return $temp_file;
		}

		// Prepare file array.
		$file = [
			'name'     => $args['filename'] ?? basename( $url ),
			'tmp_name' => $temp_file,
		];

		// Upload the file.
		$attachment_id = media_handle_sideload( $file, 0, $args['title'] ?? '' );

		// Clean up temp file.
		if ( file_exists( $temp_file ) ) {
			// phpcs:ignore Generic.PHP.NoSilencedErrors.Forbidden, WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink -- Necessary cleanup of temporary file during media upload.
			@unlink( $temp_file );
		}

		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		// Set alt text if provided.
		if ( ! empty( $args['alt'] ) ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $args['alt'] ) );
		}

		// Set description if provided.
		if ( ! empty( $args['description'] ) ) {
			wp_update_post(
				[
					'ID'           => $attachment_id,
					'post_content' => sanitize_textarea_field( $args['description'] ),
				]
			);
		}

		return $this->get_attachment( [ 'ID' => $attachment_id ] );
	}

	/**
	 * Upload media from base64 data.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Upload arguments (requires data, filename, optional title, alt).
	 * @return array|\WP_Error Media attachment data or error.
	 */
	public function upload_from_base64( array $args ) {
		if ( empty( $args['data'] ) ) {
			return new \WP_Error(
				'missing_data',
				__( 'Base64 data is required.', 'jazzsequence-mcp-abilities' )
			);
		}

		if ( empty( $args['filename'] ) ) {
			return new \WP_Error(
				'missing_filename',
				__( 'Filename is required.', 'jazzsequence-mcp-abilities' )
			);
		}

		// Decode base64 data.
		$data = base64_decode( $args['data'], true );

		if ( false === $data ) {
			return new \WP_Error(
				'invalid_base64',
				__( 'Invalid base64 data.', 'jazzsequence-mcp-abilities' )
			);
		}

		// Create temp file.
		$upload_dir = wp_upload_dir();
		$temp_file  = $upload_dir['path'] . '/' . sanitize_file_name( $args['filename'] );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents, WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents -- Necessary for media upload.
		if ( false === file_put_contents( $temp_file, $data ) ) {
			return new \WP_Error(
				'upload_failed',
				__( 'Failed to write file.', 'jazzsequence-mcp-abilities' )
			);
		}

		// Prepare file array.
		$file = [
			'name'     => $args['filename'],
			'tmp_name' => $temp_file,
		];

		// Upload the file.
		$attachment_id = media_handle_sideload( $file, 0, $args['title'] ?? '' );

		// Clean up temp file.
		if ( file_exists( $temp_file ) ) {
			// phpcs:ignore Generic.PHP.NoSilencedErrors.Forbidden, WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink -- Necessary cleanup of temporary file during media upload.
			@unlink( $temp_file );
		}

		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		// Set alt text if provided.
		if ( ! empty( $args['alt'] ) ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $args['alt'] ) );
		}

		return $this->get_attachment( [ 'ID' => $attachment_id ] );
	}

	/**
	 * Update media attachment.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Update arguments (requires ID).
	 * @return array|\WP_Error Updated attachment data or error.
	 */
	public function update_attachment( array $args ) {
		if ( empty( $args['ID'] ) ) {
			return new \WP_Error(
				'missing_attachment_id',
				__( 'Attachment ID is required.', 'jazzsequence-mcp-abilities' )
			);
		}

		$attachment_id = absint( $args['ID'] );
		$attachment    = get_post( $attachment_id );

		if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
			return new \WP_Error(
				'attachment_not_found',
				sprintf(
					/* translators: %d: Attachment ID */
					__( 'Attachment with ID %d not found.', 'jazzsequence-mcp-abilities' ),
					$attachment_id
				)
			);
		}

		// Update post fields.
		$update_args = [
			'ID' => $attachment_id,
		];

		if ( isset( $args['title'] ) ) {
			$update_args['post_title'] = sanitize_text_field( $args['title'] );
		}

		if ( isset( $args['caption'] ) ) {
			$update_args['post_excerpt'] = sanitize_textarea_field( $args['caption'] );
		}

		if ( isset( $args['description'] ) ) {
			$update_args['post_content'] = sanitize_textarea_field( $args['description'] );
		}

		if ( count( $update_args ) > 1 ) {
			$result = wp_update_post( $update_args, true );

			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		// Update alt text.
		if ( isset( $args['alt'] ) ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $args['alt'] ) );
		}

		return $this->get_attachment( [ 'ID' => $attachment_id ] );
	}

	/**
	 * Delete media attachment.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Deletion arguments (requires ID, optional force_delete).
	 * @return array|\WP_Error Deletion result or error.
	 */
	public function delete_attachment( array $args ) {
		if ( empty( $args['ID'] ) ) {
			return new \WP_Error(
				'missing_attachment_id',
				__( 'Attachment ID is required.', 'jazzsequence-mcp-abilities' )
			);
		}

		$attachment_id = absint( $args['ID'] );
		$force_delete  = ! empty( $args['force_delete'] );

		$attachment = get_post( $attachment_id );

		if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
			return new \WP_Error(
				'attachment_not_found',
				sprintf(
					/* translators: %d: Attachment ID */
					__( 'Attachment with ID %d not found.', 'jazzsequence-mcp-abilities' ),
					$attachment_id
				)
			);
		}

		// Store attachment data before deletion.
		$attachment_data = $this->format_attachment( $attachment );

		// Delete the attachment.
		$result = wp_delete_attachment( $attachment_id, $force_delete );

		if ( ! $result ) {
			return new \WP_Error(
				'deletion_failed',
				__( 'Failed to delete attachment.', 'jazzsequence-mcp-abilities' )
			);
		}

		return [
			'deleted'      => true,
			'force_delete' => $force_delete,
			'attachment'   => $attachment_data,
		];
	}

	/**
	 * Get attachment by ID.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Query arguments (requires ID).
	 * @return array|\WP_Error Attachment data or error.
	 */
	public function get_attachment( array $args ) {
		if ( empty( $args['ID'] ) ) {
			return new \WP_Error(
				'missing_attachment_id',
				__( 'Attachment ID is required.', 'jazzsequence-mcp-abilities' )
			);
		}

		$attachment_id = absint( $args['ID'] );
		$attachment    = get_post( $attachment_id );

		if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
			return new \WP_Error(
				'attachment_not_found',
				sprintf(
					/* translators: %d: Attachment ID */
					__( 'Attachment with ID %d not found.', 'jazzsequence-mcp-abilities' ),
					$attachment_id
				)
			);
		}

		return $this->format_attachment( $attachment );
	}

	/**
	 * Format attachment data for response.
	 *
	 * @since 0.1.0
	 *
	 * @param \WP_Post $attachment Attachment post object.
	 * @return array Formatted attachment data.
	 */
	private function format_attachment( \WP_Post $attachment ): array {
		$metadata = wp_get_attachment_metadata( $attachment->ID );

		return [
			'ID'          => $attachment->ID,
			'title'       => $attachment->post_title,
			'filename'    => basename( get_attached_file( $attachment->ID ) ),
			'url'         => wp_get_attachment_url( $attachment->ID ),
			'link'        => get_attachment_link( $attachment->ID ),
			'alt'         => get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ),
			'caption'     => $attachment->post_excerpt,
			'description' => $attachment->post_content,
			'mime_type'   => $attachment->post_mime_type,
			'type'        => wp_attachment_is_image( $attachment->ID ) ? 'image' : 'file',
			'uploaded'    => $attachment->post_date,
			'modified'    => $attachment->post_modified,
			'metadata'    => $metadata,
			'sizes'       => $this->get_image_sizes( $attachment->ID ),
		];
	}

	/**
	 * Get image sizes if attachment is an image.
	 *
	 * @since 0.1.0
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return array|null Image sizes or null.
	 */
	private function get_image_sizes( int $attachment_id ): ?array {
		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return null;
		}

		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.get_intermediate_image_sizes_get_intermediate_image_sizes -- Not a VIP site.
		$sizes      = get_intermediate_image_sizes();
		$size_data  = [];

		foreach ( $sizes as $size ) {
			$image = wp_get_attachment_image_src( $attachment_id, $size );

			if ( $image ) {
				$size_data[ $size ] = [
					'url'    => $image[0],
					'width'  => $image[1],
					'height' => $image[2],
				];
			}
		}

		return $size_data;
	}
}
