<?php
/**
 * Single Donation Template
 * Template for displaying donation pages
 */

get_header('custom');

// Get all donation data from plugin (with fallbacks if plugin is inactive)
if (function_exists('ctf_get_donation_data')) {
    $donation_data = ctf_get_donation_data();
    $donation_amounts = $donation_data['amounts'];
    $auto_tag = $donation_data['auto_tag'];
    $show_title = $donation_data['show_title'];
    $campaign_id = $donation_data['campaign_id'];
} else {
    // Fallback if plugin is not active
    $donation_amounts = [25, 50, 100, 200, 500];
    $auto_tag = false;
    $show_title = true;
    $campaign_id = 'donation_' . get_the_ID();
}
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

    <div class="donation-options">
        <div class="donation-frequency">
            <button type="button" class="frequency-btn active" data-frequency="monthly">DONATE MONTHLY</button>
            <button type="button" class="frequency-btn" data-frequency="once">DONATE TODAY</button>
        </div>
        
        <div class="frequency-description">
            <p id="monthly-description" class="description active">A monthly gift will go even further to hold politicians accountable and fight for lower taxes and against government waste.</p>
            <p id="once-description" class="description">Make a one-time donation to support our campaigns for lower taxes and accountable government.</p>
        </div>
    </div>

    <form class="donation-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="process_donation" />
        <input type="hidden" name="donation_frequency" id="donation_frequency" value="monthly" />
        <input type="hidden" name="campaign_id" value="<?php echo esc_attr($campaign_id); ?>" />
        <input type="hidden" name="auto_tag" value="<?php echo $auto_tag ? '1' : '0'; ?>" />
        <input type="hidden" name="post_id" value="<?php echo get_the_ID(); ?>" />
        <input type="hidden" name="amount" id="final_amount" value="" />
        <?php wp_nonce_field('donation_form', 'donation_nonce'); ?>
        
        <div class="amount-section">
            <h3>Donation amount:</h3>
            <div class="amount-grid">
                <?php 
                // Generate amount options from ACF field
                if (!empty($donation_amounts)) :
                    foreach ($donation_amounts as $index => $amount) :
                        $amount = intval($amount);
                        if ($amount > 0) :
                ?>
                    <label class="amount-option">
                        <input type="radio" name="donation_amount" value="<?php echo $amount; ?>" />
                        <span class="amount-display">$<?php echo $amount; ?></span>
                        <?php if ($amount >= 100) : ?>
                            <small>Includes The Taxpayer magazine</small>
                        <?php endif; ?>
                    </label>
                <?php 
                        endif;
                    endforeach;
                else : 
                    // Fallback amounts if ACF field is empty
                ?>
                    <label class="amount-option">
                        <input type="radio" name="donation_amount" value="25" />
                        <span class="amount-display">$25</span>
                    </label>
                    <label class="amount-option">
                        <input type="radio" name="donation_amount" value="50" />
                        <span class="amount-display">$50</span>
                    </label>
                    <label class="amount-option">
                        <input type="radio" name="donation_amount" value="100" />
                        <span class="amount-display">$100</span>
                        <small>Includes The Taxpayer magazine</small>
                    </label>
                    <label class="amount-option">
                        <input type="radio" name="donation_amount" value="200" />
                        <span class="amount-display">$200</span>
                        <small>Includes The Taxpayer magazine</small>
                    </label>
                <?php endif; ?>
                
                <!-- Custom amount option always available -->
                <label class="amount-option custom-amount">
                    <input type="radio" name="donation_amount" value="custom" />
                    <span class="amount-display">Other</span>
                    <input type="number" name="custom_amount" placeholder="$ Amount" min="1" step="0.01" />
                </label>
            </div>
            
            <div class="donation-note">
                <p><strong>Donations of $100 and higher receive The Taxpayer magazine.</strong></p>
                <p><em>Donations to the Canadian Taxpayers Federation are not tax deductible</em></p>
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
                    <input type="text" name="postal_code" class="form-control" placeholder="Postal Code" autocomplete="postal-code" pattern="[A-Za-z]\d[A-Za-z] ?\d[A-Za-z]\d" />
                </div>
            </div>
        </div>

        <div class="payment-section">
            <h3>Credit card information</h3>
            <div class="form-grid">
                <div class="form-group full-width">
                    <input type="text" name="cardholder_name" class="form-control" required placeholder="Cardholder name *" autocomplete="cc-name" />
                </div>
                <div class="form-group">
                    <input type="text" name="card_number" class="form-control" required placeholder="Card number *" autocomplete="cc-number" maxlength="19" />
                </div>
                <div class="form-group">
                    <input type="text" name="cvv" class="form-control" required placeholder="CVV *" autocomplete="cc-csc" maxlength="4" />
                </div>
                <div class="form-group">
                    <select name="expiry_month" class="form-control" required autocomplete="cc-exp-month">
                        <option value="">Month</option>
                        <?php for($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo sprintf('%02d', $i); ?>"><?php echo sprintf('%02d', $i); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <select name="expiry_year" class="form-control" required autocomplete="cc-exp-year">
                        <option value="">Year</option>
                        <?php for($i = date('Y'); $i <= date('Y') + 10; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-large donate-button">Donate</button>
        </div>
    </form>

    <div class="other-options">
        <h3>Other donation options</h3>
        
        <div class="option-list">
            <p><strong>Debit Cards:</strong> If you have a Visa or Mastercard debit card you can use it to make a donation using the Credit Card Information form above.</p>
            
            <p><strong>PayPal:</strong> <a href="https://www.taxpayer.com/utilities/paypal?Id=1&IdCampaigns=18519" target="_blank" rel="noopener">Click here to donate via PayPal</a></p>
            
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
// Enhanced donation form functionality based on taxpayer.com
let selectedAmount = 0;
let isRecurring = false;

document.addEventListener('DOMContentLoaded', function() {
    initializeDonationForm();
    setupFormValidation();
    setupAmountSelection();
    setupFrequencyToggle();
    setupCardValidation();
});

function initializeDonationForm() {
    // Set default state - one-time donation
    const oneTimeBtn = document.querySelector('[data-frequency="onetime"]');
    if (oneTimeBtn && !oneTimeBtn.classList.contains('active')) {
        oneTimeBtn.click();
    }
}

function setupFrequencyToggle() {
    const frequencyBtns = document.querySelectorAll('.frequency-btn');
    const frequencyInput = document.getElementById('donation_frequency');
    const descriptions = document.querySelectorAll('.description');
    
    frequencyBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            frequencyBtns.forEach(b => b.classList.remove('active'));
            descriptions.forEach(d => d.classList.remove('active'));
            
            this.classList.add('active');
            const frequency = this.dataset.frequency;
            
            if (frequencyInput) frequencyInput.value = frequency;
            isRecurring = (frequency === 'monthly');
            
            const targetDesc = document.getElementById(frequency + '-description');
            if (targetDesc) targetDesc.classList.add('active');
            
            // Clear amount selection when switching frequency
            clearAmountSelection();
            updateDonateButton();
        });
    });
}

