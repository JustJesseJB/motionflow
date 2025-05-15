<?php
/**
 * Filter Base Class
 *
 * @package    MotionFlow
 * @subpackage MotionFlow/includes/filters
 */

namespace MotionFlow\Filters;

/**
 * Base class for filter types.
 */
abstract class Filter_Base {

    /**
     * Filter ID.
     *
     * @var string
     */
    protected $id;

    /**
     * Filter label.
     *
     * @var string
     */
    protected $label;

    /**
     * Filter type.
     *
     * @var string
     */
    protected $type;

    /**
     * Filter settings.
     *
     * @var array
     */
    protected $settings;

    /**
     * Filter options.
     *
     * @var array
     */
    protected $options;

    /**
     * Current filter value.
     *
     * @var mixed
     */
    protected $value;

    /**
     * Initialize the filter.
     *
     * @param string $id       The filter ID.
     * @param string $label    The filter label.
     * @param array  $settings The filter settings.
     */
    public function __construct($id, $label, $settings = []) {
        $this->id = $id;
        $this->label = $label;
        $this->settings = $settings;
        $this->options = [];
        $this->value = null;
        
        // Set filter type (should be overridden by child classes)
        $this->type = 'base';
        
        // Load options
        $this->load_options();
        
        // Get current value
        $this->get_current_value();
    }

    /**
     * Load filter options.
     *
     * @return void
     */
    abstract protected function load_options();

    /**
     * Render the filter.
     *
     * @return string
     */
    abstract public function render();

    /**
     * Get the filter ID.
     *
     * @return string
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Get the filter label.
     *
     * @return string
     */
    public function get_label() {
        return $this->label;
    }

    /**
     * Get the filter type.
     *
     * @return string
     */
    public function get_type() {
        return $this->type;
    }

    /**
     * Get the filter options.
     *
     * @return array
     */
    public function get_options() {
        return $this->options;
    }

    /**
     * Get the current filter value.
     *
     * @return mixed
     */
    public function get_value() {
        return $this->value;
    }

    /**
     * Set the current filter value.
     *
     * @param mixed $value The filter value.
     *
     * @return void
     */
    public function set_value($value) {
        $this->value = $value;
    }

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
     * Check if the filter has an active value.
     *
     * @return bool
     */
    public function is_active() {
        return $this->value !== null && $this->value !== '';
    }

    /**
     * Get the current filter value from request.
     *
     * @return void
     */
    protected function get_current_value() {
        // Try to get value from request
        if (isset($_GET[$this->id])) {
            $this->value = sanitize_text_field($_GET[$this->id]);
        } elseif (isset($_GET['filter_' . $this->id])) {
            $this->value = sanitize_text_field($_GET['filter_' . $this->id]);
        }
    }

    /**
     * Get the filter URL for a specific option.
     *
     * @param string $value The option value.
     *
     * @return string
     */
    protected function get_filter_url($value) {
        global $wp;
        
        // Get current URL parameters
        $params = $_GET;
        
        // Set or remove the filter parameter
        if ($value === '' || $value === null) {
            unset($params[$this->id]);
        } else {
            $params[$this->id] = $value;
        }
        
        // Reset page parameter
        unset($params['paged']);
        
        // Build the URL
        $base_url = home_url($wp->request);
        
        if (empty($params)) {
            return $base_url;
        }
        
        return add_query_arg($params, $base_url);
    }

    /**
     * Get the reset URL for this filter.
     *
     * @return string
     */
    protected function get_reset_url() {
        return $this->get_filter_url('');
    }

    /**
     * Check if a specific option is selected.
     *
     * @param string $value The option value.
     *
     * @return bool
     */
    protected function is_option_selected($value) {
        if (is_array($this->value)) {
            return in_array($value, $this->value);
        }
        
        return $this->value === $value;
    }

    /**
     * Parse option settings.
     *
     * @param array $option The option to parse.
     *
     * @return array
     */
    protected function parse_option($option) {
        // Default option structure
        $default = [
            'value' => '',
            'label' => '',
            'count' => 0,
            'tooltip' => '',
            'selected' => false,
            'disabled' => false,
            'data' => [],
        ];
        
        // Parse option
        $parsed = wp_parse_args($option, $default);
        
        // Check if selected
        $parsed['selected'] = $this->is_option_selected($parsed['value']);
        
        return $parsed;
    }

