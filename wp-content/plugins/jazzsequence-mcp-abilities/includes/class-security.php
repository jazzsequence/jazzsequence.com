<?php
/**
 * Security and authentication management.
 *
 * Handles MCP user creation, application password generation,
 * and role/capability management for AI access.
 *
 * @package JazzSequence\MCP_Abilities
 */

declare( strict_types=1 );

namespace JazzSequence\MCP_Abilities\Security;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// MCP user constants.
const MCP_USER_LOGIN = 'claude-mcp';
const MCP_USER_EMAIL = 'claude-mcp@jazzsequence.com';
const MCP_ROLE_NAME  = 'ai_manager';

/**
 * Initialize security features.
 *
 * @since 0.1.0
 */
function init(): void {
	// Register custom role on init.
	add_action( 'init', __NAMESPACE__ . '\register_mcp_role' );

	// Add admin menu for MCP management.
	add_action( 'admin_menu', __NAMESPACE__ . '\add_admin_menu' );

	// Display admin notices for MCP setup.
	add_action( 'admin_notices', __NAMESPACE__ . '\display_setup_notices' );
}

/**
 * Register the AI Manager role.
 *
 * @since 0.1.0
 */
function register_mcp_role(): void {
	// Check if role already exists.
	if ( get_role( MCP_ROLE_NAME ) ) {
		return;
	}

	// Define capabilities for AI Manager role.
	$capabilities = [
		// Read capabilities.
		'read'                   => true,
		'read_private_posts'     => true,
		'read_private_pages'     => true,

		// Post capabilities (all post types).
		'edit_posts'             => true,
		'edit_pages'             => true,
		'edit_others_posts'      => true,
		'edit_others_pages'      => true,
		'edit_published_posts'   => true,
		'edit_published_pages'   => true,
		'publish_posts'          => true,
		'publish_pages'          => true,
		'delete_posts'           => true,
		'delete_pages'           => true,
		'delete_others_posts'    => true,
		'delete_others_pages'    => true,
		'delete_published_posts' => true,
		'delete_published_pages' => true,

		// Media capabilities.
		'upload_files'           => true,
		'edit_files'             => true,
		'delete_files'           => true,

		// Taxonomy capabilities.
		'manage_categories'      => true,
		'manage_post_tags'       => true,
		'edit_categories'        => true,
		'edit_post_tags'         => true,
		'delete_categories'      => true,
		'delete_post_tags'       => true,
		'assign_categories'      => true,
		'assign_post_tags'       => true,

		// Comment capabilities.
		'moderate_comments'      => true,
		'edit_comment'           => true,

		// Menu capabilities.
		'edit_theme_options'     => true, // Required for nav menus.

		// User capabilities (limited).
		'list_users'             => true,
		'edit_users'             => false, // Cannot edit users.
		'create_users'           => false, // Cannot create users.
		'delete_users'           => false, // Cannot delete users.

		// Plugin/theme capabilities (optional - can be disabled).
		'activate_plugins'       => true,
		'install_plugins'        => false, // Cannot install plugins.
		'update_plugins'         => false, // Cannot update plugins.
		'delete_plugins'         => false, // Cannot delete plugins.
		'switch_themes'          => false, // Cannot switch themes.
		'install_themes'         => false, // Cannot install themes.
		'update_themes'          => false, // Cannot update themes.
		'delete_themes'          => false, // Cannot delete themes.

		// Site configuration.
		'manage_options'         => true, // Required for many discovery abilities.

		// Core capabilities (restricted).
		'update_core'            => false,
		'export'                 => true,
		'import'                 => false,
	];

	/**
	 * Filter the capabilities for the AI Manager role.
	 *
	 * @since 0.1.0
	 *
	 * @param array $capabilities The capabilities to assign to the role.
	 */
	$capabilities = apply_filters( 'jsmcp_ai_manager_capabilities', $capabilities );

	// Add the role.
	// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.custom_role_add_role -- Not a VIP site.
	add_role(
		MCP_ROLE_NAME,
		__( 'AI Manager', 'jazzsequence-mcp-abilities' ),
		$capabilities
	);
}

