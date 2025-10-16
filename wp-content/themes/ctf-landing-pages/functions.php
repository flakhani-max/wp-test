<?php
// Custom functions.php for petition template

// Load ACF field definitions for petition pages
require_once get_template_directory() . '/acf-petition-fields.php';

add_action('wp_enqueue_scripts', function() {
    // Only enqueue for the custom petition template
    if (is_page_template('petition-template.php')) {
        wp_enqueue_style('petition-template', get_template_directory_uri() . '/css/petition-template.css');
        wp_enqueue_script('petition-template', get_template_directory_uri() . '/js/petition-template.js', [], null, true);
    }
});
