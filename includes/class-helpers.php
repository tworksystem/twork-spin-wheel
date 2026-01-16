<?php
/**
 * Helper Functions Class
 * 
 * Utility functions for the Spin Wheel System
 */

if (!defined('ABSPATH')) {
    exit;
}

class TWork_Spin_Wheel_Helpers
{
    /**
     * Get client IP address
     * 
     * @return string IP address
     */
    public static function get_client_ip()
    {
        $ip = '';

        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_CLIENT_IP']));
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
        }

        return $ip;
    }

    /**
     * Select prize based on probability
     * 
     * @param array $prizes Array of prizes with probability weights
     * @return array|null Selected prize or null on failure
     */
    public static function select_prize_by_probability($prizes)
    {
        if (empty($prizes) || !is_array($prizes)) {
            return null;
        }

        // Calculate total probability
        $total_probability = 0;
        foreach ($prizes as $prize) {
            if (!isset($prize['probability']) || !is_numeric($prize['probability'])) {
                continue;
            }
            $total_probability += floatval($prize['probability']);
        }

        if ($total_probability <= 0) {
            return null;
        }

        // Generate random number
        $random = mt_rand(1, $total_probability * 100) / 100;

        // Select prize
        $cumulative = 0;
        foreach ($prizes as $prize) {
            $cumulative += floatval($prize['probability']);
            if ($random <= $cumulative) {
                return $prize;
            }
        }

        // Fallback to last prize
        return end($prizes);
    }

    /**
     * Generate unique coupon code
     * 
     * @param string $prefix Coupon prefix
     * @return string Unique coupon code
     */
    public static function generate_unique_coupon_code($prefix = 'SPIN')
    {
        $prefix = strtoupper(sanitize_title($prefix));
        $prefix = substr($prefix, 0, 10); // Limit prefix length

        do {
            $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
            $code = $prefix . '-' . $random;
            $exists = get_page_by_title($code, OBJECT, 'shop_coupon');
        } while ($exists);

        return $code;
    }

    /**
     * Create WooCommerce coupon
     * 
     * @param string $code Coupon code
     * @param array $prize Prize data
     * @return int|false Coupon ID on success, false on failure
     */
    public static function create_woocommerce_coupon($code, $prize)
    {
        if (!function_exists('wc_get_coupon_id_by_code')) {
            return false;
        }

        // Parse discount value
        $discount_value = isset($prize['value']) ? sanitize_text_field($prize['value']) : '0';
        $discount_type = 'fixed_cart'; // Default

        // Check if percentage
        if (strpos($discount_value, '%') !== false) {
            $discount_type = 'percent';
            $discount_value = floatval(str_replace('%', '', $discount_value));
        } else {
            $discount_value = floatval($discount_value);
        }

        // Create coupon
        $coupon = array(
            'post_title' => $code,
            'post_content' => sprintf(__('Spin Wheel Prize: %s', 'twork-spin-wheel'), isset($prize['label']) ? sanitize_text_field($prize['label']) : ''),
            'post_status' => 'publish',
            'post_author' => 1,
            'post_type' => 'shop_coupon',
        );

        $coupon_id = wp_insert_post($coupon);

        if (!$coupon_id || is_wp_error($coupon_id)) {
            return false;
        }

        // Set coupon meta
        update_post_meta($coupon_id, 'discount_type', $discount_type);
        update_post_meta($coupon_id, 'coupon_amount', $discount_value);
        update_post_meta($coupon_id, 'individual_use', 'yes');
        update_post_meta($coupon_id, 'usage_limit', '1');
        update_post_meta($coupon_id, 'usage_limit_per_user', '1');
        update_post_meta($coupon_id, 'expiry_date', date('Y-m-d', strtotime('+30 days')));
        update_post_meta($coupon_id, 'free_shipping', 'no');

        return $coupon_id;
    }

    /**
     * Check if user can spin today
     * 
     * @param int $user_id User ID
     * @param int $wheel_id Wheel ID (optional)
     * @return bool True if user can spin, false otherwise
     */
    public static function can_user_spin_today($user_id, $wheel_id = 0)
    {
        $spins_left = self::get_user_spins_left_today($user_id, $wheel_id);
        return $spins_left > 0;
    }

    /**
     * Get user's remaining spins for today
     * 
     * @param int $user_id User ID
     * @param int $wheel_id Wheel ID (optional)
     * @return int Number of spins left
     */
    public static function get_user_spins_left_today($user_id, $wheel_id = 0)
    {
        global $wpdb;
        $database = new TWork_Spin_Wheel_Database();

        // Get active configuration
        $wheels_table = $database->get_wheels_table();
        $config = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT daily_limit, id FROM {$wheels_table} WHERE is_active = %d ORDER BY is_default DESC, id DESC LIMIT 1",
                1
            )
        );

        if (!$config) {
            return 0;
        }

        $wheel_id = $wheel_id ?: $config->id;
        $max_spins = absint($config->daily_limit ?: 3);

        // Get today's spin count
        $history_table = $database->get_history_table();
        $today_start = date('Y-m-d 00:00:00');
        $today_end = date('Y-m-d 23:59:59');

        $today_spins = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$history_table} 
                WHERE user_id = %d 
                AND wheel_id = %d
                AND created_at >= %s 
                AND created_at <= %s",
                $user_id,
                $wheel_id,
                $today_start,
                $today_end
            )
        );

        return max(0, $max_spins - absint($today_spins));
    }

    /**
     * Get user's spin history
     * 
     * @param int $user_id User ID
     * @param int $limit Number of records to retrieve
     * @return array Array of spin records
     */
    public static function get_user_spin_history($user_id, $limit = 10)
    {
        global $wpdb;
        $database = new TWork_Spin_Wheel_Database();
        $history_table = $database->get_history_table();

        $spins = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$history_table} 
                WHERE user_id = %d 
                ORDER BY created_at DESC 
                LIMIT %d",
                $user_id,
                $limit
            )
        );

        $history = array();
        foreach ($spins as $spin) {
            if (!is_object($spin)) {
                continue;
            }
            $history[] = array(
                'prize_label' => isset($spin->prize_name) ? sanitize_text_field($spin->prize_name) : '',
                'prize_type' => isset($spin->prize_type) ? sanitize_text_field($spin->prize_type) : '',
                'spin_date' => isset($spin->created_at) ? $spin->created_at : '',
                'spin_date_formatted' => isset($spin->created_at) ? date_i18n(get_option('date_format'), strtotime($spin->created_at)) : '',
            );
        }

        return $history;
    }

    /**
     * Update spin wheel statistics
     * 
     * @param int $wheel_id Wheel configuration ID
     */
    public static function update_spin_wheel_stats($wheel_id)
    {
        global $wpdb;
        $database = new TWork_Spin_Wheel_Database();
        $wheels_table = $database->get_wheels_table();

        // Update wheel's updated_at timestamp
        $wpdb->update(
            $wheels_table,
            array(
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $wheel_id),
            array('%s'),
            array('%d')
        );
    }
}

