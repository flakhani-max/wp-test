<?php
/**
 * Plugin Name: Fix Cloud Run Loopback Requests with IAP
 * Description: Addresses loopback request issues on Google Cloud Run behind IAP by adding proper authentication.
 * Version: 2.0
 * Author: CTF Digital Team
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Get IAP authentication token for the current service account.
 *
 * @return string|false The IAP token or false on failure.
 */
function ctf_get_iap_token() {
	static $token_cache = null;
	static $token_expiry = 0;

	// Return cached token if still valid (cache for 45 minutes)
	if ( $token_cache && time() < $token_expiry ) {
		return $token_cache;
	}

	// IAP Client ID for the backend service
	// Get this from: gcloud compute backend-services describe ctf-wordpress-backend --global --format="get(iap.oauth2ClientId)"
	$iap_client_id = '369001918367-t5qrahnqdaasaifvk6akpqkpjk9vli58.apps.googleusercontent.com';

	// Get service account token from metadata server
	$metadata_url = 'http://metadata.google.internal/computeMetadata/v1/instance/service-accounts/default/identity?audience=' . urlencode( $iap_client_id );
	
	// Use curl instead of wp_remote_get to avoid recursive filter calls
	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_URL, $metadata_url );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Metadata-Flavor: Google' ) );
	curl_setopt( $ch, CURLOPT_TIMEOUT, 10 );
	$token = curl_exec( $ch );
	$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
	curl_close( $ch );

	if ( $http_code !== 200 || empty( $token ) ) {
		error_log( 'IAP Token Error: Failed to get token from metadata server (HTTP ' . $http_code . ')' );
		return false;
	}

	// Cache token for 45 minutes
	$token_cache = $token;
	$token_expiry = time() + ( 45 * 60 );

	return $token;
}

/**
 * Filter HTTP API arguments to fix loopback requests on Cloud Run behind IAP.
 *
 * @param array  $args Array of HTTP API arguments.
 * @param string $url  The request URL.
 * @return array Modified HTTP API arguments.
 */
function ctf_fix_cloud_run_loopback_requests( $args, $url ) {
	// Parse the request URL and site URL
	$request_host = parse_url( $url, PHP_URL_HOST );
	$site_host = parse_url( site_url(), PHP_URL_HOST );
	
	// Check if this is a loopback request (WordPress calling itself)
	$is_loopback = ( 
		$request_host === $site_host || 
		$request_host === 'dev.taxpayer.com' ||
		$request_host === 'localhost' ||
		$request_host === '127.0.0.1'
	);
	
	if ( ! $is_loopback ) {
		return $args;
	}

	// Disable SSL verification for loopback requests
	$args['sslverify'] = false;

	// Increase timeout for background processes
	$args['timeout'] = 30;

	// Get IAP token and add to headers
	$iap_token = ctf_get_iap_token();
	if ( $iap_token ) {
		if ( ! isset( $args['headers'] ) ) {
			$args['headers'] = array();
		}
		// IAP expects the token in the 'Proxy-Authorization' header
		$args['headers']['Proxy-Authorization'] = 'Bearer ' . $iap_token;
		error_log( 'CTF IAP: Added IAP token to loopback request: ' . $url );
	} else {
		error_log( 'CTF IAP: Failed to get IAP token for loopback request: ' . $url );
	}

	// If the request is for WP-Cron, ensure it's non-blocking
	if ( strpos( $url, 'wp-cron.php' ) !== false ) {
		$args['blocking'] = false;
		$args['timeout']  = 0.01; // Very short timeout for non-blocking cron
	}

	return $args;
}
add_filter( 'http_request_args', 'ctf_fix_cloud_run_loopback_requests', 10, 2 );

/**
 * Alternative cron method that uses IAP authentication
 */
function ctf_fix_cron_request( $cron_request ) {
	$cron_request['args']['blocking'] = false;
	$cron_request['args']['sslverify'] = false;
	$cron_request['args']['timeout'] = 0.01; // Fire and forget
	
	// Add IAP token to cron requests
	$iap_token = ctf_get_iap_token();
	if ( $iap_token ) {
		if ( ! isset( $cron_request['args']['headers'] ) ) {
			$cron_request['args']['headers'] = array();
		}
		$cron_request['args']['headers']['Proxy-Authorization'] = 'Bearer ' . $iap_token;
	}
	
	return $cron_request;
}
add_filter( 'cron_request', 'ctf_fix_cron_request' );

// For WP Offload Media: Force enable background processes even if loopback fails
add_filter( 'as3cf_use_background_processes', '__return_true' );
add_filter( 'as3cf_background_process_force_http_loopback', '__return_false' );
