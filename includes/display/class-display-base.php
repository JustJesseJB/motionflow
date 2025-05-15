<?php
/**
 * Display Base Class
 *
 * @package    MotionFlow
 * @subpackage MotionFlow/includes/display
 */

namespace MotionFlow\Display;

/**
 * Base class for display types.
 */
abstract class Display_Base {

    /**
     * Display ID.
     *
     * @var string
     */
    protected $id;

    /**
     * Display settings.
     *
     * @var array
     */
    protected $settings;

    /**
     * Products data.
     *
     * @var array
     */
    protected $products;

    /**
     * Pagination data.
     *
     * @var array
     */
    protected $pagination;

    /**
     * Initialize the display.
     *
     * @param string $id       The display ID.
     * @param array  $settings The display settings.
     */
    public function __construct($id, $settings = []) {
        $this->id = $id;
        $this->settings = $settings;
        $this->products = [];
        $this->pagination = [
            'total' => 0,
            'per_page' => 20,
            'current_page' => 1,
            'total_pages' => 1,
        ];
    }

    /**
     * Set the products data.
     *
     * @param array $products The products data.
     *
     * @return void
     */
    public function set_products($products) {
        $this->products = $products;
    }

    /**
     * Set the pagination data.
     *
     * @param array $pagination The pagination data.
     *
     * @return void
     */
    public function set_pagination($pagination) {
        $this->pagination = wp_parse_args($pagination, $this->pagination);
    }

    /**
     * Render the display.
     *
     * @return string
     */
    abstract public function render();

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

    /**
     * Get the display ID.
     *
     * @return string
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Get the display wrapper classes.
     *
     * @return string
     */
    protected function get_wrapper_classes() {
        $classes = [
            'motionflow-display',
            'motionflow-display-' . $this->id,
        ];
        
        // Add layout class
        $layout = $this->get_setting('layout', 'grid');
        $classes[] = 'motionflow-display-layout-' . sanitize_html_class($layout);
        
        // Add custom classes
        $custom_classes = $this->get_setting('classes', '');
        if (!empty($custom_classes)) {
            $classes = array_merge($classes, explode(' ', $custom_classes));
        }
        
        return implode(' ', array_map('sanitize_html_class', $classes));
    }

    /**
     * Get data attributes for the display wrapper.
     *
     * @return string
     */
    protected function get_data_attributes() {
        $attributes = [
            'data-display-id="' . esc_attr($this->id) . '"',
        ];
        
        // Add layout attribute
        $layout = $this->get_setting('layout', 'grid');
        $attributes[] = 'data-layout="' . esc_attr($layout) . '"';
        
        // Add columns attributes
        $columns_desktop = $this->get_setting('columns_desktop', 4);
        $columns_tablet = $this->get_setting('columns_tablet', 3);
        $columns_mobile = $this->get_setting('columns_mobile', 2);
        
        $attributes[] = 'data-columns-desktop="' . esc_attr($columns_desktop) . '"';
        $attributes[] = 'data-columns-tablet="' . esc_attr($columns_tablet) . '"';
        $attributes[] = 'data-columns-mobile="' . esc_attr($columns_mobile) . '"';
        
        // Add pagination attributes
        $attributes[] = 'data-current-page="' . esc_attr($this->pagination['current_page']) . '"';
        $attributes[] = 'data-total-pages="' . esc_attr($this->pagination['total_pages']) . '"';
        $attributes[] = 'data-per-page="' . esc_attr($this->pagination['per_page']) . '"';
        $attributes[] = 'data-total-products="' . esc_attr($this->pagination['total']) . '"';
        
        // Add custom data attributes
        $custom_data = $this->get_setting('data_attributes', []);
        foreach ($custom_data as $key => $value) {
            $attributes[] = 'data-' . esc_attr($key) . '="' . esc_attr($value) . '"';
        }
        
        return implode(' ', $attributes);
    }

    /**
     * Get the display wrapper opening HTML.
     *
     * @return string
     */
    protected function get_wrapper_start() {
        return '<div class="' . $this->get_wrapper_classes() . '" ' . $this->get_data_attributes() . '>';
    }

    /**
     * Get the display wrapper closing HTML.
     *
     * @return string
     */
    protected function get_wrapper_end() {
        return '</div>';
    }

    /**
     * Render the no products message.
     *
     * @return string
     */
    protected function render_no_products() {
        $message = $this->get_setting('no_products_text', __('No products found.', 'motionflow'));
        
        return '<div class="motionflow-no-products">' . esc_html($message) . '</div>';
    }

    /**
     * Render the loader.
     *
     * @return string
     */
    protected function render_loader() {
        return '<div class="motionflow-loader" style="display:none;"><div class="motionflow-loader-spinner"></div></div>';
    }

