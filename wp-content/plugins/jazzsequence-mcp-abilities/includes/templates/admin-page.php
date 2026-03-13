<?php
/**
 * Admin page template for MCP access management.
 *
 * @package JazzSequence\MCP_Abilities
 *
 * @var \WP_User|\WP_Error $user MCP user object.
 * @var array              $app_passwords List of application passwords.
 * @var string|false       $new_password Newly generated password (shown once).
 * @var string             $mcp_adapter_path MCP adapter endpoint path.
 */

declare( strict_types=1 );

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="wrap">
	<h1><?php esc_html_e( 'MCP Access Management', 'jazzsequence-mcp-abilities' ); ?></h1>

	<?php settings_errors( 'jsmcp_messages' ); ?>

	<div class="card">
		<h2><?php esc_html_e( 'MCP User Account', 'jazzsequence-mcp-abilities' ); ?></h2>

		<?php if ( is_wp_error( $user ) ) : ?>
			<div class="notice notice-error inline">
				<p><?php echo esc_html( $user->get_error_message() ); ?></p>
			</div>
		<?php else : ?>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'User Login', 'jazzsequence-mcp-abilities' ); ?></th>
					<td><code><?php echo esc_html( $user->user_login ); ?></code></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Email', 'jazzsequence-mcp-abilities' ); ?></th>
					<td><code><?php echo esc_html( $user->user_email ); ?></code></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Role', 'jazzsequence-mcp-abilities' ); ?></th>
					<td><code><?php echo esc_html( implode( ', ', $user->roles ) ); ?></code></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'MCP Endpoint', 'jazzsequence-mcp-abilities' ); ?></th>
					<td><code><?php echo esc_html( $mcp_adapter_path ); ?></code></td>
				</tr>
			</table>
		<?php endif; ?>
	</div>

	<div class="card">
		<h2><?php esc_html_e( 'Application Passwords', 'jazzsequence-mcp-abilities' ); ?></h2>

		<p>
			<?php
			esc_html_e(
				'Application passwords allow Claude to authenticate with your WordPress site via MCP without exposing your main password. Each password can be revoked independently.',
				'jazzsequence-mcp-abilities'
			);
			?>
		</p>

		<?php if ( $new_password ) : ?>
			<div class="notice notice-warning inline">
				<p>
					<strong><?php esc_html_e( 'Your new application password:', 'jazzsequence-mcp-abilities' ); ?></strong>
				</p>
				<p style="font-family: monospace; font-size: 1.2em; background: #f0f0f1; padding: 1em; border-radius: 4px;">
					<?php echo esc_html( $new_password ); ?>
				</p>
				<p>
					<?php esc_html_e( 'Copy this password now - it will not be shown again. Use this with your Claude MCP configuration.', 'jazzsequence-mcp-abilities' ); ?>
				</p>
			</div>
		<?php endif; ?>

		<?php if ( ! is_wp_error( $user ) ) : ?>
			<form method="post" action="">
				<?php wp_nonce_field( 'jsmcp_generate_password' ); ?>
				<p>
					<button type="submit" name="jsmcp_generate_password" class="button button-primary">
						<?php esc_html_e( 'Generate New Application Password', 'jazzsequence-mcp-abilities' ); ?>
					</button>
				</p>
			</form>
		<?php endif; ?>

		<?php if ( ! empty( $app_passwords ) && is_array( $app_passwords ) ) : ?>
			<h3><?php esc_html_e( 'Active Application Passwords', 'jazzsequence-mcp-abilities' ); ?></h3>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Name', 'jazzsequence-mcp-abilities' ); ?></th>
						<th><?php esc_html_e( 'Created', 'jazzsequence-mcp-abilities' ); ?></th>
						<th><?php esc_html_e( 'Last Used', 'jazzsequence-mcp-abilities' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'jazzsequence-mcp-abilities' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $app_passwords as $password ) : ?>
						<tr>
							<td><?php echo esc_html( $password['name'] ); ?></td>
							<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $password['created'] ) ); ?></td>
							<td>
								<?php
								if ( ! empty( $password['last_used'] ) ) {
									echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $password['last_used'] ) );
								} else {
									esc_html_e( 'Never', 'jazzsequence-mcp-abilities' );
								}
								?>
							</td>
							<td>
								<form method="post" action="" style="display: inline;">
									<?php wp_nonce_field( 'jsmcp_revoke_password' ); ?>
									<input type="hidden" name="password_uuid" value="<?php echo esc_attr( $password['uuid'] ); ?>">
									<button type="submit" name="jsmcp_revoke_password" class="button button-small button-link-delete" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to revoke this application password? Claude will lose access.', 'jazzsequence-mcp-abilities' ); ?>')">
										<?php esc_html_e( 'Revoke', 'jazzsequence-mcp-abilities' ); ?>
									</button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p><?php esc_html_e( 'No application passwords have been created yet.', 'jazzsequence-mcp-abilities' ); ?></p>
		<?php endif; ?>
	</div>

	<div class="card">
		<h2><?php esc_html_e( 'MCP Configuration', 'jazzsequence-mcp-abilities' ); ?></h2>

		<p>
			<?php
			esc_html_e(
				'To connect Claude to your WordPress site via MCP, add the following configuration to your Claude Desktop config file:',
				'jazzsequence-mcp-abilities'
			);
			?>
		</p>

		<p><strong><?php esc_html_e( 'macOS:', 'jazzsequence-mcp-abilities' ); ?></strong> <code>~/Library/Application Support/Claude/claude_desktop_config.json</code></p>
		<p><strong><?php esc_html_e( 'Windows:', 'jazzsequence-mcp-abilities' ); ?></strong> <code>%APPDATA%\Claude\claude_desktop_config.json</code></p>

		<pre style="background: #f0f0f1; padding: 1em; border-radius: 4px; overflow-x: auto;"><code>{
  "mcpServers": {
    "jazzsequence": {
      "command": "npx",
      "args": [
        "-y",
        "@modelcontextprotocol/server-wordpress"
      ],
      "env": {
        "WORDPRESS_URL": "<?php echo esc_js( home_url() ); ?>",
        "WORDPRESS_USERNAME": "<?php echo esc_js( \JazzSequence\MCP_Abilities\Security\MCP_USER_LOGIN ); ?>",
        "WORDPRESS_APP_PASSWORD": "YOUR_APPLICATION_PASSWORD_HERE"
      }
    }
  }
}</code></pre>

		<p>
			<?php
			esc_html_e(
				'Replace YOUR_APPLICATION_PASSWORD_HERE with the application password generated above.',
				'jazzsequence-mcp-abilities'
			);
			?>
		</p>
	</div>

	<div class="card">
		<h2><?php esc_html_e( 'Registered Abilities', 'jazzsequence-mcp-abilities' ); ?></h2>

		<p>
			<?php
			esc_html_e(
				'This plugin registers the following abilities that Claude can access via MCP:',
				'jazzsequence-mcp-abilities'
			);
			?>
		</p>

		<h3><?php esc_html_e( 'Discovery Abilities (Read-Only)', 'jazzsequence-mcp-abilities' ); ?></h3>
		<ul>
			<li><code>discover-post-types</code> - <?php esc_html_e( 'Get all post types and their schemas', 'jazzsequence-mcp-abilities' ); ?></li>
			<li><code>discover-taxonomies</code> - <?php esc_html_e( 'Get all taxonomies and terms', 'jazzsequence-mcp-abilities' ); ?></li>
			<li><code>discover-plugins</code> - <?php esc_html_e( 'List active plugins', 'jazzsequence-mcp-abilities' ); ?></li>
			<li><code>discover-theme-structure</code> - <?php esc_html_e( 'Get theme architecture', 'jazzsequence-mcp-abilities' ); ?></li>
			<li><code>discover-custom-fields</code> - <?php esc_html_e( 'Get custom field definitions', 'jazzsequence-mcp-abilities' ); ?></li>
			<li><code>discover-menus</code> - <?php esc_html_e( 'Get navigation menu structures', 'jazzsequence-mcp-abilities' ); ?></li>
			<li><code>discover-shortcodes</code> - <?php esc_html_e( 'List registered shortcodes', 'jazzsequence-mcp-abilities' ); ?></li>
			<li><code>discover-blocks</code> - <?php esc_html_e( 'List registered blocks', 'jazzsequence-mcp-abilities' ); ?></li>
			<li><code>discover-hooks</code> - <?php esc_html_e( 'Get registered hooks', 'jazzsequence-mcp-abilities' ); ?></li>
			<li><code>discover-options</code> - <?php esc_html_e( 'List site options', 'jazzsequence-mcp-abilities' ); ?></li>
			<li><code>discover-rewrite-rules</code> - <?php esc_html_e( 'Get permalink structure', 'jazzsequence-mcp-abilities' ); ?></li>
			<li><code>discover-capabilities</code> - <?php esc_html_e( 'List roles and capabilities', 'jazzsequence-mcp-abilities' ); ?></li>
			<li><code>discover-cron-jobs</code> - <?php esc_html_e( 'List scheduled tasks', 'jazzsequence-mcp-abilities' ); ?></li>
		</ul>

		<p>
			<em><?php esc_html_e( 'Content management abilities (create, update, delete) will be added in future versions.', 'jazzsequence-mcp-abilities' ); ?></em>
		</p>
	</div>
</div>
