<?php
/**
 * Security Class
 * 
 * Handles security features and validations
 * 
 * @package TWork_Spin_Wheel
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class TWork_Spin_Wheel_Security
 */
class TWork_Spin_Wheel_Security
{
    /**
     * Rate limiting data
     *
     * @var array
     */
    private static $rate_limits = array();

    /**
     * Check if request is rate limited
     *
     * @param string $identifier Unique identifier (IP, user ID, etc.).
     * @param int $max_requests Maximum requests allowed.
     * @param int $time_window Time window in seconds.
     * @return bool True if rate limited, false otherwise.
     */
    public static function is_rate_limited($identifier, $max_requests = 10, $time_window = 60)
    {
        $enabled = get_option('twork_spin_wheel_enable_rate_limiting', true);
        if (!$enabled) {
            return false;
        }

        $cache_key = 'rate_limit_' . md5($identifier);
        $requests = wp_cache_get($cache_key, 'twork_spin_wheel_rate_limits');

        if ($requests === false) {
            wp_cache_set($cache_key, 1, 'twork_spin_wheel_rate_limits', $time_window);
            return false;
        }

        if ($requests >= $max_requests) {
            return true;
        }

        wp_cache_incr($cache_key, 1, 'twork_spin_wheel_rate_limits');
        return false;
    }

    /**
     * Validate and sanitize input
     *
     * @param mixed $input Input to validate.
     * @param string $type Input type (int, string, email, url, etc.).
     * @return mixed Sanitized input or false on failure.
     */
    public static function validate_input($input, $type = 'string')
    {
        switch ($type) {
            case 'int':
                return absint($input);
            case 'float':
                return floatval($input);
            case 'email':
                return sanitize_email($input);
            case 'url':
                return esc_url_raw($input);
            case 'text':
                return sanitize_text_field($input);
            case 'textarea':
                return sanitize_textarea_field($input);
            case 'html':
                return wp_kses_post($input);
            case 'color':
                return sanitize_hex_color($input);
            case 'array':
                if (!is_array($input)) {
                    return false;
                }
                return array_map('sanitize_text_field', $input);
            default:
                return sanitize_text_field($input);
        }
    }

    /**
     * Verify nonce
     *
     * @param string $action Action name.
     * @param string $nonce Nonce value.
     * @return bool True if valid, false otherwise.
     */
    public static function verify_nonce($action, $nonce)
    {
        return wp_verify_nonce($nonce, $action);
    }

    /**
     * Check user capability
     *
     * @param string $capability Capability to check.
     * @param int $user_id Optional user ID.
     * @return bool True if user has capability, false otherwise.
     */
    public static function check_capability($capability, $user_id = 0)
    {
        if (!$user_id) {
            return current_user_can($capability);
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        return $user->has_cap($capability);
    }

    /**
     * Sanitize SQL query
     *
     * @param string $query SQL query.
     * @param array $values Values to prepare.
     * @return string|WP_Error Prepared query or WP_Error on failure.
     */
    public static function prepare_query($query, $values = array())
    {
        global $wpdb;

        if (empty($values)) {
            return $query;
        }

        return $wpdb->prepare($query, $values);
    }

    /**
     * Log security event
     *
     * @param string $event Event type.
     * @param string $message Event message.
     * @param array $context Additional context.
     * @return void
     */
    public static function log_security_event($event, $message, $context = array())
    {
        $enabled = get_option('twork_spin_wheel_log_security_events', true);
        if (!$enabled) {
            return;
        }

        $context['security_event'] = $event;
        $context['ip_address'] = self::get_client_ip();
        $context['user_agent'] = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';

        TWork_Spin_Wheel_Logger::log($message, TWork_Spin_Wheel_Logger::LEVEL_WARNING, $context);
    }

    /**
     * Get client IP address
     *
     * @return string IP address.
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
     * Check for SQL injection attempts
     *
     * @param string $input Input to check.
     * @return bool True if suspicious, false otherwise.
     */
    public static function detect_sql_injection($input)
    {
        $suspicious_patterns = array(
            '/(\bUNION\b.*\bSELECT\b)/i',
            '/(\bSELECT\b.*\bFROM\b)/i',
            '/(\bINSERT\b.*\bINTO\b)/i',
            '/(\bUPDATE\b.*\bSET\b)/i',
            '/(\bDELETE\b.*\bFROM\b)/i',
            '/(\bDROP\b.*\bTABLE\b)/i',
            '/(\bEXEC\b|\bEXECUTE\b)/i',
            '/(\bSCRIPT\b)/i',
            '/(\bJAVASCRIPT\b)/i',
            '/(\bONLOAD\b|\bONERROR\b)/i',
        );

        foreach ($suspicious_patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate wheel ID
     *
     * @param int $wheel_id Wheel ID.
     * @return bool True if valid, false otherwise.
     */
    public static function validate_wheel_id($wheel_id)
    {
        $wheel_id = absint($wheel_id);
        if ($wheel_id <= 0) {
            return false;
        }

        global $wpdb;
        $database = new TWork_Spin_Wheel_Database();
        $wheels_table = $database->get_wheels_table();

        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wheels_table} WHERE id = %d",
                $wheel_id
            )
        );

        return $exists > 0;
    }

    /**
     * Validate user ID
     *
     * @param int $user_id User ID.
     * @return bool True if valid, false otherwise.
     */
    public static function validate_user_id($user_id)
    {
        $user_id = absint($user_id);
        if ($user_id <= 0) {
            return false;
        }

        $user = get_userdata($user_id);
        return $user !== false;
    }
}

