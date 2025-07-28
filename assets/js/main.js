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
        
        // Toggle function with animation
        const toggleDropdown = () => {
            if (dropdown.classList.contains('hidden')) {
                // Close any other open dropdowns first
                document.querySelectorAll('[id*="dropdown-"]').forEach(el => {
                    if (el !== dropdown && !el.classList.contains('hidden')) {
                        el.classList.add('hidden');
                        el.classList.remove('show');
                    }
                });
                
                // Show dropdown with animation
                dropdown.classList.remove('hidden');
                dropdown.classList.add('show');
                button.setAttribute('aria-expanded', 'true');
            } else {
                // Hide dropdown with animation
                dropdown.classList.remove('show');
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
                    dropdown.classList.remove('show');
                    dropdown.classList.add('hidden');
                    button.setAttribute('aria-expanded', 'false');
                }
            }
        });
        
        // Close dropdown when pressing escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                if (!dropdown.classList.contains('hidden')) {
                    dropdown.classList.remove('show');
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
        // Create or get notification container
        let container = document.getElementById('notification-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'notification-container';
            container.className = 'fixed top-20 right-4 z-50 flex flex-col gap-2';
            document.body.appendChild(container);
        }

        // Create alert element
        const alertDiv = document.createElement('div');
        alertDiv.className = `notification max-w-sm p-4 rounded-md shadow-lg transition-opacity duration-300 ease-out ${
            type === 'success' ? 'bg-green-50 border border-green-200 text-green-700' :
            type === 'error' ? 'bg-red-50 border border-red-200 text-red-700' :
            type === 'info' ? 'bg-blue-50 border border-blue-200 text-blue-700' :
            'bg-purple-50 border border-purple-200 text-purple-700'
        }`;
        
        // Start with fade animation
        alertDiv.style.opacity = '0';
        
        alertDiv.innerHTML = `
            <div class="flex items-start">
                <i class="fas ${
                    type === 'success' ? 'fa-check-circle text-green-500' :
                    type === 'error' ? 'fa-exclamation-triangle text-red-500' :
                    type === 'info' ? 'fa-info-circle text-blue-500' :
                    'fa-bell text-purple-500'
                } mr-3 mt-0.5"></i>
                <span class="text-sm flex-1 pr-4">${message}</span>
                <button class="ml-4 text-sm opacity-60 hover:opacity-100 transition-opacity" onclick="MorningNewsletter.removeNotification(this.parentElement.parentElement)">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        // Insert at the beginning of container (new notifications on top)
        container.insertBefore(alertDiv, container.firstChild);
        
        // Trigger animation
        setTimeout(() => {
            alertDiv.style.opacity = '1';
        }, 10);
        
        // Auto remove after 5 seconds
        const removeTimeout = setTimeout(() => {
            this.removeNotification(alertDiv);
        }, 5000);
        
        // Store timeout ID so we can cancel it if user manually closes
        alertDiv.dataset.timeoutId = removeTimeout;
    },
    
    removeNotification(alertDiv) {
        if (!alertDiv || !alertDiv.parentElement) return;
        
        // Clear the auto-remove timeout if it exists
        if (alertDiv.dataset.timeoutId) {
            clearTimeout(parseInt(alertDiv.dataset.timeoutId));
        }
        
        // Animate out (fade)
        alertDiv.style.opacity = '0';
        
        // Remove after animation completes
        setTimeout(() => {
            if (alertDiv.parentElement) {
                alertDiv.remove();
                
                // Remove container if empty
                const container = document.getElementById('notification-container');
                if (container && container.children.length === 0) {
                    container.remove();
                }
            }
        }, 300);
    },

    // Modal confirmation system
    confirm(message, options = {}) {
        return new Promise((resolve) => {
            // Default options
            const defaults = {
                title: 'Confirm Action',
                confirmText: 'Confirm',
                cancelText: 'Cancel',
                confirmClass: 'bg-primary hover:bg-primary-dark text-white',
                cancelClass: 'bg-gray-300 hover:bg-gray-400 text-gray-800',
                dangerous: false
            };
            
            const settings = { ...defaults, ...options };
            
            // If dangerous action, use red colors
            if (settings.dangerous) {
                settings.confirmClass = 'bg-red-600 hover:bg-red-700 text-white';
            }
            
            // Create modal backdrop
            const backdrop = document.createElement('div');
            backdrop.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 animate-fade-in';
            backdrop.style.animation = 'fadeIn 0.2s ease-out';
            
            // Create modal
            const modal = document.createElement('div');
            modal.className = 'bg-white rounded-lg shadow-xl max-w-md w-full transform transition-all animate-modal-in';
            modal.style.animation = 'modalIn 0.3s ease-out';
            
            modal.innerHTML = `
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">${settings.title}</h3>
                    <p class="text-gray-600 mb-6">${message}</p>
                    <div class="flex justify-end space-x-3">
                        <button class="px-4 py-2 rounded-md font-medium transition-colors ${settings.cancelClass}" data-action="cancel">
                            ${settings.cancelText}
                        </button>
                        <button class="px-4 py-2 rounded-md font-medium transition-colors ${settings.confirmClass}" data-action="confirm">
                            ${settings.confirmText}
                        </button>
                    </div>
                </div>
            `;
            
            backdrop.appendChild(modal);
            document.body.appendChild(backdrop);
            
            // Focus confirm button
            const confirmBtn = modal.querySelector('[data-action="confirm"]');
            confirmBtn.focus();
            
            // Handle clicks
            const handleClick = (e) => {
                const action = e.target.dataset.action;
                if (action === 'confirm') {
                    cleanup();
                    resolve(true);
                } else if (action === 'cancel' || e.target === backdrop) {
                    cleanup();
                    resolve(false);
                }
            };
            
            // Handle escape key
            const handleEscape = (e) => {
                if (e.key === 'Escape') {
                    cleanup();
                    resolve(false);
                }
            };
            
            // Cleanup function
            const cleanup = () => {
                backdrop.removeEventListener('click', handleClick);
                document.removeEventListener('keydown', handleEscape);
                backdrop.style.opacity = '0';
                modal.style.transform = 'scale(0.95)';
                modal.style.opacity = '0';
                setTimeout(() => backdrop.remove(), 200);
            };
            
            // Add event listeners
            backdrop.addEventListener('click', handleClick);
            document.addEventListener('keydown', handleEscape);
        });
    },

    // Check for server-side notifications and display them
    checkServerNotifications() {
        // Check for success messages
        const successElements = document.querySelectorAll('.bg-green-50.border-green-200, [data-notification="success"]');
        successElements.forEach(el => {
            const message = el.textContent.trim().replace(/^\s*[✓✔]\s*/, '');
            if (message) {
                this.showAlert(message, 'success');
                el.style.display = 'none'; // Hide the server-side notification
            }
        });
        
        // Check for error messages
        const errorElements = document.querySelectorAll('.bg-red-50.border-red-200, [data-notification="error"]');
        errorElements.forEach(el => {
            const message = el.textContent.trim().replace(/^\s*[⚠✕]\s*/, '');
            if (message) {
                this.showAlert(message, 'error');
                el.style.display = 'none'; // Hide the server-side notification
            }
        });
        
        // Check for info messages
        const infoElements = document.querySelectorAll('.bg-blue-50.border-blue-200, [data-notification="info"]');
        infoElements.forEach(el => {
            const message = el.textContent.trim().replace(/^\s*[ℹ]\s*/, '');
            if (message) {
                this.showAlert(message, 'info');
                el.style.display = 'none'; // Hide the server-side notification
            }
        });
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
    // Check for server-side notifications
    MorningNewsletter.checkServerNotifications();
});

// Export for use in other files
if (typeof module !== 'undefined' && module.exports) {
    module.exports = MorningNewsletter;
}