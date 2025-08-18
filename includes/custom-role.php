<?php

/**
 * Ensure wa_level_* users can access physicians edit page
 */
function dalen_wa_level_physicians_admin_access($allcaps, $caps, $args, $user)
{
    if (!is_admin()) return $allcaps;
    if (empty($user->roles)) return $allcaps;

    // Check if this is a physicians-related page
    $is_physicians_page = false;
    if (isset($_GET['post_type']) && $_GET['post_type'] === 'physicians') {
        $is_physicians_page = true;
    }
    // Also check for edit.php page with physicians post type
    global $pagenow;
    if ($pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'physicians') {
        $is_physicians_page = true;
    }
    // Check for post.php editing individual physicians posts
    if ($pagenow === 'post.php' && isset($_GET['post'])) {
        $post_id = intval($_GET['post']);
        $post = get_post($post_id);
        if ($post && $post->post_type === 'physicians') {
            $is_physicians_page = true;
        }
    }

    if (!$is_physicians_page) return $allcaps;

    foreach ($user->roles as $role) {
        if (strpos($role, 'wa_level_') === 0) {
            // Grant necessary capabilities for accessing the physicians edit page
            $allcaps['edit_physicians'] = true;
            $allcaps['edit_posts'] = true;  // Core capability for admin edit page access
            $allcaps['read'] = true;
            $allcaps['publish_posts'] = true;  // General publish capability
            $allcaps['publish_physicians'] = true;

            // If editing a specific post, check ownership and grant edit capabilities
            if (isset($post) && $post->post_author == $user->ID) {
                $allcaps['edit_post'] = true;
                $allcaps['edit_physician'] = true;
                $allcaps['edit_own_physicians'] = true;
                $allcaps['edit_published_physicians'] = true;
            }
            break;
        }
    }
    return $allcaps;
}
add_filter('user_has_cap', 'dalen_wa_level_physicians_admin_access', 9, 4);

/**
 * Ensure wa_level_* users always have minimum required capabilities
 * This is a backup to ensure access even if roles are not properly set up
 */
function dalen_wa_level_minimum_caps($allcaps, $caps, $args, $user)
{
    if (empty($user->roles)) return $allcaps;

    foreach ($user->roles as $role) {
        if (strpos($role, 'wa_level_') === 0) {
            // Ensure minimum capabilities are always present
            $allcaps['read'] = true;
            $allcaps['edit_posts'] = true;
            $allcaps['publish_posts'] = true;
            $allcaps['edit_physicians'] = true;
            $allcaps['publish_physicians'] = true;

            // Handle specific capability checks for physicians posts
            if (!empty($caps)) {
                foreach ($caps as $cap) {
                    // If checking for edit_post capability and we have context
                    if ($cap === 'edit_post' && !empty($args) && isset($args[0])) {
                        $post_id = $args[0];
                        $post = get_post($post_id);
                        if ($post && $post->post_type === 'physicians' && $post->post_author == $user->ID) {
                            $allcaps['edit_post'] = true;
                            $allcaps['edit_published_physicians'] = true;
                        }
                    }
                    // Handle physician-specific capabilities
                    if (in_array($cap, ['edit_physician', 'edit_physicians', 'edit_own_physicians', 'publish_physicians', 'edit_published_physicians'])) {
                        $allcaps[$cap] = true;
                    }
                }
            }
            break;
        }
    }
    return $allcaps;
}
add_filter('user_has_cap', 'dalen_wa_level_minimum_caps', 8, 4);

/**
 * Map meta capabilities for wa_level users editing physicians posts
 */
