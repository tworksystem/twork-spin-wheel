<?php
/**
 * Analytics Class
 * 
 * Handles analytics, statistics, and reporting for the Spin Wheel System
 * 
 * @package TWork_Spin_Wheel
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class TWork_Spin_Wheel_Analytics
 */
class TWork_Spin_Wheel_Analytics
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
    }

    /**
     * Get overall statistics
     *
     * @param int $wheel_id Optional wheel ID.
     * @param string $date_from Optional start date (Y-m-d).
     * @param string $date_to Optional end date (Y-m-d).
     * @return array Statistics data.
     */
    public function get_statistics($wheel_id = 0, $date_from = '', $date_to = '')
    {
        global $wpdb;
        $history_table = $this->database->get_history_table();
        $wheels_table = $this->database->get_wheels_table();
        $prizes_table = $this->database->get_prizes_table();

        $where_clauses = array('1=1');
        $where_values = array();

        if ($wheel_id > 0) {
            $where_clauses[] = 'h.wheel_id = %d';
            $where_values[] = $wheel_id;
        }

        if (!empty($date_from)) {
            $where_clauses[] = 'h.created_at >= %s';
            $where_values[] = $date_from . ' 00:00:00';
        }

        if (!empty($date_to)) {
            $where_clauses[] = 'h.created_at <= %s';
            $where_values[] = $date_to . ' 23:59:59';
        }

        $where_sql = implode(' AND ', $where_clauses);

        // Total spins
        $total_spins_query = "SELECT COUNT(*) FROM {$history_table} h WHERE {$where_sql}";
        if (!empty($where_values)) {
            $total_spins = $wpdb->get_var($wpdb->prepare($total_spins_query, $where_values));
        } else {
            $total_spins = $wpdb->get_var($total_spins_query);
        }

        // Total unique users
        $unique_users_query = "SELECT COUNT(DISTINCT h.user_id) FROM {$history_table} h WHERE {$where_sql}";
        if (!empty($where_values)) {
            $unique_users = $wpdb->get_var($wpdb->prepare($unique_users_query, $where_values));
        } else {
            $unique_users = $wpdb->get_var($unique_users_query);
        }

        // Total points spent
        $points_spent_query = "SELECT SUM(h.cost_points) FROM {$history_table} h WHERE {$where_sql}";
        if (!empty($where_values)) {
            $points_spent = $wpdb->get_var($wpdb->prepare($points_spent_query, $where_values));
        } else {
            $points_spent = $wpdb->get_var($points_spent_query);
        }

        // Prize distribution
        $prize_dist_query = "
            SELECT 
                h.prize_type,
                h.prize_name,
                COUNT(*) as count,
                SUM(h.cost_points) as total_points
            FROM {$history_table} h
            WHERE {$where_sql}
            GROUP BY h.prize_type, h.prize_name
            ORDER BY count DESC
        ";

        if (!empty($where_values)) {
            $prize_distribution = $wpdb->get_results($wpdb->prepare($prize_dist_query, $where_values));
        } else {
            $prize_distribution = $wpdb->get_results($prize_dist_query);
        }

        // Daily statistics (last 30 days)
        $daily_stats_query = "
            SELECT 
                DATE(h.created_at) as date,
                COUNT(*) as spins,
                COUNT(DISTINCT h.user_id) as users,
                SUM(h.cost_points) as points
            FROM {$history_table} h
            WHERE {$where_sql}
            AND h.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(h.created_at)
            ORDER BY date DESC
        ";

        if (!empty($where_values)) {
            $daily_stats = $wpdb->get_results($wpdb->prepare($daily_stats_query, $where_values));
        } else {
            $daily_stats = $wpdb->get_results($daily_stats_query);
        }

        // Top users
        $top_users_query = "
            SELECT 
                h.user_id,
                COUNT(*) as spin_count,
                SUM(h.cost_points) as total_points_spent
            FROM {$history_table} h
            WHERE {$where_sql}
            GROUP BY h.user_id
            ORDER BY spin_count DESC
            LIMIT 10
        ";

        if (!empty($where_values)) {
            $top_users = $wpdb->get_results($wpdb->prepare($top_users_query, $where_values));
        } else {
            $top_users = $wpdb->get_results($top_users_query);
        }

        return array(
            'total_spins' => absint($total_spins),
            'unique_users' => absint($unique_users),
            'points_spent' => absint($points_spent),
            'prize_distribution' => $prize_distribution,
            'daily_stats' => $daily_stats,
            'top_users' => $top_users,
        );
    }

    /**
     * Get prize statistics
     *
     * @param int $prize_id Prize ID.
     * @return array Prize statistics.
     */
    public function get_prize_statistics($prize_id)
    {
        global $wpdb;
        $history_table = $this->database->get_history_table();

        $stats = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT 
                    COUNT(*) as total_wins,
                    SUM(cost_points) as total_points_spent,
                    COUNT(DISTINCT user_id) as unique_winners,
                    MIN(created_at) as first_win,
                    MAX(created_at) as last_win
                FROM {$history_table}
                WHERE prize_id = %d",
                $prize_id
            )
        );

        return $stats;
    }

    /**
     * Get user statistics
     *
     * @param int $user_id User ID.
     * @return array User statistics.
     */
    public function get_user_statistics($user_id)
    {
        global $wpdb;
        $history_table = $this->database->get_history_table();

        $stats = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT 
                    COUNT(*) as total_spins,
                    SUM(cost_points) as total_points_spent,
                    COUNT(DISTINCT prize_id) as unique_prizes_won,
                    MIN(created_at) as first_spin,
                    MAX(created_at) as last_spin
                FROM {$history_table}
                WHERE user_id = %d",
                $user_id
            )
        );

        // Get prize breakdown
        $prize_breakdown = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
                    prize_type,
                    prize_name,
                    COUNT(*) as count
                FROM {$history_table}
                WHERE user_id = %d
                GROUP BY prize_type, prize_name
                ORDER BY count DESC",
                $user_id
            )
        );

        return array(
            'overall' => $stats,
            'prize_breakdown' => $prize_breakdown,
        );
    }

    /**
     * Update analytics table
     *
     * @param int $wheel_id Wheel ID.
     * @param int $prize_id Prize ID (optional).
     * @param string $date Date (Y-m-d).
     * @return void
     */
    public function update_analytics($wheel_id, $prize_id = null, $date = null)
    {
        global $wpdb;
        $analytics_table = $this->database->get_analytics_table();
        $history_table = $this->database->get_history_table();

        if (!$date) {
            $date = current_time('Y-m-d');
        }

        // Get statistics for the day
        $where = array(
            'wheel_id' => $wheel_id,
            'DATE(created_at)' => $date,
        );

        if ($prize_id) {
            $where['prize_id'] = $prize_id;
        }

        $stats = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT 
                    COUNT(*) as total_spins,
                    COUNT(DISTINCT user_id) as unique_users,
                    SUM(cost_points) as total_cost_points
                FROM {$history_table}
                WHERE wheel_id = %d
                AND DATE(created_at) = %s
                " . ($prize_id ? 'AND prize_id = %d' : ''),
                $wheel_id,
                $date,
                ...($prize_id ? array($prize_id) : array())
            )
        );

        if ($stats) {
            // Insert or update analytics record
            $wpdb->replace(
                $analytics_table,
                array(
                    'wheel_id' => $wheel_id,
                    'prize_id' => $prize_id,
                    'date' => $date,
                    'total_spins' => absint($stats->total_spins),
                    'total_wins' => absint($stats->total_spins), // Assuming all spins are wins
                    'total_cost_points' => absint($stats->total_cost_points),
                    'unique_users' => absint($stats->unique_users),
                ),
                array('%d', '%d', '%s', '%d', '%d', '%d', '%d')
            );
        }
    }

    /**
     * Get conversion rate
     *
     * @param int $wheel_id Wheel ID.
     * @param string $date_from Start date.
     * @param string $date_to End date.
     * @return float Conversion rate percentage.
     */
    public function get_conversion_rate($wheel_id = 0, $date_from = '', $date_to = '')
    {
        // This would compare spins vs total eligible users
        // Implementation depends on how you track eligible users
        $stats = $this->get_statistics($wheel_id, $date_from, $date_to);
        
        // Placeholder calculation
        return $stats['unique_users'] > 0 ? 
            round(($stats['total_spins'] / $stats['unique_users']) * 100, 2) : 0;
    }
}

