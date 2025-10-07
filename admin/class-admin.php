<?php

/**
 * Admin functionality for the Find an Allergist plugin
 *
 * @package FAA
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class FAA_Admin
{
    /**
     * Initialize the admin functionality
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_admin_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_faa_reset_settings', array($this, 'ajax_reset_settings'));
    }

    /**
     * Add admin menu pages
     */
    public function add_admin_menu()
    {
        // Main menu page
        add_menu_page(
            'Find an Allergist', // Page title
            'Find an Allergist', // Menu title
            'manage_options', // Capability
            'faa', // Menu slug
            array($this, 'admin_page_main'), // Callback function
            'dashicons-location-alt', // Icon
            25 // Position
        );

        // Settings submenu
        add_submenu_page(
            'faa', // Parent slug
            'Find Allergist Settings', // Page title
            'Settings', // Menu title
            'manage_options', // Capability
            'faa-settings', // Menu slug
            array($this, 'admin_page_settings') // Callback function
        );
    }

    /**
     * Initialize admin settings
     */
    public function init_admin_settings()
    {
        register_setting(
            'faa_settings',
            'faa_options',
            array(
                'sanitize_callback' => array($this, 'sanitize_settings'),
                'default' => $this->get_default_settings()
            )
        );

        // General Settings Section
        add_settings_section(
            'faa_general',
            'General Settings',
            array($this, 'settings_section_general_callback'),
            'faa-settings'
        );

        // Google Maps API Key
        add_settings_field(
            'google_maps_api_key',
            'Google Maps API Key',
            array($this, 'google_maps_api_key_callback'),
            'faa-settings',
            'faa_general'
        );

        // Edit Profile Page Slug
        add_settings_field(
            'edit_profile_page_slug',
            'Find an Allergist - Edit Profile Page Slug',
            array($this, 'edit_profile_page_slug_callback'),
            'faa-settings',
            'faa_general'
        );

        // Search Form Settings Section
        add_settings_section(
            'faa_search_form',
            'Search Form Settings',
            array($this, 'settings_section_search_form_callback'),
            'faa-settings'
        );

        // Search Form Title
        add_settings_field(
            'search_form_title',
            'Search Form Title',
            array($this, 'search_form_title_callback'),
            'faa-settings',
            'faa_search_form'
        );

        // Search Form Intro Text
        add_settings_field(
            'search_form_intro',
            'Search Form Intro Text',
            array($this, 'search_form_intro_callback'),
            'faa-settings',
            'faa_search_form'
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook)
    {
        // Only load on our plugin pages
        if (strpos($hook, 'faa') === false) {
            return;
        }

        // Ensure asset loader functions are available
        if (!function_exists('faa_get_asset_url')) {
            require_once plugin_dir_path(__FILE__) . '../includes/class-asset-loader.php';
        }

        $asset_base_url = plugin_dir_url(__FILE__) . '../assets/';
        wp_enqueue_style(
            'faa-admin',
            faa_get_asset_url('css/admin.css', $asset_base_url),
            array(),
            faa_get_asset_version('css/admin.css')
        );

        wp_enqueue_script(
            'faa-admin',
            faa_get_asset_url('js/admin.js', $asset_base_url),
            array('jquery'),
            faa_get_asset_version('js/admin.js'),
            true
        );

        // Localize script for AJAX
        wp_localize_script(
            'faa-admin',
            'faaAdmin',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('faa_admin_nonce'),
                'strings' => array(
                    'confirmReset' => __('Are you sure you want to reset all settings? This cannot be undone.', 'faa'),
                    'resetting' => __('Resetting...', 'faa'),
                    'resetSuccess' => __('Settings reset successfully!', 'faa'),
                    'resetError' => __('An error occurred while resetting settings.', 'faa'),
                    'apiKeyRequired' => __('Please enter an API key first.', 'faa'),
                    'testing' => __('Testing...', 'faa'),
                    'apiKeyValid' => __('✓ API Key Valid', 'faa'),
                    'apiKeyInvalid' => __('✗ API Key Invalid', 'faa'),
                    'testFailed' => __('✗ Test Failed', 'faa'),
                    'testApiKey' => __('Test API Key', 'faa'),
                    'invalidApiKey' => __('Google Maps API key format appears to be invalid.', 'faa')
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
     * Settings section callbacks
     */
    public function settings_section_general_callback()
    {
        echo '<p>' . esc_html__('Configure general settings for the Find an Allergist plugin.', 'faa') . '</p>';
    }

    public function settings_section_search_form_callback()
    {
        echo '<p>' . esc_html__('Customize the search form appearance and content.', 'faa') . '</p>';
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
        $options = get_option('faa_options', []);
        return isset($options[$key]) ? $options[$key] : $default;
    }

    /**
     * Settings field callbacks
     */
    public function google_maps_api_key_callback()
    {
        $value = $this->get_option_value('google_maps_api_key');

        printf(
            '<input type="text" id="google_maps_api_key" name="faa_options[google_maps_api_key]" value="%s" class="regular-text" />',
            esc_attr($value)
        );
        echo '<button type="button" class="button button-secondary" id="faa-test-api-key" style="margin-left: 10px;">' . esc_html__('Test API Key', 'faa') . '</button>';
        echo '<span id="faa-api-test-result" style="margin-left: 10px;"></span>';
        echo '<p class="description">' . esc_html__('Enter your Google Maps API key for map functionality.', 'faa') . '</p>';
    }

    /**
     * Edit Profile Page Slug field callback
     */
    public function edit_profile_page_slug_callback()
    {
        $value = $this->get_option_value('edit_profile_page_slug');

        printf(
            '<input type="text" id="edit_profile_page_slug" name="faa_options[edit_profile_page_slug]" value="%s" class="regular-text" />',
            esc_attr($value)
        );
        echo '<p class="description">' . esc_html__('Enter the slug for the "Find an Allergist - Edit Profile" page. This is where Wild Apricot users will be redirected to if they try to access the WP back-end while logged in', 'faa') . '</p>';
    }

    /**
     * Search Form Title field callback
     */
    public function search_form_title_callback()
    {
        $value = $this->get_option_value('search_form_title', 'Find an Allergist Near You');

        printf(
            '<input type="text" id="search_form_title" name="faa_options[search_form_title]" value="%s" class="regular-text" />',
            esc_attr($value)
        );
        echo '<p class="description">' . esc_html__('Enter the title text for the search form.', 'faa') . '</p>';
    }

    /**
     * Search Form Intro Text field callback
     */
    public function search_form_intro_callback()
    {
        $value = $this->get_option_value('search_form_intro', '');

        wp_editor(
            $value,
            'search_form_intro',
            array(
                'textarea_name' => 'faa_options[search_form_intro]',
                'textarea_rows' => 5,
                'media_buttons' => false,
                'teeny' => true,
                'quicktags' => true
            )
        );
        echo '<p class="description">' . esc_html__('Enter introductory text to display above the search form.', 'faa') . '</p>';
    }

    /**
     * Get default settings
     */
    public function get_default_settings()
    {
        return array(
            'google_maps_api_key' => '',
            'edit_profile_page_slug' => 'find-an-allergist-edit-profile',
            'search_form_title' => 'Find an Allergist Near You',
            'search_form_intro' => ''
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
                    'faa_options',
                    'invalid_api_key',
                    'Google Maps API key format appears to be invalid.',
                    'error'
                );
            }
        }

        // Sanitize Edit Profile Page Slug
        if (isset($input['edit_profile_page_slug'])) {
            $sanitized['edit_profile_page_slug'] = sanitize_text_field($input['edit_profile_page_slug']);
        }

        // Sanitize Search Form Title
        if (isset($input['search_form_title'])) {
            $sanitized['search_form_title'] = sanitize_text_field($input['search_form_title']);
        }

        // Sanitize Search Form Intro
        if (isset($input['search_form_intro'])) {
            $sanitized['search_form_intro'] = wp_kses_post($input['search_form_intro']);
        }

        return $sanitized;
    }

    /**
     * AJAX handler for reset settings
     */
    public function ajax_reset_settings()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'faa_reset_settings')) {
            wp_send_json_error(__('Security check failed.', 'faa'));
            return;
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission to perform this action.', 'faa'));
            return;
        }

        // Reset to default settings
        $default_settings = $this->get_default_settings();
        $updated = update_option('faa_options', $default_settings);

        if ($updated) {
            wp_send_json_success(__('Settings reset to defaults successfully.', 'faa'));
        } else {
            wp_send_json_error(__('Failed to reset settings. Please try again.', 'faa'));
        }
    }
}
