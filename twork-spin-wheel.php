<?php
/**
 * Plugin Name: T-Work Spin Wheel System
 * Plugin URI: https://twork.com
 * Description: Professional Spin Wheel Management System with REST API, Admin Interface, and Analytics
 * Version: 1.0.0
 * Author: T-Work System
 * Author URI: https://twork.com
 * Text Domain: twork-spin-wheel
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('TWORK_SPIN_WHEEL_VERSION', '1.0.0');
define('TWORK_SPIN_WHEEL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TWORK_SPIN_WHEEL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TWORK_SPIN_WHEEL_PLUGIN_FILE', __FILE__);

/**
 * Main Plugin Class
 */
class TWork_Spin_Wheel_System
{
    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Database instance
     */
    private $database;

    /**
     * REST API instance
     */
    private $rest_api;

    /**
     * Admin instance
     */
    private $admin;

    /**
     * Get singleton instance
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load required files
     */
    private function load_dependencies()
    {
        // Core classes
        require_once TWORK_SPIN_WHEEL_PLUGIN_DIR . 'includes/class-database.php';
        require_once TWORK_SPIN_WHEEL_PLUGIN_DIR . 'includes/class-helpers.php';
        require_once TWORK_SPIN_WHEEL_PLUGIN_DIR . 'includes/class-rest-api.php';
        
        // Feature classes
        require_once TWORK_SPIN_WHEEL_PLUGIN_DIR . 'includes/class-analytics.php';
        require_once TWORK_SPIN_WHEEL_PLUGIN_DIR . 'includes/class-export.php';
        require_once TWORK_SPIN_WHEEL_PLUGIN_DIR . 'includes/class-notifications.php';
        require_once TWORK_SPIN_WHEEL_PLUGIN_DIR . 'includes/class-shortcodes.php';
        require_once TWORK_SPIN_WHEEL_PLUGIN_DIR . 'includes/class-ajax.php';
        require_once TWORK_SPIN_WHEEL_PLUGIN_DIR . 'includes/class-widgets.php';
        require_once TWORK_SPIN_WHEEL_PLUGIN_DIR . 'includes/class-webhooks.php';
        require_once TWORK_SPIN_WHEEL_PLUGIN_DIR . 'includes/class-prize-templates.php';
        require_once TWORK_SPIN_WHEEL_PLUGIN_DIR . 'includes/class-bulk-operations.php';
        require_once TWORK_SPIN_WHEEL_PLUGIN_DIR . 'includes/class-health-check.php';
        require_once TWORK_SPIN_WHEEL_PLUGIN_DIR . 'includes/class-backup.php';
        require_once TWORK_SPIN_WHEEL_PLUGIN_DIR . 'includes/class-api-docs.php';
        require_once TWORK_SPIN_WHEEL_PLUGIN_DIR . 'includes/class-prize-categories.php';
        require_once TWORK_SPIN_WHEEL_PLUGIN_DIR . 'includes/class-custom-fields.php';
        require_once TWORK_SPIN_WHEEL_PLUGIN_DIR . 'includes/class-ab-testing.php';
        require_once TWORK_SPIN_WHEEL_PLUGIN_DIR . 'includes/class-conversion-tracking.php';
        require_once TWORK_SPIN_WHEEL_PLUGIN_DIR . 'includes/class-charts.php';
        
        // Utility classes
        require_once TWORK_SPIN_WHEEL_PLUGIN_DIR . 'includes/class-logger.php';
        require_once TWORK_SPIN_WHEEL_PLUGIN_DIR . 'includes/class-cache.php';
        require_once TWORK_SPIN_WHEEL_PLUGIN_DIR . 'includes/class-security.php';
        
        // Admin class (load last as it depends on others)
        if (is_admin()) {
            require_once TWORK_SPIN_WHEEL_PLUGIN_DIR . 'includes/class-admin.php';
        }
    }

