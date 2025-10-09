<?php
/*
Plugin Name: WP Petition Mailchimp
Description: Handles petition form submissions and integrates with Mailchimp.
Version: 1.0
Author: Your Name
*/

if (!defined('ABSPATH')) exit;

// Settings: Add your Mailchimp API key and Audience ID here
if (!defined('WP_PETITION_MAILCHIMP_API_KEY')) {
    define('WP_PETITION_MAILCHIMP_API_KEY', 'YOUR_API_KEY');
}
if (!defined('WP_PETITION_MAILCHIMP_AUDIENCE_ID')) {
    define('WP_PETITION_MAILCHIMP_AUDIENCE_ID', 'YOUR_AUDIENCE_ID');
}

// Handle form submission
add_action('admin_post_nopriv_petition_mailchimp_submit', 'wp_petition_mailchimp_handle_form');
add_action('admin_post_petition_mailchimp_submit', 'wp_petition_mailchimp_handle_form');

function wp_petition_mailchimp_handle_form() {
    if (
        isset($_POST['name'], $_POST['email'], $_POST['postal']) &&
        is_email($_POST['email'])
    ) {
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $postal = sanitize_text_field($_POST['postal']);
        $sms_optin = !empty($_POST['sms_optin']) ? 'yes' : 'no';

        $result = wp_petition_mailchimp_subscribe($email, [
            'FNAME' => $name,
            'POSTAL' => $postal,
            'SMS_OPTIN' => $sms_optin
        ]);

        if ($result === true) {
            wp_redirect(add_query_arg('petition_success', '1', wp_get_referer()));
            exit;
        } else {
            wp_redirect(add_query_arg('petition_error', '1', wp_get_referer()));
            exit;
        }
    }
    wp_redirect(add_query_arg('petition_error', '1', wp_get_referer()));
    exit;
}

function wp_petition_mailchimp_subscribe($email, $merge_fields = []) {
    $api_key = WP_PETITION_MAILCHIMP_API_KEY;
    $audience_id = WP_PETITION_MAILCHIMP_AUDIENCE_ID;
    $data_center = substr($api_key, strpos($api_key, '-') + 1);
    $url = 'https://' . $data_center . '.api.mailchimp.com/3.0/lists/' . $audience_id . '/members/';

    $body = [
        'email_address' => $email,
        'status' => 'subscribed',
        'merge_fields' => $merge_fields
    ];

    $response = wp_remote_post($url, [
        'headers' => [
            'Authorization' => 'apikey ' . $api_key,
            'Content-Type' => 'application/json'
        ],
        'body' => json_encode($body)
    ]);

    if (is_wp_error($response)) {
        return false;
    }
    $code = wp_remote_retrieve_response_code($response);
    return ($code == 200 || $code == 201);
}
