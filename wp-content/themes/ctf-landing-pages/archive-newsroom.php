<?php
/**
 * Archive Template for Newsroom
 * Displays all newsroom items in a grid layout
 */

get_header('custom');
?>

<div class="newsroom-archive card">
    <div class="archive-header">
        <?php if (is_tax('newsroom_category')) : ?>
            <h1><?php single_term_title(); ?> News</h1>
            <?php if (term_description()) : ?>
                <p class="archive-subtitle"><?php echo term_description(); ?></p>
            <?php endif; ?>
        <?php else : ?>
            <h1>Newsroom</h1>
            <p class="archive-subtitle">Latest news and updates from the Canadian Taxpayers Federation</p>
        <?php endif; ?>
    </div>

    <div class="archive-content">
        <?php if (have_posts()) : ?>
            <div class="newsroom-archive-grid">
                <?php while (have_posts()) : the_post(); ?>
                    <?php 
                    // Set context for newsroom card template part
                    $context = 'archive';
                    $show_excerpt_length = 25;
                    $show_category = true;
                    
                    get_template_part('template-parts/newsroom-card', null, compact('context', 'show_excerpt_length', 'show_category'));
                    ?>
                <?php endwhile; ?>
            </div>

            <nav class="pagination">
                <?php
                echo paginate_links(array(
                    'prev_text' => '&laquo; Previous',
                    'next_text' => 'Next &raquo;'
                ));
                ?>
            </nav>

        <?php else : ?>
            <div class="no-newsroom">
                <h2>No news found</h2>
                <p>There are currently no news articles available. Check back soon for updates!</p>
                <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-primary">Return to Home</a>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php get_footer(); ?>

