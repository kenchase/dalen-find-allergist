<?php

/**
 * Physician Post Auto-Creation for Wild Apricot Users
 * 
 * Note: The faa_send_urgent_admin_notification() function is available in this file
 * but all calls to it are currently commented out. To enable urgent admin email
 * notifications when physician post creation fails, uncomment the function calls
 * in faa_ensure_physician_post_on_login() and faa_get_or_create_physician_post().
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * PRIMARY: Create physician post on user registration
 */
add_action('user_register', 'faa_create_physician_post_on_registration', 10, 1);
function faa_create_physician_post_on_registration($user_id)
{
    $user = get_userdata($user_id);

    // Only process Wild Apricot users
    if (strpos($user->user_login, 'wa_') !== 0) {
        return;
    }

    // Attempt creation
    $post_id = faa_attempt_physician_post_creation($user_id);

    if (is_wp_error($post_id)) {
        error_log("CRITICAL: Failed to create physician post during registration for user $user_id: " . $post_id->get_error_message());
    }
}

/**
 * FALLBACK: Ensure physician post exists on login
 */
add_action('wp_login', 'faa_ensure_physician_post_on_login', 10, 2);
function faa_ensure_physician_post_on_login($user_login, $user)
{
    // Only process Wild Apricot users
    if (strpos($user_login, 'wa_') !== 0) {
        return;
    }

    // Check if physician post exists
    $existing_posts = get_posts(array(
        'post_type'      => FAA_POST_TYPE,
        'author'         => $user->ID,
        'posts_per_page' => 1,
        'post_status'    => 'any',
        'fields'         => 'ids'
    ));

    // If post doesn't exist, create it NOW
    if (empty($existing_posts)) {
        error_log("NOTICE: Creating missing physician post for user {$user->ID} at login");

        $post_id = faa_attempt_physician_post_creation($user->ID);

        if (is_wp_error($post_id)) {
            error_log("CRITICAL: Failed to create physician post at login for user {$user->ID}: " . $post_id->get_error_message());
            update_user_meta($user->ID, 'faa_physician_post_creation_error', $post_id->get_error_message());
            // faa_send_urgent_admin_notification($user->ID, $post_id->get_error_message());
        }
    }
}

/**
 * Core function to create physician post
 */
function faa_attempt_physician_post_creation($user_id)
{
    $user = get_userdata($user_id);

    if (!$user) {
        return new WP_Error('invalid_user', 'User does not exist');
    }

    // Double-check that post doesn't already exist
    $existing_posts = get_posts(array(
        'post_type'      => FAA_POST_TYPE,
        'author'         => $user_id,
        'posts_per_page' => 1,
        'post_status'    => 'any',
        'fields'         => 'ids'
    ));

    if (!empty($existing_posts)) {
        // Post already exists, store ID and return success
        update_user_meta($user_id, 'faa_physician_post_id', $existing_posts[0]);
        return $existing_posts[0];
    }

    // Prepare post title with fallbacks
    $post_title = faa_get_physician_post_title($user);

    $post_data = array(
        'post_title'   => $post_title,
        'post_type'    => FAA_POST_TYPE,
        'post_status'  => 'publish',
        'post_author'  => $user_id,
        'post_content' => '',
    );

    $post_id = wp_insert_post($post_data, true);

    if (!is_wp_error($post_id)) {
        // Store post ID in user meta for quick lookup
        update_user_meta($user_id, 'faa_physician_post_id', $post_id);
        delete_user_meta($user_id, 'faa_physician_post_creation_error');
        error_log("Successfully created physician post $post_id for user $user_id");
    }

    return $post_id;
}

/**
 * Generate appropriate post title from user data
 */
function faa_get_physician_post_title($user)
{
    // Try first + last name first (priority format: "First Last")
    if (!empty($user->first_name) || !empty($user->last_name)) {
        $first = !empty($user->first_name) ? trim($user->first_name) : '';
        $last = !empty($user->last_name) ? trim($user->last_name) : '';
        $full_name = trim($first . ' ' . $last);
        if (!empty($full_name)) {
            return $full_name;
        }
    }

    // Try display name (if it's not just the username)
    if (!empty($user->display_name) && $user->display_name !== $user->user_login) {
        return $user->display_name;
    }

    // Fallback: clean up the username for display
    return ucwords(str_replace(array('wa_', '_'), array('', ' '), $user->user_login));
}

/**
 * EMERGENCY FALLBACK: Get or create physician post on-demand
 * Call this function from your existing display code if needed
 */
