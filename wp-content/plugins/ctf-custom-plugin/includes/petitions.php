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
    // Verify nonce for security
    if (!isset($_POST['petition_nonce']) || !wp_verify_nonce($_POST['petition_nonce'], 'ctf_petition_form')) {
        wp_die('Security check failed');
    }

    // Sanitize input data
    $first_name = sanitize_text_field($_POST['first_name'] ?? '');
    $last_name = sanitize_text_field($_POST['last_name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $zip_code = sanitize_text_field($_POST['zip_code'] ?? '');
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    $petition_id = sanitize_text_field($_POST['petition_id'] ?? '');

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
        wp_send_json_error(array(
            'message' => 'Please fix the following errors:',
            'errors' => $errors
        ));
    }

    // Attempt to subscribe to Mailchimp
    $mailchimp_result = ctf_subscribe_to_mailchimp($email, $first_name, $last_name, $zip_code, $phone);
    
    if ($mailchimp_result['success']) {
        // Store petition signature locally
        $signature_id = ctf_store_petition_signature($first_name, $last_name, $email, $zip_code, $phone, $petition_id);
        
        wp_send_json_success(array(
            'message' => 'Thank you for signing the petition! You have been added to our mailing list.',
            'signature_id' => $signature_id
        ));
    } else {
        wp_send_json_error(array(
            'message' => 'There was an error submitting your petition. Please try again.',
            'error' => $mailchimp_result['error']
        ));
    }

    wp_die();
}
?>