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
 * Get or create a Stripe Price ID for any donation amount
 * 
 * This function creates a new Price object in Stripe for custom amounts,
 * allowing donors to choose any monthly donation amount they want.
 * 
 * @param float $amount Monthly donation amount in dollars
 * @return string|WP_Error Stripe Price ID or error
 */
function ctf_get_or_create_price_id($amount) {
    try {
        // Ensure we have a valid Product ID for donations
        $product_id = ctf_get_or_create_donation_product();
        
        if (is_wp_error($product_id)) {
            return $product_id;
        }
        
        // Convert amount to cents (Stripe uses smallest currency unit)
        $amount_cents = intval($amount * 100);
        
        error_log("CTF Stripe: Creating price for \${$amount}/month ({$amount_cents} cents)");
        
        // Create a new Price object for this specific amount
        $price = \Stripe\Price::create([
            'product' => $product_id,
            'unit_amount' => $amount_cents,
            'currency' => 'cad',
            'recurring' => [
                'interval' => 'month',
                'interval_count' => 1,
            ],
            'metadata' => [
                'created_by' => 'ctf_donation_system',
                'amount_dollars' => $amount,
            ],
        ]);
        
        error_log("CTF Stripe: Price created - {$price->id} for \${$amount}/month");
        
        return $price->id;
        
    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log("CTF Stripe: Error creating price - " . $e->getMessage());
        return new WP_Error('price_creation_failed', 'Unable to create subscription price. Please try again.');
    }
}

/**
 * Get or create the main Donation Product in Stripe
 * 
 * All monthly donations use the same Product, with different Prices
 * 
 * @return string|WP_Error Stripe Product ID or error
 */
function ctf_get_or_create_donation_product() {
    // Check if we have a cached product ID
    $product_id = get_option('ctf_stripe_donation_product_id');
    
    if (!empty($product_id)) {
        // Verify the product still exists in Stripe
        try {
            \Stripe\Product::retrieve($product_id);
            return $product_id;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            // Product doesn't exist anymore, create a new one
            error_log("CTF Stripe: Cached product not found, creating new one");
        }
    }
    
    // Create a new Product
    try {
        $product = \Stripe\Product::create([
            'name' => 'Monthly Donation - Canadian Taxpayers Federation',
            'description' => 'Recurring monthly donation to support the Canadian Taxpayers Federation',
            'metadata' => [
                'type' => 'donation',
                'organization' => 'CTF',
            ],
        ]);
        
        // Cache the product ID
        update_option('ctf_stripe_donation_product_id', $product->id);
        
        error_log("CTF Stripe: Donation product created - {$product->id}");
        
        return $product->id;
        
    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log("CTF Stripe: Error creating product - " . $e->getMessage());
        return new WP_Error('product_creation_failed', 'Unable to setup subscription product. Please contact support.');
    }
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
        
        // Step 1: Get or create a Stripe Price ID for this amount
        $price_id = ctf_get_or_create_price_id($data['amount']);
        
        if (is_wp_error($price_id)) {
            error_log("CTF Stripe: Failed to create price for \${$data['amount']}/month");
            return $price_id; // Return the WP_Error
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

