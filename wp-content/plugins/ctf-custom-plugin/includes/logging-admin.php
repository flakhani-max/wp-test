<?php
/**
 * CTF Logging Admin Interface
 * 
 * Provides WordPress admin interface for viewing and managing logs.
 */

if (!defined('ABSPATH')) exit;

// Add admin menu
add_action('admin_menu', 'add_logging_admin_page');

/**
 * Add logging admin page
 */
function add_logging_admin_page() {
    add_management_page(
        'CTF Logs',
        'CTF Logs', 
        'manage_options',
        'ctf-logs',
        'logging_admin_page'
    );
}

/**
 * Logging admin page content
 */
function logging_admin_page() {
    // Handle log cleanup
    if (isset($_POST['cleanup_logs']) && wp_verify_nonce($_POST['_wpnonce'], 'ctf_cleanup_logs')) {
        $days = intval($_POST['days_to_keep'] ?? 30);
        $deleted = cleanup_old_logs($days);
        echo '<div class="notice notice-success"><p>Cleaned up ' . $deleted . ' old log entries.</p></div>';
    }
    
    // Get filter parameters
    $level = $_GET['level'] ?? '';
    $component = $_GET['component'] ?? '';
    $page_num = max(1, intval($_GET['paged'] ?? 1));
    $per_page = 50;
    $offset = ($page_num - 1) * $per_page;
    
    // Build filter args
    $filter_args = array(
        'limit' => $per_page,
        'offset' => $offset
    );
    
    if ($level) {
        $filter_args['level'] = $level;
    }
    
    if ($component) {
        $filter_args['component'] = $component;
    }
    
    // Get logs
    $logs = get_logs($filter_args);
    
    // Get available components and levels for filters
    global $wpdb;
    $table_name = $wpdb->prefix . 'ctf_logs';
    $components = $wpdb->get_col("SELECT DISTINCT component FROM $table_name ORDER BY component");
    $levels = $wpdb->get_col("SELECT DISTINCT level FROM $table_name ORDER BY level");
    
    ?>
    <div class="wrap">
        <h1>CTF Plugin Logs</h1>
        
        <div class="tablenav top">
            <div class="alignleft actions">
                <form method="get" style="display: inline-block;">
                    <input type="hidden" name="page" value="ctf-logs">
                    
                    <select name="level">
                        <option value="">All Levels</option>
                        <?php foreach ($levels as $log_level): ?>
                            <option value="<?php echo esc_attr($log_level); ?>" <?php selected($level, $log_level); ?>>
                                <?php echo esc_html(ucfirst($log_level)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="component">
                        <option value="">All Components</option>
                        <?php foreach ($components as $comp): ?>
                            <option value="<?php echo esc_attr($comp); ?>" <?php selected($component, $comp); ?>>
                                <?php echo esc_html(ucfirst($comp)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <input type="submit" class="button" value="Filter">
                    
                    <?php if ($level || $component): ?>
                        <a href="<?php echo admin_url('tools.php?page=ctf-logs'); ?>" class="button">Clear Filters</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="alignright actions">
                <form method="post" style="display: inline-block;">
                    <?php wp_nonce_field('ctf_cleanup_logs'); ?>
                    <input type="number" name="days_to_keep" value="30" min="1" max="365" style="width: 60px;">
                    <label>days to keep</label>
                    <input type="submit" name="cleanup_logs" class="button" value="Cleanup Old Logs" 
                           onclick="return confirm('Are you sure you want to delete old log entries?');">
                </form>
            </div>
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 130px;">Timestamp</th>
                    <th style="width: 80px;">Level</th>
                    <th style="width: 100px;">Component</th>
                    <th>Message</th>
                    <th style="width: 80px;">User</th>
                    <th style="width: 100px;">IP</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="6">No logs found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo esc_html(date('M j, Y g:i:s A', strtotime($log['timestamp']))); ?></td>
                            <td>
                                <span class="ctf-log-level ctf-log-level-<?php echo esc_attr($log['level']); ?>">
                                    <?php echo esc_html(ucfirst($log['level'])); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($log['component']); ?></td>
                            <td>
                                <div class="ctf-log-message"><?php echo esc_html($log['message']); ?></div>
                                <?php if ($log['context']): ?>
                                    <details class="ctf-log-context">
                                        <summary>Context</summary>
                                        <pre><?php echo esc_html($log['context']); ?></pre>
                                    </details>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($log['user_id']): ?>
                                    <?php $user = get_user_by('id', $log['user_id']); ?>
                                    <?php echo $user ? esc_html($user->user_login) : 'Unknown'; ?>
                                <?php else: ?>
                                    Guest
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($log['ip_address']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php
        // Simple pagination
        $total_logs = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $total_pages = ceil($total_logs / $per_page);
        
        if ($total_pages > 1):
        ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num"><?php echo $total_logs; ?> items</span>
                <span class="pagination-links">
                    <?php if ($page_num > 1): ?>
                        <a class="prev-page button" href="<?php echo add_query_arg('paged', $page_num - 1); ?>">‹</a>
                    <?php endif; ?>
                    
                    <span class="paging-input">
                        <span class="tablenav-paging-text">
                            <?php echo $page_num; ?> of <?php echo $total_pages; ?>
                        </span>
                    </span>
                    
                    <?php if ($page_num < $total_pages): ?>
                        <a class="next-page button" href="<?php echo add_query_arg('paged', $page_num + 1); ?>">›</a>
                    <?php endif; ?>
                </span>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <style>
    .ctf-log-level {
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 11px;
        font-weight: bold;
        text-transform: uppercase;
    }
    .ctf-log-level-debug { background: #f0f0f0; color: #666; }
    .ctf-log-level-info { background: #e7f3ff; color: #0073aa; }
    .ctf-log-level-warning { background: #fff3cd; color: #856404; }
    .ctf-log-level-error { background: #f8d7da; color: #721c24; }
    .ctf-log-level-critical { background: #721c24; color: white; }
    
    .ctf-log-message {
        font-family: monospace;
        font-size: 13px;
    }
    
    .ctf-log-context {
        margin-top: 5px;
    }
    
    .ctf-log-context summary {
        cursor: pointer;
        font-size: 11px;
        color: #666;
    }
    
    .ctf-log-context pre {
        background: #f5f5f5;
        padding: 8px;
        border-radius: 3px;
        font-size: 11px;
        margin: 5px 0 0 0;
        white-space: pre-wrap;
        word-wrap: break-word;
        max-height: 200px;
        overflow-y: auto;
    }
    </style>
    <?php
}

?>
