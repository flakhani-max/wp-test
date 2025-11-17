<?php
// Default header
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
    <?php include get_template_directory() . '/snippets/analytics-inits.php'; ?>
</head>
<body <?php body_class(); ?>>
<header class="site-header">
    <div class="header-content">
        <a href="<?php echo esc_url(home_url('/')); ?>">
            <img src="https://www.taxpayer.com/media/Taxpayer.comVectorStandUpBeHeard.png" alt="Taxpayer.com">
        </a>
        <nav class="main-navigation">
            <ul>
                <li><a href="<?php echo esc_url(home_url('/')); ?>">Home</a></li>
                <li><a href="<?php echo esc_url(home_url('/petition/')); ?>">Petitions</a></li>
                <li><a href="<?php echo esc_url(home_url('/donation/')); ?>">Donations</a></li>
            </ul>
        </nav>
    </div>
</header>
