<?php
/**
 * AJAX Handler Class
 *
 * @package    MotionFlow
 * @subpackage MotionFlow/includes/api
 */

namespace MotionFlow\API;

/**
 * Handles AJAX requests for the plugin.
 */
class Ajax_Handler {

    /**
     * Initialize the class.
     */
    public function __construct() {
        $this->register_ajax_hooks();
    }

    /**
     * Register AJAX hooks.
     *
     * @return void
     */
    private function register_ajax_hooks() {
        // Public AJAX actions
        add_action('wp_ajax_motionflow_filter_products', [$this, 'filter_products']);
        add_action('wp_ajax_nopriv_motionflow_filter_products', [$this, 'filter_products']);
        
        add_action('wp_ajax_motionflow_add_to_cart', [$this, 'add_to_cart']);
        add_action('wp_ajax_nopriv_motionflow_add_to_cart', [$this, 'add_to_cart']);
        
        add_action('wp_ajax_motionflow_update_cart_item', [$this, 'update_cart_item']);
        add_action('wp_ajax_nopriv_motionflow_update_cart_item', [$this, 'update_cart_item']);
        
        add_action('wp_ajax_motionflow_remove_cart_item', [$this, 'remove_cart_item']);
        add_action('wp_ajax_nopriv_motionflow_remove_cart_item', [$this, 'remove_cart_item']);
        
        add_action('wp_ajax_motionflow_get_modal_content', [$this, 'get_modal_content']);
        add_action('wp_ajax_nopriv_motionflow_get_modal_content', [$this, 'get_modal_content']);
        
        add_action('wp_ajax_motionflow_track_event', [$this, 'track_event']);
        add_action('wp_ajax_nopriv_motionflow_track_event', [$this, 'track_event']);
        
        // Admin AJAX actions
        add_action('wp_ajax_motionflow_save_settings', [$this, 'save_settings']);
        add_action('wp_ajax_motionflow_get_analytics', [$this, 'get_analytics']);
    }

