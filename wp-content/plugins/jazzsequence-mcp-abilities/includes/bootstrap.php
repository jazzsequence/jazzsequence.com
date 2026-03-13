<?php
/**
 * Bootstrap file for JazzSequence MCP Abilities plugin.
 *
 * Handles plugin initialization, ability registration, and feature loading.
 *
 * @package JazzSequence\MCP_Abilities
 */

declare( strict_types=1 );

namespace JazzSequence\MCP_Abilities;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bootstrap the plugin.
 *
 * @since 0.1.0
 */
function bootstrap(): void {
	// Load required files.
	require_once JSMCP_PLUGIN_DIR . 'includes/class-security.php';
	require_once JSMCP_PLUGIN_DIR . 'includes/class-audit-log.php';
	require_once JSMCP_PLUGIN_DIR . 'includes/helpers.php';

	// Register ability category.
	add_action( 'wp_abilities_api_init', __NAMESPACE__ . '\register_ability_category' );

	// Register all abilities.
	add_action( 'wp_abilities_api_init', __NAMESPACE__ . '\register_abilities' );

	// Initialize security features.
	Security\init();

	// Initialize audit logging.
	Audit_Log\init();
}

/**
 * Register the plugin's ability category.
 *
 * @since 0.1.0
 */
function register_ability_category(): void {
	if ( ! function_exists( 'wp_register_ability_category' ) ) {
		return;
	}

	wp_register_ability_category(
		JSMCP_ABILITY_CATEGORY,
		array(
			'label'       => __( 'JazzSequence MCP', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'Comprehensive site management abilities for AI-powered WordPress administration via MCP.', 'jazzsequence-mcp-abilities' ),
		)
	);
}

/**
 * Register all plugin abilities.
 *
 * @since 0.1.0
 */
function register_abilities(): void {
	// Discovery Abilities (Read-Only Introspection).
	register_discovery_abilities();

	// Content Management Abilities (CRUD).
	register_content_abilities();

	// Media Management Abilities.
	register_media_abilities();

	// Taxonomy Management Abilities.
	register_taxonomy_abilities();

	// User Management Abilities.
	register_user_abilities();

	// System Management Abilities.
	register_system_abilities();

	// Configuration Management Abilities.
	register_configuration_abilities();
}

/**
 * Register discovery abilities.
 *
 * @since 0.1.0
 */
