<?php
/**
 * Fix WordPress Loopback Requests for Cloud Run
 * Allows WordPress to make HTTP requests to itself for background tasks
 */

// Allow loopback requests to bypass authentication
add_filter('http_request_args', function($args, $url) {
    // Parse the request URL and site URL
    $request_host = parse_url($url, PHP_URL_HOST);
    $site_host = parse_url(site_url(), PHP_URL_HOST);
    
    // If WordPress is making a request to itself
    if ($request_host === $site_host || 
        $request_host === 'dev.taxpayer.com' ||
        $request_host === 'localhost' ||
        $request_host === '127.0.0.1') {
        
        // Disable SSL verification for loopback requests (safe for self-requests)
        $args['sslverify'] = false;
        
        // Increase timeout for background processes
        $args['timeout'] = 30;
        $args['blocking'] = false; // Make it non-blocking
        
        // Remove any authentication requirements for loopback
        if (isset($args['headers'])) {
            unset($args['headers']['Authorization']);
        }
    }
    
    return $args;
}, 10, 2);

// Alternative: Use alternative cron method that doesn't require loopback
add_filter('cron_request', function($cron_request) {
    $cron_request['args']['blocking'] = false;
    $cron_request['args']['sslverify'] = false;
    $cron_request['args']['timeout'] = 0.01; // Fire and forget
    return $cron_request;
});

// For WP Offload Media: Disable loopback requirement for background tools
add_filter('as3cf_use_background_processes', '__return_true'); // Force enable even if loopback fails
add_filter('as3cf_background_process_force_http_loopback', '__return_false'); // Don't require HTTP loopback

