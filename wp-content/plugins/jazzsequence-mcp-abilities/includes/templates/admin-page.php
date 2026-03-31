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

		<?php
		<?php
		// wp_get_abilities() is available in WordPress 6.9+ Abilities API.
		if ( ! function_exists( 'wp_get_abilities' ) ) :
			?>
			<p><?php esc_html_e( 'The WordPress Abilities API is not available. Please ensure WordPress 6.9+ is installed.', 'jazzsequence-mcp-abilities' ); ?></p>
		<?php else : ?>
			<?php
			$abilities = wp_get_abilities();
			if ( ! is_array( $abilities ) ) {
				$abilities = is_object( $abilities ) && is_iterable( $abilities ) ? iterator_to_array( $abilities ) : [];
			}

			if ( ! empty( $abilities ) ) :
				$by_category = [];
				foreach ( $abilities as $ability_name => $ability ) {
					$ability_arr = is_array( $ability ) ? $ability : (array) $ability;
					$category    = $ability_arr['category'] ?? 'uncategorized';

					$by_category[ $category ][] = [
						'name' => $ability_name,
						'data' => $ability_arr,
					];
				}
				?>
				<p>
					<?php
					printf(
						/* translators: %d: number of abilities */
						esc_html__( '%d abilities are currently registered and available via MCP:', 'jazzsequence-mcp-abilities' ),
						count( $abilities )
					);
					?>
				</p>
				<?php foreach ( $by_category as $category => $category_abilities ) : ?>
					<h3><?php echo esc_html( $category ); ?></h3>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th style="width:30%"><?php esc_html_e( 'Ability', 'jazzsequence-mcp-abilities' ); ?></th>
								<th><?php esc_html_e( 'Description', 'jazzsequence-mcp-abilities' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $category_abilities as $ability ) : ?>
								<tr>
									<td><code><?php echo esc_html( $ability['name'] ); ?></code></td>
									<td><?php echo esc_html( $ability['data']['description'] ?? '' ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endforeach; ?>
			<?php else : ?>
				<p><?php esc_html_e( 'No abilities are currently registered.', 'jazzsequence-mcp-abilities' ); ?></p>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</div>
