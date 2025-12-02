<?php
/**
 * ACF Utilities
 * Shared ACF-related functions used across multiple post types
 */

if (!defined('ABSPATH')) exit;

/**
 * Prevent URL rewriting for storage bucket URLs in ACF fields
 * WP Offload Media and similar plugins try to rewrite storage.googleapis.com URLs to local WordPress URLs.
 */
function ctf_capture_storage_url($value, $post_id, $field) {
    // Only apply to URL type fields for specific field names
    if (!isset($field['type']) || $field['type'] !== 'url' || empty($value)) {
        return $value;
    }
    
    $protected_fields = array('newsroom_image', 'petition_image', 'donation_image');
    if (!isset($field['name']) || !in_array($field['name'], $protected_fields)) {
        return $value;
    }
    
    // If this is a storage bucket URL, capture it before rewriting
    if (strpos($value, 'storage.googleapis.com') !== false) {
        $key = 'ctf_storage_url_' . $post_id . '_' . $field['name'];
        set_transient($key, $value, 60);
    }
    
    return $value;
}
add_filter('acf/update_value', 'ctf_capture_storage_url', 1, 3);

function ctf_restore_storage_url($value, $post_id, $field) {
    // Only apply to URL type fields for specific field names
    if (!isset($field['type']) || $field['type'] !== 'url' || empty($value)) {
        return $value;
    }
    
    $protected_fields = array('newsroom_image', 'petition_image', 'donation_image');
    if (!isset($field['name']) || !in_array($field['name'], $protected_fields)) {
        return $value;
    }
    
    // Check if we captured a storage URL that got rewritten
    $key = 'ctf_storage_url_' . $post_id . '_' . $field['name'];
    $original = get_transient($key);
    if ($original && strpos($original, 'storage.googleapis.com') !== false && $original !== $value) {
        // Original was storage URL but current value is different - restore it
        return $original;
    }
    
    return $value;
}
// Use late priority to run after plugins that rewrite URLs
add_filter('acf/update_value', 'ctf_restore_storage_url', 999, 3);

