<?php
/**
 * Mailchimp Integration
 * 
 * Handles all Mailchimp API interactions for both petition
 * signups and donation subscriptions.
 */

if (!defined('ABSPATH')) exit;

// ========================================
// MAILCHIMP SUBSCRIPTION FUNCTIONS
// ========================================

/**
 * Subscribe email to Mailchimp list
 * 
 * @param string $email Email address
 * @param string $first_name First name
 * @param string $last_name Last name
 * @param string $zip_code ZIP code
 * @param string $phone Phone number
 * @param array $tags Additional tags for the subscriber
 * @return array Result array with success/error information
 */
function ctf_subscribe_to_mailchimp($email, $first_name = '', $last_name = '', $zip_code = '', $phone = '', $tags = array()) {
    $api_key = ctf_get_mailchimp_api_key();
    $list_id = ctf_get_mailchimp_list_id();
    
    if (!$api_key || !$list_id) {
        return array(
            'success' => false,
            'error' => 'Mailchimp configuration missing'
        );
    }
    
    // Extract datacenter from API key
    $datacenter = explode('-', $api_key)[1] ?? '';
    if (!$datacenter) {
        return array(
            'success' => false,
            'error' => 'Invalid Mailchimp API key format'
        );
    }
    
    $url = "https://{$datacenter}.api.mailchimp.com/3.0/lists/{$list_id}/members";
    
    $data = array(
        'email_address' => $email,
        'status' => 'subscribed',
        'merge_fields' => array()
    );
    
    // Add merge fields if provided
    if (!empty($first_name)) {
        $data['merge_fields']['FNAME'] = $first_name;
    }
    
    if (!empty($last_name)) {
        $data['merge_fields']['LNAME'] = $last_name;
    }
    
    if (!empty($zip_code)) {
        $data['merge_fields']['ZIP'] = $zip_code;
    }
    
    if (!empty($phone)) {
        $data['merge_fields']['PHONE'] = $phone;
    }
    
    // Add tags if provided
    if (!empty($tags) && is_array($tags)) {
        $data['tags'] = $tags;
    }
    
    $args = array(
        'method' => 'POST',
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode('user:' . $api_key),
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode($data),
        'timeout' => 30
    );
    
    $response = wp_remote_request($url, $args);
    
    if (is_wp_error($response)) {
        return array(
            'success' => false,
            'error' => 'HTTP request failed: ' . $response->get_error_message()
        );
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    $data = json_decode($response_body, true);
    
    if ($response_code === 200) {
        return array(
            'success' => true,
            'data' => $data
        );
    } elseif ($response_code === 400 && isset($data['title']) && $data['title'] === 'Member Exists') {
        // Update existing member
        return ctf_update_mailchimp_subscriber($email, $first_name, $last_name, $zip_code, $phone, $tags);
    } else {
        return array(
            'success' => false,
            'error' => isset($data['detail']) ? $data['detail'] : 'Unknown Mailchimp error',
            'response_code' => $response_code,
            'response_data' => $data
        );
    }
}

/**
 * Update existing Mailchimp subscriber
 * 
 * @param string $email Email address
 * @param string $first_name First name
 * @param string $last_name Last name
 * @param string $zip_code ZIP code
 * @param string $phone Phone number
 * @param array $tags Additional tags for the subscriber
 * @return array Result array with success/error information
 */
function ctf_update_mailchimp_subscriber($email, $first_name = '', $last_name = '', $zip_code = '', $phone = '', $tags = array()) {
    $api_key = ctf_get_mailchimp_api_key();
    $list_id = ctf_get_mailchimp_list_id();
    
    if (!$api_key || !$list_id) {
        return array(
            'success' => false,
            'error' => 'Mailchimp configuration missing'
        );
    }
    
    // Extract datacenter from API key
    $datacenter = explode('-', $api_key)[1] ?? '';
    if (!$datacenter) {
        return array(
            'success' => false,
            'error' => 'Invalid Mailchimp API key format'
        );
    }
    
    $subscriber_hash = md5(strtolower($email));
    $url = "https://{$datacenter}.api.mailchimp.com/3.0/lists/{$list_id}/members/{$subscriber_hash}";
    
    $data = array(
        'status_if_new' => 'subscribed',
        'merge_fields' => array()
    );
    
    // Add merge fields if provided
    if (!empty($first_name)) {
        $data['merge_fields']['FNAME'] = $first_name;
    }
    
    if (!empty($last_name)) {
        $data['merge_fields']['LNAME'] = $last_name;
    }
    
    if (!empty($zip_code)) {
        $data['merge_fields']['ZIP'] = $zip_code;
    }
    
    if (!empty($phone)) {
        $data['merge_fields']['PHONE'] = $phone;
    }
    
    $args = array(
        'method' => 'PUT',
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode('user:' . $api_key),
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode($data),
        'timeout' => 30
    );
    
    $response = wp_remote_request($url, $args);
    
    if (is_wp_error($response)) {
        return array(
            'success' => false,
            'error' => 'HTTP request failed: ' . $response->get_error_message()
        );
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    $data = json_decode($response_body, true);
    
    if ($response_code === 200) {
        $result = array(
            'success' => true,
            'data' => $data
        );
        
        // Add tags if provided
        if (!empty($tags) && is_array($tags)) {
            ctf_add_mailchimp_tags($email, $tags);
        }
        
        return $result;
    } else {
        return array(
            'success' => false,
            'error' => isset($data['detail']) ? $data['detail'] : 'Unknown Mailchimp error',
            'response_code' => $response_code,
            'response_data' => $data
        );
    }
}

/**
 * Add tags to Mailchimp subscriber
 * 
 * @param string $email Email address
 * @param array $tags Array of tags to add
 * @return array Result array with success/error information
 */
function ctf_add_mailchimp_tags($email, $tags) {
    if (empty($tags) || !is_array($tags)) {
        return array('success' => true, 'message' => 'No tags to add');
    }
    
    $api_key = ctf_get_mailchimp_api_key();
    $list_id = ctf_get_mailchimp_list_id();
    
    if (!$api_key || !$list_id) {
        return array(
            'success' => false,
            'error' => 'Mailchimp configuration missing'
        );
    }
    
    // Extract datacenter from API key
    $datacenter = explode('-', $api_key)[1] ?? '';
    if (!$datacenter) {
        return array(
            'success' => false,
            'error' => 'Invalid Mailchimp API key format'
        );
    }
    
    $subscriber_hash = md5(strtolower($email));
    $url = "https://{$datacenter}.api.mailchimp.com/3.0/lists/{$list_id}/members/{$subscriber_hash}/tags";
    
    // Format tags for Mailchimp API
    $formatted_tags = array();
    foreach ($tags as $tag) {
        $formatted_tags[] = array(
            'name' => $tag,
            'status' => 'active'
        );
    }
    
    $data = array(
        'tags' => $formatted_tags
    );
    
    $args = array(
        'method' => 'POST',
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode('user:' . $api_key),
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode($data),
        'timeout' => 30
    );
    
    $response = wp_remote_request($url, $args);
    
    if (is_wp_error($response)) {
        return array(
            'success' => false,
            'error' => 'HTTP request failed: ' . $response->get_error_message()
        );
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    
    if ($response_code === 204) {
        return array('success' => true);
    } else {
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);
        
        return array(
            'success' => false,
            'error' => isset($data['detail']) ? $data['detail'] : 'Unknown Mailchimp error',
            'response_code' => $response_code
        );
    }
}

/**
 * Get Mailchimp list ID
 * 
 * @return string|false List ID or false if not found
 */
function ctf_get_mailchimp_list_id() {
    // Try to get from Secret Manager first
    $list_id = ctf_get_secret('mailchimp-list-id');
    
    if ($list_id) {
        return $list_id;
    }
    
    // Fallback to environment variable
    $list_id = getenv('MAILCHIMP_LIST_ID');
    if ($list_id) {
        return $list_id;
    }
    
    // Fallback to WordPress option (for local development)
    return get_option('ctf_mailchimp_list_id', false);
}

/**
 * Handle donation subscription with auto-tagging
 * 
 * @param string $email Email address
 * @param string $first_name First name
 * @param string $last_name Last name
 * @param string $zip_code ZIP code
 * @param string $phone Phone number
 * @param int $amount Donation amount
 * @param string $frequency Donation frequency ('onetime' or 'monthly')
 * @param int $post_id Optional post ID for auto-tagging
 * @return array Result array with success/error information
 */
function ctf_subscribe_donation_to_mailchimp($email, $first_name, $last_name, $zip_code, $phone, $amount, $frequency = 'onetime', $post_id = null) {
    $tags = array();
    
    // Add donation-specific tags
    $tags[] = 'Donor';
    $tags[] = ucfirst($frequency) . ' Donor';
    
    // Add amount-based tags
    if ($amount >= 100) {
        $tags[] = 'Major Donor';
        $tags[] = 'Magazine Subscriber';
    }
    
    // Add auto-tags if enabled and post ID provided
    if ($post_id && function_exists('ctf_get_donation_auto_tag_safe')) {
        if (ctf_get_donation_auto_tag_safe($post_id)) {
            $post_title = get_the_title($post_id);
            if ($post_title) {
                $tags[] = sanitize_text_field($post_title);
            }
        }
    }
    
    // Subscribe to Mailchimp with tags
    return ctf_subscribe_to_mailchimp($email, $first_name, $last_name, $zip_code, $phone, $tags);
}

/**
 * Handle petition subscription
 * 
 * @param string $email Email address
 * @param string $first_name First name
 * @param string $last_name Last name
 * @param string $zip_code ZIP code
 * @param string $phone Phone number
 * @param string $petition_id Petition identifier
 * @return array Result array with success/error information
 */
function ctf_subscribe_petition_to_mailchimp($email, $first_name, $last_name, $zip_code, $phone, $petition_id = '') {
    $tags = array('Petition Signer');
    
    // Add petition-specific tag if ID provided
    if (!empty($petition_id)) {
        $tags[] = 'Petition: ' . sanitize_text_field($petition_id);
    }
    
    return ctf_subscribe_to_mailchimp($email, $first_name, $last_name, $zip_code, $phone, $tags);
}

// ========================================
// MAILCHIMP CONFIGURATION FUNCTIONS
// ========================================

/**
 * Test Mailchimp connection
 * 
 * @return array Result with connection status
 */
function ctf_test_mailchimp_connection() {
    $api_key = ctf_get_mailchimp_api_key();
    
    if (!$api_key) {
        return array(
            'success' => false,
            'error' => 'Mailchimp API key not found'
        );
    }
    
    // Extract datacenter from API key
    $datacenter = explode('-', $api_key)[1] ?? '';
    if (!$datacenter) {
        return array(
            'success' => false,
            'error' => 'Invalid Mailchimp API key format'
        );
    }
    
    $url = "https://{$datacenter}.api.mailchimp.com/3.0/ping";
    
    $args = array(
        'method' => 'GET',
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode('user:' . $api_key)
        ),
        'timeout' => 15
    );
    
    $response = wp_remote_request($url, $args);
    
    if (is_wp_error($response)) {
        return array(
            'success' => false,
            'error' => 'HTTP request failed: ' . $response->get_error_message()
        );
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    $data = json_decode($response_body, true);
    
    if ($response_code === 200 && isset($data['health_status'])) {
        return array(
            'success' => true,
            'data' => $data
        );
    } else {
        return array(
            'success' => false,
            'error' => 'Mailchimp API connection failed',
            'response_code' => $response_code,
            'response_data' => $data
        );
    }
}

/**
 * Get Mailchimp lists
 * 
 * @return array Result with list data
 */
function ctf_get_mailchimp_lists() {
    $api_key = ctf_get_mailchimp_api_key();
    
    if (!$api_key) {
        return array(
            'success' => false,
            'error' => 'Mailchimp API key not found'
        );
    }
    
    // Extract datacenter from API key
    $datacenter = explode('-', $api_key)[1] ?? '';
    if (!$datacenter) {
        return array(
            'success' => false,
            'error' => 'Invalid Mailchimp API key format'
        );
    }
    
    $url = "https://{$datacenter}.api.mailchimp.com/3.0/lists";
    
    $args = array(
        'method' => 'GET',
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode('user:' . $api_key)
        ),
        'timeout' => 15
    );
    
    $response = wp_remote_request($url, $args);
    
    if (is_wp_error($response)) {
        return array(
            'success' => false,
            'error' => 'HTTP request failed: ' . $response->get_error_message()
        );
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    $data = json_decode($response_body, true);
    
    if ($response_code === 200 && isset($data['lists'])) {
        return array(
            'success' => true,
            'lists' => $data['lists']
        );
    } else {
        return array(
            'success' => false,
            'error' => 'Failed to retrieve Mailchimp lists',
            'response_code' => $response_code,
            'response_data' => $data
        );
    }
}

// ========================================
// ADMIN INTEGRATION
// ========================================

// Add admin page for Mailchimp settings
add_action('admin_menu', 'ctf_add_mailchimp_admin_page');

/**
 * Add Mailchimp settings page to admin menu
 */
function ctf_add_mailchimp_admin_page() {
    add_options_page(
        'Mailchimp Settings',
        'Mailchimp',
        'manage_options',
        'ctf-mailchimp-settings',
        'ctf_mailchimp_settings_page'
    );
}

/**
 * Mailchimp settings page content
 */
function ctf_mailchimp_settings_page() {
    if (isset($_POST['test_connection'])) {
        $test_result = ctf_test_mailchimp_connection();
    }
    
    if (isset($_POST['get_lists'])) {
        $lists_result = ctf_get_mailchimp_lists();
    }
    
    ?>
    <div class="wrap">
        <h1>Mailchimp Settings</h1>
        
        <div class="notice notice-info">
            <p><strong>Note:</strong> This plugin uses Google Secret Manager for secure API key storage in production. Local development can use environment variables or WordPress options.</p>
        </div>
        
        <h2>Connection Test</h2>
        <form method="post">
            <p>
                <button type="submit" name="test_connection" class="button button-secondary">Test Mailchimp Connection</button>
            </p>
        </form>
        
        <?php if (isset($test_result)): ?>
        <div class="notice <?php echo $test_result['success'] ? 'notice-success' : 'notice-error'; ?>">
            <p>
                <strong>Connection Test Result:</strong>
                <?php if ($test_result['success']): ?>
                    ✅ Connected successfully!
                    <?php if (isset($test_result['data']['health_status'])): ?>
                        (Health Status: <?php echo esc_html($test_result['data']['health_status']); ?>)
                    <?php endif; ?>
                <?php else: ?>
                    ❌ Connection failed: <?php echo esc_html($test_result['error']); ?>
                <?php endif; ?>
            </p>
        </div>
        <?php endif; ?>
        
        <h2>Available Lists</h2>
        <form method="post">
            <p>
                <button type="submit" name="get_lists" class="button button-secondary">Get Mailchimp Lists</button>
            </p>
        </form>
        
        <?php if (isset($lists_result)): ?>
        <div class="notice <?php echo $lists_result['success'] ? 'notice-success' : 'notice-error'; ?>">
            <?php if ($lists_result['success']): ?>
                <p><strong>Available Lists:</strong></p>
                <ul>
                <?php foreach ($lists_result['lists'] as $list): ?>
                    <li>
                        <strong><?php echo esc_html($list['name']); ?></strong> 
                        (ID: <code><?php echo esc_html($list['id']); ?></code>)
                        - <?php echo esc_html($list['stats']['member_count']); ?> members
                    </li>
                <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p><strong>Error:</strong> <?php echo esc_html($lists_result['error']); ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <h2>Current Configuration</h2>
        <table class="form-table">
            <tr>
                <th scope="row">API Key Status</th>
                <td>
                    <?php 
                    $api_key = ctf_get_mailchimp_api_key();
                    if ($api_key) {
                        echo '✅ Found (ends with: ' . esc_html(substr($api_key, -10)) . ')';
                    } else {
                        echo '❌ Not found';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <th scope="row">List ID Status</th>
                <td>
                    <?php 
                    $list_id = ctf_get_mailchimp_list_id();
                    if ($list_id) {
                        echo '✅ Found: <code>' . esc_html($list_id) . '</code>';
                    } else {
                        echo '❌ Not found';
                    }
                    ?>
                </td>
            </tr>
        </table>
    </div>
    <?php
}