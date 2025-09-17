# Grumpy Gears WordPress Plugin Framework

A comprehensive WordPress plugin development framework with built-in GitHub integration for automatic updates. This framework allows you to create professional WordPress plugins that can automatically update from private GitHub repositories.

## Features

- 🔄 **Automatic Updates**: Daily update checks with manual override
- 🔒 **Private Repository Support**: Works with private GitHub repositories using personal access tokens
- 📋 **Changelog Display**: Integrated changelog viewer with GitHub release notes
- ⚙️ **Easy Configuration**: Centralized configuration system for quick plugin setup
- 🎨 **Professional Admin Interface**: Clean, WordPress-native admin interface
- 🔧 **Developer Friendly**: Well-documented, modular code structure
- 📱 **Responsive Design**: Mobile-friendly admin interface

## Quick Start

### Prerequisites

- WordPress 5.0 or higher
- PHP 7.4 or higher
- GitHub repository (can be private)
- GitHub Personal Access Token (for private repositories)

### 1. Copy the Framework

1. Copy this entire folder to create your new plugin
2. Rename the folder to your new plugin name (e.g., `my-awesome-plugin`)
3. Rename `grumpy-gears-plugin-template.php` to match your plugin (e.g., `my-awesome-plugin.php`)

### 2. Configure Your Plugin

Edit the `config/plugin-config.php` file and update the following sections:

#### Plugin Information (PLUGIN_INFO)
```php
const PLUGIN_INFO = array(
    'name' => 'Your Plugin Name',                    // ← CHANGE THIS
    'description' => 'Your plugin description.',    // ← CHANGE THIS
    'version' => '1.0.0',                          // ← CHANGE THIS
    'text_domain' => 'your-plugin-textdomain',     // ← CHANGE THIS
    
    // Keep these as-is for Grumpy Gears branding
    'author' => 'Grumpy Gears',
    'author_uri' => 'https://github.com/grumpygears',
    
    // Update WordPress requirements if needed
    'requires_wp' => '5.0',
    'tested_up_to' => '6.3',
    'requires_php' => '7.4',
);
```

#### GitHub Configuration (GITHUB_CONFIG)
```php
const GITHUB_CONFIG = array(
    'username' => 'grumpygears',          // ← Your GitHub username/org
    'repository' => 'your-repo-name',    // ← CHANGE THIS to your repo name
    'branch' => 'main',                  // ← Change if using different branch
    'access_token_option' => 'your_plugin_github_token', // ← CHANGE THIS
);
```

#### Plugin Paths (PATHS)
```php
const PATHS = array(
    'plugin_file' => 'your-plugin-name.php',  // ← CHANGE THIS to your main file name
    // Other paths can stay the same
);
```

#### Database Options (OPTIONS)
```php
const OPTIONS = array(
    'main_options' => 'your_plugin_options',      // ← CHANGE THIS
    'updater_options' => 'your_plugin_updater',   // ← CHANGE THIS
    'version_key' => 'your_plugin_version'        // ← CHANGE THIS
);
```

### 3. Update Main Plugin File

Edit your main plugin file (renamed from `grumpy-gears-plugin-template.php`) and update:

#### Plugin Header
```php
/**
 * Plugin Name: Your Plugin Name                    ← CHANGE THIS
 * Plugin URI: https://github.com/username/repo     ← CHANGE THIS
 * Description: Your plugin description             ← CHANGE THIS
 * Version: 1.0.0                                  ← CHANGE THIS
 * Text Domain: your-plugin-textdomain             ← CHANGE THIS
 * GitHub Plugin URI: username/repository-name      ← CHANGE THIS
 */
```

#### Constants
```php
define('YOUR_PLUGIN_VERSION', '1.0.0');                    // ← CHANGE THIS
define('YOUR_PLUGIN_PATH', plugin_dir_path(__FILE__));     // ← CHANGE THIS
define('YOUR_PLUGIN_URL', plugin_dir_url(__FILE__));       // ← CHANGE THIS
define('YOUR_PLUGIN_FILE', __FILE__);                      // Keep as-is
define('YOUR_PLUGIN_BASENAME', plugin_basename(__FILE__)); // Keep as-is
```

#### Class Names
Update all class names to match your plugin:
- `GrumpyGearsPlugin` → `YourPluginName`
- Function names like `grumpy_gears_plugin_init` → `your_plugin_init`

### 4. Setup GitHub Repository

1. Create a new repository on GitHub (can be private)
2. Push your plugin code to the repository
3. Create your first release:
   ```bash
   git tag v1.0.0
   git push origin v1.0.0
   ```
4. Go to GitHub → Your Repository → Releases → Create a new release
5. Use tag `v1.0.0`, add release notes

### 5. Generate GitHub Personal Access Token

For private repositories, you need a Personal Access Token:

