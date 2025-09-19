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
        add_action('admin_init', [__CLASS__, 'force_wa_capabilities'], 999);

        // Additional capability check for publishing specifically
        add_filter('user_has_cap', [__CLASS__, 'ensure_publishing_capabilities'], 999, 4);

        // Keep only essential restrictions
        add_action('pre_get_posts', [__CLASS__, 'restrict_posts_query']);

        // Re-enable remaining restrictions now that delete is working
        add_filter('wp_insert_post_data', [__CLASS__, 'prevent_unauthorized_changes'], 10, 2);
        add_filter('user_has_cap', [__CLASS__, 'restrict_duplicate_posts'], 10, 4);
        add_action('save_post', [__CLASS__, 'validate_physicians_post'], 10, 3);
        add_action('admin_menu', [__CLASS__, 'modify_admin_interface'], 999);
        add_action('admin_head', [__CLASS__, 'hide_ui_elements']);
        add_filter('bulk_actions-edit-physicians', [__CLASS__, 'modify_bulk_actions']);
        add_filter('handle_bulk_actions-edit-physicians', [__CLASS__, 'handle_bulk_delete'], 10, 3);

        // Re-enable page access restrictions (FIXED TO ALLOW DELETE)
        add_action('admin_init', [__CLASS__, 'block_restricted_pages'], 1);
        add_action('current_screen', [__CLASS__, 'validate_admin_access'], 1);

        // Re-enable AJAX restrictions and column/row action modifications
        add_action('wp_ajax_inline-save', [__CLASS__, 'block_unauthorized_ajax'], 1);
        add_action('wp_ajax_sample-permalink', [__CLASS__, 'block_unauthorized_ajax'], 1);
        add_filter('manage_physicians_posts_columns', [__CLASS__, 'modify_post_columns'], 999);
        add_filter('post_row_actions', [__CLASS__, 'remove_quick_edit_for_wa_users'], 10, 2);

        // Re-enable safe UI restrictions that shouldn't affect delete
        add_action('admin_head', [__CLASS__, 'remove_help_tab']);
        add_filter('views_edit-physicians', [__CLASS__, 'hide_post_status_views']);
        add_filter('months_dropdown_results', [__CLASS__, 'remove_date_filter_dropdown'], 10, 2);
        add_action('add_meta_boxes', [__CLASS__, 'remove_meta_boxes'], 999);
        add_filter('screen_options_show_screen', [__CLASS__, 'hide_screen_options'], 10, 2);

        // Re-enable admin bar restrictions (cosmetic only)
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
            'publish_physician',
            'create_physicians',
            'edit_published_physicians',
            // Additional capabilities that might be needed for delete operations
            'delete_others_physicians', // Even though they can't use it, having it might help
            'edit_others_physicians',   // Same as above
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
        // Targeted approach: grant only necessary capabilities for wa_level users
        if (!is_admin() || !self::is_wa_user($user)) {
            return $allcaps;
        }

        // Essential capabilities for physicians post management and publishing
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
            // Additional capabilities for publishing functionality
            'edit_others_posts',
            'delete_others_posts',
            'read_private_posts',
            'edit_private_posts',
            'delete_private_posts',
            'publish_private_posts'
        ];

        foreach ($essential_caps as $cap) {
            $allcaps[$cap] = true;
        }

        // Special handling for specific capability checks that WordPress performs during publishing
        if (!empty($caps)) {
            foreach ($caps as $cap) {
                if (in_array($cap, ['publish_posts', 'publish_physicians', 'publish_physician'])) {
                    $allcaps[$cap] = true;
                }
                if (in_array($cap, ['edit_posts', 'edit_physicians', 'edit_physician'])) {
                    $allcaps[$cap] = true;
                }
            }
        }

        return $allcaps;
    }

    /**
     * Force capabilities for wa_level users on admin pages
     * This runs late to ensure capabilities are set
     */
    public static function force_wa_capabilities()
    {
        if (!is_admin() || !self::is_wa_user()) {
            return;
        }

        $current_user = wp_get_current_user();
        if (!$current_user || !$current_user->exists()) {
            return;
        }

        // Force add essential capabilities directly to the user object
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
            'edit_published_physicians'
        ];

        foreach ($essential_caps as $cap) {
            $current_user->add_cap($cap);
        }
    }

    /**
     * Ensure publishing capabilities are always available for wa_level users
     * This runs late to override any restrictions from other plugins
     * 
     * @param array $allcaps All capabilities
     * @param array $caps Capabilities being checked
     * @param array $args Arguments
     * @param WP_User $user User object
     * @return array
     */
    public static function ensure_publishing_capabilities($allcaps, $caps, $args, $user)
    {
        if (!self::is_wa_user($user)) {
            return $allcaps;
        }

        // Critical publishing capabilities that must always be true for wa_level users
        $publishing_caps = [
            'publish_posts',
            'publish_physicians',
            'publish_physician',
            'edit_posts',
            'edit_physicians',
            'edit_physician',
            'edit_published_posts',
            'edit_published_physicians'
        ];

        // If any publishing-related capability is being checked, ensure it's granted
        if (!empty($caps)) {
            foreach ($caps as $cap) {
                if (in_array($cap, $publishing_caps)) {
                    $allcaps[$cap] = true;
                }
            }
        }

        // Always ensure these capabilities are available
        foreach ($publishing_caps as $cap) {
            $allcaps[$cap] = true;
        }

        return $allcaps;
    }

    /*
     * DEBUGGING METHODS - Commented out but kept for future troubleshooting
     * These were used to diagnose and fix the publishing issue for WA users
     * Uncomment and re-enable the hooks in init() if debugging is needed again
     */

    /**
     * Debug publishing attempts (for troubleshooting)
     * 
     * @param int $post_id Post ID
     * @param WP_Post $post Post object  
     * @param bool $update Whether this is an update
     */
    /*
    public static function debug_publishing($post_id, $post, $update)
    {
        if ($post->post_type !== 'physicians' || !self::is_wa_user($post->post_author)) {
            return;
        }

        // Log publishing attempts for debugging
        $debug_msg = 'WA User Publishing Debug: Post ID ' . $post_id . ', Status: ' . $post->post_status . ', Update: ' . ($update ? 'yes' : 'no');
        error_log($debug_msg);

        // Also write to a file in the plugin directory for easier debugging
        $debug_file = dirname(__FILE__) . '/../debug.log';
        file_put_contents($debug_file, date('Y-m-d H:i:s') . ' - ' . $debug_msg . "\n", FILE_APPEND);

        // Check current user capabilities
        $current_user = wp_get_current_user();
        if ($current_user && $current_user->ID == $post->post_author) {
            $caps_to_check = ['publish_posts', 'publish_physicians', 'publish_physician'];
            foreach ($caps_to_check as $cap) {
                $has_cap = current_user_can($cap) ? 'YES' : 'NO';
                $cap_msg = 'WA User Publishing Debug: User has ' . $cap . ': ' . $has_cap;
                error_log($cap_msg);
                file_put_contents($debug_file, date('Y-m-d H:i:s') . ' - ' . $cap_msg . "\n", FILE_APPEND);
            }
        }
    }
    /**
     * Debug status transitions (temporary)
     * 
     * @param string $new_status New post status
     * @param string $old_status Old post status
     * @param WP_Post $post Post object
     */
    public static function debug_status_transition($new_status, $old_status, $post)
    {
        if ($post->post_type !== 'physicians' || !self::is_wa_user($post->post_author)) {
            return;
        }

        $debug_msg = 'WA User Status Transition: Post ID ' . $post->ID . ', From: ' . $old_status . ' To: ' . $new_status;
        error_log($debug_msg);

        // Also write to debug file
        $debug_file = dirname(__FILE__) . '/../debug.log';
        file_put_contents($debug_file, date('Y-m-d H:i:s') . ' - ' . $debug_msg . "\n", FILE_APPEND);

        // If trying to publish, check capabilities at this moment
        if ($new_status === 'publish') {
            $current_user = wp_get_current_user();
            if ($current_user && $current_user->ID == $post->post_author) {
                $can_publish = current_user_can('publish_posts') && current_user_can('publish_physicians');
                $cap_msg = 'WA User Status Transition: Can publish = ' . ($can_publish ? 'YES' : 'NO');
                error_log($cap_msg);
                file_put_contents($debug_file, date('Y-m-d H:i:s') . ' - ' . $cap_msg . "\n", FILE_APPEND);
            }
        }
    }

    /**
     * Add a test button to check publishing capabilities (temporary debugging)
     */
    public static function add_publish_test_button()
    {
        if (!self::is_wa_user() || !self::is_physicians_page()) {
            return;
        }

        $debug_file = dirname(__FILE__) . '/../debug.log';

?>
        <script>
            function testPublishCapabilities() {
                // Send AJAX request to test capabilities
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=test_wa_publish_caps'
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log('Capability test results:', data);
                        alert('Capability test completed. Check debug.log file in plugin directory.');
                    });
            }
        </script>
        <div style="position: fixed; top: 50px; right: 20px; z-index: 9999; background: #fff; border: 1px solid #ccc; padding: 10px;">
            <button onclick="testPublishCapabilities()" style="background: #0073aa; color: white; padding: 5px 10px; border: none; cursor: pointer;">
                Test Publish Capabilities
            </button>
            <br><small>Debug file: <?php echo $debug_file; ?></small>
        </div>
