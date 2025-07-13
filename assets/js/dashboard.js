/**
 * Dashboard Core JavaScript
 * Common functionality shared across dashboard pages
 */

// Dashboard namespace
const Dashboard = {
    /**
     * Common CSRF token utilities
     */
    csrf: {
        getToken: function() {
            const metaTag = document.querySelector('meta[name="csrf-token"]');
            return metaTag ? metaTag.getAttribute('content') : '';
        }
    },

    /**
     * Modal management utilities
     */
    modal: {
        open: function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('hidden');
                // Focus trap for accessibility
                const firstInput = modal.querySelector('input, select, textarea, button');
                if (firstInput) firstInput.focus();
            }
        },

        close: function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('hidden');
            }
        },

        closeOnOutsideClick: function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        Dashboard.modal.close(modalId);
                    }
                });
            }
        },

        closeOnEscape: function(modalId) {
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    Dashboard.modal.close(modalId);
                }
            });
        }
    },

    /**
     * Dropdown management utilities
     */
    dropdown: {
        toggle: function(dropdownId, buttonId) {
            const dropdown = document.getElementById(dropdownId);
            const button = document.getElementById(buttonId);
            
            if (!dropdown || !button) return;
            
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
        },

        closeAll: function() {
            document.querySelectorAll('[id*="dropdown-"]').forEach(el => {
                el.classList.add('hidden');
            });
            document.querySelectorAll('[aria-expanded="true"]').forEach(button => {
                button.setAttribute('aria-expanded', 'false');
            });
        },

        initializeOutsideClick: function() {
            document.addEventListener('click', function(event) {
                const dropdown = event.target.closest('[id*="dropdown-"]');
                const button = event.target.closest('[aria-haspopup="true"]');
                
                if (!dropdown && !button) {
                    Dashboard.dropdown.closeAll();
                }
            });
        },

        initializeEscapeKey: function() {
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    Dashboard.dropdown.closeAll();
                }
            });
        }
    },

    /**
     * Form utilities
     */
    form: {
        submitWithConfirmation: function(formData, message) {
            if (confirm(message)) {
                const form = document.createElement('form');
                form.method = 'POST';
                
                for (const [key, value] of Object.entries(formData)) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = value;
                    form.appendChild(input);
                }
                
                document.body.appendChild(form);
                form.submit();
            }
        }
    },

    /**
     * Print functionality
     */
    print: {
        hideElementsAndPrint: function(elementsToHide = []) {
            // Hide specified elements
            elementsToHide.forEach(selector => {
                const elements = document.querySelectorAll(selector);
                elements.forEach(el => el.classList.add('no-print'));
            });
            
            // Print
            window.print();
            
            // Restore elements after printing
            setTimeout(() => {
                elementsToHide.forEach(selector => {
                    const elements = document.querySelectorAll(selector);
                    elements.forEach(el => el.classList.remove('no-print'));
                });
            }, 1000);
        }
    },

    /**
     * Newsletter creation utilities
     */
    newsletter: {
        showCreateForm: function() {
            const section = document.getElementById('createNewsletterSection');
            if (!section) return;
            
            section.classList.remove('hidden');
            section.scrollIntoView({ behavior: 'smooth', block: 'start' });
            
            // Hide the entire empty state section and header button
            const emptyStateSection = document.getElementById('emptyStateSection');
            const headerButton = document.getElementById('createButtonHeader');
            if (emptyStateSection) emptyStateSection.style.display = 'none';
            if (headerButton) headerButton.style.display = 'none';
            
            // Focus on the title input
            setTimeout(() => {
                const titleInput = document.getElementById('title');
                if (titleInput) titleInput.focus();
            }, 300);
        },

        hideCreateForm: function() {
            const section = document.getElementById('createNewsletterSection');
            if (!section) return;
            
            section.classList.add('hidden');
            
            // Show the empty state section and header button again
            const emptyStateSection = document.getElementById('emptyStateSection');
            const headerButton = document.getElementById('createButtonHeader');
            if (emptyStateSection) emptyStateSection.style.display = 'block';
            if (headerButton) headerButton.style.display = 'inline-block';
            
            // Clear form
            const titleInput = document.getElementById('title');
            const timezoneSelect = document.getElementById('timezone');
            const sendTimeInput = document.getElementById('send_time');
            
            if (titleInput) titleInput.value = '';
            if (timezoneSelect) timezoneSelect.value = 'UTC';
            if (sendTimeInput) sendTimeInput.value = '06:00';
        }
    },

    /**
     * Initialize dashboard functionality
     */
    init: function() {
        // Initialize dropdown functionality
        this.dropdown.initializeOutsideClick();
        this.dropdown.initializeEscapeKey();
        
        // Initialize modal escape key handling
        this.modal.closeOnEscape();
        
        console.log('Dashboard core functionality initialized');
    }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    Dashboard.init();
});

// Export for use in other scripts
window.Dashboard = Dashboard;