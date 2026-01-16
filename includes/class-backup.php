<?php
/**
 * Backup/Restore Class
 * 
 * Handles backup and restore functionality
 * 
 * @package TWork_Spin_Wheel
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class TWork_Spin_Wheel_Backup
 */
class TWork_Spin_Wheel_Backup
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
     * Create full backup
     *
     * @return array|WP_Error Backup data or WP_Error on failure.
     */
    public function create_backup()
    {
        global $wpdb;

        $backup = array(
            'version' => TWORK_SPIN_WHEEL_VERSION,
            'created_at' => current_time('mysql'),
            'site_url' => home_url(),
            'wheels' => array(),
            'prizes' => array(),
            'settings' => array(),
        );

        try {
            // Backup wheels
            $wheels_table = $this->database->get_wheels_table();
            $wheels = $wpdb->get_results("SELECT * FROM {$wheels_table}", ARRAY_A);
            $backup['wheels'] = $wheels;

            // Backup prizes
            $prizes_table = $this->database->get_prizes_table();
            $prizes = $wpdb->get_results("SELECT * FROM {$prizes_table}", ARRAY_A);
            $backup['prizes'] = $prizes;

            // Backup settings
            $backup['settings'] = $this->backup_settings();

        } catch (Exception $e) {
            return new WP_Error('backup_failed', $e->getMessage());
        }

        return $backup;
    }

    /**
     * Backup settings
     *
     * @return array Settings array.
     */
    private function backup_settings()
    {
        $settings = array();

        $option_keys = array(
            'twork_spin_wheel_enable_cache',
            'twork_spin_wheel_enable_logging',
            'twork_spin_wheel_enable_rate_limiting',
            'twork_spin_wheel_notify_on_spin',
            'twork_spin_wheel_log_retention_days',
        );

        foreach ($option_keys as $key) {
            $settings[$key] = get_option($key);
        }

        return $settings;
    }

    /**
     * Restore from backup
     *
     * @param array $backup_data Backup data.
     * @param bool $overwrite_existing Whether to overwrite existing data.
     * @return array|WP_Error Restore result or WP_Error on failure.
     */
    public function restore_backup($backup_data, $overwrite_existing = false)
    {
        global $wpdb;

        if (!isset($backup_data['wheels']) || !isset($backup_data['prizes'])) {
            return new WP_Error('invalid_backup', __('Invalid backup data format.', 'twork-spin-wheel'));
        }

        $restored = array(
            'wheels' => 0,
            'prizes' => 0,
            'settings' => 0,
        );

        try {
            // Restore wheels
            if (!empty($backup_data['wheels'])) {
                $wheels_table = $this->database->get_wheels_table();
                
                foreach ($backup_data['wheels'] as $wheel) {
                    if (!$overwrite_existing) {
                        $exists = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM {$wheels_table} WHERE id = %d",
                            $wheel['id']
                        ));
                        if ($exists > 0) {
                            continue;
                        }
                    }

                    // Remove id for insert
                    $wheel_id = $wheel['id'];
                    unset($wheel['id']);

                    if ($overwrite_existing) {
                        $wpdb->replace($wheels_table, $wheel);
                    } else {
                        $wpdb->insert($wheels_table, $wheel);
                    }
                    $restored['wheels']++;
                }
            }

            // Restore prizes
            if (!empty($backup_data['prizes'])) {
                $prizes_table = $this->database->get_prizes_table();
                
                foreach ($backup_data['prizes'] as $prize) {
                    if (!$overwrite_existing) {
                        $exists = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM {$prizes_table} WHERE id = %d",
                            $prize['id']
                        ));
                        if ($exists > 0) {
                            continue;
                        }
                    }

                    $prize_id = $prize['id'];
                    unset($prize['id']);

                    if ($overwrite_existing) {
                        $wpdb->replace($prizes_table, $prize);
                    } else {
                        $wpdb->insert($prizes_table, $prize);
                    }
                    $restored['prizes']++;
                }
            }

            // Restore settings
            if (!empty($backup_data['settings'])) {
                foreach ($backup_data['settings'] as $key => $value) {
                    update_option($key, $value);
                    $restored['settings']++;
                }
            }

        } catch (Exception $e) {
            return new WP_Error('restore_failed', $e->getMessage());
        }

        return $restored;
    }

    /**
     * Export backup to file
     *
     * @param array $backup_data Backup data.
     * @param string $filename Optional filename.
     * @return void
     */
    public function export_backup_file($backup_data, $filename = '')
    {
        if (empty($filename)) {
            $filename = 'spin-wheel-backup-' . date('Y-m-d-His') . '.json';
        }

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Pragma: no-cache');
        header('Expires: 0');

        echo wp_json_encode($backup_data, JSON_PRETTY_PRINT);
        exit;
    }
}

