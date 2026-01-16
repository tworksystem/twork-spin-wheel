<?php
/**
 * AJAX Handlers Class
 * 
 * Handles all AJAX requests for the Spin Wheel System
 * 
 * @package TWork_Spin_Wheel
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class TWork_Spin_Wheel_Ajax
 */
class TWork_Spin_Wheel_Ajax
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
        $this->register_ajax_handlers();
    }

    /**
     * Register all AJAX handlers
     *
     * @return void
     */
    private function register_ajax_handlers()
    {
        // Public AJAX handlers (for logged-in users)
        add_action('wp_ajax_twork_spin_wheel_get_config', array($this, 'ajax_get_config'));
        add_action('wp_ajax_twork_spin_wheel_spin', array($this, 'ajax_spin'));
        add_action('wp_ajax_twork_spin_wheel_get_history', array($this, 'ajax_get_history'));

        // Admin AJAX handlers
        add_action('wp_ajax_twork_spin_wheel_get_stats', array($this, 'ajax_get_stats'));
        add_action('wp_ajax_twork_spin_wheel_delete_spin', array($this, 'ajax_delete_spin'));
        add_action('wp_ajax_twork_spin_wheel_bulk_action', array($this, 'ajax_bulk_action'));
        add_action('wp_ajax_twork_spin_wheel_export', array($this, 'ajax_export'));
        add_action('wp_ajax_twork_spin_wheel_get_analytics', array($this, 'ajax_get_analytics'));
    }

    /**
     * AJAX: Get wheel configuration
     *
     * @return void
     */
    public function ajax_get_config()
    {
        check_ajax_referer('twork_spin_wheel_frontend', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
        }

        if ($user_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid user ID.', 'twork-spin-wheel')));
        }

        $wheel_id = isset($_POST['wheel_id']) ? absint($_POST['wheel_id']) : 0;

        // Use REST API method to get config
        $rest_api = new TWork_Spin_Wheel_REST_API();
        $request = new WP_REST_Request('GET', '/twork/v1/spin-wheel/config/' . $user_id);
        if ($wheel_id > 0) {
            $request->set_param('wheel_id', $wheel_id);
        }

        $response = $rest_api->get_config($request);

        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => $response->get_error_message()));
        }

        wp_send_json_success($response->get_data());
    }

    /**
     * AJAX: Process spin
     *
     * @return void
     */
    public function ajax_spin()
    {
        check_ajax_referer('twork_spin_wheel_frontend', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
        }

        if ($user_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid user ID.', 'twork-spin-wheel')));
        }

        // Use REST API method to process spin
        $rest_api = new TWork_Spin_Wheel_REST_API();
        $request = new WP_REST_Request('POST', '/twork/v1/spin-wheel/spin');
        $request->set_param('user_id', $user_id);

        $response = $rest_api->spin($request);

        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => $response->get_error_message()));
        }

        wp_send_json_success($response->get_data());
    }

    /**
     * AJAX: Get spin history
     *
     * @return void
     */
    public function ajax_get_history()
    {
        check_ajax_referer('twork_spin_wheel_frontend', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
        }

        if ($user_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid user ID.', 'twork-spin-wheel')));
        }

        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 20;

        // Use REST API method
        $rest_api = new TWork_Spin_Wheel_REST_API();
        $request = new WP_REST_Request('GET', '/twork/v1/spin-wheel/prizes');
        $request->set_param('user_id', $user_id);
        $request->set_param('page', $page);
        $request->set_param('per_page', $per_page);

        $response = $rest_api->get_prizes($request);

        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => $response->get_error_message()));
        }

        wp_send_json_success($response->get_data());
    }

    /**
     * AJAX: Get statistics (admin only)
     *
     * @return void
     */
    public function ajax_get_stats()
    {
        check_ajax_referer('twork_spin_wheel_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'twork-spin-wheel')));
        }

        $wheel_id = isset($_POST['wheel_id']) ? absint($_POST['wheel_id']) : 0;
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';

        $analytics = new TWork_Spin_Wheel_Analytics();
        $stats = $analytics->get_statistics($wheel_id, $date_from, $date_to);

        wp_send_json_success($stats);
    }

    /**
     * AJAX: Delete spin (admin only)
     *
     * @return void
     */
    public function ajax_delete_spin()
    {
        check_ajax_referer('twork_spin_wheel_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'twork-spin-wheel')));
        }

        $spin_id = isset($_POST['spin_id']) ? absint($_POST['spin_id']) : 0;

        if (!$spin_id) {
            wp_send_json_error(array('message' => __('Invalid spin ID.', 'twork-spin-wheel')));
        }

        global $wpdb;
        $history_table = $this->database->get_history_table();

        $result = $wpdb->delete(
            $history_table,
            array('id' => $spin_id),
            array('%d')
        );

        if ($result) {
            wp_send_json_success(array('message' => __('Spin deleted successfully.', 'twork-spin-wheel')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete spin.', 'twork-spin-wheel')));
        }
    }

    /**
     * AJAX: Bulk action (admin only)
     *
     * @return void
     */
    public function ajax_bulk_action()
    {
        check_ajax_referer('twork_spin_wheel_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'twork-spin-wheel')));
        }

        $action = isset($_POST['bulk_action']) ? sanitize_text_field($_POST['bulk_action']) : '';
        $spin_ids = isset($_POST['spin_ids']) ? array_map('absint', $_POST['spin_ids']) : array();

        if (empty($action) || empty($spin_ids)) {
            wp_send_json_error(array('message' => __('Invalid action or IDs.', 'twork-spin-wheel')));
        }

        global $wpdb;
        $history_table = $this->database->get_history_table();

        $processed = 0;

        foreach ($spin_ids as $spin_id) {
            switch ($action) {
                case 'delete':
                    $wpdb->delete($history_table, array('id' => $spin_id), array('%d'));
                    $processed++;
                    break;
                case 'mark_claimed':
                    $wpdb->update(
                        $history_table,
                        array('is_claimed' => 1, 'claimed_at' => current_time('mysql')),
                        array('id' => $spin_id),
                        array('%d', '%s'),
                        array('%d')
                    );
                    $processed++;
                    break;
            }
        }

        wp_send_json_success(array(
            'message' => sprintf(__('%d items processed.', 'twork-spin-wheel'), $processed),
            'processed' => $processed,
        ));
    }

    /**
     * AJAX: Export data
     *
     * @return void
     */
    public function ajax_export()
    {
        check_ajax_referer('twork_spin_wheel_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'twork-spin-wheel')));
        }

        $export_type = isset($_POST['export_type']) ? sanitize_text_field($_POST['export_type']) : 'history';
        $args = isset($_POST['args']) ? $_POST['args'] : array();

        $export = new TWork_Spin_Wheel_Export();

        switch ($export_type) {
            case 'history':
                $export->export_history_csv($args);
                break;
            case 'prizes':
                $json = $export->export_prizes_json($args['wheel_id'] ?? 0);
                wp_send_json_success(array('data' => $json));
                break;
            case 'settings':
                $json = $export->export_settings_json();
                wp_send_json_success(array('data' => $json));
                break;
            default:
                wp_send_json_error(array('message' => __('Invalid export type.', 'twork-spin-wheel')));
        }
    }

    /**
     * AJAX: Get analytics data
     *
     * @return void
     */
    public function ajax_get_analytics()
    {
        check_ajax_referer('twork_spin_wheel_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'twork-spin-wheel')));
        }

        $wheel_id = isset($_POST['wheel_id']) ? absint($_POST['wheel_id']) : 0;
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';

        $analytics = new TWork_Spin_Wheel_Analytics();
        $stats = $analytics->get_statistics($wheel_id, $date_from, $date_to);

        wp_send_json_success($stats);
    }
}

