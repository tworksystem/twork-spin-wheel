<?php
/**
 * Conversion Tracking Class
 * 
 * Tracks conversions and user behavior
 * 
 * @package TWork_Spin_Wheel
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class TWork_Spin_Wheel_Conversion_Tracking
 */
class TWork_Spin_Wheel_Conversion_Tracking
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
        add_action('twork_spin_wheel_after_spin', array($this, 'track_spin'), 10, 2);
        add_action('twork_spin_wheel_prize_claimed', array($this, 'track_claim'), 10, 2);
    }

    /**
     * Track spin event
     *
     * @param int $user_id User ID.
     * @param array $spin_result Spin result.
     * @return void
     */
    public function track_spin($user_id, $spin_result)
    {
        $this->log_event('spin', $user_id, $spin_result);
    }

    /**
     * Track prize claim
     *
     * @param int $user_id User ID.
     * @param array $prize_data Prize data.
     * @return void
     */
    public function track_claim($user_id, $prize_data)
    {
        $this->log_event('claim', $user_id, $prize_data);
    }

    /**
     * Log conversion event
     *
     * @param string $event_type Event type.
     * @param int $user_id User ID.
     * @param array $data Event data.
     * @return void
     */
    private function log_event($event_type, $user_id, $data)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'twork_spin_wheel_conversions';

        // Create table if needed
        $this->create_table();

        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'event_type' => sanitize_text_field($event_type),
                'event_data' => wp_json_encode($data),
                'ip_address' => TWork_Spin_Wheel_Security::get_client_ip(),
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '',
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
    }

    /**
     * Get conversion rate
     *
     * @param string $event_type Event type.
     * @param string $date_from Start date.
     * @param string $date_to End date.
     * @return float Conversion rate.
     */
    public function get_conversion_rate($event_type, $date_from = '', $date_to = '')
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'twork_spin_wheel_conversions';

        $where = array('1=1');
        $where_values = array();

        if (!empty($event_type)) {
            $where[] = 'event_type = %s';
            $where_values[] = $event_type;
        }

        if (!empty($date_from)) {
            $where[] = 'created_at >= %s';
            $where_values[] = $date_from . ' 00:00:00';
        }

        if (!empty($date_to)) {
            $where[] = 'created_at <= %s';
            $where_values[] = $date_to . ' 23:59:59';
        }

        $where_sql = implode(' AND ', $where);

        $total = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT user_id) FROM {$table_name} WHERE {$where_sql}",
                $where_values
            )
        );

        // Get unique users who spun
        $spins_table = $this->database->get_history_table();
        $spins_where = array('1=1');
        $spins_where_values = array();

        if (!empty($date_from)) {
            $spins_where[] = 'created_at >= %s';
            $spins_where_values[] = $date_from . ' 00:00:00';
        }

        if (!empty($date_to)) {
            $spins_where[] = 'created_at <= %s';
            $spins_where_values[] = $date_to . ' 23:59:59';
        }

        $spins_where_sql = implode(' AND ', $spins_where);
        $total_users = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT user_id) FROM {$spins_table} WHERE {$spins_where_sql}",
                $spins_where_values
            )
        );

        if ($total_users > 0) {
            return round(($total / $total_users) * 100, 2);
        }

        return 0;
    }

    /**
     * Create conversions table
     *
     * @return void
     */
    private function create_table()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'twork_spin_wheel_conversions';

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            event_type varchar(50) NOT NULL,
            event_data longtext,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_id (user_id),
            KEY idx_event_type (event_type),
            KEY idx_created_at (created_at)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

