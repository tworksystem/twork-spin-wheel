<?php
/**
 * Database Management Class
 * 
 * Handles all database operations for the Spin Wheel System
 */

if (!defined('ABSPATH')) {
    exit;
}

class TWork_Spin_Wheel_Database
{
    /**
     * Get wheels table name
     */
    public function get_wheels_table()
    {
        global $wpdb;
        return $wpdb->prefix . 'twork_spin_wheels';
    }

    /**
     * Get prizes table name
     */
    public function get_prizes_table()
    {
        global $wpdb;
        return $wpdb->prefix . 'twork_spin_wheel_prizes';
    }

    /**
     * Get history table name
     */
    public function get_history_table()
    {
        global $wpdb;
        return $wpdb->prefix . 'twork_spin_wheel_history';
    }

    /**
     * Get analytics table name
     */
    public function get_analytics_table()
    {
        global $wpdb;
        return $wpdb->prefix . 'twork_spin_wheel_analytics';
    }

    /**
     * Create all database tables
     */
    public function create_tables()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Table 1: Spin Wheel Configurations
        $wheels_table = $this->get_wheels_table();
        $wheels_sql = "CREATE TABLE IF NOT EXISTS {$wheels_table} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            description text DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            is_default tinyint(1) DEFAULT 0,
            cost_points int(11) DEFAULT 0,
            cooldown_hours int(11) DEFAULT 24,
            daily_limit int(11) DEFAULT NULL,
            weekly_limit int(11) DEFAULT NULL,
            monthly_limit int(11) DEFAULT NULL,
            min_points_required int(11) DEFAULT 0,
            require_tier tinyint(1) DEFAULT 0,
            min_tier_level int(11) DEFAULT 0,
            enable_scheduled tinyint(1) DEFAULT 0,
            start_time time DEFAULT NULL,
            end_time time DEFAULT NULL,
            timezone varchar(50) DEFAULT 'Asia/Yangon',
            background_color varchar(50) DEFAULT '#FFFFFF',
            border_color varchar(50) DEFAULT '#000000',
            center_color varchar(50) DEFAULT '#FFD700',
            enable_sound tinyint(1) DEFAULT 1,
            enable_animation tinyint(1) DEFAULT 1,
            animation_duration int(11) DEFAULT 3000,
            show_probability tinyint(1) DEFAULT 0,
            banner_content longtext DEFAULT NULL,
            notification_title varchar(255) DEFAULT NULL,
            notification_message text DEFAULT NULL,
            display_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by bigint(20) UNSIGNED NULL DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_slug (slug),
            KEY idx_is_active (is_active),
            KEY idx_is_default (is_default),
            KEY idx_display_order (display_order),
            KEY idx_created_at (created_at)
        ) {$charset_collate};";

        // Table 2: Spin Wheel Prizes/Sectors
        $prizes_table = $this->get_prizes_table();
        $prizes_sql = "CREATE TABLE IF NOT EXISTS {$prizes_table} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            wheel_id bigint(20) UNSIGNED NOT NULL,
            prize_name varchar(255) NOT NULL,
            prize_type varchar(50) NOT NULL DEFAULT 'points',
            prize_value varchar(255) DEFAULT NULL,
            probability_weight int(11) DEFAULT 1,
            sector_color varchar(50) DEFAULT '#FFD700',
            text_color varchar(50) DEFAULT '#000000',
            icon varchar(255) DEFAULT NULL,
            description text DEFAULT NULL,
            image_url varchar(500) DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            max_wins_per_day int(11) DEFAULT NULL,
            max_wins_per_week int(11) DEFAULT NULL,
            max_wins_per_month int(11) DEFAULT NULL,
            max_total_wins int(11) DEFAULT NULL,
            current_wins int(11) DEFAULT 0,
            display_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_wheel_id (wheel_id),
            KEY idx_prize_type (prize_type),
            KEY idx_is_active (is_active),
            KEY idx_display_order (display_order),
            KEY idx_probability_weight (probability_weight)
        ) {$charset_collate};";

        // Table 3: User Spin History
        $history_table = $this->get_history_table();
        $history_sql = "CREATE TABLE IF NOT EXISTS {$history_table} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            wheel_id bigint(20) UNSIGNED NOT NULL,
            prize_id bigint(20) UNSIGNED NULL DEFAULT NULL,
            prize_name varchar(255) DEFAULT NULL,
            prize_type varchar(50) DEFAULT NULL,
            prize_value varchar(255) DEFAULT NULL,
            cost_points int(11) DEFAULT 0,
            status varchar(20) DEFAULT 'won',
            is_claimed tinyint(1) DEFAULT 0,
            claimed_at datetime DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            device_info varchar(255) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_id (user_id),
            KEY idx_wheel_id (wheel_id),
            KEY idx_prize_id (prize_id),
            KEY idx_status (status),
            KEY idx_is_claimed (is_claimed),
            KEY idx_created_at (created_at),
            KEY idx_user_wheel_date (user_id, wheel_id, created_at)
        ) {$charset_collate};";

        // Table 4: Spin Wheel Analytics
        $analytics_table = $this->get_analytics_table();
        $analytics_sql = "CREATE TABLE IF NOT EXISTS {$analytics_table} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            wheel_id bigint(20) UNSIGNED NOT NULL,
            prize_id bigint(20) UNSIGNED NULL DEFAULT NULL,
            date date NOT NULL,
            total_spins int(11) DEFAULT 0,
            total_wins int(11) DEFAULT 0,
            total_cost_points bigint(20) DEFAULT 0,
            total_prize_value bigint(20) DEFAULT 0,
            unique_users int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_wheel_prize_date (wheel_id, prize_id, date),
            KEY idx_wheel_id (wheel_id),
            KEY idx_prize_id (prize_id),
            KEY idx_date (date)
        ) {$charset_collate};";

        dbDelta($wheels_sql);
        dbDelta($prizes_sql);
        dbDelta($history_sql);
        dbDelta($analytics_sql);

        // Log table creation
        if (defined('WP_DEBUG') && WP_DEBUG) {
            if ($wpdb->last_error) {
                error_log('T-Work Spin Wheel: Tables creation error: ' . $wpdb->last_error);
            } else {
                error_log('T-Work Spin Wheel: Tables created successfully');
            }
        }
    }
}

