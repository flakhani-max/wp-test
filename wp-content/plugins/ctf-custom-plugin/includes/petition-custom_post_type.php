<?php
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
        'show_in_rest'          => false, // Enable Gutenberg editor
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

/**
 * ACF Field Group for Petition Pages
 * Add this to functions.php or use ACF UI to create these fields
 */

if( function_exists('acf_add_local_field_group') ):

acf_add_local_field_group(array(
    'key' => 'group_petition_fields',
    'title' => 'Petition Content',
    'fields' => array(
        array(
            'key' => 'field_petition_image',
            'label' => 'Petition Image URL',
            'name' => 'petition_image',
            'type' => 'url',
            'instructions' => 'URL of the main petition image',
            'placeholder' => 'https://www.taxpayer.com/media/image.jpg',
        ),
        array(
            'key' => 'petition_tag',
            'label' => 'Mailchimp Petition Tag',
            'name' => 'petition_tag',
            'type' => 'textarea',
            'instructions' => 'Mailchimp petition tag'
        )
    ),
    'location' => array(
        array(
            array(
                'param' => 'post_type',
                'operator' => '==',
                'value' => 'petition',
            ),
        ),
    ),
    'menu_order' => 0,
    'position' => 'normal',
    'style' => 'default',
));

endif;

?>