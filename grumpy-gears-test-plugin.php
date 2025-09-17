<?php
/**
 * Plugin Name: Grumpy Gears Test Plugin
 * Plugin URI: https://github.com/meezymeek/grumpy-gears-test-plugin
 * Description: A test plugin to demonstrate the Grumpy Gears plugin framework with GitHub auto-update capabilities.
 * Version: 1.0.0
 * Author: Grumpy Gears
 * Author URI: https://github.com/grumpygears
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: grumpy-gears-test-plugin
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 * GitHub Plugin URI: meezymeek/grumpy-gears-test-plugin
 * GitHub Branch: main
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('GRUMPY_GEARS_PLUGIN_VERSION', '1.0.0');
define('GRUMPY_GEARS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('GRUMPY_GEARS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GRUMPY_GEARS_PLUGIN_FILE', __FILE__);
define('GRUMPY_GEARS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
class GrumpyGearsPlugin {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * GitHub updater instance
     */
    private $github_updater;
    
    /**
     * Admin interface instance
     */
    private $admin_interface;
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Load required files
     */
    private function load_dependencies() {
        require_once GRUMPY_GEARS_PLUGIN_PATH . 'includes/class-github-updater.php';
        require_once GRUMPY_GEARS_PLUGIN_PATH . 'includes/class-admin-interface.php';
        require_once GRUMPY_GEARS_PLUGIN_PATH . 'includes/class-plugin-updater.php';
        require_once GRUMPY_GEARS_PLUGIN_PATH . 'config/plugin-config.php';
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'plugins_loaded'));
        
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Initialize updater
        if (is_admin()) {
            $this->github_updater = new GrumpyGears_GitHub_Updater();
            $this->admin_interface = new GrumpyGears_Admin_Interface();
        }
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain for internationalization
        load_plugin_textdomain('grumpy-gears-test-plugin', false, dirname(GRUMPY_GEARS_PLUGIN_BASENAME) . '/languages');
    }
    
    /**
     * Plugins loaded callback
     */
    public function plugins_loaded() {
        // Plugin initialization code here
        do_action('grumpy_gears_plugin_loaded');
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create necessary database tables, options, etc.
        $this->create_plugin_options();
        
        // Schedule daily update check
        if (!wp_next_scheduled('grumpy_gears_daily_update_check')) {
            wp_schedule_event(time(), 'daily', 'grumpy_gears_daily_update_check');
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('grumpy_gears_daily_update_check');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create default plugin options
     */
    private function create_plugin_options() {
        $default_options = array(
            'auto_update_enabled' => false,
            'github_token' => '',
            'last_update_check' => time(),
            'update_notifications' => true
        );
        
        add_option('grumpy_gears_plugin_options', $default_options);
    }
    
    /**
     * Get plugin option
     */
    public function get_option($key, $default = false) {
        $options = get_option('grumpy_gears_plugin_options', array());
        return isset($options[$key]) ? $options[$key] : $default;
    }
    
    /**
     * Update plugin option
     */
    public function update_option($key, $value) {
        $options = get_option('grumpy_gears_plugin_options', array());
        $options[$key] = $value;
        update_option('grumpy_gears_plugin_options', $options);
    }
}

// Initialize the plugin
function grumpy_gears_plugin_init() {
    return GrumpyGearsPlugin::get_instance();
}

// Start the plugin
grumpy_gears_plugin_init();
