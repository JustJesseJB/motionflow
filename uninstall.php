<?php
/**
 * Uninstall MotionFlow
 *
 * @package    MotionFlow
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Define constants for the plugin
define('MOTIONFLOW_UNINSTALLING', true);
define('MOTIONFLOW_PLUGIN_FILE', __FILE__);

// Require autoloader if available
if (file_exists(dirname(MOTIONFLOW_PLUGIN_FILE) . '/includes/class-motionflow-autoloader.php')) {
    require_once dirname(MOTIONFLOW_PLUGIN_FILE) . '/includes/class-motionflow-autoloader.php';
}

/**
 * Delete plugin options
 */
function motionflow_delete_options() {
    delete_option('motionflow_general_settings');
    delete_option('motionflow_grid_settings');
    delete_option('motionflow_filter_settings');
    delete_option('motionflow_cart_settings');

    // Delete any other options created by the plugin
    global $wpdb;
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'motionflow\_%';");
}

/**
 * Delete custom database tables
 */
function motionflow_delete_tables() {
    global $wpdb;
    
    $tables = [
        $wpdb->prefix . 'motionflow_analytics',
        $wpdb->prefix . 'motionflow_filter_settings',
        $wpdb->prefix . 'motionflow_layouts',
    ];
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }
}

/**
 * Delete plugin logs
 */
function motionflow_delete_logs() {
    // Delete log files from the logs directory
    $log_dir = WP_CONTENT_DIR . '/motionflow-logs/';
    
    if (is_dir($log_dir)) {
        $log_files = glob($log_dir . '*.log');
        
        foreach ($log_files as $log_file) {
            @unlink($log_file);
        }
        
        // Try to remove the directory
        @rmdir($log_dir);
    }
}

/**
 * Delete plugin user capabilities
 */
function motionflow_delete_capabilities() {
    // Remove capabilities from roles
    $roles = ['administrator', 'shop_manager'];
    
    foreach ($roles as $role_name) {
        $role = get_role($role_name);
        
        if ($role) {
            $role->remove_cap('manage_motionflow');
            $role->remove_cap('edit_motionflow_layouts');
            $role->remove_cap('view_motionflow_analytics');
        }
    }
}

/**
 * Delete plugin transients
 */
function motionflow_delete_transients() {
    global $wpdb;
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_motionflow_%'");
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_motionflow_%'");
}

// Only proceed with uninstall if the setting allows it
$general_settings = get_option('motionflow_general_settings', []);
$delete_on_uninstall = isset($general_settings['delete_on_uninstall']) ? (bool) $general_settings['delete_on_uninstall'] : false;

if ($delete_on_uninstall) {
    // Execute all uninstall functions
    motionflow_delete_options();
    motionflow_delete_tables();
    motionflow_delete_logs();
    motionflow_delete_capabilities();
    motionflow_delete_transients();
    
    // Clear any caches
    wp_cache_flush();
    
    // If using an object cache, flush it
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
}