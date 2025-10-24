<?php
/**
 * Legacy Function Aliases
 * 
 * Maintains backward compatibility for function names that may
 * be used in templates or other parts of the codebase.
 */

if (!defined('ABSPATH')) exit;

// ========================================
// LEGACY PETITION FUNCTIONS
// ========================================

/**
 * Legacy alias for petition submission handling
 * @deprecated Use ctf_handle_petition_submission() instead
 */
function ctf_petition_mailchimp_submit() {
    return ctf_handle_petition_submission();
}

/**
 * Legacy alias for Mailchimp subscription
 * @deprecated Use ctf_subscribe_to_mailchimp() instead
 */
function subscribe_to_mailchimp($email, $first_name = '', $last_name = '', $zip_code = '', $phone = '') {
    return ctf_subscribe_to_mailchimp($email, $first_name, $last_name, $zip_code, $phone);
}

/**
 * Legacy alias for petition form generation
 * @deprecated Use ctf_generate_petition_form() instead
 */
function generate_petition_form($args = array()) {
    return ctf_generate_petition_form($args);
}

// ========================================
// LEGACY DONATION FUNCTIONS
// ========================================

/**
 * Legacy alias for donation data retrieval
 * @deprecated Use ctf_get_donation_data() instead
 */
function get_donation_data($post_id = null) {
    return ctf_get_donation_data($post_id);
}

/**
 * Legacy alias for donation amounts
 * @deprecated Use ctf_get_donation_amounts_safe() instead
 */
function get_donation_amounts($post_id = null) {
    return ctf_get_donation_amounts_safe($post_id);
}

/**
 * Legacy alias for auto tag checking
 * @deprecated Use ctf_get_donation_auto_tag_safe() instead
 */
function get_donation_auto_tag($post_id = null) {
    return ctf_get_donation_auto_tag_safe($post_id);
}

/**
 * Legacy alias for title display checking
 * @deprecated Use ctf_get_donation_show_title_safe() instead
 */
function get_donation_show_title($post_id = null) {
    return ctf_get_donation_show_title_safe($post_id);
}

// ========================================
// LEGACY MAILCHIMP FUNCTIONS
// ========================================

/**
 * Legacy alias for Mailchimp API key retrieval
 * @deprecated Use ctf_get_mailchimp_api_key() instead
 */
function get_mailchimp_api_key() {
    return ctf_get_mailchimp_api_key();
}

/**
 * Legacy alias for Mailchimp list ID retrieval
 * @deprecated Use ctf_get_mailchimp_list_id() instead
 */
function get_mailchimp_list_id() {
    return ctf_get_mailchimp_list_id();
}

/**
 * Legacy alias for donation Mailchimp subscription
 * @deprecated Use ctf_subscribe_donation_to_mailchimp() instead
 */
function subscribe_donation_to_mailchimp($email, $first_name, $last_name, $zip_code, $phone, $amount, $frequency = 'onetime', $post_id = null) {
    return ctf_subscribe_donation_to_mailchimp($email, $first_name, $last_name, $zip_code, $phone, $amount, $frequency, $post_id);
}

// ========================================
// LEGACY SECRET MANAGER FUNCTIONS
// ========================================

/**
 * Legacy alias for secret retrieval
 * @deprecated Use ctf_get_secret() instead
 */
function get_secret($secret_name) {
    return ctf_get_secret($secret_name);
}

// ========================================
// LEGACY HELPER FUNCTIONS
// ========================================

/**
 * Legacy function for backward compatibility with old petition template
 * @deprecated Use ctf_get_petition_signature_count() instead
 */
function get_petition_signature_count($petition_id = null) {
    return ctf_get_petition_signature_count($petition_id);
}

/**
 * Legacy function for backward compatibility
 * @deprecated Use ctf_get_recent_petition_signatures() instead
 */
function get_recent_petition_signatures($limit = 10, $petition_id = null) {
    return ctf_get_recent_petition_signatures($limit, $petition_id);
}

// ========================================
// LEGACY AJAX HOOKS (maintained for compatibility)
// ========================================

// Keep old AJAX action names working
add_action('wp_ajax_submit_petition', 'ctf_handle_petition_submission');
add_action('wp_ajax_nopriv_submit_petition', 'ctf_handle_petition_submission');

// ========================================
// LEGACY SHORTCODES
// ========================================

/**
 * Legacy shortcode for petition form
 * @deprecated Use [ctf_petition_form] instead
 */
function petition_form_shortcode($atts) {
    $atts = shortcode_atts(array(
        'petition_id' => get_the_ID(),
        'show_phone' => true,
        'submit_text' => 'Sign the Petition'
    ), $atts);
    
    return ctf_generate_petition_form($atts);
}
add_shortcode('petition_form', 'petition_form_shortcode');

/**
 * New shortcode for petition form
 */
function ctf_petition_form_shortcode($atts) {
    $atts = shortcode_atts(array(
        'petition_id' => get_the_ID(),
        'show_phone' => true,
        'submit_text' => 'Sign the Petition',
        'form_id' => 'petition-form',
        'ajax' => true
    ), $atts);
    
    return ctf_generate_petition_form($atts);
}
add_shortcode('ctf_petition_form', 'ctf_petition_form_shortcode');

/**
 * Shortcode for petition signature count
 */
function ctf_petition_count_shortcode($atts) {
    $atts = shortcode_atts(array(
        'petition_id' => get_the_ID()
    ), $atts);
    
    return ctf_get_petition_signature_count($atts['petition_id']);
}
add_shortcode('ctf_petition_count', 'ctf_petition_count_shortcode');

// ========================================
// DEPRECATION NOTICES (for development)
// ========================================

/**
 * Log deprecation notice for development
 * 
 * @param string $function Deprecated function name
 * @param string $replacement Replacement function name
 */
function ctf_log_deprecation($function, $replacement = '') {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $message = "Function {$function} is deprecated.";
        if ($replacement) {
            $message .= " Use {$replacement} instead.";
        }
        error_log('[CTF Plugin] ' . $message);
    }
}

// ========================================
// TEMPLATE COMPATIBILITY FUNCTIONS
// ========================================

/**
 * Check if function exists before defining legacy aliases
 * This prevents conflicts if multiple versions are loaded
 */
if (!function_exists('wp_petition_mailchimp_get_api_key')) {
    function wp_petition_mailchimp_get_api_key() {
        ctf_log_deprecation('wp_petition_mailchimp_get_api_key', 'ctf_get_mailchimp_api_key');
        return ctf_get_mailchimp_api_key();
    }
}

if (!function_exists('wp_petition_mailchimp_get_list_id')) {
    function wp_petition_mailchimp_get_list_id() {
        ctf_log_deprecation('wp_petition_mailchimp_get_list_id', 'ctf_get_mailchimp_list_id');
        return ctf_get_mailchimp_list_id();
    }
}

// ========================================
// COMPATIBILITY WITH OLD PLUGIN NAME
// ========================================

/**
 * Maintain compatibility with old plugin activation hooks
 */
register_activation_hook(__FILE__, 'ctf_custom_plugin_activate');
register_deactivation_hook(__FILE__, 'ctf_custom_plugin_deactivate');

// Also register with old plugin file path for backward compatibility
$old_plugin_file = str_replace('ctf-custom-plugin', 'wp-petition-mailchimp', __FILE__);
if (file_exists($old_plugin_file)) {
    register_activation_hook($old_plugin_file, 'ctf_custom_plugin_activate');
    register_deactivation_hook($old_plugin_file, 'ctf_custom_plugin_deactivate');
}