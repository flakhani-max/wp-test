<?php
/**
 * Admin UI cleanup for unused core sections.
 */

if (!defined('ABSPATH')) exit;

add_action('admin_menu', 'ctf_hide_core_content_menus', 999);
function ctf_hide_core_content_menus() {
    remove_menu_page('edit.php');            // Posts
    remove_menu_page('edit-comments.php');   // Comments
}

add_action('admin_bar_menu', 'ctf_hide_core_admin_bar_items', 999);
function ctf_hide_core_admin_bar_items($wp_admin_bar) {
    $wp_admin_bar->remove_node('new-post');
    $wp_admin_bar->remove_node('comments');
}

/**
 * Replace "Howdy" greeting with username and optional (Admin).
 * Runs after core builds the toolbar to overwrite the default label.
 */
add_action('admin_bar_menu', 'ctf_replace_howdy_in_toolbar', 11);
function ctf_replace_howdy_in_toolbar($wp_admin_bar) {
    $user = wp_get_current_user();
    if (!$user || !$user->exists()) {
        return;
    }

    $node = $wp_admin_bar->get_node('my-account');
    if (!$node) {
        return;
    }

    $is_admin = current_user_can('manage_options');
    $label = $user->user_login . ($is_admin ? ' (Admin)' : '');

    $wp_admin_bar->add_node(array(
        'id'    => 'my-account',
        'title' => esc_html($label),
        'href'  => $node->href,
        'meta'  => $node->meta,
    ));
}

// Fallback: strip "Howdy, %s" from translations to catch any remaining occurrences.
add_filter('gettext', 'ctf_remove_howdy_translation', 10, 3);
function ctf_remove_howdy_translation($translation, $text, $domain) {
    if ($domain === 'default' && $text === 'Howdy, %s') {
        return '%s';
    }
    return $translation;
}

// Dashboard cleanup and quick links
add_action('wp_dashboard_setup', 'ctf_customize_dashboard');
function ctf_customize_dashboard() {
    // Remove default widgets we don't use
    $widgets = array(
        'dashboard_activity',
        'dashboard_quick_press',
        'dashboard_primary',
        'dashboard_right_now',
        'dashboard_site_health',
        'dashboard_recent_comments',
        'dashboard_incoming_links',
    );
    foreach ($widgets as $widget) {
        remove_meta_box($widget, 'dashboard', 'normal');
        remove_meta_box($widget, 'dashboard', 'side');
    }

    // Add a simple quick-links widget
    wp_add_dashboard_widget(
        'ctf_dashboard_quick_links',
        __('Quick Links', 'ctf-custom'),
        'ctf_render_dashboard_links'
    );
}

function ctf_render_dashboard_links() {
    $links = array(
        array('label' => 'Donations', 'url' => admin_url('edit.php?post_type=donation')),
        array('label' => 'Petitions', 'url' => admin_url('edit.php?post_type=petition')),
        array('label' => 'Media Library', 'url' => admin_url('upload.php')),
        array('label' => 'Mailchimp Settings', 'url' => admin_url('options-general.php?page=ctf-mailchimp-settings')),
    );

    echo '<ul style="margin:0; padding-left:16px;">';
    foreach ($links as $link) {
        printf(
            '<li style="margin:4px 0;"><a href="%s">%s</a></li>',
            esc_url($link['url']),
            esc_html($link['label'])
        );
    }
    echo '</ul>';
}

// Footer branding
add_filter('admin_footer_text', 'ctf_admin_footer_text');
function ctf_admin_footer_text() {
    return 'Powered by Canadian Taxpayers Federation · Need help? <a href="mailto:jbowes@taxpayer.com">Contact the web team</a>';
}
