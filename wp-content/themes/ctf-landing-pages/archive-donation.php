<?php
/**
 * Archive Template for Donations
 * Displays all donations in a grid layout
 */

get_header('custom');
?>

<div class="donation-archive card">
    <div class="archive-header">
        <?php if (is_tax('donation_category')) : ?>
            <h1><?php single_term_title(); ?> Donations</h1>
            <?php if (term_description()) : ?>
                <p class="archive-subtitle"><?php echo term_description(); ?></p>
            <?php endif; ?>
        <?php else : ?>
            <h1>Donations</h1>
            <p class="archive-subtitle">Support important causes and make a difference for Canadian taxpayers</p>
        <?php endif; ?>
    </div>

    <div class="archive-content">
        <?php if (have_posts()) : ?>
            <div class="donation-archive-grid">
                <?php while (have_posts()) : the_post(); ?>
                    <?php 
                    // Set context for donation card template part
                    $context = 'archive';
                    $show_excerpt_length = 25;
                    $show_category = true;
                    
                    get_template_part('template-parts/donation-card', null, compact('context', 'show_excerpt_length', 'show_category'));
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
            <div class="no-donations">
                <h2>No donation campaigns found</h2>
                <p>There are currently no donation campaigns available. Check back soon for new opportunities to support our cause!</p>
                <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-primary">Return to Home</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="archive-cta content-block">
        <h3>Want to Make a General Donation?</h3>
        <p>Support the Canadian Taxpayers Federation's ongoing work to fight for lower taxes and government accountability.</p>
        <a href="<?php echo esc_url(home_url('/donate/')); ?>" class="btn btn-secondary">Make a Donation</a>
    </div>
</div>

<?php get_footer('custom'); ?>