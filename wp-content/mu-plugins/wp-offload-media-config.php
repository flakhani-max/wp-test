<?php
/**
 * WP Offload Media Lite Auto-Configuration
 * This must-use plugin automatically configures WP Offload Media on container startup
 */

// Only define if not already defined (allows wp-config.php to override)
if (!defined('AS3CF_SETTINGS')) {
    $gcs_bucket = getenv('GCS_BUCKET') ?: 'taxpayer-media-bucket';
    $gcs_key_path = '/var/www/html/wp-content/uploads/gcs-key.json';
    
    define('AS3CF_SETTINGS', serialize(array(
        'provider' => 'gcp',
        'key-file-path' => $gcs_key_path,
        'bucket' => $gcs_bucket,
    )));
}

