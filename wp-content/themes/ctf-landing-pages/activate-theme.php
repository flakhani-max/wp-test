<?php
// wp-content/themes/ctf-petition-theme/activate-theme.php
add_action('after_switch_theme', function() {
    // Set front page to a static page if not already set
    $front_page = get_option('page_on_front');
    if (!$front_page) {
        $page = get_page_by_title('Home');
        if (!$page) {
            $page_id = wp_insert_post([
                'post_title' => 'Home',
                'post_status' => 'publish',
                'post_type' => 'page',
            ]);
        } else {
            $page_id = $page->ID;
        }
        update_option('show_on_front', 'page');
        update_option('page_on_front', $page_id);
    }
});
