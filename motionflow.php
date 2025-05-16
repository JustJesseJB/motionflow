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
 * Load translation files
 * Moving this to init hook to fix the "function _load_textdomain_just_in_time was called incorrectly" warning
 */
function motionflow_load_textdomain() {
    load_plugin_textdomain('motionflow', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('init', 'motionflow_load_textdomain');

/**
 * Manually include all required files
 * This replaces the autoloader temporarily to fix the class not found error
 */
function motionflow_include_files() {
    // Core files
    require_once MOTIONFLOW_PLUGIN_DIR . 'includes/class-i18n.php';
    require_once MOTIONFLOW_PLUGIN_DIR . 'includes/class-loader.php';
    require_once MOTIONFLOW_PLUGIN_DIR . 'includes/class-config.php';
    
    // Create the frontend directory if it doesn't exist
    if (!file_exists(MOTIONFLOW_PLUGIN_DIR . 'includes/frontend')) {
        mkdir(MOTIONFLOW_PLUGIN_DIR . 'includes/frontend', 0755, true);
    }
    
    // Create the Main Frontend class file if it doesn't exist
    $frontend_main_file = MOTIONFLOW_PLUGIN_DIR . 'includes/frontend/class-main.php';
    if (!file_exists($frontend_main_file)) {
        $frontend_main_content = '<?php
/**
 * Frontend Main Class
 *
 * @package    MotionFlow
 * @subpackage MotionFlow/includes/frontend
 */

namespace MotionFlow\Frontend;

/**
 * The public-facing functionality of the plugin.
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
     * Register the stylesheets for the public-facing area.
     *
     * @return void
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            \'motionflow-public\',
            MOTIONFLOW_PLUGIN_URL . \'public/css/motionflow-public.css\',
            [],
            $this->version,
            \'all\'
        );
    }

    /**
     * Register the JavaScript for the public-facing area.
     *
     * @return void
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            \'motionflow-public\',
            MOTIONFLOW_PLUGIN_URL . \'public/js/motionflow-public.js\',
            [\'jquery\'],
            $this->version,
            true
        );
        
        // Localize script with settings
        $config = \MotionFlow\Config::instance();
        
        wp_localize_script(
            \'motionflow-public\',
            \'motionflow_params\',
            [
                \'ajaxUrl\' => admin_url(\'admin-ajax.php\'),
                \'nonce\' => wp_create_nonce(\'motionflow_nonce\'),
                \'settings\' => [
                    \'animations\' => true,
                    \'enable_analytics\' => $config->is_feature_enabled(\'analytics\'),
                ],
                \'i18n\' => [
                    \'add_to_cart\' => __(\'Add to cart\', \'motionflow\'),
                    \'view_details\' => __(\'View details\', \'motionflow\'),
                    \'no_products\' => __(\'No products found\', \'motionflow\'),
                ],
            ]
        );
    }

    /**
     * Register shortcodes.
     *
     * @return void
     */
    public function register_shortcodes() {
        add_shortcode(\'motionflow\', [$this, \'render_motionflow_shortcode\']);
        add_shortcode(\'motionflow_filters\', [$this, \'render_filters_shortcode\']);
        add_shortcode(\'motionflow_grid\', [$this, \'render_grid_shortcode\']);
        add_shortcode(\'motionflow_cart\', [$this, \'render_cart_shortcode\']);
    }

    /**
     * Render the main MotionFlow shortcode.
     *
     * @param array $atts Shortcode attributes.
     *
     * @return string
     */
    public function render_motionflow_shortcode($atts) {
        $atts = shortcode_atts([
            \'layout\' => \'default\',
            \'filters\' => \'yes\',
            \'grid\' => \'yes\',
            \'cart\' => \'yes\',
        ], $atts, \'motionflow\');
        
        $output = \'<div class="motionflow-container">\';
        
        // Add filters if enabled
        if ($atts[\'filters\'] === \'yes\') {
            $output .= $this->render_filters_shortcode($atts);
        }
        
        // Add grid if enabled
        if ($atts[\'grid\'] === \'yes\') {
            $output .= $this->render_grid_shortcode($atts);
        }
        
        // Add cart if enabled
        if ($atts[\'cart\'] === \'yes\') {
            $output .= $this->render_cart_shortcode($atts);
        }
        
        $output .= \'</div>\';
        
        return $output;
    }

    /**
     * Render the filters shortcode.
     *
     * @param array $atts Shortcode attributes.
     *
     * @return string
     */
    public function render_filters_shortcode($atts) {
        $atts = shortcode_atts([
            \'layout\' => \'default\',
        ], $atts, \'motionflow_filters\');
        
        ob_start();
        include MOTIONFLOW_PLUGIN_DIR . \'public/partials/filters.php\';
        return ob_get_clean();
    }

    /**
     * Render the grid shortcode.
     *
     * @param array $atts Shortcode attributes.
     *
     * @return string
     */
    public function render_grid_shortcode($atts) {
        $atts = shortcode_atts([
            \'layout\' => \'default\',
            \'columns_desktop\' => 0,
            \'columns_tablet\' => 0,
            \'columns_mobile\' => 0,
            \'limit\' => 0,
            \'categories\' => \'\',
            \'tags\' => \'\',
        ], $atts, \'motionflow_grid\');
        
        ob_start();
        include MOTIONFLOW_PLUGIN_DIR . \'public/partials/grid.php\';
        return ob_get_clean();
    }

    /**
     * Render the cart shortcode.
     *
     * @param array $atts Shortcode attributes.
     *
     * @return string
     */
    public function render_cart_shortcode($atts) {
        $atts = shortcode_atts([
            \'layout\' => \'default\',
            \'show_quantity_controls\' => \'yes\',
        ], $atts, \'motionflow_cart\');
        
        ob_start();
        include MOTIONFLOW_PLUGIN_DIR . \'public/partials/cart.php\';
        return ob_get_clean();
    }
}';
        file_put_contents($frontend_main_file, $frontend_main_content);
    }
    
    // Include the new frontend class we just created
    require_once MOTIONFLOW_PLUGIN_DIR . 'includes/frontend/class-main.php';
    
    // Admin files
    require_once MOTIONFLOW_PLUGIN_DIR . 'admin/class-main.php';
    
    // Include integration files
    require_once MOTIONFLOW_PLUGIN_DIR . 'includes/integrations/class-woocommerce.php';
    
    // Core plugin file
    require_once MOTIONFLOW_PLUGIN_DIR . 'includes/class-core.php';
}

/**
 * Initialize the plugin
 */
function motionflow_init() {
    // Check if WooCommerce is active
    if (!motionflow_check_woocommerce()) {
        return;
    }

    // Include all required files
    motionflow_include_files();

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