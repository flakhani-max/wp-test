<?php
// Custom functions.php for petition template

// Register Custom Post Type: Petition
function ctf_register_petition_post_type() {
    $labels = array(
        'name'                  => 'Petitions',
        'singular_name'         => 'Petition',
        'menu_name'             => 'Petitions',
        'add_new'               => 'Add New',
        'add_new_item'          => 'Add New Petition',
        'edit_item'             => 'Edit Petition',
        'new_item'              => 'New Petition',
        'view_item'             => 'View Petition',
        'view_items'            => 'View Petitions',
        'search_items'          => 'Search Petitions',
        'not_found'             => 'No petitions found',
        'not_found_in_trash'    => 'No petitions found in trash',
        'all_items'             => 'All Petitions',
        'archives'              => 'Petition Archives',
    );

    $args = array(
        'labels'                => $labels,
        'public'                => true,
        'publicly_queryable'    => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'query_var'             => true,
        'rewrite'               => array('slug' => 'petitions'),
        'capability_type'       => 'post',
        'has_archive'           => true,
        'hierarchical'          => false,
        'menu_position'         => 5,
        'menu_icon'             => 'dashicons-edit-page',
        'supports'              => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
        'show_in_rest'          => true, // Enable Gutenberg editor
    );

    register_post_type('petition', $args);
}
add_action('init', 'ctf_register_petition_post_type');

// Register Custom Taxonomy: Petition Category
function ctf_register_petition_category() {
    $labels = array(
        'name'              => 'Petition Categories',
        'singular_name'     => 'Petition Category',
        'search_items'      => 'Search Petition Categories',
        'all_items'         => 'All Petition Categories',
        'parent_item'       => 'Parent Petition Category',
        'parent_item_colon' => 'Parent Petition Category:',
        'edit_item'         => 'Edit Petition Category',
        'update_item'       => 'Update Petition Category',
        'add_new_item'      => 'Add New Petition Category',
        'new_item_name'     => 'New Petition Category Name',
        'menu_name'         => 'Categories',
    );

    $args = array(
        'labels'            => $labels,
        'hierarchical'      => true, // Like categories (not tags)
        'public'            => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_nav_menus' => true,
        'show_tagcloud'     => false,
        'show_in_rest'      => true, // Enable in Gutenberg
        'rewrite'           => array('slug' => 'petition-category'),
    );

    register_taxonomy('petition_category', array('petition'), $args);
}
add_action('init', 'ctf_register_petition_category');

// Load ACF field definitions for petition custom post type
require_once get_template_directory() . '/acf-petition-fields.php';

add_action('wp_enqueue_scripts', function() {
    // Enqueue for petition custom post type
    if (is_singular('petition')) {
        wp_enqueue_style('petition-template', get_template_directory_uri() . '/css/petition-template.css');
        wp_enqueue_script('petition-template', get_template_directory_uri() . '/js/petition-template.js', [], null, true);
    }
});
