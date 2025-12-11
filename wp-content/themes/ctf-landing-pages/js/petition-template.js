document.addEventListener('DOMContentLoaded', function() {
// petition-template.js
// Enhanced petition form interactivity, accessibility, and AJAX submission
// Adds lightweight analytics events similar to taxpayer.com petitions

    const form = document.querySelector('.petition-form');
    if (!form) return;

    // --- Analytics helpers ---
    const defaultEventMap = {
        petition_view: { ga: 'petition_view', fb: 'PetitionView', tw: 'ViewContent', ph: 'petition_view' },
        petition_start_typing: { ga: 'petition_start_typing', fb: 'StartTyping', tw: 'StartTyping', ph: 'petition_start_typing' },
        petition_field_focus: { ga: 'petition_field_focus', fb: 'FieldFocus', tw: 'FieldFocus', ph: 'petition_field_focus' },
        petition_submit_click: { ga: 'petition_submit_click', fb: 'SubmitClick', tw: 'InitiateCheckout', ph: 'petition_submit_click' },
        petition_submit_success: { ga: 'petition_submit_success', fb: 'Lead', tw: 'CompleteRegistration', ph: 'petition_submit_success' },
        petition_submit_error: { ga: 'petition_submit_error', fb: 'SubmitError', tw: 'SubmitError', ph: 'petition_submit_error' },
        petition_field_invalid: { ga: 'petition_field_invalid', fb: 'FieldInvalid', tw: 'FieldInvalid', ph: 'petition_field_invalid' },
        petition_feedback_visible: { ga: 'petition_feedback_visible', fb: 'FeedbackVisible', tw: 'FeedbackVisible', ph: 'petition_feedback_visible' }
    };

    const cfg = window.ctfAnalyticsConfig || {
        // Google Ads conversion example: AW-CONVERSION_ID / label
        googleAds: { conversionId: null, label: null },
        facebook: { pixelEnabled: true },
        twitter: { enabled: true },
        posthog: { enabled: true },
        events: defaultEventMap
    };

    function sendGA(eventName, params = {}) {
        try {
            if (typeof window.gtag === 'function') {
                window.gtag('event', eventName, params);
            } else {
                window.dataLayer = window.dataLayer || [];
                window.dataLayer.push({ event: eventName, ...params });
            }
        } catch (_) {}
    }

    function sendGoogleAdsConversion(params = {}) {
        try {
            if (typeof window.gtag === 'function' && cfg.googleAds?.conversionId && cfg.googleAds?.label) {
                window.gtag('event', 'conversion', {
                    'send_to': `AW-${cfg.googleAds.conversionId}/${cfg.googleAds.label}`,
                    ...params
                });
            }
        } catch (_) {}
    }

    function sendFacebook(eventName, params = {}) {
        try {
            if (typeof window.fbq === 'function' && cfg.facebook?.pixelEnabled !== false) {
                window.fbq('trackCustom', eventName, params);
            }
        } catch (_) {}
    }

    function sendTwitter(eventName, params = {}) {
        try {
            // Twitter Pixel via ttq
            if (window.ttq && typeof window.ttq.track === 'function' && cfg.twitter?.enabled !== false) {
                window.ttq.track(eventName, params);
            }
        } catch (_) {}
    }

    function sendPosthog(eventName, params = {}) {
        try {
            if (window.posthog && typeof window.posthog.capture === 'function' && cfg.posthog?.enabled !== false) {
                window.posthog.capture(eventName, params);
            }
        } catch (_) {}
    }

    function sendAll(eventName, params = {}) {
        const map = (cfg.events && cfg.events[eventName]) || defaultEventMap[eventName] ||
                    { ga: eventName, fb: eventName, tw: eventName, ph: eventName };
        sendGA(map.ga, params);
        sendFacebook(map.fb, params);
        sendTwitter(map.tw, params);
        sendPosthog(map.ph, params);
    }

    // Page/petition view
    sendAll('petition_view', {
        petition_title: document.querySelector('.petition-title')?.textContent || document.title,
        page_path: window.location.pathname
    });

    // Focus first input for accessibility
    const firstInput = form.querySelector('input:not([type="hidden"]):not([type="checkbox"])');
    if (firstInput) firstInput.focus();


    // Function to get fresh nonce for cached pages
    function refreshNonce() {
        console.log('CTF: Refreshing nonce for cached page compatibility');
        
        fetch(wp_petition.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=ctf_get_petition_nonce'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.nonce) {
                const nonceField = form.querySelector('input[name="ctf_petition_nonce"]');
                if (nonceField) {
                    nonceField.value = data.data.nonce;
                    console.log('CTF: Updated nonce for cache compatibility');
                }
            }
        })
        .catch(error => {
            console.log('CTF: Could not refresh nonce, using cached value');
        });
    }

    // Refresh nonce when page loads (helps with caching)
    refreshNonce();

    // AJAX form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const button = form.querySelector('button[type="submit"]');
        const messages = form.querySelector('.form-messages');
        
        // Clear any existing messages
        if (messages) {
            messages.style.display = 'none';
            messages.classList.remove('success', 'error');
        }
        
        // Disable submit button and show loading state
        if (button) {
            button.disabled = true;
            button.textContent = 'Submitting...';
        }
        
        console.log('CTF: Submitting petition form');
        sendAll('petition_submit_click', {
            petition_title: document.querySelector('.petition-title')?.textContent || document.title
        });
        // Optional Google Ads conversion on submit click (top-of-funnel)
        sendGoogleAdsConversion({
            value: 0.0,
            currency: 'CAD'
        });
        
        // Prepare form data
        const formData = new FormData(form);
        
        // Convert FormData to URLSearchParams for easy sending
        const params = new URLSearchParams();
        for (let [key, value] of formData.entries()) {
            params.append(key, value);
        }
        
        // Make AJAX request
        fetch('/wp-admin/admin-ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: params.toString()
        })
        .then(response => response.json())
        .then(data => {
            console.log('CTF: AJAX response:', data);
            
            if (messages) {
                if (data.success) {
                    // Use the structured helpers with the site's event names
                    (function firePostSubmitEvents(payload){
                        try {
                            const product_name = document.querySelector('.petition-title')?.textContent || document.title;
                            const email = document.getElementById('inputEmail')?.value || '';

                            // Twitter (legacy twq syntax from site)
                            if (typeof window.twq === 'function') {
                                window.twq('event', 'tw-oodh1-oodhk', { email_address: email });
                            }

                            // PostHog exact names
                            sendPosthog('email capture', {
                                new_petition_sign: !!payload.newPetition,
                                new_au: !!payload.newAU,
                                new_email: !payload.existing
                            });

                            // Base Google Ads conversion (configurable if provided)
                            if (cfg.googleAds?.conversionId && cfg.googleAds?.label) {
                                sendGoogleAdsConversion({});
                            } else if (typeof window.gtag === 'function') {
                                // Fallback to site example ID/label
                                window.gtag('event', 'conversion', { 'send_to': 'AW-1038171580/O4V6CJmCy9sZELz7hO8D' });
                            }

                            // Facebook Lead and GA email_capture
                            sendFacebook('Lead', { content_name: product_name });
                            sendGA('email_capture', {});

                            // New petition sign
                            if (payload.newPetition) {
                                sendFacebook('new_petition_sign', { content_name: product_name }); // trackCustom
                                sendGA('new_petition_sign', {});
                                if (cfg.googleAds?.conversionId) {
                                    // Use configured label if supplied via cfg.googleAds.petitionLabel
                                    const label = cfg.googleAds.petitionLabel || 'vRWVCLW0yrAbELz7hO8D';
                                    if (typeof window.gtag === 'function') {
                                        window.gtag('event', 'conversion', { 'send_to': `AW-${cfg.googleAds.conversionId}/${label}`, 'value': 1.0, 'currency': 'CAD' });
                                    }
                                } else if (typeof window.gtag === 'function') {
                                    window.gtag('event', 'conversion', { 'send_to': 'AW-1038171580/vRWVCLW0yrAbELz7hO8D', 'value': 1.0, 'currency': 'CAD' });
                                }
                            }

                            // New AU (join group)
                            if (payload.newAU) {
                                sendFacebook('CompleteRegistration', { content_name: product_name });
                                sendGA('join_group', {});
                            }

                            // New email
                            if (!payload.existing) {
                                sendFacebook('new_email', { content_name: product_name }); // trackCustom
                                sendGA('sign_up', {});
                                if (cfg.googleAds?.conversionId) {
                                    const label = cfg.googleAds.emailLabel || '9nD6CKjZ-dsZELz7hO8D';
                                    if (typeof window.gtag === 'function') {
                                        window.gtag('event', 'conversion', { 'send_to': `AW-${cfg.googleAds.conversionId}/${label}` });
                                    }
                                } else if (typeof window.gtag === 'function') {
                                    window.gtag('event', 'conversion', { 'send_to': 'AW-1038171580/9nD6CKjZ-dsZELz7hO8D' });
                                }
                                if (typeof window.custom_event === 'function') {
                                    window.custom_event('new_email');
                                }
                            }

                            // SMS permission
                            const isSMSChecked = document.querySelector('input[name="sms"]')?.checked || false;
                            if (isSMSChecked) {
                                sendPosthog('sms permission', {
                                    new_petition_sign: !!payload.newPetition,
                                    new_au: !!payload.newAU,
                                    new_email: !payload.existing
                                });
                                console.log('sms permission');
                            }
                        } catch (e) {
                            console.log('error');
                            console.log(e);
                        }
                    })(data.data || {});

                    sendAll('petition_submit_success', {
                        petition_title: document.querySelector('.petition-title')?.textContent || document.title
                    });
                    // Confirmed conversion for Ads on success
                    sendGoogleAdsConversion({ value: 1.0, currency: 'CAD' });
                    messages.classList.remove('error');
                    messages.classList.add('success');
                    messages.innerHTML = '<p>' + data.data.message + '</p>';
                    messages.style.display = 'block';
                    
                    // Reset form on success
                    form.reset();
                    
                    // Scroll to success message
                    messages.scrollIntoView({ behavior: 'smooth', block: 'center' });

                    // Redirect to donation page with source page id
                    try {
                        const sourceId = (window.wp_petition && window.wp_petition.page_id)
                            || document.body.getAttribute('data-page-id')
                            || document.querySelector('input[name="page_id"]')?.value
                            || '';
                        const petitionTitle = (window.wp_petition && window.wp_petition.title)
                            || document.querySelector('input[name="petition_title"]')?.value
                            || document.querySelector('.petition-title')?.textContent
                            || document.title;
                        const params = new URLSearchParams();
                        if (sourceId) params.set('source', sourceId);
                        if (petitionTitle) params.set('petition_title', petitionTitle);
                        const query = params.toString();
                        const dest = query ? '/donation/donate/?' + query : '/donation/donate/';
                        // small delay to allow analytics to fire
                        setTimeout(function(){ window.location.href = dest; }, 250);
                    } catch (_) {}
                } else {
                    sendAll('petition_submit_error', {
                        petition_title: document.querySelector('.petition-title')?.textContent || document.title,
                        error_message: (data?.data?.message) || 'Unknown error'
                    });
                    let errorMessage = '<p>' + data.data.message + '</p>';
                    if (data.data.errors && data.data.errors.length > 0) {
                        errorMessage += '<ul>';
                        data.data.errors.forEach(function(error) {
                            errorMessage += '<li>' + error + '</li>';
                        });
                        errorMessage += '</ul>';
                    }
                    
                    messages.classList.remove('success');
                    messages.classList.add('error');
                    messages.innerHTML = errorMessage;
                    messages.style.display = 'block';
                    
                    // Scroll to error message
                    messages.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        })
        .catch(error => {
            console.error('CTF: AJAX error:', error);
            sendAll('petition_submit_error', {
                petition_title: document.querySelector('.petition-title')?.textContent || document.title,
                error_message: error && error.message ? error.message : 'Network error'
            });
            if (messages) {
                messages.classList.remove('success');
                messages.classList.add('error');
                messages.innerHTML = '<p>An error occurred. Please try again. (' + error.message + ')</p>';
                messages.style.display = 'block';
                
                // Scroll to error message
                messages.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        })
        .finally(() => {
            // Re-enable submit button
            if (button) {
                button.disabled = false;
                button.textContent = 'Sign the petition';
            }
        });
    });

    // Simple client-side validation feedback
    form.addEventListener('invalid', function(e) {
        e.preventDefault();
        e.target.classList.add('input-error');
        sendAll('petition_field_invalid', {
            field_name: e.target.name || 'unknown'
        });
        // Show error message below the field
        let msg = e.target.parentNode.querySelector('.input-error-msg');
        if (!msg) {
            msg = document.createElement('div');
            msg.className = 'input-error-msg';
            msg.style.color = '#a12a2a';
            msg.style.fontSize = '0.97em';
            msg.style.marginTop = '0.2em';
            msg.textContent = 'This field is required.';
            e.target.parentNode.appendChild(msg);
        }
        e.target.setAttribute('aria-invalid', 'true');
    }, true);

    form.addEventListener('input', function(e) {
        if (e.target.classList.contains('input-error')) {
            e.target.classList.remove('input-error');
            e.target.removeAttribute('aria-invalid');
            let msg = e.target.parentNode.querySelector('.input-error-msg');
            if (msg) msg.remove();
        }
    });

    // Keyboard navigation: Enter on last field submits
    const inputs = Array.from(form.querySelectorAll('input'));
    if (inputs.length) {
        inputs.forEach((input, idx) => {
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && idx < inputs.length - 1 && input.type !== 'checkbox') {
                    e.preventDefault();
                    inputs[idx + 1].focus();
                }
            });
            input.addEventListener('focus', function() {
                sendAll('petition_field_focus', {
                    field_name: input.name || 'unknown'
                });
            });
        });
    }

    // Add focus style for accessibility
    const style = document.createElement('style');
    style.textContent = '.input-error { border: 1.5px solid #a12a2a !important; background: #fff6f6 !important; }';
    document.head.appendChild(style);

    // Scroll to feedback message on error or success
    const feedback = document.querySelector('.petition-success, .petition-error');
    if (feedback) {
        feedback.scrollIntoView({ behavior: 'smooth', block: 'center' });
        sendAll('petition_feedback_visible', {
            type: feedback.classList.contains('petition-success') ? 'success' : 'error'
        });
    }
});
