<?php
/**
 * Archive Template for Petitions
 * Displays all petitions in a grid layout
 */

get_header('custom');
?>

<div class="petition-archive card">
    <div class="archive-header">
        <?php if (is_tax('petition_category')) : ?>
            <h1><?php single_term_title(); ?> Petitions</h1>
            <?php if (term_description()) : ?>
                <p class="archive-subtitle"><?php echo term_description(); ?></p>
            <?php endif; ?>
        <?php else : ?>
            <h1>Petitions</h1>
            <p class="archive-subtitle">Join thousands of Canadians taking action on important issues</p>
        <?php endif; ?>
    </div>

    <div class="archive-content">
        <?php if (have_posts()) : ?>
            <div class="petition-archive-grid">
                <?php while (have_posts()) : the_post(); ?>
                    <?php 
                    // Set context for petition card template part
                    $context = 'archive';
                    $show_excerpt_length = 25;
                    $show_category = true;
                    
                    get_template_part('template-parts/petition-card', null, compact('context', 'show_excerpt_length', 'show_category'));
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
            <div class="no-petitions">
                <h2>No petitions found</h2>
                <p>There are currently no petitions available. Check back soon for new campaigns!</p>
                <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-primary">Return to Home</a>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php get_footer('custom'); ?>

