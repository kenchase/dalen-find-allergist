<?php

/**
 * Prevent direct access to wp-admin pages for users with Wild Apricot 'wa_' role.
 * This will redirect them to a front-end page where they can edit their profile.
 *
 * @package Dalen_Find_Allergist
 * 
 */

function redirect_wa_users_from_admin()
{
    // Check if we're in admin area and not doing an AJAX request
    if (is_admin() && ! wp_doing_ajax()) {
        $user = wp_get_current_user();

        // Check if user has any role starting with "wa_"
        foreach ((array) $user->roles as $role) {
            if (strpos($role, 'wa_') === 0) {
                // Redirect to the front-end edit page
                wp_redirect(home_url('/my-account-wa/'));
                exit;
            }
        }
    }
}
add_action('admin_init', 'redirect_wa_users_from_admin');
