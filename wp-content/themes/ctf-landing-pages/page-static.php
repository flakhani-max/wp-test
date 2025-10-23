<?php
/**
 * Template Name: Static Content Page
 * Description: Clean template for static pages like Privacy Policy, About, Terms of Service
 */

get_header('custom');
?>

<div class="static-page-container">
    <div class="static-page-wrapper">
        <?php if (have_posts()) : ?>
            <?php while (have_posts()) : the_post(); ?>
                <header class="static-page-header">
                    <h1 class="static-page-title"><?php the_title(); ?></h1>
                </header>

                <div class="static-page-content">
                    <?php the_content(); ?>
                </div>

                <footer class="static-page-footer">
                    <div class="static-page-meta">
                        <span class="static-page-date">Last updated: <?php echo get_the_modified_date(); ?></span>
                    </div>
                </footer>
            <?php endwhile; ?>
        <?php else : ?>
            <div class="static-page-not-found">
                <h2>Page Not Found</h2>
                <p>The page you're looking for doesn't exist.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php get_footer('custom'); ?>
