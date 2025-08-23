<?php

/**
 * Admin functionality for Dalen Find Allergist plugin
 *
 * @package Dalen_Find_Allergist
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Dalen_Find_Allergist_Admin
{
    /**
     * Initialize the admin functionality
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_admin_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_dalen_reset_settings', array($this, 'ajax_reset_settings'));
    }

    /**
     * Add admin menu pages
     */
    public function add_admin_menu()
    {
        // Main menu page
        add_menu_page(
            'Find Allergist', // Page title
            'Find Allergist', // Menu title
            'manage_options', // Capability
            'dalen-find-allergist', // Menu slug
            array($this, 'admin_page_main'), // Callback function
            'dashicons-location-alt', // Icon
            25 // Position
        );

        // Settings submenu
        add_submenu_page(
            'dalen-find-allergist', // Parent slug
            'Find Allergist Settings', // Page title
            'Settings', // Menu title
            'manage_options', // Capability
            'dalen-find-allergist-settings', // Menu slug
            array($this, 'admin_page_settings') // Callback function
        );

        // Help submenu
        add_submenu_page(
            'dalen-find-allergist', // Parent slug
            'Find Allergist Help', // Page title
            'Help', // Menu title
            'manage_options', // Capability
            'dalen-find-allergist-help', // Menu slug
            array($this, 'admin_page_help') // Callback function
        );
    }

    /**
     * Initialize admin settings
     */
    public function init_admin_settings()
    {
        register_setting(
            'dalen_find_allergist_settings',
            'dalen_find_allergist_options',
            array(
                'sanitize_callback' => array($this, 'sanitize_settings'),
                'default' => $this->get_default_settings()
            )
        );

        // General Settings Section
        add_settings_section(
            'dalen_find_allergist_general',
            'General Settings',
            array($this, 'settings_section_general_callback'),
            'dalen-find-allergist-settings'
        );

        // Google Maps API Key
        add_settings_field(
            'google_maps_api_key',
            'Google Maps API Key',
            array($this, 'google_maps_api_key_callback'),
            'dalen-find-allergist-settings',
            'dalen_find_allergist_general'
        );

        // Search Results Limit
        add_settings_field(
            'search_results_limit',
            'Search Results Limit',
            array($this, 'search_results_limit_callback'),
            'dalen-find-allergist-settings',
            'dalen_find_allergist_general'
        );

        // Default Search Radius
        add_settings_field(
            'default_search_radius',
            'Default Search Radius (km)',
            array($this, 'default_search_radius_callback'),
            'dalen-find-allergist-settings',
            'dalen_find_allergist_general'
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook)
    {
        // Only load on our plugin pages
        if (strpos($hook, 'dalen-find-allergist') === false) {
            return;
        }

        // Ensure asset loader functions are available
        if (!function_exists('dalen_get_asset_url')) {
            require_once plugin_dir_path(__FILE__) . '../includes/class-asset-loader.php';
        }

        $asset_base_url = plugin_dir_url(__FILE__) . '../assets/';
        wp_enqueue_style(
            'dalen-find-allergist-admin',
            dalen_get_asset_url('css/admin.css', $asset_base_url),
            array(),
            dalen_get_asset_version('css/admin.css')
        );

        wp_enqueue_script(
            'dalen-find-allergist-admin',
            dalen_get_asset_url('js/admin.js', $asset_base_url),
            array('jquery'),
            dalen_get_asset_version('js/admin.js'),
            true
        );

        // Localize script for AJAX
        wp_localize_script(
            'dalen-find-allergist-admin',
            'dalenAdmin',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('dalen_admin_nonce'),
                'strings' => array(
                    'confirmReset' => __('Are you sure you want to reset all settings? This cannot be undone.', 'dalen-find-allergist'),
                    'resetting' => __('Resetting...', 'dalen-find-allergist'),
                    'resetSuccess' => __('Settings reset successfully!', 'dalen-find-allergist'),
                    'resetError' => __('An error occurred while resetting settings.', 'dalen-find-allergist'),
                    'apiKeyRequired' => __('Please enter an API key first.', 'dalen-find-allergist'),
                    'testing' => __('Testing...', 'dalen-find-allergist'),
                    'apiKeyValid' => __('✓ API Key Valid', 'dalen-find-allergist'),
                    'apiKeyInvalid' => __('✗ API Key Invalid', 'dalen-find-allergist'),
                    'testFailed' => __('✗ Test Failed', 'dalen-find-allergist'),
                    'testApiKey' => __('Test API Key', 'dalen-find-allergist'),
                    'invalidApiKey' => __('Google Maps API key format appears to be invalid.', 'dalen-find-allergist'),
                    'invalidResultsLimit' => __('Search results limit must be between 1 and 100.', 'dalen-find-allergist'),
                    'invalidRadius' => __('Default search radius must be between 1 and 500 km.', 'dalen-find-allergist'),
                    'validationErrors' => __('Please fix the following errors:', 'dalen-find-allergist')
                )
            )
        );
    }

    /**
     * Main admin page
     */
    public function admin_page_main()
    {
        include_once plugin_dir_path(__FILE__) . 'partials/admin-main.php';
    }

    /**
     * Settings admin page
     */
    public function admin_page_settings()
    {
        include_once plugin_dir_path(__FILE__) . 'partials/admin-settings.php';
    }

    /**
     * Help admin page
     */
    public function admin_page_help()
    {
        include_once plugin_dir_path(__FILE__) . 'partials/admin-help.php';
    }

    /**
     * Settings section callbacks
     */
    public function settings_section_general_callback()
    {
        echo '<p>' . esc_html__('Configure general settings for the Find Allergist plugin.', 'dalen-find-allergist') . '</p>';
    }

    /**
     * Get option value with fallback
     * 
     * @param string $key Option key
     * @param mixed $default Default value
     * @return mixed Option value or default
     */
    private function get_option_value($key, $default = '')
    {
        $options = get_option('dalen_find_allergist_options', []);
        return isset($options[$key]) ? $options[$key] : $default;
    }

    /**
     * Settings field callbacks
     */
    public function google_maps_api_key_callback()
    {
        $value = $this->get_option_value('google_maps_api_key');

        printf(
            '<input type="text" id="google_maps_api_key" name="dalen_find_allergist_options[google_maps_api_key]" value="%s" class="regular-text" />',
            esc_attr($value)
        );
        echo '<p class="description">' . esc_html__('Enter your Google Maps API key for map functionality.', 'dalen-find-allergist') . '</p>';
    }

    public function search_results_limit_callback()
    {
        $value = $this->get_option_value('search_results_limit', '20');

        printf(
            '<input type="number" id="search_results_limit" name="dalen_find_allergist_options[search_results_limit]" value="%s" min="1" max="100" class="small-text" />',
            esc_attr($value)
        );
        echo '<p class="description">' . esc_html__('Maximum number of search results to display (1-100).', 'dalen-find-allergist') . '</p>';
    }

    public function default_search_radius_callback()
    {
        $value = $this->get_option_value('default_search_radius', '50');

        printf(
            '<input type="number" id="default_search_radius" name="dalen_find_allergist_options[default_search_radius]" value="%s" min="1" max="500" class="small-text" />',
            esc_attr($value)
        );
        echo '<p class="description">' . esc_html__('Default search radius in kilometers (1-500).', 'dalen-find-allergist') . '</p>';
    }

    /**
     * Get default settings
     */
    public function get_default_settings()
    {
        return array(
            'google_maps_api_key' => '',
            'search_results_limit' => '20',
            'default_search_radius' => '50'
        );
    }

    /**
     * Sanitize settings input
     */
    public function sanitize_settings($input)
    {
        $sanitized = array();

        // Sanitize Google Maps API key
        if (isset($input['google_maps_api_key'])) {
            $sanitized['google_maps_api_key'] = sanitize_text_field($input['google_maps_api_key']);

            // Validate API key format
            if (!empty($sanitized['google_maps_api_key']) && !preg_match('/^AIza[0-9A-Za-z-_]{35}$/', $sanitized['google_maps_api_key'])) {
                add_settings_error(
                    'dalen_find_allergist_options',
                    'invalid_api_key',
                    'Google Maps API key format appears to be invalid.',
                    'error'
                );
            }
        }

        // Sanitize and validate search results limit
        if (isset($input['search_results_limit'])) {
            $limit = intval($input['search_results_limit']);
            if ($limit < 1 || $limit > 100) {
                add_settings_error(
                    'dalen_find_allergist_options',
                    'invalid_results_limit',
                    'Search results limit must be between 1 and 100.',
                    'error'
                );
                $sanitized['search_results_limit'] = '20'; // Default value
            } else {
                $sanitized['search_results_limit'] = (string) $limit;
            }
        }

        // Sanitize and validate default search radius
        if (isset($input['default_search_radius'])) {
            $radius = intval($input['default_search_radius']);
            if ($radius < 1 || $radius > 500) {
                add_settings_error(
                    'dalen_find_allergist_options',
                    'invalid_radius',
                    'Default search radius must be between 1 and 500 km.',
                    'error'
                );
                $sanitized['default_search_radius'] = '50'; // Default value
            } else {
                $sanitized['default_search_radius'] = (string) $radius;
            }
        }

        return $sanitized;
    }

    /**
     * AJAX handler for reset settings
     */
    public function ajax_reset_settings()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dalen_reset_settings')) {
            wp_send_json_error(__('Security check failed.', 'dalen-find-allergist'));
            return;
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission to perform this action.', 'dalen-find-allergist'));
            return;
        }

        // Reset to default settings
        $default_settings = $this->get_default_settings();
        $updated = update_option('dalen_find_allergist_options', $default_settings);

        if ($updated) {
            wp_send_json_success(__('Settings reset to defaults successfully.', 'dalen-find-allergist'));
        } else {
            wp_send_json_error(__('Failed to reset settings. Please try again.', 'dalen-find-allergist'));
        }
    }
}
