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
<header class="site-header" style="background:#1a8cff;color:#fff;padding:1.5em 0 1em 0;text-align:center;">
    <div class="header-content" style="max-width:900px;margin:0 auto;">
        <a href="https://www.taxpayer.com/" style="color:#fff;text-decoration:none;font-size:2em;font-weight:700;letter-spacing:1px;">
            Canadian Taxpayers Federation
        </a>
    </div>
</header>
