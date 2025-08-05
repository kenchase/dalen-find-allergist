<?php

/**
 * Login redirect functionality for allergist users
 */

/**
 * Redirect allergist users to a specific page after login
 * 
 * @param string $redirect_to The redirect destination URL.
 * @param string $request The requested redirect destination URL passed as a parameter.
 * @param WP_User|WP_Error $user WP_User object on success, WP_Error object on failure.
 * @return string The redirect URL.
 */
function allergist_login_redirect($redirect_to, $request, $user)
{
    // Check if login was successful and user object exists
    if (!is_wp_error($user) && is_object($user) && is_a($user, 'WP_User')) {
        // Check if user has the 'allergist' role
        if (in_array('allergist', $user->roles)) {
            // Redirect allergist users to the physicians post type admin page
            return admin_url('edit.php?post_type=physicians');
        }
    }

    // For all other users, use the default redirect
    return $redirect_to;
}
add_filter('login_redirect', 'allergist_login_redirect', 1, 3);

/**
 * WooCommerce login redirect for allergist users
 * WooCommerce uses its own login redirect system that can override WordPress default
 */
function allergist_woocommerce_login_redirect($redirect, $user)
{
    // Check if user object exists and has the 'allergist' role
    if (is_object($user) && in_array('allergist', $user->roles)) {
        // Redirect allergist users to the physicians post type admin page
        return admin_url('edit.php?post_type=physicians');
    }

    // For all other users, use the default redirect
    return $redirect;
}
add_filter('woocommerce_login_redirect', 'allergist_woocommerce_login_redirect', 1, 2);
