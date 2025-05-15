<?php
/**
 * Grid template
 *
 * This template is used to display the product grid.
 *
 * @package MotionFlow
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get WooCommerce integration
$woocommerce = new MotionFlow\Integrations\WooCommerce();

// Get config
$config = MotionFlow\Config::instance();

// Get layout
$layout = isset($atts['layout']) ? $atts['layout'] : 'default';

// Get columns
$columns_desktop = isset($atts['columns_desktop']) && $atts['columns_desktop'] > 0 
    ? intval($atts['columns_desktop']) 
    : $config->get('grid', 'columns_desktop', 4);

$columns_tablet = isset($atts['columns_tablet']) && $atts['columns_tablet'] > 0 
    ? intval($atts['columns_tablet']) 
    : $config->get('grid', 'columns_tablet', 3);

$columns_mobile = isset($atts['columns_mobile']) && $atts['columns_mobile'] > 0 
    ? intval($atts['columns_mobile']) 
    : $config->get('grid', 'columns_mobile', 2);

// Get product limit
$limit = isset($atts['limit']) && $atts['limit'] > 0 
    ? intval($atts['limit']) 
    : $config->get('grid', 'items_per_page', 24);

// Get categories and tags from attributes
$categories = isset($atts['categories']) ? array_map('trim', explode(',', $atts['categories'])) : [];
$tags = isset($atts['tags']) ? array_map('trim', explode(',', $atts['tags'])) : [];

// Build query args
$args = [
    'post_type' => 'product',
    'post_status' => 'publish',
    'posts_per_page' => $limit,
    'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
    'tax_query' => [
        [
            'taxonomy' => 'product_visibility',
            'field' => 'name',
            'terms' => 'exclude-from-catalog',
            'operator' => 'NOT IN',
        ],
    ],
];

// Add categories to query
if (!empty($categories)) {
    $args['tax_query'][] = [
        'taxonomy' => 'product_cat',
        'field' => 'slug',
        'terms' => $categories,
        'operator' => 'IN',
    ];
}

// Add tags to query
if (!empty($tags)) {
    $args['tax_query'][] = [
        'taxonomy' => 'product_tag',
        'field' => 'slug',
        'terms' => $tags,
        'operator' => 'IN',
    ];
}

// Get products data
$products_data = $woocommerce->get_products_grid_data($args);
$products = $products_data['products'];
$total_products = $products_data['total'];
$total_pages = $products_data['pages'];

// Classes
$grid_classes = [
    'motionflow-grid',
    'motionflow-grid-layout-' . $layout,
    'motionflow-columns-desktop-' . $columns_desktop,
    'motionflow-columns-tablet-' . $columns_tablet,
    'motionflow-columns-mobile-' . $columns_mobile,
];

// Show loader
$show_loader = $config->get('grid', 'show_loader', true);

// Enable lazy loading
$lazy_load = $config->get('grid', 'lazy_load_images', true);

// Show drag handles
$show_drag_handles = $config->get('cart', 'enable_drag_to_cart', true);

// Show modal
$enable_modal = $config->get('grid', 'enable_modal', true);
?>

<?php if ($show_loader) : ?>
    <div class="motionflow-loader" style="display: none;">
        <div class="motionflow-loader-spinner"></div>
    </div>
<?php endif; ?>

<?php if ($config->get('grid', 'show_product_count', true)) : ?>
    <div class="motionflow-product-count">
        <?php
        // If no products found
        if ($total_products === 0) {
            esc_html_e('No products found', 'motionflow');
        } else {
            $current_page = max(1, get_query_var('paged'));
            $per_page = $limit;
            $from = (($current_page - 1) * $per_page) + 1;
            $to = min($from + $per_page - 1, $total_products);
            
            /* translators: %1$d: start number, %2$d: end number, %3$d: total number */
            printf(
                esc_html__('Showing %1$d&ndash;%2$d of %3$d results', 'motionflow'),
                $from,
                $to,
                $total_products
            );
        }
        ?>
    </div>
<?php endif; ?>

<?php if (empty($products)) : ?>
    <div class="motionflow-no-products">
        <?php esc_html_e('No products found. Try different filters or check back later.', 'motionflow'); ?>
    </div>
