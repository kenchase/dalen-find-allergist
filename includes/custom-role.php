<?php

/**
 * Wild Apricot User Role Management for Physicians Post Type
 * 
 * Manages access control and UI restrictions for users with roles beginning 
 * with "wa_level_" when working with the physicians custom post type.
 * 
 * This file now serves as a coordinator that loads and initializes the 
 * separated WA_User_Role and WA_Admin_Interface classes.
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

// Load the separated class files
require_once plugin_dir_path(__FILE__) . 'class-wa-user-role.php';
require_once plugin_dir_path(__FILE__) . 'class-wa-admin-interface.php';

/**
 * WA User Management Coordinator Class
 * 
 * This class maintains backward compatibility by coordinating the 
 * separated WA_User_Role and WA_Admin_Interface classes.
 */
class WA_User_Manager
{
    /**
     * Initialize both the role management and admin interface classes
     * 
     * This method ensures all functionality is properly initialized
     * while maintaining the same entry point as before.
     */
    public static function init()
    {
        // Initialize role management functionality
        WA_User_Role::init();

        // Initialize admin interface functionality
        WA_Admin_Interface::init();
    }

    /**
     * Proxy method for checking if user has wa_level role
     * 
     * Maintains backward compatibility for any external code that
     * might be calling this method directly.
     * 
     * @param WP_User|int|null $user User object, ID, or null for current user
     * @return bool True if user has wa_level role
     */
    public static function is_wa_user($user = null)
    {
        return WA_User_Role::is_wa_user($user);
    }

    /**
     * Proxy method for checking if current page is physicians-related
     * 
     * Maintains backward compatibility for any external code that
     * might be calling this method directly.
     * 
     * @return bool
     */
    public static function is_physicians_page()
    {
        return WA_User_Role::is_physicians_page();
    }

    /**
     * Proxy method for checking if user can create physicians posts
     * 
     * Maintains backward compatibility for any external code that
     * might be calling this method directly.
     * 
     * @param int|null $user_id User ID, null for current user
     * @return bool True if user can create posts
     */
    public static function can_create_physicians_post($user_id = null)
    {
        return WA_User_Role::can_create_physicians_post($user_id);
    }

    /**
     * Proxy method for getting WA user capabilities
     * 
     * Maintains backward compatibility for any external code that
     * might be calling this method directly.
     * 
     * @return array
     */
    public static function get_wa_capabilities()
    {
        return WA_User_Role::get_wa_capabilities();
    }

    /**
     * Proxy method for home page redirect
     * 
     * Maintains backward compatibility for any external code that
     * might be calling this method directly.
     */
    public static function render_home_page_redirect()
    {
        return WA_Admin_Interface::render_home_page_redirect();
    }

    /**
     * Proxy method for my account redirect
     * 
     * Maintains backward compatibility for any external code that
     * might be calling this method directly.
     */
    public static function render_my_account_redirect()
    {
        return WA_Admin_Interface::render_my_account_redirect();
    }
}

// Initialize the WA User Manager
WA_User_Manager::init();
