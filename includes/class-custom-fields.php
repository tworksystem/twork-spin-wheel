<?php
/**
 * Custom Fields Class
 * 
 * Handles custom fields for prizes
 * 
 * @package TWork_Spin_Wheel
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class TWork_Spin_Wheel_Custom_Fields
 */
class TWork_Spin_Wheel_Custom_Fields
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
     * Get custom fields table name
     *
     * @return string Table name.
     */
    public function get_fields_table()
    {
        global $wpdb;
        return $wpdb->prefix . 'twork_spin_wheel_custom_fields';
    }

    /**
     * Create custom fields table
     *
     * @return void
     */
    public function create_table()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $this->get_fields_table();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            prize_id bigint(20) UNSIGNED NOT NULL,
            field_key varchar(100) NOT NULL,
            field_value longtext,
            field_type varchar(50) DEFAULT 'text',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_prize_id (prize_id),
            KEY idx_field_key (field_key),
            UNIQUE KEY uniq_prize_field (prize_id, field_key)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Get custom fields for prize
     *
     * @param int $prize_id Prize ID.
     * @return array Custom fields.
     */
    public function get_prize_fields($prize_id)
    {
        global $wpdb;
        $table_name = $this->get_fields_table();

        $fields = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE prize_id = %d",
                $prize_id
            ),
            ARRAY_A
        );

        $result = array();
        foreach ($fields as $field) {
            $result[$field['field_key']] = array(
                'value' => $field['field_value'],
                'type' => $field['field_type'],
            );
        }

        return $result;
    }

    /**
     * Set custom field
     *
     * @param int $prize_id Prize ID.
     * @param string $field_key Field key.
     * @param mixed $field_value Field value.
     * @param string $field_type Field type.
     * @return bool True on success.
     */
    public function set_field($prize_id, $field_key, $field_value, $field_type = 'text')
    {
        global $wpdb;
        $table_name = $this->get_fields_table();

        // Sanitize based on type
        $sanitized_value = $this->sanitize_field_value($field_value, $field_type);

        $data = array(
            'prize_id' => absint($prize_id),
            'field_key' => sanitize_key($field_key),
            'field_value' => $sanitized_value,
            'field_type' => sanitize_text_field($field_type),
        );

        $result = $wpdb->replace(
            $table_name,
            $data,
            array('%d', '%s', '%s', '%s')
        );

        return $result !== false;
    }

    /**
     * Delete custom field
     *
     * @param int $prize_id Prize ID.
     * @param string $field_key Field key.
     * @return bool True on success.
     */
    public function delete_field($prize_id, $field_key)
    {
        global $wpdb;
        $table_name = $this->get_fields_table();

        return $wpdb->delete(
            $table_name,
            array(
                'prize_id' => absint($prize_id),
                'field_key' => sanitize_key($field_key),
            ),
            array('%d', '%s')
        ) !== false;
    }

    /**
     * Sanitize field value based on type
     *
     * @param mixed $value Field value.
     * @param string $type Field type.
     * @return mixed Sanitized value.
     */
    private function sanitize_field_value($value, $type)
    {
        switch ($type) {
            case 'url':
                return esc_url_raw($value);
            case 'email':
                return sanitize_email($value);
            case 'int':
                return absint($value);
            case 'float':
                return floatval($value);
            case 'html':
                return wp_kses_post($value);
            case 'json':
                return wp_json_encode($value);
            default:
                return sanitize_text_field($value);
        }
    }
}

