<?php
/**
 * I18n Class
 *
 * @package    MotionFlow
 * @subpackage MotionFlow/includes
 */

namespace MotionFlow;

/**
 * Define the internationalization functionality.
 */
class I18n {

    /**
     * Load the plugin text domain for translation.
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'motionflow',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
}