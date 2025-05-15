<?php
/**
 * Loop Integration Class
 *
 * @package    MotionFlow
 * @subpackage MotionFlow/includes/display
 */

namespace MotionFlow\Display;

/**
 * Handles integration with existing WooCommerce loops.
 */
class Loop_Integration {

    /**
     * Settings for the integration.
     *
     * @var array
     */
    protected $settings;

    /**
     * Whether the integration is active.
     *
     * @var bool
     */
    protected $is_active;

    /**
     * Initialize the integration.
     *
     * @param array $settings The settings for the integration.
     */
    public function __construct($settings = []) {
        $this->settings = $settings;
        $this->is_active = false;
        
        // Register hooks if integration is enabled
        if ($this->get_setting('enable_loop_integration', true)) {
            $this->register_hooks();
        }
    }

    /**
     * Register hooks for loop integration.
     *
     * @return void
     */
    public function register_hooks() {
        // Hook into WooCommerce templates
        add_action('woocommerce_before_shop_loop', [$this, 'before_shop_loop'], 20);
        add_action('woocommerce_after_shop_loop', [$this, 'after_shop_loop'], 20);
        
        // Product card hooks
        add_filter('woocommerce_product_loop_start', [$this, 'product_loop_start'], 99);
        add_filter('woocommerce_product_loop_end', [$this, 'product_loop_end'], 99);
        
        // Override product HTML
        if ($this->get_setting('override_product_html', true)) {
            add_action('woocommerce_before_shop_loop_item', [$this, 'before_shop_loop_item'], 1);
            add_action('woocommerce_after_shop_loop_item', [$this, 'after_shop_loop_item'], 999);
            remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
            add_action('motionflow_product_actions', 'woocommerce_template_loop_add_to_cart', 10);
        }
        
        // Add drag-and-drop support
        if ($this->get_setting('enable_drag_to_cart', true)) {
            add_action('woocommerce_before_shop_loop_item', [$this, 'add_drag_handle'], 5);
            add_action('wp_footer', [$this, 'add_cart_sidebar']);
        }
        
        // Add modal support
        if ($this->get_setting('enable_modal', true)) {
            add_action('woocommerce_after_shop_loop_item', [$this, 'add_modal_trigger'], 15);
            add_action('wp_footer', [$this, 'add_modal_container']);
        }
    }

