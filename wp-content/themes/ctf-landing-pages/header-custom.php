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
<header class="site-header">
    <div class="header-content">
        <a href="<?php echo esc_url(home_url('/')); ?>">
            <img src="http://taxpayer-media-bucket.storage.googleapis.com/uploads/2025/11/21173106/Taxpayer.comVectorStandUpBeHeard-scaled.png" alt="Taxpayer.com">
        </a>
        <nav class="main-navigation">
            <ul>
                <li><a href="<?php echo esc_url(home_url('/newsroom/')); ?>">Newsroom</a></li>
                <li><a href="<?php echo esc_url(home_url('/petitions/')); ?>">Petitions</a></li>
                <li><a href="<?php echo esc_url(home_url('/donation/')); ?>">Campaigns</a></li>
                <li><a href="<?php echo esc_url(home_url('/about/')); ?>">About</a></li>
                <li><a href="<?php echo esc_url(home_url('/donation/donate')); ?>" class="donate-btn">Donate</a></li>
            </ul>
        </nav>
    </div>
</header>
