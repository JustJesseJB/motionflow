<?php
/**
 * Deactivator Class
 *
 * @package    MotionFlow
 * @subpackage MotionFlow/includes
 */

namespace MotionFlow;

/**
 * Fired during plugin deactivation.
 */
class Deactivator {

    /**
     * Deactivate the plugin.
     *
     * @return void
     */
    public static function deactivate() {
        // Clear scheduled events
        self::clear_scheduled_events();
        
        // Clear caches
        self::clear_caches();
        
        // Log deactivation
        \MotionFlow_Logger::info('MotionFlow plugin deactivated', ['version' => MOTIONFLOW_VERSION]);
    }

    /**
     * Clear scheduled events.
     *
     * @return void
     */
    private static function clear_scheduled_events() {
        wp_clear_scheduled_hook('motionflow_daily_maintenance');
        wp_clear_scheduled_hook('motionflow_weekly_report');
        wp_clear_scheduled_hook('motionflow_cleanup_analytics');
    }

    /**
     * Clear any caches.
     *
     * @return void
     */
    private static function clear_caches() {
        // Clear WordPress cache
        wp_cache_flush();
        
        // Delete our own transients
        global $wpdb;
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_motionflow_%'");
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_motionflow_%'");
    }
}