1. Go to GitHub → Settings → Developer settings → Personal access tokens
2. Generate new token (classic)
3. Select scopes: `repo` (full control of private repositories)
4. Copy the token (you won't see it again!)

### 6. Install and Configure

1. Upload your plugin to WordPress `/wp-content/plugins/`
2. Activate the plugin
3. Go to Settings → Grumpy Gears Plugin (or your plugin name)
4. Enter your GitHub Personal Access Token
5. Configure auto-update settings
6. Click "Check for Updates" to test

## File Structure

```
your-plugin-name/
├── grumpy-gears-plugin-template.php  ← Rename this to your plugin name
├── README.md
├── config/
│   └── plugin-config.php             ← Main configuration file
├── includes/
│   ├── class-github-updater.php      ← GitHub API integration
│   ├── class-admin-interface.php     ← Admin interface
│   └── class-plugin-updater.php      ← WordPress update system integration
└── admin/
    ├── css/
    │   └── admin.css                  ← Admin styles
    └── js/
        └── admin.js                   ← Admin JavaScript
```

## Configuration Reference

### Plugin Information Fields

| Field | Description | Required | Example |
|-------|-------------|----------|---------|
| `name` | Plugin display name | Yes | "My Awesome Plugin" |
| `description` | Plugin description | Yes | "Does awesome things" |
| `version` | Current version | Yes | "1.0.0" |
| `text_domain` | Translation domain | Yes | "my-awesome-plugin" |
| `requires_wp` | Minimum WordPress version | No | "5.0" |
| `tested_up_to` | Tested up to WordPress version | No | "6.3" |
| `requires_php` | Minimum PHP version | No | "7.4" |

### GitHub Configuration Fields

| Field | Description | Required | Example |
|-------|-------------|----------|---------|
| `username` | GitHub username/organization | Yes | "grumpygears" |
| `repository` | Repository name | Yes | "my-awesome-plugin" |
| `branch` | Main branch name | No | "main" |
| `access_token_option` | WordPress option name for token | Yes | "my_plugin_github_token" |

## Update Process

### How Updates Work

1. **Daily Check**: Plugin checks GitHub for new releases daily
2. **Manual Check**: Users can manually check via "Check for Updates" button
3. **Version Compare**: Uses semantic version comparison
4. **Download**: Downloads release ZIP from GitHub
5. **Install**: Uses WordPress update system to install

### Creating Releases

1. **Update Version**: Update version in `config/plugin-config.php`
2. **Update Plugin Header**: Update version in main plugin file
3. **Commit Changes**: Commit your code changes
4. **Create Tag**: `git tag v1.1.0`
5. **Push Tag**: `git push origin v1.1.0`
6. **Create Release**: Create GitHub release with changelog

### Release Notes Format

Use markdown in your GitHub release notes:

```markdown
## What's New in v1.1.0

### Added
- New awesome feature
- Another cool feature

### Changed
- Improved performance
- Updated UI

### Fixed
- Fixed bug #123
- Resolved issue with XYZ
```

## Troubleshooting

### Common Issues

**Updates Not Working**
- Check GitHub token has `repo` permissions
- Verify repository name in config matches GitHub
- Ensure releases are properly tagged

**Permission Errors**
- Check file permissions (WordPress needs write access)
- Verify user has `update_plugins` capability

**API Rate Limits**
- GitHub allows 5,000 API requests per hour with token
- Use caching (built-in) to reduce requests

### Debug Mode

Add this to `wp-config.php` for debugging:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check `/wp-content/debug.log` for errors.

## Security Considerations

- **Store tokens securely**: Tokens are stored in WordPress options (encrypted)
- **Use HTTPS**: All GitHub API calls use HTTPS
- **Validate downloads**: Files are validated before installation
- **Rate limiting**: Built-in rate limiting prevents abuse

## Customization

### Styling
Edit `admin/css/admin.css` to customize the admin interface appearance.

### Functionality
- Extend classes in `/includes/` for additional features
- Hook into WordPress actions/filters as needed
- Add custom admin pages by extending the admin interface

### Localization
1. Update `text_domain` in configuration
2. Add translation files to `/languages/`
3. Use WordPress translation functions: `__()`, `_e()`, etc.

## Best Practices

### Version Numbers
- Use semantic versioning (MAJOR.MINOR.PATCH)
- Increment MAJOR for breaking changes
- Increment MINOR for new features
- Increment PATCH for bug fixes

### Release Process
1. Test thoroughly before releasing
2. Write clear changelog entries
3. Use descriptive commit messages
4. Tag releases properly

### Code Quality
- Follow WordPress coding standards
- Comment your code
- Keep functions small and focused
- Use proper error handling

## Support

This framework is provided as-is for development workflow simplification. For WordPress-specific issues, consult the [WordPress Developer Resources](https://developer.wordpress.org/).

## License

This framework follows the same license as your plugin. The default template uses GPL v2 or later, which is compatible with WordPress.

---

**Created by Grumpy Gears** - Streamlining WordPress plugin development workflows.
