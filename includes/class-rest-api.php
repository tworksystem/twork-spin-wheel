<?php
/**
 * REST API Class
 * 
 * Handles all REST API endpoints for the Spin Wheel System
 */

if (!defined('ABSPATH')) {
    exit;
}

class TWork_Spin_Wheel_REST_API
{
    /**
     * Database instance
     */
    private $database;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->database = new TWork_Spin_Wheel_Database();
        $this->register_routes();
    }

    /**
     * Register all REST API routes
     */
    public function register_routes()
    {
        add_action('rest_api_init', array($this, 'register_rest_routes'), 5);
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes()
    {
        // Get wheel configuration for a specific user
        register_rest_route('twork/v1', '/spin-wheel/config/(?P<user_id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_config'),
            'permission_callback' => '__return_true',
            'args' => array(
                'user_id' => array(
                    'required' => true,
                    'validate_callback' => function ($param) {
                        return absint($param) > 0;
                    }
                ),
            ),
        ));

        // Get specific wheel by ID
        register_rest_route('twork/v1', '/spin-wheel/wheel/(?P<wheel_id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_wheel'),
            'permission_callback' => '__return_true',
            'args' => array(
                'wheel_id' => array(
                    'validate_callback' => function ($param) {
                        return absint($param) > 0;
                    }
                ),
            ),
        ));

        // Process a spin for a specific user
        register_rest_route('twork/v1', '/spin-wheel/spin', array(
            'methods' => 'POST',
            'callback' => array($this, 'spin'),
            'permission_callback' => '__return_true',
            'args' => array(
                'user_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'validate_callback' => function ($param) {
                        return absint($param) > 0;
                    }
                ),
            ),
        ));

        // Get spin history / prizes for a specific user
        register_rest_route('twork/v1', '/spin-wheel/prizes', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_prizes'),
            'permission_callback' => '__return_true',
            'args' => array(
                'user_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'validate_callback' => function ($param) {
                        return absint($param) > 0;
                    }
                ),
                'page' => array(
                    'default' => 1,
                    'type' => 'integer',
                ),
                'per_page' => array(
                    'default' => 20,
                    'type' => 'integer',
                ),
            ),
        ));

        // Get banner content
        register_rest_route('twork/v1', '/spin-wheel/banner', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_banner'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * REST API: Get Spin Wheel Configuration
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function get_config($request)
    {
        // Security check
        $user_id = get_current_user_id();
        if (!$user_id) {
            $user_id = absint($request->get_param('user_id'));
        }

        if ($user_id <= 0 || !TWork_Spin_Wheel_Security::validate_user_id($user_id)) {
            return new WP_Error(
                'invalid_user',
                __('Invalid user ID for spin wheel configuration.', 'twork-spin-wheel'),
                array('status' => 400)
            );
        }

        // Rate limiting
        $identifier = 'config_' . $user_id . '_' . TWork_Spin_Wheel_Security::get_client_ip();
        if (TWork_Spin_Wheel_Security::is_rate_limited($identifier, 30, 60)) {
            return new WP_Error(
                'rate_limit_exceeded',
                __('Too many requests. Please try again later.', 'twork-spin-wheel'),
                array('status' => 429)
            );
        }

        global $wpdb;
        $wheels_table = $this->database->get_wheels_table();
        $prizes_table = $this->database->get_prizes_table();

        // Get active configuration
        $config = null;
        try {
            $config = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wheels_table} WHERE is_active = %d AND is_default = %d ORDER BY id DESC LIMIT 1",
                    1,
                    1
                )
            );

            if ($wpdb->last_error) {
                throw new Exception($wpdb->last_error);
            }

            // If no default, get any active wheel
            if (!$config) {
                $config = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT * FROM {$wheels_table} WHERE is_active = %d ORDER BY id DESC LIMIT 1",
                        1
                    )
                );

                if ($wpdb->last_error) {
                    throw new Exception($wpdb->last_error);
                }
            }
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('T-Work Spin Wheel Config API Error: ' . $e->getMessage());
            }
            return new WP_Error(
                'database_error',
                __('Database error occurred while fetching configuration.', 'twork-spin-wheel'),
                array('status' => 500)
            );
        }

        if (!$config) {
            return new WP_Error(
                'no_active_wheel',
                __('No active spin wheel configuration found.', 'twork-spin-wheel'),
                array('status' => 404)
            );
        }

        // Get prizes
        $prizes = array();
        try {
            $prizes = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$prizes_table} WHERE wheel_id = %d AND is_active = %d ORDER BY display_order ASC, id ASC",
                    $config->id,
                    1
                )
            );

            if ($wpdb->last_error) {
                throw new Exception($wpdb->last_error);
            }

            if (!is_array($prizes)) {
                $prizes = array();
            }
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('T-Work Spin Wheel Config API Error (prizes): ' . $e->getMessage());
            }
            $prizes = array();
        }

        // Format prizes
        $formatted_prizes = array();
        $total_weight = 0;
        foreach ($prizes as $prize) {
            if (!is_object($prize)) {
                continue;
            }
            
            $prize_weight = isset($prize->probability_weight) ? absint($prize->probability_weight) : 0;
            $total_weight += $prize_weight;
            
            $formatted_prizes[] = array(
                'id' => isset($prize->id) ? absint($prize->id) : 0,
                'label' => isset($prize->prize_name) ? sanitize_text_field($prize->prize_name) : '',
                'type' => isset($prize->prize_type) ? sanitize_text_field($prize->prize_type) : 'points',
                'value' => isset($prize->prize_value) ? sanitize_text_field($prize->prize_value) : '0',
                'probability' => $prize_weight,
                'color' => isset($prize->sector_color) ? sanitize_hex_color($prize->sector_color) : '#FF6B6B',
                'text_color' => isset($prize->text_color) ? sanitize_hex_color($prize->text_color) : '#FFFFFF',
                'icon' => isset($prize->icon) ? sanitize_text_field($prize->icon) : '🎁',
            );
        }

        // Calculate probability percentages
        if ($total_weight > 0) {
            foreach ($formatted_prizes as $key => $prize) {
                $formatted_prizes[$key]['probability'] = round(($prize['probability'] / $total_weight) * 100, 2);
            }
        }

        // Check if user can spin
        $can_spin = TWork_Spin_Wheel_Helpers::can_user_spin_today($user_id, $config->id);
        $spins_left = TWork_Spin_Wheel_Helpers::get_user_spins_left_today($user_id, $config->id);

        // Get user's spin history
        $spin_history = TWork_Spin_Wheel_Helpers::get_user_spin_history($user_id, 10);

        // Format response
        $config_id = isset($config->id) ? absint($config->id) : 0;
        $config_name = isset($config->name) ? sanitize_text_field($config->name) : 'Spin Wheel';
        $config_description = isset($config->description) ? wp_kses_post($config->description) : '';
        $config_daily_limit = isset($config->daily_limit) ? absint($config->daily_limit) : 3;
        $config_cost_points = isset($config->cost_points) ? absint($config->cost_points) : 100;
        $config_bg_color = isset($config->background_color) ? sanitize_hex_color($config->background_color) : '#FFFFFF';
        $config_border_color = isset($config->border_color) ? sanitize_hex_color($config->border_color) : '#000000';
        $config_center_color = isset($config->center_color) ? sanitize_hex_color($config->center_color) : '#FFD700';
        $config_enable_animation = isset($config->enable_animation) ? (bool) $config->enable_animation : false;
        $config_enable_sound = isset($config->enable_sound) ? (bool) $config->enable_sound : false;
        $config_animation_duration = isset($config->animation_duration) ? absint($config->animation_duration) : 5;

        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'wheel_id' => $config_id,
                'title' => $config_name,
                'description' => $config_description,
                'prizes' => $formatted_prizes,
                'max_spins_per_day' => $config_daily_limit,
                'points_per_spin' => $config_cost_points,
                'can_spin' => $can_spin,
                'spins_left' => $spins_left,
                'spin_history' => $spin_history,
                'colors' => array(
                    'primary' => $config_bg_color,
                    'secondary' => $config_border_color,
                    'text' => $config_center_color,
                ),
                'settings' => array(
                    'show_confetti' => $config_enable_animation,
                    'show_sound' => $config_enable_sound,
                    'animation_duration' => $config_animation_duration,
                ),
            ),
        ), 200);
    }

    /**
     * REST API: Get specific wheel by ID
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function get_wheel($request)
    {
        // Similar to get_config but for specific wheel_id
        return $this->get_config($request);
    }

    /**
     * REST API: Process User Spin
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function spin($request)
    {
        global $wpdb;

        // Determine user ID
        $user_id = get_current_user_id();
        if (!$user_id) {
            $user_id = absint($request->get_param('user_id'));
        }

        if ($user_id <= 0) {
            return new WP_Error(
                'invalid_user',
                __('Invalid user ID for spin wheel.', 'twork-spin-wheel'),
                array('status' => 400)
            );
        }

        // Get active configuration
        $wheels_table = $this->database->get_wheels_table();
        $prizes_table = $this->database->get_prizes_table();
        $history_table = $this->database->get_history_table();

        $config = null;
        try {
            $config = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wheels_table} WHERE is_active = %d AND is_default = %d ORDER BY id DESC LIMIT 1",
                    1,
                    1
                )
            );

            if ($wpdb->last_error) {
                throw new Exception($wpdb->last_error);
            }

            if (!$config) {
                $config = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT * FROM {$wheels_table} WHERE is_active = %d ORDER BY id DESC LIMIT 1",
                        1
                    )
                );

                if ($wpdb->last_error) {
                    throw new Exception($wpdb->last_error);
                }
            }
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('T-Work Spin Wheel Spin API Error: ' . $e->getMessage());
            }
            return new WP_Error(
                'database_error',
                __('Database error occurred while fetching configuration.', 'twork-spin-wheel'),
                array('status' => 500)
            );
        }

        if (!$config || !isset($config->id)) {
            return new WP_Error(
                'no_active_wheel',
                __('No active spin wheel configuration found.', 'twork-spin-wheel'),
                array('status' => 404)
            );
        }

        $config_id = absint($config->id);
        $points_required = isset($config->cost_points) ? absint($config->cost_points) : 100;

        // Check if user can spin
        if (!TWork_Spin_Wheel_Helpers::can_user_spin_today($user_id, $config_id)) {
            return new WP_Error(
                'max_spins_reached',
                __('You have reached the maximum number of spins for today.', 'twork-spin-wheel'),
                array('status' => 403)
            );
        }

        // Check if user has enough points
        $user_points = absint(get_user_meta($user_id, 'twork_reward_points', true));

        if ($user_points < $points_required) {
            return new WP_Error(
                'insufficient_points',
                sprintf(
                    __('You need %d points to spin. You have %d points.', 'twork-spin-wheel'),
                    $points_required,
                    $user_points
                ),
                array('status' => 403)
            );
        }

        // Get prizes
        $prizes = array();
        try {
            $prizes = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$prizes_table} WHERE wheel_id = %d AND is_active = %d ORDER BY display_order ASC, id ASC",
                    $config_id,
                    1
                )
            );

            if ($wpdb->last_error) {
                throw new Exception($wpdb->last_error);
            }

            if (!is_array($prizes)) {
                $prizes = array();
            }
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('T-Work Spin Wheel Spin API Error (prizes): ' . $e->getMessage());
            }
            return new WP_Error(
                'database_error',
                __('Database error occurred while fetching prizes.', 'twork-spin-wheel'),
                array('status' => 500)
            );
        }

        if (empty($prizes)) {
            return new WP_Error(
                'no_prizes',
                __('No prizes available.', 'twork-spin-wheel'),
                array('status' => 500)
            );
        }

        // Format prizes for probability selection
        $formatted_prizes = array();
        foreach ($prizes as $prize) {
            if (!is_object($prize)) {
                continue;
            }
            
            $formatted_prizes[] = array(
                'id' => isset($prize->id) ? absint($prize->id) : 0,
                'label' => isset($prize->prize_name) ? sanitize_text_field($prize->prize_name) : '',
                'type' => isset($prize->prize_type) ? sanitize_text_field($prize->prize_type) : 'points',
                'value' => isset($prize->prize_value) ? sanitize_text_field($prize->prize_value) : '0',
                'probability' => isset($prize->probability_weight) ? absint($prize->probability_weight) : 0,
                'color' => isset($prize->sector_color) ? sanitize_hex_color($prize->sector_color) : '#FF6B6B',
                'icon' => isset($prize->icon) ? sanitize_text_field($prize->icon) : '🎁',
            );
        }

        // Select prize based on probability
        $won_prize = TWork_Spin_Wheel_Helpers::select_prize_by_probability($formatted_prizes);

        if (!$won_prize) {
            return new WP_Error(
                'prize_selection_failed',
                __('Failed to select a prize. Please try again.', 'twork-spin-wheel'),
                array('status' => 500)
            );
        }

        // Deduct points
        $new_points = $user_points - $points_required;
        update_user_meta($user_id, 'twork_reward_points', $new_points);

        // Send notification before awarding (optional)
        $notifications = new TWork_Spin_Wheel_Notifications();

        // Award prize
        $prize_awarded = false;
        $prize_details = array();
        $prize_points = 0;

        if ($won_prize['type'] === 'points') {
            $prize_points = absint($won_prize['value']);
            $final_points = $new_points + $prize_points;
            update_user_meta($user_id, 'twork_reward_points', $final_points);
            $prize_awarded = true;
            $prize_details = array(
                'type' => 'points',
                'value' => $prize_points,
                'final_balance' => $final_points,
            );
        } elseif ($won_prize['type'] === 'coupon') {
            $coupon_code = TWork_Spin_Wheel_Helpers::generate_unique_coupon_code($won_prize['label']);
            $coupon_id = TWork_Spin_Wheel_Helpers::create_woocommerce_coupon($coupon_code, $won_prize);

            if ($coupon_id) {
                $prize_awarded = true;
                $prize_details = array(
                    'type' => 'coupon',
                    'code' => $coupon_code,
                    'discount' => sanitize_text_field($won_prize['value']),
                    'coupon_id' => $coupon_id,
                );
            }
        } elseif ($won_prize['type'] === 'product') {
            $prize_awarded = true;
            $prize_details = array(
                'type' => 'product',
                'product_id' => absint($won_prize['value']),
                'product_name' => sanitize_text_field($won_prize['label']),
            );
        } elseif ($won_prize['type'] === 'message') {
            $prize_awarded = true;
            $prize_details = array(
                'type' => 'message',
                'message' => sanitize_text_field($won_prize['label']),
            );
        }

        // Record spin in database
        $spin_id = 0;
        try {
            $result = $wpdb->insert(
                $history_table,
                array(
                    'user_id' => $user_id,
                    'wheel_id' => $config_id,
                    'prize_id' => absint($won_prize['id']),
                    'prize_name' => sanitize_text_field($won_prize['label']),
                    'prize_type' => sanitize_text_field($won_prize['type']),
                    'prize_value' => sanitize_text_field($won_prize['value']),
                    'cost_points' => $points_required,
                    'status' => 'won',
                    'is_claimed' => $prize_awarded ? 1 : 0,
                    'claimed_at' => $prize_awarded ? current_time('mysql') : null,
                    'ip_address' => TWork_Spin_Wheel_Helpers::get_client_ip(),
                    'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '',
                    'created_at' => current_time('mysql'),
                ),
                array('%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s')
            );

            if ($wpdb->last_error) {
                throw new Exception($wpdb->last_error);
            }

            if ($result === false) {
                throw new Exception(__('Failed to record spin in database.', 'twork-spin-wheel'));
            }

            $spin_id = $wpdb->insert_id;
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('T-Work Spin Wheel Spin API Error (history): ' . $e->getMessage());
            }
        }

        // Update statistics
        try {
            TWork_Spin_Wheel_Helpers::update_spin_wheel_stats($config_id);
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('T-Work Spin Wheel Stats Update Error: ' . $e->getMessage());
            }
        }

        // Clear cache
        TWork_Spin_Wheel_Cache::invalidate_user_spins($user_id, $config_id);
        TWork_Spin_Wheel_Cache::invalidate_wheel_config($config_id);

        // Send notification
        try {
            $notifications->send_spin_result($user_id, array(
                'prize' => $won_prize,
                'points_spent' => $points_required,
                'points_remaining' => $new_points + $prize_points,
                'spins_left' => TWork_Spin_Wheel_Helpers::get_user_spins_left_today($user_id, $config_id),
            ));
        } catch (Exception $e) {
            TWork_Spin_Wheel_Logger::log('Failed to send notification: ' . $e->getMessage(), TWork_Spin_Wheel_Logger::LEVEL_WARNING);
        }

        // Log successful spin
        TWork_Spin_Wheel_Logger::log(
            sprintf('User %d spun wheel %d and won prize: %s', $user_id, $config_id, $won_prize['label']),
            TWork_Spin_Wheel_Logger::LEVEL_INFO,
            array('user_id' => $user_id, 'wheel_id' => $config_id, 'prize' => $won_prize)
        );

        // Trigger webhooks
        do_action('twork_spin_wheel_after_spin', $user_id, array(
            'spin_id' => $spin_id,
            'wheel_id' => $config_id,
            'prize' => $won_prize,
            'prize_details' => $prize_details,
            'points_spent' => $points_required,
            'points_remaining' => $new_points + $prize_points,
        ));

        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'spin_id' => $spin_id,
                'prize' => array(
                    'id' => absint($won_prize['id']),
                    'label' => sanitize_text_field($won_prize['label']),
                    'type' => sanitize_text_field($won_prize['type']),
                    'value' => sanitize_text_field($won_prize['value']),
                    'color' => sanitize_hex_color($won_prize['color']),
                    'icon' => sanitize_text_field($won_prize['icon']),
                    'details' => $prize_details,
                ),
                'points_spent' => $points_required,
                'points_remaining' => $new_points + $prize_points,
                'spins_left' => TWork_Spin_Wheel_Helpers::get_user_spins_left_today($user_id, $config_id),
                'prize_awarded' => $prize_awarded,
            ),
        ), 200);
    }

    /**
     * REST API: Get User's Prize History
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function get_prizes($request)
    {
        // Determine user ID
        $user_id = get_current_user_id();
        if (!$user_id) {
            $user_id = absint($request->get_param('user_id'));
        }

        if ($user_id <= 0) {
            return new WP_Error(
                'invalid_user',
                __('Invalid user ID for spin wheel history.', 'twork-spin-wheel'),
                array('status' => 400)
            );
        }

        $page = absint($request->get_param('page')) ?: 1;
        $per_page = absint($request->get_param('per_page')) ?: 20;
        $offset = ($page - 1) * $per_page;

        global $wpdb;
        $history_table = $this->database->get_history_table();

        // Get total count
        $total = 0;
        $spins = array();
        
        try {
            $total = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$history_table} WHERE user_id = %d",
                    $user_id
                )
            );

            if ($wpdb->last_error) {
                throw new Exception($wpdb->last_error);
            }

            $total = absint($total);

            // Get spins
            if ($total > 0) {
                $spins = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT * FROM {$history_table} 
                        WHERE user_id = %d 
                        ORDER BY created_at DESC 
                        LIMIT %d OFFSET %d",
                        $user_id,
                        $per_page,
                        $offset
                    )
                );

                if ($wpdb->last_error) {
                    throw new Exception($wpdb->last_error);
                }

                if (!is_array($spins)) {
                    $spins = array();
                }
            }
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('T-Work Spin Wheel Get Prizes API Error: ' . $e->getMessage());
            }
            return new WP_Error(
                'database_error',
                __('Database error occurred while fetching history.', 'twork-spin-wheel'),
                array('status' => 500)
            );
        }

        $formatted_spins = array();
        foreach ($spins as $spin) {
            if (!is_object($spin)) {
                continue;
            }
            
            $spin_id = isset($spin->id) ? absint($spin->id) : 0;
            $prize_name = isset($spin->prize_name) ? sanitize_text_field($spin->prize_name) : '';
            $prize_type = isset($spin->prize_type) ? sanitize_text_field($spin->prize_type) : '';
            $prize_value = isset($spin->prize_value) ? sanitize_text_field($spin->prize_value) : '';
            $cost_points = isset($spin->cost_points) ? absint($spin->cost_points) : 0;
            $created_at = isset($spin->created_at) ? $spin->created_at : '';
            $formatted_date = $created_at ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($created_at)) : '';
            
            $formatted_spins[] = array(
                'id' => $spin_id,
                'prize_label' => $prize_name,
                'prize_type' => $prize_type,
                'prize_value' => $prize_value,
                'prize_details' => array(
                    'type' => $prize_type,
                    'value' => $prize_value,
                ),
                'points_spent' => $cost_points,
                'spin_date' => $created_at,
                'spin_date_formatted' => $formatted_date,
            );
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'spins' => $formatted_spins,
                'pagination' => array(
                    'total' => absint($total),
                    'page' => $page,
                    'per_page' => $per_page,
                    'total_pages' => ceil($total / $per_page),
                ),
            ),
        ), 200);
    }

    /**
     * REST API: Get Banner Content
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_banner($request)
    {
        global $wpdb;
        $wheels_table = $this->database->get_wheels_table();

        // Get active wheel's banner content
        $config = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT banner_content FROM {$wheels_table} WHERE is_active = %d AND is_default = %d ORDER BY id DESC LIMIT 1",
                1,
                1
            )
        );

        $banner_content = '';
        if ($config && isset($config->banner_content)) {
            $banner_content = wp_kses_post($config->banner_content);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'has_banner' => !empty($banner_content),
                'content' => $banner_content,
            ),
        ), 200);
    }
}

