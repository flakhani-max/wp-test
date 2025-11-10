<?php
/**
 * Donation Processing with Stripe Integration
 * 
 * This file handles:
 * - One-time donations (PaymentIntent)
 * - Monthly donations (Subscriptions)
 * - Integration with Stripe API
 */

if (!defined('ABSPATH')) exit;

// Load Composer autoloader for Stripe library
require_once CTF_CUSTOM_PLUGIN_PATH . 'vendor/autoload.php';

/**
 * Initialize Stripe and set API key
 * This function loads the Stripe library and configures it with your secret key
 */
function ctf_init_stripe() {
    $stripe_secret_key = getenv('STRIPE_SECRET_KEY');
    
    if (empty($stripe_secret_key)) {
        error_log('CTF Donations: STRIPE_SECRET_KEY not set in environment variables');
        return false;
    }
    
    // Initialize Stripe with your secret key
    \Stripe\Stripe::setApiKey($stripe_secret_key);
    
    // Set API version for consistency
    \Stripe\Stripe::setApiVersion('2023-10-16');
    
    return true;
}

/**
 * AJAX Handler for donation processing
 * This is called when the form is submitted from the frontend
 */
function ctf_handle_donation_submission() {
    // Step 1: Security - Verify nonce
    if (!isset($_POST['donation_nonce']) || !wp_verify_nonce($_POST['donation_nonce'], 'donation_form')) {
        wp_send_json_error(['message' => 'Security check failed. Please refresh and try again.']);
        return;
    }
    
    // Step 2: Initialize Stripe
    if (!ctf_init_stripe()) {
        wp_send_json_error(['message' => 'Payment system not configured. Please contact support.']);
        return;
    }
    
    // Step 3: Collect and validate form data
    $donation_data = ctf_collect_donation_data($_POST);
    
    if (is_wp_error($donation_data)) {
        wp_send_json_error(['message' => $donation_data->get_error_message()]);
        return;
    }
    
    // Step 4: Process based on frequency (one-time vs monthly)
    if ($donation_data['frequency'] === 'monthly') {
        $result = ctf_process_subscription($donation_data);
    } else {
        $result = ctf_process_onetime_donation($donation_data);
    }
    
    // Step 5: Return result to frontend
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    } else {
        wp_send_json_success($result);
    }
}

// Register AJAX handlers (for both logged-in and non-logged-in users)
add_action('wp_ajax_process_donation', 'ctf_handle_donation_submission');
add_action('wp_ajax_nopriv_process_donation', 'ctf_handle_donation_submission');

/**
 * Collect and validate donation data from the form
 */
function ctf_collect_donation_data($post_data) {
    // Required fields
    $required_fields = ['first_name', 'last_name', 'email', 'amount', 'payment_method_id'];
    
    foreach ($required_fields as $field) {
        if (empty($post_data[$field])) {
            return new WP_Error('missing_field', "Missing required field: {$field}");
        }
    }
    
    // Sanitize and structure the data
    $data = [
        'first_name' => sanitize_text_field($post_data['first_name']),
        'last_name' => sanitize_text_field($post_data['last_name']),
        'email' => sanitize_email($post_data['email']),
        'amount' => floatval($post_data['amount']),
        'frequency' => sanitize_text_field($post_data['donation_frequency'] ?? 'once'),
        'payment_method_id' => sanitize_text_field($post_data['payment_method_id']),
        'phone' => sanitize_text_field($post_data['phone'] ?? ''),
        'address' => sanitize_text_field($post_data['address'] ?? ''),
        'city' => sanitize_text_field($post_data['city'] ?? ''),
        'province' => sanitize_text_field($post_data['province'] ?? ''),
        'postal_code' => sanitize_text_field($post_data['postal_code'] ?? ''),
        'campaign_id' => sanitize_text_field($post_data['campaign_id'] ?? ''),
        'post_id' => intval($post_data['post_id'] ?? 0),
    ];
    
    // Validate amount
    if ($data['amount'] < 1) {
        return new WP_Error('invalid_amount', 'Donation amount must be at least $1.00');
    }
    
    return $data;
}

/**
 * Process a one-time donation using Stripe PaymentIntent
 * 
 * @param array $data Sanitized donation data
 * @return array|WP_Error Success data or error
 */
