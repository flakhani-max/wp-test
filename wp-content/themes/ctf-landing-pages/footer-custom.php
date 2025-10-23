<?php
// Custom Footer based on taxpayer.com
?>
<footer class="site-footer">
    <div class="footer-content">
        <p>&copy; <?php echo date('Y'); ?> Canadian Taxpayers Federation. All rights reserved.</p>
        <p>
            <a href="<?php echo esc_url(get_privacy_policy_url()); ?>">Privacy Policy</a>
        </p>
    </div>
</footer>
<?php
?>
<?php wp_footer(); ?>
