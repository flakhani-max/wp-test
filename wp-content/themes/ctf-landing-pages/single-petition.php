<?php
/**
 * Single Petition Template
 * Template for displaying individual petition custom post type
 */

// Get content from ACF fields if they exist, otherwise use defaults
$content = [
    'title' => get_the_title(),
    'image' => get_field('petition_image') ?: 'https://www.taxpayer.com/media/cars-4-14-2014.jpg',
    'body' => get_the_content(),
    'tag_id' => get_field('petition_tag') ?: 'default_tag',
];

get_header('custom');
?>


<div class="petition-template card">
    <div class="petition-content">
        <h1>Will you sign the petition? <?= esc_html($content['title']) ?></h1>
        <div class="petition-image">
            <img src="<?= esc_url($content['image']) ?>" alt="<?= esc_attr($content['title']) ?>" />
        </div>
        <div class="petition-intro">
            <?= apply_filters('the_content', $content['body']) ?>
        </div>
    </div>
    <form class="petition-form" method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
        <input type="hidden" name="action" value="petition_mailchimp_submit" />
        <input type="hidden" name="tag_id" value="<?= esc_attr($content['tag_id']) ?>" />
        <div class="form-grid">
            <div class="form-group">
                <input type="text" id="first_name" name="first_name" class="form-control" required autocomplete="given-name" placeholder="First Name *" />
            </div>
            <div class="form-group">
                <input type="text" id="last_name" name="last_name" class="form-control" required autocomplete="family-name" placeholder="Last Name *" />
            </div>
            <div class="form-group">
                <input type="email" id="email" name="email" class="form-control" required autocomplete="email" placeholder="Email *" />
            </div>
            <div class="form-group">
                <input type="tel" id="mobile" name="mobile" class="form-control" autocomplete="tel" placeholder="Mobile Number" />
            </div>
            <div class="form-group">
                <input type="text" id="street_address" name="street_address" class="form-control" autocomplete="street-address" placeholder="Street Address" />
            </div>
            <div class="form-group">
                <input type="text" id="city" name="city" class="form-control" autocomplete="address-level2" placeholder="City" />
            </div>
            <div class="form-group">
                <input type="text" id="postal_code" name="postal_code" class="form-control" required autocomplete="postal-code" pattern="[A-Za-z]\d[A-Za-z] ?\d[A-Za-z]\d" placeholder="Postal Code *" />
            </div>
            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" id="sms_optin" name="sms_optin" value="1" />
                    <label for="sms_optin">SMS: I also want to receive occasional text messages to keep me up to date.</label>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary btn-large">Sign the petition</button>
    </form>
    <div class="petition-privacy">
        <p>We take data security and privacy seriously. Your information will be kept safe, and will be used to sign your petition.</p>
    </div>
</div>

<?php get_footer('custom'); ?>