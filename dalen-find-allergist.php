<?php

/**
 * Plugin Name:     Dalen Find Allergist
 * Plugin URI:      https://www.dalendesign.com/
 * Description:     CSACI Find an Allergist plugin for Dalen Design.
 * Author:          Dalen Design
 * Author URI:      https://www.dalendesign.com/
 * Text Domain:     dalen-find-allergist
 * Domain Path:     /languages
 * Version:         0.2.0
 *
 * @package         Dalen_Find_Allergist
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get Google Maps API key from plugin settings
 * 
 * @return string The API key or empty string if not set
 */
function dalen_get_google_maps_api_key() {
    $options = get_option('dalen_find_allergist_options');
    return isset($options['google_maps_api_key']) ? $options['google_maps_api_key'] : '';
}

/**
 * Check if Google Maps API key is configured
 * 
 * @return bool True if API key is set, false otherwise
 */
function dalen_has_google_maps_api_key() {
    return !empty(dalen_get_google_maps_api_key());
}

/**
 * Display admin notice if Google Maps API key is not configured
 */
function dalen_check_api_key_admin_notice() {
    if (!dalen_has_google_maps_api_key() && current_user_can('manage_options')) {
        $settings_url = admin_url('admin.php?page=dalen-find-allergist-settings');
        echo '<div class="notice notice-warning is-dismissible">
            <p><strong>Find Allergist Plugin:</strong> Google Maps API key is not configured. 
            <a href="' . esc_url($settings_url) . '">Please configure it in the plugin settings</a> 
            for full functionality.</p>
        </div>';
    }
}
add_action('admin_notices', 'dalen_check_api_key_admin_notice');

function my_acf_google_map_api($api)
{
    $api_key = dalen_get_google_maps_api_key();
    
    if (!empty($api_key)) {
        $api['key'] = $api_key;
    }
    
    return $api;
}
add_filter('acf/fields/google_map/api', 'my_acf_google_map_api');

include_once plugin_dir_path(__FILE__) . 'includes/custom-role.php';
include_once plugin_dir_path(__FILE__) . 'includes/custom-post.php';
include_once plugin_dir_path(__FILE__) . 'includes/shortcodes.php';
include_once plugin_dir_path(__FILE__) . 'includes/rest-api-search.php';
// include_once plugin_dir_path(__FILE__) . 'includes/helper-functions.php';
// include_once plugin_dir_path(__FILE__) . 'includes/login-redirect.php';

// Include admin functionality
if (is_admin()) {
    include_once plugin_dir_path(__FILE__) . 'admin/class-admin.php';
}
