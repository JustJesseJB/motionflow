<?php
/**
 * MotionFlow - Digital Commerce, Redefined
 *
 * @package           MotionFlow
 * @author            Ambition Amplified, LLC
 * @copyright         2025 MotionFlow
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       MotionFlow
 * Plugin URI:        https://ambitionamplified.com/motionflow
 * Description:       Transform your WooCommerce store with interactive filtering, customizable product grids, and innovative drag-and-drop cart functionality.
 * Version:           1.0.0
 * Requires at least: 5.6
 * Requires PHP:      7.4
 * Author:            Ambition Amplified, LLC
 * Author URI:        https://ambitionamplified.com/motionflow
 * Text Domain:       motionflow
 * Domain Path:       /languages
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * 
 * WC requires at least: 5.0.0
 * WC tested up to:      8.0.0
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('MOTIONFLOW_VERSION', '1.0.0');
define('MOTIONFLOW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MOTIONFLOW_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MOTIONFLOW_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Check if WooCommerce is active
 */
function motionflow_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'motionflow_missing_wc_notice');
        return false;
    }
    return true;
}

/**
 * Admin notice for missing WooCommerce
 */
function motionflow_missing_wc_notice() {
    ?>
    <div class="error">
        <p><?php _e('MotionFlow requires WooCommerce to be installed and active.', 'motionflow'); ?></p>
    </div>
    <?php
}

/**
 * Initialize logging early
 */
require_once MOTIONFLOW_PLUGIN_DIR . 'includes/class-motionflow-logger.php';

/**
 * Initialize the plugin
 */
function motionflow_init() {
    // Check if WooCommerce is active
    if (!motionflow_check_woocommerce()) {
        return;
    }

    // Manual class includes since we're having autoloader issues
    require_once MOTIONFLOW_PLUGIN_DIR . 'includes/class-i18n.php';
    require_once MOTIONFLOW_PLUGIN_DIR . 'includes/class-loader.php';
    require_once MOTIONFLOW_PLUGIN_DIR . 'includes/class-config.php';
    require_once MOTIONFLOW_PLUGIN_DIR . 'admin/class-main.php';
    require_once MOTIONFLOW_PLUGIN_DIR . 'includes/class-core.php';

    // Initialize the core plugin class
    $motionflow = new MotionFlow\Core();
    $motionflow->run();
}

// Register activation hook
register_activation_hook(__FILE__, function() {
    require_once MOTIONFLOW_PLUGIN_DIR . 'includes/class-motionflow-activator.php';
    MotionFlow\Activator::activate();
});

// Register deactivation hook
register_deactivation_hook(__FILE__, function() {
    require_once MOTIONFLOW_PLUGIN_DIR . 'includes/class-motionflow-deactivator.php';
    MotionFlow\Deactivator::deactivate();
});

// Hook into plugins loaded
add_action('plugins_loaded', 'motionflow_init');