    /**
     * Get the item count HTML.
     *
     * @param int $count The count to display.
     *
     * @return string
     */
    protected function get_count_html($count) {
        if ($count <= 0 || !$this->get_setting('show_counts', true)) {
            return '';
        }
        
        return '<span class="motionflow-filter-count">(' . $count . ')</span>';
    }

    /**
     * Get the filter wrapper classes.
     *
     * @return string
     */
    protected function get_wrapper_classes() {
        $classes = [
            'motionflow-filter',
            'motionflow-filter-' . $this->type,
            'motionflow-filter-' . $this->id,
        ];
        
        if ($this->is_active()) {
            $classes[] = 'motionflow-filter-active';
        }
        
        // Allow custom classes
        $custom_classes = $this->get_setting('classes', '');
        if (!empty($custom_classes)) {
            $classes = array_merge($classes, explode(' ', $custom_classes));
        }
        
        return implode(' ', array_map('sanitize_html_class', $classes));
    }

    /**
     * Get data attributes for the filter wrapper.
     *
     * @return string
     */
    protected function get_data_attributes() {
        $data_attrs = [
            'data-filter-id="' . esc_attr($this->id) . '"',
            'data-filter-type="' . esc_attr($this->type) . '"',
        ];
        
        // Add custom data attributes
        $custom_data = $this->get_setting('data_attributes', []);
        foreach ($custom_data as $key => $value) {
            $data_attrs[] = 'data-' . esc_attr($key) . '="' . esc_attr($value) . '"';
        }
        
        return implode(' ', $data_attrs);
    }

    /**
     * Get the filter wrapper opening HTML.
     *
     * @return string
     */
    protected function get_wrapper_start() {
        $html = '<div class="' . $this->get_wrapper_classes() . '" ' . $this->get_data_attributes() . '>';
        
        // Add filter title if enabled
        if ($this->get_setting('show_title', true)) {
            $html .= '<div class="motionflow-filter-title">';
            $html .= '<span>' . esc_html($this->label) . '</span>';
            
            // Add clear button if filter is active
            if ($this->is_active()) {
                $html .= '<a href="' . esc_url($this->get_reset_url()) . '" class="motionflow-filter-clear" data-filter-clear="' . esc_attr($this->id) . '">' . esc_html__('Clear', 'motionflow') . '</a>';
            }
            
            $html .= '</div>';
        }
        
        // Add filter content wrapper
        $html .= '<div class="motionflow-filter-content">';
        
        return $html;
    }

    /**
     * Get the filter wrapper closing HTML.
     *
     * @return string
     */
    protected function get_wrapper_end() {
        return '</div></div>';
    }

    /**
     * Get the filter option rendered as HTML.
     *
     * @param array $option The option to render.
     *
     * @return string
     */
    protected function get_option_html($option) {
        // Parse option
        $option = $this->parse_option($option);
        
        // Option classes
        $classes = [
            'motionflow-filter-option',
            'motionflow-filter-option-' . sanitize_html_class($option['value']),
        ];
        
        if ($option['selected']) {
            $classes[] = 'motionflow-filter-option-selected';
        }
        
        if ($option['disabled']) {
            $classes[] = 'motionflow-filter-option-disabled';
        }
        
        // Option URL
        $url = $option['selected'] ? $this->get_reset_url() : $this->get_filter_url($option['value']);
        
        // Option HTML
        $html = '<a href="' . esc_url($url) . '" class="' . implode(' ', $classes) . '" ';
        
        // Add data attributes
        $html .= 'data-value="' . esc_attr($option['value']) . '" ';
        
        foreach ($option['data'] as $key => $value) {
            $html .= 'data-' . esc_attr($key) . '="' . esc_attr($value) . '" ';
        }
        
        // Add tooltip if available
        if (!empty($option['tooltip'])) {
            $html .= 'title="' . esc_attr($option['tooltip']) . '" ';
        }
        
        // Close tag
        $html .= '>';
        
        // Option label
        $html .= '<span class="motionflow-filter-option-label">' . esc_html($option['label']) . '</span>';
        
        // Option count
        $html .= $this->get_count_html($option['count']);
        
        $html .= '</a>';
        
        return $html;
    }
}