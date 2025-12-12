<?php
/**
 * WP Offload Media Lite Auto-Configuration
 * This must-use plugin automatically configures WP Offload Media on container startup
 */

// Ensure GCS key file exists
$uploads_path = '/var/www/html/wp-content/uploads';
$gcs_key_path = $uploads_path . '/gcs-key.json';

if (!file_exists($gcs_key_path)) {
    // Try to create from environment variable
    $wp_media_sa_key = getenv('WP_MEDIA_SA_KEY');
    
    if (!empty($wp_media_sa_key)) {
        // Ensure uploads directory exists
        if (!is_dir($uploads_path)) {
            mkdir($uploads_path, 0755, true);
        }
        
        // Decode base64 if needed, otherwise write directly
        $decoded = base64_decode($wp_media_sa_key, true);
        if ($decoded !== false && json_decode($decoded) !== null) {
            // Successfully decoded base64 JSON
            file_put_contents($gcs_key_path, $decoded);
        } else {
            // Direct JSON or plain text
            file_put_contents($gcs_key_path, $wp_media_sa_key);
        }
        
        // Set proper permissions
        @chmod($gcs_key_path, 0600);
    }
}

// Only define if not already defined (allows wp-config.php to override)
if (!defined('AS3CF_SETTINGS')) {
    $gcs_bucket = getenv('GCS_BUCKET') ?: 'taxpayer-media-bucket';
    
    define('AS3CF_SETTINGS', serialize(array(
        'provider' => 'gcp',
        'key-file-path' => $gcs_key_path,
        'bucket' => $gcs_bucket,
        'object-prefix' => 'dev/',
    )));
}

