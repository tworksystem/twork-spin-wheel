<?php
/**
 * Admin Interface Class
 * 
 * Handles all admin interface functionality for the Spin Wheel System
 */

if (!defined('ABSPATH')) {
    exit;
}

class TWork_Spin_Wheel_Admin
{
    /**
     * Database instance
     */
    private $database;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->database = new TWork_Spin_Wheel_Database();
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks()
    {
        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Form handlers
        add_action('admin_post_twork_spin_wheel_save_settings', array($this, 'handle_save_settings'));
        add_action('admin_post_twork_spin_wheel_save_prize', array($this, 'handle_save_prize'));
        add_action('admin_post_twork_spin_wheel_delete_prize', array($this, 'handle_delete_prize'));
        add_action('admin_post_twork_spin_wheel_toggle_prize', array($this, 'handle_toggle_prize'));
        add_action('admin_post_twork_spin_wheel_export', array($this, 'handle_export'));
        add_action('admin_post_twork_spin_wheel_import', array($this, 'handle_import'));
        add_action('admin_post_twork_spin_wheel_save_advanced', array($this, 'handle_save_advanced'));
        add_action('admin_post_twork_spin_wheel_apply_template', array($this, 'handle_apply_template'));
        add_action('admin_post_twork_spin_wheel_add_webhook', array($this, 'handle_add_webhook'));
        add_action('admin_post_twork_spin_wheel_delete_webhook', array($this, 'handle_delete_webhook'));
        add_action('admin_post_twork_spin_wheel_create_backup', array($this, 'handle_create_backup'));
        add_action('admin_post_twork_spin_wheel_restore_backup', array($this, 'handle_restore_backup'));

        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu()
    {
        add_menu_page(
            __('Spin Wheel', 'twork-spin-wheel'),
            __('Spin Wheel', 'twork-spin-wheel'),
            'manage_options',
            'twork-spin-wheel',
            array($this, 'render_main_page'),
            'dashicons-tickets-alt',
            30
        );
    }

    /**
     * Render main admin page
     */
    public function render_main_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.', 'twork-spin-wheel'));
        }

        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'settings';

        ?>
        <div class="wrap twork-spin-wheel-wrap">
            <?php
            // Get quick stats for header
            global $wpdb;
            $analytics = new TWork_Spin_Wheel_Analytics();
            $quick_stats = $analytics->get_statistics();
            ?>
            <div class="twork-header-section">
                <div class="twork-header-content">
                    <h1 class="twork-main-title">
                        <span class="twork-icon-wrapper">
                            <span class="dashicons dashicons-tickets-alt"></span>
                        </span>
                        <span class="twork-title-text"><?php esc_html_e('Spin Wheel Management', 'twork-spin-wheel'); ?></span>
                        <span class="twork-badge">Pro</span>
                    </h1>
                    <p class="twork-subtitle"><?php esc_html_e('Manage your spin wheel system with style', 'twork-spin-wheel'); ?></p>
                    
                    <!-- Quick Stats -->
                    <div class="twork-quick-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 20px; margin-top: 25px;">
                        <div class="twork-quick-stat-item">
                            <div class="twork-quick-stat-value" data-stat="total_spins"><?php echo esc_html(number_format($quick_stats['total_spins'])); ?></div>
                            <div class="twork-quick-stat-label"><?php esc_html_e('Total Spins', 'twork-spin-wheel'); ?></div>
                        </div>
                        <div class="twork-quick-stat-item">
                            <div class="twork-quick-stat-value" data-stat="unique_users"><?php echo esc_html(number_format($quick_stats['unique_users'])); ?></div>
                            <div class="twork-quick-stat-label"><?php esc_html_e('Unique Users', 'twork-spin-wheel'); ?></div>
                        </div>
                        <div class="twork-quick-stat-item">
                            <div class="twork-quick-stat-value" data-stat="points_spent"><?php echo esc_html(number_format($quick_stats['points_spent'])); ?></div>
                            <div class="twork-quick-stat-label"><?php esc_html_e('Points Spent', 'twork-spin-wheel'); ?></div>
                        </div>
                    </div>
                </div>
                <div class="twork-header-actions">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=twork-spin-wheel&tab=analytics')); ?>" class="twork-quick-link">
                        <span class="dashicons dashicons-chart-bar"></span>
                        <?php esc_html_e('View Analytics', 'twork-spin-wheel'); ?>
                    </a>
                </div>
            </div>

            <?php $this->render_notices(); ?>

            <nav class="nav-tab-wrapper wp-clearfix twork-nav-tabs">
                <a href="<?php echo esc_url(add_query_arg(array('page' => 'twork-spin-wheel', 'tab' => 'settings'), admin_url('admin.php'))); ?>" 
                   class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php esc_html_e('Settings', 'twork-spin-wheel'); ?>
                </a>
                <a href="<?php echo esc_url(add_query_arg(array('page' => 'twork-spin-wheel', 'tab' => 'prizes'), admin_url('admin.php'))); ?>" 
                   class="nav-tab <?php echo $active_tab === 'prizes' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-awards"></span>
                    <?php esc_html_e('Prizes', 'twork-spin-wheel'); ?>
                </a>
                <a href="<?php echo esc_url(add_query_arg(array('page' => 'twork-spin-wheel', 'tab' => 'history'), admin_url('admin.php'))); ?>" 
                   class="nav-tab <?php echo $active_tab === 'history' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-list-view"></span>
                    <?php esc_html_e('Spin History', 'twork-spin-wheel'); ?>
                </a>
                <a href="<?php echo esc_url(add_query_arg(array('page' => 'twork-spin-wheel', 'tab' => 'analytics'), admin_url('admin.php'))); ?>" 
                   class="nav-tab <?php echo $active_tab === 'analytics' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-chart-bar"></span>
                    <?php esc_html_e('Analytics', 'twork-spin-wheel'); ?>
                </a>
                <a href="<?php echo esc_url(add_query_arg(array('page' => 'twork-spin-wheel', 'tab' => 'export'), admin_url('admin.php'))); ?>" 
                   class="nav-tab <?php echo $active_tab === 'export' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-download"></span>
                    <?php esc_html_e('Export/Import', 'twork-spin-wheel'); ?>
                </a>
                <a href="<?php echo esc_url(add_query_arg(array('page' => 'twork-spin-wheel', 'tab' => 'advanced'), admin_url('admin.php'))); ?>" 
                   class="nav-tab <?php echo $active_tab === 'advanced' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php esc_html_e('Advanced', 'twork-spin-wheel'); ?>
                </a>
                <a href="<?php echo esc_url(add_query_arg(array('page' => 'twork-spin-wheel', 'tab' => 'templates'), admin_url('admin.php'))); ?>" 
                   class="nav-tab <?php echo $active_tab === 'templates' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-admin-page"></span>
                    <?php esc_html_e('Templates', 'twork-spin-wheel'); ?>
                </a>
                <a href="<?php echo esc_url(add_query_arg(array('page' => 'twork-spin-wheel', 'tab' => 'webhooks'), admin_url('admin.php'))); ?>" 
                   class="nav-tab <?php echo $active_tab === 'webhooks' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-networking"></span>
                    <?php esc_html_e('Webhooks', 'twork-spin-wheel'); ?>
                </a>
                <a href="<?php echo esc_url(add_query_arg(array('page' => 'twork-spin-wheel', 'tab' => 'health'), admin_url('admin.php'))); ?>" 
                   class="nav-tab <?php echo $active_tab === 'health' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-heart"></span>
                    <?php esc_html_e('Health Check', 'twork-spin-wheel'); ?>
                </a>
                <a href="<?php echo esc_url(add_query_arg(array('page' => 'twork-spin-wheel', 'tab' => 'backup'), admin_url('admin.php'))); ?>" 
                   class="nav-tab <?php echo $active_tab === 'backup' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-backup"></span>
                    <?php esc_html_e('Backup/Restore', 'twork-spin-wheel'); ?>
                </a>
            </nav>

