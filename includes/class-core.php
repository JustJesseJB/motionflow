<?php
/**
 * Core Class
 *
 * @package    MotionFlow
 * @subpackage MotionFlow/includes
 */

namespace MotionFlow;

/**
 * The core plugin class.
 */
class Core {

    /**
     * The loader that's responsible for maintaining and registering all hooks.
     *
     * @var Loader
     */
    protected $loader;

    /**
     * The current version of the plugin.
     *
     * @var string
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     */
    public function __construct() {
        $this->version = MOTIONFLOW_VERSION;
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_api_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @return void
     */
    private function load_dependencies() {
        // The class responsible for orchestrating the actions and filters of the plugin.
        $this->loader = new Loader();
        
        // Log the initialization
        \MotionFlow_Logger::info('MotionFlow Core initialized', ['version' => $this->version]);
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * @return void
     */
    private function set_locale() {
        $i18n = new I18n();
        $this->loader->add_action('plugins_loaded', $i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality.
     *
     * @return void
     */
    private function define_admin_hooks() {
        $admin = new Admin\Main($this->version);
        
        // Admin menu and settings
        $this->loader->add_action('admin_menu', $admin, 'add_plugin_menu');
        $this->loader->add_action('admin_init', $admin, 'register_settings');
        
        // Admin assets
        $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_scripts');
        
        // Plugin action links
        $this->loader->add_filter('plugin_action_links_' . MOTIONFLOW_PLUGIN_BASENAME, $admin, 'add_action_links');
    }

    /**
     * Register all of the hooks related to the public-facing functionality.
     *
     * @return void
     */
    private function define_public_hooks() {
        $public = new Frontend\Main($this->version);
        
        // Public assets
        $this->loader->add_action('wp_enqueue_scripts', $public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $public, 'enqueue_scripts');
        
        // Shortcodes
        $this->loader->add_action('init', $public, 'register_shortcodes');
        
        // WooCommerce integration hooks
        $woocommerce = new Integrations\WooCommerce();
        $this->loader->add_action('woocommerce_before_shop_loop', $woocommerce, 'display_filters', 30);
        $this->loader->add_filter('woocommerce_product_query', $woocommerce, 'modify_product_query', 10, 2);
    }

    /**
     * Register all of the hooks related to the REST API functionality.
     *
     * @return void
     */
    private function define_api_hooks() {
        $api = new API\Main($this->version);
        $this->loader->add_action('rest_api_init', $api, 'register_routes');
    }

    /**
     * Run the loader to execute all the hooks.
     *
     * @return void
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The reference to the class that orchestrates the hooks.
     *
     * @return Loader
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @return string
     */
    public function get_version() {
        return $this->version;
    }
}