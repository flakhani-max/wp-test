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

    <!-- Filter Pane -->
    <div class="newsroom-filters">
        <form method="GET" action="<?php echo esc_url(home_url('/newsroom/')); ?>" class="filter-form">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="filter-search">Search</label>
                    <input type="text" name="s" id="filter-search" 
                           value="<?php echo esc_attr(get_query_var('s')); ?>" 
                           placeholder="Search newsroom...">
                </div>

                <div class="filter-group">
                    <label for="filter-type">Type</label>
                    <select name="news_type" id="filter-type">
                        <option value="">All Types</option>
                        <option value="news_release" <?php selected(get_query_var('news_type'), 'news_release'); ?>>News Release</option>
                        <option value="commentary" <?php selected(get_query_var('news_type'), 'commentary'); ?>>Commentary</option>
                        <option value="blog" <?php selected(get_query_var('news_type'), 'blog'); ?>>Blog</option>
                        <option value="video" <?php selected(get_query_var('news_type'), 'video'); ?>>Video</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="filter-province">Province</label>
                    <select name="news_province" id="filter-province">
                        <option value="">All Provinces</option>
                        <option value="federal" <?php selected(get_query_var('news_province') ?: 'federal', 'federal'); ?>>Federal</option>
                        <option value="ab" <?php selected(get_query_var('news_province'), 'ab'); ?>>Alberta</option>
                        <option value="bc" <?php selected(get_query_var('news_province'), 'bc'); ?>>British Columbia</option>
                        <option value="mb" <?php selected(get_query_var('news_province'), 'mb'); ?>>Manitoba</option>
                        <option value="nb" <?php selected(get_query_var('news_province'), 'nb'); ?>>New Brunswick</option>
                        <option value="nl" <?php selected(get_query_var('news_province'), 'nl'); ?>>Newfoundland and Labrador</option>
                        <option value="ns" <?php selected(get_query_var('news_province'), 'ns'); ?>>Nova Scotia</option>
                        <option value="on" <?php selected(get_query_var('news_province'), 'on'); ?>>Ontario</option>
                        <option value="pe" <?php selected(get_query_var('news_province'), 'pe'); ?>>Prince Edward Island</option>
                        <option value="qc" <?php selected(get_query_var('news_province'), 'qc'); ?>>Quebec</option>
                        <option value="sk" <?php selected(get_query_var('news_province'), 'sk'); ?>>Saskatchewan</option>
                        <option value="nt" <?php selected(get_query_var('news_province'), 'nt'); ?>>Northwest Territories</option>
                        <option value="nu" <?php selected(get_query_var('news_province'), 'nu'); ?>>Nunavut</option>
                        <option value="yt" <?php selected(get_query_var('news_province'), 'yt'); ?>>Yukon</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="filter-date-start">Date From</label>
                    <input type="date" name="date_start" id="filter-date-start" 
                           value="<?php echo esc_attr(get_query_var('date_start')); ?>">
                </div>

                <div class="filter-group">
                    <label for="filter-date-end">Date To</label>
                    <input type="date" name="date_end" id="filter-date-end" 
                           value="<?php echo esc_attr(get_query_var('date_end')); ?>">
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="<?php echo esc_url(home_url('/newsroom/')); ?>" class="btn btn-secondary">Clear</a>
                </div>
            </div>
        </form>
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