function dalen_map_physicians_meta_cap($caps, $cap, $user_id, $args)
{
    $user = get_userdata($user_id);
    if (!$user) return $caps;

    $is_wa_user = false;
    foreach ($user->roles as $role) {
        if (strpos($role, 'wa_level_') === 0) {
            $is_wa_user = true;
            break;
        }
    }
    if (!$is_wa_user) return $caps;

    // Handle edit_post capability for physicians posts
    if ($cap === 'edit_post' && !empty($args)) {
        $post_id = $args[0];
        $post = get_post($post_id);

        if ($post && $post->post_type === 'physicians') {
            // If the post belongs to the current user, allow editing
            if ($post->post_author == $user_id) {
                // Return the minimal capability they need
                return ['edit_physicians'];
            } else {
                // If not their post, deny access
                return ['do_not_allow'];
            }
        }
    }

    // Handle published post editing
    if ($cap === 'edit_published_post' && !empty($args)) {
        $post_id = $args[0];
        $post = get_post($post_id);

        if ($post && $post->post_type === 'physicians' && $post->post_author == $user_id) {
            return ['edit_physicians'];
        }
    }

    // Handle other physicians-related capabilities
    if (in_array($cap, ['edit_physician', 'read_physician', 'delete_physician'])) {
        if (!empty($args)) {
            $post_id = $args[0];
            $post = get_post($post_id);

            if ($post && $post->post_type === 'physicians' && $post->post_author == $user_id) {
                return ['edit_physicians'];
            }
        }
    }

    return $caps;
}
add_filter('map_meta_cap', 'dalen_map_physicians_meta_cap', 10, 4);

/*
* Custom WordPress role for Wild Apricot users (roles that begin with "wa_level_")
* Note that these roles are dynamically created based on the Wild Apricot membership levels.
* This is part of Wild Apricot SSO integration with WordPress.
*
* The custom capabilities are to allow Wild Apricot users to manage their own "Allergist" content within WordPress
* They can create, edit, and delete their own content, but cannot modify content created by others.
*
* Notes:
* Although referred to as Allergists, the custom post type is actually called "Physicians" in the codebase.
* Ideally, the terminology should be consistent throughout the codebase to avoid confusion, but this is a legacy issue.
*/

