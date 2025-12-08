<?php
/**
 * Archive Template for Newsroom
 * Displays all newsroom items in a grid layout
 */

get_header();
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
        <form method="GET" action="/newsroom/" class="filter-form">
            <div class="filter-row one-line">
                <!-- Type first -->
                <div class="filter-group compact">
                    <select name="news_type" id="filter-type" aria-label="Type">
                        <option value="">All Types</option>
                        <option value="news_release" <?php selected(get_query_var('news_type'), 'news_release'); ?>>News Release</option>
                        <option value="commentary" <?php selected(get_query_var('news_type'), 'commentary'); ?>>Commentary</option>
                        <option value="blog" <?php selected(get_query_var('news_type'), 'blog'); ?>>Blog</option>
                        <option value="video" <?php selected(get_query_var('news_type'), 'video'); ?>>Video</option>
                    </select>
                </div>

                <!-- Province second -->
                <div class="filter-group compact">
                    <select name="news_province" id="filter-province" aria-label="Province">
                        <?php 
                        $current_province = get_query_var('news_province');
                        $has_province_param = isset($_GET['news_province']);
                        $is_all_provinces = $has_province_param && $current_province === '';
                        $is_federal = !$has_province_param || ($has_province_param && $current_province === 'federal');
                        ?>
                        <option value="" <?php selected($is_all_provinces, true); ?>>All Provinces</option>
                        <option value="federal" <?php selected($is_federal, true); ?>>Federal</option>
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

                <!-- Collapsible Date range -->
                <div class="filter-group date-collapsible">
                    <details>
                        <summary>
                            <span class="summary-icon" aria-hidden="true">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                  <rect x="3" y="5" width="18" height="16" rx="2" stroke="#495057" stroke-width="1.5"/>
                                  <path d="M8 3v4M16 3v4" stroke="#495057" stroke-width="1.5" stroke-linecap="round"/>
                                  <path d="M3 9h18" stroke="#495057" stroke-width="1.5"/>
                                </svg>
                            </span>
                            <span class="summary-text">Date Range</span>
                        </summary>
                        <div class="date-range">
                            <div class="date-field">
                                <input type="date" name="date_start" id="filter-date-start" aria-label="Date From"
                                       value="<?php echo esc_attr(get_query_var('date_start')); ?>">
                            </div>
                            <div class="date-field">
                                <input type="date" name="date_end" id="filter-date-end" aria-label="Date To"
                                       value="<?php echo esc_attr(get_query_var('date_end')); ?>">
                            </div>
                        </div>
                    </details>
                </div>

                <!-- Compact search that expands on focus -->
                <div class="filter-group search-compact">
                    <input type="text" name="s" id="filter-search" aria-label="Search"
                           value="<?php echo esc_attr(get_query_var('s')); ?>"
                           placeholder="Search newsroom">
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
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
                /** @var string|string[]|false $pagination */
                $pagination = paginate_links(array(
                    'prev_text' => '&laquo; Previous',
                    'next_text' => 'Next &raquo;',
                    'echo' => false
                ));
                if ($pagination) {
                    $site_url = home_url();
                    // Convert absolute site URLs to relative paths
                    echo str_replace($site_url, '', $pagination);
                }
                ?>
            </nav>

        <?php else : ?>
            <div class="no-newsroom">
                <h2>No news found</h2>
                <p>There are currently no news articles available. Check back soon for updates!</p>
                <a href="/" class="btn btn-primary">Return to Home</a>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php get_footer(); ?>
