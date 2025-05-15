<?php
/**
 * Dropdown Filter Class
 *
 * @package    MotionFlow
 * @subpackage MotionFlow/includes/filters
 */

namespace MotionFlow\Filters;

/**
 * Dropdown filter type.
 */
class Dropdown_Filter extends Filter_Base {

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
        $this->type = 'dropdown';
    }

    /**
     * Load filter options.
     *
     * @return void
     */
    protected function load_options() {
        // Get options from settings
        $options = $this->get_setting('options', []);
        
        // Add default "Any" option if enabled
        if ($this->get_setting('show_any_option', true)) {
            array_unshift($options, [
                'value' => '',
                'label' => $this->get_setting('any_option_text', __('Any', 'motionflow')),
                'count' => 0,
            ]);
        }
        
        $this->options = $options;
    }

    /**
     * Render the filter.
     *
     * @return string
     */
    public function render() {
        // Start with the wrapper
        $html = $this->get_wrapper_start();
        
        // Start select
        $html .= '<select class="motionflow-dropdown-filter" name="' . esc_attr($this->id) . '" data-filter-dropdown="' . esc_attr($this->id) . '">';
        
        // Add options
        foreach ($this->options as $option) {
            // Parse option
            $option = $this->parse_option($option);
            
            // Create option HTML
            $html .= '<option value="' . esc_attr($option['value']) . '" ';
            
            // Add selected attribute
            if ($option['selected']) {
                $html .= 'selected="selected" ';
            }
            
            // Add disabled attribute
            if ($option['disabled']) {
                $html .= 'disabled="disabled" ';
            }
            
            // Add data attributes
            foreach ($option['data'] as $key => $value) {
                $html .= 'data-' . esc_attr($key) . '="' . esc_attr($value) . '" ';
            }
            
            $html .= '>';
            
            // Option label
            $html .= esc_html($option['label']);
            
            // Option count
            if ($option['count'] > 0 && $this->get_setting('show_counts', true)) {
                $html .= ' (' . $option['count'] . ')';
            }
            
            $html .= '</option>';
        }
        
        // End select
        $html .= '</select>';
        
        // End wrapper
        $html .= $this->get_wrapper_end();
        
        return $html;
    }
}