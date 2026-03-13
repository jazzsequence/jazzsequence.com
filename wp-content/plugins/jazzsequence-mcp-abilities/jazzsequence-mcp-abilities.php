<?php
/**
 * Plugin Name: JazzSequence MCP Abilities
 * Plugin URI: https://github.com/jazzsequence/jazzsequence.com
 * Description: Exposes comprehensive WordPress abilities via MCP for AI-powered site management. Provides full read/write access to content, configuration, and system operations through the Model Context Protocol.
 * Version: 0.1.0
 * Requires at least: 6.9
 * Requires PHP: 8.2
 * Author: Chris Reynolds
 * Author URI: https://jazzsequence.com
 * License: GPL-2.0-or-later
 * License URI: https://spdx.org/licenses/GPL-2.0-or-later.html
 * Text Domain: jazzsequence-mcp-abilities
 * Domain Path: /languages
 *
 * @package JazzSequence\MCP_Abilities
 */

declare( strict_types=1 );

namespace JazzSequence\MCP_Abilities;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'JSMCP_VERSION', '0.1.0' );
define( 'JSMCP_PLUGIN_FILE', __FILE__ );
define( 'JSMCP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'JSMCP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'JSMCP_MIN_PHP_VERSION', '8.2' );
define( 'JSMCP_MIN_WP_VERSION', '6.9' );
define( 'JSMCP_ABILITY_CATEGORY', 'jazzsequence-mcp' );

/**
 * Check if required dependencies are available.
 *
 * @since 0.1.0
 *
 * @return bool True if dependencies are met, false otherwise.
 */
function check_dependencies(): bool {
	// Check if Abilities API is available.
	if ( ! function_exists( 'wp_register_ability' ) ) {
		add_action(
			'admin_notices',
			function () {
				?>
				<div class="notice notice-error">
					<p>
						<?php
						echo wp_kses_post(
							sprintf(
								/* translators: %s: Plugin name */
								__( '<strong>%s</strong> requires the WordPress Abilities API (WordPress 6.9+). Please update WordPress or install the Abilities API plugin.', 'jazzsequence-mcp-abilities' ),
								'JazzSequence MCP Abilities'
							)
						);
						?>
					</p>
				</div>
				<?php
			}
		);
		return false;
	}

	// Check if MCP Adapter is available.
	if ( ! class_exists( 'WP\MCP\Core\McpAdapter' ) ) {
		add_action(
			'admin_notices',
			function () {
				?>
				<div class="notice notice-warning">
					<p>
						<?php
						echo wp_kses_post(
							sprintf(
								/* translators: %s: Plugin name */
								__( '<strong>%s</strong> requires the WordPress MCP Adapter to expose abilities via MCP. Please install the MCP Adapter package.', 'jazzsequence-mcp-abilities' ),
								'JazzSequence MCP Abilities'
							)
						);
						?>
					</p>
				</div>
				<?php
			}
		);
		// MCP Adapter is recommended but not required - abilities will still register.
	}

	return true;
}

/**
 * Initialize the plugin.
 *
 * @since 0.1.0
 */
function init(): void {
	// Check dependencies first.
	if ( ! check_dependencies() ) {
		return;
	}

	// Load plugin files.
	require_once JSMCP_PLUGIN_DIR . 'includes/bootstrap.php';

	// Initialize the plugin.
	bootstrap();
}

// Hook into plugins_loaded to ensure WordPress and dependencies are ready.
add_action( 'plugins_loaded', __NAMESPACE__ . '\init', 5 );

/**
 * Plugin activation hook.
 *
 * @since 0.1.0
 */
function activate(): void {
	// Verify dependencies on activation.
	if ( ! function_exists( 'wp_register_ability' ) ) {
		wp_die(
			esc_html__( 'JazzSequence MCP Abilities requires WordPress 6.9+ or the Abilities API plugin.', 'jazzsequence-mcp-abilities' ),
			esc_html__( 'Plugin Activation Error', 'jazzsequence-mcp-abilities' ),
			[ 'back_link' => true ]
		);
	}

	// Create MCP user and role on activation.
	require_once JSMCP_PLUGIN_DIR . 'includes/class-security.php';
	Security\create_mcp_user_on_activation();
}

register_activation_hook( __FILE__, __NAMESPACE__ . '\activate' );

/**
 * Plugin deactivation hook.
 *
 * @since 0.1.0
 */
function deactivate(): void {
	/*
	 * Note: We intentionally do NOT delete the MCP user on deactivation.
	 * This preserves the user account and application passwords.
	 * Admin must manually delete the user if desired.
	 */
}

register_deactivation_hook( __FILE__, __NAMESPACE__ . '\deactivate' );
