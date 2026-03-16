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

	// Register ability category directly (not via hook).
	register_ability_category();

	// Register all abilities directly (not via hook).
	register_abilities();

	// Initialize security features.
	Security\init();

	// Initialize audit logging.
	Audit_Log\init();

	// Register MCP integration filter to expose ALL abilities.
	register_mcp_integration();
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
		[
			'label'       => __( 'JazzSequence MCP', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'Comprehensive site management abilities for AI-powered WordPress administration via MCP.', 'jazzsequence-mcp-abilities' ),
		]
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
	if ( ! function_exists( 'wp_register_ability' ) ) {
		return;
	}

	$discovery_abilities = [
		'discover-post-types'     => [
			'label'       => __( 'Discover Post Types', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'Get detailed information about all registered post types, their capabilities, supports, labels, and registered meta.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'read',
		],
		'discover-taxonomies'     => [
			'label'       => __( 'Discover Taxonomies', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'Get detailed information about all taxonomies, their terms, hierarchies, and relationships.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'read',
		],
		'discover-plugins'        => [
			'label'       => __( 'Discover Plugins', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'List all active plugins with versions, descriptions, and capabilities they provide.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'activate_plugins',
		],
		'discover-theme-structure' => [
			'label'       => __( 'Discover Theme Structure', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'Get information about active theme, parent/child relationships, templates, widget areas, and theme features.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'edit_theme_options',
		],
		'discover-custom-fields'  => [
			'label'       => __( 'Discover Custom Fields', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'List all registered custom fields, meta boxes, and field groups from CMB2, ACF, and other frameworks.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'edit_posts',
		],
		'discover-menus'          => [
			'label'       => __( 'Discover Menus', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'Get all registered menu locations, menus, and complete menu structures with items.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'edit_theme_options',
		],
		'discover-shortcodes'     => [
			'label'       => __( 'Discover Shortcodes', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'List all registered shortcodes and their signatures.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'edit_posts',
		],
		'discover-blocks'         => [
			'label'       => __( 'Discover Blocks', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'List all registered blocks, block patterns, and block templates.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'edit_posts',
		],
		'discover-hooks'          => [
			'label'       => __( 'Discover Hooks', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'Get information about registered action and filter hooks with attached callbacks.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'manage_options',
		],
		'discover-options'        => [
			'label'       => __( 'Discover Options', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'List site options, theme mods, and customizer settings.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'manage_options',
		],
		'discover-rewrite-rules'  => [
			'label'       => __( 'Discover Rewrite Rules', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'Get permalink structure and custom rewrite rules.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'manage_options',
		],
		'discover-capabilities'   => [
			'label'       => __( 'Discover Capabilities', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'List all user roles and their capabilities.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'list_users',
		],
		'discover-cron-jobs'      => [
			'label'       => __( 'Discover Cron Jobs', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'List scheduled tasks (cron jobs) with their schedules and frequencies.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'manage_options',
		],
	];

	foreach ( $discovery_abilities as $ability_id => $config ) {
		$ability_name = JSMCP_ABILITY_CATEGORY . '/' . $ability_id;

		wp_register_ability(
			$ability_name,
			[
				'label'       => $config['label'],
				'description' => $config['description'],
				'category'    => JSMCP_ABILITY_CATEGORY,
				'input'       => [
					'type'       => 'object',
					'properties' => [
						'format' => [
							'type'        => 'string',
							'enum'        => [ 'json', 'markdown' ],
							'default'     => 'json',
							'description' => __( 'Output format for the discovery results.', 'jazzsequence-mcp-abilities' ),
						],
					],
				],
				'output'      => [
					'type'        => 'object',
					'description' => __( 'Discovery results in requested format.', 'jazzsequence-mcp-abilities' ),
				],
				'execute'     => function ( $args ) use ( $ability_id ) {
					return execute_discovery_ability( $ability_id, $args );
				},
				'permission'  => function () use ( $config ) {
					return current_user_can( $config['capability'] );
				},
				'meta'        => [
					'show_in_rest' => true,
					'mcp'          => [
						'public' => true,
						'type'   => 'tool',
					],
				],
			]
		);
	}
}

/**
 * Register content management abilities.
 *
 * @since 0.1.0
 */
function register_content_abilities(): void {
	if ( ! function_exists( 'wp_register_ability' ) ) {
		return;
	}

	require_once JSMCP_PLUGIN_DIR . 'includes/abilities/content/class-content-manager.php';

	$content_abilities = [
		'create-post'  => [
			'label'       => __( 'Create Post', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'Create a new post of any post type.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'edit_posts',
			'method'      => 'create_post',
		],
		'update-post'  => [
			'label'       => __( 'Update Post', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'Update an existing post.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'edit_posts',
			'method'      => 'update_post',
		],
		'delete-post'  => [
			'label'       => __( 'Delete Post', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'Delete a post.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'delete_posts',
			'method'      => 'delete_post',
		],
		'get-post'     => [
			'label'       => __( 'Get Post', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'Get a post by ID.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'read',
			'method'      => 'get_post',
		],
		'query-posts'  => [
			'label'       => __( 'Query Posts', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'Query posts with custom arguments.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'read',
			'method'      => 'query_posts',
		],
	];

	$manager = new Abilities\Content\Content_Manager();

	foreach ( $content_abilities as $ability_id => $config ) {
		wp_register_ability(
			JSMCP_ABILITY_CATEGORY . '/' . $ability_id,
			[
				'label'       => $config['label'],
				'description' => $config['description'],
				'category'    => JSMCP_ABILITY_CATEGORY,
				'input'       => [
					'type'       => 'object',
					'properties' => [],
				],
				'output'      => [
					'type' => 'object',
				],
				'execute'     => [ $manager, $config['method'] ],
				'permission'  => function () use ( $config ) {
					return current_user_can( $config['capability'] );
				},
				'meta'        => [
					'show_in_rest' => true,
					'mcp'          => [
						'public' => true,
						'type'   => 'tool',
					],
				],
			]
		);
	}
}

/**
 * Register media management abilities.
 *
 * @since 0.1.0
 */
function register_media_abilities(): void {
	if ( ! function_exists( 'wp_register_ability' ) ) {
		return;
	}

	require_once JSMCP_PLUGIN_DIR . 'includes/abilities/media/class-media-manager.php';

	$media_abilities = [
		'upload-media-url'     => [
			'label'       => __( 'Upload Media from URL', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'Upload media from a URL.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'upload_files',
			'method'      => 'upload_from_url',
		],
		'upload-media-base64'  => [
			'label'       => __( 'Upload Media from Base64', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'Upload media from base64 encoded data.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'upload_files',
			'method'      => 'upload_from_base64',
		],
		'update-media'         => [
			'label'       => __( 'Update Media', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'Update media attachment metadata.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'upload_files',
			'method'      => 'update_attachment',
		],
		'delete-media'         => [
			'label'       => __( 'Delete Media', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'Delete a media attachment.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'delete_files',
			'method'      => 'delete_attachment',
		],
		'get-media'            => [
			'label'       => __( 'Get Media', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'Get media attachment by ID.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'read',
			'method'      => 'get_attachment',
		],
	];

	$manager = new Abilities\Media\Media_Manager();

	foreach ( $media_abilities as $ability_id => $config ) {
		wp_register_ability(
			JSMCP_ABILITY_CATEGORY . '/' . $ability_id,
			[
				'label'       => $config['label'],
				'description' => $config['description'],
				'category'    => JSMCP_ABILITY_CATEGORY,
				'input'       => [
					'type'       => 'object',
					'properties' => [],
				],
				'output'      => [
					'type' => 'object',
				],
				'execute'     => [ $manager, $config['method'] ],
				'permission'  => function () use ( $config ) {
					return current_user_can( $config['capability'] );
				},
				'meta'        => [
					'show_in_rest' => true,
					'mcp'          => [
						'public' => true,
						'type'   => 'tool',
					],
				],
			]
		);
	}
}

/**
 * Register taxonomy management abilities.
 *
 * @since 0.1.0
 */
function register_taxonomy_abilities(): void {
	if ( ! function_exists( 'wp_register_ability' ) ) {
		return;
	}

	require_once JSMCP_PLUGIN_DIR . 'includes/abilities/taxonomy/class-taxonomy-manager.php';

	$taxonomy_abilities = [
		'create-term' => [
			'label'       => __( 'Create Term', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'Create a new taxonomy term.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'manage_categories',
			'method'      => 'create_term',
		],
		'update-term' => [
			'label'       => __( 'Update Term', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'Update an existing term.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'manage_categories',
			'method'      => 'update_term',
		],
		'delete-term' => [
			'label'       => __( 'Delete Term', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'Delete a taxonomy term.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'manage_categories',
			'method'      => 'delete_term',
		],
		'get-term'    => [
			'label'       => __( 'Get Term', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'Get a term by ID.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'read',
			'method'      => 'get_term',
		],
	];

	$manager = new Abilities\Taxonomy\Taxonomy_Manager();

	foreach ( $taxonomy_abilities as $ability_id => $config ) {
		wp_register_ability(
			JSMCP_ABILITY_CATEGORY . '/' . $ability_id,
			[
				'label'       => $config['label'],
				'description' => $config['description'],
				'category'    => JSMCP_ABILITY_CATEGORY,
				'input'       => [
					'type'       => 'object',
					'properties' => [],
				],
				'output'      => [
					'type' => 'object',
				],
				'execute'     => [ $manager, $config['method'] ],
				'permission'  => function () use ( $config ) {
					return current_user_can( $config['capability'] );
				},
				'meta'        => [
					'show_in_rest' => true,
					'mcp'          => [
						'public' => true,
						'type'   => 'tool',
					],
				],
			]
		);
	}
}

/**
 * Register user management abilities.
 *
 * @since 0.1.0
 */
function register_user_abilities(): void {
	/*
	 * User management intentionally limited.
	 * AI should not create, modify, or delete users.
	 */
}

/**
 * Register system management abilities.
 *
 * @since 0.1.0
 */
function register_system_abilities(): void {
	if ( ! function_exists( 'wp_register_ability' ) ) {
		return;
	}

	require_once JSMCP_PLUGIN_DIR . 'includes/abilities/system/class-system-manager.php';

	$system_abilities = [
		'clear-cache'      => [
			'label'       => __( 'Clear Cache', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'Clear various WordPress caches.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'manage_options',
			'method'      => 'clear_cache',
		],
		'run-cron'         => [
			'label'       => __( 'Run Cron Job', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'Execute a cron job immediately.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'manage_options',
			'method'      => 'run_cron',
		],
		'schedule-cron'    => [
			'label'       => __( 'Schedule Cron Job', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'Schedule a new cron job.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'manage_options',
			'method'      => 'schedule_cron',
		],
		'unschedule-cron'  => [
			'label'       => __( 'Unschedule Cron Job', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'Remove a scheduled cron job.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'manage_options',
			'method'      => 'unschedule_cron',
		],
	];

	$manager = new Abilities\System\System_Manager();

	foreach ( $system_abilities as $ability_id => $config ) {
		wp_register_ability(
			JSMCP_ABILITY_CATEGORY . '/' . $ability_id,
			[
				'label'       => $config['label'],
				'description' => $config['description'],
				'category'    => JSMCP_ABILITY_CATEGORY,
				'input'       => [
					'type'       => 'object',
					'properties' => [],
				],
				'output'      => [
					'type' => 'object',
				],
				'execute'     => [ $manager, $config['method'] ],
				'permission'  => function () use ( $config ) {
					return current_user_can( $config['capability'] );
				},
				'meta'        => [
					'show_in_rest' => true,
					'mcp'          => [
						'public' => true,
						'type'   => 'tool',
					],
				],
			]
		);
	}
}

/**
 * Register configuration management abilities.
 *
 * @since 0.1.0
 */
function register_configuration_abilities(): void {
	if ( ! function_exists( 'wp_register_ability' ) ) {
		return;
	}

	require_once JSMCP_PLUGIN_DIR . 'includes/abilities/config/class-config-manager.php';

	$config_abilities = [
		'update-option' => [
			'label'       => __( 'Update Option', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'Update a WordPress option.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'manage_options',
			'method'      => 'update_option',
		],
		'get-option'    => [
			'label'       => __( 'Get Option', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'Get a WordPress option value.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'manage_options',
			'method'      => 'get_option',
		],
		'delete-option' => [
			'label'       => __( 'Delete Option', 'jazzsequence-mcp-abilities' ),
			'description' => __( 'Delete a WordPress option.', 'jazzsequence-mcp-abilities' ),
			'capability'  => 'manage_options',
			'method'      => 'delete_option',
		],
	];

	$manager = new Abilities\Config\Config_Manager();

	foreach ( $config_abilities as $ability_id => $config ) {
		wp_register_ability(
			JSMCP_ABILITY_CATEGORY . '/' . $ability_id,
			[
				'label'       => $config['label'],
				'description' => $config['description'],
				'category'    => JSMCP_ABILITY_CATEGORY,
				'input'       => [
					'type'       => 'object',
					'properties' => [],
				],
				'output'      => [
					'type' => 'object',
				],
				'execute'     => [ $manager, $config['method'] ],
				'permission'  => function () use ( $config ) {
					return current_user_can( $config['capability'] );
				},
				'meta'        => [
					'show_in_rest' => true,
					'mcp'          => [
						'public' => true,
						'type'   => 'tool',
					],
				],
			]
		);
	}
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

/**
 * Register MCP integration to expose all abilities.
 *
 * This filter exposes ALL abilities registered via the WordPress Abilities API
 * that have meta.mcp.public = true, not just jazzsequence-mcp abilities.
 *
 * This provides comprehensive MCP discovery of all site capabilities.
 *
 * @since 0.1.3
 */
function register_mcp_integration(): void {
	add_filter(
		'mcp_adapter_default_server_config',
		function ( $config ) {
			if ( ! function_exists( 'wp_get_abilities' ) ) {
				return $config;
			}

			$all_abilities = wp_get_abilities();

			foreach ( $all_abilities as $ability_name => $ability ) {
				$meta = $ability->get_meta();

				// Expose ANY ability that has mcp.public = true.
				if ( isset( $meta['mcp']['public'] ) && $meta['mcp']['public'] === true ) {
					$config['tools'][] = $ability_name;
				}
			}

			return $config;
		},
		10
	);
}
