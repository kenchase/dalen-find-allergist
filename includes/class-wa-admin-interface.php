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

        // Remove meta boxes for wa_level users
        add_action('add_meta_boxes', [__CLASS__, 'remove_meta_boxes_for_wa_users'], 999);

        // Robust slug and author field removal for wa_level users
        add_action('admin_init', [__CLASS__, 'remove_slug_and_author_capabilities'], 999);
        add_filter('user_can_richedit', [__CLASS__, 'modify_editor_capabilities'], 10, 1);
        add_action('admin_enqueue_scripts', [__CLASS__, 'remove_slug_author_functionality'], 999);
        add_filter('get_sample_permalink_html', [__CLASS__, 'remove_permalink_ui'], 10, 5);
        add_action('edit_form_after_title', [__CLASS__, 'remove_permalink_from_edit_form']);
        add_filter('wp_insert_post_data', [__CLASS__, 'prevent_slug_modification'], 10, 2);

        // Robust list page modifications
        add_action('load-edit.php', [__CLASS__, 'modify_list_page_for_wa_users']);
        add_filter('post_type_labels_physicians', [__CLASS__, 'modify_post_type_labels'], 10, 1);
        add_filter('admin_title', [__CLASS__, 'modify_admin_page_titles'], 10, 2);
        add_filter('get_user_option_screen_layout_edit-physicians', [__CLASS__, 'force_single_column_layout']);
        add_action('admin_head', [__CLASS__, 'remove_search_box_for_wa_users']);

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
     * wa_level users and redirecting them to the my-account-wa page.
     */
    public static function render_logout_redirect()
    {
        // Log out the user and redirect to my account page
        wp_logout();
        $my_account_url = home_url('/my-account-wa/');
        wp_redirect($my_account_url);
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
     * Note: Search box and Add New button removal is now handled by robust WordPress hooks
     */
    private static function hide_physicians_list_page_elements()
    {
        // Only include the common CSS (collapse button hiding)
        // Search and Add New button hiding is now handled by WordPress-level hooks
        $list_page_css = self::get_common_css();

        echo '<style>' . $list_page_css . '</style>';
    }

    /**
     * Hide UI elements on physicians edit pages
     * Note: Slug and author hiding is now handled by robust WordPress hooks
     */
    private static function hide_physicians_edit_page_elements()
    {
        // Only include the common CSS (collapse button hiding)
        // Slug and author hiding is now handled by WordPress-level hooks
        $edit_page_css = self::get_common_css();

        echo '<style>' . $edit_page_css . '</style>';

        // The permalink removal script is no longer needed as it's handled by 
        // the remove_slug_author_functionality method with proper WordPress hooks
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
                console.log("WA Interface: Starting slug/author removal for physicians");
                
                function removeSlugAndAuthorElements() {
                    // Remove slug elements
                    $("#edit-slug-box, .edit-slug, #post-slug-edit, #edit-slug-buttons, .edit-slug-buttons").remove();
                    $("#sample-permalink a").off("click");
                    $("#slugdiv").remove();
                    
                    // Remove author elements
                    $(".misc-pub-post-author, .misc-pub-section.misc-pub-author, #authordiv").remove();
                    
                    // Remove any parent sections that might be empty
                    $(".misc-pub-section").each(function() {
                        if ($(this).find(".misc-pub-post-author").length > 0) {
                            $(this).remove();
                        }
                    });
                    
                    console.log("WA Interface: Removed elements - slug boxes:", $("#edit-slug-box").length, "author sections:", $(".misc-pub-post-author").length);
                }
                
                // Initial execution
                removeSlugAndAuthorElements();
                
                // Handle dynamic content with multiple intervals
                setTimeout(removeSlugAndAuthorElements, 500);
                setTimeout(removeSlugAndAuthorElements, 1000);
                setTimeout(removeSlugAndAuthorElements, 2000);
                
                // Watch for DOM changes (for AJAX-loaded content)
                if (window.MutationObserver) {
                    var observer = new MutationObserver(function(mutations) {
                        var shouldRerun = false;
                        mutations.forEach(function(mutation) {
                            if (mutation.addedNodes.length > 0) {
                                shouldRerun = true;
                            }
                        });
                        if (shouldRerun) {
                            setTimeout(removeSlugAndAuthorElements, 100);
                        }
                    });
                    
                    observer.observe(document.body, {
                        childList: true,
                        subtree: true
                    });
                }
            });
        </script>';
    }

    /**
     * Output JavaScript for menu text changes (centralized for efficiency)
     * Note: Most text changes are now handled by robust WordPress hooks (post_type_labels, admin_title)
     * This is kept as a fallback for any edge cases
     * 
     * @since 1.0.0
     */
    private static function output_menu_text_changes()
    {
        // Most menu text changes are now handled by modify_post_type_labels() and modify_admin_page_titles()
        // This method is kept minimal as a fallback
        echo '<script>
            jQuery(document).ready(function($) {
                // Fallback text changes for any elements not caught by WordPress hooks
                function updateMenuTexts() {
                    // Only handle specific edge cases that WordPress hooks might miss
                    $("a[href*=\'edit.php?post_type=physicians\']:contains(\'Allergists\')").each(function() {
                        if ($(this).text() === "Allergists") {
                            $(this).text("My Allergist Profile");
                        }
                    });
                }
                
                // Single execution - most changes handled by WordPress hooks now
                updateMenuTexts();
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

    /**
     * Remove meta boxes that contain slug and author fields for wa_level users
     */
    public static function remove_meta_boxes_for_wa_users()
    {
        if (!self::is_wa_user()) {
            return;
        }

        global $pagenow;

        // Only apply on post edit pages
        if (!in_array($pagenow, ['post.php', 'post-new.php'])) {
            return;
        }

        // Check if we're editing a physicians post
        if (self::is_physicians_page()) {
            // Remove author meta box
            remove_meta_box('authordiv', 'physicians', 'normal');
            remove_meta_box('authordiv', 'physicians', 'side');
            remove_meta_box('authordiv', 'physicians', 'advanced');

            // Remove slug meta box if it exists
            remove_meta_box('slugdiv', 'physicians', 'normal');
            remove_meta_box('slugdiv', 'physicians', 'side');
            remove_meta_box('slugdiv', 'physicians', 'advanced');

            // Also try to remove for any post type (in case of edge cases)
            remove_meta_box('authordiv', 'post', 'normal');
            remove_meta_box('authordiv', 'post', 'side');
            remove_meta_box('authordiv', 'post', 'advanced');
            remove_meta_box('slugdiv', 'post', 'normal');
            remove_meta_box('slugdiv', 'post', 'side');
            remove_meta_box('slugdiv', 'post', 'advanced');
        }
    }

    /**
     * Remove slug and author capabilities and UI elements for wa_level users
     * This is a more robust approach than CSS hiding
     * 
     * IMPORTANT: Only affects wa_level users, administrators and other users are unaffected
     */
    public static function remove_slug_and_author_capabilities()
    {
        if (!self::is_wa_user()) {
            return;
        }

        global $pagenow;

        // Only apply on post edit pages
        if (!in_array($pagenow, ['post.php', 'post-new.php'])) {
            return;
        }

        // Check if we're editing a physicians post
        if (self::is_physicians_page()) {
            // Remove the author dropdown functionality
            add_filter('wp_dropdown_users_args', [__CLASS__, 'remove_author_dropdown'], 10, 1);

            // Remove author-related capabilities temporarily for wa_level users only
            $current_user = wp_get_current_user();
            if ($current_user && self::is_wa_user($current_user)) {
                // Only remove capabilities for wa_level users, not administrators
                if (!user_can($current_user, 'manage_options')) {
                    $current_user->remove_cap('edit_others_posts');
                    $current_user->remove_cap('edit_others_pages');
                }
            }
        }
    }

    /**
     * Remove author dropdown arguments for wa_level users
     */
    public static function remove_author_dropdown($args)
    {
        if (self::is_wa_user() && self::is_physicians_page()) {
            // Return empty args to prevent dropdown from showing
            return [];
        }
        return $args;
    }

    /**
     * Modify editor capabilities for wa_level users
     */
    public static function modify_editor_capabilities($can_richedit)
    {
        if (self::is_wa_user() && self::is_physicians_page()) {
            // Additional capability modifications can go here
            return $can_richedit;
        }
        return $can_richedit;
    }

    /**
     * Remove slug and author functionality via JavaScript - more targeted than CSS
     */
    public static function remove_slug_author_functionality()
    {
        if (!self::is_wa_user()) {
            return;
        }

        global $pagenow;

        // Only apply on post edit pages for physicians
        if (in_array($pagenow, ['post.php', 'post-new.php']) && self::is_physicians_page()) {
            wp_add_inline_script('jquery', '
                jQuery(document).ready(function($) {
                    // Remove permalink edit functionality completely
                    $("#edit-slug-box").remove();
                    $("#sample-permalink a").removeAttr("href").off("click");
                    $(".edit-slug").remove();
                    $("#post-slug-edit, #edit-slug-buttons").remove();
                    
                    // Remove author meta box completely
                    $("#authordiv").remove();
                    $(".misc-pub-post-author").parent().remove();
                    
                    // Disable any remaining permalink editing
                    $(document).on("click", "#sample-permalink a, .edit-slug", function(e) {
                        e.preventDefault();
                        return false;
                    });
                    
                    // Watch for dynamically added content
                    var observer = new MutationObserver(function(mutations) {
                        $("#edit-slug-box, .edit-slug, #authordiv, .misc-pub-post-author").remove();
                        $("#sample-permalink a").removeAttr("href").off("click");
                    });
                    
                    observer.observe(document.body, {
                        childList: true,
                        subtree: true
                    });
                });
            ');
        }
    }

    /**
     * Remove permalink UI completely for wa_level users
     */
    public static function remove_permalink_ui($return, $post_id, $new_title, $new_slug, $post)
    {
        if (self::is_wa_user() && $post && $post->post_type === 'physicians') {
            return ''; // Return empty string to remove permalink UI
        }
        return $return;
    }

    /**
     * Remove permalink from edit form
     */
    public static function remove_permalink_from_edit_form($post)
    {
        if (self::is_wa_user() && $post && $post->post_type === 'physicians') {
            // Remove the permalink editing section entirely
            echo '<style>#edit-slug-box, #sample-permalink { display: none !important; }</style>';
            echo '<script>
                jQuery(document).ready(function($) {
                    $("#edit-slug-box, #sample-permalink").remove();
                });
            </script>';
        }
    }

    /**
     * Prevent slug modification by wa_level users
     */
    public static function prevent_slug_modification($data, $postarr)
    {
        if (self::is_wa_user() && isset($data['post_type']) && $data['post_type'] === 'physicians') {
            // If this is an update to an existing post, preserve the original slug
            if (!empty($postarr['ID'])) {
                $original_post = get_post($postarr['ID']);
                if ($original_post) {
                    $data['post_name'] = $original_post->post_name;
                }
            }
        }
        return $data;
    }

    /**
     * Modify list page functionality for wa_level users using WordPress hooks
     */
    public static function modify_list_page_for_wa_users()
    {
        if (!self::is_wa_user()) {
            return;
        }

        // Only apply to physicians list page
        if (!isset($_GET['post_type']) || $_GET['post_type'] !== 'physicians') {
            return;
        }

        // Remove "Add New" capability temporarily for list page
        $existing_posts = self::get_user_physicians_posts();
        if (!empty($existing_posts)) {
            $current_user = wp_get_current_user();
            if ($current_user) {
                // Temporarily remove the capability to create new posts
                add_filter('user_has_cap', [__CLASS__, 'remove_add_new_capability'], 10, 4);
            }
        }
    }

    /**
     * Remove "Add New" capability for wa_level users when they already have posts
     */
    public static function remove_add_new_capability($allcaps, $caps, $args, $user)
    {
        if (!self::is_wa_user($user)) {
            return $allcaps;
        }

        // Only apply on physicians list page when user has existing posts
        if (isset($_GET['post_type']) && $_GET['post_type'] === 'physicians') {
            $existing_posts = self::get_user_physicians_posts();
            if (!empty($existing_posts)) {
                $allcaps['create_physicians'] = false;
                $allcaps['edit_physicians'] = true; // Keep edit capability
            }
        }

        return $allcaps;
    }

    /**
     * Modify post type labels for wa_level users
     */
    public static function modify_post_type_labels($labels)
    {
        if (!self::is_wa_user()) {
            return $labels;
        }

        $labels->name = 'My Allergist Profile';
        $labels->singular_name = 'My Allergist Profile';
        $labels->menu_name = 'My Allergist Profile';
        $labels->all_items = 'My Allergist Profile';
        $labels->edit_item = 'Edit My Allergist Profile';
        $labels->view_item = 'View My Allergist Profile';
        $labels->search_items = 'Search My Profile';
        $labels->not_found = 'Profile not found';
        $labels->not_found_in_trash = 'Profile not found in trash';

        return $labels;
    }

    /**
     * Modify admin page titles for wa_level users
     */
    public static function modify_admin_page_titles($admin_title, $title)
    {
        if (!self::is_wa_user()) {
            return $admin_title;
        }

        global $pagenow;

        if ($pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'physicians') {
            return str_replace('Allergists', 'My Allergist Profile', $admin_title);
        }

        if (in_array($pagenow, ['post.php', 'post-new.php']) && self::is_physicians_page()) {
            $admin_title = str_replace('Edit Allergist', 'Edit My Allergist Profile', $admin_title);
            $admin_title = str_replace('Add New Allergist', 'Add My Allergist Profile', $admin_title);
        }

        return $admin_title;
    }

    /**
     * Force single column layout for wa_level users to simplify interface
     */
    public static function force_single_column_layout($result)
    {
        if (self::is_wa_user()) {
            return 1; // Force single column
        }
        return $result;
    }

    /**
     * Remove search box for wa_level users on physicians list page using CSS
     */
    public static function remove_search_box_for_wa_users()
    {
        if (!self::is_wa_user()) {
            return;
        }

        $screen = get_current_screen();
        if (!$screen || 'edit-physicians' !== $screen->id) {
            return;
        }

        echo '<style>#post-search-input, #search-submit, .search-box { display: none !important; }</style>';
    }
}
