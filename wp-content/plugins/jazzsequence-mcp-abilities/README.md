# JazzSequence MCP Abilities

WordPress plugin that exposes comprehensive site management abilities via the Model Context Protocol (MCP) for AI-powered WordPress administration.

## Overview

This plugin enables Claude (and other AI agents) to deeply understand and interact with your WordPress site through MCP. It provides read-only discovery abilities to introspect site architecture, with full CRUD content management abilities planned for future releases.

## Requirements

- WordPress 6.9+ (for Abilities API)
- PHP 8.2+
- [WordPress MCP Adapter](https://github.com/WordPress/mcp-adapter) package

## Installation

1. Install via Composer (recommended):
```bash
composer require wordpress/mcp-adapter
```

2. The plugin creates a dedicated MCP user on activation (`claude-mcp@jazzsequence.com`)

3. Generate an application password for Claude:
   - Go to **Tools > MCP Access** in WordPress admin
   - Click "Generate New Application Password"
   - Copy the password (shown only once)

4. Configure Claude Desktop with the credentials (see admin page for full config)

## Features

### Security Model

- **Dedicated User Account**: `claude-mcp` user with custom `ai_manager` role
- **Application Passwords**: WordPress's built-in app-specific password system
- **Granular Capabilities**: Fine-grained permissions for AI operations
- **Audit Trail**: All AI actions logged with user ID, timestamp, and IP address
- **Revocable Access**: Application passwords can be revoked instantly

### Discovery Abilities (Read-Only)

Current version provides 13 introspection abilities:

1. **`discover-post-types`** - All post types with schemas, capabilities, supports, registered meta
2. **`discover-taxonomies`** - All taxonomies, terms, hierarchies, relationships
3. **`discover-plugins`** - Active plugins with versions and capabilities
4. **`discover-theme-structure`** - Theme architecture, templates, widget areas, features
5. **`discover-custom-fields`** - CMB2 fields, ACF groups, registered meta
6. **`discover-menus`** - Menu locations, menus, full menu structures
7. **`discover-shortcodes`** - All registered shortcodes and signatures
8. **`discover-blocks`** - Custom blocks, patterns, templates
9. **`discover-hooks`** - Registered actions and filters with callbacks
10. **`discover-options`** - Site options, theme mods, customizer settings
11. **`discover-rewrite-rules`** - Permalink structure, custom rewrites
12. **`discover-capabilities`** - User roles and their capabilities
13. **`discover-cron-jobs`** - Scheduled tasks with frequencies

### Content Management Abilities (Planned)

Future releases will add:

- Post/page creation, updating, deletion (all post types)
- Media management (upload, update, delete)
- Taxonomy/term management
- Menu item management
- Comment moderation
- User management (limited)
- Plugin/theme management (optional)
- System operations (cache clearing, cron management)

## Usage

### From Claude Desktop

Once configured, Claude can query your site:

```
"What post types does jazzsequence.com have?"
"Show me the navigation menu structure"
"List all custom fields for the gc_game post type"
```

### Via WordPress REST API

Abilities are also accessible via REST endpoints (requires authentication):

```bash
curl -X POST https://jazzsequence.com/wp-json/wp/v2/abilities/jazzsequence-mcp/discover-post-types \
  -u "claude-mcp:YOUR_APP_PASSWORD"
```

### Output Formats

All discovery abilities support two output formats:

- **JSON** (default) - Structured data for programmatic use
- **Markdown** - Human-readable formatted output

Specify format in request:
```json
{
  "format": "markdown"
}
```

## Security Considerations

### AI Manager Role Capabilities

The custom `ai_manager` role includes:

**Allowed:**
- Read all content (posts, pages, private content)
- Edit/publish/delete posts and pages (all post types)
- Upload/edit/delete media
- Manage categories and tags
- Moderate comments
- Edit navigation menus
- View site options
- Export content
- Activate plugins (can be disabled via filter)

**Restricted:**
- Cannot create/edit/delete users
- Cannot install/update/delete plugins or themes
- Cannot update WordPress core
- Cannot switch themes
- Cannot import content

### Customizing Capabilities

Filter the AI Manager role capabilities:

```php
add_filter( 'jsmcp_ai_manager_capabilities', function( $caps ) {
    // Remove plugin activation capability
    $caps['activate_plugins'] = false;

    // Add custom capability
    $caps['my_custom_capability'] = true;

    return $caps;
} );
```

### Audit Logging

All ability executions are logged with:

- Timestamp
- User ID and login
- Ability name
- Arguments
- Success/failure status
- IP address
- User agent

View logs programmatically:

```php
$logs = \JazzSequence\MCP_Abilities\Audit_Log\get_logs( [
    'limit' => 50,
    'ability' => 'jazzsequence-mcp/discover-post-types',
] );
```

### Revoking Access

To immediately revoke AI access:

1. Go to **Tools > MCP Access**
2. Find the application password
3. Click "Revoke"

Claude will lose access instantly.

## Development

### File Structure

```
jazzsequence-mcp-abilities/
├── jazzsequence-mcp-abilities.php    # Main plugin file
├── includes/
│   ├── bootstrap.php                 # Plugin initialization
│   ├── class-security.php            # Security & auth management
│   ├── class-audit-log.php           # Audit logging
│   ├── helpers.php                   # Utility functions
│   ├── abilities/
│   │   └── discovery/
│   │       └── class-discovery-engine.php  # Discovery implementations
│   └── templates/
│       └── admin-page.php            # Admin UI template
└── README.md
```

### Adding Custom Abilities

Register additional abilities in your own plugin:

```php
add_action( 'wp_abilities_api_init', function() {
    wp_register_ability( 'my-plugin/custom-ability', [
        'label'       => 'My Custom Ability',
        'description' => 'Does something custom',
        'category'    => 'jazzsequence-mcp',
        'input'       => [ /* schema */ ],
        'output'      => [ /* schema */ ],
        'execute'     => function( $args ) {
            // Your implementation
            return [ 'result' => 'data' ];
        },
        'permission'  => function() {
            return current_user_can( 'edit_posts' );
        },
    ] );
} );
```

## Roadmap

### Version 0.2.0
- Content management abilities (create/update/delete posts)
- Media management abilities
- Taxonomy management

### Version 0.3.0
- Advanced content operations
- Bulk operations
- Content migration/import abilities

### Version 0.4.0
- System management abilities
- Cache management
- Database optimization

### Version 1.0.0
- Full feature set
- Comprehensive documentation
- Security audit
- Performance optimization

## License

GPL-2.0-or-later

## Author

Chris Reynolds (https://jazzsequence.com)

## Support

For issues and feature requests, please use the GitHub issue tracker.
