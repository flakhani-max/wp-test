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
                'news_release' => 'News Release',
                'commentary' => 'Commentary',
                'blog' => 'Blog',
                'video' => 'Video',
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
        array(
            'key' => 'field_newsroom_pinned',
            'label' => 'Pin to Top',
            'name' => 'newsroom_pinned',
            'type' => 'true_false',
            'instructions' => 'Pin this post to the top of the newsroom archive (max 3 pinned posts)',
            'default_value' => 0,
            'ui' => 1,
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

/**
 * Handle newsroom archive filtering
 */
function ctf_newsroom_filter_query($query) {
    // Only modify newsroom archive queries on the frontend
    if (!is_admin() && $query->is_main_query() && is_post_type_archive('newsroom')) {
        
        // Exclude pinned posts from main query
        $pinned_query = new WP_Query(array(
            'post_type'      => 'newsroom',
            'posts_per_page' => 3,
            'post_status'    => 'publish',
            'fields'         => 'ids', // Only get IDs for performance
            'meta_query'     => array(
                array(
                    'key'   => 'newsroom_pinned',
                    'value' => '1',
                    'compare' => '='
                )
            ),
        ));
        
        $pinned_ids = $pinned_query->posts;
        wp_reset_postdata();
        
        if (!empty($pinned_ids)) {
            $query->set('post__not_in', $pinned_ids);
        }
        
        $meta_query = array('relation' => 'AND');
        
        // Filter by Type
        if (!empty($_GET['news_type'])) {
            $meta_query[] = array(
                'key' => 'newsroom_type',
                'value' => sanitize_text_field($_GET['news_type']),
                'compare' => '='
            );
        }
        
        // Filter by Province
        if (!empty($_GET['news_province'])) {
            $meta_query[] = array(
                'key' => 'newsroom_province',
                'value' => sanitize_text_field($_GET['news_province']),
                'compare' => '='
            );
        }
        
        // Filter by Author (partial match)
        if (!empty($_GET['news_author'])) {
            $meta_query[] = array(
                'key' => 'newsroom_author',
                'value' => sanitize_text_field($_GET['news_author']),
                'compare' => 'LIKE'
            );
        }
        
        // Apply meta query if we have filters
        if (count($meta_query) > 1) {
            $query->set('meta_query', $meta_query);
        }
        
        // Filter by Date Range
        $date_query = array();
        
        if (!empty($_GET['date_start'])) {
            $date_query[] = array(
                'after' => sanitize_text_field($_GET['date_start']),
                'inclusive' => true,
            );
        }
        
        if (!empty($_GET['date_end'])) {
            $date_query[] = array(
                'before' => sanitize_text_field($_GET['date_end']),
                'inclusive' => true,
            );
        }
        
        if (!empty($date_query)) {
            $query->set('date_query', $date_query);
        }
        
        // Default ordering by date (newest first)
        $query->set('orderby', 'date');
        $query->set('order', 'DESC');
    }
}
add_action('pre_get_posts', 'ctf_newsroom_filter_query');

/**
 * Register query vars for filters
 */
function ctf_newsroom_query_vars($vars) {
    $vars[] = 'news_type';
    $vars[] = 'news_province';
    $vars[] = 'news_author';
    $vars[] = 'date_start';
    $vars[] = 'date_end';
    return $vars;
}
add_filter('query_vars', 'ctf_newsroom_query_vars');

/**
 * ACF Field Group for Homepage Featured Images
 */
if( function_exists('acf_add_local_field_group') ):

acf_add_local_field_group(array(
    'key' => 'group_homepage_featured_images',
    'title' => 'Homepage Featured Images',
    'fields' => array(
        array(
            'key' => 'field_featured_images',
            'label' => 'Featured Images',
            'name' => 'homepage_featured_images',
            'type' => 'repeater',
            'instructions' => 'Add up to 4 featured images to display prominently at the top of the homepage',
            'min' => 1,
            'max' => 4,
            'layout' => 'table',
            'button_label' => 'Add Image',
            'sub_fields' => array(
                array(
                    'key' => 'field_featured_image',
                    'label' => 'Image',
                    'name' => 'image',
                    'type' => 'image',
                    'instructions' => 'Upload or select an image',
                    'return_format' => 'url',
                    'preview_size' => 'medium',
                    'library' => 'all',
                ),
                array(
                    'key' => 'field_featured_link',
                    'label' => 'Link URL',
                    'name' => 'link',
                    'type' => 'url',
                    'instructions' => 'Enter the URL this image should link to',
                    'placeholder' => 'https://example.com',
                ),
                array(
                    'key' => 'field_featured_title',
                    'label' => 'Title',
                    'name' => 'title',
                    'type' => 'text',
                    'instructions' => 'Title text to display over the image (white text)',
                    'placeholder' => 'Enter title',
                ),
                array(
                    'key' => 'field_featured_alt',
                    'label' => 'Alt Text',
                    'name' => 'alt_text',
                    'type' => 'text',
                    'instructions' => 'Alternative text for the image (for accessibility)',
                    'placeholder' => 'Description of image',
                ),
            ),
        ),
    ),
    'location' => array(
        array(
            array(
                'param' => 'options_page',
                'operator' => '==',
                'value' => 'acf-options-homepage',
            ),
        ),
    ),
    'menu_order' => 0,
    'position' => 'normal',
    'style' => 'default',
));

endif;

/**
 * Register ACF Options Page for Homepage Settings
 */
if( function_exists('acf_add_options_page') ) {
    acf_add_options_page(array(
        'page_title'    => 'Homepage Settings',
        'menu_title'    => 'Homepage Settings',
        'menu_slug'     => 'acf-options-homepage',
        'capability'    => 'edit_posts',
        'icon_url'      => 'dashicons-admin-home',
    ));
}

?>