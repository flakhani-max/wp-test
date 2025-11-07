/**
 * Donation Template JavaScript
 * Handles all donation form functionality including amount selection,
 * frequency toggle, form validation, and card validation
 */

// Global variables
let selectedAmount = 0;
let isRecurring = false;
let monthlyAmounts = [];
let onetimeAmounts = [];

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
        setupFormValidation();
        setupCardValidation();
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

