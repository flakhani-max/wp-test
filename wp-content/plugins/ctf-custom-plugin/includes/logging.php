<?php
/**
 * CTF Custom Logging System
 * 
 * Provides structured logging using WordPress database
 * with easy querying and filtering capabilities.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize logging table on plugin activation
 */
function create_logging_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'ctf_logs';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        level varchar(20) DEFAULT 'info' NOT NULL,
        component varchar(50) NOT NULL,
        message text NOT NULL,
        context text,
        user_id bigint(20),
        ip_address varchar(45),
        user_agent text,
        PRIMARY KEY (id),
        KEY level (level),
        KEY component (component),
        KEY timestamp (timestamp),
        KEY user_id (user_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Set version for future upgrades
    add_option('ctf_logging_db_version', '1.0');
}

/**
 * Log a message with context
 * 
 * @param string $message The log message
 * @param string $level Log level: 'debug', 'info', 'warning', 'error', 'critical'
 * @param string $component Component name: 'petition', 'donation', 'mailchimp', 'general'
 * @param array $context Additional context data
 */
function log_message($message, $level = 'info', $component = 'general', $context = null) {
    global $wpdb;
    
    // Also log to WordPress debug log for immediate visibility
    error_log("CTF Plugin [{$level}] [{$component}]: {$message}, Context: " . ($context ? print_r($context, true) : 'None'));
    
    $table_name = $wpdb->prefix . 'ctf_logs';
    
    $user_id = get_current_user_id();
    $ip_address = get_client_ip();
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $context_json = null;
    if ($context && is_array($context)) {
        $context_json = json_encode($context, JSON_UNESCAPED_UNICODE);
    }
    
    $wpdb->insert(
        $table_name,
        array(
            'level' => $level,
            'component' => $component,
            'message' => $message,
            'context' => $context_json,
            'user_id' => $user_id ?: null,
            'ip_address' => $ip_address,
            'user_agent' => $user_agent
        ),
        array(
            '%s', // level
            '%s', // component
            '%s', // message
            '%s', // context
            '%d', // user_id
            '%s', // ip_address
            '%s'  // user_agent
        )
    );
}

/**
 * Get logs with filtering options
 * 
 * @param array $args Query arguments
 * @return array Log entries
 */
function get_logs($args = array()) {
    global $wpdb;
    
    $defaults = array(
        'level' => null,
        'component' => null,
        'limit' => 100,
        'offset' => 0,
        'start_date' => null,
        'end_date' => null,
        'user_id' => null
    );
    
    $args = wp_parse_args($args, $defaults);
    
    $table_name = $wpdb->prefix . 'ctf_logs';
    
    $where_clauses = array('1=1');
    $where_values = array();
    
    if ($args['level']) {
        $where_clauses[] = 'level = %s';
        $where_values[] = $args['level'];
    }
    
    if ($args['component']) {
        $where_clauses[] = 'component = %s';
        $where_values[] = $args['component'];
    }
    
    if ($args['start_date']) {
        $where_clauses[] = 'timestamp >= %s';
        $where_values[] = $args['start_date'];
    }
    
    if ($args['end_date']) {
        $where_clauses[] = 'timestamp <= %s';
        $where_values[] = $args['end_date'];
    }
    
    if ($args['user_id']) {
        $where_clauses[] = 'user_id = %d';
        $where_values[] = $args['user_id'];
    }
    
    $where_sql = implode(' AND ', $where_clauses);
    
    $sql = "SELECT * FROM $table_name WHERE $where_sql ORDER BY timestamp DESC LIMIT %d OFFSET %d";
    $where_values[] = $args['limit'];
    $where_values[] = $args['offset'];
    
    if (!empty($where_values)) {
        $sql = $wpdb->prepare($sql, $where_values);
    }
    
    return $wpdb->get_results($sql, 'ARRAY_A');
}

/**
 * Clean up old logs
 * 
 * @param int $days_to_keep Number of days to keep logs (default: 30)
 */
function cleanup_old_logs($days_to_keep = 30) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'ctf_logs';
    $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days_to_keep} days"));
    
    $deleted = $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM $table_name WHERE timestamp < %s",
            $cutoff_date
        )
    );
    
    log_message("Cleaned up {$deleted} old log entries", 'info', 'logging');
    
    return $deleted;
}

/**
 * Get client IP address
 * 
 * @return string IP address
 */
function get_client_ip() {
    $ip_keys = array(
        'HTTP_CF_CONNECTING_IP',     // Cloudflare
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    );
    
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            $ip = $_SERVER[$key];
            if (strpos($ip, ',') !== false) {
                $ip = explode(',', $ip)[0];
            }
            $ip = trim($ip);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Shorthand logging functions
 */
function log_debug($message, $component = 'general', $context = null) {
    log_message($message, 'debug', $component, $context);
}

function log_info($message, $component = 'general', $context = null) {
    log_message($message, 'info', $component, $context);
}

function log_warning($message, $component = 'general', $context = null) {
    log_message($message, 'warning', $component, $context);
}

function log_error($message, $component = 'general', $context = null) {
    log_message($message, 'error', $component, $context);
}

function log_critical($message, $component = 'general', $context = null) {
    log_message($message, 'critical', $component, $context);
}

// Schedule log cleanup (daily)
if (!wp_next_scheduled('ctf_cleanup_logs_hook')) {
    wp_schedule_event(time(), 'daily', 'ctf_cleanup_logs_hook');
}

add_action('ctf_cleanup_logs_hook', function() {
    cleanup_old_logs(30); // Keep 30 days
});

?>
