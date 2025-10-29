<?php
/**
 * Template part for displaying a donation card
 * 
 * @package CTF_Landing_Pages
 */

// Set defaults
$context = isset($context) ? $context : 'archive';
$show_excerpt_length = isset($show_excerpt_length) ? $show_excerpt_length : 25;
$show_category = isset($show_category) ? $show_category : ($context === 'archive');

// Prepare donation-specific data
$card_type = 'donation';
$card_data = array(
    'image' => get_field('donation_image'),
    'title' => get_field('donation_title') ?: get_the_title(),
    'intro' => get_field('donation_intro'),
    'cta_text' => 'Donate Now',
    'taxonomy' => 'donation_category'
);

// Load the base card template
get_template_part('template-parts/base-card', null, compact('card_type', 'card_data', 'context', 'show_excerpt_length', 'show_category'));