function setupAmountSelection() {
    // Handle preset amount buttons
    document.querySelectorAll('input[name="donation_amount"]').forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value !== 'custom') {
                selectedAmount = parseFloat(this.value);
                clearCustomAmountInput();
                updateDonateButton();
            }
        });
    });
    
    // Handle custom amount input
    const customAmountInput = document.querySelector('input[name="custom_amount"]');
    const customAmountRadio = document.querySelector('input[value="custom"]');
    
    if (customAmountInput) {
        customAmountInput.addEventListener('focus', function() {
            if (customAmountRadio) customAmountRadio.checked = true;
        });
        
        customAmountInput.addEventListener('input', function() {
            const amount = parseFloat(this.value.replace(/[^0-9.]/g, ''));
            if (!isNaN(amount) && amount > 0) {
                selectedAmount = amount;
                if (customAmountRadio) customAmountRadio.checked = true;
                updateDonateButton();
            } else {
                selectedAmount = 0;
                updateDonateButton();
            }
        });
        
        // Format currency input
        customAmountInput.addEventListener('blur', function() {
            const value = parseFloat(this.value.replace(/[^0-9.]/g, ''));
            if (!isNaN(value) && value > 0) {
                this.value = value.toFixed(2);
            }
        });
    }
}

function clearAmountSelection() {
    selectedAmount = 0;
    document.querySelectorAll('input[name="donation_amount"]').forEach(radio => {
        radio.checked = false;
    });
    clearCustomAmountInput();
}

