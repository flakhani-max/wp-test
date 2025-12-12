<?php
/**
 * Single Newsroom Template
 * Template for displaying individual newsroom custom post type
 */

// Get content from ACF fields if they exist, otherwise use defaults
$content = [
    'title' => get_the_title(),
    'image' => get_the_post_thumbnail_url(get_the_ID(), 'large') ?: 'https://www.taxpayer.com/media/cars-4-14-2014.jpg',
    'body' => get_the_content(),
];

get_header('custom');
?>


<div class="newsroom-template card">
    <div class="newsroom-content">
        <h1><?= esc_html($content['title']) ?></h1>
        
        <div class="newsroom-meta">
            <span class="date">
                <?php echo get_the_date('F j, Y'); ?>
            </span>
            <?php
            $terms = get_the_terms(get_the_ID(), 'newsroom_category');
            if ($terms && !is_wp_error($terms)) :
                $term = array_shift($terms);
            ?>
                <span class="category">
                    <?php echo esc_html($term->name); ?>
                </span>
            <?php endif; ?>
        </div>
        
        <div class="newsroom-image">
            <img src="<?= esc_url($content['image']) ?>" alt="<?= esc_attr($content['title']) ?>" />
        </div>
        <div class="newsroom-intro">
            <?= apply_filters('the_content', $content['body']) ?>
        </div>
    </div>
</div>

<?php get_footer('custom'); ?>