            <div class="twork-tab-content">
                <?php
                switch ($active_tab) {
                    case 'settings':
                        $this->render_settings_tab();
                        break;
                    case 'prizes':
                        $this->render_prizes_tab();
                        break;
                    case 'history':
                        $this->render_history_tab();
                        break;
                    case 'analytics':
                        $this->render_analytics_tab();
                        break;
                    case 'export':
                        $this->render_export_tab();
                        break;
                    case 'advanced':
                        $this->render_advanced_tab();
                        break;
                    case 'templates':
                        $this->render_templates_tab();
                        break;
                    case 'webhooks':
                        $this->render_webhooks_tab();
                        break;
                    case 'health':
                        $this->render_health_tab();
                        break;
                    case 'backup':
                        $this->render_backup_tab();
                        break;
                    default:
                        $this->render_settings_tab();
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render admin notices
     */
    private function render_notices()
    {
        if (isset($_GET['updated'])): ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e('Settings saved successfully!', 'twork-spin-wheel'); ?></p>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e('Prize deleted successfully!', 'twork-spin-wheel'); ?></p>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['toggled'])): ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e('Prize status updated successfully!', 'twork-spin-wheel'); ?></p>
            </div>
        <?php endif;
    }

    /**
     * Render settings tab
     */
    private function render_settings_tab()
    {
        global $wpdb;
        $table_name = $this->database->get_wheels_table();

        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;

        if (!$table_exists) {
            ?>
            <div class="notice notice-error">
                <p><strong><?php esc_html_e('Error:', 'twork-spin-wheel'); ?></strong> 
                <?php esc_html_e('Spin Wheel table does not exist. Please deactivate and reactivate the plugin.', 'twork-spin-wheel'); ?></p>
            </div>
            <?php
            return;
        }

        // Get current configuration
        $config = $wpdb->get_row("SELECT * FROM {$table_name} WHERE is_default = 1 ORDER BY id DESC LIMIT 1");

        // Default values
        $defaults = array(
            'wheel_title' => $config && isset($config->name) ? $config->name : 'Lucky Spin Wheel',
            'wheel_description' => $config && isset($config->description) ? $config->description : 'Spin the wheel and win amazing prizes!',
            'wheel_status' => $config && isset($config->is_active) && $config->is_active ? 'active' : 'inactive',
            'max_spins_per_day' => $config && isset($config->daily_limit) ? $config->daily_limit : 3,
            'points_per_spin' => $config && isset($config->cost_points) ? $config->cost_points : 100,
            'color_primary' => $config && isset($config->background_color) ? $config->background_color : '#FF6B6B',
            'color_secondary' => $config && isset($config->border_color) ? $config->border_color : '#4ECDC4',
            'color_text' => $config && isset($config->center_color) ? $config->center_color : '#FFFFFF',
            'show_confetti' => $config && isset($config->enable_animation) && $config->enable_animation ? 1 : 0,
            'show_sound' => $config && isset($config->enable_sound) && $config->enable_sound ? 1 : 0,
            'animation_duration' => $config && isset($config->animation_duration) ? $config->animation_duration : 5,
        );

        ?>
        <div class="twork-settings-container">
            <div class="twork-section-header">
                <h2 class="twork-section-title">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php esc_html_e('Wheel Configuration', 'twork-spin-wheel'); ?>
                </h2>
                <p class="twork-section-description"><?php esc_html_e('Customize your spin wheel appearance and behavior', 'twork-spin-wheel'); ?></p>
            </div>
            
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="twork-settings-form">
                <?php wp_nonce_field('twork_spin_wheel_settings'); ?>
                <input type="hidden" name="action" value="twork_spin_wheel_save_settings">

                <div class="twork-form-sections">
                    <div class="twork-form-section">
                        <h3 class="twork-form-section-title"><?php esc_html_e('Basic Information', 'twork-spin-wheel'); ?></h3>
                        <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="wheel_title"><?php esc_html_e('Wheel Title', 'twork-spin-wheel'); ?></label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="wheel_title" 
                                   name="wheel_title" 
                                   value="<?php echo esc_attr($defaults['wheel_title']); ?>" 
                                   class="regular-text">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="wheel_description"><?php esc_html_e('Description', 'twork-spin-wheel'); ?></label>
                        </th>
                        <td>
                            <textarea id="wheel_description" 
                                      name="wheel_description" 
                                      rows="3" 
                                      class="large-text"><?php echo esc_textarea($defaults['wheel_description']); ?></textarea>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="wheel_status"><?php esc_html_e('Status', 'twork-spin-wheel'); ?></label>
                        </th>
                        <td>
                            <select id="wheel_status" name="wheel_status">
                                <option value="active" <?php selected($defaults['wheel_status'], 'active'); ?>><?php esc_html_e('Active', 'twork-spin-wheel'); ?></option>
                                <option value="inactive" <?php selected($defaults['wheel_status'], 'inactive'); ?>><?php esc_html_e('Inactive', 'twork-spin-wheel'); ?></option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="max_spins_per_day"><?php esc_html_e('Max Spins Per Day', 'twork-spin-wheel'); ?></label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="max_spins_per_day" 
                                   name="max_spins_per_day" 
                                   value="<?php echo esc_attr($defaults['max_spins_per_day']); ?>" 
                                   min="1" 
                                   max="100" 
                                   class="small-text">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="points_per_spin"><?php esc_html_e('Points Per Spin', 'twork-spin-wheel'); ?></label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="points_per_spin" 
                                   name="points_per_spin" 
                                   value="<?php echo esc_attr($defaults['points_per_spin']); ?>" 
                                   min="0" 
                                   step="1" 
                                   class="small-text">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label><?php esc_html_e('Wheel Colors', 'twork-spin-wheel'); ?></label>
                        </th>
                        <td>
                            <div style="display: grid; gap: 15px;">
                                <div>
                                    <label for="color_primary"><?php esc_html_e('Primary Color', 'twork-spin-wheel'); ?></label><br>
                                    <input type="color" 
                                           id="color_primary" 
                                           name="color_primary" 
                                           value="<?php echo esc_attr($defaults['color_primary']); ?>">
                                </div>
                                <div>
                                    <label for="color_secondary"><?php esc_html_e('Secondary Color', 'twork-spin-wheel'); ?></label><br>
                                    <input type="color" 
                                           id="color_secondary" 
                                           name="color_secondary" 
                                           value="<?php echo esc_attr($defaults['color_secondary']); ?>">
                                </div>
                                <div>
                                    <label for="color_text"><?php esc_html_e('Text Color', 'twork-spin-wheel'); ?></label><br>
                                    <input type="color" 
                                           id="color_text" 
                                           name="color_text" 
                                           value="<?php echo esc_attr($defaults['color_text']); ?>">
                                </div>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label><?php esc_html_e('Visual Effects', 'twork-spin-wheel'); ?></label>
                        </th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" 
                                           name="show_confetti" 
                                           value="1" 
                                           <?php checked($defaults['show_confetti'], 1); ?>>
                                    <?php esc_html_e('Show confetti animation on win', 'twork-spin-wheel'); ?>
                                </label>
                                <br>
                                <label>
                                    <input type="checkbox" 
                                           name="show_sound" 
                                           value="1" 
                                           <?php checked($defaults['show_sound'], 1); ?>>
                                    <?php esc_html_e('Play sound effects', 'twork-spin-wheel'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="animation_duration"><?php esc_html_e('Animation Duration', 'twork-spin-wheel'); ?></label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="animation_duration" 
                                   name="animation_duration" 
                                   value="<?php echo esc_attr($defaults['animation_duration']); ?>" 
                                   min="3" 
                                   max="15" 
                                   class="small-text"> 
                            <?php esc_html_e('seconds', 'twork-spin-wheel'); ?>
                        </td>
                    </tr>
                </table>
                    </div>
                </div>

                <div class="twork-form-actions">
                    <?php submit_button(__('Save Settings', 'twork-spin-wheel'), 'primary twork-save-button', 'submit', false); ?>
                    <button type="button" class="button twork-preview-button">
                        <span class="dashicons dashicons-visibility"></span>
                        <?php esc_html_e('Preview', 'twork-spin-wheel'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Render prizes tab
     */
    private function render_prizes_tab()
    {
        global $wpdb;
        $wheels_table = $this->database->get_wheels_table();
        $prizes_table = $this->database->get_prizes_table();

        // Get current wheel
        $config = $wpdb->get_row("SELECT * FROM {$wheels_table} WHERE is_default = 1 ORDER BY id DESC LIMIT 1");

        if (!$config) {
            ?>
            <div class="notice notice-warning">
                <p><?php esc_html_e('Please configure the wheel settings first.', 'twork-spin-wheel'); ?></p>
            </div>
            <?php
            return;
        }

        // Get prizes
        $prizes = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$prizes_table} WHERE wheel_id = %d ORDER BY display_order ASC, id ASC",
                $config->id
            )
        );

        // Check if editing
        $editing_prize = null;
        if (isset($_GET['edit_prize'])) {
            $edit_id = absint($_GET['edit_prize']);
            foreach ($prizes as $prize) {
                if ($prize->id == $edit_id) {
                    $editing_prize = $prize;
                    break;
                }
            }
        }

        ?>
        <div class="twork-prizes-container" style="display: grid; grid-template-columns: 1fr 400px; gap: 20px;">
            <!-- Prize List -->
            <div class="twork-prize-list">
                <h2><?php esc_html_e('Prize List', 'twork-spin-wheel'); ?></h2>

                <?php if (empty($prizes)): ?>
                    <div class="notice notice-info">
                        <p><?php esc_html_e('No prizes added yet. Add your first prize using the form on the right.', 'twork-spin-wheel'); ?></p>
                    </div>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 50px;"><?php esc_html_e('Icon', 'twork-spin-wheel'); ?></th>
                                <th><?php esc_html_e('Label', 'twork-spin-wheel'); ?></th>
                                <th><?php esc_html_e('Type', 'twork-spin-wheel'); ?></th>
                                <th><?php esc_html_e('Value', 'twork-spin-wheel'); ?></th>
                                <th><?php esc_html_e('Weight', 'twork-spin-wheel'); ?></th>
                                <th><?php esc_html_e('Actions', 'twork-spin-wheel'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prizes as $prize): ?>
                                <tr>
                                    <td style="font-size: 24px; text-align: center;">
                                        <?php echo esc_html($prize->icon ?: '🎁'); ?>
                                    </td>
                                    <td><strong><?php echo esc_html($prize->prize_name); ?></strong></td>
                                    <td><?php echo esc_html($prize->prize_type); ?></td>
                                    <td><?php echo esc_html($prize->prize_value); ?></td>
                                    <td><?php echo esc_html($prize->probability_weight); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url(add_query_arg(array('page' => 'twork-spin-wheel', 'tab' => 'prizes', 'edit_prize' => $prize->id), admin_url('admin.php'))); ?>" 
                                           class="button button-small">
                                            <?php esc_html_e('Edit', 'twork-spin-wheel'); ?>
                                        </a>
                                        <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('action' => 'twork_spin_wheel_toggle_prize', 'prize_id' => $prize->id), admin_url('admin-post.php')), 'twork_spin_wheel_toggle_prize')); ?>" 
                                           class="button button-small">
                                            <?php echo $prize->is_active ? esc_html__('Deactivate', 'twork-spin-wheel') : esc_html__('Activate', 'twork-spin-wheel'); ?>
                                        </a>
                                        <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('action' => 'twork_spin_wheel_delete_prize', 'prize_id' => $prize->id), admin_url('admin-post.php')), 'twork_spin_wheel_delete_prize')); ?>" 
                                           class="button button-small button-link-delete" 
                                           onclick="return confirm('<?php esc_attr_e('Are you sure?', 'twork-spin-wheel'); ?>');">
                                            <?php esc_html_e('Delete', 'twork-spin-wheel'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Prize Form -->
            <div class="twork-prize-form" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
                <h2><?php echo $editing_prize ? esc_html__('Edit Prize', 'twork-spin-wheel') : esc_html__('Add New Prize', 'twork-spin-wheel'); ?></h2>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('twork_spin_wheel_prize'); ?>
                    <input type="hidden" name="action" value="twork_spin_wheel_save_prize">
                    <?php if ($editing_prize): ?>
                        <input type="hidden" name="prize_id" value="<?php echo esc_attr($editing_prize->id); ?>">
                    <?php endif; ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="prize_label"><?php esc_html_e('Prize Label', 'twork-spin-wheel'); ?> *</label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="prize_label" 
                                       name="prize_label" 
                                       value="<?php echo $editing_prize ? esc_attr($editing_prize->prize_name) : ''; ?>" 
                                       class="regular-text" 
                                       required>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="prize_type"><?php esc_html_e('Prize Type', 'twork-spin-wheel'); ?> *</label>
                            </th>
                            <td>
                                <select id="prize_type" name="prize_type" required>
                                    <option value="points" <?php echo ($editing_prize && $editing_prize->prize_type === 'points') ? 'selected' : ''; ?>>
                                        <?php esc_html_e('Points', 'twork-spin-wheel'); ?>
                                    </option>
                                    <option value="coupon" <?php echo ($editing_prize && $editing_prize->prize_type === 'coupon') ? 'selected' : ''; ?>>
                                        <?php esc_html_e('Coupon', 'twork-spin-wheel'); ?>
                                    </option>
                                    <option value="product" <?php echo ($editing_prize && $editing_prize->prize_type === 'product') ? 'selected' : ''; ?>>
                                        <?php esc_html_e('Product', 'twork-spin-wheel'); ?>
                                    </option>
                                    <option value="message" <?php echo ($editing_prize && $editing_prize->prize_type === 'message') ? 'selected' : ''; ?>>
                                        <?php esc_html_e('Message Only', 'twork-spin-wheel'); ?>
                                    </option>
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="prize_value"><?php esc_html_e('Prize Value', 'twork-spin-wheel'); ?> *</label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="prize_value" 
                                       name="prize_value" 
                                       value="<?php echo $editing_prize ? esc_attr($editing_prize->prize_value) : ''; ?>" 
                                       class="regular-text" 
                                       required>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="prize_probability"><?php esc_html_e('Probability Weight', 'twork-spin-wheel'); ?> *</label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="prize_probability" 
                                       name="prize_probability" 
                                       value="<?php echo $editing_prize ? esc_attr($editing_prize->probability_weight) : '10'; ?>" 
                                       min="1" 
                                       class="small-text" 
                                       required>
                                <p class="description"><?php esc_html_e('Higher weight = higher chance of winning', 'twork-spin-wheel'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="prize_color"><?php esc_html_e('Sector Color', 'twork-spin-wheel'); ?></label>
                            </th>
                            <td>
                                <input type="color" 
                                       id="prize_color" 
                                       name="prize_color" 
                                       value="<?php echo $editing_prize ? esc_attr($editing_prize->sector_color) : '#FF6B6B'; ?>">
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="prize_icon"><?php esc_html_e('Icon/Emoji', 'twork-spin-wheel'); ?></label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="prize_icon" 
                                       name="prize_icon" 
                                       value="<?php echo $editing_prize ? esc_attr($editing_prize->icon) : '🎁'; ?>" 
                                       class="regular-text">
                            </td>
                        </tr>
                    </table>

                    <?php submit_button($editing_prize ? __('Update Prize', 'twork-spin-wheel') : __('Add Prize', 'twork-spin-wheel')); ?>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Render history tab
     */
    private function render_history_tab()
    {
        global $wpdb;
        $history_table = $this->database->get_history_table();

        // Get recent spins
        $spins = $wpdb->get_results(
            "SELECT * FROM {$history_table} ORDER BY created_at DESC LIMIT 100"
        );

        ?>
        <div class="twork-history-container">
            <h2><?php esc_html_e('Spin History', 'twork-spin-wheel'); ?></h2>

            <?php if (empty($spins)): ?>
                <div class="notice notice-info">
                    <p><?php esc_html_e('No spins recorded yet.', 'twork-spin-wheel'); ?></p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('User ID', 'twork-spin-wheel'); ?></th>
                            <th><?php esc_html_e('Prize', 'twork-spin-wheel'); ?></th>
                            <th><?php esc_html_e('Type', 'twork-spin-wheel'); ?></th>
                            <th><?php esc_html_e('Value', 'twork-spin-wheel'); ?></th>
                            <th><?php esc_html_e('Points Spent', 'twork-spin-wheel'); ?></th>
                            <th><?php esc_html_e('Date', 'twork-spin-wheel'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($spins as $spin): ?>
                            <tr>
                                <td><?php echo esc_html($spin->user_id); ?></td>
                                <td><?php echo esc_html($spin->prize_name); ?></td>
                                <td><?php echo esc_html($spin->prize_type); ?></td>
                                <td><?php echo esc_html($spin->prize_value); ?></td>
                                <td><?php echo esc_html($spin->cost_points); ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($spin->created_at))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Handle save settings
     */
    public function handle_save_settings()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'twork-spin-wheel'));
        }

        check_admin_referer('twork_spin_wheel_settings');

        global $wpdb;
        $wheels_table = $this->database->get_wheels_table();

        // Sanitize inputs
        $name = sanitize_text_field($_POST['wheel_title'] ?? 'Lucky Spin Wheel');
        $slug = sanitize_title($name);
        $description = wp_kses_post($_POST['wheel_description'] ?? '');
        $is_active = isset($_POST['wheel_status']) && $_POST['wheel_status'] === 'active' ? 1 : 0;
        $daily_limit = absint($_POST['max_spins_per_day'] ?? 3);
        $cost_points = absint($_POST['points_per_spin'] ?? 100);
        $background_color = sanitize_hex_color($_POST['color_primary'] ?? '#FFFFFF');
        $border_color = sanitize_hex_color($_POST['color_secondary'] ?? '#000000');
        $center_color = sanitize_hex_color($_POST['color_text'] ?? '#FFD700');
        $enable_animation = isset($_POST['show_confetti']) ? 1 : 0;
        $enable_sound = isset($_POST['show_sound']) ? 1 : 0;
        $animation_duration = absint($_POST['animation_duration'] ?? 5);

        // Check if exists
        $existing = $wpdb->get_var("SELECT id FROM {$wheels_table} WHERE is_default = 1 ORDER BY id DESC LIMIT 1");

        if ($existing) {
            $wpdb->update(
                $wheels_table,
                array(
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $description,
                    'is_active' => $is_active,
                    'daily_limit' => $daily_limit,
                    'cost_points' => $cost_points,
                    'background_color' => $background_color,
                    'border_color' => $border_color,
                    'center_color' => $center_color,
                    'enable_animation' => $enable_animation,
                    'enable_sound' => $enable_sound,
                    'animation_duration' => $animation_duration,
                ),
                array('id' => $existing),
                array('%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%d', '%d'),
                array('%d')
            );
        } else {
            $wpdb->insert(
                $wheels_table,
                array(
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $description,
                    'is_active' => $is_active,
                    'is_default' => 1,
                    'daily_limit' => $daily_limit,
                    'cost_points' => $cost_points,
                    'background_color' => $background_color,
                    'border_color' => $border_color,
                    'center_color' => $center_color,
                    'enable_animation' => $enable_animation,
                    'enable_sound' => $enable_sound,
                    'animation_duration' => $animation_duration,
                ),
                array('%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%d', '%d')
            );
        }

        wp_safe_redirect(add_query_arg(array(
            'page' => 'twork-spin-wheel',
            'tab' => 'settings',
            'updated' => '1',
        ), admin_url('admin.php')));
        exit;
    }

    /**
     * Handle save prize
     */
    public function handle_save_prize()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'twork-spin-wheel'));
        }

        check_admin_referer('twork_spin_wheel_prize');

        global $wpdb;
        $wheels_table = $this->database->get_wheels_table();
        $prizes_table = $this->database->get_prizes_table();

        // Get current wheel
        $config = $wpdb->get_row("SELECT * FROM {$wheels_table} WHERE is_default = 1 ORDER BY id DESC LIMIT 1");

        if (!$config) {
            wp_die(__('No spin wheel configuration found.', 'twork-spin-wheel'));
        }

        // Sanitize prize data
        $prize_name = sanitize_text_field($_POST['prize_label'] ?? 'Prize');
        $prize_type = sanitize_text_field($_POST['prize_type'] ?? 'points');
        $prize_value = sanitize_text_field($_POST['prize_value'] ?? '0');
        $probability_weight = absint($_POST['prize_probability'] ?? 10);
        $sector_color = sanitize_hex_color($_POST['prize_color'] ?? '#FF6B6B');
        $text_color = '#FFFFFF';
        $icon = sanitize_text_field($_POST['prize_icon'] ?? '🎁');
        $prize_id = absint($_POST['prize_id'] ?? 0);

        if ($prize_id > 0) {
            $wpdb->update(
                $prizes_table,
                array(
                    'prize_name' => $prize_name,
                    'prize_type' => $prize_type,
                    'prize_value' => $prize_value,
                    'probability_weight' => $probability_weight,
                    'sector_color' => $sector_color,
                    'text_color' => $text_color,
                    'icon' => $icon,
                ),
                array('id' => $prize_id),
                array('%s', '%s', '%s', '%d', '%s', '%s', '%s'),
                array('%d')
            );
        } else {
            $wpdb->insert(
                $prizes_table,
                array(
                    'wheel_id' => $config->id,
                    'prize_name' => $prize_name,
                    'prize_type' => $prize_type,
                    'prize_value' => $prize_value,
                    'probability_weight' => $probability_weight,
                    'sector_color' => $sector_color,
                    'text_color' => $text_color,
                    'icon' => $icon,
                    'is_active' => 1,
                ),
                array('%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%d')
            );
        }

        wp_safe_redirect(add_query_arg(array(
            'page' => 'twork-spin-wheel',
            'tab' => 'prizes',
            'updated' => '1',
        ), admin_url('admin.php')));
        exit;
    }

    /**
     * Handle delete prize
     */
    public function handle_delete_prize()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'twork-spin-wheel'));
        }

        check_admin_referer('twork_spin_wheel_delete_prize');

        $prize_id = absint($_GET['prize_id'] ?? 0);

        if (!$prize_id) {
            wp_die(__('Invalid prize ID.', 'twork-spin-wheel'));
        }

        global $wpdb;
        $prizes_table = $this->database->get_prizes_table();

        $wpdb->delete(
            $prizes_table,
            array('id' => $prize_id),
            array('%d')
        );

        wp_safe_redirect(add_query_arg(array(
            'page' => 'twork-spin-wheel',
            'tab' => 'prizes',
            'deleted' => '1',
        ), admin_url('admin.php')));
        exit;
    }

    /**
     * Handle toggle prize
     */
    public function handle_toggle_prize()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'twork-spin-wheel'));
        }

        check_admin_referer('twork_spin_wheel_toggle_prize');

        $prize_id = absint($_GET['prize_id'] ?? 0);

        if (!$prize_id) {
            wp_die(__('Invalid prize ID.', 'twork-spin-wheel'));
        }

        global $wpdb;
        $prizes_table = $this->database->get_prizes_table();

        $prize = $wpdb->get_row($wpdb->prepare("SELECT is_active FROM {$prizes_table} WHERE id = %d", $prize_id));

        if (!$prize) {
            wp_die(__('Prize not found.', 'twork-spin-wheel'));
        }

        $new_status = $prize->is_active ? 0 : 1;

        $wpdb->update(
            $prizes_table,
            array('is_active' => $new_status),
            array('id' => $prize_id),
            array('%d'),
            array('%d')
        );

        wp_safe_redirect(add_query_arg(array(
            'page' => 'twork-spin-wheel',
            'tab' => 'prizes',
            'toggled' => '1',
        ), admin_url('admin.php')));
        exit;
    }

    /**
     * Handle export
     */
    public function handle_export()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'twork-spin-wheel'));
        }

        check_admin_referer('twork_spin_wheel_export');

        $export_type = sanitize_text_field($_POST['export_type'] ?? 'history');
        $export = new TWork_Spin_Wheel_Export();

        switch ($export_type) {
            case 'history':
                $export->export_history_csv();
                break;
            case 'prizes':
                $json = $export->export_prizes_json();
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename=prizes-' . date('Y-m-d') . '.json');
                echo $json;
                exit;
            case 'settings':
                $json = $export->export_settings_json();
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename=settings-' . date('Y-m-d') . '.json');
                echo $json;
                exit;
        }
    }

    /**
     * Handle import
     */
    public function handle_import()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'twork-spin-wheel'));
        }

        check_admin_referer('twork_spin_wheel_import');

        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            wp_die(__('Error uploading file.', 'twork-spin-wheel'));
        }

        $file_content = file_get_contents($_FILES['import_file']['tmp_name']);
        $export = new TWork_Spin_Wheel_Export();

        global $wpdb;
        $wheels_table = $this->database->get_wheels_table();
        $config = $wpdb->get_row("SELECT id FROM {$wheels_table} WHERE is_default = 1 ORDER BY id DESC LIMIT 1");

        if (!$config) {
            wp_die(__('No wheel configuration found. Please create one first.', 'twork-spin-wheel'));
        }

        $result = $export->import_prizes_json($file_content, $config->id);

        if (is_wp_error($result)) {
            wp_die($result->get_error_message());
        }

        wp_safe_redirect(add_query_arg(array(
            'page' => 'twork-spin-wheel',
            'tab' => 'prizes',
            'imported' => $result['imported'],
        ), admin_url('admin.php')));
        exit;
    }

    /**
     * Handle save advanced settings
     */
    public function handle_save_advanced()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'twork-spin-wheel'));
        }

        check_admin_referer('twork_spin_wheel_advanced_settings');

        update_option('twork_spin_wheel_enable_cache', isset($_POST['enable_cache']) ? 1 : 0);
        update_option('twork_spin_wheel_enable_logging', isset($_POST['enable_logging']) ? 1 : 0);
        update_option('twork_spin_wheel_enable_rate_limiting', isset($_POST['enable_rate_limiting']) ? 1 : 0);
        update_option('twork_spin_wheel_notify_on_spin', isset($_POST['notify_on_spin']) ? 1 : 0);
        update_option('twork_spin_wheel_log_retention_days', absint($_POST['log_retention_days'] ?? 30));

        // Clear cache if caching was disabled
        if (!isset($_POST['enable_cache'])) {
            TWork_Spin_Wheel_Cache::clear_all();
        }

        wp_safe_redirect(add_query_arg(array(
            'page' => 'twork-spin-wheel',
            'tab' => 'advanced',
            'updated' => '1',
        ), admin_url('admin.php')));
        exit;
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook.
     * @return void
     */
    public function enqueue_admin_assets($hook)
    {
        if (strpos($hook, 'twork-spin-wheel') === false) {
            return;
        }

        wp_enqueue_style(
            'twork-spin-wheel-admin',
            TWORK_SPIN_WHEEL_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            TWORK_SPIN_WHEEL_VERSION
        );

        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
            array(),
            '4.4.0',
            true
        );

        wp_enqueue_script(
            'twork-spin-wheel-admin',
            TWORK_SPIN_WHEEL_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'chart-js'),
            TWORK_SPIN_WHEEL_VERSION,
            true
        );

        wp_localize_script(
            'twork-spin-wheel-admin',
            'tworkSpinWheelAdmin',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('twork_spin_wheel_admin'),
            )
        );
    }

    /**
     * Render analytics tab
     */
    private function render_analytics_tab()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $analytics = new TWork_Spin_Wheel_Analytics();
        $wheel_id = isset($_GET['wheel_id']) ? absint($_GET['wheel_id']) : 0;
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';

        $stats = $analytics->get_statistics($wheel_id, $date_from, $date_to);

        ?>
        <div class="twork-analytics-container">
            <h2><?php esc_html_e('Analytics Dashboard', 'twork-spin-wheel'); ?></h2>

            <form method="get" action="" style="margin: 20px 0;">
                <input type="hidden" name="page" value="twork-spin-wheel">
                <input type="hidden" name="tab" value="analytics">
                
                <table class="form-table">
                    <tr>
                        <th><label for="date_from"><?php esc_html_e('Date From', 'twork-spin-wheel'); ?></label></th>
                        <td><input type="date" id="date_from" name="date_from" value="<?php echo esc_attr($date_from); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="date_to"><?php esc_html_e('Date To', 'twork-spin-wheel'); ?></label></th>
                        <td><input type="date" id="date_to" name="date_to" value="<?php echo esc_attr($date_to); ?>"></td>
                    </tr>
                </table>
                <?php submit_button(__('Filter', 'twork-spin-wheel'), 'secondary', 'submit', false); ?>
            </form>

            <div class="twork-stats-grid">
                <div class="stat-card" data-stat-type="total_spins">
                    <h3><?php esc_html_e('Total Spins', 'twork-spin-wheel'); ?></h3>
                    <p><?php echo esc_html(number_format($stats['total_spins'])); ?></p>
                </div>
                <div class="stat-card" data-stat-type="unique_users">
                    <h3><?php esc_html_e('Unique Users', 'twork-spin-wheel'); ?></h3>
                    <p><?php echo esc_html(number_format($stats['unique_users'])); ?></p>
                </div>
                <div class="stat-card" data-stat-type="points_spent">
                    <h3><?php esc_html_e('Points Spent', 'twork-spin-wheel'); ?></h3>
                    <p style="font-size: 32px; font-weight: bold; margin: 0; color: #FF9800;"><?php echo esc_html(number_format($stats['points_spent'])); ?></p>
                </div>
            </div>

            <?php if (!empty($stats['prize_distribution'])): ?>
                <h3><?php esc_html_e('Prize Distribution', 'twork-spin-wheel'); ?></h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Prize Type', 'twork-spin-wheel'); ?></th>
                            <th><?php esc_html_e('Prize Name', 'twork-spin-wheel'); ?></th>
                            <th><?php esc_html_e('Count', 'twork-spin-wheel'); ?></th>
                            <th><?php esc_html_e('Total Points', 'twork-spin-wheel'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['prize_distribution'] as $prize): ?>
                            <tr>
                                <td><?php echo esc_html($prize->prize_type); ?></td>
                                <td><?php echo esc_html($prize->prize_name); ?></td>
                                <td><?php echo esc_html($prize->count); ?></td>
                                <td><?php echo esc_html(number_format($prize->total_points)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render export/import tab
     */
    private function render_export_tab()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $export = new TWork_Spin_Wheel_Export();

        ?>
        <div class="twork-export-container">
            <h2><?php esc_html_e('Export / Import Data', 'twork-spin-wheel'); ?></h2>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">
                <!-- Export Section -->
                <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
                    <h3><?php esc_html_e('Export', 'twork-spin-wheel'); ?></h3>
                    
                    <p><?php esc_html_e('Export spin history, prizes, or settings to JSON/CSV format.', 'twork-spin-wheel'); ?></p>

                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <?php wp_nonce_field('twork_spin_wheel_export'); ?>
                        <input type="hidden" name="action" value="twork_spin_wheel_export">
                        
                        <table class="form-table">
                            <tr>
                                <th><label for="export_type"><?php esc_html_e('Export Type', 'twork-spin-wheel'); ?></label></th>
                                <td>
                                    <select id="export_type" name="export_type" required>
                                        <option value="history"><?php esc_html_e('Spin History (CSV)', 'twork-spin-wheel'); ?></option>
                                        <option value="prizes"><?php esc_html_e('Prizes (JSON)', 'twork-spin-wheel'); ?></option>
                                        <option value="settings"><?php esc_html_e('Settings (JSON)', 'twork-spin-wheel'); ?></option>
                                    </select>
                                </td>
                            </tr>
                        </table>

                        <?php submit_button(__('Export', 'twork-spin-wheel')); ?>
                    </form>
                </div>

                <!-- Import Section -->
                <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
                    <h3><?php esc_html_e('Import', 'twork-spin-wheel'); ?></h3>
                    
                    <p><?php esc_html_e('Import prizes from JSON file.', 'twork-spin-wheel'); ?></p>

                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                        <?php wp_nonce_field('twork_spin_wheel_import'); ?>
                        <input type="hidden" name="action" value="twork_spin_wheel_import">
                        
                        <table class="form-table">
                            <tr>
                                <th><label for="import_file"><?php esc_html_e('JSON File', 'twork-spin-wheel'); ?></label></th>
                                <td><input type="file" id="import_file" name="import_file" accept=".json" required></td>
                            </tr>
                        </table>

                        <?php submit_button(__('Import', 'twork-spin-wheel')); ?>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render advanced settings tab
     */
    private function render_advanced_tab()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        ?>
        <div class="twork-advanced-container">
            <h2><?php esc_html_e('Advanced Settings', 'twork-spin-wheel'); ?></h2>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('twork_spin_wheel_advanced_settings'); ?>
                <input type="hidden" name="action" value="twork_spin_wheel_save_advanced">

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="enable_cache"><?php esc_html_e('Enable Caching', 'twork-spin-wheel'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="enable_cache" name="enable_cache" value="1" 
                                   <?php checked(get_option('twork_spin_wheel_enable_cache', true), 1); ?>>
                            <p class="description"><?php esc_html_e('Enable caching for improved performance.', 'twork-spin-wheel'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="enable_logging"><?php esc_html_e('Enable Logging', 'twork-spin-wheel'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="enable_logging" name="enable_logging" value="1" 
                                   <?php checked(get_option('twork_spin_wheel_enable_logging', true), 1); ?>>
                            <p class="description"><?php esc_html_e('Log events for debugging purposes.', 'twork-spin-wheel'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="enable_rate_limiting"><?php esc_html_e('Enable Rate Limiting', 'twork-spin-wheel'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="enable_rate_limiting" name="enable_rate_limiting" value="1" 
                                   <?php checked(get_option('twork_spin_wheel_enable_rate_limiting', true), 1); ?>>
                            <p class="description"><?php esc_html_e('Limit API requests to prevent abuse.', 'twork-spin-wheel'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="notify_on_spin"><?php esc_html_e('Email Notifications', 'twork-spin-wheel'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="notify_on_spin" name="notify_on_spin" value="1" 
                                   <?php checked(get_option('twork_spin_wheel_notify_on_spin', true), 1); ?>>
                            <p class="description"><?php esc_html_e('Send email notifications when users win prizes.', 'twork-spin-wheel'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="log_retention_days"><?php esc_html_e('Log Retention (Days)', 'twork-spin-wheel'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="log_retention_days" name="log_retention_days" 
                                   value="<?php echo esc_attr(get_option('twork_spin_wheel_log_retention_days', 30)); ?>" 
                                   min="1" max="365" class="small-text">
                            <p class="description"><?php esc_html_e('How many days to keep logs before auto-deletion.', 'twork-spin-wheel'); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(__('Save Advanced Settings', 'twork-spin-wheel')); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Handle apply template
     */
    public function handle_apply_template()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'twork-spin-wheel'));
        }

        check_admin_referer('twork_spin_wheel_template');

        $template_id = isset($_POST['template_id']) ? sanitize_text_field($_POST['template_id']) : '';
        $wheel_id = isset($_POST['wheel_id']) ? absint($_POST['wheel_id']) : 0;

        if (empty($template_id) || $wheel_id <= 0) {
            wp_redirect(add_query_arg(array('page' => 'twork-spin-wheel', 'tab' => 'templates', 'error' => 'invalid'), admin_url('admin.php')));
            exit;
        }

        $prize_id = TWork_Spin_Wheel_Prize_Templates::apply_template($template_id, $wheel_id);

        if ($prize_id) {
            wp_redirect(add_query_arg(array('page' => 'twork-spin-wheel', 'tab' => 'templates', 'success' => '1'), admin_url('admin.php')));
        } else {
            wp_redirect(add_query_arg(array('page' => 'twork-spin-wheel', 'tab' => 'templates', 'error' => 'failed'), admin_url('admin.php')));
        }
        exit;
    }

    /**
     * Handle add webhook
     */
    public function handle_add_webhook()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'twork-spin-wheel'));
        }

        check_admin_referer('twork_spin_wheel_webhook');

        $webhook_url = isset($_POST['webhook_url']) ? esc_url_raw($_POST['webhook_url']) : '';
        $event_type = isset($_POST['webhook_event_type']) ? sanitize_text_field($_POST['webhook_event_type']) : '';
        $active = isset($_POST['webhook_active']) ? 1 : 0;

        if (empty($webhook_url)) {
            wp_redirect(add_query_arg(array('page' => 'twork-spin-wheel', 'tab' => 'webhooks', 'error' => 'invalid'), admin_url('admin.php')));
            exit;
        }

        $webhooks = new TWork_Spin_Wheel_Webhooks();
        $webhook_id = $webhooks->add_webhook(array(
            'url' => $webhook_url,
            'event_type' => $event_type,
            'active' => $active,
        ));

        if ($webhook_id) {
            wp_redirect(add_query_arg(array('page' => 'twork-spin-wheel', 'tab' => 'webhooks', 'success' => '1'), admin_url('admin.php')));
        } else {
            wp_redirect(add_query_arg(array('page' => 'twork-spin-wheel', 'tab' => 'webhooks', 'error' => 'failed'), admin_url('admin.php')));
        }
        exit;
    }

    /**
     * Handle delete webhook
     */
    public function handle_delete_webhook()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'twork-spin-wheel'));
        }

        $webhook_id = isset($_GET['webhook_id']) ? sanitize_text_field($_GET['webhook_id']) : '';

        if (empty($webhook_id)) {
            wp_redirect(add_query_arg(array('page' => 'twork-spin-wheel', 'tab' => 'webhooks', 'error' => 'invalid'), admin_url('admin.php')));
            exit;
        }

        check_admin_referer('delete_webhook_' . $webhook_id);

        $webhooks = new TWork_Spin_Wheel_Webhooks();
        $result = $webhooks->delete_webhook($webhook_id);

        if ($result) {
            wp_redirect(add_query_arg(array('page' => 'twork-spin-wheel', 'tab' => 'webhooks', 'success' => '1'), admin_url('admin.php')));
        } else {
            wp_redirect(add_query_arg(array('page' => 'twork-spin-wheel', 'tab' => 'webhooks', 'error' => 'failed'), admin_url('admin.php')));
        }
        exit;
    }

    /**
     * Handle create backup
     */
    public function handle_create_backup()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'twork-spin-wheel'));
        }

        check_admin_referer('twork_spin_wheel_backup');

        $backup = new TWork_Spin_Wheel_Backup();
        $backup_data = $backup->create_backup();

        if (is_wp_error($backup_data)) {
            wp_redirect(add_query_arg(array('page' => 'twork-spin-wheel', 'tab' => 'backup', 'error' => 'failed'), admin_url('admin.php')));
            exit;
        }

        $backup->export_backup_file($backup_data);
    }

    /**
     * Handle restore backup
     */
    public function handle_restore_backup()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'twork-spin-wheel'));
        }

        check_admin_referer('twork_spin_wheel_restore');

        if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
            wp_redirect(add_query_arg(array('page' => 'twork-spin-wheel', 'tab' => 'backup', 'error' => 'upload'), admin_url('admin.php')));
            exit;
        }

        $file_content = file_get_contents($_FILES['backup_file']['tmp_name']); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $backup_data = json_decode($file_content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_redirect(add_query_arg(array('page' => 'twork-spin-wheel', 'tab' => 'backup', 'error' => 'invalid'), admin_url('admin.php')));
            exit;
        }

        $overwrite = isset($_POST['overwrite_existing']) && $_POST['overwrite_existing'] === '1';

        $backup = new TWork_Spin_Wheel_Backup();
        $result = $backup->restore_backup($backup_data, $overwrite);

        if (is_wp_error($result)) {
            wp_redirect(add_query_arg(array('page' => 'twork-spin-wheel', 'tab' => 'backup', 'error' => 'restore_failed'), admin_url('admin.php')));
        } else {
            wp_redirect(add_query_arg(array('page' => 'twork-spin-wheel', 'tab' => 'backup', 'success' => '1'), admin_url('admin.php')));
        }
        exit;
    }

    /**
     * Render templates tab
     */
    private function render_templates_tab()
    {
        global $wpdb;
        $database = new TWork_Spin_Wheel_Database();
        $wheels_table = $database->get_wheels_table();
        $wheels = $wpdb->get_results("SELECT id, name FROM {$wheels_table} ORDER BY name ASC");

        $templates = TWork_Spin_Wheel_Prize_Templates::get_templates();

        ?>
        <div class="twork-templates-container">
            <h2><?php esc_html_e('Prize Templates', 'twork-spin-wheel'); ?></h2>
            <p class="description"><?php esc_html_e('Quickly add pre-configured prizes to your spin wheel.', 'twork-spin-wheel'); ?></p>

            <?php if (empty($wheels)): ?>
                <div class="notice notice-warning">
                    <p><?php esc_html_e('Please create a wheel first before using templates.', 'twork-spin-wheel'); ?></p>
                </div>
            <?php else: ?>
                <div class="twork-templates-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
                    <?php foreach ($templates as $template_id => $template): ?>
                        <div class="twork-template-card" style="border: 1px solid #ddd; padding: 15px; border-radius: 4px;">
                            <h3><?php echo esc_html($template['name']); ?></h3>
                            <p><strong><?php esc_html_e('Type:', 'twork-spin-wheel'); ?></strong> <?php echo esc_html($template['prize_type']); ?></p>
                            <p><strong><?php esc_html_e('Value:', 'twork-spin-wheel'); ?></strong> <?php echo esc_html($template['prize_value']); ?></p>
                            <p><strong><?php esc_html_e('Weight:', 'twork-spin-wheel'); ?></strong> <?php echo esc_html($template['probability_weight']); ?></p>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top: 10px;">
                                <?php wp_nonce_field('twork_spin_wheel_template'); ?>
                                <input type="hidden" name="action" value="twork_spin_wheel_apply_template">
                                <input type="hidden" name="template_id" value="<?php echo esc_attr($template_id); ?>">
                                <select name="wheel_id" required style="width: 100%; margin-bottom: 10px;">
                                    <option value=""><?php esc_html_e('Select Wheel', 'twork-spin-wheel'); ?></option>
                                    <?php foreach ($wheels as $wheel): ?>
                                        <option value="<?php echo esc_attr($wheel->id); ?>"><?php echo esc_html($wheel->name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php submit_button(__('Apply Template', 'twork-spin-wheel'), 'secondary', 'apply_template', false, array('style' => 'width: 100%;')); ?>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render webhooks tab
     */
    private function render_webhooks_tab()
    {
        $webhooks = get_option('twork_spin_wheel_webhooks', array());
        if (!is_array($webhooks)) {
            $webhooks = array();
        }

        ?>
        <div class="twork-webhooks-container">
            <h2><?php esc_html_e('Webhooks', 'twork-spin-wheel'); ?></h2>
            <p class="description"><?php esc_html_e('Configure webhooks to send spin results to external services.', 'twork-spin-wheel'); ?></p>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-bottom: 30px;">
                <?php wp_nonce_field('twork_spin_wheel_webhook'); ?>
                <input type="hidden" name="action" value="twork_spin_wheel_add_webhook">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="webhook_url"><?php esc_html_e('Webhook URL', 'twork-spin-wheel'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="webhook_url" name="webhook_url" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="webhook_event_type"><?php esc_html_e('Event Type', 'twork-spin-wheel'); ?></label>
                        </th>
                        <td>
                            <select id="webhook_event_type" name="webhook_event_type">
                                <option value=""><?php esc_html_e('All Events', 'twork-spin-wheel'); ?></option>
                                <option value="win"><?php esc_html_e('Wins Only', 'twork-spin-wheel'); ?></option>
                                <option value="lose"><?php esc_html_e('Losses Only', 'twork-spin-wheel'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="webhook_active"><?php esc_html_e('Active', 'twork-spin-wheel'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="webhook_active" name="webhook_active" value="1" checked>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Add Webhook', 'twork-spin-wheel')); ?>
            </form>

            <h3><?php esc_html_e('Active Webhooks', 'twork-spin-wheel'); ?></h3>
            <?php if (empty($webhooks)): ?>
                <p><?php esc_html_e('No webhooks configured.', 'twork-spin-wheel'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('URL', 'twork-spin-wheel'); ?></th>
                            <th><?php esc_html_e('Event Type', 'twork-spin-wheel'); ?></th>
                            <th><?php esc_html_e('Status', 'twork-spin-wheel'); ?></th>
                            <th><?php esc_html_e('Actions', 'twork-spin-wheel'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($webhooks as $webhook): ?>
                            <tr>
                                <td><?php echo esc_html($webhook['url'] ?? ''); ?></td>
                                <td><?php echo esc_html($webhook['event_type'] ?? __('All', 'twork-spin-wheel')); ?></td>
                                <td><?php echo isset($webhook['active']) && $webhook['active'] ? __('Active', 'twork-spin-wheel') : __('Inactive', 'twork-spin-wheel'); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=twork_spin_wheel_delete_webhook&webhook_id=' . ($webhook['id'] ?? '')), 'delete_webhook_' . ($webhook['id'] ?? ''))); ?>" 
                                       class="button button-small" 
                                       onclick="return confirm('<?php esc_attr_e('Are you sure?', 'twork-spin-wheel'); ?>');">
                                        <?php esc_html_e('Delete', 'twork-spin-wheel'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render health check tab
     */
    private function render_health_tab()
    {
        $health_check = new TWork_Spin_Wheel_Health_Check();
        $results = $health_check->run_health_check();
        $system_info = $health_check->get_system_info();

        ?>
        <div class="twork-health-container">
            <h2><?php esc_html_e('System Health Check', 'twork-spin-wheel'); ?></h2>

            <div class="twork-health-status" style="margin: 20px 0;">
                <h3><?php esc_html_e('Overall Status:', 'twork-spin-wheel'); ?>
                    <span style="color: <?php echo $results['overall'] === 'good' ? '#00a32a' : ($results['overall'] === 'warning' ? '#dba617' : '#d63638'); ?>;">
                        <?php echo esc_html(ucfirst($results['overall'])); ?>
                    </span>
                </h3>
            </div>

            <div class="twork-health-checks" style="display: grid; gap: 15px; margin-top: 20px;">
                <?php foreach ($results as $key => $result): ?>
                    <?php if ($key === 'overall') continue; ?>
                    <div class="twork-health-item" style="border: 1px solid #ddd; padding: 15px; border-radius: 4px;">
                        <h4><?php echo esc_html(ucfirst($key)); ?></h4>
                        <p style="color: <?php echo $result['status'] === 'good' ? '#00a32a' : ($result['status'] === 'warning' ? '#dba617' : '#d63638'); ?>;">
                            <?php echo esc_html($result['message']); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>

            <h3 style="margin-top: 30px;"><?php esc_html_e('System Information', 'twork-spin-wheel'); ?></h3>
            <table class="form-table">
                <?php foreach ($system_info as $key => $value): ?>
                    <tr>
                        <th scope="row"><?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?></th>
                        <td><?php echo esc_html($value); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php
    }

    /**
     * Render backup/restore tab
     */
    private function render_backup_tab()
    {
        $backup = new TWork_Spin_Wheel_Backup();

        ?>
        <div class="twork-backup-container">
            <h2><?php esc_html_e('Backup & Restore', 'twork-spin-wheel'); ?></h2>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 20px;">
                <div>
                    <h3><?php esc_html_e('Create Backup', 'twork-spin-wheel'); ?></h3>
                    <p class="description"><?php esc_html_e('Export all spin wheel data to a JSON file.', 'twork-spin-wheel'); ?></p>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <?php wp_nonce_field('twork_spin_wheel_backup'); ?>
                        <input type="hidden" name="action" value="twork_spin_wheel_create_backup">
                        <?php submit_button(__('Create Backup', 'twork-spin-wheel'), 'primary'); ?>
                    </form>
                </div>

                <div>
                    <h3><?php esc_html_e('Restore Backup', 'twork-spin-wheel'); ?></h3>
                    <p class="description"><?php esc_html_e('Import data from a backup file.', 'twork-spin-wheel'); ?></p>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                        <?php wp_nonce_field('twork_spin_wheel_restore'); ?>
                        <input type="hidden" name="action" value="twork_spin_wheel_restore_backup">
                        <input type="file" name="backup_file" accept=".json" required style="margin-bottom: 10px;">
                        <label>
                            <input type="checkbox" name="overwrite_existing" value="1">
                            <?php esc_html_e('Overwrite existing data', 'twork-spin-wheel'); ?>
                        </label>
                        <br><br>
                        <?php submit_button(__('Restore Backup', 'twork-spin-wheel'), 'secondary'); ?>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
}
