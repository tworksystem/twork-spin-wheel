<?php
/**
 * Notifications Class
 * 
 * Handles email and push notifications for the Spin Wheel System
 * 
 * @package TWork_Spin_Wheel
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class TWork_Spin_Wheel_Notifications
 */
class TWork_Spin_Wheel_Notifications
{
    /**
     * Send spin result notification
     *
     * @param int $user_id User ID.
     * @param array $spin_result Spin result data.
     * @return bool True on success, false on failure.
     */
    public function send_spin_result($user_id, $spin_result)
    {
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        $enabled = get_option('twork_spin_wheel_notify_on_spin', true);
        if (!$enabled) {
            return false;
        }

        $subject = get_option(
            'twork_spin_wheel_notify_subject',
            __('🎉 You Won a Prize!', 'twork-spin-wheel')
        );

        $message = $this->get_spin_result_message($user, $spin_result);

        return wp_mail(
            $user->user_email,
            $subject,
            $message,
            array('Content-Type: text/html; charset=UTF-8')
        );
    }

    /**
     * Get spin result email message
     *
     * @param WP_User $user User object.
     * @param array $spin_result Spin result data.
     * @return string Email message.
     */
    private function get_spin_result_message($user, $spin_result)
    {
        $prize = $spin_result['prize'] ?? array();
        $prize_label = $prize['label'] ?? __('Unknown Prize', 'twork-spin-wheel');
        $prize_type = $prize['type'] ?? 'points';
        $prize_value = $prize['value'] ?? '0';

        $template = get_option(
            'twork_spin_wheel_notify_template',
            $this->get_default_template()
        );

        $replacements = array(
            '{user_name}' => $user->display_name,
            '{prize_label}' => esc_html($prize_label),
            '{prize_type}' => esc_html($prize_type),
            '{prize_value}' => esc_html($prize_value),
            '{points_spent}' => absint($spin_result['points_spent'] ?? 0),
            '{points_remaining}' => absint($spin_result['points_remaining'] ?? 0),
            '{spins_left}' => absint($spin_result['spins_left'] ?? 0),
            '{site_name}' => get_bloginfo('name'),
        );

        $message = str_replace(array_keys($replacements), array_values($replacements), $template);

        return $message;
    }

    /**
     * Get default email template
     *
     * @return string Default template.
     */
    private function get_default_template()
    {
        return '
        <html>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <h2 style="color: #4CAF50;">🎉 Congratulations, {user_name}!</h2>
                <p>You just won a prize on the Spin Wheel!</p>
                <div style="background: #f4f4f4; padding: 15px; border-radius: 5px; margin: 20px 0;">
                    <h3 style="margin-top: 0;">Your Prize:</h3>
                    <p style="font-size: 18px; font-weight: bold; color: #2196F3;">{prize_label}</p>
                    <p>Type: {prize_type}</p>
                    <p>Value: {prize_value}</p>
                </div>
                <p><strong>Points Spent:</strong> {points_spent}</p>
                <p><strong>Points Remaining:</strong> {points_remaining}</p>
                <p><strong>Spins Left Today:</strong> {spins_left}</p>
                <hr style="border: none; border-top: 1px solid #ddd; margin: 20px 0;">
                <p style="color: #666; font-size: 12px;">Thank you for using {site_name}!</p>
            </div>
        </body>
        </html>
        ';
    }

    /**
     * Send daily limit reached notification
     *
     * @param int $user_id User ID.
     * @return bool True on success, false on failure.
     */
    public function send_daily_limit_reached($user_id)
    {
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        $enabled = get_option('twork_spin_wheel_notify_daily_limit', false);
        if (!$enabled) {
            return false;
        }

        $subject = __('Daily Spin Limit Reached', 'twork-spin-wheel');
        $message = sprintf(
            __('Hello %s, you have reached your daily spin limit. Come back tomorrow for more spins!', 'twork-spin-wheel'),
            $user->display_name
        );

        return wp_mail(
            $user->user_email,
            $subject,
            $message,
            array('Content-Type: text/html; charset=UTF-8')
        );
    }

    /**
     * Send insufficient points notification
     *
     * @param int $user_id User ID.
     * @param int $required_points Required points.
     * @return bool True on success, false on failure.
     */
    public function send_insufficient_points($user_id, $required_points)
    {
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        $enabled = get_option('twork_spin_wheel_notify_insufficient_points', false);
        if (!$enabled) {
            return false;
        }

        $subject = __('Insufficient Points for Spin', 'twork-spin-wheel');
        $message = sprintf(
            __('Hello %s, you need %d more points to spin the wheel. Keep earning points to unlock more spins!', 'twork-spin-wheel'),
            $user->display_name,
            $required_points
        );

        return wp_mail(
            $user->user_email,
            $subject,
            $message,
            array('Content-Type: text/html; charset=UTF-8')
        );
    }
}

