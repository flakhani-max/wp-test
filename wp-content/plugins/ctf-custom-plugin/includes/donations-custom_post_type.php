<?php
/**
 * Donation Post Type and ACF Fields
 * 
 * Handles the donation custom post type registration and 
 * Advanced Custom Fields configuration for donation settings.
 */

if (!defined('ABSPATH')) exit;

// Register Donation Custom Post Type
add_action('init', 'ctf_register_donation_cpt');

/**
 * Register the donation custom post type
 */
function ctf_register_donation_cpt() {
    $labels = array(
        'name'                  => _x('Donations', 'Post type general name', 'ctf-custom'),
        'singular_name'         => _x('Donation', 'Post type singular name', 'ctf-custom'),
        'menu_name'             => _x('Donations', 'Admin Menu text', 'ctf-custom'),
        'name_admin_bar'        => _x('Donation', 'Add New on Toolbar', 'ctf-custom'),
        'add_new'               => __('Add New', 'ctf-custom'),
        'add_new_item'          => __('Add New Donation', 'ctf-custom'),
        'new_item'              => __('New Donation', 'ctf-custom'),
        'edit_item'             => __('Edit Donation', 'ctf-custom'),
        'view_item'             => __('View Donation', 'ctf-custom'),
        'all_items'             => __('All Donations', 'ctf-custom'),
        'search_items'          => __('Search Donations', 'ctf-custom'),
        'parent_item_colon'     => __('Parent Donations:', 'ctf-custom'),
        'not_found'             => __('No donations found.', 'ctf-custom'),
        'not_found_in_trash'    => __('No donations found in Trash.', 'ctf-custom'),
        'featured_image'        => _x('Donation Featured Image', 'Overrides the "Featured Image" phrase for this post type. Added in 4.3', 'ctf-custom'),
        'set_featured_image'    => _x('Set featured image', 'Overrides the "Set featured image" phrase for this post type. Added in 4.3', 'ctf-custom'),
        'remove_featured_image' => _x('Remove featured image', 'Overrides the "Remove featured image" phrase for this post type. Added in 4.3', 'ctf-custom'),
        'use_featured_image'    => _x('Use as featured image', 'Overrides the "Use as featured image" phrase for this post type. Added in 4.3', 'ctf-custom'),
        'archives'              => _x('Donation archives', 'The post type archive label used in nav menus. Default "Post Archives". Added in 4.4', 'ctf-custom'),
        'insert_into_item'      => _x('Insert into donation', 'Overrides the "Insert into post"/"Insert into page" phrase (used when inserting media into a post). Added in 4.4', 'ctf-custom'),
        'uploaded_to_this_item' => _x('Uploaded to this donation', 'Overrides the "Uploaded to this post"/"Uploaded to this page" phrase (used when viewing media attached to a post). Added in 4.4', 'ctf-custom'),
        'filter_items_list'     => _x('Filter donations list', 'Screen reader text for the filter links heading on the post type listing screen. Default "Filter posts list"/"Filter pages list". Added in 4.4', 'ctf-custom'),
        'items_list_navigation' => _x('Donations list navigation', 'Screen reader text for the pagination heading on the post type listing screen. Default "Posts list navigation"/"Pages list navigation". Added in 4.4', 'ctf-custom'),
        'items_list'            => _x('Donations list', 'Screen reader text for the items list heading on the post type listing screen. Default "Posts list"/"Pages list". Added in 4.4', 'ctf-custom'),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'donation'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'menu_icon'          => 'dashicons-heart',
        'supports'           => array('title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'),
        'show_in_rest'       => false,
    );

    register_post_type('donation', $args);
}

// Add ACF fields for donation post type
add_action('acf/init', 'ctf_add_donation_acf_fields');

/**
 * Add ACF fields for donation settings
 */
