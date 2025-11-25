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
    <div class="content-block">
            <h2>Join the Fight</h2>
            <p>Join thousands of Canadians taking action on the issues that matter most to taxpayers.</p>
        </div>


    <!-- Featured Images Section -->
    <?php
    // Get featured images from ACF options, or use default images from theme directory
    $featured_images = get_field('homepage_featured_images', 'option');
    
    // If no ACF images set, use default images from theme directory
    if (!$featured_images || empty($featured_images)) {
        $default_images = array(
            array(
                'image' => get_template_directory_uri() . '/images/axing_the_tax.jpg',
                'link' => 'https://www.amazon.ca/Axing-Tax-Rise-Canadas-Carbon/dp/1998365654?crid=2OONCZL11NVYE&dib=eyJ2IjoiMSJ9.hXsYzImbI3wNjTz8giJ_PxLTufLv15gW6GRrs9WvlwxnKtL1dOqMWxEv9MlnMJ6ECk-faGv5DyN2ITKfHB6HYw01ows02BI55xgVX-5bedY.CDs9QXtFXipToK01qNjW9FuYJiL5VdA_70_s-1-omjY&dib_tag=se&keywords=axing+the+tax+book&qid=1744722373&sprefix=axing%2Caps%2C89&sr=8-1&linkCode=ll1&tag=ctf07-web-20&linkId=404ae4a1ed233542e2e678533e938614&language=en_CA&ref_=as_li_ss_tl', // Update this URL in WordPress admin: Homepage Settings
                'title' => 'Order Now: Axing the Tax', // Add title here
                'alt_text' => 'Axing the Tax'
            ),
            
            array(
                'image' => get_template_directory_uri() . '/images/Main Podcast Logo Rectangle.jpg',
                'link' => 'https://canadian-taxpayers-podcast.castos.com', // Update this URL in WordPress admin: Homepage Settings
                'title' => 'Canadian Taxpayers Podcast', // Add title here
                'alt_text' => 'Main Podcast'
            ),
            array(
                'image' => get_template_directory_uri() . '/images/cameras.png',
                'link' => esc_url(home_url('/petitions/defund-the-cbc-and-end-media-bailout/')), // Update this URL in WordPress admin: Homepage Settings
                'title' => 'Petition: Defund the CBC and End Media Bailout', // Add title here
                'alt_text' => 'Cameras'
            ),
            array(
                'image' => get_template_directory_uri() . '/images/store.jpg',
                'link' => 'https://shop.taxpayer.com', // Update this URL in WordPress admin: Homepage Settings
                'title' => 'Taxpayers Federation T-Shirt Store', // Add title here
                'alt_text' => 'Store'
            ),
        );
        $featured_images = $default_images;
    }
    ?>
    <?php if ($featured_images && !empty($featured_images)) : ?>
        <div class="home-featured-images">
            <div class="featured-images-grid">
                <?php foreach ($featured_images as $item) : 
                    // Handle ACF image field (can be array or ID) or direct URL
                    if (is_array($item['image'])) {
                        $image_url = $item['image']['url'] ?? '';
                        $alt_text = $item['image']['alt'] ?? ($item['alt_text'] ?? 'Featured Image');
                    } elseif (is_numeric($item['image'])) {
                        $image_data = wp_get_attachment_image_src($item['image'], 'full');
                        $image_url = $image_data[0] ?? '';
                        $alt_text = get_post_meta($item['image'], '_wp_attachment_image_alt', true) ?: ($item['alt_text'] ?? 'Featured Image');
                    } else {
                        $image_url = $item['image'] ?? '';
                        $alt_text = $item['alt_text'] ?? 'Featured Image';
                    }
                    
                    $link_url = $item['link'] ?? '#';
                    $title = $item['title'] ?? '';
                ?>
                    <a href="<?php echo esc_url($link_url); ?>" class="featured-image-link">
                        <img src="<?php echo esc_url($image_url); ?>" 
                             alt="<?php echo esc_attr($alt_text); ?>" 
                             class="featured-image">
                        <?php if (!empty($title)) : ?>
                            <div class="featured-image-title"><?php echo esc_html($title); ?></div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="home-content">

        <?php
        // Get 3 most recent petitions for featured section
        $recent_petitions = new WP_Query(array(
            'post_type' => 'petition',
            'posts_per_page' => 3,
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
            'posts_per_page' => 3,
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

        $recent_newsroom = new WP_Query(array(
            'post_type' => 'newsroom',
            'posts_per_page' => 3,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
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

        <?php if ($recent_newsroom->have_posts()) : ?>
            <div class="content-block">
                <div class="section-header">
                    <h2>Newsroom</h2>
                </div>
                
                <div class="newsroom-archive-grid">
                    <?php while ($recent_newsroom->have_posts()) : $recent_newsroom->the_post(); ?>
                        <?php 
                        // Set context for newsroom card template part
                        $context = 'featured';
                        $show_excerpt_length = 20;
                        $show_category = false;
                        
                        get_template_part('template-parts/newsroom-card', null, compact('context', 'show_excerpt_length', 'show_category'));
                        ?>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                </div>
                
                <div class="newsroom-archive-link">
                    <a href="<?php echo get_post_type_archive_link('newsroom'); ?>" class="btn btn-secondary">
                        View All News
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