    /**
     * Render the pagination.
     *
     * @return string
     */
    protected function render_pagination() {
        // Skip if pagination is disabled
        if (!$this->get_setting('show_pagination', true)) {
            return '';
        }
        
        // Skip if we only have one page
        if ($this->pagination['total_pages'] <= 1) {
            return '';
        }
        
        $html = '<div class="motionflow-pagination">';
        
        // Previous page link
        if ($this->pagination['current_page'] > 1) {
            $prev_url = add_query_arg('paged', $this->pagination['current_page'] - 1);
            $html .= '<a href="' . esc_url($prev_url) . '" class="motionflow-pagination-prev" data-page="' . esc_attr($this->pagination['current_page'] - 1) . '">' . esc_html__('Previous', 'motionflow') . '</a>';
        } else {
            $html .= '<span class="motionflow-pagination-prev motionflow-pagination-disabled">' . esc_html__('Previous', 'motionflow') . '</span>';
        }
        
        // Page numbers
        $html .= '<div class="motionflow-pagination-numbers">';
        
        // Determine which page numbers to show
        $range = 2; // Number of pages to show on each side of current page
        $start_page = max(1, $this->pagination['current_page'] - $range);
        $end_page = min($this->pagination['total_pages'], $this->pagination['current_page'] + $range);
        
        // Always show first page
        if ($start_page > 1) {
            $html .= '<a href="' . esc_url(add_query_arg('paged', 1)) . '" class="motionflow-pagination-number" data-page="1">1</a>';
            
            // Add ellipsis if there's a gap
            if ($start_page > 2) {
                $html .= '<span class="motionflow-pagination-ellipsis">…</span>';
            }
        }
        
        // Page numbers
        for ($i = $start_page; $i <= $end_page; $i++) {
            if ($i === $this->pagination['current_page']) {
                $html .= '<span class="motionflow-pagination-number motionflow-pagination-current">' . $i . '</span>';
            } else {
                $html .= '<a href="' . esc_url(add_query_arg('paged', $i)) . '" class="motionflow-pagination-number" data-page="' . esc_attr($i) . '">' . $i . '</a>';
            }
        }
        
        // Always show last page
        if ($end_page < $this->pagination['total_pages']) {
            // Add ellipsis if there's a gap
            if ($end_page < $this->pagination['total_pages'] - 1) {
                $html .= '<span class="motionflow-pagination-ellipsis">…</span>';
            }
            
            $html .= '<a href="' . esc_url(add_query_arg('paged', $this->pagination['total_pages'])) . '" class="motionflow-pagination-number" data-page="' . esc_attr($this->pagination['total_pages']) . '">' . $this->pagination['total_pages'] . '</a>';
        }
        
        $html .= '</div>';
        
        // Next page link
        if ($this->pagination['current_page'] < $this->pagination['total_pages']) {
            $next_url = add_query_arg('paged', $this->pagination['current_page'] + 1);
            $html .= '<a href="' . esc_url($next_url) . '" class="motionflow-pagination-next" data-page="' . esc_attr($this->pagination['current_page'] + 1) . '">' . esc_html__('Next', 'motionflow') . '</a>';
        } else {
            $html .= '<span class="motionflow-pagination-next motionflow-pagination-disabled">' . esc_html__('Next', 'motionflow') . '</span>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Render the product count information.
     *
     * @return string
     */
    protected function render_product_count() {
        // Skip if product count is disabled
        if (!$this->get_setting('show_product_count', true)) {
            return '';
        }
        
        $total = $this->pagination['total'];
        $start = (($this->pagination['current_page'] - 1) * $this->pagination['per_page']) + 1;
        $end = min($this->pagination['current_page'] * $this->pagination['per_page'], $total);
        
        // If no products
        if ($total === 0) {
            $html = '<div class="motionflow-product-count">';
            $html .= esc_html__('No products found', 'motionflow');
            $html .= '</div>';
            
            return $html;
        }
        
        // With products
        $html = '<div class="motionflow-product-count">';
        
        /* translators: %1$d: start number, %2$d: end number, %3$d: total number */
        $html .= sprintf(
            esc_html__('Showing %1$d-%2$d of %3$d results', 'motionflow'),
            $start,
            $end,
            $total
        );
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Get the product card HTML.
     *
     * @param array $product The product data.
     *
     * @return string
     */
    protected function get_product_card($product) {
        // Product wrapper classes
        $classes = [
            'motionflow-product',
            'motionflow-product-' . $product['id'],
        ];
        
        if (isset($product['type'])) {
            $classes[] = 'motionflow-product-type-' . sanitize_html_class($product['type']);
        }
        
        if (isset($product['stock_status'])) {
            $classes[] = 'motionflow-product-' . sanitize_html_class($product['stock_status']);
        }
        
        if (isset($product['is_on_sale']) && $product['is_on_sale']) {
            $classes[] = 'motionflow-product-on-sale';
        }
        
        // Start product HTML
        $html = '<div class="' . implode(' ', array_map('sanitize_html_class', $classes)) . '" data-product-id="' . esc_attr($product['id']) . '">';
        
        // Product inner wrapper
        $html .= '<div class="motionflow-product-inner">';
        
        // Product thumbnail
        if (!empty($product['image']) && is_array($product['image']) && !empty($product['image'][0])) {
            $html .= '<div class="motionflow-product-thumbnail">';
            
            // Add "on sale" badge if product is on sale
            if (isset($product['is_on_sale']) && $product['is_on_sale']) {
                $sale_text = $this->get_setting('sale_badge_text', __('Sale!', 'motionflow'));
                $html .= '<span class="motionflow-onsale">' . esc_html($sale_text) . '</span>';
            }
            
            // Thumbnail link
            $html .= '<a href="' . esc_url($product['permalink']) . '" class="motionflow-product-link">';
            
            // Lazy load image if enabled
            if ($this->get_setting('lazy_load_images', true)) {
                $html .= '<img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" data-src="' . esc_url($product['image'][0]) . '" alt="' . esc_attr($product['name']) . '" class="motionflow-lazy" width="' . esc_attr($product['image'][1]) . '" height="' . esc_attr($product['image'][2]) . '">';
                $html .= '<noscript><img src="' . esc_url($product['image'][0]) . '" alt="' . esc_attr($product['name']) . '" width="' . esc_attr($product['image'][1]) . '" height="' . esc_attr($product['image'][2]) . '"></noscript>';
            } else {
                $html .= '<img src="' . esc_url($product['image'][0]) . '" alt="' . esc_attr($product['name']) . '" width="' . esc_attr($product['image'][1]) . '" height="' . esc_attr($product['image'][2]) . '">';
            }
            
            $html .= '</a>';
            $html .= '</div>';
        }
        
        // Product content
        $html .= '<div class="motionflow-product-content">';
        
        // Product title
        $html .= '<h2 class="motionflow-product-title">';
        $html .= '<a href="' . esc_url($product['permalink']) . '">' . esc_html($product['name']) . '</a>';
        $html .= '</h2>';
        
        // Product rating
        if ($this->get_setting('show_rating', true) && isset($product['average_rating'])) {
            $html .= '<div class="motionflow-product-rating">';
            
            // Generate star rating
            $rating = floatval($product['average_rating']);
            $full_stars = floor($rating);
            $half_star = ($rating - $full_stars) >= 0.5;
            $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
            
            // Add full stars
            for ($i = 0; $i < $full_stars; $i++) {
                $html .= '<span class="motionflow-star motionflow-star-full"></span>';
            }
            
            // Add half star if needed
            if ($half_star) {
                $html .= '<span class="motionflow-star motionflow-star-half"></span>';
            }
            
            // Add empty stars
            for ($i = 0; $i < $empty_stars; $i++) {
                $html .= '<span class="motionflow-star motionflow-star-empty"></span>';
            }
            
            // Add review count if available
            if (isset($product['review_count']) && $this->get_setting('show_review_count', true)) {
                $html .= '<span class="motionflow-review-count">(' . $product['review_count'] . ')</span>';
            }
            
            $html .= '</div>';
        }
        
        // Product price
        if ($this->get_setting('show_price', true) && isset($product['price_html'])) {
            $html .= '<div class="motionflow-product-price">' . $product['price_html'] . '</div>';
        }
        
        // Product attributes
        if ($this->get_setting('show_attributes', false) && !empty($product['attributes'])) {
            $html .= '<div class="motionflow-product-attributes">';
            
            // Get attributes to display
            $display_attributes = $this->get_setting('display_attributes', []);
            
            foreach ($product['attributes'] as $attribute_name => $attribute) {
                // Skip if not in display attributes (if specified)
                if (!empty($display_attributes) && !in_array($attribute_name, $display_attributes)) {
                    continue;
                }
                
                // Display attribute
                $html .= '<div class="motionflow-product-attribute motionflow-product-attribute-' . sanitize_html_class($attribute_name) . '">';
                $html .= '<span class="motionflow-attribute-name">' . esc_html($attribute['name']) . ': </span>';
                $html .= '<span class="motionflow-attribute-value">' . esc_html(implode(', ', $attribute['values'])) . '</span>';
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }
        
        // Add to cart button
        if ($this->get_setting('show_add_to_cart', true)) {
            $html .= '<div class="motionflow-product-actions">';
            
            // Add to cart button
            $html .= '<a href="' . esc_url($product['add_to_cart_url']) . '" data-product-id="' . esc_attr($product['id']) . '" class="motionflow-add-to-cart" data-quantity="1">';
            $html .= esc_html__('Add to cart', 'motionflow');
            $html .= '</a>';
            
            // View details button
            if ($this->get_setting('show_view_details', true)) {
                $html .= '<a href="' . esc_url($product['permalink']) . '" class="motionflow-view-details">';
                $html .= esc_html__('View details', 'motionflow');
                $html .= '</a>';
            }
            
            $html .= '</div>';
        }
        
        $html .= '</div>'; // End .motionflow-product-content
        $html .= '</div>'; // End .motionflow-product-inner
        $html .= '</div>'; // End .motionflow-product
        
        return $html;
    }
}