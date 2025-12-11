<?php
/**
 * Custom taxonomy for grouping Pages and optional section-based URLs.
 */

if (!defined('ABSPATH')) exit;

add_action('init', 'ctf_register_page_sections_taxonomy');
function ctf_register_page_sections_taxonomy() {
    $labels = array(
        'name'              => __('Sections', 'ctf-custom'),
        'singular_name'     => __('Section', 'ctf-custom'),
        'search_items'      => __('Search Sections', 'ctf-custom'),
        'all_items'         => __('All Sections', 'ctf-custom'),
        'parent_item'       => __('Parent Section', 'ctf-custom'),
        'parent_item_colon' => __('Parent Section:', 'ctf-custom'),
        'edit_item'         => __('Edit Section', 'ctf-custom'),
        'update_item'       => __('Update Section', 'ctf-custom'),
        'add_new_item'      => __('Add New Section', 'ctf-custom'),
        'new_item_name'     => __('New Section Name', 'ctf-custom'),
        'menu_name'         => __('Sections', 'ctf-custom'),
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => 'section'),
        'show_in_rest'      => true,
    );

    register_taxonomy('page_section', array('page'), $args);
}

/**
 * Optional section-based URL: /-/{section}/{page}/
 * Keeps standard page URL working; this is an alternate.
 */
add_filter('page_link', 'ctf_section_page_link', 10, 2);
function ctf_section_page_link($link, $post_id) {
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'page') {
        return $link;
    }

    $terms = wp_get_post_terms($post_id, 'page_section');
    if (is_wp_error($terms) || empty($terms)) {
        return $link;
    }

    $primary = $terms[0];
    return home_url(trailingslashit("-/{$primary->slug}/{$post->post_name}"));
}

// Allow custom query vars for sectioned pages
add_filter('query_vars', function($vars) {
    $vars[] = 'section_page';
    $vars[] = 'section_slug';
    $vars[] = 'page_slug';
    return $vars;
});

/**
 * Parse request manually to handle /-/{section}/{page}/ without relying on rewrite collisions.
 */
add_action('parse_request', function($wp) {
    $path = trim($wp->request, '/');
    // Expect "-/{section}/{page}"
    if (preg_match('#^-/([^/]+)/([^/]+)/?$#', $path, $m)) {
        $wp->query_vars['post_type']   = 'page';
        $wp->query_vars['pagename']    = $m[2];
        $wp->query_vars['section_page'] = 1;
        $wp->query_vars['section_slug'] = $m[1];
    }
});

// Shape the main query to enforce section match
add_action('pre_get_posts', function($query) {
    if (is_admin() || !$query->is_main_query()) {
        return;
    }
    if ($query->get('section_page')) {
        $section_slug = $query->get('section_slug');
        $page_slug = $query->get('pagename') ?: $query->get('page_slug');
        if (!$section_slug || !$page_slug) {
            return;
        }
        $query->set('post_type', 'page');
        $query->set('name', $page_slug);
        $query->set('tax_query', array(
            array(
                'taxonomy' => 'page_section',
                'field' => 'slug',
                'terms' => $section_slug,
            ),
        ));
    }
});

// Prevent canonical redirects from stripping the section URL
add_filter('redirect_canonical', function($redirect_url, $requested_url) {
    if (get_query_var('section_page')) {
        return false;
    }
    return $redirect_url;
}, 10, 2);
