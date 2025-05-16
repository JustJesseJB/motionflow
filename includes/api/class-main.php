<?php
/**
 * API Main Class
 *
 * @package    MotionFlow
 * @subpackage MotionFlow/includes/api
 */

namespace MotionFlow\API;

/**
 * The REST API functionality of the plugin.
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
     * Register the REST API routes.
     *
     * @return void
     */
    public function register_routes() {
        // Register REST API routes
        register_rest_route('motionflow/v1', '/products', [
            'methods' => 'GET',
            'callback' => [$this, 'get_products'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('motionflow/v1', '/filters', [
            'methods' => 'GET',
            'callback' => [$this, 'get_filters'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('motionflow/v1', '/add-to-cart', [
            'methods' => 'POST',
            'callback' => [$this, 'add_to_cart'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Get products for the REST API.
     *
     * @param \WP_REST_Request $request The request object.
     *
     * @return \WP_REST_Response
     */
    public function get_products($request) {
        // Get WooCommerce integration
        $woocommerce = new \MotionFlow\Integrations\WooCommerce();
        
        // Parse parameters
        $params = $request->get_params();
        
        // Build query args
        $args = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => isset($params['per_page']) ? intval($params['per_page']) : 20,
            'paged' => isset($params['page']) ? intval($params['page']) : 1,
        ];
        
        // Add category filter
        if (!empty($params['category'])) {
            $args['tax_query'][] = [
                'taxonomy' => 'product_cat',
                'field' => 'slug',
                'terms' => $params['category'],
            ];
        }
        
        // Add tag filter
        if (!empty($params['tag'])) {
            $args['tax_query'][] = [
                'taxonomy' => 'product_tag',
                'field' => 'slug',
                'terms' => $params['tag'],
            ];
        }
        
        // Add price filter
        if (!empty($params['min_price']) || !empty($params['max_price'])) {
            $args['meta_query'] = [
                'relation' => 'AND',
            ];
            
            if (!empty($params['min_price'])) {
                $args['meta_query'][] = [
                    'key' => '_price',
                    'value' => floatval($params['min_price']),
                    'compare' => '>=',
                    'type' => 'NUMERIC',
                ];
            }
            
            if (!empty($params['max_price'])) {
                $args['meta_query'][] = [
                    'key' => '_price',
                    'value' => floatval($params['max_price']),
                    'compare' => '<=',
                    'type' => 'NUMERIC',
                ];
            }
        }
        
        // Get products data
        $data = $woocommerce->get_products_grid_data($args);
        
        // Return response
        return rest_ensure_response($data);
    }

    /**
     * Get available filters for the REST API.
     *
     * @param \WP_REST_Request $request The request object.
     *
     * @return \WP_REST_Response
     */
    public function get_filters($request) {
        // Get WooCommerce integration
        $woocommerce = new \MotionFlow\Integrations\WooCommerce();
        
        // Get available filters
        $filters = $woocommerce->get_available_filters();
        
        // Return response
        return rest_ensure_response($filters);
    }

    /**
     * Add to cart for the REST API.
     *
     * @param \WP_REST_Request $request The request object.
     *
     * @return \WP_REST_Response
     */
    public function add_to_cart($request) {
        // Parse parameters
        $params = $request->get_params();
        
        // Required parameters
        if (empty($params['product_id'])) {
            return new \WP_Error('missing_product_id', __('Product ID is required', 'motionflow'), ['status' => 400]);
        }
        
        // Get product
        $product_id = intval($params['product_id']);
        $variation_id = !empty($params['variation_id']) ? intval($params['variation_id']) : 0;
        $quantity = !empty($params['quantity']) ? intval($params['quantity']) : 1;
        
        // Add to cart
        $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity, $variation_id);
        
        // Check if added successfully
        if (!$cart_item_key) {
            return new \WP_Error('add_to_cart_failed', __('Failed to add product to cart', 'motionflow'), ['status' => 400]);
        }
        
        // Get updated cart
        $cart = WC()->cart;
        
        // Return response
        return rest_ensure_response([
            'success' => true,
            'cart_item_key' => $cart_item_key,
            'cart_count' => $cart->get_cart_contents_count(),
            'cart_total' => $cart->get_total(),
        ]);
    }
}