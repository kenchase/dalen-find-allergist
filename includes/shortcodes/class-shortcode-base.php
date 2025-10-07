<?php

/**
 * Base class for all Find Allergist shortcodes
 *
 * @package FAA
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

abstract class FAA_Shortcode_Base
{

    /**
     * Plugin directory URL
     *
     * @var string
     */
    protected $plugin_url;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->plugin_url = plugin_dir_url(dirname(__FILE__));
        $this->init();
    }

    /**
     * Initialize the shortcode
     */
    abstract protected function init();

    /**
     * Render the shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    abstract public function render($atts = []);

    /**
     * Get Google Maps API key from admin settings
     *
     * @return string
     */
    protected function get_google_maps_api_key()
    {
        return faa_get_google_maps_api_key();
    }

    /**
     * Enqueue Google Maps API
     *
     * @return bool True if enqueued, false otherwise
     */
    protected function enqueue_google_maps_api()
    {
        $api_key = $this->get_google_maps_api_key();

        if (!empty($api_key)) {
            wp_enqueue_script(
                'google-maps-api',
                'https://maps.google.com/maps/api/js?key=' . esc_attr($api_key),
                array(),
                null,
                true
            );
            return true;
        }

        return false;
    }

    /**
     * Enqueue the main CSS file
     */
    protected function enqueue_main_css()
    {
        $asset_base_url = $this->plugin_url . '../assets/';
        wp_enqueue_style(
            'find-allergist-css',
            faa_get_asset_url('css/find-allergist.css', $asset_base_url),
            array(),
            faa_get_asset_version('css/find-allergist.css')
        );
    }

    /**
     * Start output buffering
     */
    protected function start_output_buffer()
    {
        ob_start();
    }

    /**
     * Get buffered content and clean
     *
     * @return string
     */
    protected function get_output_buffer()
    {
        return ob_get_clean();
    }

    /**
     * Escape HTML for output
     *
     * @param string $text
     * @return string
     */
    protected function esc_html($text)
    {
        return esc_html($text);
    }

    /**
     * Escape URL for output
     *
     * @param string $url
     * @return string
     */
    protected function esc_url($url)
    {
        return esc_url($url);
    }

    /**
     * Escape attribute for output
     *
     * @param string $attr
     * @return string
     */
    protected function esc_attr($attr)
    {
        return esc_attr($attr);
    }

    /**
     * Escape JavaScript for output
     *
     * @param string $js
     * @return string
     */
    protected function esc_js($js)
    {
        return esc_js($js);
    }
}
