/**
 * Donation Template JavaScript
 * Handles all donation form functionality including amount selection,
 * frequency toggle, form validation, and Stripe payment processing
 */

// Global variables
let selectedAmount = 0;
let isRecurring = false;
let monthlyAmounts = [];
let onetimeAmounts = [];
let stripe = null;
let elements = null;
let cardElement = null;
let paymentRequest = null;

/**
 * Initialize the donation form
 */
document.addEventListener('DOMContentLoaded', function() {
    // Get donation amounts from window object (set by PHP)
    if (window.donationAmounts) {
        monthlyAmounts = window.donationAmounts.monthly;
        onetimeAmounts = window.donationAmounts.onetime;
        const showMonthly = window.donationAmounts.showMonthly;
        const showOnetime = window.donationAmounts.showOnetime;
        console.log('Monthly amounts:', monthlyAmounts);
        console.log('One-time amounts:', onetimeAmounts);
        console.log('Show monthly:', showMonthly);
        console.log('Show onetime:', showOnetime);
        
        // Determine which frequency to show by default
        let defaultFrequency = 'once';
        if (showOnetime && showMonthly) {
            defaultFrequency = 'once'; // Both available, default to one-time
        } else if (showMonthly && !showOnetime) {
            defaultFrequency = 'monthly'; // Only monthly available
        } else if (showOnetime && !showMonthly) {
            defaultFrequency = 'once'; // Only one-time available
        }
        
        initializeDonationForm();
        if (showMonthly && showOnetime) {
            setupFrequencyToggle(); // Only setup toggle if both options exist
        }
        renderAmountButtons(defaultFrequency);
        initializeStripe(); // Initialize Stripe Elements
        setupFormValidation();
        // Remove old card validation since we're using Stripe
        // setupCardValidation();
    } else {
        console.error('Donation amounts not found!');
        return;
    }
});

function initializeDonationForm() {
    // Set default state - one-time is checked by default in HTML
    const onceRadio = document.getElementById('frequency-once');
    if (onceRadio && onceRadio.checked) {
        updateToggleLabels('once');
        document.body.classList.add('frequency-once');
        isRecurring = false;
    }
}

/**
 * Initialize Stripe Elements
 */
function initializeStripe() {
    // Check if Stripe publishable key is available
    if (!window.stripePublishableKey) {
        console.error('Stripe publishable key not found!');
        showError('Payment system not configured. Please contact support.');
        return;
    }
    
    // Initialize Stripe
    stripe = Stripe(window.stripePublishableKey);
    
    // Create an instance of Elements
    elements = stripe.elements();
    
    // Custom styling for the card element
    const style = {
        base: {
            color: '#1a2a3a',
            fontFamily: '"Segoe UI", Tahoma, Geneva, Verdana, sans-serif',
            fontSmoothing: 'antialiased',
            fontSize: '16px',
            '::placeholder': {
                color: '#99aab5'
            }
        },
        invalid: {
            color: '#dc3545',
            iconColor: '#dc3545'
        }
    };
    
    // Create the card Element and mount it
    // hidePostalCode: true removes the ZIP/Postal code field since we're in Canada
    // and Stripe's postal code validation is primarily US-focused
    cardElement = elements.create('card', { 
        style: style,
        hidePostalCode: true
    });
    cardElement.mount('#card-element');
    
    // Handle real-time validation errors from the card Element
    cardElement.on('change', function(event) {
        const displayError = document.getElementById('card-errors');
        if (event.error) {
            displayError.textContent = event.error.message;
        } else {
            displayError.textContent = '';
        }
    });
    
    // Initialize Payment Request Button (Google Pay, Apple Pay, etc.)
    initializePaymentRequestButton();
    
    console.log('✅ Stripe initialized successfully');
}

/**
 * Initialize Express Checkout Element (Apple Pay, Google Pay, Link, etc.)
 * Using the simpler Payment Request Button approach (works without clientSecret)
 */
