<?php
/**
 * PayPal Donation Processing
 * 
 * This file handles PayPal donations (one-time only for now)
 */

if (!defined('ABSPATH')) exit;

/**
 * AJAX Handler for PayPal donation processing
 */
function ctf_handle_paypal_donation() {
    // Step 1: Security - Verify nonce
    if (!isset($_POST['donation_nonce']) || !wp_verify_nonce($_POST['donation_nonce'], 'donation_form')) {
        wp_send_json_error(['message' => 'Security check failed. Please refresh and try again.']);
        return;
    }
    
    // Step 2: Collect and validate form data
    $donation_data = ctf_collect_donation_data($_POST);
    
    if (is_wp_error($donation_data)) {
        wp_send_json_error(['message' => $donation_data->get_error_message()]);
        return;
    }
    
    // Step 3: Validate PayPal order ID
    if (empty($_POST['paypal_order_id'])) {
        wp_send_json_error(['message' => 'PayPal order ID is missing.']);
        return;
    }
    
    $paypal_order_id = sanitize_text_field($_POST['paypal_order_id']);
    
    // Step 4: Verify the PayPal order with PayPal API (optional but recommended)
    // For now, we'll trust the client-side capture
    // In production, you should verify with PayPal's API using the order ID
    
    error_log("CTF PayPal: Processing donation - Order ID: {$paypal_order_id}, Amount: \${$donation_data['amount']}, Email: {$donation_data['email']}");
    
    // Step 5: Save donation record to WordPress database (TODO)
    // ctf_save_donation_record($donation_data, ['paypal_order_id' => $paypal_order_id, 'payment_source' => 'paypal']);
    
    // Step 6: Send success response
    wp_send_json_success([
        'success' => true,
        'paypal_order_id' => $paypal_order_id,
        'amount' => $donation_data['amount'],
        'message' => 'Thank you for your donation via PayPal!',
    ]);
}

// Register AJAX handlers (for both logged-in and non-logged-in users)
add_action('wp_ajax_process_paypal_donation', 'ctf_handle_paypal_donation');
add_action('wp_ajax_nopriv_process_paypal_donation', 'ctf_handle_paypal_donation');

/**
 * Optional: Verify PayPal order with PayPal API
 * This adds extra security by confirming the order with PayPal's servers
 * 
 * @param string $order_id PayPal order ID
 * @return array|WP_Error Order details or error
 */
function ctf_verify_paypal_order($order_id) {
    $paypal_client_id = getenv('PAYPAL_CLIENT_ID');
    $paypal_secret = getenv('PAYPAL_SECRET');
    
    if (empty($paypal_client_id) || empty($paypal_secret)) {
        return new WP_Error('paypal_config', 'PayPal is not configured.');
    }
    
    // Get PayPal access token
    $auth_response = wp_remote_post('https://api-m.paypal.com/v1/oauth2/token', [
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode("{$paypal_client_id}:{$paypal_secret}"),
            'Content-Type' => 'application/x-www-form-urlencoded',
        ],
        'body' => 'grant_type=client_credentials',
    ]);
    
    if (is_wp_error($auth_response)) {
        return $auth_response;
    }
    
    $auth_body = json_decode(wp_remote_retrieve_body($auth_response), true);
    $access_token = $auth_body['access_token'] ?? '';
    
    if (empty($access_token)) {
        return new WP_Error('paypal_auth', 'Failed to authenticate with PayPal.');
    }
    
    // Get order details from PayPal
    $order_response = wp_remote_get("https://api-m.paypal.com/v2/checkout/orders/{$order_id}", [
        'headers' => [
            'Authorization' => "Bearer {$access_token}",
            'Content-Type' => 'application/json',
        ],
    ]);
    
    if (is_wp_error($order_response)) {
        return $order_response;
    }
    
    $order_data = json_decode(wp_remote_retrieve_body($order_response), true);
    
    return $order_data;
}

