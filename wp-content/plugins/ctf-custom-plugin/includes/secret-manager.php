<?php
/**
 * Google Secret Manager Integration (Simplified)
 * 
 * This module provides secure secret management using Google Secret Manager
 * with fallbacks for local development. No separate Docker image needed!
 */

if (!defined('ABSPATH')) exit;

// Environment configuration - these can be set in Cloud Run environment variables
if (!defined('CTF_USE_SECRET_MANAGER')) {
    define('CTF_USE_SECRET_MANAGER', getenv('CTF_USE_SECRET_MANAGER') === 'true');
}
if (!defined('CTF_GCP_PROJECT_ID')) {
    define('CTF_GCP_PROJECT_ID', getenv('CTF_GCP_PROJECT_ID') ?: '');
}

/**
 * Get Mailchimp API key from Secret Manager or fallback
 * 
 * @return string API key
 */
function ctf_get_mailchimp_api_key() {
    $secret = ctf_get_secret('mailchimp-api-key');
    if ($secret) {
        return $secret;
    }
    
    // Fallback to environment variable or constant
    return getenv('CTF_MAILCHIMP_API_KEY') ?: (defined('CTF_MAILCHIMP_API_KEY') ? CTF_MAILCHIMP_API_KEY : 'YOUR_API_KEY');
}

/**
 * Get Mailchimp audience ID from Secret Manager or fallback
 * 
 * @return string Audience ID
 */
function ctf_get_mailchimp_audience_id() {
    $secret = ctf_get_secret('mailchimp-audience-id');
    if ($secret) {
        return $secret;
    }
    
    // Fallback to environment variable or constant
    return getenv('CTF_MAILCHIMP_AUDIENCE_ID') ?: (defined('CTF_MAILCHIMP_AUDIENCE_ID') ? CTF_MAILCHIMP_AUDIENCE_ID : 'YOUR_AUDIENCE_ID');
}

/**
 * Get secret from Google Secret Manager with simplified approach
 * Works on Cloud Run without requiring Google Cloud SDK!
 * 
 * @param string $secret_name Name of the secret in Secret Manager
 * @return string|null Secret value or null if not found
 */
function ctf_get_secret($secret_name) {
    static $cache = [];
    
    // Return cached value if available
    if (isset($cache[$secret_name])) {
        return $cache[$secret_name];
    }
    
    // Check if we should use Secret Manager
    if (!CTF_USE_SECRET_MANAGER || empty(CTF_GCP_PROJECT_ID)) {
        // Fallback to environment variables
        $env_var = strtoupper(str_replace('-', '_', $secret_name));
        return getenv("CTF_{$env_var}");
    }
    
    try {
        // Method 1: Try Cloud Run metadata server (preferred - no SDK needed!)
        $secret_value = ctf_get_secret_via_metadata_server($secret_name, CTF_GCP_PROJECT_ID);
        if ($secret_value !== null) {
            $cache[$secret_name] = $secret_value;
            return $secret_value;
        }
        
        // Method 2: Try gcloud CLI (for local development)
        $secret_value = ctf_get_secret_via_gcloud($secret_name, CTF_GCP_PROJECT_ID);
        if ($secret_value !== null) {
            $cache[$secret_name] = $secret_value;
            return $secret_value;
        }
        
        error_log("CTF Plugin: Could not retrieve secret {$secret_name} from any source");
        
    } catch (Exception $e) {
        error_log("CTF Plugin: Error retrieving secret {$secret_name}: " . $e->getMessage());
    }
    
    // Final fallback to environment variables
    $env_var = strtoupper(str_replace('-', '_', $secret_name));
    return getenv("CTF_{$env_var}");
}

/**
 * Get secret using Cloud Run metadata server (recommended for Cloud Run)
 * This doesn't require the Google Cloud SDK to be installed!
 * 
 * @param string $secret_name Name of the secret
 * @param string $project_id GCP Project ID
 * @return string|null Secret value or null if not found
 */
