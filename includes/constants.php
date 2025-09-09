<?php

/**
 * Plugin Constants
 * 
 * @package Dalen_Find_Allergist
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin Information
if (!defined('DALEN_FIND_ALLERGIST_VERSION')) {
    define('DALEN_FIND_ALLERGIST_VERSION', '0.9.2');
}

if (!defined('DALEN_FIND_ALLERGIST_PLUGIN_FILE')) {
    define('DALEN_FIND_ALLERGIST_PLUGIN_FILE', dirname(__DIR__) . '/dalen-find-allergist.php');
}

if (!defined('DALEN_FIND_ALLERGIST_PLUGIN_PATH')) {
    define('DALEN_FIND_ALLERGIST_PLUGIN_PATH', dirname(__DIR__) . '/');
}

if (!defined('DALEN_FIND_ALLERGIST_PLUGIN_URL')) {
    define('DALEN_FIND_ALLERGIST_PLUGIN_URL', plugin_dir_url(DALEN_FIND_ALLERGIST_PLUGIN_FILE));
}

// Database Options
if (!defined('DALEN_FIND_ALLERGIST_OPTIONS')) {
    define('DALEN_FIND_ALLERGIST_OPTIONS', 'dalen_find_allergist_options');
}

// Post Type and Taxonomy
if (!defined('DALEN_ALLERGIST_POST_TYPE')) {
    define('DALEN_ALLERGIST_POST_TYPE', 'physicians');
}

if (!defined('DALEN_ALLERGIST_TAXONOMY')) {
    define('DALEN_ALLERGIST_TAXONOMY', 'physiciantypes');
}

// API Settings
if (!defined('DALEN_API_NAMESPACE')) {
    define('DALEN_API_NAMESPACE', 'dalen/v1');
}

if (!defined('DALEN_API_ENDPOINT')) {
    define('DALEN_API_ENDPOINT', 'physicians/search');
}

// Default Settings
if (!defined('DALEN_DEFAULT_SEARCH_LIMIT')) {
    define('DALEN_DEFAULT_SEARCH_LIMIT', 20);
}

if (!defined('DALEN_DEFAULT_SEARCH_RADIUS')) {
    define('DALEN_DEFAULT_SEARCH_RADIUS', 50);
}

if (!defined('DALEN_MAX_SEARCH_LIMIT')) {
    define('DALEN_MAX_SEARCH_LIMIT', 100);
}

if (!defined('DALEN_MAX_SEARCH_RADIUS')) {
    define('DALEN_MAX_SEARCH_RADIUS', 500);
}

// Capability Settings
if (!defined('DALEN_ALLERGIST_CAPABILITY_TYPE')) {
    define('DALEN_ALLERGIST_CAPABILITY_TYPE', 'physicians');
}

// Text Domain
if (!defined('DALEN_FIND_ALLERGIST_TEXT_DOMAIN')) {
    define('DALEN_FIND_ALLERGIST_TEXT_DOMAIN', 'dalen-find-allergist');
}
