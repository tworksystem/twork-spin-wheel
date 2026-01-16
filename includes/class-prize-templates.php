<?php
/**
 * Prize Templates Class
 * 
 * Handles prize templates for quick prize creation
 * 
 * @package TWork_Spin_Wheel
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class TWork_Spin_Wheel_Prize_Templates
 */
class TWork_Spin_Wheel_Prize_Templates
{
    /**
     * Get predefined templates
     *
     * @return array Templates array.
     */
    public static function get_templates()
    {
        return array(
            'points_small' => array(
                'name' => __('Small Points Prize', 'twork-spin-wheel'),
                'prize_name' => __('100 Points', 'twork-spin-wheel'),
                'prize_type' => 'points',
                'prize_value' => '100',
                'probability_weight' => 30,
                'sector_color' => '#4CAF50',
                'text_color' => '#FFFFFF',
                'icon' => '💰',
            ),
            'points_medium' => array(
                'name' => __('Medium Points Prize', 'twork-spin-wheel'),
                'prize_name' => __('500 Points', 'twork-spin-wheel'),
                'prize_type' => 'points',
                'prize_value' => '500',
                'probability_weight' => 15,
                'sector_color' => '#2196F3',
                'text_color' => '#FFFFFF',
                'icon' => '💎',
            ),
            'points_large' => array(
                'name' => __('Large Points Prize', 'twork-spin-wheel'),
                'prize_name' => __('1000 Points', 'twork-spin-wheel'),
                'prize_type' => 'points',
                'prize_value' => '1000',
                'probability_weight' => 5,
                'sector_color' => '#9C27B0',
                'text_color' => '#FFFFFF',
                'icon' => '👑',
            ),
            'coupon_10' => array(
                'name' => __('10% Discount Coupon', 'twork-spin-wheel'),
                'prize_name' => __('10% Off', 'twork-spin-wheel'),
                'prize_type' => 'coupon',
                'prize_value' => '10%',
                'probability_weight' => 10,
                'sector_color' => '#FF9800',
                'text_color' => '#FFFFFF',
                'icon' => '🎫',
            ),
            'coupon_20' => array(
                'name' => __('20% Discount Coupon', 'twork-spin-wheel'),
                'prize_name' => __('20% Off', 'twork-spin-wheel'),
                'prize_type' => 'coupon',
                'prize_value' => '20%',
                'probability_weight' => 5,
                'sector_color' => '#F44336',
                'text_color' => '#FFFFFF',
                'icon' => '🎁',
            ),
            'try_again' => array(
                'name' => __('Try Again', 'twork-spin-wheel'),
                'prize_name' => __('Try Again', 'twork-spin-wheel'),
                'prize_type' => 'message',
                'prize_value' => '',
                'probability_weight' => 20,
                'sector_color' => '#9E9E9E',
                'text_color' => '#FFFFFF',
                'icon' => '🔄',
            ),
            'jackpot' => array(
                'name' => __('Jackpot', 'twork-spin-wheel'),
                'prize_name' => __('Jackpot!', 'twork-spin-wheel'),
                'prize_type' => 'points',
                'prize_value' => '5000',
                'probability_weight' => 1,
                'sector_color' => '#FFD700',
                'text_color' => '#000000',
                'icon' => '🎰',
            ),
        );
    }

    /**
     * Apply template to create prize
     *
     * @param string $template_id Template ID.
     * @param int $wheel_id Wheel ID.
     * @return int|false Prize ID on success, false on failure.
     */
    public static function apply_template($template_id, $wheel_id)
    {
        $templates = self::get_templates();

        if (!isset($templates[$template_id])) {
            return false;
        }

        $template = $templates[$template_id];

        global $wpdb;
        $database = new TWork_Spin_Wheel_Database();
        $prizes_table = $database->get_prizes_table();

        $result = $wpdb->insert(
            $prizes_table,
            array(
                'wheel_id' => $wheel_id,
                'prize_name' => $template['prize_name'],
                'prize_type' => $template['prize_type'],
                'prize_value' => $template['prize_value'],
                'probability_weight' => $template['probability_weight'],
                'sector_color' => $template['sector_color'],
                'text_color' => $template['text_color'],
                'icon' => $template['icon'],
                'is_active' => 1,
            ),
            array('%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%d')
        );

        if ($result) {
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Get template by ID
     *
     * @param string $template_id Template ID.
     * @return array|false Template data or false.
     */
    public static function get_template($template_id)
    {
        $templates = self::get_templates();
        return isset($templates[$template_id]) ? $templates[$template_id] : false;
    }
}