function ctf_process_onetime_donation($data) {
    try {
        // Convert amount to cents (Stripe requires integer cents, not dollars)
        $amount_in_cents = intval($data['amount'] * 100);
        
        error_log("CTF Stripe: Creating PaymentIntent for {$data['email']} - \${$data['amount']}");
        
        // Step 1: Create a PaymentIntent
        // This represents the intention to collect payment
        $payment_intent = \Stripe\PaymentIntent::create([
            'amount' => $amount_in_cents,
            'currency' => 'cad', // Canadian dollars
            'payment_method' => $data['payment_method_id'],
            'confirmation_method' => 'manual',
            'confirm' => true, // Immediately confirm the payment
            'description' => "Donation from {$data['first_name']} {$data['last_name']}",
            'receipt_email' => $data['email'],
            'payment_method_types' => ['card'], // Only accept cards (no redirects)
            'metadata' => [
                'donor_name' => "{$data['first_name']} {$data['last_name']}",
                'donor_email' => $data['email'],
                'donor_phone' => $data['phone'],
                'campaign_id' => $data['campaign_id'],
                'post_id' => $data['post_id'],
                'donation_type' => 'one-time',
            ],
        ]);
        
        // Step 2: Check if payment requires additional action (like 3D Secure)
        if ($payment_intent->status === 'requires_action' && 
            $payment_intent->next_action->type === 'use_stripe_sdk') {
            // Return to frontend for additional authentication
            return [
                'requires_action' => true,
                'payment_intent_client_secret' => $payment_intent->client_secret,
                'payment_intent_id' => $payment_intent->id,
            ];
        }
        
        // Step 3: Check if payment succeeded
        if ($payment_intent->status === 'succeeded') {
            error_log("CTF Stripe: Payment succeeded - {$payment_intent->id}");
            
            // TODO: Save donation record to WordPress database
            // ctf_save_donation_record($data, $payment_intent);
            
            return [
                'success' => true,
                'payment_intent_id' => $payment_intent->id,
                'amount' => $data['amount'],
                'message' => 'Thank you for your donation!',
            ];
        }
        
        // Step 4: Payment failed or has unexpected status
        error_log("CTF Stripe: Unexpected payment status - {$payment_intent->status}");
        return new WP_Error(
            'payment_failed',
            'Payment could not be processed. Please try again or contact support.'
        );
        
    } catch (\Stripe\Exception\CardException $e) {
        // Card was declined
        error_log("CTF Stripe: Card declined - " . $e->getMessage());
        return new WP_Error('card_declined', $e->getError()->message);
        
    } catch (\Stripe\Exception\InvalidRequestException $e) {
        // Invalid parameters
        error_log("CTF Stripe: Invalid request - " . $e->getMessage());
        return new WP_Error('invalid_request', 'Payment request invalid. Please contact support.');
        
    } catch (\Stripe\Exception\ApiErrorException $e) {
        // General Stripe API error
        error_log("CTF Stripe: API error - " . $e->getMessage());
        return new WP_Error('api_error', 'Payment system error. Please try again later.');
        
    } catch (Exception $e) {
        // Unexpected error
        error_log("CTF Stripe: Unexpected error - " . $e->getMessage());
        return new WP_Error('unexpected_error', 'An unexpected error occurred. Please contact support.');
    }
}

/**
 * Get Stripe Price ID for a given monthly donation amount
 * 
 * TODO: Replace these with your actual Stripe Price IDs from your dashboard
 * Go to: https://dashboard.stripe.com/products and create recurring prices
 * 
 * @param float $amount Monthly donation amount in dollars
 * @return string|null Stripe Price ID or null if not found
 */
function ctf_get_stripe_price_id($amount) {
    // Map donation amounts to Stripe Price IDs
    // Replace these placeholder IDs with your actual Price IDs from Stripe Dashboard
    $price_map = [
        10   => 'price_1SQwzzKqkrhlTgYRpA5hgWSg',
        15  => 'price_1SQx0AKqkrhlTgYRRjh9KAzM',
        20  => 'price_1SQx0NKqkrhlTgYRgf7dy5Cl',
        25  => 'price_1SQx0WKqkrhlTgYRvvLUYQpO',
        50  => 'price_1SQx0iKqkrhlTgYRr7pp5EC2',
        100 => 'price_1SQx0uKqkrhlTgYR7wneXeoz',
        200 => 'price_1SQx12KqkrhlTgYRzxs9rM23',
    ];
    
    // Convert amount to integer for lookup
    $amount_int = intval($amount);
    
    return $price_map[$amount_int] ?? null;
}

/**
 * Process a monthly subscription using Stripe Subscriptions
 * 
 * @param array $data Sanitized donation data
 * @return array|WP_Error Success data or error
 */
