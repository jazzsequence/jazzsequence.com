<?php
/**
 * Discovery Engine for WordPress introspection abilities.
 *
 * Provides comprehensive site architecture discovery for AI agents.
 *
 * @package JazzSequence\MCP_Abilities
 */

declare( strict_types=1 );

namespace JazzSequence\MCP_Abilities\Abilities\Discovery;

use function JazzSequence\MCP_Abilities\to_markdown;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Discovery Engine class.
 *
 * @since 0.1.0
 */
class Discovery_Engine {

	/**
	 * Discover all post types.
	 *
	 * @since 0.1.0
	 *
	 * @param string $format Output format (json or markdown).
	 * @return array Discovery results.
	 */
	public function discover_post_types( string $format = 'json' ): array {
		$post_types = get_post_types( [], 'objects' );
		$data       = [];

		foreach ( $post_types as $post_type ) {
			$data[ $post_type->name ] = [
				'name'          => $post_type->name,
				'label'         => $post_type->label,
				'labels'        => (array) $post_type->labels,
				'description'   => $post_type->description,
				'public'        => $post_type->public,
				'hierarchical'  => $post_type->hierarchical,
				'supports'      => get_all_post_type_supports( $post_type->name ),
				'taxonomies'    => get_object_taxonomies( $post_type->name ),
				'has_archive'   => $post_type->has_archive,
				'rewrite'       => $post_type->rewrite,
				'rest_enabled'  => $post_type->show_in_rest,
				'rest_base'     => $post_type->rest_base,
				'menu_icon'     => $post_type->menu_icon,
				'capability_type' => $post_type->capability_type,
				'capabilities'  => (array) $post_type->cap,
				'registered_meta' => $this->get_registered_meta( $post_type->name ),
			];
		}

		return $this->format_response( $data, $format, 'Post Types' );
	}

	/**
	 * Discover all taxonomies.
	 *
	 * @since 0.1.0
	 *
	 * @param string $format Output format.
	 * @return array Discovery results.
	 */
	public function discover_taxonomies( string $format = 'json' ): array {
		$taxonomies = get_taxonomies( [], 'objects' );
		$data       = [];

		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_terms(
				[
					'taxonomy'   => $taxonomy->name,
					'hide_empty' => false,
				]
			);

			$data[ $taxonomy->name ] = [
				'name'         => $taxonomy->name,
				'label'        => $taxonomy->label,
				'labels'       => (array) $taxonomy->labels,
				'description'  => $taxonomy->description,
				'public'       => $taxonomy->public,
				'hierarchical' => $taxonomy->hierarchical,
				'show_in_rest' => $taxonomy->show_in_rest,
				'rest_base'    => $taxonomy->rest_base,
				'object_types' => $taxonomy->object_type,
				'rewrite'      => $taxonomy->rewrite,
				'capabilities' => (array) $taxonomy->cap,
				'term_count'   => is_array( $terms ) ? count( $terms ) : 0,
				'terms'        => $this->format_terms( $terms ),
			];
		}

