<?php
/**
 * Template part for displaying a newsroom card
 * 
 * @package CTF_Landing_Pages
 */

// Set defaults
$context = isset($context) ? $context : 'archive';
$show_excerpt_length = isset($show_excerpt_length) ? $show_excerpt_length : 25;
$show_category = isset($show_category) ? $show_category : ($context === 'archive');

// Get newsroom data
$newsroom_image = get_field('newsroom_image');
$newsroom_title = get_the_title();
$newsroom_intro = get_the_excerpt();
?>

<article class="newsroom-card <?php echo esc_attr($context); ?>">
    <?php if ($newsroom_image || has_post_thumbnail()) : ?>
        <div class="card-image">
            <a href="<?php the_permalink(); ?>">
                <?php if ($newsroom_image) : ?>
                    <img src="<?php echo esc_url($newsroom_image); ?>" 
                         alt="<?php echo esc_attr($newsroom_title); ?>" />
                <?php else : ?>
                    <?php the_post_thumbnail('medium'); ?>
                <?php endif; ?>
            </a>
        </div>
    <?php endif; ?>
    
    <div class="card-content">
        <h3>
            <a href="<?php the_permalink(); ?>">
                <?php echo esc_html($newsroom_title); ?>
            </a>
        </h3>
        
        <div class="card-date">
            <?php echo get_the_date('F j, Y'); ?>
        </div>
        
        <div class="card-excerpt">
            <?php 
            if ($newsroom_intro) {
                echo wp_trim_words($newsroom_intro, $show_excerpt_length, '...');
            } else {
                $excerpt = get_the_excerpt();
                echo wp_trim_words($excerpt, $show_excerpt_length, '...');
            }
            ?>
        </div>
        
        <?php if ($show_category) : ?>
            <div class="card-meta">
                <?php
                $terms = get_the_terms(get_the_ID(), 'newsroom_category');
                if ($terms && !is_wp_error($terms)) :
                    $term = array_shift($terms);
                ?>
                    <span class="card-category"><?php echo esc_html($term->name); ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <a href="<?php the_permalink(); ?>" class="btn btn-primary card-cta">
            Read More
        </a>
    </div>
</article>

