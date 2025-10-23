<?php
/**
 * Main Index Template
 * The main template file for the CTF Landing Pages theme
 */

get_header('custom');
?>

<div class="home-template card">
    <div class="home-header">
        <h1>Welcome to Canadian Taxpayers Federation</h1>
        <p class="home-subtitle">Fighting for lower taxes, less government waste, and more accountability.</p>
    </div>

    <div class="home-content">
        <div class="content-block">
            <h2>Current Campaigns</h2>
            <p>Join thousands of Canadians taking action on the issues that matter most to taxpayers.</p>
        </div>

        <?php if (have_posts()) : ?>
            <div class="posts-grid">
                <?php while (have_posts()) : the_post(); ?>
                    <article class="post-card">
                        <?php if (has_post_thumbnail()) : ?>
                            <div class="post-image">
                                <?php the_post_thumbnail('medium'); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="post-content">
                            <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                            <div class="post-excerpt">
                                <?php the_excerpt(); ?>
                            </div>
                            <div class="post-meta">
                                <span class="post-date"><?php echo get_the_date(); ?></span>
                                <?php if (get_post_type() === 'petition') : ?>
                                    <span class="post-type">Petition</span>
                                <?php endif; ?>
                            </div>
                            <a href="<?php the_permalink(); ?>" class="btn btn-primary">
                                <?php echo (get_post_type() === 'petition') ? 'Sign Petition' : 'Read More'; ?>
                            </a>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>

            <?php if (get_next_posts_link() || get_previous_posts_link()) : ?>
                <nav class="pagination">
                    <?php
                    echo paginate_links(array(
                        'prev_text' => '&laquo; Previous',
                        'next_text' => 'Next &raquo;'
                    ));
                    ?>
                </nav>
            <?php endif; ?>

        <?php else : ?>
            <div class="no-posts">
                <h2>No campaigns found</h2>
                <p>There are currently no active campaigns or petitions. Check back soon!</p>
            </div>
        <?php endif; ?>

        <div class="home-cta content-block success">
            <h3>Get Involved</h3>
            <p>Stay informed about government spending and tax policy. Join our community of engaged taxpayers.</p>
            <a href="<?php echo esc_url(home_url('/about/')); ?>" class="btn btn-secondary">Learn More About CTF</a>
        </div>
    </div>
</div>

<?php get_footer('custom'); ?>