    /**
     * Filter products via AJAX.
     *
     * @return void
     */
    public function filter_products() {
        // Check nonce
        $this->verify_nonce('motionflow_filter_nonce', 'filter_products');
        
        // Parse input data
        $filters = isset($_POST['filters']) ? $this->sanitize_filters($_POST['filters']) : [];
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 20;
        $layout = isset($_POST['layout']) ? sanitize_text_field($_POST['layout']) : 'grid';
        
        try {
            // Get WooCommerce integration
            $woocommerce = new \MotionFlow\Integrations\WooCommerce();
            
            // Build query args
            $args = [
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => $per_page,
                'paged' => $page,
                'tax_query' => [
                    [
                        'taxonomy' => 'product_visibility',
                        'field' => 'name',
                        'terms' => 'exclude-from-catalog',
                        'operator' => 'NOT IN',
                    ],
                ],
            ];
            
            // Apply filters to query args
            $this->apply_filters_to_query_args($args, $filters);
            
            // Get products data
            $products_data = $woocommerce->get_products_grid_data($args);
            
            // Get grid HTML
            $grid_html = $this->get_filtered_grid_html($products_data, $layout);
            
            // Prepare response
            $response = [
                'success' => true,
                'products' => $products_data['products'],
                'total' => $products_data['total'],
                'pages' => $products_data['pages'],
                'current_page' => $page,
                'html' => $grid_html,
            ];
            
            // Log success
            \MotionFlow_Logger::info('Products filtered successfully', [
                'filters' => $filters,
                'results' => count($products_data['products']),
                'total' => $products_data['total'],
            ]);
            
            // Return response
            wp_send_json_success($response);
        } catch (\Exception $e) {
            // Log error
            \MotionFlow_Logger::error('Error filtering products', [
                'error' => $e->getMessage(),
                'filters' => $filters,
            ]);
            
            // Return error
            wp_send_json_error([
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Add a product to cart via AJAX.
     *
     * @return void
     */
    public function add_to_cart() {
        // Check nonce
        $this->verify_nonce('motionflow_cart_nonce', 'add_to_cart');
        
        // Parse input data
        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $variation_id = isset($_POST['variation_id']) ? absint($_POST['variation_id']) : 0;
        $quantity = isset($_POST['quantity']) ? absint($_POST['quantity']) : 1;
        
        // Sanitize variation data
        $variation = [];
        if (isset($_POST['variation']) && is_array($_POST['variation'])) {
            foreach ($_POST['variation'] as $key => $value) {
                $variation[sanitize_text_field($key)] = sanitize_text_field($value);
            }
        }
        
        try {
            // Validate product ID
            if ($product_id <= 0) {
                throw new \Exception(__('Invalid product ID.', 'motionflow'));
            }
            
            // Get product
            $product = wc_get_product($product_id);
            
            if (!$product) {
                throw new \Exception(__('Product not found.', 'motionflow'));
            }
            
            // Check if product can be purchased
            if (!$product->is_purchasable() || !$product->is_in_stock()) {
                throw new \Exception(__('Product cannot be purchased.', 'motionflow'));
            }
            
            // Add to cart
            $added = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation);
            
            if (!$added) {
                throw new \Exception(__('Failed to add product to cart.', 'motionflow'));
            }
            
            // Calculate cart fragments
            $fragments = $this->get_cart_fragments();
            
            // Prepare response
            $response = [
                'success' => true,
                'product_id' => $product_id,
                'cart_count' => WC()->cart->get_cart_contents_count(),
                'cart_total' => WC()->cart->get_cart_total(),
                'fragments' => $fragments,
            ];
            
            // Log success
            \MotionFlow_Logger::info('Product added to cart', [
                'product_id' => $product_id,
                'quantity' => $quantity,
                'variation_id' => $variation_id,
            ]);
            
            // Return response
            wp_send_json_success($response);
        } catch (\Exception $e) {
            // Log error
            \MotionFlow_Logger::error('Error adding product to cart', [
                'error' => $e->getMessage(),
                'product_id' => $product_id,
            ]);
            
            // Return error
            wp_send_json_error([
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update cart item quantity via AJAX.
     *
     * @return void
     */
    public function update_cart_item() {
        // Check nonce
        $this->verify_nonce('motionflow_cart_nonce', 'update_cart_item');
        
        // Parse input data
        $cart_item_key = isset($_POST['cart_item_key']) ? sanitize_text_field($_POST['cart_item_key']) : '';
        $quantity = isset($_POST['quantity']) ? absint($_POST['quantity']) : 1;
        
        try {
            // Validate cart item key
            if (empty($cart_item_key)) {
                throw new \Exception(__('Invalid cart item key.', 'motionflow'));
            }
            
            // Get cart item
            $cart_item = WC()->cart->get_cart_item($cart_item_key);
            
            if (!$cart_item) {
                throw new \Exception(__('Cart item not found.', 'motionflow'));
            }
            
            // Update cart item quantity
            $updated = WC()->cart->set_quantity($cart_item_key, $quantity);
            
            if (!$updated) {
                throw new \Exception(__('Failed to update cart item.', 'motionflow'));
            }
            
            // Calculate cart fragments
            $fragments = $this->get_cart_fragments();
            
            // Prepare response
            $response = [
                'success' => true,
                'cart_item_key' => $cart_item_key,
                'quantity' => $quantity,
                'cart_count' => WC()->cart->get_cart_contents_count(),
                'cart_total' => WC()->cart->get_cart_total(),
                'fragments' => $fragments,
            ];
            
            // Log success
            \MotionFlow_Logger::info('Cart item updated', [
                'cart_item_key' => $cart_item_key,
                'quantity' => $quantity,
            ]);
            
            // Return response
            wp_send_json_success($response);
        } catch (\Exception $e) {
            // Log error
            \MotionFlow_Logger::error('Error updating cart item', [
                'error' => $e->getMessage(),
                'cart_item_key' => $cart_item_key,
            ]);
            
            // Return error
            wp_send_json_error([
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Remove cart item via AJAX.
     *
     * @return void
     */
    public function remove_cart_item() {
        // Check nonce
        $this->verify_nonce('motionflow_cart_nonce', 'remove_cart_item');
        
        // Parse input data
        $cart_item_key = isset($_POST['cart_item_key']) ? sanitize_text_field($_POST['cart_item_key']) : '';
        
        try {
            // Validate cart item key
            if (empty($cart_item_key)) {
                throw new \Exception(__('Invalid cart item key.', 'motionflow'));
            }
            
            // Remove cart item
            $removed = WC()->cart->remove_cart_item($cart_item_key);
            
            if (!$removed) {
                throw new \Exception(__('Failed to remove cart item.', 'motionflow'));
            }
            
            // Calculate cart fragments
            $fragments = $this->get_cart_fragments();
            
            // Prepare response
            $response = [
                'success' => true,
                'cart_item_key' => $cart_item_key,
                'cart_count' => WC()->cart->get_cart_contents_count(),
                'cart_total' => WC()->cart->get_cart_total(),
                'fragments' => $fragments,
            ];
            
            // Log success
            \MotionFlow_Logger::info('Cart item removed', [
                'cart_item_key' => $cart_item_key,
            ]);
            
            // Return response
            wp_send_json_success($response);
        } catch (\Exception $e) {
            // Log error
            \MotionFlow_Logger::error('Error removing cart item', [
                'error' => $e->getMessage(),
                'cart_item_key' => $cart_item_key,
            ]);
            
            // Return error
            wp_send_json_error([
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get modal content via AJAX.
     *
     * @return void
     */
    public function get_modal_content() {
        // Check nonce
        $this->verify_nonce('motionflow_modal_nonce', 'get_modal_content');
        
        // Parse input data
        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        
        try {
            // Validate product ID
            if ($product_id <= 0) {
                throw new \Exception(__('Invalid product ID.', 'motionflow'));
            }
            
            // Get product
            $product = wc_get_product($product_id);
            
            if (!$product) {
                throw new \Exception(__('Product not found.', 'motionflow'));
            }
            
            // Get modal HTML
            ob_start();
            include MOTIONFLOW_PLUGIN_DIR . 'public/partials/modal.php';
            $modal_html = ob_get_clean();
            
            // Prepare response
            $response = [
                'success' => true,
                'product_id' => $product_id,
                'html' => $modal_html,
            ];
            
            // Log success
            \MotionFlow_Logger::info('Modal content retrieved', [
                'product_id' => $product_id,
            ]);
            
            // Return response
            wp_send_json_success($response);
        } catch (\Exception $e) {
            // Log error
            \MotionFlow_Logger::error('Error retrieving modal content', [
                'error' => $e->getMessage(),
                'product_id' => $product_id,
            ]);
            
            // Return error
            wp_send_json_error([
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Track event via AJAX.
     *
     * @return void
     */
    public function track_event() {
        // Check nonce
        $this->verify_nonce('motionflow_analytics_nonce', 'track_event');
        
        // Parse input data
        $event_type = isset($_POST['event_type']) ? sanitize_text_field($_POST['event_type']) : '';
        $event_data = isset($_POST['event_data']) ? $this->sanitize_event_data($_POST['event_data']) : [];
        
        try {
            // Validate event type
            if (empty($event_type)) {
                throw new \Exception(__('Invalid event type.', 'motionflow'));
            }
            
            // Get config
            $config = \MotionFlow\Config::instance();
            
            // Skip if analytics is disabled
            if (!$config->is_feature_enabled('analytics')) {
                wp_send_json_success([
                    'tracked' => false,
                    'reason' => 'Analytics is disabled.',
                ]);
                return;
            }
            
            // Track event
            $analytics = \MotionFlow\Analytics::instance();
            $tracked = $analytics->track_event($event_type, $event_data);
            
            if (!$tracked) {
                throw new \Exception(__('Failed to track event.', 'motionflow'));
            }
            
            // Prepare response
            $response = [
                'success' => true,
                'tracked' => true,
                'event_type' => $event_type,
            ];
            
            // Log success
            \MotionFlow_Logger::info('Event tracked', [
                'event_type' => $event_type,
                'event_data' => $event_data,
            ]);
            
            // Return response
            wp_send_json_success($response);
        } catch (\Exception $e) {
            // Log error
            \MotionFlow_Logger::error('Error tracking event', [
                'error' => $e->getMessage(),
                'event_type' => $event_type,
            ]);
            
            // Return error
            wp_send_json_error([
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Save settings via AJAX.
     *
     * @return void
     */
    public function save_settings() {
        // Check nonce and capability
        $this->verify_nonce('motionflow_admin_nonce', 'save_settings');
        $this->check_capability('manage_motionflow');
        
        // Parse input data
        $settings_group = isset($_POST['group']) ? sanitize_text_field($_POST['group']) : '';
        $settings = isset($_POST['settings']) ? $this->sanitize_settings($_POST['settings']) : [];
        
        try {
            // Validate settings group
            if (empty($settings_group)) {
                throw new \Exception(__('Invalid settings group.', 'motionflow'));
            }
            
            // Check if settings group is valid
            $valid_groups = ['general', 'grid', 'filter', 'cart'];
            
            if (!in_array($settings_group, $valid_groups)) {
                throw new \Exception(__('Invalid settings group.', 'motionflow'));
            }
            
            // Get config
            $config = \MotionFlow\Config::instance();
            
            // Update settings
            $updated = $config->update_group($settings_group, $settings);
            
            if (!$updated) {
                throw new \Exception(__('Failed to save settings.', 'motionflow'));
            }
            
            // Prepare response
            $response = [
                'success' => true,
                'group' => $settings_group,
                'message' => __('Settings saved successfully.', 'motionflow'),
            ];
            
            // Log success
            \MotionFlow_Logger::info('Settings saved', [
                'group' => $settings_group,
                'settings' => $settings,
                'user_id' => get_current_user_id(),
            ]);
            
            // Return response
            wp_send_json_success($response);
        } catch (\Exception $e) {
            // Log error
            \MotionFlow_Logger::error('Error saving settings', [
                'error' => $e->getMessage(),
                'group' => $settings_group,
                'user_id' => get_current_user_id(),
            ]);
            
            // Return error
            wp_send_json_error([
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get analytics via AJAX.
     *
     * @return void
     */
    public function get_analytics() {
        // Check nonce and capability
        $this->verify_nonce('motionflow_admin_nonce', 'get_analytics');
        $this->check_capability('view_motionflow_analytics');
        
        // Parse input data
        $report_type = isset($_POST['report_type']) ? sanitize_text_field($_POST['report_type']) : '';
        $args = isset($_POST['args']) ? $this->sanitize_analytics_args($_POST['args']) : [];
        
        try {
            // Validate report type
            if (empty($report_type)) {
                throw new \Exception(__('Invalid report type.', 'motionflow'));
            }
            
            // Get analytics
            $analytics = \MotionFlow\Analytics::instance();
            $report = $analytics->get_report($report_type, $args);
            
            if (!$report) {
                throw new \Exception(__('Failed to generate report.', 'motionflow'));
            }
            
            // Prepare response
            $response = [
                'success' => true,
                'report_type' => $report_type,
                'data' => $report,
            ];
            
            // Log success
            \MotionFlow_Logger::info('Analytics report generated', [
                'report_type' => $report_type,
                'args' => $args,
                'user_id' => get_current_user_id(),
            ]);
            
            // Return response
            wp_send_json_success($response);
        } catch (\Exception $e) {
            // Log error
            \MotionFlow_Logger::error('Error generating analytics report', [
                'error' => $e->getMessage(),
                'report_type' => $report_type,
                'user_id' => get_current_user_id(),
            ]);
            
            // Return error
            wp_send_json_error([
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Verify nonce.
     *
     * @param string $nonce_name The nonce name.
     * @param string $action     The nonce action.
     *
     * @return void
     * @throws \Exception If nonce verification fails.
     */
    private function verify_nonce($nonce_name, $action) {
        // Check if nonce exists
        if (!isset($_REQUEST['nonce'])) {
            throw new \Exception(__('Security check failed. Please refresh the page and try again.', 'motionflow'));
        }
        
        // Verify nonce
        $nonce = sanitize_text_field($_REQUEST['nonce']);
        
        if (!wp_verify_nonce($nonce, $nonce_name)) {
            throw new \Exception(__('Security check failed. Please refresh the page and try again.', 'motionflow'));
        }
    }

    /**
     * Check user capability.
     *
     * @param string $capability The capability to check.
     *
     * @return void
     * @throws \Exception If user doesn't have the required capability.
     */
    private function check_capability($capability) {
        if (!current_user_can($capability)) {
            throw new \Exception(__('You do not have permission to perform this action.', 'motionflow'));
        }
    }

    /**
     * Sanitize filters.
     *
     * @param array $filters The filters to sanitize.
     *
     * @return array
     */
    private function sanitize_filters($filters) {
        if (!is_array($filters)) {
            return [];
        }
        
        $sanitized = [];
        
        foreach ($filters as $key => $value) {
            // Sanitize key
            $key = sanitize_text_field($key);
            
            if (is_array($value)) {
                // Handle nested arrays
                $sanitized[$key] = $this->sanitize_filters($value);
            } else {
                // Sanitize value
                $sanitized[$key] = sanitize_text_field($value);
            }
        }
        
        return $sanitized;
    }

    /**
     * Sanitize settings.
     *
     * @param array $settings The settings to sanitize.
     *
     * @return array
     */
    private function sanitize_settings($settings) {
        if (!is_array($settings)) {
            return [];
        }
        
        $sanitized = [];
        
        foreach ($settings as $key => $value) {
            // Sanitize key
            $key = sanitize_text_field($key);
            
            if (is_array($value)) {
                // Handle nested arrays
                $sanitized[$key] = $this->sanitize_settings($value);
            } elseif (is_numeric($value)) {
                // Handle numeric values
                $sanitized[$key] = $value;
            } elseif (is_bool($value) || $value === 'true' || $value === 'false') {
                // Handle boolean values
                $sanitized[$key] = ($value === true || $value === 'true') ? true : false;
            } else {
                // Sanitize string values
                $sanitized[$key] = sanitize_text_field($value);
            }
        }
        
        return $sanitized;
    }

    /**
     * Sanitize event data.
     *
     * @param array $event_data The event data to sanitize.
     *
     * @return array
     */
    private function sanitize_event_data($event_data) {
        if (!is_array($event_data)) {
            return [];
        }
        
        $sanitized = [];
        
        foreach ($event_data as $key => $value) {
            // Sanitize key
            $key = sanitize_text_field($key);
            
            if (is_array($value)) {
                // Handle nested arrays
                $sanitized[$key] = $this->sanitize_event_data($value);
            } elseif (is_numeric($value)) {
                // Handle numeric values
                $sanitized[$key] = $value;
            } else {
                // Sanitize string values
                $sanitized[$key] = sanitize_text_field($value);
            }
        }
        
        return $sanitized;
    }

    /**
     * Sanitize analytics arguments.
     *
     * @param array $args The arguments to sanitize.
     *
     * @return array
     */
    private function sanitize_analytics_args($args) {
        if (!is_array($args)) {
            return [];
        }
        
        $sanitized = [];
        
        // Common args
        if (isset($args['date_from'])) {
            $sanitized['date_from'] = sanitize_text_field($args['date_from']);
        }
        
        if (isset($args['date_to'])) {
            $sanitized['date_to'] = sanitize_text_field($args['date_to']);
        }
        
        if (isset($args['group_by'])) {
            $sanitized['group_by'] = sanitize_text_field($args['group_by']);
        }
        
        // Additional args
        if (isset($args['filter'])) {
            $sanitized['filter'] = $this->sanitize_filters($args['filter']);
        }
        
        if (isset($args['limit'])) {
            $sanitized['limit'] = absint($args['limit']);
        }
        
        return $sanitized;
    }

    /**
     * Apply filters to query args.
     *
     * @param array $args    The query args.
     * @param array $filters The filters to apply.
     *
     * @return void
     */
    private function apply_filters_to_query_args(&$args, $filters) {
        // Get WooCommerce integration
        $woocommerce = new \MotionFlow\Integrations\WooCommerce();
        
        // Apply filters to query
        $woocommerce->apply_filters_to_query($args, $filters);
        
        // Allow external modifications
        $args = apply_filters('motionflow_filter_query_args', $args, $filters);
    }

    /**
     * Get filtered grid HTML.
     *
     * @param array  $data   The products data.
     * @param string $layout The grid layout.
     *
     * @return string
     */
    private function get_filtered_grid_html($data, $layout) {
        // Start buffer
        ob_start();
        
        // Get config
        $config = \MotionFlow\Config::instance();
        
        // Set up attributes for shortcode rendering
        $atts = [
            'layout' => $layout,
            'columns_desktop' => $config->get('grid', 'columns_desktop', 4),
            'columns_tablet' => $config->get('grid', 'columns_tablet', 3),
            'columns_mobile' => $config->get('grid', 'columns_mobile', 2),
        ];
        
        // Include grid template
        include MOTIONFLOW_PLUGIN_DIR . 'public/partials/grid.php';
        
        // Get buffer contents
        $html = ob_get_clean();
        
        return $html;
    }

    /**
     * Get cart fragments.
     *
     * @return array
     */
    private function get_cart_fragments() {
        $fragments = [];
        
        // Get cart count
        ob_start();
        ?>
        <span class="motionflow-cart-button-count"><?php echo esc_html(WC()->cart->get_cart_contents_count()); ?></span>
        <?php
        $fragments['.motionflow-cart-button-count'] = ob_get_clean();
        
        // Get cart total
        ob_start();
        ?>
        <span class="motionflow-cart-total-value"><?php echo WC()->cart->get_cart_total(); ?></span>
        <?php
        $fragments['.motionflow-cart-total-value'] = ob_get_clean();
        
        // Get cart items
        ob_start();
        $cart_items = WC()->cart->get_cart();
        ?>
        <div class="motionflow-cart-items">
            <?php if (empty($cart_items)) : ?>
                <div class="motionflow-cart-empty-message">
                    <?php esc_html_e('Your cart is empty.', 'motionflow'); ?>
                </div>
            <?php else : ?>
                <?php foreach ($cart_items as $cart_item_key => $cart_item) : 
                    $product = $cart_item['data'];
                    $product_id = $cart_item['product_id'];
                    $variation_id = $cart_item['variation_id'];
                    $quantity = $cart_item['quantity'];
                    $thumbnail = $product->get_image('thumbnail');
                    $price = WC()->cart->get_product_price($product);
                    $subtotal = WC()->cart->get_product_subtotal($product, $quantity);
                ?>
                    <div class="motionflow-cart-item" data-cart-key="<?php echo esc_attr($cart_item_key); ?>">
                        <div class="motionflow-cart-item-image">
                            <?php echo $thumbnail; ?>
                        </div>
                        
                        <div class="motionflow-cart-item-details">
                            <div class="motionflow-cart-item-title">
                                <?php echo esc_html($product->get_name()); ?>
                            </div>
                            
                            <div class="motionflow-cart-item-price">
                                <?php echo $price; ?>
                            </div>
                            
                            <div class="motionflow-cart-item-quantity">
                                <div class="motionflow-cart-item-quantity-button motionflow-cart-item-quantity-minus">-</div>
                                <div class="motionflow-cart-item-quantity-value"><?php echo esc_html($quantity); ?></div>
                                <div class="motionflow-cart-item-quantity-button motionflow-cart-item-quantity-plus">+</div>
                            </div>
                        </div>
                        
                        <div class="motionflow-cart-item-remove">&times;</div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
        $fragments['.motionflow-cart-items'] = ob_get_clean();
        
        // Allow external modifications
        $fragments = apply_filters('motionflow_cart_fragments', $fragments);
        
        return $fragments;
    }
}