<?php
/**
 * Donation content partial.
 * Used by single-donation template (and other contexts) without header/footer.
 */

// Get all donation data from plugin (with fallbacks if plugin is inactive)
if (function_exists('ctf_get_donation_data')) {
    $donation_data = ctf_get_donation_data();
    $donation_amounts = $donation_data['amounts'];
    $auto_tag = $donation_data['auto_tag'];
    $show_title = $donation_data['show_title'];
    $campaign_id = $donation_data['campaign_id'];
} else {
    // Fallback if plugin is not active - will be overridden by JS
    $donation_amounts = [15, 20, 25];
    $auto_tag = false;
    $show_title = true;
    $campaign_id = 'donation_' . get_the_ID();
}

// Get amounts from ACF fields
if (function_exists('ctf_get_monthly_amounts') && function_exists('ctf_get_onetime_amounts')) {
    $monthly_amounts = ctf_get_monthly_amounts();
    $onetime_amounts = ctf_get_onetime_amounts();
} else {
    // Fallback if functions don't exist
    $monthly_amounts = [15, 20, 25];
    $onetime_amounts = [15, 20, 25, 50, 100, 200];
}

// Get frequency display options
if (function_exists('ctf_get_frequency_display')) {
    $frequency_display = ctf_get_frequency_display();
} else {
    $frequency_display = array('monthly', 'onetime');
}

$show_monthly = in_array('monthly', $frequency_display);
$show_onetime = in_array('onetime', $frequency_display);
?>

