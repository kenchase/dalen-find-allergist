<?php

/**
 * Wild Apricot User Role Management
 * 
 * Handles user role assignment, capability management, and access control
 * for users with roles beginning with "wa_level_".
 * 
 * Key Features:
 * - Role-based access control for physicians post editing
 * - Capability assignment and management
 * - Content restrictions (one post per user limit)
 * - Security controls (prevent unauthorized changes)
 * - Query restrictions for own posts only
 * 
 * @package Dalen Find Allergist
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WA User Role Management Class
 * Centralizes role-based functionality for wa_level users
 */
class WA_User_Role
{
    /**
     * Initialize role-related hooks and filters
     */
    public static function init()
    {
        // Core capability and access management
        add_filter('user_has_cap', [__CLASS__, 'manage_all_user_capabilities'], 10, 4);
        add_filter('map_meta_cap', [__CLASS__, 'map_physicians_meta_capabilities'], 10, 4);
        add_action('init', [__CLASS__, 'assign_wa_role_capabilities'], 10);
        add_action('admin_init', [__CLASS__, 'handle_admin_init_tasks'], 1);

        // Content management and restrictions
        add_action('pre_get_posts', [__CLASS__, 'restrict_posts_query']);
        add_filter('wp_insert_post_data', [__CLASS__, 'prevent_unauthorized_changes'], 10, 2);
        add_action('save_post', [__CLASS__, 'validate_physicians_post'], 10, 3);

        // Admin access validation
        add_action('current_screen', [__CLASS__, 'validate_admin_access'], 1);

        // AJAX restrictions
        add_action('wp_ajax_inline-save', [__CLASS__, 'block_unauthorized_ajax'], 1);
        add_action('wp_ajax_sample-permalink', [__CLASS__, 'block_unauthorized_ajax'], 1);
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
            'publish_physician',
            'create_physicians',
            'edit_published_physicians',
            // Additional capabilities that might be needed for delete operations
            'delete_others_physicians', // Even though they can't use it, having it might help
            'edit_others_physicians',   // Same as above
        ];
    }

    /**
     * Comprehensive capability management for wa_level users
     * 
     * Consolidates all capability management logic including duplicate post restrictions
     * into a single method for better performance and maintainability.
     * 
     * @param array $allcaps All capabilities
     * @param array $caps Capabilities being checked
     * @param array $args Arguments
     * @param WP_User $user User object
     * @return array
     */
    public static function manage_all_user_capabilities($allcaps, $caps, $args, $user)
    {
        // Early return for non-wa users
        if (!self::is_wa_user($user)) {
            return $allcaps;
        }

        // Handle duplicate post creation restriction
        if (in_array('create_physicians', $caps)) {
            if (!self::can_create_physicians_post($user->ID)) {
                $allcaps['create_physicians'] = false;
                return $allcaps;
            }
        }

        // Define all essential capabilities for wa_level users
        $essential_caps = [
            'read',
            'edit_posts',
            'delete_posts',
            'publish_posts',
            'edit_published_posts',
            'delete_published_posts',
            'edit_physicians',
            'delete_physicians',
            'delete_published_physicians',
            'publish_physicians',
            'edit_physician',
            'delete_physician',
            'publish_physician',
            'create_physicians',
            'edit_published_physicians',
            // Additional capabilities for full functionality
            'edit_others_posts',
            'delete_others_posts',
            'read_private_posts',
            'edit_private_posts',
            'delete_private_posts',
            'publish_private_posts'
        ];

        // Grant all essential capabilities
        foreach ($essential_caps as $cap) {
            $allcaps[$cap] = true;
        }

        // Handle specific capability checks (publishing, editing)
        if (!empty($caps)) {
            foreach ($caps as $cap) {
                if (in_array($cap, $essential_caps)) {
                    $allcaps[$cap] = true;
                }
            }
        }

        return $allcaps;
    }

    /**
     * Handle all admin_init tasks for wa_level users
     * 
     * Consolidates page access restrictions and capability forcing into a single method.
     */
    public static function handle_admin_init_tasks()
    {
        if (!self::is_wa_user()) {
            return;
        }

        // Force essential capabilities directly to user objects (late execution)
        self::force_wa_user_capabilities();

        // Handle page access restrictions
        self::block_restricted_pages();
    }

    /**
     * Force essential capabilities directly to wa_level user objects
     * 
     * This ensures capabilities are persistently available and runs late
     * to override any restrictions from other plugins.
     */
    public static function force_wa_user_capabilities()
    {
        if (!is_admin() || !self::is_wa_user()) {
            return;
        }

        $current_user = wp_get_current_user();
        if (!$current_user || !$current_user->exists()) {
            return;
        }

        // Core capabilities that must be directly assigned
        $core_caps = [
            'read',
            'edit_posts',
            'delete_posts',
            'publish_posts',
            'edit_published_posts',
            'delete_published_posts',
            'edit_physicians',
            'delete_physicians',
            'delete_published_physicians',
            'publish_physicians',
            'edit_physician',
            'delete_physician',
            'publish_physician',
            'create_physicians',
            'edit_published_physicians'
        ];

        foreach ($core_caps as $cap) {
            $current_user->add_cap($cap);
        }
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

        $post_caps = ['edit_post', 'delete_post', 'edit_published_post', 'delete_published_post', 'publish_post', 'read_post'];
        $specific_caps = ['edit_physician', 'read_physician', 'delete_physician', 'publish_physician'];

        if (in_array($cap, array_merge($post_caps, $specific_caps)) && !empty($args)) {
            $post_id = $args[0];
            $post = get_post($post_id);

            if ($post && $post->post_type === 'physicians') {
                if ($post->post_author == $user_id) {
                    // Allow editing/deleting/publishing own posts
                    if (in_array($cap, ['delete_post', 'delete_published_post', 'delete_physician'])) {
                        return ['delete_physicians'];
                    } elseif (in_array($cap, ['publish_post', 'publish_physician'])) {
                        return ['publish_physicians'];
                    } elseif (in_array($cap, ['read_post', 'read_physician'])) {
                        return ['read'];
                    } else {
                        return ['edit_physicians'];
                    }
                } else {
                    // Deny access to others' posts
                    return ['do_not_allow'];
                }
            }
        }

        // For bulk operations or when no specific post ID, allow if user has general capability
        if (in_array($cap, $post_caps) && empty($args)) {
            if (in_array($cap, ['delete_post', 'delete_published_post'])) {
                return ['delete_physicians'];
            } elseif (in_array($cap, ['publish_post'])) {
                return ['publish_physicians'];
            } elseif (in_array($cap, ['read_post'])) {
                return ['read'];
            } else {
                return ['edit_physicians'];
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

        foreach ($wp_roles->roles as $role_key => $role) {
            if (strpos($role_key, 'wa_level_') === 0) {
                $role_obj = get_role($role_key);
                if ($role_obj) {
                    // Grant minimal capabilities needed for physicians management
                    $wa_caps = [
                        'read',
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
                        'publish_physician',
                        'create_physicians',
                        'edit_published_physicians'
                    ];

                    foreach ($wa_caps as $cap) {
                        $role_obj->add_cap($cap, true);
                    }
                }
            }
        }

        // Ensure Administrator role has all needed capabilities
        $admin_role = get_role('administrator');

        // Ensure Administrator role has all needed capabilities
        if ($admin_role) {
            $admin_caps_to_add = [
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
                'publish_physician',
                'create_physicians',
                'edit_published_physicians',
                'delete_others_physicians',
                'edit_others_physicians',
                'list_users',
                'edit_users',
                'delete_users',
                'create_users',
                'promote_users',
                'remove_users',
                'manage_options'
            ];

            foreach ($admin_caps_to_add as $cap) {
                if (!$admin_role->has_cap($cap)) {
                    $admin_role->add_cap($cap);
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
     * - Allows slug changes when publishing (WordPress needs this for draft-to-published transitions)
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

                // Only prevent slug changes if not transitioning to published status
                // WordPress needs to update slugs when publishing drafts
                if ($post_data['post_status'] !== 'publish' || $existing_post->post_status === 'publish') {
                    $post_data['post_name'] = $existing_post->post_name;
                }
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
     * Block access to restricted admin pages and enforce physicians-only access
     */
    public static function block_restricted_pages()
    {
        if (!self::is_wa_user()) {
            return;
        }

        global $pagenow;

        // Define allowed pages for wa_level users
        $allowed_pages = [
            'edit.php',     // Only with post_type=physicians
            'post.php',     // Only for their own physicians posts
            'post-new.php', // Only for physicians post type
            'admin.php',    // For custom submenu pages like home page link
            'admin-ajax.php' // For AJAX requests
        ];

        // Check if current page is in the allowed list
        if (!in_array($pagenow, $allowed_pages)) {
            // Redirect to physicians page instead of showing error
            wp_redirect(admin_url('edit.php?post_type=physicians'));
            exit;
        }

        // Additional validation for admin.php - only allow wa-home-page and wa-my-account
        if ($pagenow === 'admin.php') {
            $allowed_admin_pages = ['wa-home-page', 'wa-my-account'];
            if (!isset($_GET['page']) || !in_array($_GET['page'], $allowed_admin_pages)) {
                wp_redirect(admin_url('edit.php?post_type=physicians'));
                exit;
            }
        }

        // Additional validation for edit.php - must have physicians post_type
        if ($pagenow === 'edit.php') {
            if (!isset($_GET['post_type']) || $_GET['post_type'] !== 'physicians') {
                wp_redirect(admin_url('edit.php?post_type=physicians'));
                exit;
            }
        }

        // Validation for post-new.php - must be physicians post type
        if ($pagenow === 'post-new.php') {
            if (!isset($_GET['post_type']) || $_GET['post_type'] !== 'physicians') {
                wp_redirect(admin_url('edit.php?post_type=physicians'));
                exit;
            }

            // Block new post creation if user already has one
            if (!self::can_create_physicians_post()) {
                wp_die(__('You can only create one physician profile. Please edit your existing profile instead.'));
            }
        }

        // Validation for post.php - must be their own physicians post
        if ($pagenow === 'post.php') {
            if (isset($_GET['post']) || isset($_POST['post_ID'])) {
                // Get post ID from either GET (viewing) or POST (submitting/publishing)
                $post_id = isset($_GET['post']) ? intval($_GET['post']) : intval($_POST['post_ID']);
                $post = get_post($post_id);

                // Check if post exists and is physicians post type
                if (!$post || $post->post_type !== 'physicians') {
                    wp_redirect(admin_url('edit.php?post_type=physicians'));
                    exit;
                }

                // Check if user owns the post
                if ($post->post_author != get_current_user_id()) {
                    wp_die(__('You do not have permission to access this post.'));
                }

                // Allow POST requests (form submissions like publishing) without action validation
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    return; // Allow all POST requests for own posts (publishing, saving, etc.)
                }

                // For GET requests, validate the action if present
                if (isset($_GET['action'])) {
                    $allowed_actions = ['edit', 'delete', 'trash', 'untrash', 'restore'];
                    if (!in_array($_GET['action'], $allowed_actions)) {
                        wp_redirect(admin_url('edit.php?post_type=physicians'));
                        exit;
                    }
                }
                // If no action parameter on GET request, it's likely a normal edit page access - allow it
            } else {
                // No valid post ID, redirect to physicians list
                wp_redirect(admin_url('edit.php?post_type=physicians'));
                exit;
            }
        }
    }

    /**
     * Comprehensive admin access validation as additional safety net
     * 
     * This method provides an additional layer of security by validating
     * admin access based on current screen and redirecting unauthorized access.
     * 
     * @param WP_Screen $current_screen Current admin screen
     */
    public static function validate_admin_access($current_screen)
    {
        if (!self::is_wa_user() || !is_admin()) {
            return;
        }

        // Skip AJAX requests as they're handled separately
        if (wp_doing_ajax()) {
            return;
        }

        // Define allowed screen bases and IDs for wa_level users
        $allowed_screens = [
            'edit-physicians',      // Physicians list page
            'physicians',          // Single physician edit page
            'physicians_page_*',   // Any physicians-related admin pages
            'physicians_page_wa-home-page', // Our custom home page link
            'physicians_page_wa-my-account' // Our custom my account link
        ];

        $current_screen_id = $current_screen->id;
        $current_screen_base = $current_screen->base;

        // Check if current screen is allowed
        $is_allowed = false;

        // Check exact matches first
        if (in_array($current_screen_id, $allowed_screens) || in_array($current_screen_base, $allowed_screens)) {
            $is_allowed = true;
        }

        // Check for physicians-related screens
        if (!$is_allowed) {
            if (
                strpos($current_screen_id, 'physicians') !== false ||
                strpos($current_screen_base, 'physicians') !== false ||
                ($current_screen->post_type === 'physicians') ||
                $current_screen_id === 'physicians_page_wa-home-page' ||
                $current_screen_id === 'physicians_page_wa-my-account'
            ) {
                $is_allowed = true;
            }
        }

        // If not allowed, redirect to physicians page
        if (!$is_allowed) {
            wp_redirect(admin_url('edit.php?post_type=physicians'));
            exit;
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
}
