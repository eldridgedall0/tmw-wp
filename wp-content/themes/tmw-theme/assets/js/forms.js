/**
 * Form Handling
 *
 * Validation, password strength, AJAX submission
 *
 * @package flavor-starter-flavor
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', initForms);

    function initForms() {
        initPasswordToggles();
        initPasswordStrength();
        initPasswordMatch();
        initFormSubmissions();
    }

    /**
     * Password visibility toggles
     */
    function initPasswordToggles() {
        document.querySelectorAll('.tmw-password-toggle').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const input = this.previousElementSibling;
                if (!input || input.tagName !== 'INPUT') return;
                
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    if (icon) {
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    }
                } else {
                    input.type = 'password';
                    if (icon) {
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                }
            });
        });
    }

    /**
     * Password strength meter
     */
    function initPasswordStrength() {
        const passwordInput = document.querySelector('#password, #new_password');
        const strengthBar = document.querySelector('.tmw-password-strength-fill');
        const strengthText = document.querySelector('.tmw-password-strength-label');
        
        if (!passwordInput || !strengthBar) return;

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const strength = calculatePasswordStrength(password);
            
            strengthBar.className = 'tmw-password-strength-fill';
            
            if (password.length === 0) {
                strengthBar.style.width = '0';
                if (strengthText) strengthText.textContent = '';
            } else if (strength < 2) {
                strengthBar.classList.add('weak');
                if (strengthText) strengthText.textContent = getTranslation('passwordWeak', 'Weak');
            } else if (strength < 4) {
                strengthBar.classList.add('medium');
                if (strengthText) strengthText.textContent = getTranslation('passwordMedium', 'Medium');
            } else {
                strengthBar.classList.add('strong');
                if (strengthText) strengthText.textContent = getTranslation('passwordStrong', 'Strong');
            }
        });
    }

    /**
     * Calculate password strength score
     */
    function calculatePasswordStrength(password) {
        let score = 0;
        
        if (password.length >= 8) score++;
        if (password.length >= 12) score++;
        if (/[a-z]/.test(password)) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[^a-zA-Z0-9]/.test(password)) score++;
        
        return score;
    }

    /**
     * Password match indicator
     */
    function initPasswordMatch() {
        const password = document.querySelector('#password, #new_password');
        const confirmPassword = document.querySelector('#password_confirm, #confirm_password');
        const matchIndicator = document.querySelector('.tmw-password-match');
        
        if (!password || !confirmPassword) return;

        function checkMatch() {
            const passwordVal = password.value;
            const confirmVal = confirmPassword.value;
            
            if (!matchIndicator) return;
            
            if (confirmVal.length === 0) {
                matchIndicator.style.display = 'none';
                return;
            }
            
            matchIndicator.style.display = 'flex';
            
            if (passwordVal === confirmVal) {
                matchIndicator.className = 'tmw-password-match match';
                matchIndicator.innerHTML = '<i class="fas fa-check"></i> ' + 
                    getTranslation('passwordMatch', 'Passwords match');
            } else {
                matchIndicator.className = 'tmw-password-match no-match';
                matchIndicator.innerHTML = '<i class="fas fa-times"></i> ' + 
                    getTranslation('passwordNoMatch', 'Passwords do not match');
            }
        }

        password.addEventListener('input', checkMatch);
        confirmPassword.addEventListener('input', checkMatch);
    }

    /**
     * AJAX form submissions
     */
    function initFormSubmissions() {
        // Login form
        const loginForm = document.querySelector('#tmw-login-form');
        if (loginForm) {
            loginForm.addEventListener('submit', function(e) {
                e.preventDefault();
                handleFormSubmit(this, 'tmw_login');
            });
        }

        // Register form
        const registerForm = document.querySelector('#tmw-register-form');
        if (registerForm) {
            registerForm.addEventListener('submit', function(e) {
                e.preventDefault();
                handleFormSubmit(this, 'tmw_register');
            });
        }

        // Forgot password form
        const forgotForm = document.querySelector('#tmw-forgot-form');
        if (forgotForm) {
            forgotForm.addEventListener('submit', function(e) {
                e.preventDefault();
                handleFormSubmit(this, 'tmw_forgot_password');
            });
        }

        // Reset password form
        const resetForm = document.querySelector('#tmw-reset-form');
        if (resetForm) {
            resetForm.addEventListener('submit', function(e) {
                e.preventDefault();
                handleFormSubmit(this, 'tmw_reset_password');
            });
        }

        // Profile form
        const profileForm = document.querySelector('#tmw-profile-form');
        if (profileForm) {
            profileForm.addEventListener('submit', function(e) {
                e.preventDefault();
                handleFormSubmit(this, 'tmw_update_profile');
            });
        }

        // Change password form
        const changePasswordForm = document.querySelector('#tmw-change-password-form');
        if (changePasswordForm) {
            changePasswordForm.addEventListener('submit', function(e) {
                e.preventDefault();
                handleFormSubmit(this, 'tmw_change_password');
            });
        }
    }

    /**
     * Handle form submission
     */
    function handleFormSubmit(form, action) {
        const submitBtn = form.querySelector('[type="submit"]');
        const formData = new FormData(form);
        
        // Clear previous errors
        clearFormErrors(form);
        
        // Show loading state
        if (submitBtn) {
            submitBtn.classList.add('tmw-btn-loading');
            submitBtn.disabled = true;
        }
        
        // Add action and nonce
        formData.append('action', action);
        formData.append('nonce', typeof tmwData !== 'undefined' ? tmwData.nonce : '');

        // --- MOBILE APP SUPPORT ---
        // If this page was loaded with ?mobile=1, forward the flag so the
        // AJAX handler can return the correct login_success redirect URL.
        // The hidden input injected by mobile-login.php already handles this,
        // but we add it here too as belt-and-suspenders.
        if (typeof tmwData !== 'undefined' && tmwData.isMobile) {
            if (!formData.has('mobile')) {
                formData.append('mobile', '1');
            }
        }
        // --------------------------
        
        fetch(typeof tmwData !== 'undefined' ? tmwData.ajaxUrl : '/wp-admin/admin-ajax.php', {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(result) {
            if (result.success) {
                showFormMessage(form, result.data.message, 'success');
                
                // Redirect if provided
                if (result.data.redirect) {
                    // For mobile app: redirect immediately — the WebView intercepts it.
                    // For web: 1-second delay so the success message is readable.
                    var delay = (result.data.mobile) ? 0 : 1000;
                    setTimeout(function() {
                        window.location.href = result.data.redirect;
                    }, delay);
                }
            } else {
                showFormMessage(form, result.data.message, 'error');
            }
        })
        .catch(function(error) {
            console.error('Form submission error:', error);
            showFormMessage(form, 'An error occurred. Please try again.', 'error');
        })
        .finally(function() {
            if (submitBtn) {
                submitBtn.classList.remove('tmw-btn-loading');
                submitBtn.disabled = false;
            }
        });
    }

    /**
     * Show form message
     */
    function showFormMessage(form, message, type) {
        // Remove existing message
        const existing = form.querySelector('.tmw-form-message');
        if (existing) existing.remove();
        
        // Create new message
        const messageEl = document.createElement('div');
        messageEl.className = 'tmw-form-message tmw-alert tmw-alert-' + type;
        messageEl.innerHTML = '<span class="tmw-alert-message">' + message + '</span>';
        
        // Insert at top of form
        form.insertBefore(messageEl, form.firstChild);
        
        // Scroll to message
        messageEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    /**
     * Clear form errors
     */
    function clearFormErrors(form) {
        const messages = form.querySelectorAll('.tmw-form-message');
        messages.forEach(function(msg) {
            msg.remove();
        });
    }

    /**
     * Get translation string
     */
    function getTranslation(key, fallback) {
        if (typeof tmwData !== 'undefined' && tmwData.i18n && tmwData.i18n[key]) {
            return tmwData.i18n[key];
        }
        return fallback;
    }

    // Expose for external use
    window.TMW = window.TMW || {};
    window.TMW.forms = {
        submit: handleFormSubmit,
        showMessage: showFormMessage,
        clearErrors: clearFormErrors
    };

})();
