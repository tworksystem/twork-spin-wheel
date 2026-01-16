<?php
/**
 * Bulk Operations Class
 * 
 * Handles bulk operations for prizes and spins
 * 
 * @package TWork_Spin_Wheel
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class TWork_Spin_Wheel_Bulk_Operations
 */
class TWork_Spin_Wheel_Bulk_Operations
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
     * Bulk delete prizes
     *
     * @param array $prize_ids Prize IDs.
     * @return array Result with count.
     */
    public function bulk_delete_prizes($prize_ids)
    {
        global $wpdb;
        $prizes_table = $this->database->get_prizes_table();

        $deleted = 0;
        $failed = 0;

        foreach ($prize_ids as $prize_id) {
            $prize_id = absint($prize_id);
            if ($prize_id <= 0) {
                $failed++;
                continue;
            }

            $result = $wpdb->delete(
                $prizes_table,
                array('id' => $prize_id),
                array('%d')
            );

            if ($result) {
                $deleted++;
            } else {
                $failed++;
            }
        }

        return array(
            'deleted' => $deleted,
            'failed' => $failed,
            'total' => count($prize_ids),
        );
    }

    /**
     * Bulk activate/deactivate prizes
     *
     * @param array $prize_ids Prize IDs.
     * @param int $status Status (1 = active, 0 = inactive).
     * @return array Result with count.
     */
    public function bulk_toggle_prizes($prize_ids, $status)
    {
        global $wpdb;
        $prizes_table = $this->database->get_prizes_table();

        $updated = 0;
        $failed = 0;

        foreach ($prize_ids as $prize_id) {
            $prize_id = absint($prize_id);
            if ($prize_id <= 0) {
                $failed++;
                continue;
            }

            $result = $wpdb->update(
                $prizes_table,
                array('is_active' => absint($status)),
                array('id' => $prize_id),
                array('%d'),
                array('%d')
            );

            if ($result !== false) {
                $updated++;
            } else {
                $failed++;
            }
        }

        return array(
            'updated' => $updated,
            'failed' => $failed,
            'total' => count($prize_ids),
        );
    }

    /**
     * Bulk update prize probability
     *
     * @param array $prize_ids Prize IDs.
     * @param int $probability New probability weight.
     * @return array Result with count.
     */
    public function bulk_update_probability($prize_ids, $probability)
    {
        global $wpdb;
        $prizes_table = $this->database->get_prizes_table();

        $updated = 0;
        $failed = 0;
        $probability = absint($probability);

        foreach ($prize_ids as $prize_id) {
            $prize_id = absint($prize_id);
            if ($prize_id <= 0) {
                $failed++;
                continue;
            }

            $result = $wpdb->update(
                $prizes_table,
                array('probability_weight' => $probability),
                array('id' => $prize_id),
                array('%d'),
                array('%d')
            );

            if ($result !== false) {
                $updated++;
            } else {
                $failed++;
            }
        }

        return array(
            'updated' => $updated,
            'failed' => $failed,
            'total' => count($prize_ids),
        );
    }

    /**
     * Bulk delete spins
     *
     * @param array $spin_ids Spin IDs.
     * @return array Result with count.
     */
    public function bulk_delete_spins($spin_ids)
    {
        global $wpdb;
        $history_table = $this->database->get_history_table();

        $deleted = 0;
        $failed = 0;

        foreach ($spin_ids as $spin_id) {
            $spin_id = absint($spin_id);
            if ($spin_id <= 0) {
                $failed++;
                continue;
            }

            $result = $wpdb->delete(
                $history_table,
                array('id' => $spin_id),
                array('%d')
            );

            if ($result) {
                $deleted++;
            } else {
                $failed++;
            }
        }

        return array(
            'deleted' => $deleted,
            'failed' => $failed,
            'total' => count($spin_ids),
        );
    }

    /**
     * Bulk mark spins as claimed
     *
     * @param array $spin_ids Spin IDs.
     * @return array Result with count.
     */
    public function bulk_mark_claimed($spin_ids)
    {
        global $wpdb;
        $history_table = $this->database->get_history_table();

        $updated = 0;
        $failed = 0;

        foreach ($spin_ids as $spin_id) {
            $spin_id = absint($spin_id);
            if ($spin_id <= 0) {
                $failed++;
                continue;
            }

            $result = $wpdb->update(
                $history_table,
                array(
                    'is_claimed' => 1,
                    'claimed_at' => current_time('mysql'),
                ),
                array('id' => $spin_id),
                array('%d', '%s'),
                array('%d')
            );

            if ($result !== false) {
                $updated++;
            } else {
                $failed++;
            }
        }

        return array(
            'updated' => $updated,
            'failed' => $failed,
            'total' => count($spin_ids),
        );
    }
}

