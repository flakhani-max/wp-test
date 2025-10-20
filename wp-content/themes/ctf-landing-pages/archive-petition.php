<?php
/**
 * Archive Petition Template
 * Template for displaying the petition archive (list of all petitions)
 */

get_header('custom');
?>

<div class="custom-page-content petition-archive">
    <h1>All Petitions</h1>
    
    <?php if (have_posts()) : ?>
        <div class="petition-list" style="display:grid;gap:2em;margin-top:2em;">
            <?php while (have_posts()) : the_post(); ?>
                <article class="petition-item" style="border:1px solid #e0e0e0;border-radius:6px;padding:1.5em;box-shadow:0 1px 4px rgba(0,0,0,0.06);">
                    <h2 style="margin-top:0;">
                        <a href="<?php the_permalink(); ?>" style="color:#1a8cff;text-decoration:none;">
                            <?php echo esc_html(get_field('petition_title') ?: get_the_title()); ?>
                        </a>
                    </h2>
                    
                    <?php if (get_field('petition_image')): ?>
                        <div class="petition-thumbnail" style="margin-bottom:1em;">
                            <a href="<?php the_permalink(); ?>">
                                <img src="<?php echo esc_url(get_field('petition_image')); ?>" 
                                     alt="<?php echo esc_attr(get_field('petition_title') ?: get_the_title()); ?>" 
                                     style="max-width:100%;height:auto;border-radius:4px;" />
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (get_field('petition_intro')): ?>
                        <div class="petition-excerpt">
                            <p><?php echo esc_html(get_field('petition_intro')); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <a href="<?php the_permalink(); ?>" class="petition-read-more" 
                       style="display:inline-block;margin-top:1em;padding:0.75em 1.5em;background:#1a8cff;color:#fff;text-decoration:none;border-radius:4px;font-weight:600;">
                        Sign This Petition →
                    </a>
                </article>
            <?php endwhile; ?>
        </div>
        
        <div class="pagination" style="margin-top:2em;">
            <?php
            the_posts_pagination(array(
                'mid_size' => 2,
                'prev_text' => '← Previous',
                'next_text' => 'Next →',
            ));
            ?>
        </div>
        
    <?php else : ?>
        <p>No petitions found.</p>
    <?php endif; ?>
</div>

<?php get_footer('custom'); ?>

