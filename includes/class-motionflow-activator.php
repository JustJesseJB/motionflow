<?php
/**
 * Activator Class
 *
 * @package    MotionFlow
 * @subpackage MotionFlow/includes
 */

namespace MotionFlow;

/**
 * Fired during plugin activation.
 */
class Activator {

    /**
     * Activate the plugin.
     *
     * @return void
     */
    public static function activate() {
        // Create necessary database tables
        self::create_tables();
        
        // Create initial settings
        self::create_settings();
        
        // Set up custom capabilities
        self::setup_capabilities();
        
        // Clear any caches
        self::clear_caches();
        
        // Log activation
        \MotionFlow_Logger::info('MotionFlow plugin activated', ['version' => MOTIONFLOW_VERSION]);
    }

    /**
     * Create necessary database tables.
     *
     * @return void
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Analytics table
        $table_name = $wpdb->prefix . 'motionflow_analytics';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT NULL,
            session_id varchar(32) NOT NULL,
            event_type varchar(50) NOT NULL,
            event_data longtext NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY session_id (session_id),
            KEY event_type (event_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Filter settings table
        $table_name_filters = $wpdb->prefix . 'motionflow_filter_settings';
        
        $sql .= "CREATE TABLE $table_name_filters (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            filter_key varchar(50) NOT NULL,
            filter_settings longtext NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY filter_key (filter_key)
        ) $charset_collate;";
        
        // Layout settings table
        $table_name_layouts = $wpdb->prefix . 'motionflow_layouts';
        
        $sql .= "CREATE TABLE $table_name_layouts (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            layout_name varchar(100) NOT NULL,
            layout_type varchar(50) NOT NULL,
            layout_data longtext NOT NULL,
            is_active tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY layout_type (layout_type),
            KEY is_active (is_active)
        ) $charset_collate;";
        
        // Include WordPress database upgrade functions
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Execute SQL
        dbDelta($sql);
    }

    /**
     * Create initial settings.
     *
     * @return void
     */
    private static function create_settings() {
        // General settings
        $general_settings = [
            'version' => MOTIONFLOW_VERSION,
            'debug_mode' => false,
            'cache_expiration' => 3600, // 1 hour
            'enable_analytics' => true,
        ];
        
        update_option('motionflow_general_settings', $general_settings);
        
        // Grid settings
        $grid_settings = [
            'columns_desktop' => 4,
            'columns_tablet' => 3,
            'columns_mobile' => 2,
            'enable_virtualization' => true,
            'items_per_page' => 24,
            'lazy_load_images' => true,
            'show_rating' => true,
            'show_price' => true,
        ];
        
        update_option('motionflow_grid_settings', $grid_settings);
        
        // Filter settings
        $filter_settings = [
            'enable_ajax_filtering' => true,
            'show_active_filters' => true,
            'filter_position' => 'top',
            'default_display_type' => 'dropdown',
            'attribute_display' => [
                'pa_color' => 'swatch',
                'pa_size' => 'button',
            ],
        ];
        
        update_option('motionflow_filter_settings', $filter_settings);
        
        // Cart settings
        $cart_settings = [
            'enable_drag_to_cart' => true,
            'cart_position_desktop' => 'right',
            'cart_position_mobile' => 'bottom',
            'show_quantity_controls' => true,
            'enable_quick_checkout' => false,
        ];
        
        update_option('motionflow_cart_settings', $cart_settings);
    }

    /**
     * Set up custom capabilities.
     *
     * @return void
     */
    private static function setup_capabilities() {
        $admin_role = get_role('administrator');
        
        if ($admin_role) {
            $admin_role->add_cap('manage_motionflow');
            $admin_role->add_cap('edit_motionflow_layouts');
            $admin_role->add_cap('view_motionflow_analytics');
        }
        
        $shop_manager_role = get_role('shop_manager');
        
        if ($shop_manager_role) {
            $shop_manager_role->add_cap('manage_motionflow');
            $shop_manager_role->add_cap('edit_motionflow_layouts');
            $shop_manager_role->add_cap('view_motionflow_analytics');
        }
    }

    /**
     * Clear any caches.
     *
     * @return void
     */
    private static function clear_caches() {
        // Clear WordPress cache
        wp_cache_flush();
        
        // Clear WooCommerce transients
        if (function_exists('wc_delete_product_transients')) {
            wc_delete_product_transients();
        }
        
        // Delete our own transients
        global $wpdb;
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_motionflow_%'");
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_motionflow_%'");
    }
}