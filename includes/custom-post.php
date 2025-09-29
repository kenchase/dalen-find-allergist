<?php

/**
 * Custom WordPress Post Type for Allergists
 * 
 * Registers the 'physicians' post type for managing allergist data 
 * in the WordPress backend.
 * 
 * Note: Although named 'physicians', this post type is specifically for Allergists.
 * The naming was kept for backward compatibility as content already exists 
 * in the database with this structure.
 *
 * @package Dalen_Find_Allergist
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register custom post type for allergists
 * 
 * @since 1.0.0
 */
function csaci_custom_allergist_post()
{
    $post_type_args = array(
        'labels' => array(
            'name' => 'Allergists',
            'singular_name' => 'Allergist',
            'add_new' => 'Add Allergist',
            'add_new_item' => 'Add New Allergist',
            'edit_item' => 'Edit Allergist',
            'new_item' => 'New Allergist',
            'view_item' => 'View Allergist',
            'view_items' => 'View Allergists',
            'search_items' => 'Search Allergists',
            'not_found' => 'No Allergist found',
            'not_found_in_trash' => 'No Allergist found in Trash',
            'all_items' => 'All Allergists',
            'archives' => 'Allergist Archives',
            'attributes' => 'Allergist Attributes',
        ),
        'public' => true,
        'publicly_queryable' => false, // Prevents direct URL access
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'has_archive' => true,
        'menu_position' => 2,
        'menu_icon' => 'dashicons-groups',
        'rewrite' => array('slug' => 'allergists'),
        'exclude_from_search' => true,
        'capability_type' => 'physicians',
        'map_meta_cap' => true,
        'capabilities' => [
            'edit_post'             => 'edit_physician',
            'read_post'             => 'read_physician',
            'delete_post'           => 'delete_physician',
            'edit_posts'            => 'edit_physicians',
            'edit_others_posts'     => 'edit_others_physicians',
            'publish_posts'         => 'publish_physicians',
            'read_private_posts'    => 'read_private_physicians',
            'delete_posts'          => 'delete_physicians',
            'delete_others_posts'   => 'delete_others_physicians',
            'delete_published_posts' => 'delete_published_physicians',
            'edit_published_posts'  => 'edit_published_physicians',
            'create_posts'          => 'create_physicians',
        ],
        'hierarchical' => false,
        'supports' => array('title', 'author'),
    );
    // Although named 'physicians', this post type is for Allergists.
    // Content was already created in the database, so we continue to use 'physicians' as the post type name.
    // Changing this to 'allergists' would require a database migration which is out of scope at this time 
    register_post_type('physicians', $post_type_args);
}
add_action('init', 'csaci_custom_allergist_post');

/**
 * Make title mandatory for physicians post type
 * 
 * @since 1.0.0
 */
function make_physician_title_required()
{
    global $pagenow, $post_type;

    if (($pagenow === 'post.php' || $pagenow === 'post-new.php') && $post_type === 'physicians') {
        echo '<script>
            jQuery(document).ready(function($) {
                $("#publish, #save-post").click(function(e) {
                    var title = $("#title").val().trim();
                    if (title === "") {
                        alert("Title is required for this post type.");
                        $("#title").focus();
                        return false;
                    }
                });
            });
        </script>';
    }
}
add_action('admin_footer', 'make_physician_title_required');
