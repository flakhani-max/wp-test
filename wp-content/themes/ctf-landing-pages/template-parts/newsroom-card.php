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
$newsroom_type = get_field('newsroom_type');
$newsroom_province = get_field('newsroom_province');
$newsroom_author = get_field('newsroom_author');

// Type labels mapping
$type_labels = array(
    'news_release' => 'News Release',
    'commentary' => 'Commentary',
    'blog' => 'Blog',
    'video' => 'Video',
);

// Province labels mapping
$province_labels = array(
    'federal' => 'Federal',
    'ab' => 'Alberta',
    'bc' => 'British Columbia',
    'mb' => 'Manitoba',
    'nb' => 'New Brunswick',
    'nl' => 'Newfoundland and Labrador',
    'ns' => 'Nova Scotia',
    'on' => 'Ontario',
    'pe' => 'Prince Edward Island',
    'qc' => 'Quebec',
    'sk' => 'Saskatchewan',
    'nt' => 'Northwest Territories',
    'nu' => 'Nunavut',
    'yt' => 'Yukon',
);
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
        
        <div class="newsroom-meta">
            <div class="card-date">
                <?php echo get_the_date('F j, Y'); ?>
            </div>
            <?php if ($newsroom_type) : ?>
                <span class="newsroom-type"><?php echo esc_html($type_labels[$newsroom_type] ?? $newsroom_type); ?></span>
            <?php endif; ?>
            <?php if ($newsroom_province) : ?>
                <span class="newsroom-province"><?php echo esc_html($province_labels[$newsroom_province] ?? $newsroom_province); ?></span>
            <?php endif; ?>
            <?php if ($newsroom_author) : ?>
                <span class="newsroom-author">By <?php echo esc_html($newsroom_author); ?></span>
            <?php endif; ?>
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

