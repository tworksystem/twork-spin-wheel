<?php
/**
 * Webhooks Class
 * 
 * Handles webhook functionality for external integrations
 * 
 * @package TWork_Spin_Wheel
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class TWork_Spin_Wheel_Webhooks
 */
class TWork_Spin_Wheel_Webhooks
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
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     *
     * @return void
     */
    private function init_hooks()
    {
        add_action('twork_spin_wheel_after_spin', array($this, 'trigger_webhooks'), 10, 2);
    }

    /**
     * Trigger webhooks after spin
     *
     * @param int $user_id User ID.
     * @param array $spin_result Spin result data.
     * @return void
     */
    public function trigger_webhooks($user_id, $spin_result)
    {
        $webhooks = $this->get_active_webhooks();

        foreach ($webhooks as $webhook) {
            if (!$this->should_trigger_webhook($webhook, $spin_result)) {
                continue;
            }

            $this->send_webhook($webhook, $user_id, $spin_result);
        }
    }

    /**
     * Get active webhooks
     *
     * @return array Webhooks list.
     */
    private function get_active_webhooks()
    {
        $webhooks = get_option('twork_spin_wheel_webhooks', array());

        if (!is_array($webhooks)) {
            return array();
        }

        return array_filter($webhooks, function($webhook) {
            return isset($webhook['active']) && $webhook['active'] === true;
        });
    }

    /**
     * Check if webhook should be triggered
     *
     * @param array $webhook Webhook configuration.
     * @param array $spin_result Spin result.
     * @return bool True if should trigger.
     */
    private function should_trigger_webhook($webhook, $spin_result)
    {
        // Check event type
        if (isset($webhook['event_type'])) {
            $event_type = $webhook['event_type'];
            if ($event_type === 'win' && empty($spin_result['prize'])) {
                return false;
            }
            if ($event_type === 'lose' && !empty($spin_result['prize'])) {
                return false;
            }
        }

        // Check prize type filter
        if (isset($webhook['prize_type_filter']) && !empty($webhook['prize_type_filter'])) {
            $prize_type = $spin_result['prize']['type'] ?? '';
            if ($prize_type !== $webhook['prize_type_filter']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Send webhook
     *
     * @param array $webhook Webhook configuration.
     * @param int $user_id User ID.
     * @param array $spin_result Spin result.
     * @return bool True on success.
     */
    private function send_webhook($webhook, $user_id, $spin_result)
    {
        $url = isset($webhook['url']) ? esc_url_raw($webhook['url']) : '';

        if (empty($url)) {
            return false;
        }

        $payload = array(
            'event' => 'spin_wheel_result',
            'timestamp' => current_time('mysql'),
            'user_id' => $user_id,
            'spin_result' => $spin_result,
            'site_url' => home_url(),
        );

        $args = array(
            'method' => 'POST',
            'timeout' => 15,
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode($payload),
        );

        // Add custom headers if specified
        if (isset($webhook['headers']) && is_array($webhook['headers'])) {
            $args['headers'] = array_merge($args['headers'], $webhook['headers']);
        }

        // Add authentication if specified
        if (isset($webhook['auth_type']) && isset($webhook['auth_value'])) {
            switch ($webhook['auth_type']) {
                case 'bearer':
                    $args['headers']['Authorization'] = 'Bearer ' . $webhook['auth_value'];
                    break;
                case 'basic':
                    $args['headers']['Authorization'] = 'Basic ' . base64_encode($webhook['auth_value']);
                    break;
            }
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            TWork_Spin_Wheel_Logger::log(
                'Webhook failed: ' . $response->get_error_message(),
                TWork_Spin_Wheel_Logger::LEVEL_ERROR,
                array('webhook_url' => $url, 'user_id' => $user_id)
            );
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code >= 200 && $status_code < 300) {
            TWork_Spin_Wheel_Logger::log(
                'Webhook sent successfully',
                TWork_Spin_Wheel_Logger::LEVEL_INFO,
                array('webhook_url' => $url, 'user_id' => $user_id, 'status' => $status_code)
            );
            return true;
        }

        return false;
    }

    /**
     * Add webhook
     *
     * @param array $webhook_data Webhook data.
     * @return int|false Webhook ID on success, false on failure.
     */
    public function add_webhook($webhook_data)
    {
        $webhooks = get_option('twork_spin_wheel_webhooks', array());

        if (!is_array($webhooks)) {
            $webhooks = array();
        }

        $webhook_id = uniqid('wh_', true);
        $webhook_data['id'] = $webhook_id;
        $webhook_data['created_at'] = current_time('mysql');

        $webhooks[] = $webhook_data;

        update_option('twork_spin_wheel_webhooks', $webhooks);

        return $webhook_id;
    }

    /**
     * Delete webhook
     *
     * @param string $webhook_id Webhook ID.
     * @return bool True on success.
     */
    public function delete_webhook($webhook_id)
    {
        $webhooks = get_option('twork_spin_wheel_webhooks', array());

        if (!is_array($webhooks)) {
            return false;
        }

        $webhooks = array_filter($webhooks, function($webhook) use ($webhook_id) {
            return isset($webhook['id']) && $webhook['id'] !== $webhook_id;
        });

        update_option('twork_spin_wheel_webhooks', array_values($webhooks));

        return true;
    }
}

