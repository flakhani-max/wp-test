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
        <a href="<?php echo esc_url(home_url('/')); ?>">CTF Petition Theme</a>
    </div>
</header>
