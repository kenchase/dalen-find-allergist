<?php

/**
 * Wild Apricot Admin Interface Customization
 * 
 * Handles essential admin interface modifications for users with roles beginning with "wa_level_".
 * Relies on proper role capabilities for most access control, only implementing
 * security-critical overrides and UX improvements.
 * 
 * Key Features:
 * - Scalable whitelist-based menu management
 * - Security-critical UI restrictions (quick edit, own posts only)
 * - Essential UX improvements (menu text changes)
 * - One-post limit enforcement
 * 
 * @package Dalen Find Allergist
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WA Admin Interface Management Class
 * Centralizes admin interface customization for wa_level users
 */
class WA_Admin_Interface
{
    /**
     * Initialize interface-related hooks and filters
     */
    public static function init()
    {
        // Admin interface management - use multiple hooks with different priorities
        add_action('admin_menu', [__CLASS__, 'early_menu_cleanup'], 1);
        add_action('admin_menu', [__CLASS__, 'manage_admin_interface'], 100);
        add_action('admin_menu', [__CLASS__, 'final_menu_cleanup'], 9999);
        add_action('admin_head', [__CLASS__, 'handle_all_admin_head_tasks']);

        // Hide admin bar for wa_level users - use multiple hooks for reliability
        add_action('after_setup_theme', [__CLASS__, 'disable_admin_bar_for_wa_users'], 1);
        add_filter('show_admin_bar', [__CLASS__, 'hide_admin_bar_for_wa_users'], 10, 1);
        add_action('wp', [__CLASS__, 'disable_admin_bar_for_wa_users'], 1);
        add_action('admin_init', [__CLASS__, 'disable_admin_bar_for_wa_users'], 1);

        // Hide Screen Options and Help tabs for wa_level users
        add_filter('screen_options_show_screen', [__CLASS__, 'hide_screen_options_for_wa_users'], 10, 2);
        add_filter('contextual_help', [__CLASS__, 'hide_contextual_help_for_wa_users'], 10, 3);

        // List page customizations
        add_filter('handle_bulk_actions-edit-physicians', [__CLASS__, 'handle_bulk_delete'], 10, 3);
        add_filter('post_row_actions', [__CLASS__, 'remove_quick_edit_for_wa_users'], 10, 2);
        add_filter('months_dropdown_results', [__CLASS__, 'remove_date_filter_dropdown'], 10, 2);
        add_filter('views_edit-physicians', [__CLASS__, 'hide_post_status_views_for_wa_users']);
        add_filter('manage_physicians_posts_columns', [__CLASS__, 'modify_physicians_columns_for_wa_users'], 999);
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
     * Hide admin bar for wa_level users
     * 
     * @param bool $show_admin_bar Whether to show the admin bar
     * @return bool False if wa_level user, otherwise original value
     */
    public static function hide_admin_bar_for_wa_users($show_admin_bar)
    {
        if (self::is_wa_user()) {
            return false;
        }
        return $show_admin_bar;
    }

    /**
     * Aggressively disable admin bar for wa_level users
     * 
     * This method uses multiple robust approaches to ensure the admin bar is hidden
     * without relying on CSS. Uses WordPress core mechanisms for reliable hiding.
     */
    public static function disable_admin_bar_for_wa_users()
    {
        if (self::is_wa_user()) {
            // Primary method: High priority filter override
            add_filter('show_admin_bar', '__return_false', 999);

            // Remove admin bar initialization hooks
            remove_action('wp_head', '_admin_bar_bump_cb');
            remove_action('wp_footer', 'wp_admin_bar_render', 1000);

            // Remove admin bar from admin area
            remove_action('in_admin_header', 'wp_admin_bar_render', 0);

            // Set user preferences to disable admin bar
            $current_user_id = get_current_user_id();
            if ($current_user_id) {
                update_user_meta($current_user_id, 'show_admin_bar_front', false);
                update_user_meta($current_user_id, 'show_admin_bar_admin', false);
            }

            // Remove admin bar capability if it exists
            $current_user = wp_get_current_user();
            if ($current_user && $current_user->has_cap('show_admin_bar')) {
                $current_user->remove_cap('show_admin_bar');
            }

            // Prevent admin bar from being enqueued
            wp_dequeue_script('admin-bar');
            wp_dequeue_style('admin-bar');
        }
    }

    /**
     * Hide Screen Options tab for wa_level users
     * 
     * @param bool $show_screen Whether to show screen options
     * @param WP_Screen $screen Current screen object
     * @return bool False if wa_level user, otherwise original value
     */
    public static function hide_screen_options_for_wa_users($show_screen, $screen)
    {
        if (self::is_wa_user()) {
            return false;
        }
        return $show_screen;
    }

    /**
     * Hide contextual help for wa_level users
     * 
     * @param string $old_help The help content
     * @param string $screen_id Screen ID
     * @param WP_Screen $screen Current screen object
     * @return string Empty string if wa_level user, otherwise original help
     */
    public static function hide_contextual_help_for_wa_users($old_help, $screen_id, $screen)
    {
        if (self::is_wa_user()) {
            return '';
        }
        return $old_help;
    }

    /**
     * Get existing physicians posts for a user (helper method)
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
     * Handle all admin_head tasks for wa_level users
     * 
     * Consolidates UI hiding, help tab removal into a single method.
     */
    public static function handle_all_admin_head_tasks()
    {
        if (!self::is_wa_user()) {
            return;
        }

        // Remove help tabs
        self::remove_help_tab();

        // Hide UI elements
        self::hide_ui_elements();
    }

    /**
     * Early menu cleanup to catch WordPress core menus as they're added
     * 
     * This runs at priority 1 to remove core menus before plugins add theirs.
     * Uses whitelist approach for consistency.
     */
    public static function early_menu_cleanup()
    {
        if (!self::is_wa_user()) {
            return;
        }

        // Define allowed menu items (same whitelist as other methods)
        $allowed_menu_items = [
            'edit.php?post_type=physicians'
        ];

        // Remove all core WordPress menus that aren't in whitelist
        $core_menus_to_remove = [
            'index.php',                    // Dashboard
            'edit.php',                     // Posts  
            'upload.php',                   // Media
            'edit.php?post_type=page',      // Pages
            'edit-comments.php',            // Comments
            'themes.php',                   // Appearance
            'plugins.php',                  // Plugins
            'users.php',                    // Users
            'tools.php',                    // Tools
            'options-general.php',          // Settings
            'separator1',                   // Separators
            'separator2',
            'separator-last',
        ];

        foreach ($core_menus_to_remove as $menu_slug) {
            if (!in_array($menu_slug, $allowed_menu_items)) {
                remove_menu_page($menu_slug);
            }
        }
    }

    /**
     * Comprehensive admin interface management for wa_level users
     * 
     * Consolidates menu cleanup, submenu management, and plugin menu removal
     * into a single method for better performance and maintainability.
     */
    public static function manage_admin_interface()
    {
        if (!self::is_wa_user()) {
            return;
        }

        global $menu, $submenu;

        // Use whitelist-based approach for scalable menu management
        self::enforce_menu_whitelist($menu, $submenu);
    }

    /**
     * Final aggressive menu cleanup as a safety net
     * 
     * This runs at priority 9999 to catch any menus that might have been
     * added after our initial cleanup. Uses the same whitelist approach.
     */
    public static function final_menu_cleanup()
    {
        if (!self::is_wa_user()) {
            return;
        }

        global $menu, $submenu;

        // Use the same whitelist approach for consistency
        $allowed_menu_items = [
            'edit.php?post_type=physicians'
        ];

        // Aggressively remove any non-whitelisted menu items
        if (is_array($menu)) {
            foreach ($menu as $key => $menu_item) {
                if (empty($menu_item[2])) continue;

                if (!in_array($menu_item[2], $allowed_menu_items)) {
                    remove_menu_page($menu_item[2]);
                    unset($menu[$key]);
                }
            }
        }

        // Clean up all submenus except physicians
        if (is_array($submenu)) {
            foreach ($submenu as $parent_slug => $submenu_items) {
                if (!in_array($parent_slug, $allowed_menu_items)) {
                    unset($submenu[$parent_slug]);
                }
            }
        }
    }

    /**
     * Enforce menu whitelist for wa_level users
     * 
     * This is a scalable, efficient approach that only allows specific menu items
     * rather than trying to remove every possible plugin menu individually.
     * 
     * @param array $menu WordPress menu array
     * @param array $submenu WordPress submenu array
     */
    private static function enforce_menu_whitelist($menu, $submenu)
    {
        // Define allowed menu items (whitelist approach)
        $allowed_menu_items = [
            'edit.php?post_type=physicians'  // Only physicians menu allowed
        ];

        // Remove any menu item not in the whitelist
        if (is_array($menu)) {
            foreach ($menu as $key => $menu_item) {
                if (empty($menu_item[2])) continue;

                if (!in_array($menu_item[2], $allowed_menu_items)) {
                    remove_menu_page($menu_item[2]);
                    // Also remove from global menu array for cleanup
                    unset($menu[$key]);
                }
            }
        }

        // Clean up submenus - only keep physicians submenu
        if (is_array($submenu)) {
            foreach ($submenu as $parent_slug => $submenu_items) {
                if (!in_array($parent_slug, $allowed_menu_items)) {
                    unset($submenu[$parent_slug]);
                }
            }
        }

        // Setup allowed submenu items for physicians
        self::setup_physicians_submenu($submenu);
    }

    /**
     * Remove core WordPress menu items
     */
    private static function remove_core_wordpress_menus()
    {
        $core_menus_to_remove = [
            'index.php',                    // Dashboard
            'edit.php',                     // Posts  
            'upload.php',                   // Media
            'edit.php?post_type=page',      // Pages
            'edit-comments.php',            // Comments
            'themes.php',                   // Appearance
            'plugins.php',                  // Plugins
            'users.php',                    // Users
            'tools.php',                    // Tools
            'options-general.php',          // Settings
            'separator1',                   // Separators
            'separator2',
            'separator-last',
        ];

        foreach ($core_menus_to_remove as $menu_slug) {
            remove_menu_page($menu_slug);
        }
    }

    /**
     * Setup physicians submenu with allowed items
     * 
     * @param array $submenu WordPress submenu array (passed by reference in the calling method)
     */
    private static function setup_physicians_submenu(&$submenu)
    {
        // Clean up existing physicians submenu
        if (isset($submenu['edit.php?post_type=physicians'])) {
            $allowed_submenu_items = [
                'edit.php?post_type=physicians',
                'post-new.php?post_type=physicians',
                'wa-home-page',
                'wa-my-account',
                'wa-logout'
            ];

            foreach ($submenu['edit.php?post_type=physicians'] as $key => $submenu_item) {
                if (!in_array($submenu_item[2], $allowed_submenu_items)) {
                    unset($submenu['edit.php?post_type=physicians'][$key]);
                }
            }
        }

        // Add custom submenu items
        add_submenu_page(
            'edit.php?post_type=physicians',
            __('CSACI Home Page'),
            __('CSACI Home Page'),
            'read',
            'wa-home-page',
            [__CLASS__, 'render_home_page_redirect']
        );

        add_submenu_page(
            'edit.php?post_type=physicians',
            __('CSACI My Account Page'),
            __('CSACI My Account Page'),
            'read',
            'wa-my-account',
            [__CLASS__, 'render_my_account_redirect']
        );

        add_submenu_page(
            'edit.php?post_type=physicians',
            __('Logout'),
            __('Logout'),
            'read',
            'wa-logout',
            [__CLASS__, 'render_logout_redirect']
        );

        // Remove "Add New" if user already has a post (enforces one-post limit)
        $existing_posts = self::get_user_physicians_posts();
        if (!empty($existing_posts)) {
            remove_submenu_page('edit.php?post_type=physicians', 'post-new.php?post_type=physicians');
        }
    }

    /**
     * Render home page redirect for wa_level users
     * 
     * This method handles the home page link functionality by redirecting
     * wa_level users to the website's home page when they click the link.
     */
    public static function render_home_page_redirect()
    {
        // Redirect to home page
        wp_redirect(home_url());
        exit;
    }

    /**
     * Render my account redirect for wa_level users
     * 
     * This method handles the my account link functionality by redirecting
     * wa_level users to the CSACI my account page when they click the link.
     */
    public static function render_my_account_redirect()
    {
        // Redirect to my account page using current site domain
        $my_account_url = home_url('/my-account-wa/');
        wp_redirect($my_account_url);
        exit;
    }

    /**
     * Render logout redirect for wa_level users
     * 
     * This method handles the logout link functionality by logging out
     * wa_level users and redirecting them to the home page.
     */
    public static function render_logout_redirect()
    {
        // Log out the user and redirect to home page
        wp_logout();
        wp_redirect(home_url());
        exit;
    }

    /**
     * Hide UI elements via CSS and JavaScript (main coordinator method)
     */
    public static function hide_ui_elements()
    {
        if (!self::is_wa_user()) {
            return;
        }

        // Remove admin notices and output menu changes
        self::hide_admin_notices();
        self::output_menu_text_changes();

        // Apply page-specific UI modifications
        global $pagenow;

        if ($pagenow == 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] == 'physicians') {
            self::hide_physicians_list_page_elements();
        }

        if (in_array($pagenow, ['post.php', 'post-new.php']) && self::is_physicians_page()) {
            self::hide_physicians_edit_page_elements();
        }
    }

