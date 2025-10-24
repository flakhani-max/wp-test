<?php
/**
 * Petition Form Handling
 * 
 * Handles petition form submission, validation, and processing
 * for the CTF Custom Plugin.
 */

if (!defined('ABSPATH')) exit;

// Handle petition form submissions
add_action('wp_ajax_ctf_submit_petition', 'ctf_handle_petition_submission');
add_action('wp_ajax_nopriv_ctf_submit_petition', 'ctf_handle_petition_submission');

/**
 * Handle petition form submission
 */
function ctf_handle_petition_submission() {
    // Verify nonce for security
    if (!isset($_POST['petition_nonce']) || !wp_verify_nonce($_POST['petition_nonce'], 'ctf_petition_form')) {
        wp_die('Security check failed');
    }

    // Sanitize input data
    $first_name = sanitize_text_field($_POST['first_name'] ?? '');
    $last_name = sanitize_text_field($_POST['last_name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $zip_code = sanitize_text_field($_POST['zip_code'] ?? '');
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    $petition_id = sanitize_text_field($_POST['petition_id'] ?? '');

    // Validate required fields
    $errors = array();
    
    if (empty($first_name)) {
        $errors[] = 'First name is required';
    }
    
    if (empty($last_name)) {
        $errors[] = 'Last name is required';
    }
    
    if (empty($email) || !is_email($email)) {
        $errors[] = 'Valid email is required';
    }
    
    if (empty($zip_code)) {
        $errors[] = 'ZIP code is required';
    }

    if (!empty($errors)) {
        wp_send_json_error(array(
            'message' => 'Please fix the following errors:',
            'errors' => $errors
        ));
    }

    // Attempt to subscribe to Mailchimp
    $mailchimp_result = ctf_subscribe_to_mailchimp($email, $first_name, $last_name, $zip_code, $phone);
    
    if ($mailchimp_result['success']) {
        // Store petition signature locally
        $signature_id = ctf_store_petition_signature($first_name, $last_name, $email, $zip_code, $phone, $petition_id);
        
        wp_send_json_success(array(
            'message' => 'Thank you for signing the petition! You have been added to our mailing list.',
            'signature_id' => $signature_id
        ));
    } else {
        wp_send_json_error(array(
            'message' => 'There was an error submitting your petition. Please try again.',
            'error' => $mailchimp_result['error']
        ));
    }

    wp_die();
}

/**
 * Store petition signature in database
 * 
 * @param string $first_name
 * @param string $last_name
 * @param string $email
 * @param string $zip_code
 * @param string $phone
 * @param string $petition_id
 * @return int|false Post ID on success, false on failure
 */
function ctf_store_petition_signature($first_name, $last_name, $email, $zip_code, $phone, $petition_id) {
    $signature_data = array(
        'post_title'   => sprintf('%s %s - %s', $first_name, $last_name, $email),
        'post_content' => sprintf(
            'Petition Signature Details:<br>
            Name: %s %s<br>
            Email: %s<br>
            ZIP Code: %s<br>
            Phone: %s<br>
            Petition ID: %s<br>
            Submitted: %s',
            $first_name,
            $last_name,
            $email,
            $zip_code,
            $phone,
            $petition_id,
            current_time('mysql')
        ),
        'post_status'  => 'private',
        'post_type'    => 'petition_signature',
        'post_author'  => 1,
        'meta_input'   => array(
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'zip_code' => $zip_code,
            'phone' => $phone,
            'petition_id' => $petition_id,
            'signature_date' => current_time('mysql'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        )
    );

    return wp_insert_post($signature_data);
}

// Register petition signature post type
add_action('init', 'ctf_register_petition_signature_cpt');

/**
 * Register petition signature custom post type
 */
function ctf_register_petition_signature_cpt() {
    $labels = array(
        'name'                  => _x('Petition Signatures', 'Post type general name', 'ctf-custom'),
        'singular_name'         => _x('Petition Signature', 'Post type singular name', 'ctf-custom'),
        'menu_name'             => _x('Signatures', 'Admin Menu text', 'ctf-custom'),
        'name_admin_bar'        => _x('Signature', 'Add New on Toolbar', 'ctf-custom'),
        'add_new'               => __('Add New', 'ctf-custom'),
        'add_new_item'          => __('Add New Signature', 'ctf-custom'),
        'new_item'              => __('New Signature', 'ctf-custom'),
        'edit_item'             => __('Edit Signature', 'ctf-custom'),
        'view_item'             => __('View Signature', 'ctf-custom'),
        'all_items'             => __('All Signatures', 'ctf-custom'),
        'search_items'          => __('Search Signatures', 'ctf-custom'),
        'not_found'             => __('No signatures found.', 'ctf-custom'),
        'not_found_in_trash'    => __('No signatures found in Trash.', 'ctf-custom'),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'publicly_queryable' => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => false,
        'rewrite'            => false,
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => null,
        'menu_icon'          => 'dashicons-edit-page',
        'supports'           => array('title', 'editor'),
        'show_in_rest'       => false,
    );

    register_post_type('petition_signature', $args);
}

// Add admin columns for petition signatures
add_filter('manage_petition_signature_posts_columns', 'ctf_petition_signature_columns');
add_action('manage_petition_signature_posts_custom_column', 'ctf_petition_signature_custom_column', 10, 2);

/**
 * Add custom columns to petition signature admin
 */
function ctf_petition_signature_columns($columns) {
    $new_columns = array();
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = 'Signature';
    $new_columns['email'] = 'Email';
    $new_columns['zip_code'] = 'ZIP Code';
    $new_columns['phone'] = 'Phone';
    $new_columns['petition_id'] = 'Petition ID';
    $new_columns['signature_date'] = 'Date Signed';
    
    return $new_columns;
}

/**
 * Display custom column content
 */
function ctf_petition_signature_custom_column($column, $post_id) {
    switch ($column) {
        case 'email':
            echo esc_html(get_post_meta($post_id, 'email', true));
            break;
        case 'zip_code':
            echo esc_html(get_post_meta($post_id, 'zip_code', true));
            break;
        case 'phone':
            echo esc_html(get_post_meta($post_id, 'phone', true));
            break;
        case 'petition_id':
            echo esc_html(get_post_meta($post_id, 'petition_id', true));
            break;
        case 'signature_date':
            $date = get_post_meta($post_id, 'signature_date', true);
            if ($date) {
                echo esc_html(date('M j, Y g:i a', strtotime($date)));
            }
            break;
    }
}

// ========================================
// PETITION HELPER FUNCTIONS
// ========================================

/**
 * Generate petition form HTML
 * 
 * @param array $args Form configuration arguments
 * @return string HTML for petition form
 */
function ctf_generate_petition_form($args = array()) {
    $defaults = array(
        'petition_id' => get_the_ID(),
        'form_id' => 'petition-form',
        'show_phone' => true,
        'submit_text' => 'Sign the Petition',
        'ajax' => true
    );
    
    $args = wp_parse_args($args, $defaults);
    
    ob_start();
    ?>
    <form id="<?php echo esc_attr($args['form_id']); ?>" class="petition-form" method="post" action="<?php echo admin_url('admin-ajax.php'); ?>">
        <?php wp_nonce_field('ctf_petition_form', 'petition_nonce'); ?>
        <input type="hidden" name="action" value="ctf_submit_petition">
        <input type="hidden" name="petition_id" value="<?php echo esc_attr($args['petition_id']); ?>">
        
        <div class="form-row">
            <div class="form-group">
                <label for="first_name">First Name *</label>
                <input type="text" id="first_name" name="first_name" required>
            </div>
            
            <div class="form-group">
                <label for="last_name">Last Name *</label>
                <input type="text" id="last_name" name="last_name" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="zip_code">ZIP Code *</label>
                <input type="text" id="zip_code" name="zip_code" required>
            </div>
        </div>
        
        <?php if ($args['show_phone']): ?>
        <div class="form-row">
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone">
            </div>
        </div>
        <?php endif; ?>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <?php echo esc_html($args['submit_text']); ?>
            </button>
        </div>
        
        <div class="form-messages" style="display: none;"></div>
    </form>
    
    <?php if ($args['ajax']): ?>
    <script>
    jQuery(document).ready(function($) {
        $('#<?php echo esc_js($args['form_id']); ?>').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $button = $form.find('button[type="submit"]');
            var $messages = $form.find('.form-messages');
            
            // Disable submit button
            $button.prop('disabled', true).text('Submitting...');
            
            $.ajax({
                type: 'POST',
                url: $form.attr('action'),
                data: $form.serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $messages.removeClass('error').addClass('success')
                                .html('<p>' + response.data.message + '</p>')
                                .show();
                        $form[0].reset();
                    } else {
                        var errorMessage = '<p>' + response.data.message + '</p>';
                        if (response.data.errors && response.data.errors.length > 0) {
                            errorMessage += '<ul>';
                            response.data.errors.forEach(function(error) {
                                errorMessage += '<li>' + error + '</li>';
                            });
                            errorMessage += '</ul>';
                        }
                        $messages.removeClass('success').addClass('error')
                                .html(errorMessage)
                                .show();
                    }
                },
                error: function() {
                    $messages.removeClass('success').addClass('error')
                            .html('<p>An error occurred. Please try again.</p>')
                            .show();
                },
                complete: function() {
                    // Re-enable submit button
                    $button.prop('disabled', false).text('<?php echo esc_js($args['submit_text']); ?>');
                }
            });
        });
    });
    </script>
    <?php endif; ?>
    <?php
    
    return ob_get_clean();
}

