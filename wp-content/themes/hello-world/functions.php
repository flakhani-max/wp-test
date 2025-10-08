<?php
/**
 * Hello World Theme Functions
 */

// Enqueue theme styles
function hello_world_enqueue_styles() {
    wp_enqueue_style('hello-world-style', get_stylesheet_uri(), array(), '1.0');
}
add_action('wp_enqueue_scripts', 'hello_world_enqueue_styles');

// Theme support
function hello_world_setup() {
    // Add theme support for title tag
    add_theme_support('title-tag');
    
    // Add theme support for HTML5
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ));
}
add_action('after_setup_theme', 'hello_world_setup');


