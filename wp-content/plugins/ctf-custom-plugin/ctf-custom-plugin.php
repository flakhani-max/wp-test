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

// Load plugin modules first (before any hooks)
require_once CTF_CUSTOM_PLUGIN_PATH . 'includes/logging.php';
require_once CTF_CUSTOM_PLUGIN_PATH . 'includes/logging-admin.php';
require_once CTF_CUSTOM_PLUGIN_PATH . 'includes/secret-manager.php';
require_once CTF_CUSTOM_PLUGIN_PATH . 'includes/stripe-payment-handler.php';
require_once CTF_CUSTOM_PLUGIN_PATH . 'includes/petitions.php';
require_once CTF_CUSTOM_PLUGIN_PATH . 'includes/mailchimp.php';

// Load custom post types
require_once CTF_CUSTOM_PLUGIN_PATH . 'includes/donations-custom_post_type.php';
require_once CTF_CUSTOM_PLUGIN_PATH . 'includes/petition-custom_post_type.php';

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
    // Create logging table (function is now available)
    if (function_exists('ctf_create_logging_table')) {
        ctf_create_logging_table();
    }
    
    // Register post types (functions are now available)
    if (function_exists('ctf_register_donation_cpt')) {
        ctf_register_donation_cpt();
    }
    if (function_exists('ctf_register_petition_post_type')) {
        ctf_register_petition_post_type();
    }
    
    // Flush rewrite rules to register new post types
    flush_rewrite_rules();
    
    // Log plugin activation (function is now available)
    if (function_exists('ctf_log_info')) {
        ctf_log_info('CTF Custom Plugin activated', 'plugin');
    }
}

// Add admin function to manually flush rewrite rules if needed
add_action('admin_init', 'ctf_check_rewrite_rules');

function ctf_check_rewrite_rules() {
    // Check if we need to flush rewrite rules
    if (isset($_GET['ctf_flush_rules']) && current_user_can('manage_options')) {
        flush_rewrite_rules();
        
        // Only log if logging function exists
        if (function_exists('ctf_log_info')) {
            ctf_log_info('Rewrite rules manually flushed', 'plugin');
        }
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success"><p>Rewrite rules flushed! Petition URLs should now work correctly.</p></div>';
        });
    }
}

// Add debug info to admin if needed
add_action('admin_notices', 'ctf_debug_petition_urls');

function ctf_debug_petition_urls() {
    if (isset($_GET['page']) && $_GET['page'] === 'ctf-logs') {
        $petition_archive_url = get_post_type_archive_link('petition');
        echo '<div class="notice notice-info">';
        echo '<p><strong>Debug Info:</strong></p>';
        echo '<p>Petition Archive URL: <a href="' . esc_url($petition_archive_url) . '">' . esc_html($petition_archive_url) . '</a></p>';
        echo '<p>If petition links don\'t work, <a href="' . admin_url('?ctf_flush_rules=1') . '">click here to flush rewrite rules</a></p>';
        echo '</div>';
    }
}

// Plugin deactivation hook  
register_deactivation_hook(__FILE__, 'ctf_custom_plugin_deactivate');

function ctf_custom_plugin_deactivate() {
    // Flush rewrite rules to clean up
    flush_rewrite_rules();
}

?>