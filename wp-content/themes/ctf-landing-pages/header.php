<?php
defined('ABSPATH') || exit;
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <link rel="icon" href="https://www.taxpayer.com/favicon.ico" type="image/x-icon">
    <title><?php wp_title('|', true, 'right'); ?><?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
    <?php include get_template_directory() . '/snippets/analytics-inits.php'; ?>
</head>
<body <?php body_class(); ?>>
<?php
// Check if navigation should be shown for donation pages
$show_navigation = true;
if (is_singular('donation') && function_exists('get_field')) {
    $show_nav_field = get_field('show_navigation');
    // If field exists and is explicitly set to false, hide navigation
    if ($show_nav_field === false) {
        $show_navigation = false;
    }
}
$header_class = $show_navigation ? 'site-header' : 'site-header no-navigation';
?>
<header class="<?php echo esc_attr($header_class); ?>">
    <div class="header-content">
        <a href="/" class="site-logo">
            <img src="https://taxpayer-media-bucket.storage.googleapis.com/uploads/Taxpayer.comVectorStandUpBeHeard-scaled.png" alt="Taxpayer.com">
        </a>
        <?php if ($show_navigation) : ?>
        <button class="mobile-menu-toggle" aria-label="Toggle menu" aria-expanded="false">
            <span class="hamburger"></span>
            <span class="hamburger"></span>
            <span class="hamburger"></span>
        </button>
        <nav class="main-navigation">
            <ul>
                <li>Test</li>
                <li><a href="/newsroom/">Newsroom</a></li>
                <li><a href="/petitions/">Petitions</a></li>
                <li><a href="/donation/">Campaigns</a></li>
                <li><a href="/about/">About</a></li>
                <li><a href="/donation/donate" class="donate-btn">Donate</a></li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</header>
