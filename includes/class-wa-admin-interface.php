<?php

/**
 * Wild Apricot Admin Interface Customization
 * 
 * Handles admin interface modifications, menu management, and UI customization
 * for users with roles beginning with "wa_level_".
 * 
 * Key Features:
 * - Admin menu management and cleanup
 * - UI element hiding and customization
 * - Meta box management
 * - List page customizations
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

        // List page customizations
        add_filter('bulk_actions-edit-physicians', [__CLASS__, 'modify_bulk_actions']);
        add_filter('handle_bulk_actions-edit-physicians', [__CLASS__, 'handle_bulk_delete'], 10, 3);
        add_filter('manage_physicians_posts_columns', [__CLASS__, 'modify_post_columns'], 999);
        add_filter('post_row_actions', [__CLASS__, 'remove_quick_edit_for_wa_users'], 10, 2);
        add_filter('views_edit-physicians', [__CLASS__, 'hide_post_status_views']);
        add_filter('months_dropdown_results', [__CLASS__, 'remove_date_filter_dropdown'], 10, 2);

        // Meta boxes and screen options
        add_action('add_meta_boxes', [__CLASS__, 'remove_meta_boxes'], 999);
        add_filter('screen_options_show_screen', [__CLASS__, 'hide_screen_options'], 10, 2);
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
     */
    public static function early_menu_cleanup()
    {
        if (!self::is_wa_user()) {
            return;
        }

        // Remove core WordPress menus immediately
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

        // Remove core WordPress menus first
        self::remove_core_wordpress_menus();

        // Remove third-party plugin menus
        self::remove_third_party_plugin_menus($menu);

        // Setup physicians submenu
        self::setup_physicians_submenu($submenu);

        // Clean up any remaining unauthorized submenus
        self::cleanup_unauthorized_submenus($submenu);

        // Additional aggressive cleanup - remove anything that's not physicians
        if (is_array($menu)) {
            foreach ($menu as $key => $menu_item) {
                if (empty($menu_item[2])) continue;

                // Keep only the physicians menu
                if ($menu_item[2] !== 'edit.php?post_type=physicians') {
                    remove_menu_page($menu_item[2]);
                }
            }
        }
    }

    /**
     * Final aggressive menu cleanup as a safety net
     * 
     * This runs at priority 9999 to catch any menus that might have been
     * added after our initial cleanup.
     */
    public static function final_menu_cleanup()
    {
        if (!self::is_wa_user()) {
            return;
        }

        global $menu, $submenu;

        // Remove any core WordPress menus that might have been re-added
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

        // Aggressively remove any non-physicians menu items
        if (is_array($menu)) {
            foreach ($menu as $key => $menu_item) {
                if (empty($menu_item[2])) continue;

                // Keep only the physicians menu
                if ($menu_item[2] !== 'edit.php?post_type=physicians') {
                    unset($menu[$key]);
                }
            }
        }

        // Clean up all submenus except physicians
        if (is_array($submenu)) {
            foreach ($submenu as $parent_slug => $submenu_items) {
                if ($parent_slug !== 'edit.php?post_type=physicians') {
                    unset($submenu[$parent_slug]);
                }
            }
        }
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
     * Remove third-party plugin menus
     * 
     * @param array $menu WordPress menu array
     */
    private static function remove_third_party_plugin_menus($menu)
    {
        // Known plugin menu patterns to remove
        $plugin_menu_patterns = [
            'admin.php?page=',              // Most plugin admin pages
            'options-general.php?page=',    // Plugin settings pages
            'edit.php?post_type=acf',       // Advanced Custom Fields
            'edit.php?post_type=elementor', // Elementor
            'admin.php?page=wc-',           // WooCommerce
            'admin.php?page=yoast',         // Yoast SEO
            'admin.php?page=gf_',           // Gravity Forms
            'admin.php?page=mailchimp',     // MailChimp
            'admin.php?page=wysija',        // Newsletter plugins
            'admin.php?page=wp-mail',       // Mail plugins
            'edit.php?post_type=shop_',     // Shop/eCommerce plugins
            'edit.php?post_type=product',   // Product post types
            'edit.php?post_type=download',  // Download plugins
        ];

        foreach ($menu as $key => $menu_item) {
            if (empty($menu_item[2])) continue;

            // Skip our physicians menu
            if ($menu_item[2] === 'edit.php?post_type=physicians') {
                continue;
            }

            // Check against known plugin patterns
            foreach ($plugin_menu_patterns as $pattern) {
                if (strpos($menu_item[2], $pattern) !== false) {
                    remove_menu_page($menu_item[2]);
                    break;
                }
            }

            // Remove menus with common plugin indicators
            if (
                strpos($menu_item[2], 'plugin') !== false ||
                strpos($menu_item[2], 'settings') !== false ||
                strpos($menu_item[2], 'config') !== false ||
                strpos($menu_item[2], 'dashboard') !== false
            ) {
                remove_menu_page($menu_item[2]);
            }
        }
    }

    /**
     * Setup physicians submenu with allowed items
     * 
     * @param array $submenu WordPress submenu array
     */
    private static function setup_physicians_submenu($submenu)
    {
        // Clean up existing physicians submenu
        if (isset($submenu['edit.php?post_type=physicians'])) {
            $allowed_submenu_items = [
                'edit.php?post_type=physicians',
                'post-new.php?post_type=physicians',
                'wa-home-page',
                'wa-my-account'
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

        // Remove "Add New" if user already has a post (enforces one-post limit)
        $existing_posts = self::get_user_physicians_posts();
        if (!empty($existing_posts)) {
            remove_submenu_page('edit.php?post_type=physicians', 'post-new.php?post_type=physicians');
        }
    }

    /**
     * Clean up any unauthorized submenu items
     * 
     * @param array $submenu WordPress submenu array
     */
    private static function cleanup_unauthorized_submenus($submenu)
    {
        foreach ($submenu as $parent_slug => $submenu_items) {
            // Keep only physicians submenu
            if ($parent_slug !== 'edit.php?post_type=physicians') {
                unset($submenu[$parent_slug]);
            }
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

        // Add aggressive menu hiding CSS
        self::output_aggressive_menu_hiding_css();

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

                function aggressiveMenuCleanup() {
                    // Hide all menu items except physicians
                    $("#adminmenu > li").each(function() {
                        var $menuItem = $(this);
                        var href = $menuItem.find("a").attr("href");
                        
                        // Show only physicians menu
                        if (href && href.indexOf("edit.php?post_type=physicians") !== -1) {
                            $menuItem.show();
                        } else {
                            $menuItem.hide();
                        }
                    });
                    
                    // Hide specific problematic menu items
                    $("#adminmenu a[href=\'index.php\']").closest("li").hide();
                    $("#adminmenu a[href=\'edit.php\']").closest("li").hide();
                    $("#adminmenu a[href=\'upload.php\']").closest("li").hide();
                    $("#adminmenu a[href=\'edit.php?post_type=page\']").closest("li").hide();
                    $("#adminmenu a[href=\'edit-comments.php\']").closest("li").hide();
                    $("#adminmenu a[href=\'themes.php\']").closest("li").hide();
                    $("#adminmenu a[href=\'plugins.php\']").closest("li").hide();
                    $("#adminmenu a[href=\'users.php\']").closest("li").hide();
                    $("#adminmenu a[href=\'tools.php\']").closest("li").hide();
                    $("#adminmenu a[href=\'options-general.php\']").closest("li").hide();
                    
                    // Hide separators
                    $("#adminmenu .wp-menu-separator").hide();
                }
                
                // Initial execution
                updateMenuTexts();
                aggressiveMenuCleanup();
                
                // Handle dynamic loading - run multiple times to catch late-added menus
                setTimeout(function() {
                    updateMenuTexts();
                    aggressiveMenuCleanup();
                }, 500);
                
                setTimeout(function() {
                    aggressiveMenuCleanup();
                }, 1000);
                
                setTimeout(function() {
                    aggressiveMenuCleanup();
                }, 2000);
            });
        </script>';
    }

    /**
     * Output aggressive CSS to hide any remaining unauthorized menu items
     * 
     * This serves as a final fallback to hide any menu items that might
     * slip through the PHP-based removal methods.
     */
    private static function output_aggressive_menu_hiding_css()
    {
        echo '<style>
            /* Hide all admin menu items except physicians */
            #adminmenu > li:not([class*="wp-menu-open"]):not([class*="physicians"]) {
                display: none !important;
            }
            
            /* Hide specific menu items by href */
            #adminmenu a[href="index.php"],
            #adminmenu a[href="edit.php"],
            #adminmenu a[href="upload.php"],
            #adminmenu a[href="edit.php?post_type=page"],
            #adminmenu a[href="edit-comments.php"],
            #adminmenu a[href="themes.php"],
            #adminmenu a[href="plugins.php"],
            #adminmenu a[href="users.php"],
            #adminmenu a[href="tools.php"],
            #adminmenu a[href="options-general.php"] {
                display: none !important;
            }
            
            /* Hide separator items */
            #adminmenu .wp-menu-separator {
                display: none !important;
            }
            
            /* Show only the physicians menu */
            #adminmenu a[href*="edit.php?post_type=physicians"] {
                display: block !important;
            }
            
            /* Hide all menu items and show only physicians */
            #adminmenu > li {
                display: none !important;
            }
            
            #adminmenu > li:has(a[href*="edit.php?post_type=physicians"]) {
                display: block !important;
            }
            
            /* For browsers that don\'t support :has(), use a more specific approach */
            #menu-posts-physicians {
                display: block !important;
            }
        </style>';
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
     * Modify post list columns
     * 
     * @param array $columns
     * @return array
     */
    public static function modify_post_columns($columns)
    {
        if (self::is_wa_user()) {
            // Keep checkbox column for delete functionality
            // unset($columns['cb']); // Commented out to allow bulk actions like delete

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
        }
        return $columns;
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
     * Modify bulk actions for wa_level users to ensure delete option is available
     * 
     * @param array $actions
     * @return array
     */
    public static function modify_bulk_actions($actions)
    {
        if (self::is_wa_user()) {
            // Ensure trash/delete action is available for wa_level users
            if (!isset($actions['trash'])) {
                $actions['trash'] = __('Move to Trash');
            }
        }
        return $actions;
    }

    /**
     * Handle bulk delete operations for wa_level users
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
}