function clearCustomAmountInput() {
    const customAmountInput = document.querySelector('input[name="custom_amount"]');
    if (customAmountInput) {
        customAmountInput.value = '';
    }
}

function updateDonateButton() {
    const submitBtn = document.querySelector('.donate-button');
    if (!submitBtn) return;
    
    if (selectedAmount > 0) {
        const frequency = isRecurring ? 'monthly' : 'today';
        submitBtn.textContent = `Donate $${selectedAmount.toFixed(2)} ${frequency}`;
        submitBtn.disabled = false;
        submitBtn.classList.remove('btn-disabled');
    } else {
        submitBtn.textContent = 'Select amount to continue';
        submitBtn.disabled = true;
        submitBtn.classList.add('btn-disabled');
    }
}

function setupFormValidation() {
    const form = document.querySelector('.donation-form');
    if (!form) return;
    
    // Real-time validation for all inputs
    const allInputs = form.querySelectorAll('input, select');
    allInputs.forEach(field => {
        field.addEventListener('blur', function() {
            validateField(this);
        });
        
        field.addEventListener('input', function() {
            if (this.classList.contains('is-invalid')) {
                validateField(this);
            }
        });
    });
    
    // Form submission handling
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!validateForm()) {
            return false;
        }
        
        if (selectedAmount <= 0) {
            showError('Please select a donation amount.');
            return false;
        }
        
        if (selectedAmount < 1) {
            showError('Minimum donation amount is $1.00');
            return false;
        }
        
        // Set the final amount value
        const hiddenAmountInput = document.querySelector('input[name="amount"]');
        if (hiddenAmountInput) {
            hiddenAmountInput.value = selectedAmount.toFixed(2);
        }
        
        // Show processing state
        const submitBtn = this.querySelector('.donate-button');
        if (submitBtn) {
            submitBtn.textContent = 'Processing...';
            submitBtn.disabled = true;
        }
        
        // Form is valid, proceed with actual submission
        setTimeout(() => {
            this.submit();
        }, 100);
    });
}

function validateField(field) {
    let isValid = true;
    let errorMessage = '';
    
    field.classList.remove('is-valid', 'is-invalid');
    
    if (field.hasAttribute('required') && !field.value.trim()) {
        isValid = false;
        errorMessage = 'This field is required.';
    } else if (field.value.trim()) {
        // Specific validation rules
        switch (field.type) {
            case 'email':
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(field.value)) {
                    isValid = false;
                    errorMessage = 'Please enter a valid email address.';
                }
                break;
                
            case 'tel':
                const phoneRegex = /^[\d\s\-\+\(\)]+$/;
                if (!phoneRegex.test(field.value)) {
                    isValid = false;
                    errorMessage = 'Please enter a valid phone number.';
                }
                break;
        }
        
        // Postal code validation for Canada
        if (field.name === 'postal_code') {
            const postalRegex = /^[A-Za-z]\d[A-Za-z][ -]?\d[A-Za-z]\d$/;
            if (!postalRegex.test(field.value)) {
                isValid = false;
                errorMessage = 'Please enter a valid Canadian postal code.';
            }
        }
    }
    
    if (field.value.trim() !== '') {
        field.classList.add(isValid ? 'is-valid' : 'is-invalid');
        
        // Show/hide error message
        let errorDiv = field.parentNode.querySelector('.error-message');
        if (!isValid) {
            if (!errorDiv) {
                errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                field.parentNode.appendChild(errorDiv);
            }
            errorDiv.textContent = errorMessage;
        } else if (errorDiv) {
            errorDiv.remove();
        }
    }
    
    return isValid;
}

