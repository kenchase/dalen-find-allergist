<?php

/**
 * ACF Form Shortcode Class
 *
 * @package FAA
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class FAA_Profile_Editor_Shortcode extends FAA_Shortcode_Base
{

    /**
     * Initialize the shortcode
     */
    protected function init()
    {
        add_shortcode('faa-profile-editor', [$this, 'render']);
    }

    /**
     * Render the shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render(array $atts = []): string
    {
        // Check if ACF is available and acf_form function exists
        if (!function_exists('acf_form')) {
            return '<div class="acf-form-error">' . __('Error: Advanced Custom Fields plugin is not active or acf_form() function is not available.', FAA_TEXT_DOMAIN) . '</div>';
        }

        // Summary:
        // Only show the ACF profile form if the user is logged and is assigned as the author of a 'physicians' post.
        // To prevent duplicate entries, we will get the first 'physicians' post assigned to the current user and use that post ID in the ACF form.

        // Get current user ID
        $user_ID = get_current_user_id();
        if ($user_ID === 0) {
            // The user is not logged in.
            return '';
        } else {
            // The user is logged in, and their ID is available.

            // Check if user has a role that begins with "wa_"
            $current_user = wp_get_current_user();
            $user_roles = $current_user->roles;
            $has_wa_role = false;

            foreach ($user_roles as $role) {
                if (strpos($role, 'wa_') === 0) {
                    $has_wa_role = true;
                    break;
                }
            }

            if (!$has_wa_role) {
                return '';
            }

            // Get the first post ID associated with the user
            $user_posts = get_posts([
                'author'        => $user_ID,
                'post_type'     => 'physicians',
                'numberposts'   => 1,
                'post_status'   => ['publish', 'draft', 'pending'],
                'orderby'       => 'date',
                'order'         => 'DESC'
            ]);
            if (!empty($user_posts)) {
                $post_id = $user_posts[0]->ID;
            } else {
                $post_id = null;
            }
        }

        // If no post found, return error message
        if ($post_id === null) {
            return '<div class="acf-form-error">' . __('No profile found for the current user.', FAA_TEXT_DOMAIN) . '</div>';
        }

        // Start output buffering
        $this->start_output_buffer();

        // Set default attributes
        $atts = shortcode_atts([
            'post_id'   => $post_id,
            'post_title'    => true,
            'post_content'  => false,
            'submit_value'  => __('Update Profile', FAA_TEXT_DOMAIN),
        ], $atts, 'faa-profile-editor');


        // Call ACF form functions if the current user can edit the post
        // Check both capability AND post authorship as a fallback
        $post_author = get_post_field('post_author', $post_id);
        $can_edit_post = current_user_can('edit_post', $post_id);
        $is_post_author = ($user_ID === (int) $post_author);

        // Allow if user can edit the post OR is the post author
        // Apply filter to allow customization of permission check
        $can_edit = apply_filters('faa_profile_editor_can_edit', ($can_edit_post || $is_post_author), $user_ID, $post_id);

        if ($can_edit) {
            acf_form_head();
            $user_full_name = trim($current_user->first_name . ' ' . $current_user->last_name);
            if (empty($user_full_name)) {
                $user_full_name = $current_user->display_name;
            }
            echo '<h1>' . esc_html($user_full_name) . esc_html__(' - Find an Allergist profile', FAA_TEXT_DOMAIN) . '</h1>';
            acf_form($atts);
        } else {
            echo '<div class="acf-form-error">' . esc_html__('You do not have permission to edit this profile.', FAA_TEXT_DOMAIN) . '</div>';
        }

        return $this->get_output_buffer();
    }
}
