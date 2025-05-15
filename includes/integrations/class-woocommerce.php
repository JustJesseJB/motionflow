<?php
/**
 * WooCommerce Integration Class
 *
 * @package    MotionFlow
 * @subpackage MotionFlow/includes/integrations
 */

namespace MotionFlow\Integrations;

/**
 * Handles WooCommerce integration functionality.
 */
class WooCommerce {

    /**
     * Initialize the class.
     */
    public function __construct() {
        // Initialize the integration
    }

    /**
     * Display filter widgets before the shop loop.
     *
     * @return void
     */
    public function display_filters() {
        // Don't display if we're already using a shortcode on the page
        global $post;
        if (is_a($post, 'WP_Post') && (
            has_shortcode($post->post_content, 'motionflow') ||
            has_shortcode($post->post_content, 'motionflow_filters')
        )) {
            return;
        }
        
        // Get the configuration
        $config = \MotionFlow\Config::instance();
        
        // Skip if we're not on a shop page
        if (!is_shop() && !is_product_category() && !is_product_tag()) {
            return;
        }
        
        // Get filter position
        $filter_position = $config->get('filter', 'filter_position', 'top');
        
        // Only show in top position by default
        if ($filter_position !== 'top') {
            return;
        }
        
        // Load the filters
        echo do_shortcode('[motionflow_filters]');
    }

    /**
     * Modify the product query based on our filters.
     *
     * @param \WP_Query $query The product query.
     * @param \WC_Query $wc_query The WooCommerce query.
     *
     * @return \WP_Query
     */
    public function modify_product_query($query, $wc_query) {
        // Only modify main queries on the frontend
        if (!$query->is_main_query() || is_admin()) {
            return $query;
        }
        
        // Only modify product queries
        if (!$query->is_post_type_archive('product') && 
            !$query->is_tax(get_object_taxonomies('product'))) {
            return $query;
        }
        
        // Get the configuration
        $config = \MotionFlow\Config::instance();
        
        // Check if AJAX filtering is enabled
        if (!$config->is_feature_enabled('ajax_filtering')) {
            return $query;
        }
        
        // Get filter parameters
        $filters = $this->get_active_filters();
        
        // Apply filters to query
        if (!empty($filters)) {
            $this->apply_filters_to_query($query, $filters);
        }
        
        return $query;
    }

    /**
     * Get active filters from request parameters.
     *
     * @return array
     */
    public function get_active_filters() {
        $filters = [];
        
        // Get filter parameters from request
        $params = $_GET;
        
        // Process category filter
        if (!empty($params['product_cat'])) {
            $filters['product_cat'] = sanitize_text_field($params['product_cat']);
        }
        
        // Process tag filter
        if (!empty($params['product_tag'])) {
            $filters['product_tag'] = sanitize_text_field($params['product_tag']);
        }
        
        // Process price filter
        if (!empty($params['min_price']) || !empty($params['max_price'])) {
            $filters['price'] = [
                'min' => isset($params['min_price']) ? floatval($params['min_price']) : '',
                'max' => isset($params['max_price']) ? floatval($params['max_price']) : '',
            ];
        }
        
        // Process attribute filters
        foreach ($params as $key => $value) {
            if (strpos($key, 'filter_') === 0 && !empty($value)) {
                $attribute = str_replace('filter_', '', $key);
                $filters['attribute'][$attribute] = sanitize_text_field($value);
            }
        }
        
        // Process sorting
        if (!empty($params['orderby'])) {
            $filters['orderby'] = sanitize_text_field($params['orderby']);
        }
        
        // Process custom filters
        $filters = apply_filters('motionflow_active_filters', $filters, $params);
        
        return $filters;
    }

    /**
     * Apply filters to a WP_Query object.
     *
     * @param \WP_Query $query   The query to modify.
     * @param array     $filters The active filters.
     *
     * @return void
     */
    private function apply_filters_to_query($query, $filters) {
        // Category filter
        if (isset($filters['product_cat'])) {
            $query->set('product_cat', $filters['product_cat']);
        }
        
        // Tag filter
        if (isset($filters['product_tag'])) {
            $query->set('product_tag', $filters['product_tag']);
        }
        
        // Price filter
        if (isset($filters['price'])) {
            $meta_query = $query->get('meta_query', []);
            
            $price_filter = [
                'relation' => 'AND',
            ];
            
            if (!empty($filters['price']['min'])) {
                $price_filter[] = [
                    'key' => '_price',
                    'value' => $filters['price']['min'],
                    'compare' => '>=',
                    'type' => 'NUMERIC',
                ];
            }
            
            if (!empty($filters['price']['max'])) {
                $price_filter[] = [
                    'key' => '_price',
                    'value' => $filters['price']['max'],
                    'compare' => '<=',
                    'type' => 'NUMERIC',
                ];
            }
            
            $meta_query[] = $price_filter;
            $query->set('meta_query', $meta_query);
        }
        
        // Attribute filters
        if (isset($filters['attribute']) && is_array($filters['attribute'])) {
            $tax_query = $query->get('tax_query', []);
            
            foreach ($filters['attribute'] as $attribute => $value) {
                $tax_query[] = [
                    'taxonomy' => 'pa_' . $attribute,
                    'field' => 'slug',
                    'terms' => explode(',', $value),
                    'operator' => 'IN',
                ];
            }
            
            $query->set('tax_query', $tax_query);
        }
        
        // Apply custom query modifications
        do_action('motionflow_apply_filters_to_query', $query, $filters);
    }