/**
 * Create MCP user on plugin activation.
 *
 * @since 0.1.0
 */
function create_mcp_user_on_activation(): void {
	// Check if user already exists.
	$user = get_user_by( 'login', MCP_USER_LOGIN );

	if ( $user ) {
		// User exists - ensure they have the AI Manager role.
		$user->add_role( MCP_ROLE_NAME );
		return;
	}

	// Register the role first.
	register_mcp_role();

	// Create the user.
	$user_id = wp_insert_user(
		[
			'user_login'   => MCP_USER_LOGIN,
			'user_email'   => MCP_USER_EMAIL,
			'display_name' => __( 'Claude MCP', 'jazzsequence-mcp-abilities' ),
			'user_pass'    => wp_generate_password( 64, true, true ),
			'role'         => MCP_ROLE_NAME,
			'description'  => __( 'AI-powered site management user with MCP access. Managed by JazzSequence MCP Abilities plugin.', 'jazzsequence-mcp-abilities' ),
		]
	);

	if ( is_wp_error( $user_id ) ) {
		/*
		 * Store error for admin notice instead of error_log.
		 * This prevents development function from being used in production.
		 */
		update_option( 'jsmcp_activation_error', $user_id->get_error_message() );
		return;
	}

	// Store activation time for admin notices.
	update_option( 'jsmcp_activation_time', time() );
	update_option( 'jsmcp_user_created', true );
}

/**
 * Get the MCP user.
 *
 * @since 0.1.0
 *
 * @return \WP_User|\WP_Error User object or error if not found.
 */
function get_mcp_user() {
	$user = get_user_by( 'login', MCP_USER_LOGIN );

	if ( ! $user ) {
		return new \WP_Error(
			'mcp_user_not_found',
			__( 'MCP user not found. Please reactivate the plugin to create the user.', 'jazzsequence-mcp-abilities' )
		);
	}

	return $user;
}

/**
 * Generate application password for MCP user.
 *
 * @since 0.1.0
 *
 * @param string $app_name Name for the application password.
 * @return array|\WP_Error Array with password details or error.
 */
function generate_application_password( string $app_name = 'Claude MCP Access' ) {
	$user = get_mcp_user();

	if ( is_wp_error( $user ) ) {
		return $user;
	}

	// Check if WP_Application_Passwords is available (WordPress 5.6+).
	if ( ! class_exists( 'WP_Application_Passwords' ) ) {
		return new \WP_Error(
			'app_passwords_not_available',
			__( 'Application Passwords require WordPress 5.6 or higher.', 'jazzsequence-mcp-abilities' )
		);
	}

	// Generate the application password.
	$created = \WP_Application_Passwords::create_new_application_password(
		$user->ID,
		[ 'name' => $app_name ]
	);

	if ( is_wp_error( $created ) ) {
		return $created;
	}

	// Return the password details.
	return [
		'user_login' => MCP_USER_LOGIN,
		'password'   => \WP_Application_Passwords::chunk_password( $created[0] ),
		'uuid'       => $created[1]['uuid'],
		'created'    => $created[1]['created'],
	];
}

/**
 * List all application passwords for MCP user.
 *
 * @since 0.1.0
 *
 * @return array|\WP_Error Array of application passwords or error.
 */
function list_application_passwords() {
	$user = get_mcp_user();

	if ( is_wp_error( $user ) ) {
		return $user;
	}

	if ( ! class_exists( 'WP_Application_Passwords' ) ) {
		return new \WP_Error(
			'app_passwords_not_available',
			__( 'Application Passwords require WordPress 5.6 or higher.', 'jazzsequence-mcp-abilities' )
		);
	}

	return \WP_Application_Passwords::get_user_application_passwords( $user->ID );
}

/**
 * Revoke an application password.
 *
 * @since 0.1.0
 *
 * @param string $uuid UUID of the application password to revoke.
 * @return bool|\WP_Error True on success, error on failure.
 */
