
<?php
/**
 * Template Name: Custom Page Template
 * create a wordpress page template based on this page: https://www.taxpayer.com/petitions/no-sales-tax-on-used-cars
 */

$content = [
    'title' => 'Will you sign the petition? No Sales Tax on Used Cars',
    'image' => 'https://www.taxpayer.com/media/cars-4-14-2014.jpg',
    'intro' => 'Used cars should not be a government cash cow.',
    'body' => 'But governments charge you provincial and/or federal sales tax when you buy a used car, no matter how many times the car has been sold and taxed before.',
    'petition' => 'We, the undersigned, call on all governments to scrap sales tax on used cars.',
    'cta' => 'Sign the petition and join hundreds of thousands Canadian taxpayers receiving our Action Update emails.',
    'sms_optin' => 'SMS: I also want to receive occasional text messages to keep me up to date.',
    'privacy' => [
        'text' => 'We take data security and privacy seriously. Your information will be kept safe, and will be used to sign your petition.',
        'link' => 'https://www.taxpayer.com/privacy-policy/',
        'link_text' => 'Privacy Policy'
    ]
];

get_header('custom');
?>


<div class="custom-page-content petition-template">
    <h1><?= esc_html($content['title']) ?></h1>
    <div class="petition-image" style="margin-bottom:1em;">
        <img src="<?= esc_url($content['image']) ?>" alt="No Sales Tax on Used Cars" style="max-width:100%;height:auto;border-radius:6px;box-shadow:0 1px 8px rgba(0,0,0,0.06);" />
    </div>
    <div class="petition-intro">
        <p><?= esc_html($content['intro']) ?></p>
        <p><?= esc_html($content['body']) ?></p>
        <p><strong><?= esc_html($content['petition']) ?></strong></p>
    </div>
    <div class="petition-cta">
        <p><?= esc_html($content['cta']) ?></p>
    </div>
    <?php if (isset($_GET['petition_success'])): ?>
        <div class="petition-success" role="alert" style="background:#e6f9e6;color:#207520;padding:1em;border-radius:4px;margin-bottom:1em;text-align:center;">
            Thank you for signing the petition!
        </div>
    <?php elseif (isset($_GET['petition_error'])): ?>
        <div class="petition-error" role="alert" style="background:#ffeaea;color:#a12a2a;padding:1em;border-radius:4px;margin-bottom:1em;text-align:center;">
            There was a problem submitting your petition. Please try again.
        </div>
    <?php endif; ?>
    <form class="petition-form" method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
        <input type="hidden" name="action" value="petition_mailchimp_submit" />
        <div class="form-group">
            <label for="name">Name <span aria-hidden="true" style="color:#a12a2a;">*</span></label>
            <input type="text" id="name" name="name" required autocomplete="name" />
        </div>
        <div class="form-group">
            <label for="email">Email <span aria-hidden="true" style="color:#a12a2a;">*</span></label>
            <input type="email" id="email" name="email" required autocomplete="email" />
        </div>
        <div class="form-group">
            <label for="postal">Postal Code <span aria-hidden="true" style="color:#a12a2a;">*</span></label>
            <input type="text" id="postal" name="postal" required autocomplete="postal-code" />
        </div>
        <div class="form-group">
            <label><input type="checkbox" name="sms_optin" value="1" /> <?= esc_html($content['sms_optin']) ?></label>
        </div>
        <button type="submit">Sign the petition</button>
    </form>
    <div class="petition-privacy" style="margin-top:1em;font-size:0.95em;">
        <p><?= esc_html($content['privacy']['text']) ?> <a href="<?= esc_url($content['privacy']['link']) ?>" target="_blank" rel="noopener"><?= esc_html($content['privacy']['link_text']) ?></a></p>
    </div>
    <div class="petition-powered" style="margin-top:2em;text-align:center;font-size:0.95em;opacity:0.7;">
        Powered by <a href="https://www.taxpayer.com/" target="_blank" rel="noopener" style="color:#1a8cff;">Canadian Taxpayers Federation</a>
    </div>
</div>

<?php get_footer('custom'); ?>