/**
 * Get petition signature count
 * 
 * @param string $petition_id Optional petition ID filter
 * @return int Number of signatures
 */
function ctf_get_petition_signature_count($petition_id = null) {
    $args = array(
        'post_type' => 'petition_signature',
        'post_status' => 'private',
        'numberposts' => -1,
        'fields' => 'ids'
    );
    
    if ($petition_id) {
        $args['meta_query'] = array(
            array(
                'key' => 'petition_id',
                'value' => $petition_id,
                'compare' => '='
            )
        );
    }
    
    $signatures = get_posts($args);
    return count($signatures);
}

/**
 * Get recent petition signatures
 * 
 * @param int $limit Number of signatures to retrieve
 * @param string $petition_id Optional petition ID filter
 * @return array Array of signature data
 */
function ctf_get_recent_petition_signatures($limit = 10, $petition_id = null) {
    $args = array(
        'post_type' => 'petition_signature',
        'post_status' => 'private',
        'numberposts' => $limit,
        'orderby' => 'date',
        'order' => 'DESC'
    );
    
    if ($petition_id) {
        $args['meta_query'] = array(
            array(
                'key' => 'petition_id',
                'value' => $petition_id,
                'compare' => '='
            )
        );
    }
    
    $signatures = get_posts($args);
    $signature_data = array();
    
    foreach ($signatures as $signature) {
        $signature_data[] = array(
            'id' => $signature->ID,
            'first_name' => get_post_meta($signature->ID, 'first_name', true),
            'last_name' => get_post_meta($signature->ID, 'last_name', true),
            'email' => get_post_meta($signature->ID, 'email', true),
            'zip_code' => get_post_meta($signature->ID, 'zip_code', true),
            'phone' => get_post_meta($signature->ID, 'phone', true),
            'petition_id' => get_post_meta($signature->ID, 'petition_id', true),
            'signature_date' => get_post_meta($signature->ID, 'signature_date', true),
            'date' => $signature->post_date
        );
    }
    
    return $signature_data;
}

/**
 * Export petition signatures to CSV
 * 
 * @param string $petition_id Optional petition ID filter
 */
function ctf_export_petition_signatures_csv($petition_id = null) {
    $signatures = ctf_get_recent_petition_signatures(-1, $petition_id);
    
    if (empty($signatures)) {
        wp_die('No signatures found to export.');
    }
    
    $filename = 'petition-signatures-' . date('Y-m-d') . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Add CSV headers
    fputcsv($output, array(
        'First Name',
        'Last Name', 
        'Email',
        'ZIP Code',
        'Phone',
        'Petition ID',
        'Signature Date'
    ));
    
    // Add signature data
    foreach ($signatures as $signature) {
        fputcsv($output, array(
            $signature['first_name'],
            $signature['last_name'],
            $signature['email'],
            $signature['zip_code'],
            $signature['phone'],
            $signature['petition_id'],
            $signature['signature_date']
        ));
    }
    
    fclose($output);
    exit;
}