function ctf_add_donation_acf_fields() {
    // Check if ACF is available
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group(array(
        'key' => 'group_donation_settings',
        'title' => 'Donation Settings',
        'fields' => array(
            array(
                'key' => 'field_donation_auto_tag',
                'label' => 'Auto Tag',
                'name' => 'auto_tag',
                'type' => 'true_false',
                'instructions' => 'Enable automatic tagging for this donation campaign',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'message' => 'Enable auto-tagging',
                'default_value' => 0,
                'ui' => 1,
                'ui_on_text' => 'Yes',
                'ui_off_text' => 'No',
            ),
            array(
                'key' => 'field_donation_show_title',
                'label' => 'Show Title',
                'name' => 'show_title',
                'type' => 'true_false',
                'instructions' => 'Display the donation title on the front-end',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'message' => 'Show title on page',
                'default_value' => 1,
                'ui' => 1,
                'ui_on_text' => 'Show',
                'ui_off_text' => 'Hide',
            ),
            array(
                'key' => 'field_donation_frequency_display',
                'label' => 'Frequency Display Options',
                'name' => 'frequency_display',
                'type' => 'checkbox',
                'instructions' => 'Select which donation frequency options to display on the page',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'choices' => array(
                    'monthly' => 'Show Monthly Donations',
                    'onetime' => 'Show One-Time Donations',
                ),
                'default_value' => array('monthly', 'onetime'),
                'layout' => 'vertical',
                'toggle' => 0,
                'return_format' => 'value',
                'allow_custom' => 0,
            ),
            array(
                'key' => 'field_donation_monthly_amounts',
                'label' => 'Monthly Donation Amounts',
                'name' => 'monthly_amounts',
                'type' => 'repeater',
                'instructions' => 'Add monthly donation amount options (integers only)',
                'required' => 0,
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_donation_frequency_display',
                            'operator' => '==',
                            'value' => 'monthly',
                        ),
                    ),
                ),
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
                'collapsed' => '',
                'min' => 0,
                'max' => 10,
                'layout' => 'table',
                'button_label' => 'Add Amount',
                'sub_fields' => array(
                    array(
                        'key' => 'field_monthly_amount_value',
                        'label' => 'Amount',
                        'name' => 'amount',
                        'type' => 'number',
                        'instructions' => 'Enter monthly donation amount',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '',
                        'placeholder' => '15',
                        'prepend' => '$',
                        'append' => '',
                        'min' => 1,
                        'max' => 10000,
                        'step' => 1,
                    ),
                ),
            ),
            array(
                'key' => 'field_donation_onetime_amounts',
                'label' => 'One-Time Donation Amounts',
                'name' => 'onetime_amounts',
                'type' => 'repeater',
                'instructions' => 'Add one-time donation amount options (integers only)',
                'required' => 0,
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_donation_frequency_display',
                            'operator' => '==',
                            'value' => 'onetime',
                        ),
                    ),
                ),
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
                'collapsed' => '',
                'min' => 0,
                'max' => 10,
                'layout' => 'table',
                'button_label' => 'Add Amount',
                'sub_fields' => array(
                    array(
                        'key' => 'field_onetime_amount_value',
                        'label' => 'Amount',
                        'name' => 'amount',
                        'type' => 'number',
                        'instructions' => 'Enter one-time donation amount',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '',
                        'placeholder' => '25',
                        'prepend' => '$',
                        'append' => '',
                        'min' => 1,
                        'max' => 10000,
                        'step' => 1,
                    ),
                ),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'donation',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
        'description' => '',
    ));
}

// Add admin notice if ACF is not active
add_action('admin_notices', 'ctf_acf_admin_notice');

/**
 * Show admin notice if ACF is not installed/activated
 */
function ctf_acf_admin_notice() {
    if (!function_exists('acf_add_local_field_group')) {
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>CTF Custom Plugin:</strong> Advanced Custom Fields (ACF) plugin is required for donation settings to work properly. Please install and activate ACF.</p>';
        echo '</div>';
    }
}

// ========================================
// DONATION HELPER FUNCTIONS
// ========================================

/**
 * Get donation auto tag setting
 * 
 * @param int $post_id Optional post ID, defaults to current post
 * @return bool
 */
function ctf_get_donation_auto_tag($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    return get_field('auto_tag', $post_id);
}

/**
 * Get donation show title setting
 * 
 * @param int $post_id Optional post ID, defaults to current post
 * @return bool
 */
function ctf_get_donation_show_title($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    return get_field('show_title', $post_id);
}

/**
 * Get frequency display options
 * 
 * @param int $post_id Optional post ID, defaults to current post
 * @return array Array with 'monthly' and/or 'onetime'
 */
function ctf_get_frequency_display($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    $frequency = get_field('frequency_display', $post_id);
    
    // Default to both if not set
    if (empty($frequency)) {
        return array('monthly', 'onetime');
    }
    
    return $frequency;
}

/**
 * Get monthly donation amount options
 * 
 * @param int $post_id Optional post ID, defaults to current post
 * @return array Array of donation amounts
 */
