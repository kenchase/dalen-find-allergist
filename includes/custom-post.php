<?php
/*
*  Custom WordPress Post Type and Taxonomy for Allergists
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
        'show_ui' => true,
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
        'supports' => array('title', 'editor', 'author'),
        'taxonomies' => array('physiciantypes'),
    );
    // Although named 'physicians', this post type is for Allergists.
    // Content was already created in the database, so we continue to use 'physicians' as the post type name.
    // Changing this to 'allergists' would require a database migration which is out of scope at this time 
    register_post_type('physicians', $post_type_args);

    $taxonomy_labels = array(
        'name' => __('Allergist Types', 'dalen-find-allergist'),
        'singular_name' => __('Allergist Type', 'dalen-find-allergist'),
        'search_items' => __('Search Allergist Types', 'dalen-find-allergist'),
        'all_items' => __('All Allergist Types', 'dalen-find-allergist'),
        'parent_item' => __('Parent Allergist Type', 'dalen-find-allergist'),
        'parent_item_colon' => __('Parent Allergist Type:', 'dalen-find-allergist'),
        'edit_item' => __('Edit Allergist Type', 'dalen-find-allergist'),
        'update_item' => __('Update Allergist Type', 'dalen-find-allergist'),
        'add_new_item' => __('Add New Allergist Type', 'dalen-find-allergist'),
        'new_item_name' => __('New Allergist Type Name',   'dalen-find-allergist'),
        'menu_name' => __('Allergist Types', 'dalen-find-allergist'),
    );

    $taxonomy_args = array(
        'hierarchical' => true,
        'labels' => $taxonomy_labels,
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'physiciantypes'),
    );

    register_taxonomy('physiciantypes', array('physicians'), $taxonomy_args);
}
add_action('init', 'csaci_custom_allergist_post');
