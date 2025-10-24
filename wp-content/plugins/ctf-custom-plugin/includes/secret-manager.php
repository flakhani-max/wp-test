<?php
/**
 * Google Secret Manager Integration
 * 
 * Handles secure retrieval of API keys and sensitive configuration
 * from Google Cloud Secret Manager with fallbacks for local development.
 */

if (!defined('ABSPATH')) exit;

// Secret Manager Configuration
define('CTF_USE_SECRET_MANAGER', getenv('CTF_USE_SECRET_MANAGER') === 'true');
define('CTF_GCP_PROJECT_ID', getenv('CTF_GCP_PROJECT_ID') ?: '');

// Settings: Set up API keys using the new system
if (!defined('CTF_MAILCHIMP_API_KEY')) {
    define('CTF_MAILCHIMP_API_KEY', 'YOUR_API_KEY'); // Fallback only
}
if (!defined('CTF_MAILCHIMP_AUDIENCE_ID')) {
    define('CTF_MAILCHIMP_AUDIENCE_ID', 'YOUR_AUDIENCE_ID'); // Fallback only
}

// Legacy constants for backward compatibility
if (!defined('WP_PETITION_MAILCHIMP_API_KEY')) {
    define('WP_PETITION_MAILCHIMP_API_KEY', CTF_MAILCHIMP_API_KEY);
}
if (!defined('WP_PETITION_MAILCHIMP_AUDIENCE_ID')) {
    define('WP_PETITION_MAILCHIMP_AUDIENCE_ID', CTF_MAILCHIMP_AUDIENCE_ID);
}

/**
 * Get Mailchimp API key from Secret Manager or fallback
 * 
 * @return string API key
 */
function ctf_get_mailchimp_api_key() {
    if (CTF_USE_SECRET_MANAGER && CTF_GCP_PROJECT_ID) {
        return ctf_get_secret('mailchimp-api-key');
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
    if (CTF_USE_SECRET_MANAGER && CTF_GCP_PROJECT_ID) {
        return ctf_get_secret('mailchimp-audience-id');
    }
    
    // Fallback to environment variable or constant
    return getenv('CTF_MAILCHIMP_AUDIENCE_ID') ?: (defined('CTF_MAILCHIMP_AUDIENCE_ID') ? CTF_MAILCHIMP_AUDIENCE_ID : 'YOUR_AUDIENCE_ID');
}

/**
 * Generic secret retrieval from Google Secret Manager
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
    
    try {
        // Use Google Cloud SDK if available
        if (function_exists('shell_exec') && !empty(CTF_GCP_PROJECT_ID)) {
            $command = sprintf(
                'gcloud secrets versions access latest --secret="%s" --project="%s" 2>/dev/null',
                escapeshellarg($secret_name),
                escapeshellarg(CTF_GCP_PROJECT_ID)
            );
            
            $result = shell_exec($command);
            if ($result !== null) {
                $secret_value = trim($result);
                $cache[$secret_name] = $secret_value;
                return $secret_value;
            }
        }
        
        // Alternative: Use Google Cloud Client Library if available
        if (class_exists('Google\Cloud\SecretManager\V1\SecretManagerServiceClient')) {
            return ctf_get_secret_via_client_library($secret_name);
        }
        
        // Log error if Secret Manager is expected but not available
        error_log("CTF Plugin: Secret Manager requested but not available for secret: {$secret_name}");
        
    } catch (Exception $e) {
        error_log("CTF Plugin: Error retrieving secret {$secret_name}: " . $e->getMessage());
    }
    
    return null;
}

/**
 * Google Cloud Client Library method (if available)
 * 
 * @param string $secret_name Name of the secret
 * @return string|null Secret value or null if not found
 */
function ctf_get_secret_via_client_library($secret_name) {
    static $cache = [];
    
    if (isset($cache[$secret_name])) {
        return $cache[$secret_name];
    }
    
    try {
        $client = new Google\Cloud\SecretManager\V1\SecretManagerServiceClient();
        $secretName = $client->secretVersionName(CTF_GCP_PROJECT_ID, $secret_name, 'latest');
        
        $response = $client->accessSecretVersion($secretName);
        $secret_value = $response->getPayload()->getData();
        
        $cache[$secret_name] = $secret_value;
        $client->close();
        
        return $secret_value;
        
    } catch (Exception $e) {
        error_log("CTF Plugin: Client library error for secret {$secret_name}: " . $e->getMessage());
        return null;
    }
}