function ctf_get_monthly_amounts($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    $amounts = get_field('monthly_amounts', $post_id);
    $amount_values = array();
    
    if ($amounts && is_array($amounts)) {
        foreach ($amounts as $amount) {
            if (isset($amount['amount']) && is_numeric($amount['amount'])) {
                $amount_values[] = intval($amount['amount']);
            }
        }
    }
    
    // Default monthly amounts if empty
    if (empty($amount_values)) {
        $amount_values = array(15, 20, 25);
    }
    
    return $amount_values;
}

/**
 * Get one-time donation amount options
 * 
 * @param int $post_id Optional post ID, defaults to current post
 * @return array Array of donation amounts
 */
function ctf_get_onetime_amounts($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    $amounts = get_field('onetime_amounts', $post_id);
    $amount_values = array();
    
    if ($amounts && is_array($amounts)) {
        foreach ($amounts as $amount) {
            if (isset($amount['amount']) && is_numeric($amount['amount'])) {
                $amount_values[] = intval($amount['amount']);
            }
        }
    }
    
    // Default one-time amounts if empty
    if (empty($amount_values)) {
        $amount_values = array(15, 20, 25, 50, 100, 200);
    }
    
    return $amount_values;
}

/**
 * Get donation amount options (DEPRECATED - kept for backwards compatibility)
 * 
 * @param int $post_id Optional post ID, defaults to current post
 * @return array Array of donation amounts
 */
function ctf_get_donation_amount_options($post_id = null) {
    // Return one-time amounts for backwards compatibility
    return ctf_get_onetime_amounts($post_id);
}

/**
 * Get donation amount options with fallback
 * 
 * @param int $post_id Optional post ID, defaults to current post
 * @return array Array of donation amounts
 */
function ctf_get_donation_amounts_safe($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    // Check if this is a donation post type
    if (get_post_type($post_id) !== 'donation') {
        return [25, 50, 100, 200, 500]; // Default amounts
    }
    
    $amounts = ctf_get_donation_amount_options($post_id);
    
    // Return default amounts if ACF field is empty
    if (empty($amounts)) {
        return [25, 50, 100, 200, 500];
    }
    
    return $amounts;
}

/**
 * Get donation campaign ID for tracking
 * 
 * @param int $post_id Optional post ID, defaults to current post
 * @return string Campaign ID
 */
function ctf_get_donation_campaign_id($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    return 'donation_' . $post_id;
}

/**
 * Check if donation should auto-tag with fallback
 * 
 * @param int $post_id Optional post ID, defaults to current post
 * @return bool
 */
function ctf_get_donation_auto_tag_safe($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    // Check if this is a donation post type
    if (get_post_type($post_id) !== 'donation') {
        return false;
    }
    
    return ctf_get_donation_auto_tag($post_id);
}

/**
 * Check if donation title should be shown with fallback
 * 
 * @param int $post_id Optional post ID, defaults to current post
 * @return bool
 */
function ctf_get_donation_show_title_safe($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    // Check if this is a donation post type
    if (get_post_type($post_id) !== 'donation') {
        return true; // Default to showing title for non-donation posts
    }
    
    return ctf_get_donation_show_title($post_id);
}

/**
 * Generate donation amount options HTML
 * 
 * @param array $amounts Array of donation amounts
 * @param string $context Context for the amounts (e.g., 'onetime', 'monthly')
 * @return string HTML for amount options
 */
function ctf_generate_donation_amounts_html($amounts, $context = 'onetime') {
    if (empty($amounts) || !is_array($amounts)) {
        return '';
    }
    
    $html = '';
    foreach ($amounts as $amount) {
        $amount = intval($amount);
        if ($amount > 0) {
            $html .= sprintf(
                '<label class="amount-option">
                    <input type="radio" name="donation_amount" value="%d" />
                    <span class="amount-display">$%d</span>
                    %s
                </label>',
                $amount,
                $amount,
                $amount >= 100 ? '<small>Includes The Taxpayer magazine</small>' : ''
            );
        }
    }
    
    return $html;
}

/**
 * Get all donation-related data for a post in one function
 * Convenient for templates
 * 
 * @param int $post_id Optional post ID, defaults to current post
 * @return array Array containing all donation settings
 */
function ctf_get_donation_data($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    return [
        'amounts' => ctf_get_donation_amounts_safe($post_id),
        'auto_tag' => ctf_get_donation_auto_tag_safe($post_id),
        'show_title' => ctf_get_donation_show_title_safe($post_id),
        'campaign_id' => ctf_get_donation_campaign_id($post_id),
        'post_id' => $post_id,
        'is_donation_post' => (get_post_type($post_id) === 'donation')
    ];
}