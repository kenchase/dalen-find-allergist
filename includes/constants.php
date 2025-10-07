<?php

/**
 * Plugin Constants
 * 
 * @package FAA
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin Information
if (!defined('FAA_VERSION')) {
    define('FAA_VERSION', '1.0.0');
}

// Note: FAA_PLUGIN_FILE should be defined in the main plugin file (faa.php) before including this file
// These path constants depend on FAA_PLUGIN_FILE being set first

if (!defined('FAA_PLUGIN_PATH')) {
    define('FAA_PLUGIN_PATH', plugin_dir_path(FAA_PLUGIN_FILE));
}

if (!defined('FAA_PLUGIN_URL')) {
    define('FAA_PLUGIN_URL', plugin_dir_url(FAA_PLUGIN_FILE));
}

// Database Options
if (!defined('FAA_OPTIONS')) {
    define('FAA_OPTIONS', 'faa_options');
}

// Post Type
if (!defined('FAA_POST_TYPE')) {
    define('FAA_POST_TYPE', 'physicians');
}

// API Settings
if (!defined('FAA_API_NAMESPACE')) {
    define('FAA_API_NAMESPACE', 'faa/v1');
}

if (!defined('FAA_API_ENDPOINT')) {
    define('FAA_API_ENDPOINT', 'physicians/search');
}

// Capability Settings
if (!defined('FAA_CAPABILITY_TYPE')) {
    define('FAA_CAPABILITY_TYPE', 'physicians');
}

// Text Domain
if (!defined('FAA_TEXT_DOMAIN')) {
    define('FAA_TEXT_DOMAIN', 'faa');
}
