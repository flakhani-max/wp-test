document.addEventListener('DOMContentLoaded', function() {
// petition-template.js
// Enhanced petition form interactivity, accessibility, and AJAX submission

    const form = document.querySelector('.petition-form');
    if (!form) return;

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
                    messages.classList.remove('error');
                    messages.classList.add('success');
                    messages.innerHTML = '<p>' + data.data.message + '</p>';
                    messages.style.display = 'block';
                    
                    // Reset form on success
                    form.reset();
                    
                    // Scroll to success message
                    messages.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
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
    }
});