<?php else : ?>
    <div class="<?php echo esc_attr(implode(' ', $grid_classes)); ?>">
        <?php foreach ($products as $product) : 
            // Classes
            $product_classes = [
                'motionflow-product',
                'motionflow-product-' . $product['id'],
            ];
            
            if (isset($product['stock_status'])) {
                $product_classes[] = 'motionflow-product-' . $product['stock_status'];
            }
            
            if (isset($product['is_on_sale']) && $product['is_on_sale']) {
                $product_classes[] = 'motionflow-product-on-sale';
            }
        ?>
            <div class="<?php echo esc_attr(implode(' ', $product_classes)); ?>" data-product-id="<?php echo esc_attr($product['id']); ?>">
                <div class="motionflow-product-inner">
                    <div class="motionflow-product-thumbnail">
                        <?php if (isset($product['is_on_sale']) && $product['is_on_sale']) : ?>
                            <span class="motionflow-onsale"><?php esc_html_e('Sale!', 'motionflow'); ?></span>
                        <?php endif; ?>
                        
                        <a href="<?php echo esc_url($product['permalink']); ?>" class="motionflow-product-link">
                            <?php if (!empty($product['image']) && is_array($product['image'])) : ?>
                                <?php if ($lazy_load) : ?>
                                    <img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" 
                                         data-src="<?php echo esc_url($product['image'][0]); ?>" 
                                         alt="<?php echo esc_attr($product['name']); ?>" 
                                         class="motionflow-lazy" 
                                         width="<?php echo esc_attr($product['image'][1]); ?>" 
                                         height="<?php echo esc_attr($product['image'][2]); ?>">
                                    <noscript>
                                        <img src="<?php echo esc_url($product['image'][0]); ?>" 
                                             alt="<?php echo esc_attr($product['name']); ?>" 
                                             width="<?php echo esc_attr($product['image'][1]); ?>" 
                                             height="<?php echo esc_attr($product['image'][2]); ?>">
                                    </noscript>
                                <?php else : ?>
                                    <img src="<?php echo esc_url($product['image'][0]); ?>" 
                                         alt="<?php echo esc_attr($product['name']); ?>" 
                                         width="<?php echo esc_attr($product['image'][1]); ?>" 
                                         height="<?php echo esc_attr($product['image'][2]); ?>">
                                <?php endif; ?>
                            <?php else : ?>
                                <img src="<?php echo esc_url(wc_placeholder_img_src('woocommerce_thumbnail')); ?>" 
                                     alt="<?php esc_attr_e('Placeholder', 'motionflow'); ?>" 
                                     class="motionflow-product-placeholder">
                            <?php endif; ?>
                        </a>
                        
                        <?php if ($show_drag_handles) : ?>
                            <div class="motionflow-drag-handle" 
                                 data-product-id="<?php echo esc_attr($product['id']); ?>" 
                                 title="<?php esc_attr_e('Drag to add to cart', 'motionflow'); ?>"></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="motionflow-product-content">
                        <h2 class="motionflow-product-title">
                            <a href="<?php echo esc_url($product['permalink']); ?>">
                                <?php echo esc_html($product['name']); ?>
                            </a>
                        </h2>
                        
                        <?php if ($config->get('grid', 'show_rating', true) && isset($product['average_rating'])) : ?>
                            <div class="motionflow-product-rating">
                                <?php
                                $rating = floatval($product['average_rating']);
                                $full_stars = floor($rating);
                                $half_star = ($rating - $full_stars) >= 0.5;
                                $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
                                
                                // Add full stars
                                for ($i = 0; $i < $full_stars; $i++) {
                                    echo '<span class="motionflow-star motionflow-star-full"></span>';
                                }
                                
                                // Add half star if needed
                                if ($half_star) {
                                    echo '<span class="motionflow-star motionflow-star-half"></span>';
                                }
                                
                                // Add empty stars
                                for ($i = 0; $i < $empty_stars; $i++) {
                                    echo '<span class="motionflow-star motionflow-star-empty"></span>';
                                }
                                ?>
                                
                                <?php if (isset($product['review_count']) && $config->get('grid', 'show_review_count', true)) : ?>
                                    <span class="motionflow-review-count">(<?php echo esc_html($product['review_count']); ?>)</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($config->get('grid', 'show_price', true) && isset($product['price_html'])) : ?>
                            <div class="motionflow-product-price">
                                <?php echo $product['price_html']; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($config->get('grid', 'show_attributes', false) && !empty($product['attributes'])) : 
                            // Get attributes to display
                            $display_attributes = $config->get('grid', 'display_attributes', []);
                        ?>
                            <div class="motionflow-product-attributes">
                                <?php foreach ($product['attributes'] as $attribute_name => $attribute) : 
                                    // Skip if not in display attributes (if specified)
                                    if (!empty($display_attributes) && !in_array($attribute_name, $display_attributes)) {
                                        continue;
                                    }
                                ?>
                                    <div class="motionflow-product-attribute motionflow-product-attribute-<?php echo sanitize_html_class($attribute_name); ?>">
                                        <span class="motionflow-attribute-name"><?php echo esc_html($attribute['name']); ?>: </span>
                                        <span class="motionflow-attribute-value"><?php echo esc_html(implode(', ', $attribute['values'])); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="motionflow-product-actions">
                            <a href="<?php echo esc_url($product['add_to_cart_url']); ?>" 
                               data-product-id="<?php echo esc_attr($product['id']); ?>" 
                               class="motionflow-add-to-cart">
                                <?php esc_html_e('Add to cart', 'motionflow'); ?>
                            </a>
                            
                            <?php if ($config->get('grid', 'show_view_details', true)) : ?>
                                <a href="<?php echo esc_url($product['permalink']); ?>" class="motionflow-view-details">
                                    <?php esc_html_e('View details', 'motionflow'); ?>
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($enable_modal) : ?>
                                <a href="#" 
                                   class="motionflow-modal-trigger" 
                                   data-product-id="<?php echo esc_attr($product['id']); ?>">
                                    <?php esc_html_e('Quick View', 'motionflow'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <?php if ($enable_modal) : ?>
                    <div class="motionflow-product-modal" id="motionflow-modal-<?php echo esc_attr($product['id']); ?>" style="display: none;" data-product-id="<?php echo esc_attr($product['id']); ?>">
                        <div class="motionflow-modal-close">&times;</div>
                        
                        <div class="motionflow-modal-container">
                            <div class="motionflow-modal-left">
                                <div class="motionflow-modal-images">
                                    <div class="motionflow-modal-main-image">
                                        <?php if (!empty($product['image']) && is_array($product['image'])) : ?>
                                            <img src="<?php echo esc_url($product['image'][0]); ?>" 
                                                 alt="<?php echo esc_attr($product['name']); ?>" 
                                                 width="<?php echo esc_attr($product['image'][1]); ?>" 
                                                 height="<?php echo esc_attr($product['image'][2]); ?>">
                                        <?php else : ?>
                                            <img src="<?php echo esc_url(wc_placeholder_img_src('woocommerce_single')); ?>" 
                                                 alt="<?php esc_attr_e('Placeholder', 'motionflow'); ?>">
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="motionflow-modal-thumbnails">
                                        <div class="motionflow-modal-thumbnail motionflow-modal-thumbnail-active">
                                            <?php if (!empty($product['image']) && is_array($product['image'])) : ?>
                                                <img src="<?php echo esc_url($product['image'][0]); ?>" alt="<?php echo esc_attr($product['name']); ?>">
                                            <?php else : ?>
                                                <img src="<?php echo esc_url(wc_placeholder_img_src('thumbnail')); ?>" alt="<?php esc_attr_e('Placeholder', 'motionflow'); ?>">
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="motionflow-modal-right">
                                <h2 class="motionflow-modal-title"><?php echo esc_html($product['name']); ?></h2>
                                
                                <?php if (isset($product['average_rating'])) : ?>
                                    <div class="motionflow-modal-rating">
                                        <?php
                                        $rating = floatval($product['average_rating']);
                                        $full_stars = floor($rating);
                                        $half_star = ($rating - $full_stars) >= 0.5;
                                        $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
                                        
                                        // Add full stars
                                        for ($i = 0; $i < $full_stars; $i++) {
                                            echo '<span class="motionflow-star motionflow-star-full"></span>';
                                        }
                                        
                                        // Add half star if needed
                                        if ($half_star) {
                                            echo '<span class="motionflow-star motionflow-star-half"></span>';
                                        }
                                        
                                        // Add empty stars
                                        for ($i = 0; $i < $empty_stars; $i++) {
                                            echo '<span class="motionflow-star motionflow-star-empty"></span>';
                                        }
                                        ?>
                                        
                                        <?php if (isset($product['review_count'])) : ?>
                                            <span class="motionflow-review-count">(<?php echo esc_html($product['review_count']); ?>)</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (isset($product['price_html'])) : ?>
                                    <div class="motionflow-modal-price">
                                        <?php echo $product['price_html']; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($product['attributes'])) : ?>
                                    <div class="motionflow-modal-attributes">
                                        <?php foreach ($product['attributes'] as $attribute_name => $attribute) : ?>
                                            <div class="motionflow-modal-attribute motionflow-modal-attribute-<?php echo sanitize_html_class($attribute_name); ?>">
                                                <span class="motionflow-attribute-name"><?php echo esc_html($attribute['name']); ?>: </span>
                                                <span class="motionflow-attribute-value"><?php echo esc_html(implode(', ', $attribute['values'])); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="motionflow-modal-actions">
                                    <a href="<?php echo esc_url($product['add_to_cart_url']); ?>" 
                                       data-product-id="<?php echo esc_attr($product['id']); ?>" 
                                       class="motionflow-modal-add-to-cart">
                                        <?php esc_html_e('Add to cart', 'motionflow'); ?>
                                    </a>
                                    
                                    <a href="<?php echo esc_url($product['permalink']); ?>" class="motionflow-modal-view-more">
                                        <?php esc_html_e('View more', 'motionflow'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php if ($config->get('grid', 'show_pagination', true) && $total_pages > 1) : ?>
        <div class="motionflow-pagination">
            <?php
            $current_page = max(1, get_query_var('paged'));
            
            // Previous page link
            if ($current_page > 1) {
                echo '<a href="' . esc_url(add_query_arg('paged', $current_page - 1)) . '" class="motionflow-pagination-prev" data-page="' . esc_attr($current_page - 1) . '">' . esc_html__('Previous', 'motionflow') . '</a>';
            } else {
                echo '<span class="motionflow-pagination-prev motionflow-pagination-disabled">' . esc_html__('Previous', 'motionflow') . '</span>';
            }
            
            echo '<div class="motionflow-pagination-numbers">';
            
            // Determine which page numbers to show
            $range = 2; // Show 2 page numbers on each side of current page
            $start_page = max(1, $current_page - $range);
            $end_page = min($total_pages, $current_page + $range);
            
            // Always show first page
            if ($start_page > 1) {
                echo '<a href="' . esc_url(add_query_arg('paged', 1)) . '" class="motionflow-pagination-number" data-page="1">1</a>';
                
                // Add ellipsis if there's a gap
                if ($start_page > 2) {
                    echo '<span class="motionflow-pagination-ellipsis">&hellip;</span>';
                }
            }
            
            // Page numbers
            for ($i = $start_page; $i <= $end_page; $i++) {
                if ($i === $current_page) {
                    echo '<span class="motionflow-pagination-number motionflow-pagination-current">' . $i . '</span>';
                } else {
                    echo '<a href="' . esc_url(add_query_arg('paged', $i)) . '" class="motionflow-pagination-number" data-page="' . esc_attr($i) . '">' . $i . '</a>';
                }
            }
            
            // Always show last page
            if ($end_page < $total_pages) {
                // Add ellipsis if there's a gap
                if ($end_page < $total_pages - 1) {
                    echo '<span class="motionflow-pagination-ellipsis">&hellip;</span>';
                }
                
                echo '<a href="' . esc_url(add_query_arg('paged', $total_pages)) . '" class="motionflow-pagination-number" data-page="' . esc_attr($total_pages) . '">' . $total_pages . '</a>';
            }
            
            echo '</div>';
            
            // Next page link
            if ($current_page < $total_pages) {
                echo '<a href="' . esc_url(add_query_arg('paged', $current_page + 1)) . '" class="motionflow-pagination-next" data-page="' . esc_attr($current_page + 1) . '">' . esc_html__('Next', 'motionflow') . '</a>';
            } else {
                echo '<span class="motionflow-pagination-next motionflow-pagination-disabled">' . esc_html__('Next', 'motionflow') . '</span>';
            }
            ?>
        </div>
    <?php endif; ?>
<?php endif; ?>