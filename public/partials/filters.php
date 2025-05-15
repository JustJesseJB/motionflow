<?php
/**
 * Filters template
 *
 * This template is used to display the filter options.
 *
 * @package MotionFlow
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get available filters
$woocommerce = new MotionFlow\Integrations\WooCommerce();
$available_filters = $woocommerce->get_available_filters();

// Get active filters
$active_filters = $woocommerce->get_active_filters();

// Get layout
$layout = isset($atts['layout']) ? $atts['layout'] : 'default';

// Classes
$classes = [
    'motionflow-filters',
    'motionflow-filters-layout-' . $layout,
];

if (!empty($active_filters)) {
    $classes[] = 'motionflow-filters-has-active';
}
?>

<div class="<?php echo esc_attr(implode(' ', $classes)); ?>">
    <div class="motionflow-filters-heading">
        <div class="motionflow-filters-heading-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
            <?php esc_html_e('Filter Products', 'motionflow'); ?>
        </div>
        
        <?php if (!empty($active_filters)) : ?>
            <a href="<?php echo esc_url(remove_query_arg(array_keys($active_filters))); ?>" class="motionflow-filters-clear-all">
                <?php esc_html_e('Clear All', 'motionflow'); ?>
            </a>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($active_filters)) : ?>
        <div class="motionflow-active-filters">
            <?php foreach ($active_filters as $key => $value) : 
                $filter_label = '';
                $value_label = $value;
                
                // Get filter label
                if (strpos($key, 'product_cat') !== false) {
                    $filter_label = __('Category', 'motionflow');
                    
                    $term = get_term_by('slug', $value, 'product_cat');
                    if ($term) {
                        $value_label = $term->name;
                    }
                } elseif (strpos($key, 'product_tag') !== false) {
                    $filter_label = __('Tag', 'motionflow');
                    
                    $term = get_term_by('slug', $value, 'product_tag');
                    if ($term) {
                        $value_label = $term->name;
                    }
                } elseif ($key === 'min_price' || $key === 'max_price') {
                    $filter_label = __('Price', 'motionflow');
                    $value_label = wc_price($value);
                } elseif (strpos($key, 'filter_') === 0) {
                    $attribute = str_replace('filter_', '', $key);
                    $taxonomy = wc_attribute_taxonomy_name($attribute);
                    
                    $attr = wc_get_attribute(wc_attribute_taxonomy_id_by_name($attribute));
                    if ($attr) {
                        $filter_label = $attr->name;
                    } else {
                        $filter_label = ucfirst($attribute);
                    }
                    
                    $term = get_term_by('slug', $value, $taxonomy);
                    if ($term) {
                        $value_label = $term->name;
                    }
                }
            ?>
                <div class="motionflow-active-filter">
                    <?php echo esc_html($filter_label); ?>: <?php echo esc_html($value_label); ?>
                    <a href="<?php echo esc_url(remove_query_arg($key)); ?>" class="motionflow-active-filter-remove">&times;</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <form class="motionflow-filters-form" method="get" action="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">
        <div class="motionflow-filters-list">
            <?php
            // Categories filter
            if (!empty($available_filters['categories'])) :
                $categories = $available_filters['categories'];
                $current_category = isset($_GET['product_cat']) ? sanitize_text_field($_GET['product_cat']) : '';
            ?>
                <div class="motionflow-filter motionflow-filter-categories">
                    <div class="motionflow-filter-title">
                        <span><?php esc_html_e('Categories', 'motionflow'); ?></span>
                        <?php if (!empty($current_category)) : ?>
                            <a href="<?php echo esc_url(remove_query_arg('product_cat')); ?>" class="motionflow-filter-clear" data-filter-clear="product_cat">
                                <?php esc_html_e('Clear', 'motionflow'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="motionflow-filter-content">
                        <select name="product_cat" class="motionflow-dropdown-filter motionflow-filter-control" data-filter-dropdown="product_cat">
                            <option value=""><?php esc_html_e('All Categories', 'motionflow'); ?></option>
                            <?php foreach ($categories as $category) : ?>
                                <option value="<?php echo esc_attr($category->slug); ?>" <?php selected($current_category, $category->slug); ?>>
                                    <?php echo esc_html($category->name); ?> (<?php echo esc_html($category->count); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php
            // Tags filter
            if (!empty($available_filters['tags'])) :
                $tags = $available_filters['tags'];
                $current_tag = isset($_GET['product_tag']) ? sanitize_text_field($_GET['product_tag']) : '';
            ?>
                <div class="motionflow-filter motionflow-filter-tags">
                    <div class="motionflow-filter-title">
                        <span><?php esc_html_e('Tags', 'motionflow'); ?></span>
                        <?php if (!empty($current_tag)) : ?>
                            <a href="<?php echo esc_url(remove_query_arg('product_tag')); ?>" class="motionflow-filter-clear" data-filter-clear="product_tag">
                                <?php esc_html_e('Clear', 'motionflow'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="motionflow-filter-content">
                        <select name="product_tag" class="motionflow-dropdown-filter motionflow-filter-control" data-filter-dropdown="product_tag">
                            <option value=""><?php esc_html_e('All Tags', 'motionflow'); ?></option>
                            <?php foreach ($tags as $tag) : ?>
                                <option value="<?php echo esc_attr($tag->slug); ?>" <?php selected($current_tag, $tag->slug); ?>>
                                    <?php echo esc_html($tag->name); ?> (<?php echo esc_html($tag->count); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php
            // Price range filter
            if (!empty($available_filters['price_range'])) :
                $price_range = $available_filters['price_range'];
                $current_min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : $price_range['min'];
                $current_max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : $price_range['max'];
            ?>
                <div class="motionflow-filter motionflow-filter-price">
                    <div class="motionflow-filter-title">
                        <span><?php esc_html_e('Price', 'motionflow'); ?></span>
                        <?php if (isset($_GET['min_price']) || isset($_GET['max_price'])) : ?>
                            <a href="<?php echo esc_url(remove_query_arg(['min_price', 'max_price'])); ?>" class="motionflow-filter-clear" data-filter-clear="price">
                                <?php esc_html_e('Clear', 'motionflow'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="motionflow-filter-content">
                        <div class="motionflow-range-slider" 
                             data-min="<?php echo esc_attr($price_range['min']); ?>" 
                             data-max="<?php echo esc_attr($price_range['max']); ?>" 
                             data-current-min="<?php echo esc_attr($current_min_price); ?>" 
                             data-current-max="<?php echo esc_attr($current_max_price); ?>">
                            
                            <div class="motionflow-range-slider-rail">
                                <div class="motionflow-range-slider-track"></div>
                                <div class="motionflow-range-slider-handle motionflow-range-slider-handle-min"></div>
                                <div class="motionflow-range-slider-handle motionflow-range-slider-handle-max"></div>
                            </div>
                            
                            <div class="motionflow-range-slider-values">
                                <span class="motionflow-range-slider-value-min"><?php echo wc_price($current_min_price); ?></span>
                                <span class="motionflow-range-slider-value-max"><?php echo wc_price($current_max_price); ?></span>
                            </div>
                            
                            <input type="hidden" name="min_price" value="<?php echo esc_attr($current_min_price); ?>" class="motionflow-filter-control">
                            <input type="hidden" name="max_price" value="<?php echo esc_attr($current_max_price); ?>" class="motionflow-filter-control">
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php
            // Attributes filters
            if (!empty($available_filters['attributes'])) :
                foreach ($available_filters['attributes'] as $attribute_name => $attribute) :
                    $filter_key = 'filter_' . $attribute_name;
                    $current_value = isset($_GET[$filter_key]) ? sanitize_text_field($_GET[$filter_key]) : '';
                    
                    // Get display type for this attribute
                    $config = \MotionFlow\Config::instance();
                    $attribute_display = $config->get('filter', 'attribute_display', []);
                    $display_type = isset($attribute_display['pa_' . $attribute_name]) ? $attribute_display['pa_' . $attribute_name] : 'dropdown';
            ?>
                <div class="motionflow-filter motionflow-filter-<?php echo esc_attr($attribute_name); ?>">
                    <div class="motionflow-filter-title">
                        <span><?php echo esc_html($attribute['name']); ?></span>
                        <?php if (!empty($current_value)) : ?>
                            <a href="<?php echo esc_url(remove_query_arg($filter_key)); ?>" class="motionflow-filter-clear" data-filter-clear="<?php echo esc_attr($filter_key); ?>">
                                <?php esc_html_e('Clear', 'motionflow'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="motionflow-filter-content">
                        <?php if ($display_type === 'dropdown') : ?>
                            <select name="<?php echo esc_attr($filter_key); ?>" class="motionflow-dropdown-filter motionflow-filter-control" data-filter-dropdown="<?php echo esc_attr($filter_key); ?>">
                                <option value=""><?php printf(esc_html__('All %s', 'motionflow'), $attribute['name']); ?></option>
                                <?php foreach ($attribute['terms'] as $term) : ?>
                                    <option value="<?php echo esc_attr($term->slug); ?>" <?php selected($current_value, $term->slug); ?>>
                                        <?php echo esc_html($term->name); ?> (<?php echo esc_html($term->count); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($display_type === 'swatch') : ?>
                            <div class="motionflow-swatch-filter-container">
                                <?php foreach ($attribute['terms'] as $term) : 
                                    $selected = ($current_value === $term->slug) ? 'motionflow-swatch-selected' : '';
                                    $term_id = $term->term_id;
                                    
                                    // Get swatch data - this would need a proper implementation
                                    $swatch_type = 'text';
                                    $swatch_value = $term->name;
                                    
                                    // Try to get color value if it's a color attribute
                                    if ($attribute_name === 'color' || $attribute_name === 'colour') {
                                        $swatch_type = 'color';
                                        $swatch_value = get_term_meta($term_id, 'product_attribute_color', true);
                                        
                                        if (empty($swatch_value)) {
                                            // Fallback to term name
                                            $swatch_value = strtolower($term->name);
                                            
                                            // Common color names
                                            $color_map = [
                                                'black' => '#000000',
                                                'white' => '#ffffff',
                                                'red' => '#ff0000',
                                                'green' => '#00ff00',
                                                'blue' => '#0000ff',
                                                'yellow' => '#ffff00',
                                                'purple' => '#800080',
                                                'pink' => '#ffc0cb',
                                                'orange' => '#ffa500',
                                                'brown' => '#a52a2a',
                                                'gray' => '#808080',
                                                'grey' => '#808080',
                                            ];
                                            
                                            if (isset($color_map[$swatch_value])) {
                                                $swatch_value = $color_map[$swatch_value];
                                            }
                                        }
                                    }
                                ?>
                                    <a href="<?php echo esc_url(add_query_arg($filter_key, $term->slug)); ?>" 
                                       class="motionflow-swatch-filter-option <?php echo esc_attr($selected); ?>"
                                       data-swatch-type="<?php echo esc_attr($swatch_type); ?>"
                                       data-value="<?php echo esc_attr($term->slug); ?>">
                                        
                                        <span class="motionflow-swatch-preview">
                                            <?php if ($swatch_type === 'color') : ?>
                                                <span class="motionflow-swatch-color" style="background-color: <?php echo esc_attr($swatch_value); ?>;"></span>
                                            <?php else : ?>
                                                <span class="motionflow-swatch-text"><?php echo esc_html($swatch_value); ?></span>
                                            <?php endif; ?>
                                        </span>
                                        
                                        <span class="motionflow-swatch-label">
                                            <?php echo esc_html($term->name); ?>
                                            <?php if ($term->count > 0) : ?>
                                                <span class="motionflow-filter-count">(<?php echo esc_html($term->count); ?>)</span>
                                            <?php endif; ?>
                                        </span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else : // Default to buttons ?>
                            <div class="motionflow-button-filter-container">
                                <?php foreach ($attribute['terms'] as $term) : 
                                    $selected = ($current_value === $term->slug) ? 'motionflow-button-selected' : '';
                                ?>
                                    <a href="<?php echo esc_url(add_query_arg($filter_key, $term->slug)); ?>" 
                                       class="motionflow-button-filter-option <?php echo esc_attr($selected); ?>"
                                       data-value="<?php echo esc_attr($term->slug); ?>">
                                        
                                        <?php echo esc_html($term->name); ?>
                                        <?php if ($term->count > 0) : ?>
                                            <span class="motionflow-filter-count">(<?php echo esc_html($term->count); ?>)</span>
                                        <?php endif; ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </form>
</div>