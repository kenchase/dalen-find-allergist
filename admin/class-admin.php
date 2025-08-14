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
        register_setting('dalen_find_allergist_settings', 'dalen_find_allergist_options');

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

        // Display Settings Section
        add_settings_section(
            'dalen_find_allergist_display',
            'Display Settings',
            array($this, 'settings_section_display_callback'),
            'dalen-find-allergist-settings'
        );

        // Show Map
        add_settings_field(
            'show_map',
            'Show Map',
            array($this, 'show_map_callback'),
            'dalen-find-allergist-settings',
            'dalen_find_allergist_display'
        );

        // Show Contact Info
        add_settings_field(
            'show_contact_info',
            'Show Contact Information',
            array($this, 'show_contact_info_callback'),
            'dalen-find-allergist-settings',
            'dalen_find_allergist_display'
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

        wp_enqueue_style(
            'dalen-find-allergist-admin',
            plugin_dir_url(__FILE__) . '../assets/css/admin.css',
            array(),
            '1.0.0'
        );

        wp_enqueue_script(
            'dalen-find-allergist-admin',
            plugin_dir_url(__FILE__) . '../assets/js/admin.js',
            array('jquery'),
            '1.0.0',
            true
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
        echo '<p>Configure general settings for the Find Allergist plugin.</p>';
    }

    public function settings_section_display_callback()
    {
        echo '<p>Configure how the allergist search results are displayed.</p>';
    }

    /**
     * Settings field callbacks
     */
    public function google_maps_api_key_callback()
    {
        $options = get_option('dalen_find_allergist_options');
        $value = isset($options['google_maps_api_key']) ? $options['google_maps_api_key'] : '';
        echo '<input type="text" id="google_maps_api_key" name="dalen_find_allergist_options[google_maps_api_key]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Enter your Google Maps API key for map functionality.</p>';
    }

    public function search_results_limit_callback()
    {
        $options = get_option('dalen_find_allergist_options');
        $value = isset($options['search_results_limit']) ? $options['search_results_limit'] : '20';
        echo '<input type="number" id="search_results_limit" name="dalen_find_allergist_options[search_results_limit]" value="' . esc_attr($value) . '" min="1" max="100" />';
        echo '<p class="description">Maximum number of search results to display.</p>';
    }

    public function default_search_radius_callback()
    {
        $options = get_option('dalen_find_allergist_options');
        $value = isset($options['default_search_radius']) ? $options['default_search_radius'] : '50';
        echo '<input type="number" id="default_search_radius" name="dalen_find_allergist_options[default_search_radius]" value="' . esc_attr($value) . '" min="1" max="500" />';
        echo '<p class="description">Default search radius in kilometers.</p>';
    }

    public function show_map_callback()
    {
        $options = get_option('dalen_find_allergist_options');
        $value = isset($options['show_map']) ? $options['show_map'] : '1';
        echo '<input type="checkbox" id="show_map" name="dalen_find_allergist_options[show_map]" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="show_map">Display map with search results</label>';
    }

    public function show_contact_info_callback()
    {
        $options = get_option('dalen_find_allergist_options');
        $value = isset($options['show_contact_info']) ? $options['show_contact_info'] : '1';
        echo '<input type="checkbox" id="show_contact_info" name="dalen_find_allergist_options[show_contact_info]" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="show_contact_info">Display contact information in results</label>';
    }
}

// Initialize the admin class
if (is_admin()) {
    new Dalen_Find_Allergist_Admin();
}