function initializePaymentRequestButton() {
    console.log('🔄 Initializing Payment Request Button...');
    console.log('Selected amount:', selectedAmount);
    
    // Start with amount of 10 if nothing is selected yet
    const initialAmount = selectedAmount > 0 ? selectedAmount : 10;
    
    // Create a Payment Request
    paymentRequest = stripe.paymentRequest({
        country: 'CA',
        currency: 'cad',
        total: {
            label: 'Donation to Canadian Taxpayers Federation',
            amount: Math.round(initialAmount * 100), // Convert to cents
        },
        requestPayerName: true,
        requestPayerEmail: true,
    });
    
    console.log('📦 Payment Request created');
    
    // Create the Payment Request Button Element
    const prButton = elements.create('paymentRequestButton', {
        paymentRequest: paymentRequest,
    });
    
    console.log('🔘 Payment Request Button element created');
    
    // Check if Payment Request is available (browser supports it)
    paymentRequest.canMakePayment().then(function(result) {
        console.log('🔍 canMakePayment result:', result);
        
        if (result) {
            // Mount the button
            prButton.mount('#payment-request-button');
            
            // Show the divider
            document.querySelector('.payment-divider').classList.add('visible');
            
            // Disable button initially if no amount is selected
            if (selectedAmount <= 0) {
                disablePaymentRequestButton();
            }
            
            console.log('✅ Payment Request Button mounted successfully!');
            console.log('Available payment methods:', result);
        } else {
            // Hide the payment request button container if not available
            document.getElementById('payment-request-button').style.display = 'none';
            console.log('ℹ️ Payment Request Button not available on this device/browser');
            console.log('Possible reasons: No saved cards, browser not supported, or HTTPS required');
        }
    }).catch(function(error) {
        console.error('❌ Error checking Payment Request availability:', error);
        document.getElementById('payment-request-button').style.display = 'none';
    });
    
    // Handle payment method from Payment Request Button
    paymentRequest.on('paymentmethod', async function(ev) {
        console.log('💳 Payment method received from Payment Request Button');
        
        // Get form data
        const form = document.getElementById('donation-form');
        const formData = new FormData(form);
        
        // Validate required fields
        const firstName = formData.get('first_name') || ev.payerName?.split(' ')[0] || '';
        const lastName = formData.get('last_name') || ev.payerName?.split(' ').slice(1).join(' ') || '';
        const email = formData.get('email') || ev.payerEmail || '';
        
        if (!firstName || !lastName || !email || selectedAmount <= 0) {
            ev.complete('fail');
            showError('Please fill in all required fields and select a donation amount.');
            return;
        }
        
        // Append payment details to form data
        formData.append('action', 'process_donation');
        formData.append('payment_method_id', ev.paymentMethod.id);
        formData.append('amount', selectedAmount.toFixed(2));
        formData.append('donation_frequency', isRecurring ? 'monthly' : 'once');
        
        try {
            // Process the payment on backend
            const response = await fetch('/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            console.log('📡 Backend response:', result);
            
            if (!result.success) {
                ev.complete('fail');
                throw new Error(result.data.message || 'Payment failed');
            }
            
            // Handle 3D Secure if required
            if (result.data.requires_action) {
                const { error: confirmError } = await stripe.confirmCardPayment(
                    result.data.payment_intent_client_secret
                );
                
                if (confirmError) {
                    ev.complete('fail');
                    throw new Error(confirmError.message);
                }
            }
            
            // Payment successful!
            ev.complete('success');
            
            // Redirect to thank you page
            window.location.href = '/thank-you-for-your-donation/';
            
        } catch (error) {
            console.error('❌ Payment error:', error);
            ev.complete('fail');
            showError(error.message || 'An error occurred processing your donation.');
        }
    });
    
}

/**
 * Check if required fields are filled
 */
function areRequiredFieldsFilled() {
    const form = document.getElementById('donation-form');
    if (!form) return false;
    
    const firstName = form.querySelector('[name="first_name"]')?.value.trim();
    const lastName = form.querySelector('[name="last_name"]')?.value.trim();
    const email = form.querySelector('[name="email"]')?.value.trim();
    
    return firstName && lastName && email;
}

/**
 * Update Payment Request Button amount
 */
function updatePaymentRequestAmount(newAmount) {
    const requiredFieldsFilled = areRequiredFieldsFilled();
    const shouldEnable = newAmount > 0 && requiredFieldsFilled;
    
    if (paymentRequest) {
        if (newAmount > 0) {
            console.log('🔄 Updating Payment Request amount to:', newAmount);
            paymentRequest.update({
                total: {
                    label: 'Donation to Canadian Taxpayers Federation',
                    amount: Math.round(newAmount * 100),
                },
            });
            if (shouldEnable) {
                enablePaymentRequestButton();
            } else {
                disablePaymentRequestButton();
            }
        } else {
            console.log('⚠️ Amount is 0, disabling Payment Request button');
            disablePaymentRequestButton();
        }
    } else {
        // If payment request isn't available, still enable/disable PayPal
        if (shouldEnable) {
            enablePaymentRequestButton();
        } else {
            disablePaymentRequestButton();
        }
    }
}

/**
 * Disable Payment Request Button (Apple Pay, Google Pay, Link) and PayPal Button
 */
function disablePaymentRequestButton() {
    const prButtonContainer = document.getElementById('payment-request-button');
    if (prButtonContainer) {
        prButtonContainer.style.pointerEvents = 'none';
        prButtonContainer.style.opacity = '0.5';
        console.log('🔒 Payment Request Button disabled');
    }
    
    const paypalContainer = document.getElementById('paypal-button-container');
    if (paypalContainer) {
        paypalContainer.style.pointerEvents = 'none';
        paypalContainer.style.opacity = '0.5';
        console.log('🔒 PayPal Button disabled');
    }
}

/**
 * Enable Payment Request Button (Apple Pay, Google Pay, Link) and PayPal Button
 */
function enablePaymentRequestButton() {
    const prButtonContainer = document.getElementById('payment-request-button');
    if (prButtonContainer) {
        prButtonContainer.style.pointerEvents = 'auto';
        prButtonContainer.style.opacity = '1';
        console.log('🔓 Payment Request Button enabled');
    }
    
    const paypalContainer = document.getElementById('paypal-button-container');
    if (paypalContainer) {
        paypalContainer.style.pointerEvents = 'auto';
        paypalContainer.style.opacity = '1';
        console.log('🔓 PayPal Button enabled');
    }
}

function setupFrequencyToggle() {
    const frequencyRadios = document.querySelectorAll('input[name="frequency_toggle"]');
    const frequencyInput = document.getElementById('donation_frequency');
    const descriptions = document.querySelectorAll('.description');
    
    frequencyRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            descriptions.forEach(d => d.classList.remove('active'));
            
            const frequency = this.value;
            
            if (frequencyInput) frequencyInput.value = frequency;
            isRecurring = (frequency === 'monthly');
            
            const targetDesc = document.getElementById(frequency + '-description');
            if (targetDesc) targetDesc.classList.add('active');
            
            // Update body class for badge visibility
            document.body.classList.remove('frequency-monthly', 'frequency-once');
            document.body.classList.add('frequency-' + frequency);
            
            // Update label colors
            updateToggleLabels(frequency);
            
            // Re-render amount buttons for the selected frequency
            renderAmountButtons(frequency);
            
            // Clear amount selection when switching frequency
            clearAmountSelection();
            updateDonateButton();
        });
    });
}

