<?php
/**
 * Export/Import Class
 * 
 * Handles data export and import functionality
 * 
 * @package TWork_Spin_Wheel
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class TWork_Spin_Wheel_Export
 */
class TWork_Spin_Wheel_Export
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
     * Export spin history to CSV
     *
     * @param array $args Export arguments.
     * @return void
     */
    public function export_history_csv($args = array())
    {
        global $wpdb;
        $history_table = $this->database->get_history_table();

        $defaults = array(
            'wheel_id' => 0,
            'user_id' => 0,
            'date_from' => '',
            'date_to' => '',
            'limit' => 0,
        );

        $args = wp_parse_args($args, $defaults);

        // Build query
        $where = array('1=1');
        $where_values = array();

        if ($args['wheel_id'] > 0) {
            $where[] = 'wheel_id = %d';
            $where_values[] = $args['wheel_id'];
        }

        if ($args['user_id'] > 0) {
            $where[] = 'user_id = %d';
            $where_values[] = $args['user_id'];
        }

        if (!empty($args['date_from'])) {
            $where[] = 'created_at >= %s';
            $where_values[] = $args['date_from'] . ' 00:00:00';
        }

        if (!empty($args['date_to'])) {
            $where[] = 'created_at <= %s';
            $where_values[] = $args['date_to'] . ' 23:59:59';
        }

        $where_sql = implode(' AND ', $where);
        $limit_sql = $args['limit'] > 0 ? 'LIMIT ' . absint($args['limit']) : '';

        $query = "SELECT * FROM {$history_table} WHERE {$where_sql} ORDER BY created_at DESC {$limit_sql}";

        if (!empty($where_values)) {
            $results = $wpdb->get_results($wpdb->prepare($query, $where_values));
        } else {
            $results = $wpdb->get_results($query);
        }

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=spin-wheel-history-' . date('Y-m-d') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Output BOM for UTF-8
        echo "\xEF\xBB\xBF";

        // Open output stream
        $output = fopen('php://output', 'w');

        // Add CSV headers
        fputcsv($output, array(
            'ID',
            'User ID',
            'Wheel ID',
            'Prize ID',
            'Prize Name',
            'Prize Type',
            'Prize Value',
            'Cost Points',
            'Status',
            'Is Claimed',
            'Claimed At',
            'Created At',
        ));

        // Add data rows
        foreach ($results as $row) {
            fputcsv($output, array(
                $row->id,
                $row->user_id,
                $row->wheel_id,
                $row->prize_id,
                $row->prize_name,
                $row->prize_type,
                $row->prize_value,
                $row->cost_points,
                $row->status,
                $row->is_claimed,
                $row->claimed_at,
                $row->created_at,
            ));
        }

        fclose($output);
        exit;
    }

    /**
     * Export prizes to JSON
     *
     * @param int $wheel_id Wheel ID.
     * @return string JSON data.
     */
    public function export_prizes_json($wheel_id = 0)
    {
        global $wpdb;
        $prizes_table = $this->database->get_prizes_table();

        $where = $wheel_id > 0 ? $wpdb->prepare('WHERE wheel_id = %d', $wheel_id) : '';
        $prizes = $wpdb->get_results("SELECT * FROM {$prizes_table} {$where} ORDER BY display_order ASC, id ASC");

        $export_data = array(
            'version' => TWORK_SPIN_WHEEL_VERSION,
            'export_date' => current_time('mysql'),
            'prizes' => array(),
        );

        foreach ($prizes as $prize) {
            $export_data['prizes'][] = array(
                'prize_name' => $prize->prize_name,
                'prize_type' => $prize->prize_type,
                'prize_value' => $prize->prize_value,
                'probability_weight' => $prize->probability_weight,
                'sector_color' => $prize->sector_color,
                'text_color' => $prize->text_color,
                'icon' => $prize->icon,
                'description' => $prize->description,
                'is_active' => $prize->is_active,
                'display_order' => $prize->display_order,
            );
        }

        return wp_json_encode($export_data, JSON_PRETTY_PRINT);
    }

    /**
     * Import prizes from JSON
     *
     * @param string $json_data JSON data.
     * @param int $wheel_id Target wheel ID.
     * @return array|WP_Error Result array or WP_Error on failure.
     */
    public function import_prizes_json($json_data, $wheel_id)
    {
        global $wpdb;
        $prizes_table = $this->database->get_prizes_table();

        $data = json_decode($json_data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('invalid_json', __('Invalid JSON data.', 'twork-spin-wheel'));
        }

        if (!isset($data['prizes']) || !is_array($data['prizes'])) {
            return new WP_Error('invalid_format', __('Invalid import format.', 'twork-spin-wheel'));
        }

        $imported = 0;
        $errors = array();

        foreach ($data['prizes'] as $prize_data) {
            $result = $wpdb->insert(
                $prizes_table,
                array(
                    'wheel_id' => $wheel_id,
                    'prize_name' => sanitize_text_field($prize_data['prize_name'] ?? ''),
                    'prize_type' => sanitize_text_field($prize_data['prize_type'] ?? 'points'),
                    'prize_value' => sanitize_text_field($prize_data['prize_value'] ?? '0'),
                    'probability_weight' => absint($prize_data['probability_weight'] ?? 1),
                    'sector_color' => sanitize_hex_color($prize_data['sector_color'] ?? '#FF6B6B'),
                    'text_color' => sanitize_hex_color($prize_data['text_color'] ?? '#FFFFFF'),
                    'icon' => sanitize_text_field($prize_data['icon'] ?? '🎁'),
                    'description' => wp_kses_post($prize_data['description'] ?? ''),
                    'is_active' => isset($prize_data['is_active']) ? absint($prize_data['is_active']) : 1,
                    'display_order' => absint($prize_data['display_order'] ?? 0),
                ),
                array('%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%d')
            );

            if ($result) {
                $imported++;
            } else {
                $errors[] = sprintf(
                    __('Failed to import prize: %s', 'twork-spin-wheel'),
                    $prize_data['prize_name'] ?? 'Unknown'
                );
            }
        }

        return array(
            'imported' => $imported,
            'total' => count($data['prizes']),
            'errors' => $errors,
        );
    }

    /**
     * Export settings to JSON
     *
     * @return string JSON data.
     */
    public function export_settings_json()
    {
        global $wpdb;
        $wheels_table = $this->database->get_wheels_table();

        $wheel = $wpdb->get_row("SELECT * FROM {$wheels_table} WHERE is_default = 1 ORDER BY id DESC LIMIT 1");

        if (!$wheel) {
            return wp_json_encode(array('error' => __('No settings found.', 'twork-spin-wheel')));
        }

        $export_data = array(
            'version' => TWORK_SPIN_WHEEL_VERSION,
            'export_date' => current_time('mysql'),
            'settings' => array(
                'name' => $wheel->name,
                'description' => $wheel->description,
                'is_active' => $wheel->is_active,
                'daily_limit' => $wheel->daily_limit,
                'cost_points' => $wheel->cost_points,
                'background_color' => $wheel->background_color,
                'border_color' => $wheel->border_color,
                'center_color' => $wheel->center_color,
                'enable_animation' => $wheel->enable_animation,
                'enable_sound' => $wheel->enable_sound,
                'animation_duration' => $wheel->animation_duration,
            ),
        );

        return wp_json_encode($export_data, JSON_PRETTY_PRINT);
    }
}

