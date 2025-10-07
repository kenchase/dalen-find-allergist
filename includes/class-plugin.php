<?php

/**
 * Main Plugin Class for Find an Allergist
 *
 * @package FAA
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main plugin class
 */
class FAA_Plugin
{
    /**
     * Plugin version
     *
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * Single instance of the plugin
     *
     * @var FAA_Plugin
     */
    private static $instance = null;

    /**
     * Plugin file path
     *
     * @var string
     */
    private $plugin_file;

    /**
     * Plugin directory path
     *
     * @var string
     */
    private $plugin_path;

    /**
     * Plugin directory URL
     *
     * @var string
     */
    private $plugin_url;

    /**
     * Get single instance of the plugin
     *
     * @param string $plugin_file Main plugin file path
     * @return FAA_Plugin
     */
    public static function get_instance($plugin_file = null)
    {
        if (null === self::$instance) {
            self::$instance = new self($plugin_file);
        }

        return self::$instance;
    }

    /**
     * Constructor
     *
     * @param string $plugin_file Main plugin file path
     */
    private function __construct($plugin_file)
    {
        $this->plugin_file = $plugin_file;
        $this->plugin_path = plugin_dir_path($plugin_file);
        $this->plugin_url = plugin_dir_url($plugin_file);

        $this->init_hooks();
        $this->load_dependencies();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks()
    {
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        add_action('admin_notices', [$this, 'check_api_key_admin_notice']);
        add_filter('acf/fields/google_map/api', [$this, 'configure_acf_google_map_api']);

        register_activation_hook($this->plugin_file, [$this, 'activate']);
        register_deactivation_hook($this->plugin_file, [$this, 'deactivate']);
    }

    /**
     * Load plugin dependencies
     */
    private function load_dependencies()
    {
        // Core functionality
        require_once $this->plugin_path . 'includes/custom-post.php';
        require_once $this->plugin_path . 'includes/custom-post-auto-create.php';
        require_once $this->plugin_path . 'includes/shortcodes.php';
        require_once $this->plugin_path . 'includes/custom-wa-role.php';
        require_once $this->plugin_path . 'includes/rest-api-search.php';

        // Admin functionality
        if (is_admin()) {
            require_once $this->plugin_path . 'admin/class-admin.php';
            new FAA_Admin();
        }
    }

    /**
     * Load plugin textdomain for translations
     */
    public function load_textdomain()
    {
        load_plugin_textdomain(
            'faa',
            false,
            dirname(plugin_basename($this->plugin_file)) . '/languages'
        );
    }

    /**
     * Display admin notice if Google Maps API key is not configured
     */
    public function check_api_key_admin_notice()
    {
        if (!faa_has_google_maps_api_key() && current_user_can('manage_options')) {
            $settings_url = admin_url('admin.php?page=faa-settings');
            printf(
                '<div class="notice notice-warning is-dismissible">
                    <p><strong>%s:</strong> %s 
                    <a href="%s">%s</a> %s</p>
                </div>',
                esc_html__('Find Allergist Plugin', 'faa'),
                esc_html__('Google Maps API key is not configured.', 'faa'),
                esc_url($settings_url),
                esc_html__('Please configure it in the plugin settings', 'faa'),
                esc_html__('for full functionality.', 'faa')
            );
        }
    }

    /**
     * Configure ACF Google Map API key
     *
     * @param array $api ACF Google Map API settings
     * @return array Modified API settings
     */
    public function configure_acf_google_map_api($api)
    {
        // Only configure if ACF is active
        if (!function_exists('acf')) {
            return $api;
        }

        $api_key = faa_get_google_maps_api_key();

        if (!empty($api_key)) {
            $api['key'] = $api_key;
        }

        return $api;
    }

    /**
     * Plugin activation
     */
    public function activate()
    {
        // Register custom post types before flushing rewrite rules
        // This ensures the post type rewrites are properly registered
        if (function_exists('faa_custom_allergist_post')) {
            faa_custom_allergist_post();
        }

        // Flush rewrite rules to ensure custom post types work
        flush_rewrite_rules();

        // Set default options if they don't exist
        $default_options = [
            'google_maps_api_key' => ''
        ];

        if (!get_option('faa_options')) {
            add_option('faa_options', $default_options);
        }
    }

    /**
     * Plugin deactivation
     */
    public function deactivate()
    {
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Get plugin version
     *
     * @return string
     */
    public function get_version()
    {
        return self::VERSION;
    }

    /**
     * Get plugin path
     *
     * @return string
     */
    public function get_plugin_path()
    {
        return $this->plugin_path;
    }

    /**
     * Get plugin URL
     *
     * @return string
     */
    public function get_plugin_url()
    {
        return $this->plugin_url;
    }
}
