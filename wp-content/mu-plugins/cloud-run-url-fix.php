<?php
/**
 * Plugin Name: Cloud Run URL Auto-Detection
 * Description: Automatically detects and fixes WordPress URLs when running on Cloud Run
 * Version: 1.0
 * Author: CTF
 */

// Auto-detect Cloud Run URL from headers
add_filter('option_siteurl', 'cloudrun_fix_url');
add_filter('option_home', 'cloudrun_fix_url');

function cloudrun_fix_url($url) {
    // Only auto-detect if we're on Cloud Run (K_SERVICE env var is set)
    if (!getenv('K_SERVICE')) {
        return $url;
    }
    
    // Check if we have forwarded headers from Cloud Run
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && isset($_SERVER['HTTP_HOST'])) {
        $protocol = $_SERVER['HTTP_X_FORWARDED_PROTO'];
        $host = $_SERVER['HTTP_HOST'];
        $detected_url = $protocol . '://' . $host;
        
        // Only update if it's different and valid
        if ($detected_url !== $url && filter_var($detected_url, FILTER_VALIDATE_URL)) {
            return $detected_url;
        }
    }
    
    return $url;
}

// Fix asset URLs (CSS, JS, images) to use correct domain
add_filter('stylesheet_directory_uri', 'cloudrun_fix_asset_url', 10, 3);
add_filter('template_directory_uri', 'cloudrun_fix_asset_url', 10, 3);
add_filter('plugins_url', 'cloudrun_fix_asset_url', 10, 3);
add_filter('content_url', 'cloudrun_fix_asset_url', 10, 1);

function cloudrun_fix_asset_url($url) {
    // Only auto-detect if we're on Cloud Run
    if (!getenv('K_SERVICE')) {
        return $url;
    }
    
    // Get the correct hostname from headers
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && isset($_SERVER['HTTP_HOST'])) {
        $correct_protocol = $_SERVER['HTTP_X_FORWARDED_PROTO'];
        $correct_host = $_SERVER['HTTP_HOST'];
        
        // Parse the URL
        $parsed = parse_url($url);
        
        // If the hostname is wrong, replace it
        if (isset($parsed['host']) && $parsed['host'] !== $correct_host) {
            $fixed_url = $correct_protocol . '://' . $correct_host;
            if (isset($parsed['path'])) {
                $fixed_url .= $parsed['path'];
            }
            if (isset($parsed['query'])) {
                $fixed_url .= '?' . $parsed['query'];
            }
            return $fixed_url;
        }
    }
    
    return $url;
}

// Fix redirect issues on Cloud Run
add_filter('wp_redirect', 'cloudrun_fix_redirect', 10, 2);

function cloudrun_fix_redirect($location, $status) {
    if (!getenv('K_SERVICE')) {
        return $location;
    }
    
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && isset($_SERVER['HTTP_HOST'])) {
        $protocol = $_SERVER['HTTP_X_FORWARDED_PROTO'];
        $host = $_SERVER['HTTP_HOST'];
        $correct_base = $protocol . '://' . $host;
        
        // Replace any incorrect base URL in the redirect location
        $parsed = parse_url($location);
        if (isset($parsed['host']) && $parsed['host'] !== $host) {
            $location = $correct_base . ($parsed['path'] ?? '') . 
                       (isset($parsed['query']) ? '?' . $parsed['query'] : '');
        }
    }
    
    return $location;
}



