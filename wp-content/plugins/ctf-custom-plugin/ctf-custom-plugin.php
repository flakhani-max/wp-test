<?php
/*
Plugin Name: CTF Custom Plugin
Plugin URI: https://taxpayer.com
Description: Custom functionality for Canadian Taxpayers Federation including petitions, donations, and Mailchimp integration.
Version: 2.0
Author: Canadian Taxpayers Federation
Text Domain: ctf-custom
*/

if (!defined('ABSPATH')) exit;

// Plugin constants
define('CTF_CUSTOM_PLUGIN_VERSION', '2.0');
define('CTF_CUSTOM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CTF_CUSTOM_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Legacy constants for backward compatibility
if (!defined('CTF_MAILCHIMP_API_KEY')) {
    define('CTF_MAILCHIMP_API_KEY', 'YOUR_API_KEY'); // Fallback only
}
if (!defined('CTF_MAILCHIMP_AUDIENCE_ID')) {
    define('CTF_MAILCHIMP_AUDIENCE_ID', 'YOUR_AUDIENCE_ID'); // Fallback only
}
if (!defined('WP_PETITION_MAILCHIMP_API_KEY')) {
    define('WP_PETITION_MAILCHIMP_API_KEY', CTF_MAILCHIMP_API_KEY);
}
if (!defined('WP_PETITION_MAILCHIMP_AUDIENCE_ID')) {
    define('WP_PETITION_MAILCHIMP_AUDIENCE_ID', CTF_MAILCHIMP_AUDIENCE_ID);
}

// Plugin activation hook
register_activation_hook(__FILE__, 'ctf_custom_plugin_activate');

function ctf_custom_plugin_activate() {
    // Load modules to register post types
    require_once CTF_CUSTOM_PLUGIN_PATH . 'includes/donations.php';
    require_once CTF_CUSTOM_PLUGIN_PATH . 'includes/petitions.php';
    
    // Register post types
    if (function_exists('ctf_register_donation_cpt')) {
        ctf_register_donation_cpt();
    }
    if (function_exists('ctf_register_petition_signature_cpt')) {
        ctf_register_petition_signature_cpt();
    }
    
    // Flush rewrite rules to register new post types
    flush_rewrite_rules();
}

// Plugin deactivation hook  
register_deactivation_hook(__FILE__, 'ctf_custom_plugin_deactivate');

function ctf_custom_plugin_deactivate() {
    // Flush rewrite rules to clean up
    flush_rewrite_rules();
}

// Load plugin modules
require_once CTF_CUSTOM_PLUGIN_PATH . 'includes/secret-manager.php';
require_once CTF_CUSTOM_PLUGIN_PATH . 'includes/donations.php';
require_once CTF_CUSTOM_PLUGIN_PATH . 'includes/petitions.php';
require_once CTF_CUSTOM_PLUGIN_PATH . 'includes/mailchimp.php';

// Legacy function aliases for backward compatibility
require_once CTF_CUSTOM_PLUGIN_PATH . 'includes/legacy.php';
