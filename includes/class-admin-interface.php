<?php
/**
 * Admin Interface Class
 * 
 * Handles admin pages and interface for plugin updates
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class GrumpyGears_Admin_Interface {
    
    /**
     * Plugin configuration
     */
    private $config;
    
    /**
     * GitHub updater instance
     */
    private $github_updater;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->config = new GrumpyGears_Plugin_Config();
        $this->github_updater = new GrumpyGears_GitHub_Updater();
        
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Add settings link to plugins page
        $plugin_basename = plugin_basename(GRUMPY_GEARS_PLUGIN_FILE);
        add_filter("plugin_action_links_{$plugin_basename}", array($this, 'add_settings_link'));
        
        // Add update notification
        add_action('admin_notices', array($this, 'update_admin_notice'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            $this->config->get_plugin_info('name') . ' Settings',
            'Grumpy Gears Plugin',
            'manage_options',
            'grumpy-gears-plugin',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Initialize admin settings
     */
    public function admin_init() {
        register_setting(
            'grumpy_gears_plugin_settings',
            $this->config->get_options('main_options'),
            array($this, 'sanitize_settings')
        );
        
        // Plugin Settings Section
        add_settings_section(
            'grumpy_gears_plugin_main',
            'Plugin Settings',
            array($this, 'settings_section_callback'),
            'grumpy-gears-plugin'
        );
        
        // Auto-update setting
        add_settings_field(
            'auto_update_enabled',
            'Enable Automatic Updates',
            array($this, 'auto_update_callback'),
            'grumpy-gears-plugin',
            'grumpy_gears_plugin_main'
        );
        
        // GitHub token setting
        add_settings_field(
            'github_token',
            'GitHub Personal Access Token',
            array($this, 'github_token_callback'),
            'grumpy-gears-plugin',
            'grumpy_gears_plugin_main'
        );
        
        // Update notifications setting
        add_settings_field(
            'update_notifications',
            'Show Update Notifications',
            array($this, 'update_notifications_callback'),
            'grumpy-gears-plugin',
            'grumpy_gears_plugin_main'
        );
    }
    
    /**
     * Settings section callback
     */
    public function settings_section_callback() {
        echo '<p>Configure your plugin update settings and GitHub integration.</p>';
    }
    
    /**
     * Auto-update checkbox callback
     */
    public function auto_update_callback() {
        $options = get_option($this->config->get_options('main_options'));
        $checked = isset($options['auto_update_enabled']) ? $options['auto_update_enabled'] : false;
        
        echo '<input type="checkbox" id="auto_update_enabled" name="' . $this->config->get_options('main_options') . '[auto_update_enabled]" value="1" ' . checked(1, $checked, false) . ' />';
        echo '<label for="auto_update_enabled">Automatically install updates when they become available</label>';
        echo '<p class="description">When enabled, the plugin will automatically update to new versions during daily checks.</p>';
    }
    
    /**
     * GitHub token field callback
     */
    public function github_token_callback() {
        $options = get_option($this->config->get_options('main_options'));
        $token = isset($options['github_token']) ? $options['github_token'] : '';
        
        echo '<input type="password" id="github_token" name="' . $this->config->get_options('main_options') . '[github_token]" value="' . esc_attr($token) . '" class="regular-text" />';
        echo '<p class="description">Required for private repositories. <a href="https://github.com/settings/tokens" target="_blank">Generate a token</a> with "repo" permissions.</p>';
    }
    
    /**
     * Update notifications checkbox callback
     */
    public function update_notifications_callback() {
        $options = get_option($this->config->get_options('main_options'));
        $checked = isset($options['update_notifications']) ? $options['update_notifications'] : true;
        
        echo '<input type="checkbox" id="update_notifications" name="' . $this->config->get_options('main_options') . '[update_notifications]" value="1" ' . checked(1, $checked, false) . ' />';
        echo '<label for="update_notifications">Show admin notifications when updates are available</label>';
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        $sanitized['auto_update_enabled'] = isset($input['auto_update_enabled']) ? (bool) $input['auto_update_enabled'] : false;
        $sanitized['github_token'] = isset($input['github_token']) ? sanitize_text_field($input['github_token']) : '';
        $sanitized['update_notifications'] = isset($input['update_notifications']) ? (bool) $input['update_notifications'] : true;
        $sanitized['last_update_check'] = time();
        
        // Store GitHub token separately for security
        if (!empty($sanitized['github_token'])) {
            update_option($this->config->get_github_config('access_token_option'), $sanitized['github_token']);
            $sanitized['github_token'] = ''; // Don't store in main options
        }
        
        return $sanitized;
    }
    
    /**
     * Admin page content
     */
    public function admin_page() {
        $update_status = $this->github_updater->get_update_status();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html($this->config->get_plugin_info('name')); ?> Settings</h1>
            
            <!-- Update Status Card -->
            <div class="card">
                <h2>Update Status</h2>
                <div id="update-status">
                    <?php $this->render_update_status($update_status); ?>
                </div>
                <p>
                    <button type="button" id="check-updates-btn" class="button button-secondary">
                        <span class="dashicons dashicons-update"></span> Check for Updates
                    </button>
                    <span id="check-updates-spinner" class="spinner" style="display: none; margin-top: 5px;"></span>
                </p>
            </div>
            
            <!-- Settings Form -->
            <form method="post" action="options.php">
                <?php
                settings_fields('grumpy_gears_plugin_settings');
                do_settings_sections('grumpy-gears-plugin');
                submit_button();
                ?>
            </form>
            
            <!-- Plugin Information -->
            <div class="card">
                <h2>Plugin Information</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Current Version:</th>
                        <td><?php echo esc_html($this->config->get_plugin_info('version')); ?></td>
                    </tr>
                    <tr>
                        <th scope="row">Author:</th>
                        <td><a href="<?php echo esc_url($this->config->get_plugin_info('author_uri')); ?>" target="_blank"><?php echo esc_html($this->config->get_plugin_info('author')); ?></a></td>
                    </tr>
                    <tr>
                        <th scope="row">Repository:</th>
                        <td><a href="<?php echo esc_url($this->config->get_repo_url()); ?>" target="_blank"><?php echo esc_html($this->config->get_repo_url()); ?></a></td>
                    </tr>
                    <tr>
                        <th scope="row">Last Update Check:</th>
                        <td><?php echo esc_html(date('Y-m-d H:i:s', GrumpyGearsPlugin::get_instance()->get_option('last_update_check', time()))); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Changelog Modal -->
        <div id="changelog-modal" class="grumpy-gears-modal" style="display: none;">
            <div class="grumpy-gears-modal-content">
                <span class="grumpy-gears-modal-close">&times;</span>
                <h2>Changelog</h2>
                <div id="changelog-content"></div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render update status
     */
    private function render_update_status($status) {
        switch ($status['status']) {
            case 'update_available':
                echo '<div class="notice notice-warning inline">';
                echo '<p><strong>Update Available!</strong></p>';
                echo '<p>Current version: <strong>' . esc_html($status['current_version']) . '</strong></p>';
                echo '<p>New version: <strong>' . esc_html($status['new_version']) . '</strong></p>';
                echo '<p><button type="button" id="view-changelog-btn" class="button button-primary">View Changelog</button></p>';
                echo '</div>';
                break;
                
            case 'up_to_date':
                echo '<div class="notice notice-success inline">';
                echo '<p><strong>Plugin is up to date!</strong></p>';
                echo '<p>Current version: <strong>' . esc_html($status['current_version']) . '</strong></p>';
                echo '</div>';
                break;
                
            case 'error':
                echo '<div class="notice notice-error inline">';
                echo '<p><strong>Error:</strong> ' . esc_html($status['message']) . '</p>';
                echo '</div>';
                break;
        }
    }
    
    /**
     * Add settings link to plugins page
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=grumpy-gears-plugin') . '">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Show update admin notice
     */
    public function update_admin_notice() {
        $options = get_option($this->config->get_options('main_options'));
        
        if (isset($options['update_notifications']) && !$options['update_notifications']) {
            return;
        }
        
        $update_status = $this->github_updater->get_update_status();
        
        if ($update_status['status'] === 'update_available') {
            $current_screen = get_current_screen();
            
            // Don't show on our settings page to avoid duplicate notices
            if ($current_screen && $current_screen->id === 'settings_page_grumpy-gears-plugin') {
                return;
            }
            
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>' . esc_html($this->config->get_plugin_info('name')) . ':</strong> ';
            echo 'Version ' . esc_html($update_status['new_version']) . ' is available. ';
            echo '<a href="' . admin_url('options-general.php?page=grumpy-gears-plugin') . '">View details</a></p>';
            echo '</div>';
        }
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'settings_page_grumpy-gears-plugin') {
            return;
        }
        
        wp_enqueue_script(
            'grumpy-gears-admin',
            GRUMPY_GEARS_PLUGIN_URL . 'admin/js/admin.js',
            array('jquery'),
            $this->config->get_plugin_info('version'),
            true
        );
        
        wp_localize_script('grumpy-gears-admin', 'grumpyGearsAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'check_update_nonce' => wp_create_nonce('grumpy_gears_check_update'),
            'changelog_nonce' => wp_create_nonce('grumpy_gears_get_changelog')
        ));
        
        wp_enqueue_style(
            'grumpy-gears-admin',
            GRUMPY_GEARS_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            $this->config->get_plugin_info('version')
        );
    }
}
