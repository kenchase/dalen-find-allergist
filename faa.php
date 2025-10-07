<?php

/**
 * Plugin Name:     Find an Allergist
 * Plugin URI:      https://www.dalendesign.com/
 * Description:     CSACI Find an Allergist plugin for Dalen Design.
 * Author:          Dalen Design
 * Author URI:      https://www.dalendesign.com/
 * Text Domain:     faa
 * Domain Path:     /languages
 * Version:         1.00
 * Requires at least: 5.0
 * Tested up to:    6.4
 * Requires PHP:    7.4
 *
 * @package         FAA
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
require_once plugin_dir_path(__FILE__) . 'includes/constants.php';

/**
 * Get Google Maps API key from plugin settings
 * 
 * @return string The API key or empty string if not set
 */
function faa_get_google_maps_api_key()
{
    $options = get_option(FAA_OPTIONS);
    return isset($options['google_maps_api_key']) ? $options['google_maps_api_key'] : '';
}

/**
 * Check if Google Maps API key is configured
 * 
 * @return bool True if API key is set, false otherwise
 */
function faa_has_google_maps_api_key()
{
    return !empty(faa_get_google_maps_api_key());
}

// Load the asset loader utility
require_once plugin_dir_path(__FILE__) . 'includes/class-asset-loader.php';

// Load the main plugin class
require_once plugin_dir_path(__FILE__) . 'includes/class-plugin.php';

// Initialize the plugin
function faa_init()
{
    return FAA_Plugin::get_instance(__FILE__);
}

// Initialize plugin on plugins_loaded hook
add_action('plugins_loaded', 'faa_init', 10);
