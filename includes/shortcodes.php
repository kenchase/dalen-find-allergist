<?php

/**
 * Shortcodes Loader for Dalen Find Allergist Plugin
 *
 * @package Dalen_Find_Allergist
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize all shortcodes
 */
function dalen_find_allergist_init_shortcodes()
{
    // Load base class
    require_once plugin_dir_path(__FILE__) . 'shortcodes/class-shortcode-base.php';

    // Load individual shortcode classes
    require_once plugin_dir_path(__FILE__) . 'shortcodes/class-find-allergist-form.php';
    require_once plugin_dir_path(__FILE__) . 'shortcodes/class-find-allergist-results.php';
    require_once plugin_dir_path(__FILE__) . 'shortcodes/class-acf-form.php';

    // Initialize shortcode instances
    new Find_Allergist_Form_Shortcode();
    new Find_Allergist_Results_Shortcode();
    new Find_Allergist_ACF_Form_Shortcode();
}

// Initialize shortcodes
add_action('init', 'dalen_find_allergist_init_shortcodes');