    /**
     * Initialize hooks
     */
    private function init_hooks()
    {
        // Activation/Deactivation hooks
        register_activation_hook(TWORK_SPIN_WHEEL_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(TWORK_SPIN_WHEEL_PLUGIN_FILE, array($this, 'deactivate'));

        // Initialize components
        add_action('plugins_loaded', array($this, 'init'), 10);
    }

    /**
     * Initialize plugin components
     */
    public function init()
    {
        // Initialize database
        $this->database = new TWork_Spin_Wheel_Database();

        // Initialize REST API
        $this->rest_api = new TWork_Spin_Wheel_REST_API();

        // Initialize AJAX handlers
        new TWork_Spin_Wheel_Ajax();

        // Initialize widgets
        new TWork_Spin_Wheel_Widgets();

        // Initialize webhooks
        new TWork_Spin_Wheel_Webhooks();

        // Initialize API documentation
        new TWork_Spin_Wheel_API_Docs();

        // Initialize conversion tracking
        new TWork_Spin_Wheel_Conversion_Tracking();

        // Initialize shortcodes (frontend)
        if (!is_admin()) {
            new TWork_Spin_Wheel_Shortcodes();
        }

        // Initialize Admin (only in admin area)
        if (is_admin()) {
            $this->admin = new TWork_Spin_Wheel_Admin();
        }

        // Schedule cleanup tasks
        $this->schedule_cleanup_tasks();
    }

    /**
     * Schedule cleanup tasks
     *
     * @return void
     */
    private function schedule_cleanup_tasks()
    {
        // Clear old logs daily
        if (!wp_next_scheduled('twork_spin_wheel_cleanup_logs')) {
            wp_schedule_event(time(), 'daily', 'twork_spin_wheel_cleanup_logs');
        }

        add_action('twork_spin_wheel_cleanup_logs', array($this, 'cleanup_old_logs'));

        // Update analytics daily
        if (!wp_next_scheduled('twork_spin_wheel_update_analytics')) {
            wp_schedule_event(time(), 'daily', 'twork_spin_wheel_update_analytics');
        }

        add_action('twork_spin_wheel_update_analytics', array($this, 'update_daily_analytics'));
    }

    /**
     * Cleanup old logs
     *
     * @return void
     */
    public function cleanup_old_logs()
    {
        $days = get_option('twork_spin_wheel_log_retention_days', 30);
        TWork_Spin_Wheel_Logger::clear_old_logs($days);
    }

    /**
     * Update daily analytics
     *
     * @return void
     */
    public function update_daily_analytics()
    {
        global $wpdb;
        $database = new TWork_Spin_Wheel_Database();
        $wheels_table = $database->get_wheels_table();
        $analytics = new TWork_Spin_Wheel_Analytics();

        $wheels = $wpdb->get_results("SELECT id FROM {$wheels_table} WHERE is_active = 1");

        foreach ($wheels as $wheel) {
            $analytics->update_analytics($wheel->id, null, date('Y-m-d', strtotime('-1 day')));
        }
    }

    /**
     * Plugin activation
     */
    public function activate()
    {
        // Create database tables
        $database = new TWork_Spin_Wheel_Database();
        $database->create_tables();

        // Create additional tables
        $categories = new TWork_Spin_Wheel_Prize_Categories();
        $categories->create_table();

        $custom_fields = new TWork_Spin_Wheel_Custom_Fields();
        $custom_fields->create_table();

        // Set default options
        add_option('twork_spin_wheel_version', TWORK_SPIN_WHEEL_VERSION);
        add_option('twork_spin_wheel_db_version', '1.0.0');
        add_option('twork_spin_wheel_enable_cache', true);
        add_option('twork_spin_wheel_enable_logging', true);
        add_option('twork_spin_wheel_enable_rate_limiting', true);
        add_option('twork_spin_wheel_notify_on_spin', true);
        add_option('twork_spin_wheel_log_retention_days', 30);

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate()
    {
        // Clear scheduled events
        wp_clear_scheduled_hook('twork_spin_wheel_cleanup_logs');
        wp_clear_scheduled_hook('twork_spin_wheel_update_analytics');

        // Clear cache
        TWork_Spin_Wheel_Cache::clear_all();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Get database instance
     */
    public function get_database()
    {
        return $this->database;
    }

    /**
     * Get REST API instance
     */
    public function get_rest_api()
    {
        return $this->rest_api;
    }

    /**
     * Get admin instance
     */
    public function get_admin()
    {
        return $this->admin;
    }
}

/**
 * Initialize the plugin
 */
function twork_spin_wheel_init()
{
    return TWork_Spin_Wheel_System::get_instance();
}

// Start the plugin
twork_spin_wheel_init();

