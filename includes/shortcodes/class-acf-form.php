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

class Find_Allergist_ACF_Form_Shortcode extends Find_Allergist_Shortcode_Base
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
            return '<div class="acf-form-error">' . __('Error: Advanced Custom Fields plugin is not active or acf_form() function is not available.', 'dalen-find-allergist') . '</div>';
        }

        // Summary:
        // Only show the ACF profile form if the user is logged and is assigned as the author of a 'physicians' post.
        // To prevent duplicate entries, we will get the first 'physicians' post assigned to the current user and use that post ID in the ACF form.

        // Get current user ID
        $user_ID = get_current_user_id();
        if ($user_ID == 0) {
            // The user is not logged in.
            return '';
        } else {
            // The user is logged in, and their ID is available.
            // Get the first post ID associated with the user

            // Get current user
            $current_user = wp_get_current_user();

            $user_posts = get_posts([
                'author'        => $user_ID,
                'post_type'     => 'physicians',
                'numberposts'   => 1,
                'post_status'   => ['publish', 'draft', 'pending']
            ]);
            if (!empty($user_posts)) {
                $post_id = $user_posts[0]->ID;
            } else {
                return '<div class="acf-form-error">' . __('No profile found for the current user.', 'dalen-find-allergist') . '</div>';
            }
        }

        // Start output buffering
        $this->start_output_buffer();

        // Set default attributes
        $atts = shortcode_atts([
            'post_id'   => $post_id,
            'post_title'    => false,
            'post_content'  => false,
            'submit_value'  => __('Update Profile', 'dalen-find-allergist'),
        ], $atts, 'acf-form');

        // Call ACF form functions if the current user can edit the post
        if (current_user_can('edit_post', $post_id)) {
            acf_form_head();
            acf_form($atts);
        } else {
            echo '<div class="acf-form-error">' . __('You do not have permission to edit this profile.', 'dalen-find-allergist') . '</div>';
        }

        return $this->get_output_buffer();
    }
}
