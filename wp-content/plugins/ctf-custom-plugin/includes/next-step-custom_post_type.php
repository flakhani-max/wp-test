<?php
/**
 * Next Step Post Type
 *
 * Houses post-completion content such as donation asks, confirmations,
 * or other follow-up articles.
 */

if (!defined('ABSPATH')) exit;

// Register Next Step Custom Post Type
add_action('init', 'ctf_register_next_step_cpt');

/**
 * Register the Next Step custom post type.
 */
function ctf_register_next_step_cpt() {
    $labels = array(
        'name'                  => _x('Next Steps', 'Post type general name', 'ctf-custom'),
        'singular_name'         => _x('Next Step', 'Post type singular name', 'ctf-custom'),
        'menu_name'             => _x('Next Steps', 'Admin Menu text', 'ctf-custom'),
        'name_admin_bar'        => _x('Next Step', 'Add New on Toolbar', 'ctf-custom'),
        'add_new'               => __('Add New', 'ctf-custom'),
        'add_new_item'          => __('Add New Next Step', 'ctf-custom'),
        'new_item'              => __('New Next Step', 'ctf-custom'),
        'edit_item'             => __('Edit Next Step', 'ctf-custom'),
        'view_item'             => __('View Next Step', 'ctf-custom'),
        'all_items'             => __('All Next Steps', 'ctf-custom'),
        'search_items'          => __('Search Next Steps', 'ctf-custom'),
        'parent_item_colon'     => __('Parent Next Steps:', 'ctf-custom'),
        'not_found'             => __('No Next Steps found.', 'ctf-custom'),
        'not_found_in_trash'    => __('No Next Steps found in Trash.', 'ctf-custom'),
        'featured_image'        => _x('Featured Image', 'ctf-custom'),
        'set_featured_image'    => _x('Set featured image', 'ctf-custom'),
        'remove_featured_image' => _x('Remove featured image', 'ctf-custom'),
        'use_featured_image'    => _x('Use as featured image', 'ctf-custom'),
        'archives'              => _x('Next Step archives', 'ctf-custom'),
        'insert_into_item'      => _x('Insert into Next Step', 'ctf-custom'),
        'uploaded_to_this_item' => _x('Uploaded to this Next Step', 'ctf-custom'),
        'filter_items_list'     => _x('Filter Next Steps list', 'ctf-custom'),
        'items_list_navigation' => _x('Next Steps list navigation', 'ctf-custom'),
        'items_list'            => _x('Next Steps list', 'ctf-custom'),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'next-step'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'menu_icon'          => 'dashicons-randomize',
        'supports'           => array('title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'),
        'show_in_rest'       => false,
    );

    register_post_type('next_step', $args);
}
