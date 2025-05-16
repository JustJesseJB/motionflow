<?php
/**
 * Main Frontend Class
 *
 * @package    MotionFlow
 * @subpackage MotionFlow/public
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
     * Register the stylesheets for the public-facing side of the site.
     *
     * @return void
     */
    public function enqueue_styles() {
        // Skip if not WooCommerce page
        if (!$this->is_woocommerce_page()) {
            return;
        }
        
        wp_enqueue_style(
            'motionflow-public',
            MOTIONFLOW_PLUGIN_URL . 'public/css/motionflow-public.css',
            [],
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @return void
     */
    public function enqueue_scripts() {
        // Skip if not WooCommerce page
        if (!$this->is_woocommerce_page()) {
            return;
        }
        
        // Enqueue main script
        wp_enqueue_script(
            'motionflow-public',
            MOTIONFLOW_PLUGIN_URL . 'public/js/motionflow-public.js',
            ['jquery'],
            $this->version,
            true
        );
        
        // Enqueue AJAX handler
        wp_enqueue_script(
            'motionflow-ajax',
            MOTIONFLOW_PLUGIN_URL . 'public/js/motionflow-ajax.js',
            ['jquery', 'motionflow-public'],
            $this->version,
            true
        );
        
        // Enqueue drag and drop
        wp_enqueue_script(
            'motionflow-dragdrop',
            MOTIONFLOW_PLUGIN_URL . 'public/js/motionflow-dragdrop.js',
            ['jquery', 'motionflow-public'],
            $this->version,
            true
        );
        
        // Check if Hammer.js should be included
        $config = \MotionFlow\Config::instance();
        if ($config->get('general', 'include_hammer_js', true)) {
            wp_enqueue_script(
                'hammer-js',
                MOTIONFLOW_PLUGIN_URL . 'public/js/vendor/hammer.min.js',
                [],
                '2.0.8',
                true
            );
        }
        
        // Localize script with data
        wp_localize_script(
            'motionflow-public',
            'motionflow_params',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => [
                    'filter' => wp_create_nonce('motionflow_filter_nonce'),
                    'cart' => wp_create_nonce('motionflow_cart_nonce'),
                    'modal' => wp_create_nonce('motionflow_modal_nonce'),
                    'analytics' => wp_create_nonce('motionflow_analytics_nonce'),
                ],
                'settings' => [
                    'animations' => $config->get('grid', 'enable_animations', true),
                    'drag_to_cart' => $config->get('cart', 'enable_drag_to_cart', true),
                    'modal' => $config->get('grid', 'enable_modal', true),
                    'lazy_load' => $config->get('grid', 'lazy_load_images', true),
                    'debug' => $config->is_debug_mode(),
                ],
                'selectors' => [
                    'product' => '.motionflow-product',
                    'dragHandle' => '.motionflow-drag-handle',
                    'cartContainer' => '.motionflow-cart-sidebar',
                    'cartButton' => '.motionflow-cart-button',
                    'filterForm' => '.motionflow-filters-form',
                    'filterControl' => '.motionflow-filter-control',
                    'gridContainer' => '.motionflow-grid',
                ],
                'i18n' => [
                    'add_to_cart' => __('Added to cart', 'motionflow'),
                    'error' => __('Error', 'motionflow'),
                    'no_products' => __('No products found', 'motionflow'),
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
        add_shortcode('motionflow', [$this, 'shortcode_motionflow']);
        add_shortcode('motionflow_filters', [$this, 'shortcode_filters']);
        add_shortcode('motionflow_grid', [$this, 'shortcode_grid']);
        add_shortcode('motionflow_cart', [$this, 'shortcode_cart']);
    }

    /**
     * Process the motionflow shortcode.
     *
     * @param array $atts The shortcode attributes.
     *
     * @return string
     */
    public function shortcode_motionflow($atts) {
        // Parse attributes
        $atts = shortcode_atts(
            [
                'layout' => 'default',
                'filters' => 'yes',
                'grid' => 'yes',
                'cart' => 'yes',
                'categories' => '',
                'tags' => '',
                'limit' => 24,
                'columns_desktop' => 4,
                'columns_tablet' => 3,
                'columns_mobile' => 2,
            ],
            $atts,
            'motionflow'
        );
        
        // Start output buffer
        ob_start();
        
        // Add filters if enabled
        if ($atts['filters'] === 'yes') {
            echo $this->shortcode_filters($atts);
        }
        
        // Add grid if enabled
        if ($atts['grid'] === 'yes') {
            echo $this->shortcode_grid($atts);
        }
        
        // Add cart if enabled
        if ($atts['cart'] === 'yes') {
            echo $this->shortcode_cart($atts);
        }
        
        // Return buffer contents
        return ob_get_clean();
    }

    /**
     * Process the motionflow_filters shortcode.
     *
     * @param array $atts The shortcode attributes.
     *
     * @return string
     */
    public function shortcode_filters($atts) {
        // Parse attributes
        $atts = shortcode_atts(
            [
                'layout' => 'default',
                'position' => '',
                'categories' => '',
                'tags' => '',
            ],
            $atts,
            'motionflow_filters'
        );
        
        // Start output buffer
        ob_start();
        
        // Include template
        include MOTIONFLOW_PLUGIN_DIR . 'public/partials/filters.php';
        
        // Return buffer contents
        return ob_get_clean();
    }

    /**
     * Process the motionflow_grid shortcode.
     *
     * @param array $atts The shortcode attributes.
     *
     * @return string
     */
    public function shortcode_grid($atts) {
        // Parse attributes
        $atts = shortcode_atts(
            [
                'layout' => 'default',
                'categories' => '',
                'tags' => '',
                'limit' => 24,
                'columns_desktop' => 4,
                'columns_tablet' => 3,
                'columns_mobile' => 2,
            ],
            $atts,
            'motionflow_grid'
        );
        
        // Start output buffer
        ob_start();
        
        // Include template
        include MOTIONFLOW_PLUGIN_DIR . 'public/partials/grid.php';
        
        // Return buffer contents
        return ob_get_clean();
    }

    /**
     * Process the motionflow_cart shortcode.
     *
     * @param array $atts The shortcode attributes.
     *
     * @return string
     */
    public function shortcode_cart($atts) {
        // Parse attributes
        $atts = shortcode_atts(
            [
                'layout' => 'default',
                'show_quantity_controls' => 'yes',
            ],
            $atts,
            'motionflow_cart'
        );
        
        // Start output buffer
        ob_start();
        
        // Include template
        include MOTIONFLOW_PLUGIN_DIR . 'public/partials/cart.php';
        
        // Return buffer contents
        return ob_get_clean();
    }
    
    /**
     * Check if the current page is a WooCommerce page.
     *
     * @return bool
     */
    private function is_woocommerce_page() {
        if (!function_exists('is_woocommerce') || !function_exists('is_cart') || !function_exists('is_checkout') || !function_exists('is_account_page')) {
            return false;
        }

        return (
            is_woocommerce() ||
            is_cart() ||
            is_checkout() ||
            is_account_page() ||
            is_shop() ||
            is_product_category() ||
            is_product_tag() ||
            is_product()
        );
    }
}