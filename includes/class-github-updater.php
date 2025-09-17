<?php
/**
 * GitHub Updater Class
 * 
 * Handles GitHub API integration for automatic plugin updates
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class GrumpyGears_GitHub_Updater {
    
    /**
     * Plugin configuration
     */
    private $config;
    
    /**
     * GitHub API base URL
     */
    private $api_url;
    
    /**
     * Current plugin version
     */
    private $current_version;
    
    /**
     * Plugin slug
     */
    private $plugin_slug;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->config = new GrumpyGears_Plugin_Config();
        $this->api_url = $this->config->get_api_url();
        $this->current_version = $this->config->get_plugin_info('version');
        $this->plugin_slug = $this->config->get_paths('plugin_file');
        
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Hook into WordPress update system
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_update'));
        add_filter('plugins_api', array($this, 'plugin_popup'), 10, 3);
        add_filter('upgrader_pre_download', array($this, 'upgrader_pre_download'), 10, 3);
        
        // Daily update check
        add_action('grumpy_gears_daily_update_check', array($this, 'daily_update_check'));
        
        // AJAX handlers
        add_action('wp_ajax_grumpy_gears_check_update', array($this, 'ajax_check_update'));
        add_action('wp_ajax_grumpy_gears_get_changelog', array($this, 'ajax_get_changelog'));
    }
    
    /**
     * Check for plugin updates
     */
    public function check_for_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }
        
        $plugin_slug = plugin_basename(GRUMPY_GEARS_PLUGIN_FILE);
        
        // Get remote version
        $remote_version = $this->get_remote_version();
        
        if (version_compare($this->current_version, $remote_version['version'], '<')) {
            $transient->response[$plugin_slug] = (object) array(
                'slug' => $plugin_slug,
                'plugin' => $plugin_slug,
                'new_version' => $remote_version['version'],
                'url' => $this->config->get_repo_url(),
                'package' => $remote_version['download_url'],
                'icons' => array(),
                'banners' => array(),
                'banners_rtl' => array(),
                'tested' => $this->config->get_plugin_info('tested_up_to'),
                'requires_php' => $this->config->get_plugin_info('requires_php'),
                'compatibility' => new stdClass(),
            );
        }
        
        return $transient;
    }
    
    /**
     * Get remote version from GitHub API
     */
    private function get_remote_version() {
        $request_uri = $this->config->get_latest_release_url();
        $response = $this->github_api_request($request_uri);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $release = json_decode($body, true);
        
        if (empty($release)) {
            return false;
        }
        
        return array(
            'version' => $release['tag_name'],
            'download_url' => $release['zipball_url'],
            'changelog' => $release['body'],
            'published_at' => $release['published_at']
        );
    }
    
    /**
     * Plugin information popup
     */
    public function plugin_popup($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }
        
        if (!isset($args->slug) || $args->slug !== plugin_basename(GRUMPY_GEARS_PLUGIN_FILE)) {
            return $result;
        }
        
        $remote_version = $this->get_remote_version();
        
        if (!$remote_version) {
            return $result;
        }
        
        return (object) array(
            'name' => $this->config->get_plugin_info('name'),
            'slug' => plugin_basename(GRUMPY_GEARS_PLUGIN_FILE),
            'version' => $remote_version['version'],
            'author' => $this->config->get_plugin_info('author'),
            'author_profile' => $this->config->get_plugin_info('author_uri'),
            'requires' => $this->config->get_plugin_info('requires_wp'),
            'tested' => $this->config->get_plugin_info('tested_up_to'),
            'requires_php' => $this->config->get_plugin_info('requires_php'),
            'sections' => array(
                'description' => $this->config->get_plugin_info('description'),
                'changelog' => $this->format_changelog($remote_version['changelog'])
            ),
            'download_link' => $remote_version['download_url'],
        );
    }
    
    /**
     * Override download URL for private repositories
     */
    public function upgrader_pre_download($reply, $package, $upgrader) {
        if (strpos($package, 'github.com') !== false && strpos($package, 'zipball') !== false) {
            $response = $this->github_api_request($package);
            
            if (is_wp_error($response)) {
                return new WP_Error('download_failed', 'Failed to download update package');
            }
            
            $body = wp_remote_retrieve_body($response);
            $temp_file = download_url($package);
            
            if (is_wp_error($temp_file)) {
                return $temp_file;
            }
            
            return $temp_file;
        }
        
        return $reply;
    }
    
    /**
     * Daily update check
     */
    public function daily_update_check() {
        // Force WordPress to check for updates
        delete_site_transient('update_plugins');
        wp_update_plugins();
        
        // Update last check time
        GrumpyGearsPlugin::get_instance()->update_option('last_update_check', time());
        
        // If auto-updates are enabled, trigger update
        if (GrumpyGearsPlugin::get_instance()->get_option('auto_update_enabled', false)) {
            $this->maybe_auto_update();
        }
    }
    
    /**
     * Maybe perform automatic update
     */
    private function maybe_auto_update() {
        $remote_version = $this->get_remote_version();
        
        if ($remote_version && version_compare($this->current_version, $remote_version['version'], '<')) {
            // Include necessary WordPress files
            if (!function_exists('request_filesystem_credentials')) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }
            
            include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
            include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
            
            $plugin_slug = plugin_basename(GRUMPY_GEARS_PLUGIN_FILE);
            $upgrader = new Plugin_Upgrader();
            $result = $upgrader->upgrade($plugin_slug);
            
            // Log the result
            if (is_wp_error($result)) {
                error_log('Grumpy Gears Plugin Auto-Update Failed: ' . $result->get_error_message());
            } else {
                error_log('Grumpy Gears Plugin Auto-Updated to version: ' . $remote_version['version']);
            }
        }
    }
    
    /**
     * AJAX handler for manual update check
     */
    public function ajax_check_update() {
        check_ajax_referer('grumpy_gears_check_update', 'nonce');
        
        if (!current_user_can('update_plugins')) {
            wp_die('Insufficient permissions');
        }
        
        // Force check for updates
        delete_site_transient('update_plugins');
        wp_update_plugins();
        
        $remote_version = $this->get_remote_version();
        
        if ($remote_version && version_compare($this->current_version, $remote_version['version'], '<')) {
            wp_send_json_success(array(
                'update_available' => true,
                'current_version' => $this->current_version,
                'new_version' => $remote_version['version'],
                'changelog' => $remote_version['changelog']
            ));
        } else {
            wp_send_json_success(array(
                'update_available' => false,
                'current_version' => $this->current_version,
                'message' => 'Plugin is up to date.'
            ));
        }
    }
    
    /**
     * AJAX handler for getting changelog
     */
    public function ajax_get_changelog() {
        check_ajax_referer('grumpy_gears_get_changelog', 'nonce');
        
        $version = sanitize_text_field($_POST['version']);
        $changelog = $this->get_changelog_for_version($version);
        
        wp_send_json_success(array('changelog' => $changelog));
    }
    
    /**
     * Get changelog for specific version
     */
    private function get_changelog_for_version($version) {
        $request_uri = $this->config->get_releases_url();
        $response = $this->github_api_request($request_uri);
        
        if (is_wp_error($response)) {
            return 'Unable to retrieve changelog.';
        }
        
        $body = wp_remote_retrieve_body($response);
        $releases = json_decode($body, true);
        
        foreach ($releases as $release) {
            if ($release['tag_name'] === $version) {
                return $this->format_changelog($release['body']);
            }
        }
        
        return 'Changelog not found.';
    }
    
    /**
     * Format changelog for display
     */
    private function format_changelog($changelog) {
        if (empty($changelog)) {
            return 'No changelog available.';
        }
        
        // Convert markdown to HTML (basic conversion)
        $changelog = wp_kses_post($changelog);
        $changelog = wpautop($changelog);
        
        return $changelog;
    }
    
    /**
     * Make GitHub API request with authentication
     */
    private function github_api_request($url) {
        $token = get_option($this->config->get_github_config('access_token_option'));
        
        $args = array(
            'timeout' => 30,
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress Plugin Updater'
            )
        );
        
        // Add authentication for private repositories
        if (!empty($token)) {
            $args['headers']['Authorization'] = 'token ' . $token;
        }
        
        return wp_remote_get($url, $args);
    }
    
    /**
     * Get update status
     */
    public function get_update_status() {
        $remote_version = $this->get_remote_version();
        
        if (!$remote_version) {
            return array(
                'status' => 'error',
                'message' => 'Unable to check for updates.'
            );
        }
        
        if (version_compare($this->current_version, $remote_version['version'], '<')) {
            return array(
                'status' => 'update_available',
                'current_version' => $this->current_version,
                'new_version' => $remote_version['version'],
                'changelog' => $remote_version['changelog']
            );
        }
        
        return array(
            'status' => 'up_to_date',
            'current_version' => $this->current_version,
            'message' => 'Plugin is up to date.'
        );
    }
}