    /**
     * Get available product filters.
     *
     * @return array
     */
    public function get_available_filters() {
        $filters = [];
        
        // Get product categories
        $categories = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
        ]);
        
        if (!is_wp_error($categories) && !empty($categories)) {
            $filters['categories'] = $categories;
        }
        
        // Get product tags
        $tags = get_terms([
            'taxonomy' => 'product_tag',
            'hide_empty' => true,
        ]);
        
        if (!is_wp_error($tags) && !empty($tags)) {
            $filters['tags'] = $tags;
        }
        
        // Get price range
        global $wpdb;
        $prices = $wpdb->get_row("
            SELECT min(meta_value + 0) as min_price, max(meta_value + 0) as max_price
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_price'
            AND meta_value != ''
        ");
        
        if ($prices) {
            $filters['price_range'] = [
                'min' => floor($prices->min_price),
                'max' => ceil($prices->max_price),
            ];
        }
        
        // Get product attributes
        $attributes = wc_get_attribute_taxonomies();
        
        if (!empty($attributes)) {
            $filters['attributes'] = [];
            
            foreach ($attributes as $attribute) {
                $terms = get_terms([
                    'taxonomy' => 'pa_' . $attribute->attribute_name,
                    'hide_empty' => true,
                ]);
                
                if (!is_wp_error($terms) && !empty($terms)) {
                    $filters['attributes'][$attribute->attribute_name] = [
                        'name' => $attribute->attribute_label,
                        'terms' => $terms,
                    ];
                }
            }
        }
        
        // Allow custom filters
        $filters = apply_filters('motionflow_available_filters', $filters);
        
        return $filters;
    }

    /**
     * Get product grid data.
     *
     * @param array $args Query arguments.
     *
     * @return array
     */
    public function get_products_grid_data($args = []) {
        // Default arguments
        $default_args = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'tax_query' => [
                [
                    'taxonomy' => 'product_visibility',
                    'field' => 'name',
                    'terms' => 'exclude-from-catalog',
                    'operator' => 'NOT IN',
                ],
            ],
        ];
        
        // Merge with provided arguments
        $args = wp_parse_args($args, $default_args);
        
        // Query products
        $products_query = new \WP_Query($args);
        
        $products_data = [];
        
        if ($products_query->have_posts()) {
            while ($products_query->have_posts()) {
                $products_query->the_post();
                global $product;
                
                if (!$product) {
                    continue;
                }
                
                // Build product data
                $product_data = [
                    'id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'permalink' => get_permalink($product->get_id()),
                    'price_html' => $product->get_price_html(),
                    'price' => $product->get_price(),
                    'image' => wp_get_attachment_image_src(get_post_thumbnail_id($product->get_id()), 'woocommerce_thumbnail'),
                    'average_rating' => $product->get_average_rating(),
                    'review_count' => $product->get_review_count(),
                    'add_to_cart_url' => $product->add_to_cart_url(),
                    'type' => $product->get_type(),
                    'stock_status' => $product->get_stock_status(),
                    'is_on_sale' => $product->is_on_sale(),
                    'attributes' => [],
                ];
                
                // Get product attributes
                $attributes = $product->get_attributes();
                
                if (!empty($attributes)) {
                    foreach ($attributes as $attribute) {
                        if ($attribute->is_taxonomy()) {
                            $attribute_taxonomy = $attribute->get_taxonomy_object();
                            $attribute_values = wc_get_product_terms(
                                $product->get_id(),
                                $attribute->get_name(),
                                ['fields' => 'names']
                            );
                            
                            $product_data['attributes'][$attribute->get_name()] = [
                                'name' => $attribute_taxonomy->attribute_label,
                                'values' => $attribute_values,
                            ];
                        } else {
                            $product_data['attributes'][$attribute->get_name()] = [
                                'name' => $attribute->get_name(),
                                'values' => $attribute->get_options(),
                            ];
                        }
                    }
                }
                
                // Add to products data array
                $products_data[] = $product_data;
            }
            
            wp_reset_postdata();
        }
        
        return [
            'products' => $products_data,
            'total' => $products_query->found_posts,
            'pages' => $products_query->max_num_pages,
        ];
    }
}