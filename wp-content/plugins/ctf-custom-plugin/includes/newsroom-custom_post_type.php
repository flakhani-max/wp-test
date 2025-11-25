<?php
// Register Custom Post Type: Newsroom      
function ctf_register_newsroom_post_type() {
    $labels = array(
        'name'                  => 'Newsroom',
        'singular_name'         => 'Newsroom',
        'menu_name'             => 'Newsroom',
        'add_new'               => 'Add New',
        'add_new_item'          => 'Add New Newsroom Item',
        'edit_item'             => 'Edit Newsroom Item',
        'new_item'              => 'New Newsroom Item',
        'view_item'             => 'View Newsroom Item',
        'view_items'            => 'View Newsroom Items',
        'search_items'          => 'Search Newsroom Items',
        'not_found'             => 'No newsroom item found',
        'not_found_in_trash'    => 'No newsroom item found in trash',
        'all_items'             => 'All Newsroom Items',
        'archives'              => 'Newsroom Archives',
    );

    $args = array(
        'labels'                => $labels,
        'public'                => true,
        'publicly_queryable'    => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'query_var'             => true,
        'rewrite'               => array('slug' => 'newsroom'),
        'capability_type'       => 'post',
        'has_archive'           => true,
        'hierarchical'          => false,
        'menu_position'         => 5,
        'menu_icon'             => 'dashicons-media-document',
        'supports'              => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
        'show_in_rest'          => false, // Enable Gutenberg editor
    );

    register_post_type('newsroom', $args);
}
add_action('init', 'ctf_register_newsroom_post_type');

// Register Custom Taxonomy: Newsroom Category
function ctf_register_newsroom_category() {
    $labels = array(
        'name'              => 'Newsroom Categories',
        'singular_name'     => 'Newsroom Category',
        'search_items'      => 'Search Newsroom Categories',
        'all_items'         => 'All Newsroom Categories',
        'parent_item'       => 'Parent Newsroom Category',
        'parent_item_colon' => 'Parent Newsroom Category:',
        'edit_item'         => 'Edit Newsroom Category',
        'update_item'       => 'Update Newsroom Category',
        'add_new_item'      => 'Add New Newsroom Category',
        'new_item_name'     => 'New Newsroom Category Name',
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
        'rewrite'           => array('slug' => 'newsroom-category'),
    );

    register_taxonomy('newsroom_category', array('newsroom'), $args);
}
add_action('init', 'ctf_register_newsroom_category');

/**
 * ACF Field Group for Newsroom Pages
 * Add this to functions.php or use ACF UI to create these fields
 */

if( function_exists('acf_add_local_field_group') ):

acf_add_local_field_group(array(
    'key' => 'group_newsroom_fields',
    'title' => 'Newsroom Content',
    'fields' => array(
        array(
            'key' => 'field_newsroom_image',
            'label' => 'Newsroom Image URL',
            'name' => 'newsroom_image',
            'type' => 'url',
            'instructions' => 'URL of the main newsroom image',
            'placeholder' => 'https://www.taxpayer.com/media/image.jpg',
        ),
        array(
            'key' => 'field_newsroom_type',
            'label' => 'Type',
            'name' => 'newsroom_type',
            'type' => 'select',
            'instructions' => 'Select the type of news item',
            'choices' => array(
                'press_release' => 'Press Release',
                'media_coverage' => 'Media Coverage',
                'blog_post' => 'Blog Post',
                'op_ed' => 'Op-Ed',
                'statement' => 'Statement',
            ),
            'default_value' => 'press_release',
            'allow_null' => 0,
            'multiple' => 0,
        ),
        array(
            'key' => 'field_newsroom_province',
            'label' => 'Province',
            'name' => 'newsroom_province',
            'type' => 'select',
            'instructions' => 'Select the province this news relates to',
            'choices' => array(
                'national' => 'National',
                'ab' => 'Alberta',
                'bc' => 'British Columbia',
                'mb' => 'Manitoba',
                'nb' => 'New Brunswick',
                'nl' => 'Newfoundland and Labrador',
                'ns' => 'Nova Scotia',
                'on' => 'Ontario',
                'pe' => 'Prince Edward Island',
                'qc' => 'Quebec',
                'sk' => 'Saskatchewan',
                'nt' => 'Northwest Territories',
                'nu' => 'Nunavut',
                'yt' => 'Yukon',
            ),
            'default_value' => 'national',
            'allow_null' => 0,
            'multiple' => 0,
        ),
        array(
            'key' => 'field_newsroom_date',
            'label' => 'Publication Date',
            'name' => 'newsroom_date',
            'type' => 'date_picker',
            'instructions' => 'Select the publication date (if different from post date)',
            'display_format' => 'F j, Y',
            'return_format' => 'Y-m-d',
            'first_day' => 0,
        ),
        array(
            'key' => 'field_newsroom_author',
            'label' => 'Author',
            'name' => 'newsroom_author',
            'type' => 'text',
            'instructions' => 'Enter the author name',
            'placeholder' => 'e.g., John Smith, Communications Director',
            'default_value' => '',
        ),
    ),
    'location' => array(
        array(
            array(
                'param' => 'post_type',
                'operator' => '==',
                'value' => 'newsroom',
            ),
        ),
    ),
    'menu_order' => 0,
    'position' => 'normal',
    'style' => 'default',
));

endif;

?>