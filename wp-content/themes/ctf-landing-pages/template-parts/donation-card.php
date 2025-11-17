<?php
/**
 * Template part for displaying a donation card
 * 
 * @package CTF_Landing_Pages
 */

// Set defaults
$context = isset($context) ? $context : 'archive';
$show_excerpt_length = isset($show_excerpt_length) ? $show_excerpt_length : 25;
$show_category = isset($show_category) ? $show_category : ($context === 'archive');

// Get donation data
$donation_image = get_field('donation_image');
$donation_title = get_the_title();
$donation_intro = get_field('donation_intro');
?>

<article class="donation-card <?php echo esc_attr($context); ?>">
    <?php if ($donation_image || has_post_thumbnail()) : ?>
        <div class="card-image">
            <a href="<?php the_permalink(); ?>">
                <?php if ($donation_image) : ?>
                    <img src="<?php echo esc_url($donation_image); ?>" 
                         alt="<?php echo esc_attr($donation_title); ?>" />
                <?php else : ?>
                    <?php the_post_thumbnail('medium'); ?>
                <?php endif; ?>
            </a>
        </div>
    <?php endif; ?>
    
    <div class="card-content">
        <h3>
            <a href="<?php the_permalink(); ?>">
                <?php echo esc_html($donation_title); ?>
            </a>
        </h3>
        
        <div class="card-excerpt">
            <?php 
            if ($donation_intro) {
                echo wp_trim_words($donation_intro, $show_excerpt_length, '...');
            } else {
                $excerpt = get_the_excerpt();
                echo wp_trim_words($excerpt, $show_excerpt_length, '...');
            }
            ?>
        </div>
        
        <?php if ($show_category) : ?>
            <div class="card-meta">
                <?php
                $terms = get_the_terms(get_the_ID(), 'donation_category');
                if ($terms && !is_wp_error($terms)) :
                    $term = array_shift($terms);
                ?>
                    <span class="card-category"><?php echo esc_html($term->name); ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <a href="<?php the_permalink(); ?>" class="btn btn-primary card-cta">
            Donate Now
        </a>
    </div>
</article>