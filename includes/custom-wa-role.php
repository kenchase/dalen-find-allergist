<?php

/**
 * Lock down WordPress Admin for Wild Apricot 'wa_' Role Users. 
 * Make sure they can only edit their own posts and prevent access to admin area.
 *
 * @package FAA
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
    // Only process for wa_ users
    $has_wa_role = false;
    foreach ((array) $user->roles as $role) {
        if (strpos($role, 'wa_') === 0) {
            $has_wa_role = true;
            break;
        }
    }

    if (!$has_wa_role || !isset($args[2])) {
        return $allcaps;
    }

    $post_id = $args[2];
    $post = get_post($post_id);

    // Only deny if post exists AND belongs to someone else
    if ($post && intval($post->post_author) !== intval($user->ID)) {
        // Block all post-editing capabilities
        $allcaps['edit_post'] = false;
        $allcaps['delete_post'] = false;
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
