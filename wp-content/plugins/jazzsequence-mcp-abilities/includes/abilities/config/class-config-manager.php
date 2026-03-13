<?php
/**
 * Configuration Management.
 *
 * Provides safe options and configuration updates.
 *
 * @package JazzSequence\MCP_Abilities
 */

declare( strict_types=1 );

namespace JazzSequence\MCP_Abilities\Abilities\Config;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Config Manager class.
 *
 * @since 0.1.0
 */
class Config_Manager {

	/**
	 * Protected options that should not be modified.
	 *
	 * @var array
	 */
	private $protected_options = [
		'admin_email',
		'users_can_register',
		'default_role',
		'siteurl',
		'home',
	];

	/**
	 * Update a site option.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Update arguments (requires option_name, value).
	 * @return array|\WP_Error Update result or error.
	 */
	public function update_option( array $args ) {
		if ( empty( $args['option_name'] ) ) {
			return new \WP_Error(
				'missing_option_name',
				__( 'Option name is required.', 'jazzsequence-mcp-abilities' )
			);
		}

		if ( ! isset( $args['value'] ) ) {
			return new \WP_Error(
				'missing_value',
				__( 'Option value is required.', 'jazzsequence-mcp-abilities' )
			);
		}

		$option_name = sanitize_text_field( $args['option_name'] );

		// Check if option is protected.
		if ( in_array( $option_name, $this->protected_options, true ) ) {
			return new \WP_Error(
				'protected_option',
				sprintf(
					/* translators: %s: Option name */
					__( 'Option "%s" is protected and cannot be modified via MCP.', 'jazzsequence-mcp-abilities' ),
					$option_name
				)
			);
		}

		$value = $args['value'];

		// Update the option.
		$result = update_option( $option_name, $value );

		if ( ! $result ) {
			return new \WP_Error(
				'update_failed',
				__( 'Failed to update option. Value may be unchanged.', 'jazzsequence-mcp-abilities' )
			);
		}

		return [
			'updated'     => true,
			'option_name' => $option_name,
			'value'       => get_option( $option_name ),
			'message'     => __( 'Option updated successfully.', 'jazzsequence-mcp-abilities' ),
		];
	}

	/**
	 * Get a site option.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Query arguments (requires option_name).
	 * @return array|\WP_Error Option value or error.
	 */
	public function get_option( array $args ) {
		if ( empty( $args['option_name'] ) ) {
			return new \WP_Error(
				'missing_option_name',
				__( 'Option name is required.', 'jazzsequence-mcp-abilities' )
			);
		}

		$option_name = sanitize_text_field( $args['option_name'] );
		$default     = $args['default'] ?? false;

		$value = get_option( $option_name, $default );

		return [
			'option_name' => $option_name,
			'value'       => $value,
		];
	}

	/**
	 * Delete a site option.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Deletion arguments (requires option_name).
	 * @return array|\WP_Error Deletion result or error.
	 */
	public function delete_option( array $args ) {
		if ( empty( $args['option_name'] ) ) {
			return new \WP_Error(
				'missing_option_name',
				__( 'Option name is required.', 'jazzsequence-mcp-abilities' )
			);
		}

		$option_name = sanitize_text_field( $args['option_name'] );

		// Check if option is protected.
		if ( in_array( $option_name, $this->protected_options, true ) ) {
			return new \WP_Error(
				'protected_option',
				sprintf(
					/* translators: %s: Option name */
					__( 'Option "%s" is protected and cannot be deleted via MCP.', 'jazzsequence-mcp-abilities' ),
					$option_name
				)
			);
		}

		$result = delete_option( $option_name );

		if ( ! $result ) {
			return new \WP_Error(
				'deletion_failed',
				__( 'Failed to delete option. Option may not exist.', 'jazzsequence-mcp-abilities' )
			);
		}

		return [
			'deleted'     => true,
			'option_name' => $option_name,
			'message'     => __( 'Option deleted successfully.', 'jazzsequence-mcp-abilities' ),
		];
	}
}
