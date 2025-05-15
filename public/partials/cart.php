<?php
/**
 * Cart template
 *
 * This template is used to display the cart sidebar.
 *
 * @package MotionFlow
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get config
$config = MotionFlow\Config::instance();

// Get layout
$layout = isset($atts['layout']) ? $atts['layout'] : 'default';

// Get cart position
$cart_position = $config->get_cart_position('desktop', 'right');

// Get cart items
$cart = WC()->cart;
$cart_items = $cart->get_cart();
$cart_total = $cart->get_total();
$cart_count = $cart->get_cart_contents_count();

// Classes
$classes = [
    'motionflow-cart-sidebar',
    'motionflow-cart-layout-' . $layout,
    'motionflow-cart-position-' . $cart_position,
];

// Show quantity controls
$show_quantity_controls = isset($atts['show_quantity_controls']) 
    ? ($atts['show_quantity_controls'] === 'yes')
    : $config->get('cart', 'show_quantity_controls', true);
?>

<div class="<?php echo esc_attr(implode(' ', $classes)); ?>" data-motionflow-cart data-drop-zone="cart">
    <div class="motionflow-cart-header">
        <h3><?php esc_html_e('Your Cart', 'motionflow'); ?></h3>
        <a href="#" class="motionflow-cart-close">&times;</a>
    </div>
    
    <div class="motionflow-cart-content">
        <?php if (empty($cart_items)) : ?>
            <div class="motionflow-cart-empty-message">
                <?php esc_html_e('Your cart is empty.', 'motionflow'); ?>
            </div>
        <?php else : ?>
            <div class="motionflow-cart-items">
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
                            
                            <?php if ($show_quantity_controls) : ?>
                                <div class="motionflow-cart-item-quantity">
                                    <div class="motionflow-cart-item-quantity-button motionflow-cart-item-quantity-minus">-</div>
                                    <div class="motionflow-cart-item-quantity-value"><?php echo esc_html($quantity); ?></div>
                                    <div class="motionflow-cart-item-quantity-button motionflow-cart-item-quantity-plus">+</div>
                                </div>
                            <?php else : ?>
                                <div class="motionflow-cart-item-quantity">
                                    <?php 
                                    /* translators: %s: product quantity */
                                    printf(esc_html__('Qty: %s', 'motionflow'), $quantity); 
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="motionflow-cart-item-remove">&times;</div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="motionflow-cart-footer">
        <div class="motionflow-cart-total">
            <span class="motionflow-cart-total-label"><?php esc_html_e('Total:', 'motionflow'); ?></span>
            <span class="motionflow-cart-total-value"><?php echo $cart_total; ?></span>
        </div>
        
        <div class="motionflow-cart-actions">
            <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="motionflow-cart-view-cart">
                <?php esc_html_e('View Cart', 'motionflow'); ?>
            </a>
            
            <a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="motionflow-cart-checkout">
                <?php esc_html_e('Checkout', 'motionflow'); ?>
            </a>
        </div>
    </div>
</div>

<?php if ($config->get('cart', 'enable_drag_to_cart', true) && $config->get('cart', 'show_cart_button', true)) : ?>
    <div class="motionflow-cart-button" data-drop-zone="cart-button">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="9" cy="21" r="1"></circle>
            <circle cx="20" cy="21" r="1"></circle>
            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
        </svg>
        
        <span class="motionflow-cart-button-count"><?php echo esc_html($cart_count); ?></span>
    </div>
<?php endif; ?>