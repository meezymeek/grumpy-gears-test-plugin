<?php
/**
 * Plugin Configuration File
 * 
 * Edit this file when creating a new plugin from the template
 * This centralizes all the plugin-specific settings
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin Configuration Class
 */
class GrumpyGears_Plugin_Config {
    
    /**
     * Plugin Information
     * 
     * IMPORTANT: Update these values when creating a new plugin
     */
    const PLUGIN_INFO = array(
        // Plugin Details
        'name' => 'Grumpy Gears Test Plugin',
        'description' => 'A test plugin to demonstrate the Grumpy Gears plugin framework with GitHub auto-update capabilities.',
        'version' => '1.0.0',
        'text_domain' => 'grumpy-gears-test-plugin',
        
        // Author Information (Keep as is for Grumpy Gears branding)
        'author' => 'Grumpy Gears',
        'author_uri' => 'https://github.com/grumpygears',
        
        // WordPress Requirements
        'requires_wp' => '5.0',
        'tested_up_to' => '6.3',
        'requires_php' => '7.4',
        
        // License
        'license' => 'GPL v2 or later',
        'license_uri' => 'https://www.gnu.org/licenses/gpl-2.0.html'
    );
    
    /**
     * GitHub Configuration
     * 
     * IMPORTANT: Update these values for each new plugin
     */
    const GITHUB_CONFIG = array(
        // Repository Details
        'username' => 'meezymeek',          // Your GitHub username/organization
        'repository' => 'grumpy-gears-test-plugin',        // Repository name (CHANGE THIS)
        'branch' => 'main',                   // Main branch name
        'access_token_option' => 'grumpy_gears_test_github_token', // WordPress option name for token
        
        // Update Settings
        'check_interval' => 'daily',          // How often to check (daily, twicedaily, hourly)
        'auto_update_default' => false,      // Default auto-update setting
    );
    
    /**
     * Plugin Paths and URLs
     */
    const PATHS = array(
        'plugin_file' => 'grumpy-gears-test-plugin.php',  // Main plugin file name (CHANGE THIS)
        'admin_css' => 'admin/css/admin.css',
        'admin_js' => 'admin/js/admin.js',
        'languages' => 'languages'
    );
    
    /**
     * Database Options
     */
    const OPTIONS = array(
        'main_options' => 'grumpy_gears_test_plugin_options',      // Main options key (CHANGE THIS)
        'updater_options' => 'grumpy_gears_test_updater_options',  // Updater options key (CHANGE THIS)
        'version_key' => 'grumpy_gears_test_plugin_version'        // Version tracking key (CHANGE THIS)
    );
    
    /**
     * Get plugin information
     */
    public static function get_plugin_info($key = null) {
        if ($key) {
            return isset(self::PLUGIN_INFO[$key]) ? self::PLUGIN_INFO[$key] : null;
        }
        return self::PLUGIN_INFO;
    }
    
    /**
     * Get GitHub configuration
     */
    public static function get_github_config($key = null) {
        if ($key) {
            return isset(self::GITHUB_CONFIG[$key]) ? self::GITHUB_CONFIG[$key] : null;
        }
        return self::GITHUB_CONFIG;
    }
    
    /**
     * Get paths configuration
     */
    public static function get_paths($key = null) {
        if ($key) {
            return isset(self::PATHS[$key]) ? self::PATHS[$key] : null;
        }
        return self::PATHS;
    }
    
    /**
     * Get options configuration
     */
    public static function get_options($key = null) {
        if ($key) {
            return isset(self::OPTIONS[$key]) ? self::OPTIONS[$key] : null;
        }
        return self::OPTIONS;
    }
    
    /**
     * Get GitHub repository URL
     */
    public static function get_repo_url() {
        return sprintf('https://github.com/%s/%s', 
            self::GITHUB_CONFIG['username'], 
            self::GITHUB_CONFIG['repository']
        );
    }
    
    /**
     * Get GitHub API URL
     */
    public static function get_api_url() {
        return sprintf('https://api.github.com/repos/%s/%s', 
            self::GITHUB_CONFIG['username'], 
            self::GITHUB_CONFIG['repository']
        );
    }
    
    /**
     * Get GitHub releases API URL
     */
    public static function get_releases_url() {
        return self::get_api_url() . '/releases';
    }
    
    /**
     * Get latest release API URL
     */
    public static function get_latest_release_url() {
        return self::get_releases_url() . '/latest';
    }
}
