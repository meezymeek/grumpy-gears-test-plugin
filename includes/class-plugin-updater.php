<?php
/**
 * Plugin Updater Class
 * 
 * Integrates with WordPress update system
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class GrumpyGears_Plugin_Updater {
    
    /**
     * Plugin configuration
     */
    private $config;
    
    /**
     * Plugin slug
     */
    private $plugin_slug;
    
    /**
     * Plugin basename
     */
    private $plugin_basename;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->config = new GrumpyGears_Plugin_Config();
        $this->plugin_slug = $this->config->get_paths('plugin_file');
        $this->plugin_basename = plugin_basename(GRUMPY_GEARS_PLUGIN_FILE);
        
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Override plugin update transients
        add_filter('site_transient_update_plugins', array($this, 'modify_transient'), 10, 1);
        add_filter('transient_update_plugins', array($this, 'modify_transient'), 10, 1);
        
        // Plugin information
        add_filter('plugins_api', array($this, 'plugin_info'), 20, 3);
        
        // Update row styling
        add_action("in_plugin_update_message-{$this->plugin_basename}", array($this, 'update_message'));
        
        // After plugin row
        add_action("after_plugin_row_{$this->plugin_basename}", array($this, 'show_update_notification'), 10, 2);
    }
    
    /**
     * Modify the plugin update transient
     */
    public function modify_transient($transient) {
        if (empty($transient->checked) || !isset($transient->checked[$this->plugin_basename])) {
            return $transient;
        }
        
        $remote_version = $this->get_remote_version();
        
        if ($remote_version && version_compare($transient->checked[$this->plugin_basename], $remote_version['version'], '<')) {
            $transient->response[$this->plugin_basename] = (object) array(
                'slug' => $this->plugin_basename,
                'plugin' => $this->plugin_basename,
                'new_version' => $remote_version['version'],
                'url' => $this->config->get_repo_url(),
                'package' => $this->get_download_url($remote_version['version']),
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
     * Get remote version information
     */
    private function get_remote_version() {
        $transient_key = 'grumpy_gears_remote_version_' . md5($this->plugin_basename);
        $remote_version = get_transient($transient_key);
        
        if (false === $remote_version) {
            $request_uri = $this->config->get_latest_release_url();
            $response = $this->github_api_request($request_uri);
            
            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
                return false;
            }
            
            $body = wp_remote_retrieve_body($response);
            $release = json_decode($body, true);
            
            if (empty($release)) {
                return false;
            }
            
            $remote_version = array(
                'version' => $release['tag_name'],
                'download_url' => $release['zipball_url'],
                'changelog' => $release['body'],
                'published_at' => $release['published_at']
            );
            
            // Cache for 1 hour
            set_transient($transient_key, $remote_version, HOUR_IN_SECONDS);
        }
        
        return $remote_version;
    }
    
    /**
     * Get download URL for specific version
     */
    private function get_download_url($version) {
        return sprintf(
            'https://api.github.com/repos/%s/%s/zipball/%s',
            $this->config->get_github_config('username'),
            $this->config->get_github_config('repository'),
            $version
        );
    }
    
    /**
     * Plugin information for WordPress popup
     */
    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information' || !isset($args->slug)) {
            return $result;
        }
        
        if ($args->slug !== $this->plugin_basename && $args->slug !== dirname($this->plugin_basename)) {
            return $result;
        }
        
        $remote_version = $this->get_remote_version();
        
        if (!$remote_version) {
            return $result;
        }
        
        $plugin_info = (object) array(
            'name' => $this->config->get_plugin_info('name'),
            'slug' => $this->plugin_basename,
            'version' => $remote_version['version'],
            'author' => '<a href="' . $this->config->get_plugin_info('author_uri') . '">' . $this->config->get_plugin_info('author') . '</a>',
            'author_profile' => $this->config->get_plugin_info('author_uri'),
            'requires' => $this->config->get_plugin_info('requires_wp'),
            'tested' => $this->config->get_plugin_info('tested_up_to'),
            'requires_php' => $this->config->get_plugin_info('requires_php'),
            'rating' => 100,
            'num_ratings' => 1,
            'downloaded' => 0,
            'active_installs' => 1,
            'last_updated' => $remote_version['published_at'],
            'homepage' => $this->config->get_repo_url(),
            'download_link' => $this->get_download_url($remote_version['version']),
            'sections' => array(
                'description' => $this->config->get_plugin_info('description'),
                'installation' => 'This plugin updates automatically from GitHub releases.',
                'changelog' => $this->format_changelog($remote_version['changelog']),
            ),
        );
        
        return $plugin_info;
    }
    
    /**
     * Show update message in plugin row
     */
    public function update_message($plugin_data) {
        $remote_version = $this->get_remote_version();
        
        if ($remote_version) {
            echo '<br /><strong>Note:</strong> This update will be downloaded from GitHub.';
        }
    }
    
    /**
     * Show update notification after plugin row
     */
    public function show_update_notification($file, $plugin_data) {
        if (!current_user_can('update_plugins')) {
            return;
        }
        
        $remote_version = $this->get_remote_version();
        
        if (!$remote_version || !version_compare($plugin_data['Version'], $remote_version['version'], '<')) {
            return;
        }
        
        // Check if auto-update is enabled
        $options = get_option($this->config->get_options('main_options'));
        $auto_update_enabled = isset($options['auto_update_enabled']) ? $options['auto_update_enabled'] : false;
        
        echo '<tr class="plugin-update-tr" id="' . esc_attr($this->plugin_basename) . '-update" data-slug="' . esc_attr($this->plugin_basename) . '" data-plugin="' . esc_attr($file) . '">';
        echo '<td colspan="4" class="plugin-update colspanchange">';
        echo '<div class="update-message notice inline notice-warning notice-alt">';
        echo '<p>';
        
        if ($auto_update_enabled) {
            echo '<strong>Auto-update enabled:</strong> This plugin will update automatically during the next daily check. ';
        } else {
            echo '<strong>Manual update required:</strong> ';
        }
        
        echo 'Version ' . esc_html($remote_version['version']) . ' is available. ';
        echo '<a href="' . admin_url('options-general.php?page=grumpy-gears-plugin') . '">View details and changelog</a>.';
        echo '</p>';
        echo '</div>';
        echo '</td>';
        echo '</tr>';
    }
    
    /**
     * Format changelog for display
     */
    private function format_changelog($changelog) {
        if (empty($changelog)) {
            return 'No changelog available.';
        }
        
        // Basic markdown to HTML conversion
        $changelog = wp_kses_post($changelog);
        $changelog = wpautop($changelog);
        
        // Convert markdown headers
        $changelog = preg_replace('/^### (.+)$/m', '<h4>$1</h4>', $changelog);
        $changelog = preg_replace('/^## (.+)$/m', '<h3>$1</h3>', $changelog);
        $changelog = preg_replace('/^# (.+)$/m', '<h2>$1</h2>', $changelog);
        
        // Convert markdown lists
        $changelog = preg_replace('/^\* (.+)$/m', '<ul><li>$1</li></ul>', $changelog);
        $changelog = preg_replace('/^\- (.+)$/m', '<ul><li>$1</li></ul>', $changelog);
        
        // Clean up multiple ul tags
        $changelog = preg_replace('/<\/ul>\s*<ul>/', '', $changelog);
        
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
     * Clear update transients
     */
    public function clear_update_transients() {
        $transient_key = 'grumpy_gears_remote_version_' . md5($this->plugin_basename);
        delete_transient($transient_key);
        delete_site_transient('update_plugins');
    }
    
    /**
     * Force check for updates
     */
    public function force_update_check() {
        $this->clear_update_transients();
        wp_update_plugins();
    }
}