function updateToggleLabels(frequency) {
    const labels = document.querySelectorAll('.toggle-label');
    labels.forEach((label, index) => {
        if ((frequency === 'monthly' && index === 0) || (frequency === 'once' && index === 1)) {
            label.style.color = '#fff';
        } else {
            label.style.color = '#1a2a3a';
        }
    });
}

function renderAmountButtons(frequency) {
    const amountGrid = document.getElementById('amount-grid');
    if (!amountGrid) {
        console.error('Amount grid not found!');
        return;
    }
    
    const amounts = frequency === 'monthly' ? monthlyAmounts : onetimeAmounts;
    console.log('Rendering amounts for', frequency, ':', amounts);
    
    // Clear existing buttons
    amountGrid.innerHTML = '';
    
    // Generate amount buttons
    amounts.forEach(amount => {
        const isFeatured = amount == 100;
        
        const label = document.createElement('label');
        label.className = 'amount-option' + (isFeatured ? ' amount-featured' : '');
        
        let html = '';
        
        // Add badge for $100 donation (both monthly and one-time)
        if (isFeatured) {
            html += '<span class="amount-badge">Top 10% of donors</span>';
        }
        
        html += `<input type="radio" name="donation_amount" value="${amount}" />`;
        html += `<span class="amount-display">$${amount}</span>`;
        
        label.innerHTML = html;
        amountGrid.appendChild(label);
    });
    
    // Add custom amount option
    const customLabel = document.createElement('label');
    customLabel.className = 'amount-option custom-amount';
    customLabel.innerHTML = `
        <input type="radio" name="donation_amount" value="custom" />
        <span class="amount-display">Other</span>
        <input type="number" name="custom_amount" placeholder="$ Amount" min="1" step="0.01" />
    `;
    amountGrid.appendChild(customLabel);
    
    console.log('Amount buttons rendered, total:', amountGrid.children.length);
    
    // Re-setup amount selection listeners
    setupAmountSelection();
}

