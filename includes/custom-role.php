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
                'delete_own_physicians' => true,
                'publish_physicians' => true,
                'edit_others_physicians' => false,
                'delete_others_physicians' => false,
                'read_private_physicians' => false,
                'edit_physician' => true,
                'read_physician' => true,
                'delete_physician' => true,
                'create_physicians' => true,
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
 * Restrict allergist users to only see their own physicians posts
 */
function restrict_allergist_posts_query($query)
{
    global $pagenow;

    // Only apply to admin area and main query
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    // Check if current user is allergist
    $current_user = wp_get_current_user();
    if (!in_array('allergist', $current_user->roles)) {
        return;
    }

    // Only apply to physicians post type queries
    if ($pagenow == 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] == 'physicians') {
        $query->set('author', $current_user->ID);
    }
}
add_action('pre_get_posts', 'restrict_allergist_posts_query');

/**
 * Hide admin menu items from allergist users
 */
function hide_admin_menus_from_allergist()
{
    $current_user = wp_get_current_user();

    // Only apply to allergist users
    if (!in_array('allergist', $current_user->roles)) {
        return;
    }

    global $menu, $submenu;

    // Get all menu items
    foreach ($menu as $key => $menu_item) {
        // Skip if menu item is empty
        if (empty($menu_item[2])) {
            continue;
        }

        $menu_slug = $menu_item[2];

        // Keep only physicians post type menu and user profile
        if (
            $menu_slug !== 'edit.php?post_type=physicians' &&
            $menu_slug !== 'profile.php' &&
            $menu_slug !== 'users.php' // Keep users menu but we'll filter it in submenu
        ) {
            remove_menu_page($menu_slug);
        }
    }

    // Also remove users menu since allergists shouldn't manage users
    remove_menu_page('users.php');

    // Remove any remaining submenus that might bypass the main menu removal
    if (isset($submenu['edit.php?post_type=physicians'])) {
        foreach ($submenu['edit.php?post_type=physicians'] as $key => $submenu_item) {
            // Keep only the main physicians list and add new (if they don't have one yet)
            if (
                $submenu_item[2] !== 'edit.php?post_type=physicians' &&
                $submenu_item[2] !== 'post-new.php?post_type=physicians'
            ) {
                unset($submenu['edit.php?post_type=physicians'][$key]);
            }
        }
    }

    // Also check if user already has a physicians post and remove "Add New" submenu
    $existing_posts = get_posts(array(
        'post_type' => 'physicians',
        'post_status' => array('publish', 'draft', 'pending'),
        'author' => $current_user->ID,
        'numberposts' => 1
    ));

    if (!empty($existing_posts)) {
        // Remove the "Add New" submenu item if they already have a post
        remove_submenu_page('edit.php?post_type=physicians', 'post-new.php?post_type=physicians');
    }
}
add_action('admin_menu', 'hide_admin_menus_from_allergist', 999);

/**
 * Hide "Add New" button if allergist already has a physicians post
 */
function hide_add_new_for_allergist()
{
    $current_user = wp_get_current_user();

    // Only apply to allergist users
    if (!in_array('allergist', $current_user->roles)) {
        return;
    }

    global $pagenow;

    // Only on physicians listing page
    if ($pagenow == 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] == 'physicians') {
        // Check if user already has a physicians post
        $existing_posts = get_posts(array(
            'post_type' => 'physicians',
            'post_status' => array('publish', 'draft', 'pending'),
            'author' => $current_user->ID,
            'numberposts' => 1
        ));

        if (!empty($existing_posts)) {
            // Hide the "Add New" button with more comprehensive CSS
            echo '<style>
                .page-title-action,
                .add-new-h2,
                #favorite-actions,
                .tablenav .alignleft .button,
                .wrap .page-title-action,
                .wrap h1 .page-title-action,
                a.page-title-action,
                .wp-heading-inline + .page-title-action,
                .subsubsub .current + li a,
                input[value="Add New"] {
                    display: none !important;
                }
                
                /* Also hide any "Add New" links in submenus */
                #adminmenu .wp-submenu a[href*="post-new.php?post_type=physicians"] {
                    display: none !important;
                }
            </style>';
        }
    }
}
add_action('admin_head', 'hide_add_new_for_allergist');

/**
 * Comprehensive system to restrict allergist users to one physicians post
 */

/**
 * Check if allergist user can create/publish physicians posts
 */
function can_allergist_create_physicians_post($user_id = null)
{
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    $user = get_userdata($user_id);
    if (!$user || !in_array('allergist', $user->roles)) {
        return true; // Not an allergist, no restriction
    }

    // Check if user already has any physicians post
    $existing_posts = get_posts(array(
        'post_type' => 'physicians',
        'post_status' => array('publish', 'draft', 'pending', 'future', 'private'),
        'author' => $user_id,
        'numberposts' => 1,
        'fields' => 'ids'
    ));

    return empty($existing_posts);
}

/**
 * Block creation of new physicians posts if allergist already has one
 */
function restrict_allergist_physicians_creation($post_data, $postarr)
{
    // Only apply to physicians post type
    if ($post_data['post_type'] !== 'physicians') {
        return $post_data;
    }

    // Skip if this is an update to existing post
    if (!empty($postarr['ID'])) {
        return $post_data;
    }

    $current_user = wp_get_current_user();

    // Only apply to allergist users
    if (!in_array('allergist', $current_user->roles)) {
        return $post_data;
    }

    // Check if they can create a physicians post
    if (!can_allergist_create_physicians_post($current_user->ID)) {
        // Prevent the post from being created by setting an invalid post type
        wp_die(
            __('You can only create one physician profile. Please edit your existing profile instead.'),
            __('Permission Denied'),
            array('response' => 403, 'back_link' => true)
        );
    }

    return $post_data;
}
add_filter('wp_insert_post_data', 'restrict_allergist_physicians_creation', 10, 2);

