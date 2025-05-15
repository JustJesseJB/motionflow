<?php
/**
 * Config Class
 *
 * @package    MotionFlow
 * @subpackage MotionFlow/includes
 */

namespace MotionFlow;

/**
 * Handle plugin configuration and settings.
 */
class Config {

    /**
     * The instance of this class.
     *
     * @var Config
     */
    private static $instance = null;

    /**
     * Cached settings.
     *
     * @var array
     */
    private $settings_cache = [];

    /**
     * Get the singleton instance.
     *
     * @return Config
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        // Initialize settings cache
        $this->refresh_cache();
    }

    /**
     * Refresh the settings cache.
     *
     * @return void
     */
    public function refresh_cache() {
        $this->settings_cache = [
            'general' => get_option('motionflow_general_settings', []),
            'grid' => get_option('motionflow_grid_settings', []),
            'filter' => get_option('motionflow_filter_settings', []),
            'cart' => get_option('motionflow_cart_settings', []),
        ];
    }

    /**
     * Get a specific setting.
     *
     * @param string $group   The settings group.
     * @param string $key     The setting key.
     * @param mixed  $default The default value if setting doesn't exist.
     *
     * @return mixed
     */
    public function get($group, $key, $default = null) {
        if (!isset($this->settings_cache[$group])) {
            return $default;
        }
        
        if (!isset($this->settings_cache[$group][$key])) {
            return $default;
        }
        
        return $this->settings_cache[$group][$key];
    }

    /**
     * Get all settings in a group.
     *
     * @param string $group   The settings group.
     * @param array  $default The default value if group doesn't exist.
     *
     * @return array
     */
    public function get_group($group, $default = []) {
        return isset($this->settings_cache[$group]) ? $this->settings_cache[$group] : $default;
    }

    /**
     * Update a specific setting.
     *
     * @param string $group The settings group.
     * @param string $key   The setting key.
     * @param mixed  $value The new value.
     *
     * @return bool Whether the update was successful.
     */
    public function update($group, $key, $value) {
        // Get current settings
        $current = $this->get_group($group, []);
        
        // Update setting
        $current[$key] = $value;
        
        // Save to database
        $result = update_option('motionflow_' . $group . '_settings', $current);
        
        // Refresh cache on success
        if ($result) {
            $this->refresh_cache();
        }
        
        return $result;
    }

    /**
     * Update an entire settings group.
     *
     * @param string $group  The settings group.
     * @param array  $values The new values.
     *
     * @return bool Whether the update was successful.
     */
    public function update_group($group, $values) {
        // Save to database
        $result = update_option('motionflow_' . $group . '_settings', $values);
        
        // Refresh cache on success
        if ($result) {
            $this->refresh_cache();
        }
        
        return $result;
    }

    /**
     * Check if debug mode is enabled.
     *
     * @return bool
     */
    public function is_debug_mode() {
        return (bool) $this->get('general', 'debug_mode', false);
    }

    /**
     * Check if a feature is enabled.
     *
     * @param string $feature The feature to check.
     *
     * @return bool
     */
    public function is_feature_enabled($feature) {
        switch ($feature) {
            case 'analytics':
                return (bool) $this->get('general', 'enable_analytics', true);
            
            case 'ajax_filtering':
                return (bool) $this->get('filter', 'enable_ajax_filtering', true);
            
            case 'drag_to_cart':
                return (bool) $this->get('cart', 'enable_drag_to_cart', true);
            
            case 'virtualization':
                return (bool) $this->get('grid', 'enable_virtualization', true);
            
            case 'quick_checkout':
                return (bool) $this->get('cart', 'enable_quick_checkout', false);
            
            default:
                return false;
        }
    }

    /**
     * Get a layout setting.
     *
     * @param string $device  The device type (desktop, tablet, mobile).
     * @param string $setting The setting to get.
     * @param mixed  $default The default value.
     *
     * @return mixed
     */
    public function get_layout_setting($device, $setting, $default = null) {
        // Map device types to their specific settings
        $device_settings = [
            'desktop' => [
                'columns' => 'columns_desktop',
            ],
            'tablet' => [
                'columns' => 'columns_tablet',
            ],
            'mobile' => [
                'columns' => 'columns_mobile',
            ],
        ];
        
        // If device or setting mapping doesn't exist, return default
        if (!isset($device_settings[$device]) || !isset($device_settings[$device][$setting])) {
            return $default;
        }
        
        // Get the actual setting key
        $key = $device_settings[$device][$setting];
        
        // Return the setting value
        return $this->get('grid', $key, $default);
    }

    /**
     * Get cart position for the specified device.
     *
     * @param string $device  The device type (desktop, mobile).
     * @param string $default The default position.
     *
     * @return string
     */
    public function get_cart_position($device, $default = 'right') {
        return $this->get('cart', 'cart_position_' . $device, $default);
    }

    /**
     * Get the cache expiration time in seconds.
     *
     * @return int
     */
    public function get_cache_expiration() {
        return (int) $this->get('general', 'cache_expiration', 3600);
    }
}