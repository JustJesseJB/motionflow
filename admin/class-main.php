<?php
/**
 * Admin Main Class
 *
 * @package    MotionFlow
 * @subpackage MotionFlow/admin
 */

namespace MotionFlow\Admin;

/**
 * The admin-specific functionality of the plugin.
 */
class Main {

    /**
     * The version of this plugin.
     *
     * @var string
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @param string $version The version of this plugin.
     */
    public function __construct($version) {
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @return void
     */
    public function enqueue_styles() {
        $screen = get_current_screen();
        
        // Only load on our plugin pages
        if (!$screen || strpos($screen->id, 'motionflow') === false) {
            return;
        }
        
        wp_enqueue_style(
            'motionflow-admin',
            MOTIONFLOW_PLUGIN_URL . 'admin/css/motionflow-admin.css',
            [],
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @return void
     */
    public function enqueue_scripts() {
        $screen = get_current_screen();
        
        // Only load on our plugin pages
        if (!$screen || strpos($screen->id, 'motionflow') === false) {
            return;
        }
        
        wp_enqueue_script(
            'motionflow-admin',
            MOTIONFLOW_PLUGIN_URL . 'admin/js/motionflow-admin.js',
            ['jquery', 'wp-color-picker', 'jquery-ui-sortable'],
            $this->version,
            false
        );
        
        // Localize the script with data
        wp_localize_script(
            'motionflow-admin',
            'motionflow_admin',
            [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('motionflow_admin_nonce'),
                'i18n' => [
                    'save_success' => __('Settings saved successfully!', 'motionflow'),
                    'save_error' => __('Error saving settings. Please try again.', 'motionflow'),
                    'confirm_reset' => __('Are you sure you want to reset all settings to defaults? This cannot be undone.', 'motionflow'),
                ],
            ]
        );
    }

    /**
     * Add admin menu pages.
     *
     * @return void
     */
    public function add_plugin_menu() {
        // Main menu
        add_menu_page(
            __('MotionFlow', 'motionflow'),
            __('MotionFlow', 'motionflow'),
            'manage_motionflow',
            'motionflow',
            [$this, 'render_dashboard_page'],
            'dashicons-cart',
            58
        );
        
        // Dashboard submenu
        add_submenu_page(
            'motionflow',
            __('Dashboard', 'motionflow'),
            __('Dashboard', 'motionflow'),
            'manage_motionflow',
            'motionflow',
            [$this, 'render_dashboard_page']
        );
        
        // Settings submenu
        add_submenu_page(
            'motionflow',
            __('Settings', 'motionflow'),
            __('Settings', 'motionflow'),
            'manage_motionflow',
            'motionflow-settings',
            [$this, 'render_settings_page']
        );
        
        // Layouts submenu
        add_submenu_page(
            'motionflow',
            __('Layouts', 'motionflow'),
            __('Layouts', 'motionflow'),
            'edit_motionflow_layouts',
            'motionflow-layouts',
            [$this, 'render_layouts_page']
        );
        
        // Analytics submenu
        add_submenu_page(
            'motionflow',
            __('Analytics', 'motionflow'),
            __('Analytics', 'motionflow'),
            'view_motionflow_analytics',
            'motionflow-analytics',
            [$this, 'render_analytics_page']
        );
        
        // Tools submenu
        add_submenu_page(
            'motionflow',
            __('Tools', 'motionflow'),
            __('Tools', 'motionflow'),
            'manage_motionflow',
            'motionflow-tools',
            [$this, 'render_tools_page']
        );
    }

    /**
     * Register plugin settings.
     *
     * @return void
     */
    public function register_settings() {
        // General settings
        register_setting(
            'motionflow_general_settings',
            'motionflow_general_settings',
            [$this, 'sanitize_general_settings']
        );
        
        // Grid settings
        register_setting(
            'motionflow_grid_settings',
            'motionflow_grid_settings',
            [$this, 'sanitize_grid_settings']
        );
        
        // Filter settings
        register_setting(
            'motionflow_filter_settings',
            'motionflow_filter_settings',
            [$this, 'sanitize_filter_settings']
        );
        
        // Cart settings
        register_setting(
            'motionflow_cart_settings',
            'motionflow_cart_settings',
            [$this, 'sanitize_cart_settings']
        );
    }

    /**
     * Add plugin action links.
     *
     * @param array $links The plugin action links.
     *
     * @return array
     */
    public function add_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=motionflow-settings') . '">' . __('Settings', 'motionflow') . '</a>';
        array_unshift($links, $settings_link);
        
        return $links;
    }

    /**
     * Render the dashboard page.
     *
     * @return void
     */
    public function render_dashboard_page() {
        require_once MOTIONFLOW_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    /**
     * Render the settings page.
     *
     * @return void
     */
    public function render_settings_page() {
        require_once MOTIONFLOW_PLUGIN_DIR . 'admin/views/settings.php';
    }

    /**
     * Render the layouts page.
     *
     * @return void
     */
    public function render_layouts_page() {
        require_once MOTIONFLOW_PLUGIN_DIR . 'admin/views/layouts.php';
    }

    /**
     * Render the analytics page.
     *
     * @return void
     */
    public function render_analytics_page() {
        require_once MOTIONFLOW_PLUGIN_DIR . 'admin/views/analytics.php';
    }

    /**
     * Render the tools page.
     *
     * @return void
     */
    public function render_tools_page() {
        require_once MOTIONFLOW_PLUGIN_DIR . 'admin/views/tools.php';
    }

    /**
     * Sanitize general settings.
     *
     * @param array $input The input to sanitize.
     *
     * @return array
     */
    public function sanitize_general_settings($input) {
        $sanitized = [];
        
        $sanitized['version'] = MOTIONFLOW_VERSION;
        
        $sanitized['debug_mode'] = isset($input['debug_mode']) ? (bool) $input['debug_mode'] : false;
        
        $cache_expiration = isset($input['cache_expiration']) ? absint($input['cache_expiration']) : 3600;
        $sanitized['cache_expiration'] = ($cache_expiration < 60) ? 60 : $cache_expiration;
        
        $sanitized['enable_analytics'] = isset($input['enable_analytics']) ? (bool) $input['enable_analytics'] : true;
        
        // Log settings update
        \MotionFlow_Logger::info('General settings updated', [
            'new_settings' => $sanitized,
            'user_id' => get_current_user_id(),
        ]);
        
        return $sanitized;
    }

    /**
     * Sanitize grid settings.
     *
     * @param array $input The input to sanitize.
     *
     * @return array
     */
    public function sanitize_grid_settings($input) {
        $sanitized = [];
        
        $sanitized['columns_desktop'] = isset($input['columns_desktop']) ? absint($input['columns_desktop']) : 4;
        if ($sanitized['columns_desktop'] < 1) {
            $sanitized['columns_desktop'] = 1;
        } elseif ($sanitized['columns_desktop'] > 8) {
            $sanitized['columns_desktop'] = 8;
        }
        
        $sanitized['columns_tablet'] = isset($input['columns_tablet']) ? absint($input['columns_tablet']) : 3;
        if ($sanitized['columns_tablet'] < 1) {
            $sanitized['columns_tablet'] = 1;
        } elseif ($sanitized['columns_tablet'] > 6) {
            $sanitized['columns_tablet'] = 6;
        }
        
        $sanitized['columns_mobile'] = isset($input['columns_mobile']) ? absint($input['columns_mobile']) : 2;
        if ($sanitized['columns_mobile'] < 1) {
            $sanitized['columns_mobile'] = 1;
        } elseif ($sanitized['columns_mobile'] > 4) {
            $sanitized['columns_mobile'] = 4;
        }
        
        $sanitized['enable_virtualization'] = isset($input['enable_virtualization']) ? (bool) $input['enable_virtualization'] : true;
        
        $sanitized['items_per_page'] = isset($input['items_per_page']) ? absint($input['items_per_page']) : 24;
        if ($sanitized['items_per_page'] < 4) {
            $sanitized['items_per_page'] = 4;
        } elseif ($sanitized['items_per_page'] > 100) {
            $sanitized['items_per_page'] = 100;
        }
        
        $sanitized['lazy_load_images'] = isset($input['lazy_load_images']) ? (bool) $input['lazy_load_images'] : true;
        
        $sanitized['show_rating'] = isset($input['show_rating']) ? (bool) $input['show_rating'] : true;
        
        $sanitized['show_price'] = isset($input['show_price']) ? (bool) $input['show_price'] : true;
        
        // Log settings update
        \MotionFlow_Logger::info('Grid settings updated', [
            'new_settings' => $sanitized,
            'user_id' => get_current_user_id(),
        ]);
        
        return $sanitized;
    }

    /**
     * Sanitize filter settings.
     *
     * @param array $input The input to sanitize.
     *
     * @return array
     */
    public function sanitize_filter_settings($input) {
        $sanitized = [];
        
        $sanitized['enable_ajax_filtering'] = isset($input['enable_ajax_filtering']) ? (bool) $input['enable_ajax_filtering'] : true;
        
        $sanitized['show_active_filters'] = isset($input['show_active_filters']) ? (bool) $input['show_active_filters'] : true;
        
        $valid_positions = ['top', 'left', 'right'];
        $sanitized['filter_position'] = isset($input['filter_position']) && in_array($input['filter_position'], $valid_positions) ? $input['filter_position'] : 'top';
        
        $valid_display_types = ['dropdown', 'button', 'swatch', 'radio', 'checkbox', 'slider'];
        $sanitized['default_display_type'] = isset($input['default_display_type']) && in_array($input['default_display_type'], $valid_display_types) ? $input['default_display_type'] : 'dropdown';
        
        $sanitized['attribute_display'] = [];
        if (isset($input['attribute_display']) && is_array($input['attribute_display'])) {
            foreach ($input['attribute_display'] as $attribute => $display_type) {
                if (in_array($display_type, $valid_display_types)) {
                    $sanitized['attribute_display'][$attribute] = $display_type;
                }
            }
        }
        
        // Log settings update
        \MotionFlow_Logger::info('Filter settings updated', [
            'new_settings' => $sanitized,
            'user_id' => get_current_user_id(),
        ]);
        
        return $sanitized;
    }

    /**
     * Sanitize cart settings.
     *
     * @param array $input The input to sanitize.
     *
     * @return array
     */
    public function sanitize_cart_settings($input) {
        $sanitized = [];
        
        $sanitized['enable_drag_to_cart'] = isset($input['enable_drag_to_cart']) ? (bool) $input['enable_drag_to_cart'] : true;
        
        $valid_positions = ['left', 'right', 'top', 'bottom'];
        $sanitized['cart_position_desktop'] = isset($input['cart_position_desktop']) && in_array($input['cart_position_desktop'], $valid_positions) ? $input['cart_position_desktop'] : 'right';
        
        $sanitized['cart_position_mobile'] = isset($input['cart_position_mobile']) && in_array($input['cart_position_mobile'], $valid_positions) ? $input['cart_position_mobile'] : 'bottom';
        
        $sanitized['show_quantity_controls'] = isset($input['show_quantity_controls']) ? (bool) $input['show_quantity_controls'] : true;
        
        $sanitized['enable_quick_checkout'] = isset($input['enable_quick_checkout']) ? (bool) $input['enable_quick_checkout'] : false;
        
        // Log settings update
        \MotionFlow_Logger::info('Cart settings updated', [
            'new_settings' => $sanitized,
            'user_id' => get_current_user_id(),
        ]);
        
        return $sanitized;
    }
}