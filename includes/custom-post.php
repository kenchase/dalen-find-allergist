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
function faa_custom_allergist_post()
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
        'has_archive' => false,
        'menu_position' => 2,
        'menu_icon' => 'dashicons-groups',
        'rewrite' => array('slug' => 'allergists'),
        'exclude_from_search' => true,
        'hierarchical' => false,
        'supports' => array('title', 'author'),
    );
    register_post_type('physicians', $post_type_args);
}
add_action('init', 'faa_custom_allergist_post');
