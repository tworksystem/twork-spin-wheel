<?php
/**
 * Logger Class
 * 
 * Handles logging functionality for debugging and monitoring
 * 
 * @package TWork_Spin_Wheel
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class TWork_Spin_Wheel_Logger
 */
class TWork_Spin_Wheel_Logger
{
    /**
     * Log levels
     */
    const LEVEL_DEBUG = 'debug';
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';

    /**
     * Log an event
     *
     * @param string $message Log message.
     * @param string $level Log level.
     * @param array $context Additional context data.
     * @return void
     */
    public static function log($message, $level = self::LEVEL_INFO, $context = array())
    {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        $enabled = get_option('twork_spin_wheel_enable_logging', true);
        if (!$enabled) {
            return;
        }

        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
        );

        // Log to WordPress debug log
        $log_message = sprintf(
            '[T-Work Spin Wheel] [%s] %s %s',
            strtoupper($level),
            $message,
            !empty($context) ? wp_json_encode($context) : ''
        );

        error_log($log_message);

        // Optionally log to database
        $log_to_db = get_option('twork_spin_wheel_log_to_db', false);
        if ($log_to_db) {
            self::log_to_database($log_entry);
        }
    }

    /**
     * Log to database
     *
     * @param array $log_entry Log entry data.
     * @return void
     */
    private static function log_to_database($log_entry)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'twork_spin_wheel_logs';

        // Create table if it doesn't exist
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            timestamp datetime NOT NULL,
            level varchar(20) NOT NULL,
            message text NOT NULL,
            context longtext,
            PRIMARY KEY (id),
            KEY idx_timestamp (timestamp),
            KEY idx_level (level)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Insert log entry
        $wpdb->insert(
            $table_name,
            array(
                'timestamp' => $log_entry['timestamp'],
                'level' => $log_entry['level'],
                'message' => $log_entry['message'],
                'context' => !empty($log_entry['context']) ? wp_json_encode($log_entry['context']) : null,
            ),
            array('%s', '%s', '%s', '%s')
        );
    }

    /**
     * Get logs
     *
     * @param array $args Query arguments.
     * @return array Log entries.
     */
    public static function get_logs($args = array())
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'twork_spin_wheel_logs';

        $defaults = array(
            'level' => '',
            'limit' => 100,
            'offset' => 0,
            'date_from' => '',
            'date_to' => '',
        );

        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $where_values = array();

        if (!empty($args['level'])) {
            $where[] = 'level = %s';
            $where_values[] = $args['level'];
        }

        if (!empty($args['date_from'])) {
            $where[] = 'timestamp >= %s';
            $where_values[] = $args['date_from'] . ' 00:00:00';
        }

        if (!empty($args['date_to'])) {
            $where[] = 'timestamp <= %s';
            $where_values[] = $args['date_to'] . ' 23:59:59';
        }

        $where_sql = implode(' AND ', $where);
        $limit_sql = 'LIMIT ' . absint($args['limit']) . ' OFFSET ' . absint($args['offset']);

        $query = "SELECT * FROM {$table_name} WHERE {$where_sql} ORDER BY timestamp DESC {$limit_sql}";

        if (!empty($where_values)) {
            return $wpdb->get_results($wpdb->prepare($query, $where_values));
        }

        return $wpdb->get_results($query);
    }

    /**
     * Clear old logs
     *
     * @param int $days Number of days to keep.
     * @return int Number of logs deleted.
     */
    public static function clear_old_logs($days = 30)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'twork_spin_wheel_logs';

        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table_name} WHERE timestamp < %s",
                $cutoff_date
            )
        );
    }
}