function faa_get_or_create_physician_post($user_id = null)
{
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return new WP_Error('no_user', 'User not logged in');
    }

    // Try to get post from user meta first (performance optimization)
    $post_id = get_user_meta($user_id, 'faa_physician_post_id', true);

    // Verify post still exists
    if ($post_id && get_post_status($post_id) !== false) {
        return $post_id;
    }

    // Fallback: Query for post
    $posts = get_posts(array(
        'post_type'      => FAA_POST_TYPE,
        'author'         => $user_id,
        'posts_per_page' => 1,
        'post_status'    => 'any',
        'fields'         => 'ids'
    ));

    if (!empty($posts)) {
        $post_id = $posts[0];
        update_user_meta($user_id, 'faa_physician_post_id', $post_id);
        return $post_id;
    }

    // EMERGENCY: Post doesn't exist, try creating now
    error_log("EMERGENCY: Attempting to create physician post on-demand for user $user_id");
    $post_id = faa_attempt_physician_post_creation($user_id);

    if (is_wp_error($post_id)) {
        // faa_send_urgent_admin_notification($user_id, $post_id->get_error_message());
    }

    return $post_id;
}

/**
 * Send urgent notification to admin when creation fails
 */
function faa_send_urgent_admin_notification($user_id, $error_message)
{
    $user = get_userdata($user_id);
    $admin_email = get_option('admin_email');

    // Don't spam - only send if we haven't sent in last hour
    $last_sent = get_transient('faa_urgent_notification_' . $user_id);
    if ($last_sent) {
        return;
    }

    $subject = '[URGENT] Physician Post Creation Failed';
    $message = "IMMEDIATE ACTION REQUIRED\n\n";
    $message .= "Failed to create physician post for Wild Apricot user:\n\n";
    $message .= "User ID: {$user_id}\n";
    $message .= "Username: {$user->user_login}\n";
    $message .= "Email: {$user->user_email}\n";
    $message .= "Error: {$error_message}\n\n";
    $message .= "The user may be unable to access their physician profile.\n\n";
    $message .= "Please manually create a physician post for this user at:\n";
    $message .= admin_url('post-new.php?post_type=' . FAA_POST_TYPE) . "\n\n";
    $message .= "Timestamp: " . current_time('mysql');

    wp_mail($admin_email, $subject, $message);

    // Set transient to prevent spam
    set_transient('faa_urgent_notification_' . $user_id, true, HOUR_IN_SECONDS);
}

/**
 * MANUAL TRIGGER: Admin function to create missing physician posts
 * Add ?faa_create_missing_posts=1&faa_nonce=[nonce] to admin URL to trigger (admin only)
 */
add_action('admin_init', 'faa_manual_create_missing_posts');
function faa_manual_create_missing_posts()
{
    if (!isset($_GET['faa_create_missing_posts']) || !current_user_can('manage_options')) {
        return;
    }

    // Verify nonce for security
    if (!isset($_GET['faa_nonce']) || !wp_verify_nonce($_GET['faa_nonce'], 'faa_create_missing_posts')) {
        wp_die('Security check failed');
    }

    error_log("=== FAA: Manual trigger for missing physician posts ===");

    // Get all users and filter for Wild Apricot users
    $all_users = get_users(array(
        'fields' => array('ID', 'user_login')
    ));

    $wa_users = array_filter($all_users, function ($user) {
        return strpos($user->user_login, 'wa_') === 0;
    });

    $created = 0;
    $already_exists = 0;
    $errors = 0;

    foreach ($wa_users as $user) {
        error_log("FAA: Checking user {$user->ID} ({$user->user_login})");

        // Check if post exists
        $posts = get_posts(array(
            'post_type' => FAA_POST_TYPE,
            'author' => $user->ID,
            'posts_per_page' => 1,
            'post_status' => 'any',
            'fields' => 'ids'
        ));

        if (empty($posts)) {
            error_log("FAA: No post found, creating for user {$user->ID}");
            $result = faa_attempt_physician_post_creation($user->ID);
            if (is_wp_error($result)) {
                $errors++;
                error_log("FAA: Error creating post: " . $result->get_error_message());
            } else {
                $created++;
                error_log("FAA: Created post $result for user {$user->ID}");
            }
        } else {
            $already_exists++;
            error_log("FAA: Post already exists: {$posts[0]}");
        }
    }

    $message = "Physician Post Creation Results: Created: $created, Already Exists: $already_exists, Errors: $errors";
    error_log("FAA: $message");

    wp_die($message . '<br><br><a href="' . admin_url() . '">Back to Dashboard</a>');
}

/**
 * Admin notice if post creation has errors
 */
add_action('admin_notices', 'faa_physician_post_creation_admin_notice');
function faa_physician_post_creation_admin_notice()
{
    $user_id = get_current_user_id();
    $error = get_user_meta($user_id, 'faa_physician_post_creation_error', true);

    if ($error && current_user_can('edit_posts')) {
        echo '<div class="notice notice-error"><p><strong>Physician Post Creation Error:</strong> ' . esc_html($error) . '</p></div>';
    }
}

/**
 * Helper function to generate nonce-protected manual trigger URL
 * Use this to get the safe URL for manual triggering
 */
function faa_get_manual_trigger_url()
{
    return add_query_arg(array(
        'faa_create_missing_posts' => '1',
        'faa_nonce' => wp_create_nonce('faa_create_missing_posts')
    ), admin_url());
}
