<?php
/**
 * CTF Landing Pages Theme Functions
 */


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
    
    if (is_singular('donation')) {
        // Donation template styles
        wp_enqueue_style('donation-template', 
            get_template_directory_uri() . '/css/donation-template.css', 
            ['ctf-components'], '1.0'
        );
        // Donation template scripts
        wp_enqueue_script('donation-template', 
            get_template_directory_uri() . '/js/donation-template.js', 
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
    
    // Load main theme JavaScript only if the file exists. Do not depend on jQuery by default.
    $main_js_path = get_template_directory() . '/js/main.js';
    if ( file_exists( $main_js_path ) ) {
        wp_enqueue_script(
            'ctf-main',
            get_template_directory_uri() . '/js/main.js',
            [],
            '1.0',
            true
        );
    }
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

/**
 * Set appropriate cache headers for pages with forms
 */
function ctf_set_cache_headers() {
    // Don't cache pages with petition forms
    if (is_singular('petition') || is_page()) {
        // Set cache headers to prevent aggressive caching of dynamic content
        header('Cache-Control: public, max-age=300, s-maxage=300'); // 5 minutes
        header('Vary: Cookie'); // Vary by user session
    }
}
add_action('template_redirect', 'ctf_set_cache_headers');
