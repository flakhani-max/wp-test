<?php
/**
 * Petition Form Handling
 * 
 * Handles petition form submission, validation, and processing
 * for the CTF Custom Plugin.
 */

if (!defined('ABSPATH')) exit;

// Handle petition form submissions
add_action('wp_ajax_ctf_submit_petition', 'ctf_handle_petition_submission');
add_action('wp_ajax_nopriv_ctf_submit_petition', 'ctf_handle_petition_submission');

/**
 * Handle petition form submission
 */
function ctf_handle_petition_submission() {
    ctf_log_info('Processing petition submission', 'petition');
    
    // Verify nonce for security
    if (!isset($_POST['ctf_petition_nonce']) || !wp_verify_nonce($_POST['ctf_petition_nonce'], 'ctf_petition_nonce')) {
        ctf_log_warning('Nonce verification failed', 'petition', array('ip' => ctf_get_client_ip()));
        wp_send_json_error(array('message' => 'Security check failed'));
    }

    // Sanitize input data
    $first_name = sanitize_text_field($_POST['first_name'] ?? '');
    $last_name = sanitize_text_field($_POST['last_name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $zip_code = sanitize_text_field($_POST['zip_code'] ?? '');
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    $petition_id = sanitize_text_field($_POST['petition_id'] ?? '');

    ctf_log_info('Form data received', 'petition', array(
        'name' => $first_name . ' ' . $last_name,
        'email' => $email,
        'zip_code' => $zip_code,
        'petition_id' => $petition_id
    ));

    // Validate required fields
    $errors = array();
    
    if (empty($first_name)) {
        $errors[] = 'First name is required';
    }
    
    if (empty($last_name)) {
        $errors[] = 'Last name is required';
    }
    
    if (empty($email) || !is_email($email)) {
        $errors[] = 'Valid email is required';
    }
    
    if (empty($zip_code)) {
        $errors[] = 'ZIP code is required';
    }

    if (!empty($errors)) {
        ctf_log_warning('Validation errors', 'petition', array('errors' => $errors));
        wp_send_json_error(array(
            'message' => 'Please fix the following errors:',
            'errors' => $errors
        ));
    }

    // Add to Mailchimp - this is the main goal
    $mailchimp_result = ctf_subscribe_petition_to_mailchimp($email, $first_name, $last_name, $zip_code, $phone, $petition_id);
    
    if ($mailchimp_result['success']) {
        ctf_log_info('Successfully added to Mailchimp', 'petition', array(
            'email' => $email,
            'petition_id' => $petition_id
        ));
        wp_send_json_success(array(
            'message' => 'Thank you for signing the petition! You have been added to our mailing list.'
        ));
    } else {
        ctf_log_error('Mailchimp subscription failed', 'petition', array(
            'email' => $email,
            'error' => $mailchimp_result['error'],
            'petition_id' => $petition_id
        ));
        wp_send_json_error(array(
            'message' => 'There was an error submitting your petition. Please try again.',
            'error' => $mailchimp_result['error']
        ));
    }

    wp_die();
}

/**
 * AJAX handler to get fresh nonce for petition forms
 * This allows cached pages to get fresh nonces dynamically
 */
function ctf_get_petition_nonce_handler() {
    // Generate fresh nonce for petition submission
    $nonce = wp_create_nonce('ctf_petition_nonce');
    
    wp_send_json_success(array(
        'nonce' => $nonce,
        'timestamp' => current_time('timestamp')
    ));
}
add_action('wp_ajax_ctf_get_petition_nonce', 'ctf_get_petition_nonce_handler');
add_action('wp_ajax_nopriv_ctf_get_petition_nonce', 'ctf_get_petition_nonce_handler');

?>