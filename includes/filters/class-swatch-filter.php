<?php
/**
 * Swatch Filter Class
 *
 * @package    MotionFlow
 * @subpackage MotionFlow/includes/filters
 */

namespace MotionFlow\Filters;

/**
 * Swatch filter type.
 */
class Swatch_Filter extends Filter_Base {

    /**
     * Initialize the filter.
     *
     * @param string $id       The filter ID.
     * @param string $label    The filter label.
     * @param array  $settings The filter settings.
     */
    public function __construct($id, $label, $settings = []) {
        parent::__construct($id, $label, $settings);
        
        // Set filter type
        $this->type = 'swatch';
    }

    /**
     * Load filter options.
     *
     * @return void
     */
    protected function load_options() {
        // Get options from settings
        $this->options = $this->get_setting('options', []);
    }

    /**
     * Render the filter.
     *
     * @return string
     */
    public function render() {
        // Start with the wrapper
        $html = $this->get_wrapper_start();
        
        // Start swatch container
        $html .= '<div class="motionflow-swatch-filter-container">';
        
        // Add options as swatches
        foreach ($this->options as $option) {
            $html .= $this->render_swatch($option);
        }
        
        // End swatch container
        $html .= '</div>';
        
        // End wrapper
        $html .= $this->get_wrapper_end();
        
        return $html;
    }

    /**
     * Render a swatch option.
     *
     * @param array $option The option to render.
     *
     * @return string
     */
    protected function render_swatch($option) {
        // Parse option
        $option = $this->parse_option($option);
        
        // Get swatch type and value
        $swatch_type = isset($option['swatch_type']) ? $option['swatch_type'] : 'color';
        $swatch_value = isset($option['swatch_value']) ? $option['swatch_value'] : '';
        
        // Option classes
        $classes = [
            'motionflow-swatch-filter-option',
            'motionflow-swatch-type-' . sanitize_html_class($swatch_type),
        ];
        
        if ($option['selected']) {
            $classes[] = 'motionflow-swatch-selected';
        }
        
        if ($option['disabled']) {
            $classes[] = 'motionflow-swatch-disabled';
        }
        
        // Option URL
        $url = $option['selected'] ? $this->get_reset_url() : $this->get_filter_url($option['value']);
        
        // Start swatch HTML
        $html = '<a href="' . esc_url($url) . '" class="' . implode(' ', array_map('sanitize_html_class', $classes)) . '" ';
        
        // Add data attributes
        $html .= 'data-value="' . esc_attr($option['value']) . '" ';
        $html .= 'data-swatch-type="' . esc_attr($swatch_type) . '" ';
        
        foreach ($option['data'] as $key => $value) {
            $html .= 'data-' . esc_attr($key) . '="' . esc_attr($value) . '" ';
        }
        
        // Add tooltip with the label as fallback
        $tooltip = !empty($option['tooltip']) ? $option['tooltip'] : $option['label'];
        $html .= 'title="' . esc_attr($tooltip) . '" ';
        
        // Close opening tag
        $html .= '>';
        
        // Render swatch preview based on type
        $html .= '<span class="motionflow-swatch-preview">';
        
        switch ($swatch_type) {
            case 'color':
                // Color swatch
                $html .= '<span class="motionflow-swatch-color" style="background-color: ' . esc_attr($swatch_value) . ';"></span>';
                break;
                
            case 'image':
                // Image swatch
                $image_id = intval($swatch_value);
                $image_url = wp_get_attachment_image_url($image_id, 'thumbnail');
                
                if ($image_url) {
                    $html .= '<span class="motionflow-swatch-image" style="background-image: url(' . esc_url($image_url) . ');"></span>';
                } else {
                    $html .= '<span class="motionflow-swatch-image motionflow-swatch-image-placeholder"></span>';
                }
                break;
                
            case 'text':
            default:
                // Text swatch
                $html .= '<span class="motionflow-swatch-text">' . esc_html($swatch_value) . '</span>';
                break;
        }
        
        $html .= '</span>';
        
        // Add label if enabled
        if ($this->get_setting('show_labels', true)) {
            $html .= '<span class="motionflow-swatch-label">' . esc_html($option['label']) . '</span>';
            
            // Add count if enabled
            $html .= $this->get_count_html($option['count']);
        }
        
        // Close swatch link
        $html .= '</a>';
        
        return $html;
    }
}