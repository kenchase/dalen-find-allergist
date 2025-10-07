<?php

/**
 * Lock down WordPress Admin for Wild Apricot 'wa_' Role Users. 
 * Make sure they can only edit their own posts and prevent access to admin area.
 *
 * @package FAA
 * @since 1.0.0
 */

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Helper function to check if a user has a Wild Apricot role (starts with 'wa_').
 *
 * @param WP_User|null $user The user object to check. If null, gets current user.
 * @return bool True if user has a WA role, false otherwise.
 * @since 1.0.0
 */
function faa_user_has_wa_role($user = null)
{
    if ($user === null) {
        $user = wp_get_current_user();
    }

    // Validate user object
    if (!$user || !($user instanceof WP_User) || empty($user->roles)) {
        return false;
    }

    // Check if user has any role starting with "wa_"
    foreach ((array) $user->roles as $role) {
        if (strpos($role, 'wa_') === 0) {
            return true;
        }
    }

    return false;
}

/**
 * Filter posts in admin area for Wild Apricot users to show only their own posts.
 * This ensures they only see their own allergist profile post if they somehow access the admin area.
 * faa_redirect_wa_users_from_admin() should prevent this normally.
 *
 * @param WP_Query $query The WordPress query object.
 * @return void
 * @since 1.0.0
 */
function faa_filter_posts_for_wa_users($query)
{
    if (is_admin() && !wp_doing_ajax() && $query->is_main_query()) {
        $user = wp_get_current_user();

        // Only filter for WA users
        if (faa_user_has_wa_role($user)) {
            $query->set('author', get_current_user_id());
        }
    }
}
add_action('pre_get_posts', 'faa_filter_posts_for_wa_users');

/**
 * Prevent Wild Apricot users from editing posts they do not own.
 * This is a safety net in case they somehow access posts they do not own.
 *
 * @param array    $allcaps All capabilities of the user.
 * @param array    $caps    Required capabilities.
 * @param array    $args    Arguments passed to has_cap().
 * @param WP_User  $user    The user object.
 * @return array Modified capabilities array.
 * @since 1.0.0
 */
function faa_restrict_wa_users_to_own_posts($allcaps, $caps, $args, $user)
{
    // Only process for WA users
    if (!faa_user_has_wa_role($user) || !isset($args[2])) {
        return $allcaps;
    }

    $post_id = absint($args[2]);
    if ($post_id === 0) {
        return $allcaps;
    }

    $post = get_post($post_id);

    // Only deny if post exists AND belongs to someone else
    if ($post && intval($post->post_author) !== intval($user->ID)) {
        // Block all post-editing capabilities
        $allcaps['edit_post'] = false;
        $allcaps['delete_post'] = false;
        $allcaps['publish_post'] = false;
    }

    return $allcaps;
}
add_filter('user_has_cap', 'faa_restrict_wa_users_to_own_posts', 10, 4);

/**
 * Redirect Wild Apricot users from the admin area to their front-end profile edit page.
 * This prevents WA users from accessing the WordPress admin dashboard.
 *
 * @return void
 * @since 1.0.0
 */
function faa_redirect_wa_users_from_admin()
{
    // Check if we're in admin area and not doing an AJAX request
    if (!is_admin() || wp_doing_ajax()) {
        return;
    }

    $user = wp_get_current_user();

    // Only redirect WA users
    if (!faa_user_has_wa_role($user)) {
        return;
    }

    // Get the edit profile page slug from plugin settings
    $edit_profile_slug = get_option('fa_edit_profile_page_slug', 'my-account');
    
    // Sanitize the slug
    $edit_profile_slug = sanitize_title($edit_profile_slug);
    
    // Ensure we have a valid slug
    if (empty($edit_profile_slug)) {
        $edit_profile_slug = 'my-account';
    }

    /**
     * Filter the redirect URL for Wild Apricot users.
     *
     * @param string $redirect_url The URL to redirect to.
     * @param WP_User $user The current user object.
     * @since 1.0.0
     */
    $redirect_url = apply_filters(
        'faa_wa_user_redirect_url',
        home_url('/' . trailingslashit($edit_profile_slug)),
        $user
    );

    // Perform the redirect with proper sanitization
    wp_safe_redirect($redirect_url);
    exit;
}
add_action('admin_init', 'faa_redirect_wa_users_from_admin');

/**
 * Hide the admin bar for Wild Apricot users on the front-end.
 * WA users should have a streamlined front-end experience without admin access.
 *
 * @return void
 * @since 1.0.0
 */
function faa_hide_admin_bar_for_allergist_users()
{
    $user = wp_get_current_user();

    // Hide admin bar for WA users
    if (faa_user_has_wa_role($user)) {
        show_admin_bar(false);
    }
}
add_action('after_setup_theme', 'faa_hide_admin_bar_for_allergist_users');