    /**
     * Remove admin notices for cleaner interface
     */
    private static function hide_admin_notices()
    {
        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');
        remove_all_actions('network_admin_notices');
    }

    /**
     * Hide UI elements on physicians list page
     */
    private static function hide_physicians_list_page_elements()
    {
        $existing_posts = self::get_user_physicians_posts();

        $list_page_css = self::get_common_css() . '
            /* Hide search box for all wa_level users on physicians page */
            .search-box {
                display: none !important;
            }
        ';

        if (!empty($existing_posts)) {
            $list_page_css .= '
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
            ';
        }

        echo '<style>' . $list_page_css . '</style>';
    }

    /**
     * Hide UI elements on physicians edit pages
     */
    private static function hide_physicians_edit_page_elements()
    {
        $edit_page_css = self::get_common_css() . '
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
        ';

        echo '<style>' . $edit_page_css . '</style>';
        self::output_permalink_removal_script();
    }

    /**
     * Get common CSS that applies to all physicians pages
     * 
     * @return string Common CSS rules
     */
    private static function get_common_css()
    {
        return '
            /* Hide collapse menu button for wa_level users on physicians pages */
            #collapse-button,
            #collapse-menu {
                display: none !important;
            }
        ';
    }

    /**
     * Output JavaScript to remove permalink editing functionality
     */
    private static function output_permalink_removal_script()
    {
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

    /**
     * Output JavaScript for menu text changes (centralized for efficiency)
     * 
     * @since 1.0.0
     */
    private static function output_menu_text_changes()
    {
        echo '<script>
            jQuery(document).ready(function($) {
                function updateMenuTexts() {
                    // Change "Allergists" to "My Allergist Profile" in the admin menu
                    $("#adminmenu a[href*=\'edit.php?post_type=physicians\'] .wp-menu-name").text("My Allergist Profile");
                    
                    // Change "All Allergists" to "My Allergist Profile" in submenu items
                    $("#adminmenu a[href*=\'edit.php?post_type=physicians\']:contains(\'All Allergists\')").text("My Allergist Profile");
                    
                    // Change "Allergists" to "My Allergist Profile" in page heading
                    $("h1.wp-heading-inline:contains(\'Allergists\')").text("My Allergist Profile");
                    
                    // Change "Edit Allergist" to "Edit My Allergist Profile" in page heading
                    $("h1.wp-heading-inline:contains(\'Edit Allergist\')").text("Edit My Allergist Profile");
                }
                
                // Initial execution and handle dynamic loading
                updateMenuTexts();
                setTimeout(updateMenuTexts, 500);
            });
        </script>';
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
     * Remove quick edit functionality for wa_level users
     * 
     * Prevents wa_level users from using inline editing which could bypass
     * security restrictions and validation rules.
     * Only removes quick edit, keeps delete/trash functionality.
     * 
     * @param array $actions Row actions for the post
     * @param WP_Post $post Current post object
     * @return array Modified actions array
     */
    public static function remove_quick_edit_for_wa_users($actions, $post)
    {
        // Only apply to physicians posts and wa_level users
        if ($post->post_type === 'physicians' && self::is_wa_user()) {
            // Remove only the quick edit action, keep delete/trash actions
            unset($actions['inline hide-if-no-js']);

            // Ensure wa_level users can only see actions for their own posts
            if ($post->post_author != get_current_user_id()) {
                // Remove all actions for posts that don't belong to the user
                return [];
            }
        }
        return $actions;
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
     * Hide post status views for wa_level users
     * 
     * Removes the "All | Mine | Published | Drafts | Private" filters from the physicians list page.
     * 
     * @param array $views Array of view links
     * @return array Empty array if wa_level user, otherwise original views
     */
    public static function hide_post_status_views_for_wa_users($views)
    {
        if (self::is_wa_user()) {
            // Remove all views (All, Mine, Published, Drafts, Private)
            return [];
        }
        return $views;
    }

    /**
     * Modify physicians list table columns for wa_level users
     * 
     * Removes unnecessary columns like Author, Date, and Yoast SEO links
     * to provide a cleaner interface for wa_level users.
     * 
     * @param array $columns Array of column headers
     * @return array Modified columns array
     */
    public static function modify_physicians_columns_for_wa_users($columns)
    {
        if (self::is_wa_user()) {
            // Remove author column (wa_level users only see their own posts anyway)
            unset($columns['author']);

            // Remove date column (simplifies the interface)
            unset($columns['date']);

            // Remove Yoast SEO columns (try different possible names)
            unset($columns['wpseo-links']);
            unset($columns['wpseo-linked']);
            unset($columns['wpseo_links']);
            unset($columns['wpseo_linked']);
            unset($columns['yoast-seo-links']);
            unset($columns['yoast-seo-linked']);

            // Keep checkbox column for bulk actions like delete
            // Keep title column for editing posts
            // Keep other essential columns
        }
        return $columns;
    }

    /**
     * Handle bulk delete operations for wa_level users
     * 
     * Security measure to ensure wa_level users can only delete their own posts.
     * 
     * @param string $redirect_to
     * @param string $doaction
     * @param array $post_ids
     * @return string
     */
    public static function handle_bulk_delete($redirect_to, $doaction, $post_ids)
    {
        if (!self::is_wa_user() || $doaction !== 'trash') {
            return $redirect_to;
        }

        $current_user_id = get_current_user_id();
        $deleted_count = 0;

        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);

            // Only allow deletion of user's own physicians posts
            if ($post && $post->post_type === 'physicians' && $post->post_author == $current_user_id) {
                if (wp_trash_post($post_id)) {
                    $deleted_count++;
                }
            }
        }

        if ($deleted_count > 0) {
            $redirect_to = add_query_arg('trashed', $deleted_count, $redirect_to);
        }

        return $redirect_to;
    }
}
