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
 */
function ctf_add_petition_acf_fields() {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

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
            'type' => 'text',
            'instructions' => 'Tag used on the backend in Mailchimp. This will be automatically generated when creating a new petition.',
            ),
            array(
                'key' => 'field_petition_province',
                'label' => 'Province',
                'name' => 'petition_province',
                'type' => 'select',
                'instructions' => 'Select the province this petition relates to',
                'choices' => array(
                    'federal' => 'Federal',
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
                'default_value' => 'federal',
                'allow_null' => 0,
                'multiple' => 0,
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
        array(
            array(
                'param' => 'post_type',
                'operator' => '==',
                'value' => 'next_step',
            ),
        ),
        array(
            array(
                'param' => 'page_template',
                'operator' => '==',
                'value' => 'page-petition.php',
            ),
            array(
                'param' => 'post_type',
                'operator' => '==',
                'value' => 'page',
            ),
        ),
    ),
    'menu_order' => 0,
    // Surface fields near the top in the editor
    'position' => 'acf_after_title',
    'style' => 'default',
));
}
add_action('acf/init', 'ctf_add_petition_acf_fields');

/**
 * Handle petition archive filtering by province
 */
function ctf_petition_filter_query($query) {
    // Only modify petition archive queries on the frontend
    if (!is_admin() && $query->is_main_query() && is_post_type_archive('petition')) {
        
        $meta_query = array('relation' => 'AND');
        
        // Filter by Province (default to federal if not specified)
        if (!isset($_GET['petition_province'])) {
            // First visit - default to federal
            $meta_query[] = array(
                'key' => 'petition_province',
                'value' => 'federal',
                'compare' => '='
            );
        } elseif ($_GET['petition_province'] !== '') {
            // User selected a specific province
            $meta_query[] = array(
                'key' => 'petition_province',
                'value' => sanitize_text_field($_GET['petition_province']),
                'compare' => '='
            );
        }
        // If $_GET['petition_province'] === '', user selected "All Provinces" - don't filter
        
        // Apply meta query if we have filters
        if (count($meta_query) > 1) {
            $query->set('meta_query', $meta_query);
        }
        
        // Default ordering by date (newest first)
        $query->set('orderby', 'date');
        $query->set('order', 'DESC');
    }
}
add_action('pre_get_posts', 'ctf_petition_filter_query');

/**
 * Register query vars for petition filters
 */
function ctf_petition_query_vars($vars) {
    $vars[] = 'petition_province';
    return $vars;
}
add_filter('query_vars', 'ctf_petition_query_vars');

?>
