<?php

/**
 * Asset loader utility for Find an Allergist
 * Automatically loads minified assets in production
 * 
 * This class provides secure asset loading with:
 * - Automatic minified asset selection in production mode
 * - Path traversal attack prevention
 * - Input validation and sanitization
 * - Graceful fallback to non-minified assets
 * - Cache busting via file modification time
 * 
 * @package FindAnAllergist
 * @since 1.0.0
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
        // Validate and sanitize asset path to prevent path traversal
        $asset_path = self::sanitize_asset_path($asset_path);
        if (empty($asset_path)) {
            return $base_url;
        }

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
     * Sanitize asset path to prevent path traversal attacks
     * 
     * @param string $path The path to sanitize
     * @return string      Sanitized path or empty string if invalid
     */
    private static function sanitize_asset_path($path)
    {
        if (!is_string($path) || empty($path)) {
            return '';
        }

        // Remove any null bytes
        $path = str_replace(chr(0), '', $path);

        // Prevent path traversal
        if (strpos($path, '..') !== false) {
            return '';
        }

        // Remove leading slashes
        $path = ltrim($path, '/\\');

        // Only allow alphanumeric, dash, underscore, dot, and forward slash
        if (!preg_match('/^[a-zA-Z0-9\-_\.\/]+$/', $path)) {
            return '';
        }

        return $path;
    }

    /**
     * Convert a regular asset path to its minified equivalent
     * 
     * @param string $path Original asset path
     * @return string      Minified asset path
     */
    public static function get_minified_path($path)
    {
        if (empty($path)) {
            return '';
        }

        $path_info = pathinfo($path);
        $dir = isset($path_info['dirname']) ? $path_info['dirname'] : '';
        $filename = isset($path_info['filename']) ? $path_info['filename'] : '';
        $extension = isset($path_info['extension']) ? $path_info['extension'] : '';

        // If no extension found, return original path
        if (empty($extension)) {
            return $path;
        }

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
        if (!is_string($url) || empty($url)) {
            return '';
        }

        // Get the plugin base URL (one level up from includes directory)
        $plugin_base_url = plugin_dir_url(dirname(__FILE__));
        $plugin_base_path = plugin_dir_path(dirname(__FILE__));

        // Handle plugin assets (most common case for this plugin)
        if (strpos($url, $plugin_base_url) === 0) {
            $relative_path = str_replace($plugin_base_url, '', $url);
            return $plugin_base_path . $relative_path;
        }

        // Fallback: check upload directory (less common for plugin assets)
        $upload_dir = wp_upload_dir();
        if (isset($upload_dir['baseurl']) && isset($upload_dir['basedir'])) {
            $base_url = $upload_dir['baseurl'];
            $base_dir = $upload_dir['basedir'];

            if (strpos($url, $base_url) === 0) {
                $relative_path = str_replace($base_url, '', $url);
                return $base_dir . $relative_path;
            }
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
        if (!empty($file_path) && file_exists($file_path)) {
            $mtime = @filemtime($file_path);
            if ($mtime !== false) {
                return (string) $mtime;
            }
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
    if (!class_exists('FAA_Asset_Loader')) {
        return '';
    }

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
    if (!class_exists('FAA_Asset_Loader')) {
        return '1.0.0';
    }

    $plugin_base_path = plugin_dir_path(dirname(__FILE__));

    // Check if we should use minified version and if it exists
    $use_minified = !defined('WP_DEBUG') || !WP_DEBUG;

    if ($use_minified) {
        // Try minified version first
        $minified_path = FAA_Asset_Loader::get_minified_path($asset_path);
        if (!empty($minified_path)) {
            $minified_file_path = $plugin_base_path . 'assets/' . $minified_path;

            if (file_exists($minified_file_path)) {
                return FAA_Asset_Loader::get_asset_version($minified_file_path);
            }
        }
    }

    // Fallback to original file
    $file_path = $plugin_base_path . 'assets/' . $asset_path;
    return FAA_Asset_Loader::get_asset_version($file_path);
}
