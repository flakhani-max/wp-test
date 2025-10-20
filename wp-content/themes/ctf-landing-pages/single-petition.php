<?php
/**
 * Single Petition Template
 * Template for displaying individual petition custom post type
 */

// Get content from ACF fields if they exist, otherwise use defaults
$content = [
    'title' => get_field('petition_title') ?: get_the_title(),
    'image' => get_field('petition_image') ?: 'https://www.taxpayer.com/media/cars-4-14-2014.jpg',
    'intro' => get_field('petition_intro') ?: 'Used cars should not be a government cash cow.',
    'body' => get_field('petition_body') ?: 'But governments charge you provincial and/or federal sales tax when you buy a used car, no matter how many times the car has been sold and taxed before.',
    'petition' => get_field('petition_text') ?: 'We, the undersigned, call on all governments to scrap sales tax on used cars.',
    'cta' => get_field('petition_cta') ?: 'Sign the petition and join hundreds of thousands Canadian taxpayers receiving our Action Update emails.',
    'sms_optin' => get_field('petition_sms_text') ?: 'SMS: I also want to receive occasional text messages to keep me up to date.',
    'petition_tag' => get_field('petition_tag') ?: '',
    'privacy' => [
        'text' => get_field('privacy_text') ?: 'We take data security and privacy seriously. Your information will be kept safe, and will be used to sign your petition.',
        'link' => get_field('privacy_link') ?: 'https://www.taxpayer.com/privacy-policy/',
        'link_text' => get_field('privacy_link_text') ?: 'Privacy Policy'
    ]
];

get_header('custom');
?>

<div class="custom-page-content petition-template">
    <?php 
    $categories = get_the_terms(get_the_ID(), 'petition_category');
    if ($categories && !is_wp_error($categories)) : ?>
        <div class="petition-categories" style="margin-bottom:1em;">
            <?php foreach ($categories as $category) : ?>
                <a href="<?php echo esc_url(get_term_link($category)); ?>" 
                   style="display:inline-block;padding:0.25em 0.75em;background:#f0f0f0;color:#333;text-decoration:none;border-radius:3px;font-size:0.9em;margin-right:0.5em;">
                    <?php echo esc_html($category->name); ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <h1><?= esc_html($content['title']) ?></h1>
    <div class="petition-image" style="margin-bottom:1em;">
        <img src="<?= esc_url($content['image']) ?>" alt="<?= esc_attr($content['title']) ?>" style="max-width:100%;height:auto;border-radius:6px;box-shadow:0 1px 8px rgba(0,0,0,0.06);" />
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
        <input type="hidden" name="petition_tag" value="<?= esc_attr($content['petition_tag']) ?>" />
        <input type="hidden" name="petition_id" value="<?= get_the_ID() ?>" />
        <input type="hidden" name="redirect_url" value="<?= esc_url(get_permalink()) ?>" />
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