		return $this->format_response( $data, $format, 'Taxonomies' );
	}

	/**
	 * Discover active plugins.
	 *
	 * @since 0.1.0
	 *
	 * @param string $format Output format.
	 * @return array Discovery results.
	 */
	public function discover_plugins( string $format = 'json' ): array {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all_plugins    = get_plugins();
		$active_plugins = get_option( 'active_plugins', [] );
		$data           = [];

		foreach ( $active_plugins as $plugin_file ) {
			if ( ! isset( $all_plugins[ $plugin_file ] ) ) {
				continue;
			}

			$plugin_data = $all_plugins[ $plugin_file ];

			$data[ $plugin_file ] = [
				'name'        => $plugin_data['Name'],
				'version'     => $plugin_data['Version'],
				'description' => $plugin_data['Description'],
				'author'      => $plugin_data['Author'],
				'author_uri'  => $plugin_data['AuthorURI'],
				'plugin_uri'  => $plugin_data['PluginURI'],
				'network'     => $plugin_data['Network'] ?? false,
				'text_domain' => $plugin_data['TextDomain'] ?? '',
			];
		}

		return $this->format_response( $data, $format, 'Active Plugins' );
	}

	/**
	 * Discover theme structure.
	 *
	 * @since 0.1.0
	 *
	 * @param string $format Output format.
	 * @return array Discovery results.
	 */
	public function discover_theme_structure( string $format = 'json' ): array {
		$theme = wp_get_theme();
		$data  = [
			'name'            => $theme->get( 'Name' ),
			'version'         => $theme->get( 'Version' ),
			'description'     => $theme->get( 'Description' ),
			'author'          => $theme->get( 'Author' ),
			'author_uri'      => $theme->get( 'AuthorURI' ),
			'theme_uri'       => $theme->get( 'ThemeURI' ),
			'template'        => $theme->get_template(),
			'stylesheet'      => $theme->get_stylesheet(),
			'is_child_theme'  => is_child_theme(),
			'parent_theme'    => $theme->parent() ? $theme->parent()->get( 'Name' ) : null,
			'supports'        => $this->get_theme_supports(),
			'sidebars'        => $this->get_sidebars(),
			'nav_menus'       => $this->get_nav_menu_locations(),
			'template_files'  => $this->get_template_files( $theme ),
			'custom_templates' => $this->get_custom_templates(),
		];

		return $this->format_response( $data, $format, 'Theme Structure' );
	}

	/**
	 * Discover custom fields.
	 *
	 * @since 0.1.0
	 *
	 * @param string $format Output format.
	 * @return array Discovery results.
	 */
	public function discover_custom_fields( string $format = 'json' ): array {
		global $wp_meta_keys;

		$data = [
			'registered_meta' => [],
			'cmb2_fields'     => $this->get_cmb2_fields(),
		];

		// Get all registered meta.
		if ( ! empty( $wp_meta_keys ) ) {
			foreach ( $wp_meta_keys as $object_type => $meta_keys ) {
				foreach ( $meta_keys as $object_subtype => $keys ) {
					foreach ( $keys as $meta_key => $args ) {
						$data['registered_meta'][ $object_type ][ $meta_key ] = [
							'object_subtype' => $object_subtype,
							'type'           => $args['type'] ?? 'string',
							'description'    => $args['description'] ?? '',
							'single'         => $args['single'] ?? false,
							'show_in_rest'   => $args['show_in_rest'] ?? false,
						];
					}
				}
			}
		}

		return $this->format_response( $data, $format, 'Custom Fields' );
	}

	/**
	 * Discover navigation menus.
	 *
	 * @since 0.1.0
	 *
	 * @param string $format Output format.
	 * @return array Discovery results.
	 */
	public function discover_menus( string $format = 'json' ): array {
		$menus     = wp_get_nav_menus();
		$locations = get_nav_menu_locations();
		$data      = [
			'locations' => get_registered_nav_menus(),
			'menus'     => [],
		];

		foreach ( $menus as $menu ) {
			$menu_items = wp_get_nav_menu_items( $menu->term_id );

			$data['menus'][ $menu->slug ] = [
				'id'           => $menu->term_id,
				'name'         => $menu->name,
				'slug'         => $menu->slug,
				'description'  => $menu->description,
				'count'        => $menu->count,
				'locations'    => array_keys( $locations, $menu->term_id, true ),
				'items'        => $this->format_menu_items( $menu_items ),
			];
		}

		return $this->format_response( $data, $format, 'Navigation Menus' );
	}

	/**
	 * Discover shortcodes.
	 *
	 * @since 0.1.0
	 *
	 * @param string $format Output format.
	 * @return array Discovery results.
	 */
	public function discover_shortcodes( string $format = 'json' ): array {
		global $shortcode_tags;

		$data = [];

		foreach ( $shortcode_tags as $tag => $callback ) {
			$data[ $tag ] = [
				'tag'      => $tag,
				'callback' => $this->format_callback( $callback ),
			];
		}

		return $this->format_response( $data, $format, 'Shortcodes' );
	}

	/**
	 * Discover blocks.
	 *
	 * @since 0.1.0
	 *
	 * @param string $format Output format.
	 * @return array Discovery results.
	 */
	public function discover_blocks( string $format = 'json' ): array {
		$registry = \WP_Block_Type_Registry::get_instance();
		$blocks   = $registry->get_all_registered();
		$data     = [];

		foreach ( $blocks as $block_name => $block_type ) {
			$data[ $block_name ] = [
				'name'        => $block_name,
				'title'       => $block_type->title,
				'description' => $block_type->description,
				'category'    => $block_type->category,
				'icon'        => $block_type->icon,
				'keywords'    => $block_type->keywords,
				'supports'    => $block_type->supports,
				'attributes'  => $block_type->attributes,
			];
		}

		return $this->format_response( $data, $format, 'Blocks' );
	}

	/**
	 * Discover hooks.
	 *
	 * @since 0.1.0
	 *
	 * @param string $format Output format.
	 * @return array Discovery results.
	 */
	public function discover_hooks( string $format = 'json' ): array {
		global $wp_filter;

		$data = [
			'actions' => [],
			'filters' => [],
		];

		foreach ( $wp_filter as $hook_name => $hook ) {
			$callbacks = [];

			foreach ( $hook->callbacks as $priority => $functions ) {
				foreach ( $functions as $function ) {
					$callbacks[] = [
						'priority' => $priority,
						'callback' => $this->format_callback( $function['function'] ),
					];
				}
			}

			/*
			 * Determine if it's primarily used as action or filter.
			 * This is a heuristic - WordPress doesn't distinguish.
			 */
			$type = $this->guess_hook_type( $hook_name );

			$data[ $type ][ $hook_name ] = $callbacks;
		}

		return $this->format_response( $data, $format, 'Hooks' );
	}

	/**
	 * Discover options.
	 *
	 * @since 0.1.0
	 *
	 * @param string $format Output format.
	 * @return array Discovery results.
	 */
	public function discover_options( string $format = 'json' ): array {
		global $wpdb;

		// Get all options (be careful with sensitive data).
		$options = $wpdb->get_results(
			"SELECT option_name, option_value FROM {$wpdb->options}
			WHERE autoload = 'yes'
			AND option_name NOT LIKE '%_transient_%'
			ORDER BY option_name",
			ARRAY_A
		);

		$data = [
			'site_options' => [],
			'theme_mods'   => get_theme_mods(),
		];

		foreach ( $options as $option ) {
			// Skip sensitive options.
			if ( $this->is_sensitive_option( $option['option_name'] ) ) {
				continue;
			}

			$data['site_options'][ $option['option_name'] ] = maybe_unserialize( $option['option_value'] );
		}

		return $this->format_response( $data, $format, 'Site Options' );
	}

	/**
	 * Discover rewrite rules.
	 *
	 * @since 0.1.0
	 *
	 * @param string $format Output format.
	 * @return array Discovery results.
	 */
	public function discover_rewrite_rules( string $format = 'json' ): array {
		global $wp_rewrite;

		$data = [
			'permalink_structure' => get_option( 'permalink_structure' ),
			'category_base'       => get_option( 'category_base' ),
			'tag_base'            => get_option( 'tag_base' ),
			'rewrite_rules'       => get_option( 'rewrite_rules' ),
			'endpoints'           => $wp_rewrite->endpoints,
			'extra_permastructs'  => $wp_rewrite->extra_permastructs,
		];

		return $this->format_response( $data, $format, 'Rewrite Rules' );
	}

	/**
	 * Discover capabilities.
	 *
	 * @since 0.1.0
	 *
	 * @param string $format Output format.
	 * @return array Discovery results.
	 */
	public function discover_capabilities( string $format = 'json' ): array {
		global $wp_roles;

		$data = [];

		foreach ( $wp_roles->roles as $role_name => $role ) {
			$data[ $role_name ] = [
				'name'         => $role_name,
				'display_name' => $role['name'],
				'capabilities' => $role['capabilities'],
			];
		}

		return $this->format_response( $data, $format, 'User Roles and Capabilities' );
	}

	/**
	 * Discover cron jobs.
	 *
	 * @since 0.1.0
	 *
	 * @param string $format Output format.
	 * @return array Discovery results.
	 */
	public function discover_cron_jobs( string $format = 'json' ): array {
		$crons = _get_cron_array();
		$data  = [];

		foreach ( $crons as $timestamp => $cron ) {
			foreach ( $cron as $hook => $events ) {
				foreach ( $events as $key => $event ) {
					$data[] = [
						'hook'      => $hook,
						'timestamp' => $timestamp,
						'schedule'  => $event['schedule'] ?? 'single',
						'interval'  => $event['interval'] ?? 0,
						'args'      => $event['args'],
					];
				}
			}
		}

		return $this->format_response( $data, $format, 'Cron Jobs' );
	}

	/**
	 * Format response in requested format.
	 *
	 * @since 0.1.0
	 *
	 * @param array  $data   Data to format.
	 * @param string $format Format (json or markdown).
	 * @param string $title  Title for markdown.
	 * @return array Formatted response.
	 */
	private function format_response( array $data, string $format, string $title = '' ): array {
		if ( 'markdown' === $format ) {
			return [
				'content' => to_markdown( $data, $title ),
				'format'  => 'markdown',
			];
		}

		return [
			'data'   => $data,
			'format' => 'json',
		];
	}

	/**
	 * Get registered meta for a post type.
	 *
	 * @since 0.1.0
	 *
	 * @param string $post_type Post type name.
	 * @return array Registered meta.
	 */
	private function get_registered_meta( string $post_type ): array {
		$meta_keys = get_registered_meta_keys( 'post', $post_type );
		$data      = [];

		foreach ( $meta_keys as $meta_key => $args ) {
			$data[ $meta_key ] = [
				'type'         => $args['type'] ?? 'string',
				'description'  => $args['description'] ?? '',
				'single'       => $args['single'] ?? false,
				'show_in_rest' => $args['show_in_rest'] ?? false,
			];
		}

		return $data;
	}

	/**
	 * Format terms for output.
	 *
	 * @since 0.1.0
	 *
	 * @param array|\WP_Error $terms Terms to format.
	 * @return array Formatted terms.
	 */
	private function format_terms( $terms ): array {
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return [];
		}

		$formatted = [];

		foreach ( $terms as $term ) {
			$formatted[] = [
				'id'          => $term->term_id,
				'name'        => $term->name,
				'slug'        => $term->slug,
				'description' => $term->description,
				'parent'      => $term->parent,
				'count'       => $term->count,
			];
		}

		return $formatted;
	}

	/**
	 * Get theme supports.
	 *
	 * @since 0.1.0
	 *
	 * @return array Theme supports.
	 */
	private function get_theme_supports(): array {
		global $_wp_theme_features;

		return array_keys( $_wp_theme_features ?? [] );
	}

	/**
	 * Get registered sidebars.
	 *
	 * @since 0.1.0
	 *
	 * @return array Registered sidebars.
	 */
	private function get_sidebars(): array {
		global $wp_registered_sidebars;

		return $wp_registered_sidebars ?? [];
	}

	/**
	 * Get nav menu locations.
	 *
	 * @since 0.1.0
	 *
	 * @return array Nav menu locations.
	 */
	private function get_nav_menu_locations(): array {
		return get_registered_nav_menus();
	}

	/**
	 * Get template files from theme.
	 *
	 * @since 0.1.0
	 *
	 * @param \WP_Theme $theme Theme object.
	 * @return array Template files.
	 */
	private function get_template_files( \WP_Theme $theme ): array {
		$templates = $theme->get_files( 'php', 1 );

		return array_keys( $templates );
	}

	/**
	 * Get custom page templates.
	 *
	 * @since 0.1.0
	 *
	 * @return array Custom templates.
	 */
	private function get_custom_templates(): array {
		return get_page_templates();
	}

	/**
	 * Get CMB2 fields if CMB2 is available.
	 *
	 * @since 0.1.0
	 *
	 * @return array CMB2 fields.
	 */
	private function get_cmb2_fields(): array {
		if ( ! function_exists( 'cmb2_get_metabox_sanitized_values' ) ) {
			return [];
		}

		// Get all CMB2 metaboxes.
		$all_metaboxes = \CMB2_Boxes::get_all();
		$data          = [];

		foreach ( $all_metaboxes as $cmb ) {
			$data[ $cmb->cmb_id ] = [
				'id'           => $cmb->cmb_id,
				'title'        => $cmb->prop( 'title' ),
				'object_types' => $cmb->prop( 'object_types' ),
				'context'      => $cmb->prop( 'context' ),
				'fields'       => $this->format_cmb2_fields( $cmb->prop( 'fields' ) ),
			];
		}

		return $data;
	}

	/**
	 * Format CMB2 fields.
	 *
	 * @since 0.1.0
	 *
	 * @param array $fields CMB2 fields.
	 * @return array Formatted fields.
	 */
	private function format_cmb2_fields( array $fields ): array {
		$formatted = [];

		foreach ( $fields as $field ) {
			$formatted[ $field['id'] ] = [
				'id'          => $field['id'],
				'name'        => $field['name'] ?? '',
				'type'        => $field['type'] ?? 'text',
				'description' => $field['desc'] ?? '',
			];
		}

		return $formatted;
	}

	/**
	 * Format menu items.
	 *
	 * @since 0.1.0
	 *
	 * @param array|false $items Menu items.
	 * @return array Formatted menu items.
	 */
	private function format_menu_items( $items ): array {
		if ( ! $items ) {
			return [];
		}

		$formatted = [];

		foreach ( $items as $item ) {
			$formatted[] = [
				'id'          => $item->ID,
				'title'       => $item->title,
				'url'         => $item->url,
				'type'        => $item->type,
				'object'      => $item->object,
				'object_id'   => $item->object_id,
				'parent'      => $item->menu_item_parent,
				'order'       => $item->menu_order,
				'classes'     => $item->classes,
				'description' => $item->description,
			];
		}

		return $formatted;
	}

	/**
	 * Format callback for display.
	 *
	 * @since 0.1.0
	 *
	 * @param callable|string $callback Callback to format.
	 * @return string Formatted callback.
	 */
	private function format_callback( $callback ): string {
		if ( is_string( $callback ) ) {
			return $callback;
		}

		if ( is_array( $callback ) ) {
			if ( is_object( $callback[0] ) ) {
				return get_class( $callback[0] ) . '::' . $callback[1];
			}
			return implode( '::', $callback );
		}

		if ( is_object( $callback ) ) {
			return get_class( $callback );
		}

		return 'Closure';
	}

	/**
	 * Guess hook type based on name.
	 *
	 * @since 0.1.0
	 *
	 * @param string $hook_name Hook name.
	 * @return string 'actions' or 'filters'.
	 */
	private function guess_hook_type( string $hook_name ): string {
		// Common action patterns.
		$action_patterns = [ '_init', '_loaded', '_enqueue_', '_save_', '_delete_', '_update_' ];

		foreach ( $action_patterns as $pattern ) {
			if ( str_contains( $hook_name, $pattern ) ) {
				return 'actions';
			}
		}

		// Everything else is likely a filter.
		return 'filters';
	}

	/**
	 * Check if option is sensitive.
	 *
	 * @since 0.1.0
	 *
	 * @param string $option_name Option name.
	 * @return bool True if sensitive.
	 */
	private function is_sensitive_option( string $option_name ): bool {
		$sensitive_patterns = [
			'password',
			'secret',
			'key',
			'token',
			'salt',
			'auth',
			'private',
		];

		$lower_name = strtolower( $option_name );

		foreach ( $sensitive_patterns as $pattern ) {
			if ( str_contains( $lower_name, $pattern ) ) {
				return true;
			}
		}

		return false;
	}
}