function ctf_process_subscription($data) {
    try {
        error_log("CTF Stripe: Creating subscription for {$data['email']} - \${$data['amount']}/month");
        
        // Step 1: Get the Stripe Price ID for this amount
        $price_id = ctf_get_stripe_price_id($data['amount']);
        
        if (empty($price_id) || strpos($price_id, 'REPLACE_WITH') !== false) {
            error_log("CTF Stripe: No price configured for \${$data['amount']}/month");
            return new WP_Error(
                'invalid_amount',
                'This subscription amount is not available. Please choose a different amount or contact support.'
            );
        }
        
        // Step 2: Create a Stripe Customer
        // This stores the donor's information in Stripe
        $customer = \Stripe\Customer::create([
            'email' => $data['email'],
            'name' => "{$data['first_name']} {$data['last_name']}",
            'phone' => $data['phone'],
            'payment_method' => $data['payment_method_id'],
            'invoice_settings' => [
                'default_payment_method' => $data['payment_method_id'],
            ],
            'address' => [
                'line1' => $data['address'],
                'city' => $data['city'],
                'state' => $data['province'],
                'postal_code' => $data['postal_code'],
                'country' => 'CA', // Canada
            ],
            'metadata' => [
                'campaign_id' => $data['campaign_id'],
                'post_id' => $data['post_id'],
                'donor_phone' => $data['phone'],
            ],
        ]);
        
        error_log("CTF Stripe: Customer created - {$customer->id}");
        
        // Step 3: Create the Subscription
        $subscription = \Stripe\Subscription::create([
            'customer' => $customer->id,
            'items' => [
                ['price' => $price_id],
            ],
            'payment_settings' => [
                'payment_method_types' => ['card'],
                'save_default_payment_method' => 'on_subscription',
            ],
            'expand' => ['latest_invoice.payment_intent'],
            'metadata' => [
                'donor_name' => "{$data['first_name']} {$data['last_name']}",
                'donor_email' => $data['email'],
                'campaign_id' => $data['campaign_id'],
                'post_id' => $data['post_id'],
                'donation_type' => 'monthly',
            ],
        ]);
        
        error_log("CTF Stripe: Subscription created - {$subscription->id}");
        
        // Step 4: Check the subscription status
        $invoice = $subscription->latest_invoice;
        $payment_intent = $invoice->payment_intent;
        
        // Check if first payment requires action (like 3D Secure)
        if ($payment_intent && $payment_intent->status === 'requires_action') {
            return [
                'requires_action' => true,
                'payment_intent_client_secret' => $payment_intent->client_secret,
                'subscription_id' => $subscription->id,
                'customer_id' => $customer->id,
            ];
        }
        
        // Step 5: Check if subscription is active
        if ($subscription->status === 'active' || $subscription->status === 'trialing') {
            error_log("CTF Stripe: Subscription active - {$subscription->id}");
            
            // TODO: Save subscription record to WordPress database
            // ctf_save_subscription_record($data, $subscription, $customer);
            
            return [
                'success' => true,
                'subscription_id' => $subscription->id,
                'customer_id' => $customer->id,
                'amount' => $data['amount'],
                'message' => 'Thank you for your monthly donation! Your first payment has been processed.',
            ];
        }
        
        // Subscription has unexpected status
        error_log("CTF Stripe: Unexpected subscription status - {$subscription->status}");
        return new WP_Error(
            'subscription_failed',
            'Subscription could not be created. Please try again or contact support.'
        );
        
    } catch (\Stripe\Exception\CardException $e) {
        // Card was declined
        error_log("CTF Stripe: Card declined (subscription) - " . $e->getMessage());
        return new WP_Error('card_declined', $e->getError()->message);
        
    } catch (\Stripe\Exception\InvalidRequestException $e) {
        // Invalid parameters
        error_log("CTF Stripe: Invalid request (subscription) - " . $e->getMessage());
        return new WP_Error('invalid_request', 'Subscription request invalid. Please contact support.');
        
    } catch (\Stripe\Exception\ApiErrorException $e) {
        // General Stripe API error
        error_log("CTF Stripe: API error (subscription) - " . $e->getMessage());
        return new WP_Error('api_error', 'Payment system error. Please try again later.');
        
    } catch (Exception $e) {
        // Unexpected error
        error_log("CTF Stripe: Unexpected error (subscription) - " . $e->getMessage());
        return new WP_Error('unexpected_error', 'An unexpected error occurred. Please contact support.');
    }
}

