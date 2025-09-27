<?php

/**
 * ACF Form Shortcode Class
 *
 * @package Dalen_Find_Allergist
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ACF_Form_Shortcode extends Find_Allergist_Shortcode_Base
{

    /**
     * Initialize the shortcode
     */
    protected function init()
    {
        add_shortcode('acf-form', [$this, 'render']);
    }

    /**
     * Render the shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render($atts = [])
    {
        // Check if ACF is available and acf_form function exists
        if (!function_exists('acf_form')) {
            return '<div class="acf-form-error">Error: Advanced Custom Fields plugin is not active or acf_form() function is not available.</div>';
        }

        // Start output buffering
        $this->start_output_buffer();

        // Set default attributes
        $atts = shortcode_atts([
            'post_id'       => 14859,
            'post_title'    => false,
            'post_content'  => false,
            'submit_value'  => __('Update meta')
        ], $atts, 'acf-form');
        // Call ACF form function
        acf_form_head();
        acf_form($atts);

        return $this->get_output_buffer();
    }
}
