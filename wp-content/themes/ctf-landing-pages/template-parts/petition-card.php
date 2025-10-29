<?php
/**
 * Template part for displaying a petition card
 * 
 * @package CTF_Landing_Pages
 */

// Set defaults
$context = isset($context) ? $context : 'archive';
$show_excerpt_length = isset($show_excerpt_length) ? $show_excerpt_length : 25;
$show_category = isset($show_category) ? $show_category : ($context === 'archive');

// Prepare petition-specific data
$card_type = 'petition';
$card_data = array(
    'image' => get_field('petition_image'),
    'title' => get_field('petition_title') ?: get_the_title(),
    'intro' => get_field('petition_intro'),
    'cta_text' => 'Sign This Petition',
    'taxonomy' => 'petition_category'
);

// Load the base card template
get_template_part('template-parts/base-card', null, compact('card_type', 'card_data', 'context', 'show_excerpt_length', 'show_category'));