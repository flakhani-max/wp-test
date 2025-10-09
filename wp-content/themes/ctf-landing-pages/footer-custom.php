<?php
// Custom Footer based on taxpayer.com
?>
<footer class="site-footer" style="background:#1a2a3a;color:#fff;padding:2em 0 1em 0;text-align:center;">
    <div class="footer-content" style="max-width:900px;margin:0 auto;">
        <p style="margin-bottom:0.5em;">&copy; <?php echo date('Y'); ?> Canadian Taxpayers Federation. All rights reserved.</p>
        <p style="margin-bottom:0.5em;">
            <a href="https://www.taxpayer.com/privacy-policy/" style="color:#fff;text-decoration:underline;">Privacy Policy</a>
        </p>
        <p style="font-size:0.95em;opacity:0.8;">We take data security and privacy seriously. Your information will be kept safe, and will be used to sign your petition.</p>
    </div>
</footer>
<?php
// Example: Google Analytics (replace with your own tracking code)
/*
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-XXXXXXX-X"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', 'UA-XXXXXXX-X');
</script>
*/
?>
<?php wp_footer(); ?>
</content>
