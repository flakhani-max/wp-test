document.addEventListener('DOMContentLoaded', function() {
// petition-template.js
// Enhanced petition form interactivity and accessibility

    const form = document.querySelector('.petition-form');
    if (!form) return;

    // Focus first input for accessibility
    const firstInput = form.querySelector('input:not([type="hidden"]):not([type="checkbox"])');
    if (firstInput) firstInput.focus();

    // Show a loading state on submit
    form.addEventListener('submit', function(e) {
        let btn = form.querySelector('button[type="submit"]');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Submitting...';
        }
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
