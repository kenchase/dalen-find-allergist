<?php

/**
 * Shortcodes Loader for Find an Allergist Plugin
 *
 * @package FAA
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize all shortcodes
 */
function faa_init_shortcodes()
{
    // Load base class
    require_once plugin_dir_path(__FILE__) . 'shortcodes/class-shortcode-base.php';

    // Load individual shortcode classes
    require_once plugin_dir_path(__FILE__) . 'shortcodes/class-shortcode-faa-search-form.php';
    require_once plugin_dir_path(__FILE__) . 'shortcodes/class-shortcode-faa-results.php';
    require_once plugin_dir_path(__FILE__) . 'shortcodes/class-shortcode-faa-profile-editor.php';

    // Initialize shortcode instances
    new FAA_Search_Form_Shortcode();
    new FAA_Search_Results_Shortcode();
    new FAA_Profile_Editor_Shortcode();
}

// Initialize shortcodes
add_action('init', 'faa_init_shortcodes');
