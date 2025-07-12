/**
 * Authentication JavaScript
 * Handles form validation, submission, and user interactions for auth pages
 */

const AuthManager = {
    // Initialize authentication functionality
    init() {
        this.initFormValidation();
        this.initTimezoneDetection();
        this.initPasswordToggle();
        this.initFormSubmission();
        this.initCaptcha();
    },

    // Form validation
    initFormValidation() {
        const forms = document.querySelectorAll('.auth-form');
        forms.forEach(form => {
            const inputs = form.querySelectorAll('input[required]');
            
            inputs.forEach(input => {
                // Real-time validation
                input.addEventListener('blur', () => {
                    this.validateField(input);
                });
                
                input.addEventListener('input', () => {
                    this.clearFieldError(input);
                });
            });
            
            // Form submission validation
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                }
            });
        });
    },

    // Timezone detection for registration
    initTimezoneDetection() {
        const timezoneInput = document.getElementById('timezone');
        if (timezoneInput) {
            try {
                const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                timezoneInput.value = timezone;
                console.log('Detected timezone:', timezone);
            } catch (error) {
                console.warn('Could not detect timezone, using UTC:', error);
                timezoneInput.value = 'UTC';
            }
        }
    },

    // Password visibility toggle
    initPasswordToggle() {
        const passwordInputs = document.querySelectorAll('input[type="password"]');
        passwordInputs.forEach(input => {
            const toggleButton = this.createPasswordToggle(input);
            if (toggleButton) {
                input.parentNode.style.position = 'relative';
                input.parentNode.appendChild(toggleButton);
            }
        });
    },

    createPasswordToggle(input) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600';
        button.innerHTML = '<i class="fas fa-eye"></i>';
        
        button.addEventListener('click', () => {
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            button.innerHTML = isPassword ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
        });
        
        return button;
    },

    // Form submission handling
    initFormSubmission() {
        const forms = document.querySelectorAll('.auth-form');
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                const submitButton = form.querySelector('button[type="submit"]');
                if (submitButton) {
                    this.setSubmitLoading(submitButton, true);
                    
                    // Add a timeout to prevent indefinite loading
                    setTimeout(() => {
                        this.setSubmitLoading(submitButton, false);
                    }, 10000);
                }
            });
        });
    },

    // Field validation
    validateField(input) {
        const value = input.value.trim();
        let isValid = true;
        let errorMessage = '';

        // Required field check
        if (input.required && !value) {
            isValid = false;
            errorMessage = 'This field is required';
        }
        // Email validation
        else if (input.type === 'email' && value && !this.isValidEmail(value)) {
            isValid = false;
            errorMessage = 'Please enter a valid email address';
        }
        // Password validation
        else if (input.type === 'password' && input.name === 'password' && value && value.length < 8) {
            isValid = false;
            errorMessage = 'Password must be at least 8 characters long';
        }
        // Confirm password validation
        else if (input.name === 'confirm_password' && value) {
            const passwordInput = document.querySelector('input[name="password"]');
            if (passwordInput && value !== passwordInput.value) {
                isValid = false;
                errorMessage = 'Passwords do not match';
            }
        }

        if (isValid) {
            this.clearFieldError(input);
            input.classList.add('auth-input-success');
        } else {
            this.showFieldError(input, errorMessage);
        }

        return isValid;
    },

    // Form validation
    validateForm(form) {
        const inputs = form.querySelectorAll('input[required]');
        let isValid = true;

        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });

        return isValid;
    },

    // Show field error
    showFieldError(input, message) {
        this.clearFieldError(input);
        
        input.classList.add('auth-input-error');
        input.classList.remove('auth-input-success');
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'auth-field-error text-red-600 text-sm mt-1';
        errorDiv.textContent = message;
        
        // Insert after the input or its container
        const container = input.closest('.auth-input-group') || input.parentNode;
        container.appendChild(errorDiv);
    },

    // Clear field error
    clearFieldError(input) {
        input.classList.remove('auth-input-error', 'auth-input-success');
        
        const container = input.closest('.auth-input-group') || input.parentNode;
        const errorDiv = container.querySelector('.auth-field-error');
        if (errorDiv) {
            errorDiv.remove();
        }
    },

    // Submit button loading state
    setSubmitLoading(button, isLoading) {
        if (isLoading) {
            button.disabled = true;
            button.classList.add('auth-loading');
            button.dataset.originalText = button.textContent;
        } else {
            button.disabled = false;
            button.classList.remove('auth-loading');
            if (button.dataset.originalText) {
                button.textContent = button.dataset.originalText;
                delete button.dataset.originalText;
            }
        }
    },

    // Utility functions
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },

    // Show toast message
    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 max-w-sm p-4 rounded-md shadow-lg z-50 transform translate-x-full transition-transform duration-300 ${
            type === 'success' ? 'bg-green-50 border border-green-200 text-green-700' :
            type === 'error' ? 'bg-red-50 border border-red-200 text-red-700' :
            'bg-blue-50 border border-blue-200 text-blue-700'
        }`;
        
        toast.innerHTML = `
            <div class="flex items-center">
                <i class="fas ${
                    type === 'success' ? 'fa-check-circle' :
                    type === 'error' ? 'fa-exclamation-triangle' :
                    'fa-info-circle'
                } mr-2"></i>
                <span class="text-sm">${message}</span>
                <button class="ml-auto text-sm opacity-70 hover:opacity-100" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        document.body.appendChild(toast);
        
        // Animate in
        setTimeout(() => {
            toast.classList.remove('translate-x-full');
        }, 100);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            toast.classList.add('translate-x-full');
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.remove();
                }
            }, 300);
        }, 5000);
    },

    // Handle form errors from server
    handleServerError(errorMessage) {
        this.showToast(errorMessage, 'error');
    },

    // Handle form success from server
    handleServerSuccess(successMessage) {
        this.showToast(successMessage, 'success');
    },

    // Captcha functionality
    initCaptcha() {
        const captchaInput = document.getElementById('captcha_answer');
        const captchaExpected = document.querySelector('input[name="captcha_expected"]');
        
        if (!captchaInput || !captchaExpected) return;
        
        // Real-time validation for captcha
        captchaInput.addEventListener('input', () => {
            const userAnswer = parseInt(captchaInput.value);
            const expectedAnswer = parseInt(captchaExpected.value);
            
            if (captchaInput.value && !isNaN(userAnswer)) {
                if (userAnswer === expectedAnswer) {
                    captchaInput.classList.remove('border-red-500');
                    captchaInput.classList.add('border-green-500');
                    this.clearFieldError(captchaInput);
                } else {
                    captchaInput.classList.remove('border-green-500');
                    captchaInput.classList.add('border-red-500');
                }
            } else {
                captchaInput.classList.remove('border-red-500', 'border-green-500');
            }
        });
        
        // Clear validation on focus
        captchaInput.addEventListener('focus', () => {
            captchaInput.classList.remove('border-red-500', 'border-green-500');
            this.clearFieldError(captchaInput);
        });
    }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    AuthManager.init();
});

// Export for use in other files
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AuthManager;
}