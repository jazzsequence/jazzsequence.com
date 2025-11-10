# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Project Overview

This is a WordPress multisite network built with Altis DXP (Digital Experience Platform), a high-performance WordPress distribution by Human Made. The site uses Composer for dependency management and includes both custom plugins and themes alongside third-party packages.

## Architecture

### Composer-Based WordPress Setup
- **Altis DXP** as the core platform (includes WordPress, caching, security features)
- **Multisite Configuration**: WordPress Multisite with subdomain installation
- **Dependency Management**: All plugins, themes, and core components managed via Composer
- **Custom Plugin Dependencies**: Several jazzsequence-authored plugins loaded via custom repositories

### Key Directories Structure
```
wp-content/
├── mu-plugins/           # Must-use plugins (always active)
│   ├── loader.php        # Main MU plugin loader
│   ├── dashboard-changelog/
│   ├── cmb2/            # Custom fields framework
│   └── *.php            # Custom site-specific functionality
├── plugins/             # Standard plugins (Composer-managed)
├── themes/              # Themes (Composer-managed)
└── db.php              # Database configuration

packages/                # Local development packages
bin/                    # Build and deployment scripts
```

### Must-Use Plugins Architecture
The site uses a sophisticated MU plugin loading system (`wp-content/mu-plugins/loader.php`) that:
- Loads Composer autoloader for all dependencies
- Manages cookie domains for multisite functionality
- Automatically registers MU plugins in WordPress admin
- Handles specialized functionality for custom plugins

### Custom Plugin Ecosystem
The site includes several custom plugins developed by jazzsequence:
- **address-book**: Contact management system
- **artists**: Artist/musician data management
- **games-collector**: Gaming library tracking
- **recipe-box**: Recipe management
- **wp-show-tracker**: Concert/show tracking
- **reviews**: Review system
- **releases**: Release management

## Common Development Commands

### Dependencies & Installation
```bash
# Install all dependencies (production)
composer install --no-dev --optimize-autoloader

# Install with development dependencies
composer install

# Update dependencies
composer update
```

### Code Quality & Testing
```bash
# Run shell script linting
composer shellcheck

# Test release dry-run functionality
composer dry-run-release

# Run Bats tests
cd bin && bats tests/*.bats
```

### Release Management
```bash
# Create a new release (dry run)
bin/create_release.sh --dry-run --version "1.7.3"

# Create actual release
bin/create_release.sh --version "1.7.3"

# Test dry-run release script
bash bin/dry-run-release.sh
```

### Local Development Setup
```bash
# Backup configuration files before updates
composer backup-files

# Restore backed up files
composer restore-backup-files
```

## Version Management

The project uses semantic versioning with specific conventions defined in `version.json`:
- **Patch (x.x.Y)**: Updates and patches
- **Minor (x.Y.x)**: WordPress updates or major feature additions (new plugins/themes)
- **Major (Y.x.x)**: Major site architecture updates

## Deployment & CI/CD

### GitHub Actions Workflows
- **test-scripts.yml**: Runs shellcheck and Bats tests on all pushes
- **deploy.yml**: Automated deployment to Digital Ocean on main branch pushes
- **dry-run-deploy.yml**: Tests deployment process without actual deployment

### Deployment Process
The site automatically deploys to Digital Ocean via rsync when changes are pushed to the main branch. The deployment:
1. Sets up PHP 8.4 environment
2. Installs production Composer dependencies
3. Handles specialized plugin setup (Dashboard Changelog)
4. Syncs files to production server via rsync

## Development Patterns

### Composer Repository Strategy
The project uses multiple repository types:
- **wpackagist.org**: Standard WordPress plugins/themes
- **Path repositories**: Local packages in `./packages`
- **VCS repositories**: Custom plugins from GitHub
- **Package repositories**: Specific version pinning for custom plugins

### WordPress Configuration
- **Multisite**: Configured for subdomain installation
- **Caching**: LiteSpeed Cache enabled
- **Security**: Altis Browser Security, Patchstack protection
- **Dashboard**: Custom changelog integration via Dashboard Changelog plugin

### Custom MU Plugin Development
When developing new MU plugins, follow the pattern established in existing files:
- Use PHP namespaces for organization
- Implement proper WordPress hooks and filters
- Document plugin functionality in header comments
- Consider impact on multisite functionality

## Testing Strategy

### Shell Script Testing
- All bash scripts are linted with shellcheck
- Bats (Bash Automated Testing System) for integration testing
- GitHub Actions ensures all scripts pass tests before deployment

### Release Testing  
- Dry-run capabilities for testing release processes
- Automated validation of release creation workflows
- Version pattern matching for PR-based releases

## Key Technical Considerations

### WordPress Multisite Complexity
- Cookie domain handling for subdomain sites
- Network-wide plugin activation patterns
- Site-specific functionality management

### Composer Autoloading
- Custom autoloader requirements for certain plugins
- Platform version pinning (PHP 8.2)
- Plugin installer path customization

### Security & Performance
- Object caching integration
- CDN integration (Amazon S3/CloudFront)
- Security monitoring (Patchstack)
- Performance optimization (LiteSpeed Cache)