<?php
    }

    /**
     * AJAX handler to test publish capabilities (temporary debugging)
     */
    public static function test_publish_capabilities_ajax()
    {
        if (!self::is_wa_user()) {
            wp_die('Not a WA user');
        }

        $debug_file = dirname(__FILE__) . '/../debug.log';
        $current_user = wp_get_current_user();

        $results = [
            'user_id' => $current_user->ID,
            'user_roles' => $current_user->roles,
            'capabilities' => []
        ];

        // Test all publishing-related capabilities
        $caps_to_test = [
            'publish_posts',
            'publish_physicians',
            'publish_physician',
            'edit_posts',
            'edit_physicians',
            'edit_physician',
            'edit_published_posts',
            'edit_published_physicians',
            'create_physicians'
        ];

        foreach ($caps_to_test as $cap) {
            $has_cap = current_user_can($cap);
            $results['capabilities'][$cap] = $has_cap;

            $debug_msg = 'WA User Capability Test: ' . $cap . ' = ' . ($has_cap ? 'YES' : 'NO');
            file_put_contents($debug_file, date('Y-m-d H:i:s') . ' - ' . $debug_msg . "\n", FILE_APPEND);
        }

        // Also test meta capabilities with a sample post
        $user_posts = get_posts([
            'post_type' => 'physicians',
            'author' => $current_user->ID,
            'numberposts' => 1,
            'post_status' => ['draft', 'publish']
        ]);

        if (!empty($user_posts)) {
            $post = $user_posts[0];
            $meta_caps_to_test = ['publish_post', 'edit_post'];

            foreach ($meta_caps_to_test as $cap) {
                $has_cap = current_user_can($cap, $post->ID);
                $results['meta_capabilities'][$cap] = $has_cap;

                $debug_msg = 'WA User Meta Capability Test: ' . $cap . ' (post ' . $post->ID . ') = ' . ($has_cap ? 'YES' : 'NO');
                file_put_contents($debug_file, date('Y-m-d H:i:s') . ' - ' . $debug_msg . "\n", FILE_APPEND);
            }
        }

        wp_send_json_success($results);
    }

    // END OF DEBUGGING METHODS */

    /**
     */
    public static function debug_save_post($post_id, $post, $update)
    {
        if ($post->post_type !== 'physicians' || !self::is_wa_user($post->post_author)) {
            return;
        }

        $debug_msg = 'WA User Save Post: Post ID ' . $post_id . ', Status: ' . $post->post_status . ', Update: ' . ($update ? 'yes' : 'no') . ', Author: ' . $post->post_author;
        error_log($debug_msg);

        // Also write to debug file
        $debug_file = dirname(__FILE__) . '/../debug.log';
        file_put_contents($debug_file, date('Y-m-d H:i:s') . ' - ' . $debug_msg . "\n", FILE_APPEND);

        // Check if this is a publish attempt
        if (isset($_POST['post_status']) && $_POST['post_status'] === 'publish') {
            $publish_msg = 'WA User Save Post: PUBLISH ATTEMPT detected via $_POST[post_status]';
            error_log($publish_msg);
            file_put_contents($debug_file, date('Y-m-d H:i:s') . ' - ' . $publish_msg . "\n", FILE_APPEND);
        }

        // Log some $_POST data to see what's being submitted
        if (isset($_POST['action'])) {
            $action_msg = 'WA User Save Post: Action = ' . $_POST['action'];
            error_log($action_msg);
            file_put_contents($debug_file, date('Y-m-d H:i:s') . ' - ' . $action_msg . "\n", FILE_APPEND);
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

        // Log capability checks for debugging
        if (!empty($args) && isset($args[0])) {
            $post = get_post($args[0]);
            if ($post && $post->post_type === 'physicians') {
                error_log('WA User Meta Cap Check: ' . $cap . ' for post ' . $post->ID . ' by user ' . $user_id);
            }
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
                        error_log('WA User Meta Cap: Granting delete_physicians for ' . $cap);
                        return ['delete_physicians'];
                    } elseif (in_array($cap, ['publish_post', 'publish_physician'])) {
                        error_log('WA User Meta Cap: Granting publish_physicians for ' . $cap);
                        return ['publish_physicians'];
                    } elseif (in_array($cap, ['read_post', 'read_physician'])) {
                        return ['read'];
                    } else {
                        error_log('WA User Meta Cap: Granting edit_physicians for ' . $cap);
                        return ['edit_physicians'];
                    }
                } else {
                    // Deny access to others' posts
                    error_log('WA User Meta Cap: Denying access to others post for ' . $cap);
                    return ['do_not_allow'];
                }
            }
        }

        // For bulk operations or when no specific post ID, allow if user has general capability
        if (in_array($cap, $post_caps) && empty($args)) {
            if (in_array($cap, ['delete_post', 'delete_published_post'])) {
                return ['delete_physicians'];
            } elseif (in_array($cap, ['publish_post'])) {
                error_log('WA User Meta Cap: Granting publish_physicians for bulk ' . $cap);
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
     * Restrict duplicate post creation (but allow publishing of existing drafts)
     * 
     * @param array $allcaps All capabilities
     * @param array $caps Capabilities being checked
     * @param array $args Arguments
     * @param WP_User $user User object
     * @return array
     */
    public static function restrict_duplicate_posts($allcaps, $caps, $args, $user)
    {
        // Only restrict creation of new posts, not publishing of existing ones
        if (in_array('create_physicians', $caps) && self::is_wa_user($user)) {
            if (!self::can_create_physicians_post($user->ID)) {
                $allcaps['create_physicians'] = false;
            }
        }

        // Always allow publishing of existing posts - don't restrict publish capabilities here
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
                'post-new.php?post_type=physicians',
                'wa-home-page', // Add our home page link to allowed items
                'wa-my-account' // Add our my account link to allowed items
            ];

            foreach ($submenu['edit.php?post_type=physicians'] as $key => $submenu_item) {
                if (!in_array($submenu_item[2], $allowed_submenu_items)) {
                    unset($submenu['edit.php?post_type=physicians'][$key]);
                }
            }
        }

        // Add home page link to physicians submenu
        add_submenu_page(
            'edit.php?post_type=physicians',
            __('CSACI Home Page'),
            __('CSACI Home Page'),
            'read',
            'wa-home-page',
            [__CLASS__, 'render_home_page_redirect']
        );

        // Add my account link to physicians submenu
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

        // Output JavaScript for menu text changes (applies to all physicians pages)
        self::output_menu_text_changes();

        global $pagenow;

        // Common CSS that applies to all physicians pages
        $common_css = '
            /* Hide collapse menu button for wa_level users on physicians pages */
            #collapse-button,
            #collapse-menu {
                display: none !important;
            }
        ';

        // Page-specific CSS and functionality
        if ($pagenow == 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] == 'physicians') {
            $existing_posts = self::get_user_physicians_posts();

            $list_page_css = $common_css . '
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

        // Hide author and slug elements on individual post editing pages
        if (in_array($pagenow, ['post.php', 'post-new.php']) && self::is_physicians_page()) {
            $edit_page_css = $common_css . '
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
                
                // Initial execution
                updateMenuTexts();
                
                // Handle dynamic loading
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
