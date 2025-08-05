<?php
/*
* Custom WordPress role for Allergist
*/

/**
 * Register the 'Allergist' role with limited capabilities.
 */
function register_allergist_role()
{
    if (!get_role('allergist')) {
        add_role(
            'allergist',
            'Allergist',
            [
                'read' => true,
                'edit_physicians' => true,
                'edit_own_physicians' => true,
                'delete_physicians' => true,
                'publish_physicians' => true,
                'edit_others_physicians' => false,
                'delete_others_physicians' => false,
                'read_private_physicians' => false,
                'edit_physician' => true,
                'read_physician' => true,
                'delete_physician' => true,
            ]
        );
    }

    // Add capabilities to Administrator role (only if not already present)
    $admin = get_role('administrator');
    if ($admin) {
        $caps = [
            // Plural capabilities (for managing multiple posts)
            'edit_physicians',
            'edit_others_physicians',
            'publish_physicians',
            'read_private_physicians',
            'delete_physicians',
            'delete_others_physicians',
            // Singular capabilities (for individual post actions)
            'edit_physician',
            'read_physician',
            'delete_physician',
            // Additional capabilities for full management
            'edit_published_physicians',
            'delete_published_physicians',
            'create_physicians',
            // User management capabilities for allergist role
            'list_users',
            'edit_users',
            'delete_users',
            'create_users',
            'promote_users',
            // Additional user management capabilities
            'remove_users',
            'manage_options', // Often needed for user management
        ];
        foreach ($caps as $cap) {
            if (!$admin->has_cap($cap)) {
                $admin->add_cap($cap);
            }
        }
    }
}
add_action('init', 'register_allergist_role', 10);

/**
 * Allow administrators to edit users with allergist role
 */
function allow_admin_edit_allergist_users($caps, $cap, $user_id, $args)
{
    // Check if we're dealing with user editing capabilities
    if ($cap === 'edit_user' || $cap === 'delete_user' || $cap === 'promote_user') {
        // Get the current user (the one trying to perform the action)
        $current_user = wp_get_current_user();

        // If current user is administrator
        if (in_array('administrator', $current_user->roles)) {
            // If we have a target user ID
            if (isset($args[0])) {
                $target_user = get_userdata($args[0]);

                // If target user has allergist role, allow the action
                if ($target_user && in_array('allergist', $target_user->roles)) {
                    return array('exist'); // Grant permission
                }
            }
        }
    }

    return $caps; // Return original capabilities
}
add_filter('map_meta_cap', 'allow_admin_edit_allergist_users', 10, 4);

/**
 * Remove the 'Allergist' role on plugin deactivation.
 */
function remove_allergist_role()
{
    remove_role('allergist');
}
register_deactivation_hook(__FILE__, 'remove_allergist_role');
