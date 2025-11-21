<?php
/**
 * Main Index Template
 * The main template file for the CTF Landing Pages theme
 */

get_header('custom');
?>

<div class="home-template card">
    <div class="home-header">
        <h1>Canadian Taxpayers Federation</h1>
        <p class="home-subtitle">Fighting for lower taxes, less government waste, and more accountability.</p>
    </div>

    <div class="home-content">
        <div class="content-block">
            <h2>Join the Fight</h2>
            <p>Join thousands of Canadians taking action on the issues that matter most to taxpayers.</p>
        </div>

        <?php
        // Get 3 most recent petitions for featured section
        $recent_petitions = new WP_Query(array(
            'post_type' => 'petition',
            'posts_per_page' => 4,
            'post_status' => 'publish',
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => 'petition_active',
                    'value' => '1',
                    'compare' => '='
                ),
                array(
                    'key' => 'petition_active',
                    'compare' => 'NOT EXISTS'
                )
            )
        ));

        $recent_donations= new WP_Query(array(
            'post_type' => 'donation',
            'posts_per_page' => 4,
            'post_status' => 'publish',
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => 'donation_active',
                    'value' => '1',
                    'compare' => '='
                ),
                array(
                    'key' => 'donation_active',
                    'compare' => 'NOT EXISTS'
                )
            )
        ));

        
        ?>

        <?php if ($recent_petitions->have_posts()) : ?>
            <div class="content-block">
                <div class="section-header">
                    <h2>Petitions</h2>
                </div>
                
                <div class="petition-archive-grid">
                    <?php while ($recent_petitions->have_posts()) : $recent_petitions->the_post(); ?>
                        <?php 
                        // Set context for petition card template part
                        $context = 'featured';
                        $show_excerpt_length = 20;
                        $show_category = false;
                        
                        get_template_part('template-parts/petition-card', null, compact('context', 'show_excerpt_length', 'show_category'));
                        ?>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                </div>
                
                <div class="petition-archive-link">
                    <a href="<?php echo get_post_type_archive_link('petition'); ?>" class="btn btn-secondary">
                        View All Petitions
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($recent_donations->have_posts()) : ?>
            <div class="content-block">
                <div class="section-header">
                    <h2>Campaigns</h2>
                </div>
                
                <div class="donation-archive-grid">
                    <?php while ($recent_donations->have_posts()) : $recent_donations->the_post(); ?>
                        <?php 
                        // Get the post slug
                        $post_slug = get_post_field('post_name', get_the_ID());
                        // Skip if it's the donate page
                        if ($post_slug === 'donate' ) {
                            continue;
                        }
                        
                        // Set context for petition card template part
                        $context = 'featured';
                        $show_excerpt_length = 20;
                        $show_category = false;
                        
                        get_template_part('template-parts/donation-card', null, compact('context', 'show_excerpt_length', 'show_category'));
                        ?>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                </div>
                
                <div class="donation-archive-link">
                    <a href="<?php echo get_post_type_archive_link('donation'); ?>" class="btn btn-secondary">
                        View All Donations
                    </a>
                </div>
            </div>
        <?php endif; ?>


        <div class="content-block">
            <a href="<?php echo esc_url(home_url('/about/')); ?>" class="btn btn-secondary">Learn More About CTF</a>
        </div>
    </div>
</div>

<?php get_footer('custom'); ?>