<?php
/**
 * Template part for displaying a petition card
 * 
 * @package CTF_Landing_Pages
 */

// Set defaults
$context = isset($context) ? $context : 'archive';
$show_excerpt_length = isset($show_excerpt_length) ? $show_excerpt_length : 25;
$show_category = isset($show_category) ? $show_category : ($context === 'archive');

// Get petition data
$petition_title = get_the_title();
$petition_intro = get_field('petition_intro');
?>

<article class="petition-card <?php echo esc_attr($context); ?>">
    <?php if (has_post_thumbnail()) : ?>
        <div class="card-image">
            <a href="<?php echo esc_url( wp_make_link_relative( get_permalink() ) ); ?>">
                <?php the_post_thumbnail('medium', array('alt' => esc_attr($petition_title))); ?>
            </a>
        </div>
    <?php endif; ?>
    
    <div class="card-content">
        <h3>
            <a href="<?php echo esc_url( wp_make_link_relative( get_permalink() ) ); ?>">
                <?php echo esc_html($petition_title); ?>
            </a>
        </h3>
        
        <div class="card-excerpt">
            <?php 
            if ($petition_intro) {
                echo wp_trim_words($petition_intro, $show_excerpt_length, '...');
            } else {
                $excerpt = get_the_excerpt();
                echo wp_trim_words($excerpt, $show_excerpt_length, '...');
            }
            ?>
        </div>
        
        <?php if ($show_category) : ?>
            <div class="card-meta">
                <?php
                $terms = get_the_terms(get_the_ID(), 'petition_category');
                if ($terms && !is_wp_error($terms)) :
                    $term = array_shift($terms);
                ?>
                    <span class="card-category"><?php echo esc_html($term->name); ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <a href="<?php echo esc_url( wp_make_link_relative( get_permalink() ) ); ?>" class="btn btn-primary card-cta">
            Sign This Petition
        </a>
    </div>
</article>