    /**
     * Actions before the shop loop starts.
     *
     * @return void
     */
    public function before_shop_loop() {
        $this->is_active = true;
        
        // Add MotionFlow container class
        add_filter('post_class', [$this, 'add_product_classes']);
        
        // Add scripts and styles if not already added
        wp_enqueue_style('motionflow-public');
        wp_enqueue_script('motionflow-public');
        wp_enqueue_script('motionflow-grid');
        
        if ($this->get_setting('enable_drag_to_cart', true)) {
            wp_enqueue_script('motionflow-cart');
        }
        
        // Initialize MotionFlow on this loop
        add_action('wp_footer', function() {
            ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof MotionFlow !== 'undefined') {
                        MotionFlow.initGrid({
                            container: '.products',
                            enableDragToCart: <?php echo $this->get_setting('enable_drag_to_cart', true) ? 'true' : 'false'; ?>,
                            enableModal: <?php echo $this->get_setting('enable_modal', true) ? 'true' : 'false'; ?>,
                            isLoopIntegration: true
                        });
                    }
                });
            </script>
            <?php
        });
    }

    /**
     * Actions after the shop loop ends.
     *
     * @return void
     */
    public function after_shop_loop() {
        $this->is_active = false;
        
        // Remove the filter for product classes
        remove_filter('post_class', [$this, 'add_product_classes']);
    }

    /**
     * Modify the product loop start HTML.
     *
     * @param string $html The original HTML.
     *
     * @return string
     */
    public function product_loop_start($html) {
        if (!$this->is_active) {
            return $html;
        }
        
        // Get columns settings
        $columns_desktop = $this->get_setting('columns_desktop', 4);
        $columns_tablet = $this->get_setting('columns_tablet', 3);
        $columns_mobile = $this->get_setting('columns_mobile', 2);
        
        // Add MotionFlow classes to the loop container
        $html = str_replace('class="products', 'class="products motionflow-grid motionflow-columns-desktop-' . esc_attr($columns_desktop) . ' motionflow-columns-tablet-' . esc_attr($columns_tablet) . ' motionflow-columns-mobile-' . esc_attr($columns_mobile), $html);
        
        return $html;
    }

    /**
     * Modify the product loop end HTML.
     *
     * @param string $html The original HTML.
     *
     * @return string
     */
    public function product_loop_end($html) {
        if (!$this->is_active) {
            return $html;
        }
        
        return $html;
    }

    /**
     * Add MotionFlow classes to product.
     *
     * @param array $classes The current classes.
     *
     * @return array
     */
    public function add_product_classes($classes) {
        if (!$this->is_active) {
            return $classes;
        }
        
        global $product;
        
        if (!$product) {
            return $classes;
        }
        
        // Add MotionFlow product class
        $classes[] = 'motionflow-product';
        
        // Add stock status class
        $classes[] = 'motionflow-product-' . $product->get_stock_status();
        
        // Add on sale class if product is on sale
        if ($product->is_on_sale()) {
            $classes[] = 'motionflow-product-on-sale';
        }
        
        return $classes;
    }

    /**
     * Actions before each product in the loop.
     *
     * @return void
     */
    public function before_shop_loop_item() {
        if (!$this->is_active) {
            return;
        }
        
        // Add inner wrapper
        echo '<div class="motionflow-product-inner">';
    }

    /**
     * Actions after each product in the loop.
     *
     * @return void
     */
    public function after_shop_loop_item() {
        if (!$this->is_active) {
            return;
        }
        
        // Add product actions
        echo '<div class="motionflow-product-actions">';
        do_action('motionflow_product_actions');
        echo '</div>';
        
        // Close inner wrapper
        echo '</div>';
    }

    /**
     * Add drag handle to product.
     *
     * @return void
     */
    public function add_drag_handle() {
        if (!$this->is_active || !$this->get_setting('enable_drag_to_cart', true)) {
            return;
        }
        
        global $product;
        
        if (!$product) {
            return;
        }
        
        // Add drag handle
        echo '<div class="motionflow-drag-handle" data-product-id="' . esc_attr($product->get_id()) . '" title="' . esc_attr__('Drag to add to cart', 'motionflow') . '"></div>';
    }

    /**
     * Add modal trigger to product.
     *
     * @return void
     */
    public function add_modal_trigger() {
        if (!$this->is_active || !$this->get_setting('enable_modal', true)) {
            return;
        }
        
        global $product;
        
        if (!$product) {
            return;
        }
        
        // Add modal trigger
        echo '<a href="#" class="motionflow-modal-trigger" data-product-id="' . esc_attr($product->get_id()) . '">' . esc_html__('Quick View', 'motionflow') . '</a>';
    }

    /**
     * Add cart sidebar to the footer.
     *
     * @return void
     */
    public function add_cart_sidebar() {
        if (!$this->is_active || !$this->get_setting('enable_drag_to_cart', true)) {
            return;
        }
        
        // Get cart position
        $position = $this->get_setting('cart_position_desktop', 'right');
        
        // Add cart sidebar
        ?>
        <div class="motionflow-cart-sidebar motionflow-cart-position-<?php echo esc_attr($position); ?>" data-motionflow-cart>
            <div class="motionflow-cart-header">
                <h3><?php esc_html_e('Your Cart', 'motionflow'); ?></h3>
                <a href="#" class="motionflow-cart-close">&times;</a>
            </div>
            <div class="motionflow-cart-content">
                <div class="motionflow-cart-empty-message"><?php esc_html_e('Your cart is empty.', 'motionflow'); ?></div>
                <div class="motionflow-cart-items"></div>
            </div>
            <div class="motionflow-cart-footer">
                <div class="motionflow-cart-total">
                    <span class="motionflow-cart-total-label"><?php esc_html_e('Total:', 'motionflow'); ?></span>
                    <span class="motionflow-cart-total-value"></span>
                </div>
                <div class="motionflow-cart-actions">
                    <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="motionflow-cart-view-cart"><?php esc_html_e('View Cart', 'motionflow'); ?></a>
                    <a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="motionflow-cart-checkout"><?php esc_html_e('Checkout', 'motionflow'); ?></a>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Add modal container to the footer.
     *
     * @return void
     */
    public function add_modal_container() {
        if (!$this->is_active || !$this->get_setting('enable_modal', true)) {
            return;
        }
        
        // Add modal container
        ?>
        <div class="motionflow-modal-overlay" style="display: none;"></div>
        <div class="motionflow-modal-container" style="display: none;">
            <div class="motionflow-modal-close">&times;</div>
            <div class="motionflow-modal-content"></div>
        </div>
        <?php
    }

    /**
     * Get a setting value.
     *
     * @param string $key     The setting key.
     * @param mixed  $default The default value if setting doesn't exist.
     *
     * @return mixed
     */
    protected function get_setting($key, $default = null) {
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }
}