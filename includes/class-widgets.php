<?php
/**
 * Widgets Class
 * 
 * Handles WordPress dashboard widgets and sidebar widgets
 * 
 * @package TWork_Spin_Wheel
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class TWork_Spin_Wheel_Widgets
 */
class TWork_Spin_Wheel_Widgets
{
    /**
     * Database instance
     *
     * @var TWork_Spin_Wheel_Database
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
     *
     * @return void
     */
    private function init_hooks()
    {
        // Dashboard widgets
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widgets'));

        // Sidebar widgets
        add_action('widgets_init', array($this, 'register_sidebar_widgets'));
    }

    /**
     * Add dashboard widgets
     *
     * @return void
     */
    public function add_dashboard_widgets()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        wp_add_dashboard_widget(
            'twork_spin_wheel_stats',
            __('Spin Wheel Statistics', 'twork-spin-wheel'),
            array($this, 'render_dashboard_stats_widget')
        );

        wp_add_dashboard_widget(
            'twork_spin_wheel_recent_spins',
            __('Recent Spins', 'twork-spin-wheel'),
            array($this, 'render_recent_spins_widget')
        );
    }

    /**
     * Render dashboard stats widget
     *
     * @return void
     */
    public function render_dashboard_stats_widget()
    {
        $analytics = new TWork_Spin_Wheel_Analytics();
        $stats = $analytics->get_statistics();

        ?>
        <div class="twork-dashboard-stats">
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 15px;">
                <div style="text-align: center; padding: 15px; background: #f0f0f1; border-radius: 4px;">
                    <div style="font-size: 24px; font-weight: bold; color: #2271b1;">
                        <?php echo esc_html(number_format($stats['total_spins'])); ?>
                    </div>
                    <div style="font-size: 12px; color: #646970; margin-top: 5px;">
                        <?php esc_html_e('Total Spins', 'twork-spin-wheel'); ?>
                    </div>
                </div>
                <div style="text-align: center; padding: 15px; background: #f0f0f1; border-radius: 4px;">
                    <div style="font-size: 24px; font-weight: bold; color: #00a32a;">
                        <?php echo esc_html(number_format($stats['unique_users'])); ?>
                    </div>
                    <div style="font-size: 12px; color: #646970; margin-top: 5px;">
                        <?php esc_html_e('Unique Users', 'twork-spin-wheel'); ?>
                    </div>
                </div>
                <div style="text-align: center; padding: 15px; background: #f0f0f1; border-radius: 4px;">
                    <div style="font-size: 24px; font-weight: bold; color: #d63638;">
                        <?php echo esc_html(number_format($stats['points_spent'])); ?>
                    </div>
                    <div style="font-size: 12px; color: #646970; margin-top: 5px;">
                        <?php esc_html_e('Points Spent', 'twork-spin-wheel'); ?>
                    </div>
                </div>
            </div>
            <p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=twork-spin-wheel&tab=analytics')); ?>" class="button">
                    <?php esc_html_e('View Full Analytics', 'twork-spin-wheel'); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Render recent spins widget
     *
     * @return void
     */
    public function render_recent_spins_widget()
    {
        global $wpdb;
        $history_table = $this->database->get_history_table();

        $recent_spins = $wpdb->get_results(
            "SELECT h.*, u.display_name 
             FROM {$history_table} h
             LEFT JOIN {$wpdb->users} u ON h.user_id = u.ID
             ORDER BY h.created_at DESC 
             LIMIT 5"
        );

        if (empty($recent_spins)) {
            echo '<p>' . esc_html__('No spins yet.', 'twork-spin-wheel') . '</p>';
            return;
        }

        ?>
        <ul style="margin: 0; padding: 0; list-style: none;">
            <?php foreach ($recent_spins as $spin): ?>
                <li style="padding: 10px 0; border-bottom: 1px solid #f0f0f1;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong><?php echo esc_html($spin->prize_name ?: __('Unknown Prize', 'twork-spin-wheel')); ?></strong>
                            <br>
                            <small style="color: #646970;">
                                <?php echo esc_html($spin->display_name ?: 'User #' . $spin->user_id); ?>
                                - <?php echo esc_html(human_time_diff(strtotime($spin->created_at), current_time('timestamp')) . ' ago'); ?>
                            </small>
                        </div>
                        <span style="background: #2271b1; color: #fff; padding: 2px 8px; border-radius: 3px; font-size: 11px;">
                            <?php echo esc_html($spin->cost_points); ?> pts
                        </span>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        <p style="margin-top: 10px;">
            <a href="<?php echo esc_url(admin_url('admin.php?page=twork-spin-wheel&tab=history')); ?>">
                <?php esc_html_e('View All Spins', 'twork-spin-wheel'); ?> →
            </a>
        </p>
        <?php
    }

    /**
     * Register sidebar widgets
     *
     * @return void
     */
    public function register_sidebar_widgets()
    {
        register_widget('TWork_Spin_Wheel_Sidebar_Widget');
    }
}

/**
 * Sidebar Widget Class
 */
class TWork_Spin_Wheel_Sidebar_Widget extends WP_Widget
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct(
            'twork_spin_wheel_widget',
            __('Spin Wheel', 'twork-spin-wheel'),
            array(
                'description' => __('Display spin wheel in sidebar', 'twork-spin-wheel'),
            )
        );
    }

    /**
     * Widget output
     *
     * @param array $args Widget arguments.
     * @param array $instance Widget instance.
     * @return void
     */
    public function widget($args, $instance)
    {
        echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        $title = !empty($instance['title']) ? $instance['title'] : __('Spin Wheel', 'twork-spin-wheel');
        echo $args['before_title'] . esc_html($title) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        $user_id = get_current_user_id();
        if (!$user_id) {
            echo '<p>' . esc_html__('Please login to use the spin wheel.', 'twork-spin-wheel') . '</p>';
            echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            return;
        }

        // Render spin wheel shortcode
        $shortcodes = new TWork_Spin_Wheel_Shortcodes();
        echo do_shortcode('[spin_wheel]'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * Widget form
     *
     * @param array $instance Widget instance.
     * @return void
     */
    public function form($instance)
    {
        $title = !empty($instance['title']) ? $instance['title'] : __('Spin Wheel', 'twork-spin-wheel');
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                <?php esc_html_e('Title:', 'twork-spin-wheel'); ?>
            </label>
            <input class="widefat" 
                   id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                   type="text" 
                   value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }

    /**
     * Update widget
     *
     * @param array $new_instance New instance.
     * @param array $old_instance Old instance.
     * @return array Updated instance.
     */
    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['title'] = !empty($new_instance['title']) ? sanitize_text_field($new_instance['title']) : '';

        return $instance;
    }
}