function dalen_find_allergist_add_caps_to_wa_roles()
{
    $allergist_caps = [
        'read',
        'edit_posts',  // Core capability needed to access admin edit pages
        'edit_physicians',
        'edit_own_physicians',
        'delete_own_physicians',
        'publish_physicians',
        'edit_others_physicians',
        'delete_others_physicians',
        'read_private_physicians',
        'edit_physician',
        'read_physician',
        'delete_physician',
        'create_physicians',
        'edit_published_physicians',  // Important for editing published posts
        'publish_posts',  // General publish capability
    ];
    global $wp_roles;
    if (! isset($wp_roles)) {
        $wp_roles = wp_roles();
    }
    foreach ($wp_roles->roles as $role_key => $role) {
        if (strpos($role_key, 'wa_level_') === 0) {
            $role_obj = get_role($role_key);
            if ($role_obj) {
                foreach ($allergist_caps as $cap) {
                    $role_obj->add_cap($cap, true);
                }
            }
        }
    }
    // Add capabilities to Administrator role (only if not already present)
    $admin = get_role('administrator');
    if ($admin) {
        $caps = [
            'edit_physicians',
            'edit_others_physicians',
            'publish_physicians',
            'read_private_physicians',
            'delete_physicians',
            'delete_others_physicians',
            'edit_physician',
            'read_physician',
            'delete_physician',
            'edit_published_physicians',
            'delete_published_physicians',
            'create_physicians',
            'list_users',
            'edit_users',
            'delete_users',
            'create_users',
            'promote_users',
            'remove_users',
            'manage_options',
        ];
        foreach ($caps as $cap) {
            if (!$admin->has_cap($cap)) {
                $admin->add_cap($cap);
            }
        }
    }
}
add_action('init', 'dalen_find_allergist_add_caps_to_wa_roles', 10);


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

    $current_user = wp_get_current_user();
    $is_wa_user = false;
    foreach ($current_user->roles as $role) {
        if (strpos($role, 'wa_level_') === 0) {
            $is_wa_user = true;
            break;
        }
    }
    if (!$is_wa_user) {
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

    $is_wa_user = false;
    foreach ($current_user->roles as $role) {
        if (strpos($role, 'wa_level_') === 0) {
            $is_wa_user = true;
            break;
        }
    }
    if (!$is_wa_user) {
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

        // Keep only physicians post type menu (remove profile access)
        if (
            $menu_slug !== 'edit.php?post_type=physicians'
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

    $is_wa_user = false;
    foreach ($current_user->roles as $role) {
        if (strpos($role, 'wa_level_') === 0) {
            $is_wa_user = true;
            break;
        }
    }
    if (!$is_wa_user) {
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
    $is_wa_user = false;
    if ($user && is_array($user->roles)) {
        foreach ($user->roles as $role) {
            if (strpos($role, 'wa_level_') === 0) {
                $is_wa_user = true;
                break;
            }
        }
    }
    if (!$is_wa_user) {
        return true; // Not a wa_level_* user, no restriction
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

    $is_wa_user = false;
    foreach ($current_user->roles as $role) {
        if (strpos($role, 'wa_level_') === 0) {
            $is_wa_user = true;
            break;
        }
    }
    if (!$is_wa_user) {
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
    $is_wa_user = false;
    if (in_array('create_physicians', $caps) && isset($user->roles)) {
        foreach ($user->roles as $role) {
            if (strpos($role, 'wa_level_') === 0) {
                $is_wa_user = true;
                break;
            }
        }
        if ($is_wa_user && !can_allergist_create_physicians_post($user->ID)) {
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

    $is_wa_user = false;
    if ($author && is_array($author->roles)) {
        foreach ($author->roles as $role) {
            if (strpos($role, 'wa_level_') === 0) {
                $is_wa_user = true;
                break;
            }
        }
    }
    if (!$is_wa_user) {
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

    $is_wa_user = false;
    foreach ($current_user->roles as $role) {
        if (strpos($role, 'wa_level_') === 0) {
            $is_wa_user = true;
            break;
        }
    }
    if (!$is_wa_user) {
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

    $is_wa_user = false;
    foreach ($current_user->roles as $role) {
        if (strpos($role, 'wa_level_') === 0) {
            $is_wa_user = true;
            break;
        }
    }
    if (!$is_wa_user) {
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
    $wp_admin_bar->remove_node('my-account');
    $wp_admin_bar->remove_node('user-actions');
    $wp_admin_bar->remove_node('user-info');
    $wp_admin_bar->remove_node('edit-profile');
}
add_action('wp_before_admin_bar_render', 'hide_admin_bar_items_from_allergist', 999);

/**
 * Block wa_level users from accessing user management and profile pages
 */
function block_wa_user_profile_access()
{
    $current_user = wp_get_current_user();

    $is_wa_user = false;
    foreach ($current_user->roles as $role) {
        if (strpos($role, 'wa_level_') === 0) {
            $is_wa_user = true;
            break;
        }
    }
    if (!$is_wa_user) {
        return;
    }

    global $pagenow;

    // Block access to user-related pages
    $blocked_pages = [
        'profile.php',
        'user-edit.php',
        'users.php',
        'user-new.php'
    ];

    if (in_array($pagenow, $blocked_pages)) {
        wp_die(__('You do not have permission to access this page.'), __('Access Denied'), array('response' => 403));
    }
}
add_action('admin_init', 'block_wa_user_profile_access', 1);

/**
 * Hide admin bar completely for wa_level users
 */
function hide_admin_bar_for_wa_users()
{
    $current_user = wp_get_current_user();

    $is_wa_user = false;
    foreach ($current_user->roles as $role) {
        if (strpos($role, 'wa_level_') === 0) {
            $is_wa_user = true;
            break;
        }
    }
    
    if ($is_wa_user) {
        // Hide admin bar on frontend and backend
        show_admin_bar(false);
        
        // Also remove it via filter as backup
        add_filter('show_admin_bar', '__return_false');
        
        // Remove admin bar CSS and JS
        remove_action('wp_head', '_admin_bar_bump_cb');
        
        // Additional CSS to ensure it's completely hidden
        add_action('wp_head', function() {
            echo '<style type="text/css">
                #wpadminbar { display: none !important; }
                html { margin-top: 0 !important; }
                * html body { margin-top: 0 !important; }
            </style>';
        });
        
        add_action('admin_head', function() {
            echo '<style type="text/css">
                #wpadminbar { display: none !important; }
                html { margin-top: 0 !important; }
            </style>';
        });
    }
}
add_action('init', 'hide_admin_bar_for_wa_users');
add_action('admin_init', 'hide_admin_bar_for_wa_users');
