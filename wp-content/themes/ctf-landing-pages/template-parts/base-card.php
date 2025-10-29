<?php
/**
 * Generic card template
 * 
 * Expected variables:
 * - $card_type: 'petition', 'donation', etc.
 * - $context: 'archive', 'featured', etc.
 * - $show_excerpt_length: number of words for excerpt
 * - $show_category: whether to show category
 * - $card_data: array with 'image', 'title', 'intro', 'cta_text', 'taxonomy'
 * 
 * @package CTF_Landing_Pages
 */
?>

<article class="<?php echo esc_attr($card_type); ?>-card <?php echo esc_attr($context); ?>">
    <?php if ($card_data['image'] || has_post_thumbnail()) : ?>
        <div class="card-image">
            <a href="<?php the_permalink(); ?>">
                <?php if ($card_data['image']) : ?>
                    <img src="<?php echo esc_url($card_data['image']); ?>" 
                         alt="<?php echo esc_attr($card_data['title']); ?>" />
                <?php else : ?>
                    <?php the_post_thumbnail('medium'); ?>
                <?php endif; ?>
            </a>
        </div>
    <?php endif; ?>
    
    <div class="card-content">
        <h3>
            <a href="<?php the_permalink(); ?>">
                <?php echo esc_html($card_data['title']); ?>
            </a>
        </h3>
        
        <div class="card-excerpt">
            <?php 
            if ($card_data['intro']) {
                echo wp_trim_words($card_data['intro'], $show_excerpt_length, '...');
            } else {
                $excerpt = get_the_excerpt();
                echo wp_trim_words($excerpt, $show_excerpt_length, '...');
            }
            ?>
        </div>
        
        <?php if ($show_category && $card_data['taxonomy']) : ?>
            <div class="card-meta">
                <?php
                $terms = get_the_terms(get_the_ID(), $card_data['taxonomy']);
                if ($terms && !is_wp_error($terms)) :
                    $term = array_shift($terms);
                ?>
                    <span class="card-category"><?php echo esc_html($term->name); ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <a href="<?php the_permalink(); ?>" class="btn btn-primary card-cta">
            <?php echo esc_html($card_data['cta_text']); ?>
        </a>
    </div>
</article>