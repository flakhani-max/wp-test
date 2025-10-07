<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
    <div class="hello-world-container">
        <span class="emoji">👋</span>
        <h1>Hello World!</h1>
        <p>Welcome to your WordPress site running in Docker</p>
        <p style="margin-top: 20px; font-size: 0.9em; color: #999;">
            Deployed with Google Cloud Run via GitHub Actions
        </p>
    </div>
    <?php wp_footer(); ?>
</body>
</html>