function setupAmountSelection() {
    // Handle preset amount buttons
    document.querySelectorAll('input[name="donation_amount"]').forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value !== 'custom') {
                selectedAmount = parseFloat(this.value);
                clearCustomAmountInput();
                updateDonateButton();
                updatePaymentRequestAmount(selectedAmount);
            } else {
                // Focus the textbox when "Other" is selected
                const customAmountInput = document.querySelector('input[name="custom_amount"]');
                if (customAmountInput) {
                    customAmountInput.focus();
                }
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
                updatePaymentRequestAmount(selectedAmount);
            } else {
                selectedAmount = 0;
                updateDonateButton();
                updatePaymentRequestAmount(0);
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
    updatePaymentRequestAmount(0); // Disable payment request button
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
            
            // Check if required fields for PayPal are filled
            const fieldName = this.getAttribute('name');
            if (fieldName === 'first_name' || fieldName === 'last_name' || fieldName === 'email') {
                // Re-check if buttons should be enabled/disabled
                updatePaymentRequestAmount(selectedAmount);
            }
        });
    });
    
    // Form submission handling with Stripe
    form.addEventListener('submit', async function(e) {
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
        
        // Show processing state
        const submitBtn = this.querySelector('.donate-button');
        if (submitBtn) {
            submitBtn.classList.add('processing');
            submitBtn.textContent = 'Processing...';
            submitBtn.disabled = true;
        }
        
        try {
            // Create PaymentMethod with Stripe
            const { paymentMethod, error } = await stripe.createPaymentMethod({
                type: 'card',
                card: cardElement,
                billing_details: {
                    name: `${form.first_name.value} ${form.last_name.value}`,
                    email: form.email.value,
                    phone: form.phone.value,
                    address: {
                        line1: form.address.value,
                        city: form.city.value,
                        state: form.province.value,
                        postal_code: form.postal_code.value,
                        country: 'CA'
                    }
                }
            });
            
            if (error) {
                throw new Error(error.message);
            }
            
            console.log('✅ PaymentMethod created:', paymentMethod.id);
            
            // Prepare form data for backend
            const formData = new FormData(form);
            formData.append('action', 'process_donation');
            formData.append('payment_method_id', paymentMethod.id);
            formData.append('amount', selectedAmount.toFixed(2));
            
            // Debug: Log what we're sending
            console.log('Sending to backend:');
            for (let [key, value] of formData.entries()) {
                console.log(`  ${key}:`, value);
            }
            
            // Send to backend via AJAX
            const response = await fetch('/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.data.message || 'Payment failed');
            }
            
            // Check if payment requires additional action (3D Secure)
            if (result.data.requires_action) {
                const { error: confirmError } = await stripe.confirmCardPayment(
                    result.data.payment_intent_client_secret
                );
                
                if (confirmError) {
                    throw new Error(confirmError.message);
                }
            }
            
            // Payment successful - redirect to thank you page
            window.location.href = '/thank-you-for-your-donation/';
            
        } catch (error) {
            console.error('Payment error:', error);
            showError(error.message || 'An error occurred processing your donation.');
            
            // Reset button state
            if (submitBtn) {
                submitBtn.classList.remove('processing');
                submitBtn.textContent = `Donate $${selectedAmount.toFixed(2)}`;
                submitBtn.disabled = false;
            }
        }
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
    alert('Error: ' + message);
}

function showSuccess(message) {
    // Show success message and redirect or reset form
    alert(message);
    // Could redirect to a thank you page:
    // window.location.href = '/thank-you';
    // Or reload the page to show a fresh form:
    // window.location.reload();
}

// Utility function to trim whitespace and clean currency input
function cleanCurrencyInput(input) {
    if (input.value) {
        input.value = input.value.trim().replace(/[^0-9.]/g, '');
    }
}

/**
 * Initialize PayPal Buttons
 */
function initializePayPal() {
    // Check if PayPal SDK is loaded and client ID is available
    console.log('🔍 PayPal Check:');
    console.log('  - PayPal SDK loaded:', typeof paypal !== 'undefined');
    console.log('  - PayPal Client ID:', window.paypalClientId ? 'Present' : 'Missing');
    console.log('  - Full Client ID:', window.paypalClientId);
    
    if (typeof paypal === 'undefined') {
        console.error('❌ PayPal SDK not loaded. Check if PAYPAL_CLIENT_ID is set.');
        return;
    }
    
    if (!window.paypalClientId) {
        console.error('❌ PayPal Client ID not found in window.paypalClientId');
        return;
    }
    
    console.log('🔄 Initializing PayPal buttons...');
    
    paypal.Buttons({
        style: {
            layout: 'horizontal',
            color: 'gold',
            shape: 'rect',
            label: 'paypal',
            height: 45
        },
        
        // Disable button if no amount selected
        onInit: function(data, actions) {
            // Disable button initially if no amount is selected
            if (selectedAmount <= 0) {
                actions.disable();
            }
            
            // Re-check whenever amount changes (this runs on each render)
            const checkAmount = setInterval(function() {
                if (selectedAmount > 0) {
                    actions.enable();
                } else {
                    actions.disable();
                }
            }, 100);
        },
        
        onClick: function() {
            // Extra validation before PayPal opens
            if (selectedAmount <= 0) {
                showError('Please select a donation amount first.');
                return false;
            }
            
            if (!areRequiredFieldsFilled()) {
                showError('Please fill in your first name, last name, and email before using PayPal.');
                return false;
            }
        },
        
        // Called when button is clicked
        createOrder: function(data, actions) {
            // Validate form fields
            const form = document.getElementById('donation-form');
            const formData = new FormData(form);
            
            const firstName = formData.get('first_name');
            const lastName = formData.get('last_name');
            const email = formData.get('email');
            
            if (!firstName || !lastName || !email || selectedAmount <= 0) {
                showError('Please fill in your name, email, and select a donation amount.');
                return Promise.reject();
            }
            
            console.log('💰 Creating PayPal order for $', selectedAmount);
            
            // Create the order
            return actions.order.create({
                purchase_units: [{
                    description: isRecurring ? 
                        `Monthly Donation - Canadian Taxpayers Federation` : 
                        `One-time Donation - Canadian Taxpayers Federation`,
                    amount: {
                        currency_code: 'CAD',
                        value: selectedAmount.toFixed(2)
                    }
                }],
                application_context: {
                    shipping_preference: 'NO_SHIPPING'
                }
            });
        },
        
        // Called when payment is approved
        onApprove: async function(data, actions) {
            console.log('✅ PayPal payment approved:', data.orderID);
            
            // Capture the order
            const order = await actions.order.capture();
            console.log('📦 PayPal order captured:', order);
            
            // Get form data
            const form = document.getElementById('donation-form');
            const formData = new FormData(form);
            
            // Add PayPal data to form submission
            formData.append('action', 'process_paypal_donation');
            formData.append('paypal_order_id', data.orderID);
            formData.append('payment_source', 'paypal');
            formData.append('amount', selectedAmount.toFixed(2));
            formData.append('donation_frequency', isRecurring ? 'monthly' : 'once');
            
            try {
                // Send to backend
                const response = await fetch('/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                console.log('📡 Backend response:', result);
                
                if (!result.success) {
                    throw new Error(result.data.message || 'Failed to process donation');
                }
                
                // Redirect to thank you page
                window.location.href = '/thank-you-for-your-donation/';
                
            } catch (error) {
                console.error('❌ Error processing PayPal donation:', error);
                showError(error.message || 'An error occurred processing your donation.');
            }
        },
        
        // Called when payment is cancelled
        onCancel: function(data) {
            console.log('⚠️ PayPal payment cancelled');
            showError('Payment was cancelled. Please try again.');
        },
        
        // Called when an error occurs
        onError: function(err) {
            console.error('❌ PayPal error:', err);
            showError('An error occurred with PayPal. Please try another payment method.');
        }
    }).render('#paypal-button-container');
    
    console.log('✅ PayPal buttons initialized');
    
    // Disable button initially if no amount is selected
    if (selectedAmount <= 0) {
        disablePaymentRequestButton();
    }
}

// Initialize PayPal after DOM is loaded and after amount selection is available
document.addEventListener('DOMContentLoaded', function() {
    // Delay PayPal initialization slightly to ensure everything else is ready
    setTimeout(initializePayPal, 500);
});