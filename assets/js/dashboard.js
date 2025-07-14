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
            
            // Auto-detect and set timezone
            this.detectAndSetTimezone();
            
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
            const timezoneInput = document.getElementById('timezone');
            const frequencySelect = document.getElementById('frequency');
            
            if (titleInput) titleInput.value = '';
            if (timezoneInput) timezoneInput.value = 'UTC';
            if (frequencySelect) frequencySelect.value = 'daily';
            
            // Clear frequency-specific options
            this.clearFrequencyOptions();
            
            // Hide frequency-specific sections
            const weeklyOptions = document.getElementById('weekly-options');
            const monthlyOptions = document.getElementById('monthly-options');
            if (weeklyOptions) weeklyOptions.classList.add('hidden');
            if (monthlyOptions) monthlyOptions.classList.add('hidden');
        },

        detectAndSetTimezone: function() {
            const timezoneInput = document.getElementById('timezone');
            if (timezoneInput) {
                try {
                    const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                    timezoneInput.value = timezone;
                    console.log('Auto-detected timezone:', timezone);
                } catch (error) {
                    console.warn('Could not detect timezone, using UTC:', error);
                    timezoneInput.value = 'UTC';
                }
            }
        },

        clearFrequencyOptions: function() {
            // Reset daily times to single 6:00 AM option
            const dailyTimesContainer = document.getElementById('daily-times-container');
            if (dailyTimesContainer) {
                // Generate time options for 15-minute intervals
                let timeOptions = '';
                for (let h = 0; h < 24; h++) {
                    for (let m = 0; m < 60; m += 15) {
                        const timeValue = String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
                        const timeObj = new Date('2000-01-01 ' + timeValue);
                        const timeDisplay = timeObj.toLocaleTimeString('en-US', {hour: 'numeric', minute: '2-digit', hour12: true});
                        const selected = (timeValue === '06:00') ? 'selected' : '';
                        timeOptions += `<option value="${timeValue}" ${selected}>${timeDisplay}</option>`;
                    }
                }
                
                dailyTimesContainer.innerHTML = `
                    <div class="flex items-center gap-2">
                        <select name="daily_times[]" 
                                class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            ${timeOptions}
                        </select>
                        <button type="button" onclick="removeDailyTime(this)" class="text-red-600 hover:text-red-800 px-2" style="display: none;">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                        <div class="px-2 w-8 spacer"></div>
                    </div>
                `;
            }
            
            // Clear weekly checkboxes
            const weeklyCheckboxes = document.querySelectorAll('input[name="days_of_week[]"]');
            weeklyCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
                checkbox.parentElement.classList.remove('bg-blue-50', 'border-blue-300', 'text-blue-900');
            });
            
            // Reset monthly day selector
            const dayOfMonthSelect = document.getElementById('day_of_month');
            if (dayOfMonthSelect) dayOfMonthSelect.value = '1';
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

// Global wrapper functions for onclick handlers (to ensure Dashboard is loaded)
window.showCreateForm = function() {
    if (window.Dashboard && Dashboard.newsletter) {
        Dashboard.newsletter.showCreateForm();
    } else {
        console.error('Dashboard not loaded yet');
    }
};

window.hideCreateForm = function() {
    if (window.Dashboard && Dashboard.newsletter) {
        Dashboard.newsletter.hideCreateForm();
    } else {
        console.error('Dashboard not loaded yet');
    }
};