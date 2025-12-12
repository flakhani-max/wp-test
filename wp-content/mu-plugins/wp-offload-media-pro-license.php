<?php
/**
 * WP Offload Media Pro License Configuration
 * This must-use plugin automatically sets the Pro license key from environment variable
 */

// Get license key from environment variable
$wp_offload_license = getenv('WP_OFFLOAD_MEDIA_LICENSE');

if (!empty($wp_offload_license)) {
    // Define the license key constant - MUST be WPOS3_LICENCE (not WPOS3_LICENCE_KEY)
    if (!defined('WPOS3_LICENCE')) {
        define('WPOS3_LICENCE', $wp_offload_license);
    }
    
    // When constant is defined, the plugin expects the database to be EMPTY
    // So we remove any stored license keys to avoid conflicts
    add_action('init', function() {
        if (defined('WPOS3_LICENCE')) {
            delete_option('wpos3_licence');
            delete_option('wpos3_licence_key');
            delete_site_option('wpos3_licence');
            delete_site_option('wpos3_licence_key');
        }
    }, 1);
}