function register_discovery_abilities(): void {
	$discovery_abilities = array(
		'discover-post-types'     => array(
			'label'       => __( 'Discover Post Types', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'Get detailed information about all registered post types, their capabilities, supports, labels, and registered meta.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'read',
		),
		'discover-taxonomies'     => array(
			'label'       => __( 'Discover Taxonomies', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'Get detailed information about all taxonomies, their terms, hierarchies, and relationships.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'read',
		),
		'discover-plugins'        => array(
			'label'       => __( 'Discover Plugins', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'List all active plugins with versions, descriptions, and capabilities they provide.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'activate_plugins',
		),
		'discover-theme-structure' => array(
			'label'       => __( 'Discover Theme Structure', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'Get information about active theme, parent/child relationships, templates, widget areas, and theme features.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'edit_theme_options',
		),
		'discover-custom-fields'  => array(
			'label'       => __( 'Discover Custom Fields', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'List all registered custom fields, meta boxes, and field groups from CMB2, ACF, and other frameworks.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'edit_posts',
		),
		'discover-menus'          => array(
			'label'       => __( 'Discover Menus', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'Get all registered menu locations, menus, and complete menu structures with items.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'edit_theme_options',
		),
		'discover-shortcodes'     => array(
			'label'       => __( 'Discover Shortcodes', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'List all registered shortcodes and their signatures.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'edit_posts',
		),
		'discover-blocks'         => array(
			'label'       => __( 'Discover Blocks', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'List all registered blocks, block patterns, and block templates.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'edit_posts',
		),
		'discover-hooks'          => array(
			'label'       => __( 'Discover Hooks', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'Get information about registered action and filter hooks with attached callbacks.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'manage_options',
		),
		'discover-options'        => array(
			'label'       => __( 'Discover Options', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'List site options, theme mods, and customizer settings.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'manage_options',
		),
		'discover-rewrite-rules'  => array(
			'label'       => __( 'Discover Rewrite Rules', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'Get permalink structure and custom rewrite rules.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'manage_options',
		),
		'discover-capabilities'   => array(
			'label'       => __( 'Discover Capabilities', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'List all user roles and their capabilities.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'list_users',
		),
		'discover-cron-jobs'      => array(
			'label'       => __( 'Discover Cron Jobs', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'List scheduled tasks (cron jobs) with their schedules and frequencies.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'manage_options',
		),
	);

	foreach ( $discovery_abilities as $ability_id => $config ) {
		$ability_name = JSMCP_ABILITY_CATEGORY . '/' . $ability_id;

		wp_register_ability(
			$ability_name,
			array(
				'label'       => $config['label'],
				'description' => $config['description'],
				'category'    => JSMCP_ABILITY_CATEGORY,
				'input'       => array(
					'type'       => 'object',
					'properties' => array(
						'format' => array(
							'type'        => 'string',
							'enum'        => array( 'json', 'markdown' ),
							'default'     => 'json',
							'description' => __( 'Output format for the discovery results.', 'jazzsequence-mcp-abilities' ),
						),
					),
				),
				'output'      => array(
					'type'        => 'object',
					'description' => __( 'Discovery results in requested format.', 'jazzsequence-mcp-abilities' ),
				),
				'execute'     => function ( $args ) use ( $ability_id ) {
					return execute_discovery_ability( $ability_id, $args );
				},
				'permission'  => function () use ( $config ) {
					return current_user_can( $config['capability'] );
				},
				'meta'        => array(
					'show_in_rest' => true,
				),
			)
		);
	}
}

/**
 * Register content management abilities.
 *
 * @since 0.1.0
 */
function register_content_abilities(): void {
	// These will be implemented in phase 2.
	// Placeholder for now.
}

/**
 * Register media management abilities.
 *
 * @since 0.1.0
 */
function register_media_abilities(): void {
	// Phase 2.
}

/**
 * Register taxonomy management abilities.
 *
 * @since 0.1.0
 */
function register_taxonomy_abilities(): void {
	// Phase 2.
}

/**
 * Register user management abilities.
 *
 * @since 0.1.0
 */
function register_user_abilities(): void {
	// Phase 2.
}

/**
 * Register system management abilities.
 *
 * @since 0.1.0
 */
function register_system_abilities(): void {
	// Phase 2.
}

/**
 * Register configuration management abilities.
 *
 * @since 0.1.0
 */
function register_configuration_abilities(): void {
	// Phase 2.
}

/**
 * Execute a discovery ability.
 *
 * @since 0.1.0
 *
 * @param string $ability_id The ability ID (without namespace).
 * @param array  $args       Ability arguments.
 * @return array|\WP_Error Discovery results or error.
 */
function execute_discovery_ability( string $ability_id, array $args ) {
	$format = $args['format'] ?? 'json';

	// Load discovery implementations.
	require_once JSMCP_PLUGIN_DIR . 'includes/abilities/discovery/class-discovery-engine.php';

	$discovery = new Abilities\Discovery\Discovery_Engine();

	switch ( $ability_id ) {
		case 'discover-post-types':
			return $discovery->discover_post_types( $format );

		case 'discover-taxonomies':
			return $discovery->discover_taxonomies( $format );

		case 'discover-plugins':
			return $discovery->discover_plugins( $format );

		case 'discover-theme-structure':
			return $discovery->discover_theme_structure( $format );

		case 'discover-custom-fields':
			return $discovery->discover_custom_fields( $format );

		case 'discover-menus':
			return $discovery->discover_menus( $format );

		case 'discover-shortcodes':
			return $discovery->discover_shortcodes( $format );

		case 'discover-blocks':
			return $discovery->discover_blocks( $format );

		case 'discover-hooks':
			return $discovery->discover_hooks( $format );

		case 'discover-options':
			return $discovery->discover_options( $format );

		case 'discover-rewrite-rules':
			return $discovery->discover_rewrite_rules( $format );

		case 'discover-capabilities':
			return $discovery->discover_capabilities( $format );

		case 'discover-cron-jobs':
			return $discovery->discover_cron_jobs( $format );

		default:
			return new \WP_Error(
				'unknown_ability',
				sprintf(
					/* translators: %s: Ability ID */
					__( 'Unknown discovery ability: %s', 'jazzsequence-mcp-abilities' ),
					$ability_id
				)
			);
	}
}