function validateForm() {
    const form = document.querySelector('.donation-form');
    const requiredFields = form.querySelectorAll('input[required], select[required]');
    let isValid = true;
    let firstInvalidField = null;
    
    requiredFields.forEach(field => {
        if (!validateField(field)) {
            isValid = false;
            if (!firstInvalidField) {
                firstInvalidField = field;
            }
        }
    });
    
    // Scroll to first invalid field
    if (!isValid && firstInvalidField) {
        firstInvalidField.scrollIntoView({ 
            behavior: 'smooth', 
            block: 'center' 
        });
        firstInvalidField.focus();
    }
    
    return isValid;
}

function setupCardValidation() {
    const cardInput = document.querySelector('input[name="card_number"]');
    if (cardInput) {
        cardInput.addEventListener('input', function(e) {
            // Remove non-digits and format with spaces
            let value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            
            // Limit to 19 characters (16 digits + 3 spaces)
            if (formattedValue.length > 19) {
                formattedValue = formattedValue.substring(0, 19);
            }
            
            e.target.value = formattedValue;
            
            // Validate card number using Luhn algorithm
            if (value.length >= 13) {
                const isValid = luhnCheck(value);
                e.target.classList.remove('is-valid', 'is-invalid');
                e.target.classList.add(isValid ? 'is-valid' : 'is-invalid');
            }
        });
    }
    
    // CVV validation
    const cvvInput = document.querySelector('input[name="cvv"]');
    if (cvvInput) {
        cvvInput.addEventListener('input', function(e) {
            // Only allow digits, max 4 characters
            e.target.value = e.target.value.replace(/[^0-9]/g, '').substring(0, 4);
        });
    }
    
    // Expiry validation
    const monthSelect = document.querySelector('select[name="expiry_month"]');
    const yearSelect = document.querySelector('select[name="expiry_year"]');
    
    if (monthSelect && yearSelect) {
        [monthSelect, yearSelect].forEach(select => {
            select.addEventListener('change', function() {
                validateExpiry();
            });
        });
    }
}

function luhnCheck(value) {
    if (/[^0-9-\s]+/.test(value)) return false;
    
    let nCheck = 0;
    let bEven = false;
    value = value.replace(/\D/g, "");
    
    for (let n = value.length - 1; n >= 0; n--) {
        const cDigit = value.charAt(n);
        let nDigit = parseInt(cDigit, 10);
        
        if (bEven && (nDigit *= 2) > 9) nDigit -= 9;
        nCheck += nDigit;
        bEven = !bEven;
    }
    
    return (nCheck % 10) === 0;
}

function validateExpiry() {
    const monthSelect = document.querySelector('select[name="expiry_month"]');
    const yearSelect = document.querySelector('select[name="expiry_year"]');
    
    if (!monthSelect || !yearSelect) return true;
    
    const month = parseInt(monthSelect.value);
    const year = parseInt(yearSelect.value);
    const now = new Date();
    const currentMonth = now.getMonth() + 1;
    const currentYear = now.getFullYear();
    
    let isValid = true;
    
    if (year < currentYear || (year === currentYear && month < currentMonth)) {
        isValid = false;
    }
    
    [monthSelect, yearSelect].forEach(select => {
        select.classList.remove('is-valid', 'is-invalid');
        if (select.value) {
            select.classList.add(isValid ? 'is-valid' : 'is-invalid');
        }
    });
    
    return isValid;
}

function showError(message) {
    // Simple alert for now - could be enhanced with modal or toast
    alert(message);
}

// Utility function to trim whitespace and clean currency input
function cleanCurrencyInput(input) {
    if (input.value) {
        input.value = input.value.trim().replace(/[^0-9.]/g, '');
    }
}

// Add currency cleaning to relevant fields
document.addEventListener('DOMContentLoaded', function() {
    const customAmountInput = document.querySelector('input[name="custom_amount"]');
    if (customAmountInput) {
        customAmountInput.addEventListener('blur', function() {
            cleanCurrencyInput(this);
        });
    }
});
</script>

<?php get_footer('custom'); ?>
