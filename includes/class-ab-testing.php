<?php
/**
 * A/B Testing Class
 * 
 * Handles A/B testing for different wheel configurations
 * 
 * @package TWork_Spin_Wheel
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class TWork_Spin_Wheel_AB_Testing
 */
class TWork_Spin_Wheel_AB_Testing
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
     * Get test variant for user
     *
     * @param int $user_id User ID.
     * @param int $test_id Test ID.
     * @return int|false Variant ID or false.
     */
    public function get_user_variant($user_id, $test_id)
    {
        // Check if user already has a variant assigned
        $variant_key = 'twork_ab_test_' . $test_id . '_variant';
        $variant = get_user_meta($user_id, $variant_key, true);

        if ($variant) {
            return absint($variant);
        }

        // Assign variant based on user ID (consistent assignment)
        $variants = $this->get_test_variants($test_id);
        if (empty($variants)) {
            return false;
        }

        // Use user ID to consistently assign variant
        $variant_index = $user_id % count($variants);
        $assigned_variant = $variants[$variant_index]['wheel_id'];

        // Store assignment
        update_user_meta($user_id, $variant_key, $assigned_variant);

        return $assigned_variant;
    }

    /**
     * Get test variants
     *
     * @param int $test_id Test ID.
     * @return array Variants.
     */
    public function get_test_variants($test_id)
    {
        $test = get_option('twork_ab_test_' . $test_id, array());

        return isset($test['variants']) ? $test['variants'] : array();
    }

    /**
     * Track conversion
     *
     * @param int $user_id User ID.
     * @param int $test_id Test ID.
     * @param string $action Action name.
     * @return bool True on success.
     */
    public function track_conversion($user_id, $test_id, $action = 'spin')
    {
        $variant = $this->get_user_variant($user_id, $test_id);
        if (!$variant) {
            return false;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'twork_spin_wheel_ab_tests';

        // Create table if needed
        $this->create_tracking_table();

        $wpdb->insert(
            $table_name,
            array(
                'test_id' => $test_id,
                'user_id' => $user_id,
                'variant_id' => $variant,
                'action' => sanitize_text_field($action),
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%d', '%d', '%s', '%s')
        );

        return true;
    }

    /**
     * Get test results
     *
     * @param int $test_id Test ID.
     * @return array Test results.
     */
    public function get_test_results($test_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'twork_spin_wheel_ab_tests';

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT variant_id, action, COUNT(*) as count
                 FROM {$table_name}
                 WHERE test_id = %d
                 GROUP BY variant_id, action",
                $test_id
            )
        );

        return $results;
    }

    /**
     * Create tracking table
     *
     * @return void
     */
    private function create_tracking_table()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'twork_spin_wheel_ab_tests';

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            test_id int(11) NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            variant_id int(11) NOT NULL,
            action varchar(50) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_test_id (test_id),
            KEY idx_user_id (user_id),
            KEY idx_variant_id (variant_id),
            KEY idx_action (action)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

