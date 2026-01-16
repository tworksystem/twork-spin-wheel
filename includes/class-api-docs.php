<?php
/**
 * API Documentation Class
 * 
 * Generates API documentation endpoint
 * 
 * @package TWork_Spin_Wheel
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class TWork_Spin_Wheel_API_Docs
 */
class TWork_Spin_Wheel_API_Docs
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('rest_api_init', array($this, 'register_docs_endpoint'));
    }

    /**
     * Register API documentation endpoint
     *
     * @return void
     */
    public function register_docs_endpoint()
    {
        register_rest_route('twork/v1', '/spin-wheel/docs', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_documentation'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Get API documentation
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_documentation($request)
    {
        $base_url = rest_url('twork/v1/spin-wheel');

        $docs = array(
            'version' => TWORK_SPIN_WHEEL_VERSION,
            'base_url' => $base_url,
            'endpoints' => array(
                array(
                    'method' => 'GET',
                    'endpoint' => $base_url . '/config/{user_id}',
                    'description' => __('Get spin wheel configuration for a user', 'twork-spin-wheel'),
                    'parameters' => array(
                        'user_id' => array(
                            'type' => 'integer',
                            'required' => true,
                            'description' => __('User ID', 'twork-spin-wheel'),
                        ),
                    ),
                    'response' => array(
                        'success' => 'boolean',
                        'data' => array(
                            'wheel_id' => 'integer',
                            'title' => 'string',
                            'description' => 'string',
                            'prizes' => 'array',
                            'max_spins_per_day' => 'integer',
                            'points_per_spin' => 'integer',
                            'can_spin' => 'boolean',
                            'spins_left' => 'integer',
                        ),
                    ),
                ),
                array(
                    'method' => 'POST',
                    'endpoint' => $base_url . '/spin',
                    'description' => __('Process a spin for a user', 'twork-spin-wheel'),
                    'parameters' => array(
                        'user_id' => array(
                            'type' => 'integer',
                            'required' => true,
                            'description' => __('User ID', 'twork-spin-wheel'),
                        ),
                    ),
                    'request_body' => array(
                        'user_id' => 'integer (required)',
                    ),
                    'response' => array(
                        'success' => 'boolean',
                        'data' => array(
                            'spin_id' => 'integer',
                            'prize' => 'object',
                            'points_spent' => 'integer',
                            'points_remaining' => 'integer',
                            'spins_left' => 'integer',
                        ),
                    ),
                ),
                array(
                    'method' => 'GET',
                    'endpoint' => $base_url . '/prizes',
                    'description' => __('Get user spin history', 'twork-spin-wheel'),
                    'parameters' => array(
                        'user_id' => array(
                            'type' => 'integer',
                            'required' => true,
                            'description' => __('User ID', 'twork-spin-wheel'),
                        ),
                        'page' => array(
                            'type' => 'integer',
                            'required' => false,
                            'default' => 1,
                            'description' => __('Page number', 'twork-spin-wheel'),
                        ),
                        'per_page' => array(
                            'type' => 'integer',
                            'required' => false,
                            'default' => 20,
                            'description' => __('Items per page', 'twork-spin-wheel'),
                        ),
                    ),
                ),
                array(
                    'method' => 'GET',
                    'endpoint' => $base_url . '/banner',
                    'description' => __('Get banner content', 'twork-spin-wheel'),
                    'response' => array(
                        'success' => 'boolean',
                        'data' => array(
                            'has_banner' => 'boolean',
                            'content' => 'string',
                        ),
                    ),
                ),
            ),
            'authentication' => array(
                'type' => 'none',
                'note' => __('Currently, endpoints are publicly accessible. Consider implementing authentication for production use.', 'twork-spin-wheel'),
            ),
            'rate_limiting' => array(
                'enabled' => true,
                'limit' => __('30 requests per minute per IP/user', 'twork-spin-wheel'),
            ),
            'error_codes' => array(
                'invalid_user' => __('Invalid user ID provided', 'twork-spin-wheel'),
                'no_active_wheel' => __('No active spin wheel configuration found', 'twork-spin-wheel'),
                'max_spins_reached' => __('User has reached daily spin limit', 'twork-spin-wheel'),
                'insufficient_points' => __('User does not have enough points', 'twork-spin-wheel'),
                'rate_limit_exceeded' => __('Too many requests. Please try again later', 'twork-spin-wheel'),
            ),
        );

        return new WP_REST_Response($docs, 200);
    }
}

