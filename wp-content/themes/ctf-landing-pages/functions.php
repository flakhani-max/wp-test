<?php
/**
 * CTF Landing Pages Theme Functions
 */

// Load petition custom post type and fields
require_once get_template_directory() . '/inc/petition-post-type.php';
require_once get_template_directory() . '/inc/petition-acf-fields.php';

/**
 * Enqueue styles and scripts with conditional loading
 */
function ctf_enqueue_assets() {
    // Always load main theme styles and shared components
    wp_enqueue_style('ctf-main', get_stylesheet_uri(), [], '1.0');
    wp_enqueue_style('ctf-components', get_template_directory_uri() . '/css/components.css', ['ctf-main'], '1.0');
    
    // Load template-specific styles conditionally
    if (is_singular('petition')) {
        // Petition template styles and scripts
        wp_enqueue_style('petition-template', 
            get_template_directory_uri() . '/css/petition-template.css', 
            ['ctf-components'], '1.0'
        );
        wp_enqueue_script('petition-template', 
            get_template_directory_uri() . '/js/petition-template.js', 
            [], '1.0', true
        );
    }
    
    if (is_post_type_archive('petition')) {
        // Petition archive styles
        wp_enqueue_style('petition-archive', 
            get_template_directory_uri() . '/css/petition-archive.css', 
            ['ctf-components'], '1.0'
        );
    }
    
    // Load page-specific styles
    if (is_page()) {
        $page_template = get_page_template_slug();

        // Load specific CSS for page templates
        if ($page_template === 'page-static.php') {
            wp_enqueue_style('static-page',
                get_template_directory_uri() . '/css/static-page.css',
                ['ctf-components'], '1.0'
            );
        }
    }
    
    // Load main theme JavaScript (if needed)
    wp_enqueue_script('ctf-main', 
        get_template_directory_uri() . '/js/main.js', 
        ['jquery'], '1.0', true
    );
}
add_action('wp_enqueue_scripts', 'ctf_enqueue_assets');

/**
 * Theme setup
 */
function ctf_theme_setup() {
    // Add theme support for various features
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', [
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ]);
    
    // Register navigation menus
    register_nav_menus([
        'primary' => __('Primary Menu', 'ctf-landing-pages'),
        'footer' => __('Footer Menu', 'ctf-landing-pages'),
    ]);
}
add_action('after_setup_theme', 'ctf_theme_setup');
