<?php

/**
 * Lock down WordPress Admin for Wild Apricot 'wa_' Role Users. 
 * Make sure they can only edit their own posts and prevent access to admin area.
 *
 * @package Dalen_Find_Allergist
 * 
 */

// if the user has a wa_ role, filter the posts they see in the admin to only those they authored
// This ensures they only see their own allergist profile post if they somehow access the admin area 
// faa_redirect_wa_users_from_admin() should prevent this normally

function faa_filter_posts_for_wa_users($query)
{
    if (is_admin() && !wp_doing_ajax() && $query->is_main_query()) {
        $user = wp_get_current_user();

        // Check if user has a role starting with "wa_"
        foreach ((array) $user->roles as $role) {
            if (strpos($role, 'wa_') === 0) {
                $query->set('author', get_current_user_id());
                break;
            }
        }
    }
}
add_action('pre_get_posts', 'faa_filter_posts_for_wa_users');


// Prevent wa users from editing posts they do not own
// This is a safety net in case they somehow access posts they do not own

function faa_restrict_wa_users_to_own_posts($allcaps, $caps, $args, $user)
{
    // Check if user has a role starting with "wa_"
    $has_wa_role = false;
    foreach ((array) $user->roles as $role) {
        if (strpos($role, 'wa_') === 0) {
            $has_wa_role = true;
            break;
        }
    }

    if (!$has_wa_role) {
        return $allcaps;
    }

    // If checking a specific post capability
    if (isset($args[2])) {
        $post_id = $args[2];
        $post = get_post($post_id);

        // If the post exists and doesn't belong to this user, deny access
        if ($post && $post->post_author != $user->ID) {
            // Remove all edit/delete capabilities for posts they don't own
            $allcaps['edit_post'] = false;
            $allcaps['delete_post'] = false;
            $allcaps['edit_posts'] = false;
            $allcaps['delete_posts'] = false;
            $allcaps['publish_posts'] = false;
            $allcaps['edit_published_posts'] = false;
            $allcaps['delete_published_posts'] = false;
        }
    }

    return $allcaps;
}
add_filter('user_has_cap', 'faa_restrict_wa_users_to_own_posts', 10, 4);


// Redirect for users with the Wild Apricot 'wa_' role to a front-end page where they can edit their profile.

function faa_redirect_wa_users_from_admin()
{
    // Check if we're in admin area and not doing an AJAX request
    if (is_admin() && ! wp_doing_ajax()) {
        $user = wp_get_current_user();

        // Check if user has any role starting with "wa_"
        foreach ((array) $user->roles as $role) {
            if (strpos($role, 'wa_') === 0) {
                // Get the edit profile page slug from plugin settings
                $edit_profile_slug = get_option('fa_edit_profile_page_slug', 'my-account');

                // Redirect to the front-end edit page
                wp_redirect(home_url('/' . $edit_profile_slug . '/'));
                exit;
            }
        }
    }
}
add_action('admin_init', 'faa_redirect_wa_users_from_admin');


// Hide the admin bar for users with Wild Apricot 'wa_' role

function faa_hide_admin_bar_for_allergist_users()
{
    $user = wp_get_current_user();

    // Check if user has any role starting with "wa_"
    foreach ((array) $user->roles as $role) {
        if (strpos($role, 'wa_') === 0) {
            show_admin_bar(false);
            break;
        }
    }
}
add_action('after_setup_theme', 'faa_hide_admin_bar_for_allergist_users');
