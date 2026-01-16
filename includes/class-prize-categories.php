<?php
/**
 * Prize Categories Class
 * 
 * Handles prize categorization system
 * 
 * @package TWork_Spin_Wheel
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class TWork_Spin_Wheel_Prize_Categories
 */
class TWork_Spin_Wheel_Prize_Categories
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
     * Get categories table name
     *
     * @return string Table name.
     */
    public function get_categories_table()
    {
        global $wpdb;
        return $wpdb->prefix . 'twork_spin_wheel_categories';
    }

    /**
     * Create categories table
     *
     * @return void
     */
    public function create_table()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $this->get_categories_table();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            description text DEFAULT NULL,
            color varchar(50) DEFAULT '#666666',
            icon varchar(255) DEFAULT NULL,
            display_order int(11) DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_slug (slug),
            KEY idx_is_active (is_active),
            KEY idx_display_order (display_order)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Get all categories
     *
     * @return array Categories.
     */
    public function get_categories()
    {
        global $wpdb;
        $table_name = $this->get_categories_table();

        return $wpdb->get_results(
            "SELECT * FROM {$table_name} WHERE is_active = 1 ORDER BY display_order ASC, name ASC"
        );
    }

    /**
     * Add category
     *
     * @param array $category_data Category data.
     * @return int|false Category ID on success, false on failure.
     */
    public function add_category($category_data)
    {
        global $wpdb;
        $table_name = $this->get_categories_table();

        $data = array(
            'name' => sanitize_text_field($category_data['name'] ?? ''),
            'slug' => sanitize_title($category_data['name'] ?? ''),
            'description' => wp_kses_post($category_data['description'] ?? ''),
            'color' => sanitize_hex_color($category_data['color'] ?? '#666666'),
            'icon' => sanitize_text_field($category_data['icon'] ?? ''),
            'display_order' => absint($category_data['display_order'] ?? 0),
            'is_active' => 1,
        );

        $result = $wpdb->insert($table_name, $data, array('%s', '%s', '%s', '%s', '%s', '%d', '%d'));

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Update category
     *
     * @param int $category_id Category ID.
     * @param array $category_data Category data.
     * @return bool True on success.
     */
    public function update_category($category_id, $category_data)
    {
        global $wpdb;
        $table_name = $this->get_categories_table();

        $data = array();
        $format = array();

        if (isset($category_data['name'])) {
            $data['name'] = sanitize_text_field($category_data['name']);
            $data['slug'] = sanitize_title($category_data['name']);
            $format[] = '%s';
            $format[] = '%s';
        }

        if (isset($category_data['description'])) {
            $data['description'] = wp_kses_post($category_data['description']);
            $format[] = '%s';
        }

        if (isset($category_data['color'])) {
            $data['color'] = sanitize_hex_color($category_data['color']);
            $format[] = '%s';
        }

        if (isset($category_data['icon'])) {
            $data['icon'] = sanitize_text_field($category_data['icon']);
            $format[] = '%s';
        }

        if (isset($category_data['display_order'])) {
            $data['display_order'] = absint($category_data['display_order']);
            $format[] = '%d';
        }

        if (isset($category_data['is_active'])) {
            $data['is_active'] = absint($category_data['is_active']);
            $format[] = '%d';
        }

        if (empty($data)) {
            return false;
        }

        $result = $wpdb->update(
            $table_name,
            $data,
            array('id' => $category_id),
            $format,
            array('%d')
        );

        return $result !== false;
    }

    /**
     * Delete category
     *
     * @param int $category_id Category ID.
     * @return bool True on success.
     */
    public function delete_category($category_id)
    {
        global $wpdb;
        $table_name = $this->get_categories_table();

        return $wpdb->delete(
            $table_name,
            array('id' => $category_id),
            array('%d')
        ) !== false;
    }
}

