<?php
/**
 * Analytics Class
 *
 * @package    MotionFlow
 * @subpackage MotionFlow/includes
 */

namespace MotionFlow;

/**
 * Handles analytics functionality.
 */
class Analytics {

    /**
     * The singleton instance.
     *
     * @var Analytics
     */
    private static $instance;

    /**
     * The session ID.
     *
     * @var string
     */
    private $session_id;

    /**
     * Get the singleton instance.
     *
     * @return Analytics
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    // Other methods are already implemented above

    /**
     * Get device usage report.
     *
     * @param array $args Report arguments.
     *
     * @return array
     */
    private function get_device_usage_report($args) {
        global $wpdb;
        
        $date_from = $args['date_from'] . ' 00:00:00';
        $date_to = $args['date_to'] . ' 23:59:59';
        
        $data = [];
        
        // Get pageviews by device type
        $devices = $wpdb->get_results($wpdb->prepare(
            "SELECT event_data, COUNT(*) as count
            FROM {$wpdb->prefix}motionflow_analytics
            WHERE event_type = 'page_view'
            AND created_at BETWEEN %s AND %s
            GROUP BY event_data",
            $date_from,
            $date_to
        ), ARRAY_A);
        
        // Process device types
        $device_types = [
            'desktop' => 0,
            'tablet' => 0,
            'mobile' => 0,
            'other' => 0,
        ];
        
        foreach ($devices as $device) {
            $device_data = json_decode($device['event_data'], true);
            
            if (isset($device_data['device_type'])) {
                if (isset($device_types[$device_data['device_type']])) {
                    $device_types[$device_data['device_type']] += $device['count'];
                } else {
                    $device_types['other'] += $device['count'];
                }
            } else {
                $device_types['other'] += $device['count'];
            }
        }
        
        // Calculate percentages
        $total_views = array_sum($device_types);
        $device_percentages = [];
        
        if ($total_views > 0) {
            foreach ($device_types as $device => $count) {
                $device_percentages[$device] = ($count / $total_views) * 100;
            }
        }
        
        // Build report data
        $data = [
            'total_views' => $total_views,
            'device_types' => $device_types,
            'device_percentages' => $device_percentages,
        ];
        
        return $data;
    }

    /**
     * Get cart abandonment report.
     *
     * @param array $args Report arguments.
     *
     * @return array
     */
    private function get_cart_abandonment_report($args) {
        global $wpdb;
        
        $date_from = $args['date_from'] . ' 00:00:00';
        $date_to = $args['date_to'] . ' 23:59:59';
        
        $data = [];
        
        // Get total add to cart events
        $total_adds = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}motionflow_analytics
            WHERE event_type = 'add_to_cart'
            AND created_at BETWEEN %s AND %s",
            $date_from,
            $date_to
        ));
        
        // Get total checkouts
        $total_checkouts = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}motionflow_analytics
            WHERE event_type = 'checkout'
            AND created_at BETWEEN %s AND %s",
            $date_from,
            $date_to
        ));
        
        // Calculate abandonment rate
        $abandonment_rate = 0;
        
        if ($total_adds > 0) {
            $abandonment_rate = (($total_adds - $total_checkouts) / $total_adds) * 100;
        }
        
        // Get sessions that added to cart but didn't checkout
        $abandoned_sessions = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT session_id FROM {$wpdb->prefix}motionflow_analytics
            WHERE event_type = 'add_to_cart'
            AND created_at BETWEEN %s AND %s
            AND session_id NOT IN (
                SELECT DISTINCT session_id FROM {$wpdb->prefix}motionflow_analytics
                WHERE event_type = 'checkout'
                AND created_at BETWEEN %s AND %s
            )",
            $date_from,
            $date_to,
            $date_from,
            $date_to
        ), ARRAY_A);
        
        // Count abandoned sessions
        $abandoned_count = count($abandoned_sessions);
        
        // Build report data
        $data = [
            'total_adds' => $total_adds,
            'total_checkouts' => $total_checkouts,
            'abandonment_rate' => $abandonment_rate,
            'abandoned_count' => $abandoned_count,
        ];
        
        return $data;
    }

    /**
     * Validate group by parameter.
     *
     * @param string $group_by Group by parameter.
     *
     * @return string
     */
    private function validate_group_by($group_by) {
        $valid_group_by = ['day', 'month'];
        
        if (!in_array($group_by, $valid_group_by)) {
            return 'day';
        }
        
        return $group_by;
    }
}