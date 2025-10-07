<?php

/**
 * Asset loader utility for Find an Allergist
 * Automatically loads minified assets in production
 */

if (!defined('ABSPATH')) {
    exit;
}

class FAA_Asset_Loader
{

    /**
     * Get the appropriate asset URL (minified in production, regular in development)
     * 
     * @param string $asset_path Relative path to the asset (e.g., 'css/admin.css')
     * @param string $base_url   Base URL for assets
     * @return string            Full URL to the asset
     */
    public static function get_asset_url($asset_path, $base_url)
    {
        $use_minified = self::should_use_minified();

        if ($use_minified) {
            // Convert path to minified version
            $minified_path = self::get_minified_path($asset_path);
            $minified_url = $base_url . $minified_path;

            // Check if minified file exists
            $minified_file_path = self::url_to_path($minified_url);
            if (file_exists($minified_file_path)) {
                return $minified_url;
            }
        }

        // Fallback to original file
        return $base_url . $asset_path;
    }

    /**
     * Determine if we should use minified assets
     * 
     * @return bool
     */
    private static function should_use_minified()
    {
        // Use minified assets when not in debug mode
        return !defined('WP_DEBUG') || !WP_DEBUG;
    }

    /**
     * Convert a regular asset path to its minified equivalent
     * 
     * @param string $path Original asset path
     * @return string      Minified asset path
     */
    public static function get_minified_path($path)
    {
        $path_info = pathinfo($path);
        $dir = $path_info['dirname'];
        $filename = $path_info['filename'];
        $extension = $path_info['extension'];

        // Remove leading slash if present
        if ($dir === '.') {
            $dir = '';
        } else {
            $dir = ltrim($dir, '/') . '/';
        }

        return $dir . $filename . '.min.' . $extension;
    }

    /**
     * Convert URL to file path
     * 
     * @param string $url Asset URL
     * @return string     File path
     */
    private static function url_to_path($url)
    {
        $upload_dir = wp_upload_dir();
        $base_url = $upload_dir['baseurl'];
        $base_dir = $upload_dir['basedir'];

        // Get the plugin base URL (one level up from includes directory)
        $plugin_base_url = plugin_dir_url(dirname(__FILE__));
        $plugin_base_path = plugin_dir_path(dirname(__FILE__));

        // Handle plugin assets
        if (strpos($url, $plugin_base_url) === 0) {
            $relative_path = str_replace($plugin_base_url, '', $url);
            return $plugin_base_path . $relative_path;
        }

        // Handle other URLs
        if (strpos($url, $base_url) === 0) {
            $relative_path = str_replace($base_url, '', $url);
            return $base_dir . $relative_path;
        }

        return $url;
    }

    /**
     * Get asset version for cache busting
     * 
     * @param string $file_path Path to the asset file
     * @return string           Version string
     */
    public static function get_asset_version($file_path)
    {
        if (file_exists($file_path)) {
            return filemtime($file_path);
        }

        // Fallback to plugin version
        return defined('FAA_VERSION') ? FAA_VERSION : '1.0.0';
    }
}

/**
 * Helper function to get asset URL
 * 
 * @param string $asset_path Relative path to the asset
 * @param string $base_url   Base URL for assets
 * @return string            Full URL to the asset
 */
function faa_get_asset_url($asset_path, $base_url = null)
{
    if ($base_url === null) {
        $base_url = plugin_dir_url(__FILE__) . '../assets/';
    }

    return FAA_Asset_Loader::get_asset_url($asset_path, $base_url);
}

/**
 * Helper function to get asset version
 * 
 * @param string $asset_path Relative path to the asset
 * @return string            Version string
 */
function faa_get_asset_version($asset_path)
{
    $plugin_base_path = plugin_dir_path(dirname(__FILE__));

    // Check if we should use minified version and if it exists
    $use_minified = !defined('WP_DEBUG') || !WP_DEBUG;

    if ($use_minified) {
        // Try minified version first
        $minified_path = FAA_Asset_Loader::get_minified_path($asset_path);
        $minified_file_path = $plugin_base_path . 'assets/' . $minified_path;

        if (file_exists($minified_file_path)) {
            return FAA_Asset_Loader::get_asset_version($minified_file_path);
        }
    }

    // Fallback to original file
    $file_path = $plugin_base_path . 'assets/' . $asset_path;
    return FAA_Asset_Loader::get_asset_version($file_path);
}
