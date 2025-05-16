<?php
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
            'motionflow-public',
            MOTIONFLOW_PLUGIN_URL . 'public/css/motionflow-public.css',
            [],
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the public-facing area.
     *
     * @return void
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            'motionflow-public',
            MOTIONFLOW_PLUGIN_URL . 'public/js/motionflow-public.js',
            ['jquery'],
            $this->version,
            true
        );
        
        // Localize script with settings
        $config = \MotionFlow\Config::instance();
        
        wp_localize_script(
            'motionflow-public',
            'motionflow_params',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('motionflow_nonce'),
                'settings' => [
                    'animations' => true,
                    'enable_analytics' => $config->is_feature_enabled('analytics'),
                ],
                'i18n' => [
                    'add_to_cart' => __('Add to cart', 'motionflow'),
                    'view_details' => __('View details', 'motionflow'),
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
        add_shortcode('motionflow', [$this, 'render_motionflow_shortcode']);
        add_shortcode('motionflow_filters', [$this, 'render_filters_shortcode']);
        add_shortcode('motionflow_grid', [$this, 'render_grid_shortcode']);
        add_shortcode('motionflow_cart', [$this, 'render_cart_shortcode']);
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
            'layout' => 'default',
            'filters' => 'yes',
            'grid' => 'yes',
            'cart' => 'yes',
        ], $atts, 'motionflow');
        
        $output = '<div class="motionflow-container">';
        
        // Add filters if enabled
        if ($atts['filters'] === 'yes') {
            $output .= $this->render_filters_shortcode($atts);
        }
        
        // Add grid if enabled
        if ($atts['grid'] === 'yes') {
            $output .= $this->render_grid_shortcode($atts);
        }
        
        // Add cart if enabled
        if ($atts['cart'] === 'yes') {
            $output .= $this->render_cart_shortcode($atts);
        }
        
        $output .= '</div>';
        
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
            'layout' => 'default',
        ], $atts, 'motionflow_filters');
        
        ob_start();
        include MOTIONFLOW_PLUGIN_DIR . 'public/partials/filters.php';
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
            'layout' => 'default',
            'columns_desktop' => 0,
            'columns_tablet' => 0,
            'columns_mobile' => 0,
            'limit' => 0,
            'categories' => '',
            'tags' => '',
        ], $atts, 'motionflow_grid');
        
        ob_start();
        include MOTIONFLOW_PLUGIN_DIR . 'public/partials/grid.php';
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
            'layout' => 'default',
            'show_quantity_controls' => 'yes',
        ], $atts, 'motionflow_cart');
        
        ob_start();
        include MOTIONFLOW_PLUGIN_DIR . 'public/partials/cart.php';
        return ob_get_clean();
    }
}