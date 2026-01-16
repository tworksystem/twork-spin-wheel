<?php
/**
 * Shortcodes Class
 * 
 * Handles shortcode functionality for displaying spin wheel
 * 
 * @package TWork_Spin_Wheel
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class TWork_Spin_Wheel_Shortcodes
 */
class TWork_Spin_Wheel_Shortcodes
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
        $this->register_shortcodes();
    }

    /**
     * Register all shortcodes
     *
     * @return void
     */
    private function register_shortcodes()
    {
        add_shortcode('spin_wheel', array($this, 'render_spin_wheel'));
        add_shortcode('spin_wheel_stats', array($this, 'render_stats'));
        add_shortcode('spin_wheel_history', array($this, 'render_history'));
    }

    /**
     * Render spin wheel shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function render_spin_wheel($atts)
    {
        $atts = shortcode_atts(
            array(
                'wheel_id' => 0,
                'width' => '320',
                'height' => '320',
            ),
            $atts,
            'spin_wheel'
        );

        $wheel_id = absint($atts['wheel_id']);
        $width = absint($atts['width']);
        $height = absint($atts['height']);

        // Get current user ID
        $user_id = get_current_user_id();

        if (!$user_id) {
            return '<p>' . esc_html__('Please login to use the spin wheel.', 'twork-spin-wheel') . '</p>';
        }

        // Get wheel config
        global $wpdb;
        $wheels_table = $this->database->get_wheels_table();

        $where = $wheel_id > 0 
            ? $wpdb->prepare('WHERE id = %d AND is_active = 1', $wheel_id)
            : 'WHERE is_active = 1 AND is_default = 1';

        $wheel = $wpdb->get_row("SELECT * FROM {$wheels_table} {$where} ORDER BY id DESC LIMIT 1");

        if (!$wheel) {
            return '<p>' . esc_html__('No active spin wheel found.', 'twork-spin-wheel') . '</p>';
        }

        // Enqueue scripts and styles
        $this->enqueue_frontend_assets();

        // Generate unique ID for this instance
        $instance_id = 'spin-wheel-' . uniqid();

        ob_start();
        ?>
        <div id="<?php echo esc_attr($instance_id); ?>" class="twork-spin-wheel-container" 
             data-user-id="<?php echo esc_attr($user_id); ?>"
             data-wheel-id="<?php echo esc_attr($wheel->id); ?>"
             style="max-width: <?php echo esc_attr($width); ?>px; margin: 0 auto;">
            <div class="spin-wheel-loading">
                <p><?php esc_html_e('Loading spin wheel...', 'twork-spin-wheel'); ?></p>
            </div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            // Initialize spin wheel here
            // This would connect to your mobile app's API or use AJAX
            console.log('Spin wheel initialized: <?php echo esc_js($instance_id); ?>');
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Render statistics shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function render_stats($atts)
    {
        $atts = shortcode_atts(
            array(
                'user_id' => 0,
                'show_total_spins' => 'yes',
                'show_points_spent' => 'yes',
                'show_prizes_won' => 'yes',
            ),
            $atts,
            'spin_wheel_stats'
        );

        $user_id = absint($atts['user_id']);
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return '<p>' . esc_html__('Please login to view statistics.', 'twork-spin-wheel') . '</p>';
        }

        $analytics = new TWork_Spin_Wheel_Analytics();
        $stats = $analytics->get_user_statistics($user_id);

        if (!$stats || !isset($stats['overall'])) {
            return '<p>' . esc_html__('No statistics available.', 'twork-spin-wheel') . '</p>';
        }

        ob_start();
        ?>
        <div class="twork-spin-wheel-stats">
            <?php if ($atts['show_total_spins'] === 'yes'): ?>
                <div class="stat-item">
                    <strong><?php esc_html_e('Total Spins:', 'twork-spin-wheel'); ?></strong>
                    <?php echo esc_html($stats['overall']->total_spins); ?>
                </div>
            <?php endif; ?>

            <?php if ($atts['show_points_spent'] === 'yes'): ?>
                <div class="stat-item">
                    <strong><?php esc_html_e('Points Spent:', 'twork-spin-wheel'); ?></strong>
                    <?php echo esc_html($stats['overall']->total_points_spent); ?>
                </div>
            <?php endif; ?>

            <?php if ($atts['show_prizes_won'] === 'yes'): ?>
                <div class="stat-item">
                    <strong><?php esc_html_e('Unique Prizes Won:', 'twork-spin-wheel'); ?></strong>
                    <?php echo esc_html($stats['overall']->unique_prizes_won); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render history shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function render_history($atts)
    {
        $atts = shortcode_atts(
            array(
                'user_id' => 0,
                'limit' => 10,
            ),
            $atts,
            'spin_wheel_history'
        );

        $user_id = absint($atts['user_id']);
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return '<p>' . esc_html__('Please login to view history.', 'twork-spin-wheel') . '</p>';
        }

        $history = TWork_Spin_Wheel_Helpers::get_user_spin_history($user_id, absint($atts['limit']));

        if (empty($history)) {
            return '<p>' . esc_html__('No spin history available.', 'twork-spin-wheel') . '</p>';
        }

        ob_start();
        ?>
        <div class="twork-spin-wheel-history">
            <ul>
                <?php foreach ($history as $spin): ?>
                    <li>
                        <strong><?php echo esc_html($spin['prize_label']); ?></strong>
                        <span class="spin-date"><?php echo esc_html($spin['spin_date_formatted']); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Enqueue frontend assets
     *
     * @return void
     */
    private function enqueue_frontend_assets()
    {
        wp_enqueue_style(
            'twork-spin-wheel-frontend',
            TWORK_SPIN_WHEEL_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            TWORK_SPIN_WHEEL_VERSION
        );

        wp_enqueue_script(
            'twork-spin-wheel-frontend',
            TWORK_SPIN_WHEEL_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            TWORK_SPIN_WHEEL_VERSION,
            true
        );

        wp_localize_script(
            'twork-spin-wheel-frontend',
            'tworkSpinWheel',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('twork_spin_wheel_frontend'),
                'apiUrl' => rest_url('twork/v1/spin-wheel/'),
            )
        );
    }
}

