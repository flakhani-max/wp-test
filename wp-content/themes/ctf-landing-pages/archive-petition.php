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

    <!-- Filter Pane -->
    <div class="petition-filters">
        <form method="GET" action="<?php echo esc_url(home_url('/petitions/')); ?>" class="filter-form">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="filter-province">Province</label>
                    <select name="petition_province" id="filter-province">
                        <?php 
                        $current_province = get_query_var('petition_province');
                        $has_province_param = isset($_GET['petition_province']);
                        // If parameter exists but is empty, user selected "All Provinces"
                        // If parameter doesn't exist, it's first visit (default to federal)
                        $is_all_provinces = $has_province_param && $current_province === '';
                        $is_federal = !$has_province_param || ($has_province_param && $current_province === 'federal');
                        ?>
                        <option value="" <?php selected($is_all_provinces, true); ?>>All Provinces</option>
                        <option value="federal" <?php selected($is_federal, true); ?>>Federal</option>
                        <option value="ab" <?php selected(get_query_var('petition_province'), 'ab'); ?>>Alberta</option>
                        <option value="bc" <?php selected(get_query_var('petition_province'), 'bc'); ?>>British Columbia</option>
                        <option value="mb" <?php selected(get_query_var('petition_province'), 'mb'); ?>>Manitoba</option>
                        <option value="nb" <?php selected(get_query_var('petition_province'), 'nb'); ?>>New Brunswick</option>
                        <option value="nl" <?php selected(get_query_var('petition_province'), 'nl'); ?>>Newfoundland and Labrador</option>
                        <option value="ns" <?php selected(get_query_var('petition_province'), 'ns'); ?>>Nova Scotia</option>
                        <option value="on" <?php selected(get_query_var('petition_province'), 'on'); ?>>Ontario</option>
                        <option value="pe" <?php selected(get_query_var('petition_province'), 'pe'); ?>>Prince Edward Island</option>
                        <option value="qc" <?php selected(get_query_var('petition_province'), 'qc'); ?>>Quebec</option>
                        <option value="sk" <?php selected(get_query_var('petition_province'), 'sk'); ?>>Saskatchewan</option>
                        <option value="nt" <?php selected(get_query_var('petition_province'), 'nt'); ?>>Northwest Territories</option>
                        <option value="nu" <?php selected(get_query_var('petition_province'), 'nu'); ?>>Nunavut</option>
                        <option value="yt" <?php selected(get_query_var('petition_province'), 'yt'); ?>>Yukon</option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="<?php echo esc_url(home_url('/petitions/')); ?>" class="btn btn-secondary">Clear</a>
                </div>
            </div>
        </form>
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

<?php get_footer(); ?>