<div class="donation-template card">
    <div class="donation-header">
        <?php if ($show_title && have_posts()) : ?>
            <?php while (have_posts()) : the_post(); ?>
                <h1><?php the_title(); ?></h1>
            <?php endwhile; ?>
        <?php else: ?>
            <h1>You clicked to donate – here's your next step:</h1>
        <?php endif; ?>
        
        <div class="donation-intro">
            <?php if (have_posts()) : ?>
                <?php while (have_posts()) : the_post(); ?>
                    <?php if (get_the_content()) : ?>
                        <div class="donation-content">
                        <?php the_content(); ?>
                        </div>
                    <?php else: ?>
                        <!-- Default content if no custom content is provided -->
                        <p>For 99% of people, it's a big step to read the stories and sign the petitions on Taxpayer.com. We're grateful to all of those people.</p>
                        
                        <p><strong>But what about the other 1%?</strong></p>
                        
                        <p>About 1% of people who support the Canadian Taxpayers Federation make a donation.</p>
                        
                        <p>Those core donors are the reason we can take a stand for lower taxes, less waste and more accountable government.</p>
                        
                        <p><strong>You can donate now on the secure form below!</strong></p>
                    <?php endif; ?>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($show_monthly && $show_onetime): ?>
    <div class="donation-options">
        <div class="toggle-wrapper">
            <div class="donation-frequency-toggle">
                <input type="radio" id="frequency-monthly" name="frequency_toggle" value="monthly">
                <input type="radio" id="frequency-once" name="frequency_toggle" value="once" checked>
                <div class="toggle-slider"></div>
                <label for="frequency-monthly" class="toggle-label">DONATE MONTHLY</label>
                <label for="frequency-once" class="toggle-label">DONATE TODAY</label>
            </div>
        </div>
        
        <div class="monthly-prompt">
            <span class="prompt-arrow">↓</span><p>A monthly gift will go even further to hold politicians accountable and fight for lower taxes and against government waste.</p>
        </div>
    </div>
    <?php endif; ?>

    <form id="donation-form" class="donation-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="process_donation" />
        <input type="hidden" name="donation_frequency" id="donation_frequency" value="once" />
        <input type="hidden" name="campaign_id" value="<?php echo esc_attr($campaign_id); ?>" />
        <input type="hidden" name="auto_tag" value="<?php echo $auto_tag ? '1' : '0'; ?>" />
        <input type="hidden" name="post_id" value="<?php echo get_the_ID(); ?>" />
        <input type="hidden" name="amount" id="final_amount" value="" />
        <?php wp_nonce_field('donation_form', 'donation_nonce'); ?>

        <div class="amount-section">
            <h3>
                <?php 
                if ($show_monthly && !$show_onetime) {
                    echo 'Monthly donation amount:';
                } elseif (!$show_monthly && $show_onetime) {
                    echo 'One-time donation amount:';
                } else {
                    echo 'Donation amount:';
                }
                ?>
            </h3>
            <div class="amount-grid" id="amount-grid">
                <!-- Amounts will be dynamically generated by JavaScript -->
            </div>
            
            <div class="donation-info-text">
                <p class="magazine-note">
                <?php 
                    if ($show_monthly && !$show_onetime) {
                        echo 'Monthly donations of $9 and higher receive The Taxpayer magazine.';
                    } elseif (!$show_monthly && $show_onetime) {
                        echo 'Donations of $100 and higher receive The Taxpayer magazine.';
                    } else {
                        // Both options available - show dynamic text
                        echo '<span class="monthly-mag-text">Monthly donations of $9 and higher receive The Taxpayer magazine.</span>';
                        echo '<span class="onetime-mag-text">Donations of $100 and higher receive The Taxpayer magazine.</span>';
                    }
                    ?>
                </p>
                <p class="tax-note">Donations to the Canadian Taxpayers Federation are not tax deductible</p>
            </div>
        </div>

        <div class="donor-info-section">
            <h3>Enter your information:</h3>
        <div class="form-grid">
            <div class="form-group">
                <input type="text" name="first_name" class="form-control" required placeholder="First Name *" autocomplete="given-name" />
            </div>
            <div class="form-group">
                <input type="text" name="last_name" class="form-control" required placeholder="Last Name *" autocomplete="family-name" />
            </div>
            <div class="form-group">
                <input type="email" name="email" class="form-control" required placeholder="Email *" autocomplete="email" />
            </div>
            <div class="form-group">
                <input type="tel" name="phone" class="form-control" placeholder="Phone" autocomplete="tel" />
            </div>
            <div class="form-group">
                <input type="text" name="address" class="form-control" placeholder="Street Address" autocomplete="street-address" />
            </div>
            <div class="form-group">
                <input type="text" name="city" class="form-control" placeholder="City" autocomplete="address-level2" />
            </div>
            <div class="form-group">
                <select name="province" class="form-control" autocomplete="address-level1">
                        <option value="">Select Province</option>
                    <option value="AB">Alberta</option>
                    <option value="BC">British Columbia</option>
                    <option value="MB">Manitoba</option>
                    <option value="NB">New Brunswick</option>
                    <option value="NL">Newfoundland and Labrador</option>
                    <option value="NS">Nova Scotia</option>
                    <option value="ON">Ontario</option>
                    <option value="PE">Prince Edward Island</option>
                    <option value="QC">Quebec</option>
                    <option value="SK">Saskatchewan</option>
                    <option value="NT">Northwest Territories</option>
                    <option value="NU">Nunavut</option>
                    <option value="YT">Yukon</option>
                </select>
                </div>
                <div class="form-group">
                    <input type="text" name="postal_code" class="form-control" placeholder="Postal Code" autocomplete="postal-code" pattern="[A-Za-z]\\d[A-Za-z] ?\\d[A-Za-z]\\d" />
                </div>
            </div>
        </div>

        <div class="payment-section">
            <h3>Payment method</h3>
            
            <!-- Payment Request Button (Google Pay, Apple Pay, etc.) -->
            <div id="payment-request-button" class="payment-request-button-container"></div>
            
            <!-- PayPal Button -->
            <div id="paypal-button-container" class="paypal-button-container"></div>
            
            <div class="payment-divider">
                <span>or pay with card</span>
            </div>
            
            <!-- Stripe Card Element will be inserted here -->
            <div id="card-element" class="stripe-card-element"></div>
            
            <!-- Display card errors -->
            <div id="card-errors" class="card-errors" role="alert"></div>
            
            <button type="submit" class="btn btn-primary btn-large donate-button">Donate</button>
        </div>
    </form>

    <div class="other-options">
        <h3>Other donation options</h3>
        
        <div class="option-list">
            <p><strong>By calling:</strong> 1-800-667-7933</p>
            
            <p><strong>E-transfer:</strong> Send to admin@taxpayer.com</p>
            
            <p><strong>By mail:</strong> <a href="https://www.taxpayer.com/media/Donation%20Form_updatedJuly2025.pdf" target="_blank" rel="noopener">Click here</a> for a printable version of our donation form.<br>
            Mail: 501 - 2201 11th Ave, Regina, SK S4P 0J8</p>
            
            <p><strong>Legacy giving:</strong> <a href="https://www.taxpayer.com/donate/a-guide-to-legacy-giving" target="_blank" rel="noopener">Click here</a> if you would like more information on leaving a permanent contribution to the Canadian Taxpayers Federation!</p>
        </div>
        
        <div class="contact-info">
            <p>If you have any questions regarding your donation please contact us at <a href="mailto:admin@taxpayer.com">admin@taxpayer.com</a></p>
        </div>
    </div>
</div>

<script>
// Pass donation amounts and Stripe key to JavaScript
window.donationAmounts = {
    monthly: <?php echo json_encode($monthly_amounts); ?>,
    onetime: <?php echo json_encode($onetime_amounts); ?>,
    showMonthly: <?php echo json_encode($show_monthly); ?>,
    showOnetime: <?php echo json_encode($show_onetime); ?>
};

// Pass Stripe publishable key to JavaScript
window.stripePublishableKey = '<?php echo esc_js(getenv('STRIPE_PUBLISHABLE_KEY')); ?>';

// Pass PayPal client ID to JavaScript  
window.paypalClientId = '<?php echo esc_js(getenv('PAYPAL_CLIENT_ID')); ?>';
</script>
