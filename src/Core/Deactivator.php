<?php
/**
 * Plugin Deactivation class
 *
 * Handles all tasks required during plugin deactivation
 *
 * @package RSD_Finance_Panel
 * @subpackage Core
 */

namespace RSD\FinancePanel\Core;

defined('ABSPATH') || exit;

class Deactivator
{
    /**
     * Run deactivation tasks
     *
     * @return void
     */
    public static function deactivate()
    {
        // Flush rewrite rules
        flush_rewrite_rules();

        // Clear scheduled cron hooks
        self::clear_cron_events();
    }

    /**
     * Clear any scheduled cron events
     *
     * @return void
     */
    private static function clear_cron_events()
    {
        $timestamp = wp_next_scheduled('rsd_fp_daily_cleanup');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'rsd_fp_daily_cleanup');
        }

        $timestamp = wp_next_scheduled('rsd_fp_deadline_reminders');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'rsd_fp_deadline_reminders');
        }
    }
}
