# Plugin Setup Checklist

Use this checklist when creating a new plugin from the Grumpy Gears Framework.

## ✅ Pre-Setup

- [ ] Have WordPress 5.0+ and PHP 7.4+
- [ ] Have GitHub repository ready (can be private)
- [ ] Have GitHub Personal Access Token (for private repos)

## ✅ File Renaming & Setup

- [ ] **Rename main plugin file**: `grumpy-gears-plugin-template.php` → `your-plugin-name.php`
- [ ] **Rename plugin folder**: Current folder → `your-plugin-name`

## ✅ Configuration Updates

### config/plugin-config.php

- [ ] **PLUGIN_INFO['name']**: Update to your plugin name
- [ ] **PLUGIN_INFO['description']**: Update plugin description  
- [ ] **PLUGIN_INFO['version']**: Set initial version (e.g., '1.0.0')
- [ ] **PLUGIN_INFO['text_domain']**: Update text domain (e.g., 'my-plugin')

- [ ] **GITHUB_CONFIG['username']**: Your GitHub username/organization
- [ ] **GITHUB_CONFIG['repository']**: Your repository name
- [ ] **GITHUB_CONFIG['access_token_option']**: Unique option name (e.g., 'my_plugin_github_token')

- [ ] **PATHS['plugin_file']**: Update to your renamed main file
- [ ] **OPTIONS['main_options']**: Unique option name (e.g., 'my_plugin_options')
- [ ] **OPTIONS['updater_options']**: Unique option name (e.g., 'my_plugin_updater')
- [ ] **OPTIONS['version_key']**: Unique version key (e.g., 'my_plugin_version')

### Main Plugin File (your-plugin-name.php)

- [ ] **Plugin Header - Name**: Update plugin name
- [ ] **Plugin Header - URI**: Update to your GitHub repository URL
- [ ] **Plugin Header - Description**: Update description
- [ ] **Plugin Header - Version**: Match config version
- [ ] **Plugin Header - Text Domain**: Match config text domain
- [ ] **Plugin Header - GitHub Plugin URI**: username/repository

- [ ] **Constants**: Update all `GRUMPY_GEARS_PLUGIN_*` to `YOUR_PLUGIN_*`
- [ ] **Class Names**: Rename `GrumpyGearsPlugin` to `YourPluginClass`
- [ ] **Function Names**: Rename `grumpy_gears_plugin_init` etc.

## ✅ GitHub Repository Setup

- [ ] **Create GitHub repository** (private if needed)
- [ ] **Push initial code** to repository
- [ ] **Create first tag**: `git tag v1.0.0 && git push origin v1.0.0`
- [ ] **Create GitHub release** with changelog

## ✅ GitHub Token Setup

- [ ] **Generate Personal Access Token**
  - Go to: GitHub → Settings → Developer settings → Personal access tokens
  - Scopes needed: `repo` (for private repositories)
- [ ] **Copy token** (save it - you won't see it again!)

## ✅ WordPress Installation

- [ ] **Upload plugin** to `/wp-content/plugins/your-plugin-name/`
- [ ] **Activate plugin** in WordPress admin
- [ ] **Navigate to** Settings → [Your Plugin Name]
- [ ] **Enter GitHub token** in settings
- [ ] **Configure auto-update** preferences
- [ ] **Test update check** by clicking "Check for Updates"

## ✅ Testing Checklist

- [ ] **Plugin activates** without errors
- [ ] **Settings page loads** correctly
- [ ] **Manual update check** works
- [ ] **GitHub API authentication** successful (no errors in logs)
- [ ] **Settings save** properly
- [ ] **Admin interface** displays correctly

## ✅ Release Process

For future updates:

- [ ] **Update version** in config/plugin-config.php
- [ ] **Update version** in main plugin file header
- [ ] **Commit changes** with descriptive message
- [ ] **Create tag**: `git tag v1.x.x`
- [ ] **Push tag**: `git push origin v1.x.x` 
- [ ] **Create GitHub release** with changelog
- [ ] **Test update** on WordPress site

## ✅ Troubleshooting

If something doesn't work:

- [ ] **Check WordPress debug logs**: `/wp-content/debug.log`
- [ ] **Verify file permissions**: WordPress needs write access
- [ ] **Test GitHub token**: Use a tool like Postman to test API access
- [ ] **Check repository settings**: Ensure repository name matches config
- [ ] **Verify release tags**: Must start with 'v' (e.g., v1.0.0)

## ✅ Security Best Practices

- [ ] **Use HTTPS** for GitHub repository URLs
- [ ] **Keep token secure**: Don't commit to repository
- [ ] **Test on staging** before production updates
- [ ] **Regular backups**: Always backup before updates

---

**Need help?** Check the README.md file for detailed instructions and troubleshooting tips.
