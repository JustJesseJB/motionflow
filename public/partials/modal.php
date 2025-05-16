
<?php
/**
 * Modal template
 *
 * This template is used to display the product modal.
 *
 * @package MotionFlow
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get product
if (!isset($product) || !$product instanceof WC_Product) {
    global $product;
    
    if (!$product) {
        return;
    }
}

// Get config
$config = MotionFlow\Config::instance();

// Get product data
$product_id = $product->get_id();
$title = $product->get_name();
$price_html = $product->get_price_html();
$image_id = $product->get_image_id();
$rating = $product->get_average_rating();
$review_count = $product->get_review_count();
$permalink = get_permalink($product_id);
$add_to_cart_url = $product->add_to_cart_url();
$thumbnail = $product->get_image('woocommerce_single');
$gallery_image_ids = $product->get_gallery_image_ids();
?>

<div class="motionflow-modal-container">
    <div class="motionflow-modal-close">&times;</div>
    
    <div class="motionflow-modal-left">
        <div class="motionflow-modal-images">
            <div class="motionflow-modal-main-image" data-main-image>
                <?php if ($image_id) : ?>
                    <?php echo wp_get_attachment_image($image_id, 'woocommerce_single'); ?>
                <?php else : ?>
                    <img src="<?php echo esc_url(wc_placeholder_img_src('woocommerce_single')); ?>" alt="<?php esc_attr_e('Placeholder', 'motionflow'); ?>">
                <?php endif; ?>
            </div>
            
            <?php if (!empty($gallery_image_ids)) : ?>
                <div class="motionflow-modal-thumbnails">
                    <?php if ($image_id) : ?>
                        <div class="motionflow-modal-thumbnail motionflow-modal-thumbnail-active" data-thumbnail-id="<?php echo esc_attr($image_id); ?>">
                            <?php echo wp_get_attachment_image($image_id, 'thumbnail'); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php foreach ($gallery_image_ids as $gallery_image_id) : ?>
                        <div class="motionflow-modal-thumbnail" data-thumbnail-id="<?php echo esc_attr($gallery_image_id); ?>">
                            <?php echo wp_get_attachment_image($gallery_image_id, 'thumbnail'); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="motionflow-modal-right">
        <h2 class="motionflow-modal-title"><?php echo esc_html($title); ?></h2>
        
        <?php if ($rating > 0) : ?>
            <div class="motionflow-modal-rating">
                <?php
                $full_stars = floor($rating);
                $half_star = ($rating - $full_stars) >= 0.5;
                $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
                
                // Full stars
                for ($i = 0; $i < $full_stars; $i++) {
                    echo '<span class="motionflow-star motionflow-star-full"></span>';
                }
                
                // Half star
                if ($half_star) {
                    echo '<span class="motionflow-star motionflow-star-half"></span>';
                }
                
                // Empty stars
                for ($i = 0; $i < $empty_stars; $i++) {
                    echo '<span class="motionflow-star motionflow-star-empty"></span>';
                }
                
                if ($review_count > 0) {
                    echo '<span class="motionflow-review-count">(' . esc_html($review_count) . ')</span>';
                }
                ?>
            </div>
        <?php endif; ?>
        
        <div class="motionflow-modal-price"><?php echo $price_html; ?></div>
        
        <?php
        // Product attributes
        $attributes = $product->get_attributes();
        
        if (!empty($attributes)) :
        ?>
            <div class="motionflow-modal-attributes">
                <?php foreach ($attributes as $attribute_name => $attribute) :
                    // Skip hidden attributes
                    if (!$attribute['is_visible']) {
                        continue;
                    }
                    
                    // Get attribute label
                    $attribute_label = wc_attribute_label($attribute['name']);
                    
                    // Get attribute values
                    if ($attribute['is_taxonomy']) {
                        $attribute_taxonomy = $attribute['name'];
                        $attribute_values = wc_get_product_terms($product_id, $attribute_taxonomy, ['fields' => 'names']);
                    } else {
                        $attribute_values = $attribute['value'] ? array_map('trim', explode('|', $attribute['value'])) : [];
                    }
                    
                    // Skip if no values
                    if (empty($attribute_values)) {
                        continue;
                    }
                ?>
                    <div class="motionflow-modal-attribute">
                        <span class="motionflow-attribute-name"><?php echo esc_html($attribute_label); ?>: </span>
                        <span class="motionflow-attribute-value"><?php echo esc_html(implode(', ', $attribute_values)); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php
        // Short description
        if ($product->get_short_description()) :
        ?>
            <div class="motionflow-modal-description">
                <?php echo wp_kses_post($product->get_short_description()); ?>
            </div>
        <?php endif; ?>
        
        <div class="motionflow-modal-actions">
            <a href="<?php echo esc_url($add_to_cart_url); ?>" class="motionflow-modal-add-to-cart" data-product-id="<?php echo esc_attr($product_id); ?>">
                <?php esc_html_e('Add to cart', 'motionflow'); ?>
            </a>
            
            <a href="<?php echo esc_url($permalink); ?>" class="motionflow-modal-view-more">
                <?php esc_html_e('View more', 'motionflow'); ?>
            </a>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle thumbnail clicks
        var thumbnails = document.querySelectorAll('.motionflow-modal-thumbnail');
        var mainImage = document.querySelector('[data-main-image]');
        
        if (thumbnails.length && mainImage) {
            thumbnails.forEach(function(thumbnail) {
                thumbnail.addEventListener('click', function() {
                    // Remove active class from all thumbnails
                    thumbnails.forEach(function(thumb) {
                        thumb.classList.remove('motionflow-modal-thumbnail-active');
                    });
                    
                    // Add active class to clicked thumbnail
                    this.classList.add('motionflow-modal-thumbnail-active');
                    
                    // Get thumbnail image
                    var thumbnailId = this.getAttribute('data-thumbnail-id');
                    var thumbnailImage = this.querySelector('img');
                    
                    if (thumbnailImage) {
                        // Update main image src
                        var mainImageSrc = thumbnailImage.src.replace('-150x150', '');
                        var mainImageImg = mainImage.querySelector('img');
                        
                        if (mainImageImg) {
                            mainImageImg.src = mainImageSrc;
                        }
                    }
                });
            });
        }
    });
</script>