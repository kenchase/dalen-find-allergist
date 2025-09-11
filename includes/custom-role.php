<?php

/**
 * Wild Apricot User Role Management for Physicians Post Type
 * 
 * Manages access control and UI restrictions for users with roles beginning 
 * with "wa_level_" when working with the physicians custom post type.
 * 
 * Key Features:
 * - Role-based access control for physicians post editing
 * - UI simplification (streamlined admin interface)
 * - Content restrictions (one post per user limit)
 * - Security controls (prevent unauthorized changes)
 * - Admin bar and interface customization
 * 
 * @package Dalen Find Allergist
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WA User Management Class
 * Centralizes all wa_level user functionality
 */
class WA_User_Manager
{

    /**
     * Initialize all hooks and filters for wa_level user management
     * 
     * Organizes hooks by functionality for better maintainability:
     * - Core capability management
     * - Query and content restrictions  
     * - UI restrictions and customization
     * - AJAX and admin bar restrictions
     */
    public static function init()
    {
        // Core capability management
        add_filter('user_has_cap', [__CLASS__, 'manage_physicians_capabilities'], 9, 4);
        add_filter('map_meta_cap', [__CLASS__, 'map_physicians_meta_capabilities'], 10, 4);
        add_action('init', [__CLASS__, 'assign_wa_role_capabilities'], 10);

        // Query and content restrictions
        add_action('pre_get_posts', [__CLASS__, 'restrict_posts_query']);
        add_filter('wp_insert_post_data', [__CLASS__, 'prevent_unauthorized_changes'], 10, 2);
        add_filter('user_has_cap', [__CLASS__, 'restrict_duplicate_posts'], 10, 4);
        add_action('save_post', [__CLASS__, 'validate_physicians_post'], 10, 3);

        // UI restrictions
        add_action('admin_menu', [__CLASS__, 'modify_admin_interface'], 999);
        add_action('admin_head', [__CLASS__, 'hide_ui_elements']);
        add_action('admin_head', [__CLASS__, 'remove_help_tab']);
        add_action('admin_init', [__CLASS__, 'block_restricted_pages'], 1);

        // AJAX restrictions
        add_action('wp_ajax_inline-save', [__CLASS__, 'block_unauthorized_ajax'], 1);
        add_action('wp_ajax_sample-permalink', [__CLASS__, 'block_unauthorized_ajax'], 1);

        // Column management
        add_filter('manage_physicians_posts_columns', [__CLASS__, 'modify_post_columns'], 999);

        // Row actions management
        add_filter('post_row_actions', [__CLASS__, 'remove_quick_edit_for_wa_users'], 10, 2);

        // Views management
        add_filter('views_edit-physicians', [__CLASS__, 'hide_post_status_views']);
        add_filter('months_dropdown_results', [__CLASS__, 'remove_date_filter_dropdown'], 10, 2);

        // Post editor restrictions
        add_action('add_meta_boxes', [__CLASS__, 'remove_meta_boxes'], 999);
        add_filter('screen_options_show_screen', [__CLASS__, 'hide_screen_options'], 10, 2);

        // Admin bar restrictions
        add_action('admin_bar_menu', [__CLASS__, 'modify_admin_bar'], 999);
        add_action('wp_before_admin_bar_render', [__CLASS__, 'remove_admin_bar_nodes']);
        add_action('admin_head', [__CLASS__, 'hide_admin_bar_account_css']);
    }