function ctf_get_secret_via_metadata_server($secret_name, $project_id) {
    // Check if we're running on Google Cloud (metadata server available)
    $metadata_url = 'http://metadata.google.internal/computeMetadata/v1/instance/service-accounts/default/token';
    
    $token_response = wp_remote_get($metadata_url, [
        'headers' => ['Metadata-Flavor' => 'Google'],
        'timeout' => 5
    ]);
    
    if (is_wp_error($token_response) || wp_remote_retrieve_response_code($token_response) !== 200) {
        return null; // Not on Google Cloud or metadata server not available
    }
    
    $token_data = json_decode(wp_remote_retrieve_body($token_response), true);
    if (!isset($token_data['access_token'])) {
        return null;
    }
    
    $access_token = $token_data['access_token'];
    
    // Use the token to access Secret Manager API directly
    $secret_url = "https://secretmanager.googleapis.com/v1/projects/{$project_id}/secrets/{$secret_name}/versions/latest:access";
    
    $secret_response = wp_remote_get($secret_url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type' => 'application/json'
        ],
        'timeout' => 10
    ]);
    
    if (is_wp_error($secret_response) || wp_remote_retrieve_response_code($secret_response) !== 200) {
        return null;
    }
    
    $secret_data = json_decode(wp_remote_retrieve_body($secret_response), true);
    if (isset($secret_data['payload']['data'])) {
        return base64_decode($secret_data['payload']['data']);
    }
    
    return null;
}

/**
 * Get secret using gcloud CLI (for local development)
 * 
 * @param string $secret_name Name of the secret
 * @param string $project_id GCP Project ID
 * @return string|null Secret value or null if not found
 */
function ctf_get_secret_via_gcloud($secret_name, $project_id) {
    if (!function_exists('shell_exec')) {
        return null;
    }
    
    $command = sprintf(
        'gcloud secrets versions access latest --secret="%s" --project="%s" 2>/dev/null',
        escapeshellarg($secret_name),
        escapeshellarg($project_id)
    );
    
    $result = shell_exec($command);
    if ($result !== null && trim($result) !== '') {
        return trim($result);
    }
    
    return null;
}

/**
 * Test Secret Manager connectivity
 * 
 * @return array Result with connection status and details
 */
function ctf_test_secret_manager_connection() {
    if (!CTF_USE_SECRET_MANAGER || empty(CTF_GCP_PROJECT_ID)) {
        return [
            'success' => false,
            'message' => 'Secret Manager not configured (CTF_USE_SECRET_MANAGER=false or no project ID)',
            'method' => 'disabled'
        ];
    }
    
    // Test metadata server method first
    $metadata_url = 'http://metadata.google.internal/computeMetadata/v1/instance/service-accounts/default/token';
    $token_response = wp_remote_get($metadata_url, [
        'headers' => ['Metadata-Flavor' => 'Google'],
        'timeout' => 5
    ]);
    
    if (!is_wp_error($token_response) && wp_remote_retrieve_response_code($token_response) === 200) {
        return [
            'success' => true,
            'message' => 'Connected via Cloud Run metadata server (recommended)',
            'method' => 'metadata_server'
        ];
    }
    
    // Test gcloud CLI method
    if (function_exists('shell_exec')) {
        $test_command = 'gcloud auth list --filter=status:ACTIVE --format="value(account)" 2>/dev/null';
        $result = shell_exec($test_command);
        if ($result !== null && trim($result) !== '') {
            return [
                'success' => true,
                'message' => 'Connected via gcloud CLI (local development)',
                'method' => 'gcloud_cli',
                'authenticated_as' => trim($result)
            ];
        }
    }
    
    return [
        'success' => false,
        'message' => 'No authentication method available',
        'method' => 'none'
    ];
}

/**
 * Admin notice for Secret Manager status
 */
add_action('admin_notices', 'ctf_secret_manager_admin_notice');

function ctf_secret_manager_admin_notice() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $status = ctf_test_secret_manager_connection();
    
    if (CTF_USE_SECRET_MANAGER && !$status['success']) {
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>CTF Custom Plugin:</strong> Secret Manager is enabled but not working: ' . esc_html($status['message']) . '</p>';
        echo '<p>Falling back to environment variables. Check your Google Cloud configuration.</p>';
        echo '</div>';
    } elseif ($status['success']) {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>CTF Custom Plugin:</strong> Secret Manager connected - ' . esc_html($status['message']) . '</p>';
        echo '</div>';
    }
}