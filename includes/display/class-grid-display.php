<?php
/**
 * Grid Display Class
 *
 * @package    MotionFlow
 * @subpackage MotionFlow/includes/display
 */

namespace MotionFlow\Display;

/**
 * Grid display type.
 */
class Grid_Display extends Display_Base {

    /**
     * Initialize the display.
     *
     * @param string $id       The display ID.
     * @param array  $settings The display settings.
     */
    public function __construct($id, $settings = []) {
        parent::__construct($id, $settings);
    }

    /**
     * Render the display.
     *
     * @return string
     */
    public function render() {
        // Start with the wrapper
        $html = $this->get_wrapper_start();
        
        // Add loader
        $html .= $this->render_loader();
        
        // Add product count if enabled
        if ($this->get_setting('show_product_count', true)) {
            $html .= $this->render_product_count();
        }
        
        // Check if we have products
        if (empty($this->products)) {
            $html .= $this->render_no_products();
            $html .= $this->get_wrapper_end();
            return $html;
        }
        
        // Get grid settings
        $columns_desktop = $this->get_setting('columns_desktop', 4);
        $columns_tablet = $this->get_setting('columns_tablet', 3);
        $columns_mobile = $this->get_setting('columns_mobile', 2);
        
        // Build grid container with appropriate classes
        $grid_classes = [
            'motionflow-grid',
            'motionflow-columns-desktop-' . $columns_desktop,
            'motionflow-columns-tablet-' . $columns_tablet,
            'motionflow-columns-mobile-' . $columns_mobile,
        ];
        
        // Add virtualization class if enabled
        if ($this->get_setting('enable_virtualization', false)) {
            $grid_classes[] = 'motionflow-grid-virtualized';
        }
        
        $html .= '<div class="' . implode(' ', array_map('sanitize_html_class', $grid_classes)) . '">';
        
        // Add products
        foreach ($this->products as $product) {
            $html .= $this->get_product_card($product);
        }
        
        $html .= '</div>'; // End .motionflow-grid
        
        // Add pagination if enabled
        if ($this->get_setting('show_pagination', true)) {
            $html .= $this->render_pagination();
        }
        
        // End wrapper
        $html .= $this->get_wrapper_end();
        
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
        // Call parent method to get base card HTML
        $html = parent::get_product_card($product);
        
        // Add modal HTML if enabled
        if ($this->get_setting('enable_modal', true)) {
            $html .= $this->get_product_modal($product);
        }
        
        return $html;
    }

    /**
     * Get the product modal HTML.
     *
     * @param array $product The product data.
     *
     * @return string
     */
    protected function get_product_modal($product) {
        $html = '<div class="motionflow-product-modal" id="motionflow-modal-' . esc_attr($product['id']) . '" style="display: none;" data-product-id="' . esc_attr($product['id']) . '">';
        
        // Modal close button
        $html .= '<div class="motionflow-modal-close">&times;</div>';
        
        // Modal container
        $html .= '<div class="motionflow-modal-container">';
        
        // Modal left column (image gallery)
        $html .= '<div class="motionflow-modal-left">';
        
        // Product image gallery (main image + gallery if available)
        $html .= '<div class="motionflow-modal-images">';
        
        // Main image
        if (!empty($product['image']) && is_array($product['image']) && !empty($product['image'][0])) {
            $html .= '<div class="motionflow-modal-main-image">';
            $html .= '<img src="' . esc_url($product['image'][0]) . '" alt="' . esc_attr($product['name']) . '" width="' . esc_attr($product['image'][1]) . '" height="' . esc_attr($product['image'][2]) . '">';
            $html .= '</div>';
        }
        
        // Gallery thumbnails (if we had the gallery data)
        // For now, we'll just use the main image
        $html .= '<div class="motionflow-modal-thumbnails">';
        $html .= '<div class="motionflow-modal-thumbnail motionflow-modal-thumbnail-active">';
        $html .= '<img src="' . esc_url($product['image'][0]) . '" alt="' . esc_attr($product['name']) . '">';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '</div>'; // End .motionflow-modal-images
        $html .= '</div>'; // End .motionflow-modal-left
        
        // Modal right column (product details)
        $html .= '<div class="motionflow-modal-right">';
        
        // Product title
        $html .= '<h2 class="motionflow-modal-title">' . esc_html($product['name']) . '</h2>';
        
        // Product rating
        if (isset($product['average_rating'])) {
            $html .= '<div class="motionflow-modal-rating">';
            
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
            if (isset($product['review_count'])) {
                $html .= '<span class="motionflow-review-count">(' . $product['review_count'] . ')</span>';
            }
            
            $html .= '</div>';
        }
        
        // Product price
        if (isset($product['price_html'])) {
            $html .= '<div class="motionflow-modal-price">' . $product['price_html'] . '</div>';
        }
        
        // Product attributes
        if (!empty($product['attributes'])) {
            $html .= '<div class="motionflow-modal-attributes">';
            
            foreach ($product['attributes'] as $attribute_name => $attribute) {
                $html .= '<div class="motionflow-modal-attribute motionflow-modal-attribute-' . sanitize_html_class($attribute_name) . '">';
                $html .= '<span class="motionflow-attribute-name">' . esc_html($attribute['name']) . ': </span>';
                $html .= '<span class="motionflow-attribute-value">' . esc_html(implode(', ', $attribute['values'])) . '</span>';
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }
        
        // Product actions
        $html .= '<div class="motionflow-modal-actions">';
        
        // Add to cart button
        $html .= '<a href="' . esc_url($product['add_to_cart_url']) . '" data-product-id="' . esc_attr($product['id']) . '" class="motionflow-modal-add-to-cart">';
        $html .= esc_html__('Add to cart', 'motionflow');
        $html .= '</a>';
        
        // View more button
        $html .= '<a href="' . esc_url($product['permalink']) . '" class="motionflow-modal-view-more">';
        $html .= esc_html__('View more', 'motionflow');
        $html .= '</a>';
        
        $html .= '</div>'; // End .motionflow-modal-actions
        
        $html .= '</div>'; // End .motionflow-modal-right
        $html .= '</div>'; // End .motionflow-modal-container
        $html .= '</div>'; // End .motionflow-product-modal
        
        return $html;
    }
}