    /**
     * Check if user has wa_level role
     * 
     * @param WP_User|int|null $user User object, ID, or null for current user
     * @return bool True if user has wa_level role
     */
    public static function is_wa_user($user = null)
    {
        if (is_numeric($user)) {
            $user = get_userdata($user);
        } elseif (!$user) {
            $user = wp_get_current_user();
        }

        if (!$user || empty($user->roles)) {
            return false;
        }

        foreach ($user->roles as $role) {
            if (strpos($role, 'wa_level_') === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get existing physicians posts for a user (helper method to reduce redundancy)
     * 
     * @param int|null $user_id User ID, null for current user
     * @param array $post_status Array of post statuses to check
     * @param int|null $exclude_post_id Post ID to exclude from results
     * @return array Array of post objects or IDs
     */
    private static function get_user_physicians_posts($user_id = null, $post_status = ['publish', 'draft', 'pending'], $exclude_post_id = null)
    {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $args = [
            'post_type' => 'physicians',
            'post_status' => $post_status,
            'author' => $user_id,
            'numberposts' => 1,
            'fields' => 'ids'
        ];

        if ($exclude_post_id) {
            $args['exclude'] = [$exclude_post_id];
        }

        return get_posts($args);
    }

    /**
     * Check if current page is physicians-related
     * 
     * @return bool
     */
    public static function is_physicians_page()
    {
        global $pagenow;

        // Check URL parameters
        if (isset($_GET['post_type']) && $_GET['post_type'] === 'physicians') {
            return true;
        }

        // Check for editing individual physicians posts
        if ($pagenow === 'post.php' && isset($_GET['post'])) {
            $post = get_post(intval($_GET['post']));
            return $post && $post->post_type === 'physicians';
        }

        return false;
    }

    /**
     * Get WA user capabilities array
     * 
     * @return array
     */
    public static function get_wa_capabilities()
    {
        return [
            'read',
            'edit_posts',
            'delete_posts',
            'publish_posts',
            'edit_physicians',
            'edit_own_physicians',
            'delete_own_physicians',
            'delete_physicians',
            'delete_published_physicians',
            'publish_physicians',
            'read_private_physicians',
            'edit_physician',
            'read_physician',
            'delete_physician',
            'create_physicians',
            'edit_published_physicians',
        ];
    }

    /**
     * Manage capabilities for wa_level users on physicians pages
     * 
     * @param array $allcaps All capabilities
     * @param array $caps Capabilities being checked
     * @param array $args Arguments
     * @param WP_User $user User object
     * @return array
     */
    public static function manage_physicians_capabilities($allcaps, $caps, $args, $user)
    {
        if (!is_admin() || !self::is_wa_user($user)) {
            return $allcaps;
        }

        // Grant capabilities on physicians pages
        if (self::is_physicians_page()) {
            $wa_caps = self::get_wa_capabilities();
            foreach ($wa_caps as $cap) {
                $allcaps[$cap] = true;
            }

            // Additional capabilities for specific post editing
            global $pagenow;
            if ($pagenow === 'post.php' && isset($_GET['post'])) {
                $post = get_post(intval($_GET['post']));
                if ($post && $post->post_type === 'physicians' && $post->post_author == $user->ID) {
                    $allcaps['edit_post'] = true;
                    $allcaps['delete_post'] = true;
                }
            }
        }

        // Handle specific capability checks
        if (!empty($caps)) {
            foreach ($caps as $cap) {
                if (in_array($cap, ['edit_post', 'delete_post']) && !empty($args)) {
                    $post_id = $args[0];
                    $post = get_post($post_id);
                    if ($post && $post->post_type === 'physicians' && $post->post_author == $user->ID) {
                        $allcaps[$cap] = true;
                        $allcaps['edit_published_physicians'] = true;
                        $allcaps['delete_published_physicians'] = true;
                    }
                }
            }
        }

        return $allcaps;
    }

    /**
     * Map meta capabilities for physicians posts
     * 
     * @param array $caps Capabilities
     * @param string $cap Capability being checked
     * @param int $user_id User ID
     * @param array $args Arguments
     * @return array
     */
    public static function map_physicians_meta_capabilities($caps, $cap, $user_id, $args)
    {
        if (!self::is_wa_user($user_id)) {
            return $caps;
        }

        $post_caps = ['edit_post', 'delete_post', 'edit_published_post', 'delete_published_post'];
        $specific_caps = ['edit_physician', 'read_physician', 'delete_physician'];

        if (in_array($cap, array_merge($post_caps, $specific_caps)) && !empty($args)) {
            $post_id = $args[0];
            $post = get_post($post_id);

            if ($post && $post->post_type === 'physicians') {
                if ($post->post_author == $user_id) {
                    // Allow editing/deleting own posts
                    return in_array($cap, ['delete_post', 'delete_published_post', 'delete_physician'])
                        ? ['delete_physicians']
                        : ['edit_physicians'];
                } else {
                    // Deny access to others' posts
                    return ['do_not_allow'];
                }
            }
        }

        return $caps;
    }

    /**
     * Assign capabilities to wa_level roles
     */
    public static function assign_wa_role_capabilities()
    {
        global $wp_roles;
        if (!isset($wp_roles)) {
            $wp_roles = wp_roles();
        }

        $wa_caps = self::get_wa_capabilities();

        foreach ($wp_roles->roles as $role_key => $role) {
            if (strpos($role_key, 'wa_level_') === 0) {
                $role_obj = get_role($role_key);
                if ($role_obj) {
                    foreach ($wa_caps as $cap) {
                        $role_obj->add_cap($cap, true);
                    }
                }
            }
        }

        // Add capabilities to Administrator role
        $admin = get_role('administrator');
        if ($admin) {
            $admin_caps = array_merge($wa_caps, [
                'edit_others_physicians',
                'delete_others_physicians',
                'list_users',
                'edit_users',
                'delete_users',
                'create_users',
                'promote_users',
                'remove_users',
                'manage_options',
            ]);

            foreach ($admin_caps as $cap) {
                if (!$admin->has_cap($cap)) {
                    $admin->add_cap($cap);
                }
            }
        }
    }

    /**
     * Restrict query to show only user's own posts
     * 
     * @param WP_Query $query
     */
    public static function restrict_posts_query($query)
    {
        if (!is_admin() || !$query->is_main_query() || !self::is_wa_user()) {
            return;
        }

        global $pagenow;
        if ($pagenow == 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] == 'physicians') {
            $query->set('author', get_current_user_id());
        }
    }

    /**
     * Prevent unauthorized changes to physicians posts
     * 
     * Enforces security rules:
     * - Prevents author changes (users can only edit their own posts)
     * - Prevents slug changes (maintains URL consistency)
     * - Blocks duplicate post creation (one post per user limit)
     * 
     * @param array $post_data Post data being saved
     * @param array $postarr Original post array
     * @return array Modified post data
     */
    public static function prevent_unauthorized_changes($post_data, $postarr)
    {
        if ($post_data['post_type'] !== 'physicians' || !self::is_wa_user()) {
            return $post_data;
        }

        $current_user = wp_get_current_user();

        // Prevent author changes
        if (!empty($postarr['ID'])) {
            $existing_post = get_post($postarr['ID']);
            if ($existing_post) {
                $post_data['post_author'] = $existing_post->post_author;
                $post_data['post_name'] = $existing_post->post_name; // Prevent slug changes
            }
        } else {
            $post_data['post_author'] = $current_user->ID;
        }

        // Prevent duplicate posts
        if (empty($postarr['ID']) && !self::can_create_physicians_post($current_user->ID)) {
            wp_die(
                __('You can only create one physician profile. Please edit your existing profile instead.'),
                __('Permission Denied'),
                array('response' => 403, 'back_link' => true)
            );
        }

        return $post_data;
    }

    /**
     * Check if user can create physicians posts (enforces one-post-per-user limit)
     * 
     * @param int|null $user_id User ID, null for current user
     * @return bool True if user can create posts
     */
    public static function can_create_physicians_post($user_id = null)
    {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        // No restriction for non-wa users
        if (!self::is_wa_user($user_id)) {
            return true;
        }

        // Check for existing posts (including future and private)
        $existing_posts = self::get_user_physicians_posts($user_id, ['publish', 'draft', 'pending', 'future', 'private']);
        return empty($existing_posts);
    }

    /**
     * Restrict duplicate post creation
     * 
     * @param array $allcaps All capabilities
     * @param array $caps Capabilities being checked
     * @param array $args Arguments
     * @param WP_User $user User object
     * @return array
     */
    public static function restrict_duplicate_posts($allcaps, $caps, $args, $user)
    {
        if (in_array('create_physicians', $caps) && self::is_wa_user($user)) {
            if (!self::can_create_physicians_post($user->ID)) {
                $allcaps['create_physicians'] = false;
            }
        }
        return $allcaps;
    }

    /**
     * Validate physicians post on save (prevents duplicate posts)
     * 
     * @param int $post_id Post ID
     * @param WP_Post $post Post object
     * @param bool $update Whether this is an update
     */
    public static function validate_physicians_post($post_id, $post, $update)
    {
        // Skip validation for autosaves, revisions, non-physicians posts, or updates
        if (
            $post->post_type !== 'physicians' ||
            wp_is_post_autosave($post_id) ||
            wp_is_post_revision($post_id) ||
            $update ||
            !self::is_wa_user($post->post_author)
        ) {
            return;
        }

        // Check for duplicate posts on new post creation
        $existing_posts = self::get_user_physicians_posts($post->post_author, ['publish', 'draft', 'pending', 'future', 'private'], $post_id);

        if (!empty($existing_posts)) {
            wp_delete_post($post_id, true);
            wp_redirect(add_query_arg([
                'post_type' => 'physicians',
                'error' => 'duplicate_post'
            ], admin_url('edit.php')));
            exit;
        }
    }

    /**
     * Modify admin interface for wa_level users (removes unnecessary menu items)
     */
    public static function modify_admin_interface()
    {
        if (!self::is_wa_user()) {
            return;
        }

        global $menu, $submenu;

        // Remove all menu items except physicians
        foreach ($menu as $key => $menu_item) {
            if (empty($menu_item[2])) continue;

            if ($menu_item[2] !== 'edit.php?post_type=physicians') {
                remove_menu_page($menu_item[2]);
            }
        }

        // Clean up physicians submenu - keep only essential items
        if (isset($submenu['edit.php?post_type=physicians'])) {
            $allowed_submenu_items = [
                'edit.php?post_type=physicians',
                'post-new.php?post_type=physicians'
            ];

            foreach ($submenu['edit.php?post_type=physicians'] as $key => $submenu_item) {
                if (!in_array($submenu_item[2], $allowed_submenu_items)) {
                    unset($submenu['edit.php?post_type=physicians'][$key]);
                }
            }
        }

        // Remove "Add New" if user already has a post (enforces one-post limit)
        $existing_posts = self::get_user_physicians_posts();
        if (!empty($existing_posts)) {
            remove_submenu_page('edit.php?post_type=physicians', 'post-new.php?post_type=physicians');
        }
    }

    /**
     * Hide UI elements via CSS and JavaScript (streamlines interface for wa_level users)
     */
    public static function hide_ui_elements()
    {
        if (!self::is_wa_user()) {
            return;
        }

        // Remove all admin notices for cleaner interface
        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');
        remove_all_actions('network_admin_notices');

        global $pagenow;

        // Hide "Add New" button if user has existing post
        if ($pagenow == 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] == 'physicians') {
            $existing_posts = self::get_user_physicians_posts();

            echo '<style>
                /* Hide search box for all wa_level users on physicians page */
                .search-box {
                    display: none !important;
                }
            </style>';

            if (!empty($existing_posts)) {
                echo '<style>
                    /* Hide Add New buttons and related elements */
                    .page-title-action,
                    .add-new-h2,
                    #favorite-actions,
                    .tablenav .alignleft .button,
                    .wrap .page-title-action,
                    .wrap h1 .page-title-action,
                    a.page-title-action,
                    .wp-heading-inline + .page-title-action,
                    input[value="Add New"],
                    #adminmenu .wp-submenu a[href*="post-new.php?post_type=physicians"] {
                        display: none !important;
                    }
                </style>';
            }
        }

        // Hide author and slug elements that can't be removed via meta boxes
        if (in_array($pagenow, ['post.php', 'post-new.php']) && self::is_physicians_page()) {
            echo '<style>
                /* Hide author elements in publish meta box */
                .misc-pub-post-author,
                .misc-pub-section.misc-pub-author,
                
                /* Hide slug elements in publish meta box and permalink */
                #edit-slug-box,
                #editable-post-name,
                #editable-post-name-full,
                .edit-slug,
                #sample-permalink,
                .sample-permalink,
                #post-slug-edit,
                #edit-slug-buttons {
                    display: none !important;
                }
            </style>';

            echo '<script>
                jQuery(document).ready(function($) {
                    // Remove permalink editing functionality
                    $("#edit-slug-box, .edit-slug").remove();
                    $("#sample-permalink a").off("click");
                    
                    // Handle dynamic content
                    setTimeout(function() {
                        $("#edit-slug-box, .edit-slug").remove();
                        $("#sample-permalink a").off("click");
                    }, 1000);
                });
            </script>';
        }
    }

    /**
     * Remove help tabs for wa_level users
     */
    public static function remove_help_tab()
    {
        if (!self::is_wa_user()) {
            return;
        }

        $screen = get_current_screen();
        if ($screen) {
            $screen->remove_help_tabs();
        }
    }

    /**
     * Block access to restricted admin pages
     */
    public static function block_restricted_pages()
    {
        if (!self::is_wa_user()) {
            return;
        }

        global $pagenow;

        $blocked_pages = ['profile.php', 'user-edit.php', 'users.php', 'user-new.php'];

        if (in_array($pagenow, $blocked_pages)) {
            wp_die(__('You do not have permission to access this page.'), __('Access Denied'), ['response' => 403]);
        }

        // Block new post creation if user already has one
        if ($pagenow == 'post-new.php' && isset($_GET['post_type']) && $_GET['post_type'] == 'physicians') {
            if (!self::can_create_physicians_post()) {
                wp_die(__('You can only create one physician profile. Please edit your existing profile instead.'));
            }
        }

        // Block editing others' posts
        if ($pagenow == 'post.php' && isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['post'])) {
            $post = get_post(intval($_GET['post']));
            if ($post && $post->post_type == 'physicians' && $post->post_author != get_current_user_id()) {
                wp_die(__('You do not have permission to edit this post.'));
            }
        }
    }

    /**
     * Block unauthorized AJAX requests
     */
    public static function block_unauthorized_ajax()
    {
        if (!self::is_wa_user()) {
            return;
        }

        $blocked_actions = ['inline-save', 'sample-permalink'];

        if (isset($_POST['action']) && in_array($_POST['action'], $blocked_actions)) {
            if (isset($_POST['post_type']) && $_POST['post_type'] === 'physicians') {
                wp_die(__('You do not have permission to perform this action.'), __('Permission Denied'), ['response' => 403]);
            }
        }
    }

    /**
     * Modify post list columns
     * 
     * @param array $columns
     * @return array
     */
    public static function modify_post_columns($columns)
    {
        if (self::is_wa_user()) {
            // Remove checkbox column
            unset($columns['cb']);

            // Remove author column
            unset($columns['author']);

            // Remove date column
            unset($columns['date']);

            // Remove Yoast SEO columns (try different possible names)
            unset($columns['wpseo-links']);
            unset($columns['wpseo-linked']);
            unset($columns['wpseo_links']);
            unset($columns['wpseo_linked']);
            unset($columns['yoast-seo-links']);
            unset($columns['yoast-seo-linked']);

            // Remove taxonomy column
            unset($columns['taxonomy-physiciantypes']);
        }
        return $columns;
    }

    /**
     * Remove quick edit functionality for wa_level users
     * 
     * Prevents wa_level users from using inline editing which could bypass
     * security restrictions and validation rules.
     * 
     * @param array $actions Row actions for the post
     * @param WP_Post $post Current post object
     * @return array Modified actions array
     */
    public static function remove_quick_edit_for_wa_users($actions, $post)
    {
        // Only apply to physicians posts and wa_level users
        if ($post->post_type === 'physicians' && self::is_wa_user()) {
            unset($actions['inline hide-if-no-js']);
        }
        return $actions;
    }

    /**
     * Hide post status views for wa_level users
     * 
     * @param array $views
     * @return array
     */
    public static function hide_post_status_views($views)
    {
        if (self::is_wa_user()) {
            // Return empty array to hide all views (All, Published, Drafts, Private)
            return [];
        }
        return $views;
    }

    /**
     * Remove date filter dropdown for physicians post type
     * 
     * @param array $months
     * @param string $post_type
     * @return array
     */
    public static function remove_date_filter_dropdown($months, $post_type)
    {
        // Remove for physicians post type when wa_level user
        if ($post_type === 'physicians' && self::is_wa_user()) {
            return array(); // Return empty array to hide dropdown
        }
        return $months;
    }

    /**
     * Remove meta boxes for wa_level users
     */
    public static function remove_meta_boxes()
    {
        if (!self::is_wa_user()) {
            return;
        }

        // Remove author meta box
        remove_meta_box('authordiv', 'physicians', 'normal');
        remove_meta_box('authordiv', 'physicians', 'side');
        remove_meta_box('authordiv', 'physicians', 'advanced');

        // Remove slug meta box
        remove_meta_box('slugdiv', 'physicians', 'normal');
        remove_meta_box('slugdiv', 'physicians', 'side');
        remove_meta_box('slugdiv', 'physicians', 'advanced');

        // Remove other potentially problematic meta boxes
        remove_meta_box('commentstatusdiv', 'physicians', 'normal');
        remove_meta_box('commentsdiv', 'physicians', 'normal');
        remove_meta_box('trackbacksdiv', 'physicians', 'normal');
        remove_meta_box('postcustom', 'physicians', 'normal');
        remove_meta_box('postexcerpt', 'physicians', 'normal');
        remove_meta_box('formatdiv', 'physicians', 'normal');
        remove_meta_box('pageparentdiv', 'physicians', 'normal');
    }

    /**
     * Hide screen options for wa_level users
     * 
     * @param bool $show_screen
     * @param WP_Screen $screen
     * @return bool
     */
    public static function hide_screen_options($show_screen, $screen)
    {
        if (self::is_wa_user() && $screen->post_type === 'physicians') {
            return false;
        }
        return $show_screen;
    }

    /**
     * Modify admin bar to show only logout link for wa_level users
     */
    public static function modify_admin_bar($wp_admin_bar)
    {
        if (!self::is_wa_user()) {
            return;
        }

        // Remove the main user account menu and all related nodes
        $wp_admin_bar->remove_node('my-account');
        $wp_admin_bar->remove_node('my-account-with-avatar');
        $wp_admin_bar->remove_node('user-actions');
        $wp_admin_bar->remove_node('user-info');
        $wp_admin_bar->remove_node('edit-profile');

        // Remove other common admin bar items
        $wp_admin_bar->remove_node('wp-logo');
        $wp_admin_bar->remove_node('site-name');
        $wp_admin_bar->remove_node('new-content');
        $wp_admin_bar->remove_node('comments');
        $wp_admin_bar->remove_node('updates');
        $wp_admin_bar->remove_node('search');

        // Remove items from root-default menu
        $wp_admin_bar->remove_node('menu-toggle');
        $wp_admin_bar->remove_node('archive');
        $wp_admin_bar->remove_node('wpseo-menu');
        $wp_admin_bar->remove_node('tribe-events');

        // Add a simple logout link
        $wp_admin_bar->add_node(array(
            'id'     => 'logout-only',
            'title'  => 'Logout',
            'href'   => wp_logout_url(),
            'parent' => 'top-secondary'
        ));
    }

    /**
     * Remove admin bar nodes using wp_before_admin_bar_render hook
     */
    public static function remove_admin_bar_nodes()
    {
        if (!self::is_wa_user()) {
            return;
        }

        global $wp_admin_bar;

        // Remove account-related nodes
        $wp_admin_bar->remove_node('my-account');
        $wp_admin_bar->remove_node('my-account-with-avatar');
        $wp_admin_bar->remove_node('user-actions');
        $wp_admin_bar->remove_node('user-info');
        $wp_admin_bar->remove_node('edit-profile');

        // Remove root-default menu items
        $wp_admin_bar->remove_node('menu-toggle');
        $wp_admin_bar->remove_node('archive');
        $wp_admin_bar->remove_node('wpseo-menu');
        $wp_admin_bar->remove_node('tribe-events');
    }

    /**
     * Hide account elements with CSS as fallback
     */
    public static function hide_admin_bar_account_css()
    {
        if (!self::is_wa_user()) {
            return;
        }

        echo '<style>
            #wp-admin-bar-my-account,
            #wp-admin-bar-my-account-with-avatar,
            #wp-admin-bar-user-actions,
            #wp-admin-bar-user-info,
            #wp-admin-bar-edit-profile,
            #wp-admin-bar-menu-toggle,
            #wp-admin-bar-archive,
            #wp-admin-bar-wpseo-menu,
            #wp-admin-bar-tribe-events {
                display: none !important;
            }
        </style>';
    }
}

// Initialize the WA User Manager
WA_User_Manager::init();
