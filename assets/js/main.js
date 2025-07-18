/**
 * MorningNewsletter - Main JavaScript
 * Contains all interactive functionality for the website
 */

// Global utilities
const MorningNewsletter = {
    // Initialize all functionality
    init() {
        this.initFAQ();
        this.initNavigation();
        this.initTimezone();
        this.initNewsletterSubscription();
        this.initSmoothScrolling();
    },

    // FAQ functionality
    initFAQ() {
        // FAQ toggle function is defined globally for onclick handlers
        window.toggleFAQ = function(button) {
            const answer = button.nextElementSibling;
            const icon = button.querySelector('i');
            
            if (answer.style.display === 'none' || answer.style.display === '') {
                // Show answer
                answer.style.display = 'block';
                icon.style.transform = 'rotate(180deg)';
                // Keep the same icon class, just rotate it
            } else {
                // Hide answer
                answer.style.display = 'none';
                icon.style.transform = 'rotate(0deg)';
            }
        };
    },

    // Navigation functionality
    initNavigation() {
        // Add scroll effect to navigation
        let lastScrollTop = 0;
        const nav = document.querySelector('nav');
        
        if (nav) {
            window.addEventListener('scroll', () => {
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                
                if (scrollTop > 100) {
                    nav.classList.add('nav-scrolled');
                } else {
                    nav.classList.remove('nav-scrolled');
                }
                
                lastScrollTop = scrollTop;
            });
        }

        // Initialize user dropdown functionality (only if Dashboard is not available)
        if (typeof Dashboard === 'undefined') {
            this.initUserDropdown();
        }
    },

    // User dropdown functionality
    initUserDropdown() {
        const dropdown = document.getElementById('dropdown-menu');
        const button = document.getElementById('user-menu-button');
        
        if (!dropdown || !button) {
            return; // No dropdown on this page
        }
        
        // Toggle function
        const toggleDropdown = () => {
            if (dropdown.classList.contains('hidden')) {
                // Close any other open dropdowns first
                document.querySelectorAll('[id*="dropdown-"]').forEach(el => {
                    if (el !== dropdown && !el.classList.contains('hidden')) {
                        el.classList.add('hidden');
                    }
                });
                
                dropdown.classList.remove('hidden');
                button.setAttribute('aria-expanded', 'true');
            } else {
                dropdown.classList.add('hidden');
                button.setAttribute('aria-expanded', 'false');
            }
        };
        
        // Add click event listener to button
        button.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            toggleDropdown();
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (!button.contains(event.target) && !dropdown.contains(event.target)) {
                if (!dropdown.classList.contains('hidden')) {
                    dropdown.classList.add('hidden');
                    button.setAttribute('aria-expanded', 'false');
                }
            }
        });
        
        // Close dropdown when pressing escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                if (!dropdown.classList.contains('hidden')) {
                    dropdown.classList.add('hidden');
                    button.setAttribute('aria-expanded', 'false');
                }
            }
        });
        
        // Prevent dropdown from closing when clicking inside it
        dropdown.addEventListener('click', function(event) {
            event.stopPropagation();
        });
    },

    // Timezone detection for registration
    initTimezone() {
        document.addEventListener('DOMContentLoaded', () => {
            const timezoneInput = document.getElementById('timezone');
            if (timezoneInput) {
                try {
                    const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                    timezoneInput.value = timezone;
                } catch (error) {
                    console.warn('Could not detect timezone, using UTC:', error);
                    timezoneInput.value = 'UTC';
                }
            }
        });
    },

    // Newsletter subscription functionality
    initNewsletterSubscription() {
        // Newsletter subscription function is defined globally for footer
        window.subscribeToNewsletter = function(event) {
            event.preventDefault();
            
            const emailInput = document.getElementById('newsletter-email');
            const email = emailInput.value.trim();
            const button = event.target.querySelector('button[type="submit"]');
            const originalText = button.textContent;
            
            // Basic email validation
            if (!email || !MorningNewsletter.isValidEmail(email)) {
                MorningNewsletter.showAlert('Please enter a valid email address', 'error');
                return;
            }
            
            // Show loading state
            button.textContent = 'Subscribing...';
            button.disabled = true;
            button.classList.add('loading');
            
            // TODO: Integrate with newsletter provider (e.g., Mailchimp, ConvertKit, etc.)
            // For now, just simulate the subscription
            setTimeout(() => {
                // Show success message
                MorningNewsletter.showAlert('Thank you for subscribing! You\'ll receive updates about new features and tips.', 'success');
                
                // Reset form
                emailInput.value = '';
                button.textContent = originalText;
                button.disabled = false;
                button.classList.remove('loading');
            }, 1000);
        };
    },

    // Smooth scrolling for anchor links
    initSmoothScrolling() {
        const links = document.querySelectorAll('a[href^="#"]');
        links.forEach(link => {
            link.addEventListener('click', (e) => {
                const href = link.getAttribute('href');
                if (href === '#') return;
                
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    },

    // Utility functions
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },

    showAlert(message, type = 'info') {
        // Create alert element
        const alertDiv = document.createElement('div');
        alertDiv.className = `fixed top-4 right-4 max-w-sm p-4 rounded-md shadow-lg z-50 ${
            type === 'success' ? 'bg-green-50 border border-green-200 text-green-700' :
            type === 'error' ? 'bg-red-50 border border-red-200 text-red-700' :
            'bg-purple-50 border border-purple-200 text-purple-700'
        }`;
        
        alertDiv.innerHTML = `
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
        
        document.body.appendChild(alertDiv);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentElement) {
                alertDiv.remove();
            }
        }, 5000);
    },

    // Form validation helpers
    validateForm(formElement) {
        const inputs = formElement.querySelectorAll('input[required]');
        let isValid = true;
        
        inputs.forEach(input => {
            if (!input.value.trim()) {
                this.showFieldError(input, 'This field is required');
                isValid = false;
            } else if (input.type === 'email' && !this.isValidEmail(input.value)) {
                this.showFieldError(input, 'Please enter a valid email address');
                isValid = false;
            } else {
                this.clearFieldError(input);
            }
        });
        
        return isValid;
    },

    showFieldError(input, message) {
        this.clearFieldError(input);
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'text-red-600 text-sm mt-1 field-error';
        errorDiv.textContent = message;
        
        input.parentNode.appendChild(errorDiv);
        input.classList.add('border-red-500');
    },

    clearFieldError(input) {
        const errorDiv = input.parentNode.querySelector('.field-error');
        if (errorDiv) {
            errorDiv.remove();
        }
        input.classList.remove('border-red-500');
    },

    // Loading state management
    setLoadingState(element, isLoading, originalText = '') {
        if (isLoading) {
            element.disabled = true;
            element.classList.add('loading');
            if (originalText) {
                element.dataset.originalText = element.textContent;
                element.textContent = originalText;
            }
        } else {
            element.disabled = false;
            element.classList.remove('loading');
            if (element.dataset.originalText) {
                element.textContent = element.dataset.originalText;
                delete element.dataset.originalText;
            }
        }
    }
};

// Stripe subscription functionality (for landing page)
async function subscribeToPlan(plan) {
    // Check if user is logged in (PHP variable will be injected)
    if (typeof isLoggedIn !== 'undefined' && !isLoggedIn) {
        // Store the selected plan in session storage for after login
        sessionStorage.setItem('selectedPlan', plan);
        window.location.href = '/auth/login.php';
        return;
    }

    try {
        // Show loading state
        const button = event.target;
        const originalText = button.textContent;
        MorningNewsletter.setLoadingState(button, true, 'Loading...');

        // Create Stripe checkout session
        const response = await fetch('/api/fixed-checkout.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ plan: plan })
        });

        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`HTTP ${response.status}: ${errorText}`);
        }

        const data = await response.json();

        if (!data.checkout_url) {
            throw new Error('No checkout URL received');
        }

        // Redirect to Stripe Checkout
        window.location.href = data.checkout_url;

    } catch (error) {
        // Restore button state
        if (typeof event !== 'undefined' && event.target) {
            MorningNewsletter.setLoadingState(event.target, false);
        }
        
        console.error('Error creating checkout session:', error);
        MorningNewsletter.showAlert('Error: ' + error.message, 'error');
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    MorningNewsletter.init();
});

// Export for use in other files
if (typeof module !== 'undefined' && module.exports) {
    module.exports = MorningNewsletter;
}