/**
 * Prevent duplicate posts via capability check
 */
function restrict_allergist_create_posts_capability($allcaps, $caps, $args, $user)
{
    // Check if we're dealing with create_posts capability for physicians
    if (in_array('create_physicians', $caps) && isset($user->roles) && in_array('allergist', $user->roles)) {
        // If they already have a physicians post, remove the create capability
        if (!can_allergist_create_physicians_post($user->ID)) {
            $allcaps['create_physicians'] = false;
        }
    }

    return $allcaps;
}
add_filter('user_has_cap', 'restrict_allergist_create_posts_capability', 10, 4);

/**
 * Validate post before saving to prevent duplicates
 */
function validate_allergist_physicians_post($post_id, $post, $update)
{
    // Only apply to physicians post type
    if ($post->post_type !== 'physicians') {
        return;
    }

    // Skip auto-saves and revisions
    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
        return;
    }

    $author = get_userdata($post->post_author);

    // Only apply to allergist users
    if (!$author || !in_array('allergist', $author->roles)) {
        return;
    }

    // If this is a new post (not an update)
    if (!$update) {
        // Check if author already has another physicians post
        $existing_posts = get_posts(array(
            'post_type' => 'physicians',
            'post_status' => array('publish', 'draft', 'pending', 'future', 'private'),
            'author' => $post->post_author,
            'exclude' => array($post_id),
            'numberposts' => 1,
            'fields' => 'ids'
        ));

        if (!empty($existing_posts)) {
            // Delete this duplicate post
            wp_delete_post($post_id, true);

            // Redirect with error message
            wp_redirect(add_query_arg(
                array(
                    'post_type' => 'physicians',
                    'error' => 'duplicate_post'
                ),
                admin_url('edit.php')
            ));
            exit;
        }
    }
}
add_action('save_post', 'validate_allergist_physicians_post', 10, 3);

/**
 * Display error message for duplicate post attempts
 */
function display_allergist_duplicate_post_error()
{
    if (isset($_GET['error']) && $_GET['error'] === 'duplicate_post') {
        echo '<div class="notice notice-error is-dismissible">
            <p><strong>Error:</strong> You can only create one physician profile. Please edit your existing profile instead.</p>
        </div>';
    }
}
add_action('admin_notices', 'display_allergist_duplicate_post_error');

/**
 * Remove the old, less robust restriction functions
 */
function restrict_allergist_post_access()
{
    global $pagenow, $post;

    $current_user = wp_get_current_user();

    // Only apply to allergist users
    if (!in_array('allergist', $current_user->roles)) {
        return;
    }

    // Check if we're on post edit page
    if ($pagenow == 'post.php' && isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['post'])) {
        $post_id = intval($_GET['post']);
        $post_obj = get_post($post_id);

        // If it's a physicians post and not authored by current user, deny access
        if ($post_obj && $post_obj->post_type == 'physicians' && $post_obj->post_author != $current_user->ID) {
            wp_die(__('You do not have permission to edit this post.'));
        }
    }

    // Check if trying to create new post when they already have one published
    if ($pagenow == 'post-new.php' && isset($_GET['post_type']) && $_GET['post_type'] == 'physicians') {
        // Check if user already has a published physicians post
        $existing_posts = get_posts(array(
            'post_type' => 'physicians',
            'post_status' => array('publish', 'draft', 'pending'),
            'author' => $current_user->ID,
            'numberposts' => 1
        ));

        if (!empty($existing_posts)) {
            wp_die(__('You can only create one physician profile. Please edit your existing profile instead.'));
        }
    }
}
add_action('admin_init', 'restrict_allergist_post_access');

/**
 * Hide admin bar items from allergist users
 */
function hide_admin_bar_items_from_allergist()
{
    $current_user = wp_get_current_user();

    // Only apply to allergist users
    if (!in_array('allergist', $current_user->roles)) {
        return;
    }

    global $wp_admin_bar;

    // Remove various admin bar items
    $wp_admin_bar->remove_node('wp-logo');
    $wp_admin_bar->remove_node('about');
    $wp_admin_bar->remove_node('wporg');
    $wp_admin_bar->remove_node('documentation');
    $wp_admin_bar->remove_node('support-forums');
    $wp_admin_bar->remove_node('feedback');
    $wp_admin_bar->remove_node('site-name');
    $wp_admin_bar->remove_node('view-site');
    $wp_admin_bar->remove_node('updates');
    $wp_admin_bar->remove_node('comments');
    $wp_admin_bar->remove_node('new-content');
    $wp_admin_bar->remove_node('edit');
}
add_action('wp_before_admin_bar_render', 'hide_admin_bar_items_from_allergist', 999);

/**
 * Redirect allergist users to physicians list on login
 */
function redirect_allergist_after_login($redirect_to, $request, $user)
{
    // Check if user has allergist role
    if (isset($user->roles) && is_array($user->roles) && in_array('allergist', $user->roles)) {
        return admin_url('edit.php?post_type=physicians');
    }

    return $redirect_to;
}
add_filter('login_redirect', 'redirect_allergist_after_login', 10, 3);

/**
 * Remove the 'Allergist' role on plugin deactivation.
 */
function remove_allergist_role()
{
    remove_role('allergist');
}
register_deactivation_hook(__FILE__, 'remove_allergist_role');
