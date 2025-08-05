<?php

/**
 * Plugin Name:     Dalen Find Allergist
 * Plugin URI:      https://www.dalendesign.com/
 * Description:     CSACI Find an Allergist plugin for Dalen Design.
 * Author:          Dalen Design
 * Author URI:      https://www.dalendesign.com/
 * Text Domain:     dalen-find-allergist
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Dalen_Find_Allergist
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

include_once plugin_dir_path(__FILE__) . 'includes/custom-role.php';
include_once plugin_dir_path(__FILE__) . 'includes/custom-post.php';
include_once plugin_dir_path(__FILE__) . 'includes/login-redirect.php';