function revoke_application_password( string $uuid ) {
	$user = get_mcp_user();

	if ( is_wp_error( $user ) ) {
		return $user;
	}

	if ( ! class_exists( 'WP_Application_Passwords' ) ) {
		return new \WP_Error(
			'app_passwords_not_available',
			__( 'Application Passwords require WordPress 5.6 or higher.', 'jazzsequence-mcp-abilities' )
		);
	}

	$deleted = \WP_Application_Passwords::delete_application_password( $user->ID, $uuid );

	if ( ! $deleted ) {
		return new \WP_Error(
			'password_not_found',
			__( 'Application password not found.', 'jazzsequence-mcp-abilities' )
		);
	}

	return true;
}

/**
 * Add admin menu for MCP management.
 *
 * @since 0.1.0
 */
function add_admin_menu(): void {
	add_management_page(
		__( 'MCP Access Management', 'jazzsequence-mcp-abilities' ),
		__( 'MCP Access', 'jazzsequence-mcp-abilities' ),
		'manage_options',
		'jsmcp-access',
		__NAMESPACE__ . '\render_admin_page'
	);
}

/**
 * Render the admin page for MCP management.
 *
 * @since 0.1.0
 */
function render_admin_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'jazzsequence-mcp-abilities' ) );
	}

	// Handle form submissions.
	if ( isset( $_POST['jsmcp_generate_password'] ) && check_admin_referer( 'jsmcp_generate_password' ) ) {
		$result = generate_application_password();

		if ( is_wp_error( $result ) ) {
			add_settings_error(
				'jsmcp_messages',
				'jsmcp_password_error',
				$result->get_error_message(),
				'error'
			);
		} else {
			// Store the password temporarily to display it (only shown once).
			set_transient( 'jsmcp_new_password', $result['password'], 300 );
			add_settings_error(
				'jsmcp_messages',
				'jsmcp_password_created',
				__( 'Application password created successfully. Copy it now - it will not be shown again.', 'jazzsequence-mcp-abilities' ),
				'success'
			);
		}
	}

	if ( isset( $_POST['jsmcp_revoke_password'] ) && check_admin_referer( 'jsmcp_revoke_password' ) ) {
		$uuid   = sanitize_text_field( wp_unslash( $_POST['password_uuid'] ?? '' ) );
		$result = revoke_application_password( $uuid );

		if ( is_wp_error( $result ) ) {
			add_settings_error(
				'jsmcp_messages',
				'jsmcp_revoke_error',
				$result->get_error_message(),
				'error'
			);
		} else {
			add_settings_error(
				'jsmcp_messages',
				'jsmcp_password_revoked',
				__( 'Application password revoked successfully.', 'jazzsequence-mcp-abilities' ),
				'success'
			);
		}
	}

	$user             = get_mcp_user();
	$app_passwords    = is_wp_error( $user ) ? [] : list_application_passwords();
	$new_password     = get_transient( 'jsmcp_new_password' );
	$mcp_adapter_path = defined( 'WP_DEBUG' ) && WP_DEBUG
		? '/wp-json/mcp/mcp-adapter-default-server'
		: home_url( '/wp-json/mcp/mcp-adapter-default-server' );

	// Display the template.
	require_once JSMCP_PLUGIN_DIR . 'includes/templates/admin-page.php';

	// Clear the password transient after displaying.
	if ( $new_password ) {
		delete_transient( 'jsmcp_new_password' );
	}
}

/**
 * Display setup notices.
 *
 * @since 0.1.0
 */
function display_setup_notices(): void {
	// Only show to admins.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Check if we just activated and created the user.
	if ( get_option( 'jsmcp_user_created' ) && ! get_option( 'jsmcp_setup_complete' ) ) {
		?>
		<div class="notice notice-info is-dismissible">
			<p>
				<strong><?php esc_html_e( 'JazzSequence MCP Abilities:', 'jazzsequence-mcp-abilities' ); ?></strong>
				<?php
				printf(
					/* translators: 1: Link to MCP Access page, 2: Link closing tag */
					esc_html__( 'MCP user created successfully. %1$sGenerate an application password%2$s to enable AI access.', 'jazzsequence-mcp-abilities' ),
					'<a href="' . esc_url( admin_url( 'tools.php?page=jsmcp-access' ) ) . '">',
					'</a>'
				);
				?>
			</p>
		</div>
		<?php
	}
}
