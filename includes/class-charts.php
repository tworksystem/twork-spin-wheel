<?php
/**
 * Charts Class
 * 
 * Handles chart generation for analytics
 * 
 * @package TWork_Spin_Wheel
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class TWork_Spin_Wheel_Charts
 */
class TWork_Spin_Wheel_Charts
{
    /**
     * Generate chart data for prize distribution
     *
     * @param int $wheel_id Wheel ID.
     * @param string $date_from Start date.
     * @param string $date_to End date.
     * @return array Chart data.
     */
    public static function get_prize_distribution_chart($wheel_id = 0, $date_from = '', $date_to = '')
    {
        global $wpdb;
        $database = new TWork_Spin_Wheel_Database();
        $history_table = $database->get_history_table();

        $where = array('1=1');
        $where_values = array();

        if ($wheel_id > 0) {
            $where[] = 'wheel_id = %d';
            $where_values[] = $wheel_id;
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

        $query = "SELECT prize_name, prize_type, COUNT(*) as count 
                  FROM {$history_table} 
                  WHERE {$where_sql}
                  GROUP BY prize_name, prize_type
                  ORDER BY count DESC";

        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }

        $results = $wpdb->get_results($query);

        $labels = array();
        $data = array();
        $colors = array(
            '#667eea', '#764ba2', '#f093fb', '#f5576c',
            '#10b981', '#f59e0b', '#3b82f6', '#ef4444',
            '#8b5cf6', '#ec4899'
        );

        foreach ($results as $index => $result) {
            $labels[] = $result->prize_name;
            $data[] = absint($result->count);
        }

        return array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'label' => __('Prize Distribution', 'twork-spin-wheel'),
                    'data' => $data,
                    'backgroundColor' => array_slice($colors, 0, count($data)),
                    'borderColor' => '#fff',
                    'borderWidth' => 2,
                ),
            ),
        );
    }

    /**
     * Generate chart data for daily spins
     *
     * @param int $wheel_id Wheel ID.
     * @param string $date_from Start date.
     * @param string $date_to End date.
     * @return array Chart data.
     */
    public static function get_daily_spins_chart($wheel_id = 0, $date_from = '', $date_to = '')
    {
        global $wpdb;
        $database = new TWork_Spin_Wheel_Database();
        $history_table = $database->get_history_table();

        $where = array('1=1');
        $where_values = array();

        if ($wheel_id > 0) {
            $where[] = 'wheel_id = %d';
            $where_values[] = $wheel_id;
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

        $query = "SELECT DATE(created_at) as date, COUNT(*) as count 
                  FROM {$history_table} 
                  WHERE {$where_sql}
                  GROUP BY DATE(created_at)
                  ORDER BY date ASC";

        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }

        $results = $wpdb->get_results($query);

        $labels = array();
        $data = array();

        foreach ($results as $result) {
            $labels[] = date('M j', strtotime($result->date));
            $data[] = absint($result->count);
        }

        return array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'label' => __('Daily Spins', 'twork-spin-wheel'),
                    'data' => $data,
                    'backgroundColor' => 'rgba(102, 126, 234, 0.2)',
                    'borderColor' => '#667eea',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                ),
            ),
        );
    }
}

