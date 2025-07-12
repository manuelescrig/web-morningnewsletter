/**
 * Payments JavaScript
 * Stripe payment processing functionality
 */

const Payments = {
    /**
     * Subscribe to a plan
     */
    subscribeToPlan: async function(plan) {
        try {
            const response = await fetch('/api/fixed-checkout.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ plan: plan })
            });

            const data = await response.json();

            if (data.success) {
                // Redirect to Stripe Checkout
                window.location.href = data.checkout_url;
            } else {
                alert('Error: ' + (data.message || 'Failed to create checkout session'));
            }
        } catch (error) {
            console.error('Subscription error:', error);
            alert('An error occurred while processing your request.');
        }
    },

    /**
     * Manage billing portal
     */
    manageBilling: async function() {
        try {
            const response = await fetch('/api/billing-portal.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            });

            const data = await response.json();

            if (data.success) {
                // Redirect to billing portal
                window.location.href = data.portal_url;
            } else {
                alert('Error: ' + (data.message || 'Failed to access billing portal'));
            }
        } catch (error) {
            console.error('Billing portal error:', error);
            alert('An error occurred while accessing the billing portal.');
        }
    },

    /**
     * Cancel subscription
     */
    cancelSubscription: async function() {
        if (!confirm('Are you sure you want to cancel your subscription? This will take effect at the end of your current billing period.')) {
            return;
        }

        try {
            const response = await fetch('/api/cancel-subscription.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            });

            const data = await response.json();

            if (data.success) {
                alert('Your subscription has been cancelled. It will remain active until the end of your current billing period.');
                // Reload the page to update the UI
                window.location.reload();
            } else {
                alert('Error: ' + (data.message || 'Failed to cancel subscription'));
            }
        } catch (error) {
            console.error('Cancellation error:', error);
            alert('An error occurred while cancelling your subscription.');
        }
    },

    /**
     * Handle plan selection and storage
     */
    selectPlan: function(plan) {
        // Store selected plan in session storage for checkout process
        sessionStorage.setItem('selectedPlan', plan);
        console.log('Plan selected:', plan);
    },

    /**
     * Toggle plan dropdown
     */
    togglePlanDropdown: function() {
        const dropdown = document.getElementById('plan-dropdown');
        if (dropdown) {
            dropdown.classList.toggle('hidden');
        }
    },

    /**
     * Initialize payment functionality
     */
    init: function() {
        // Restore selected plan from session storage
        const selectedPlan = sessionStorage.getItem('selectedPlan');
        if (selectedPlan) {
            const planElement = document.getElementById('selected-plan');
            if (planElement) {
                planElement.textContent = selectedPlan.charAt(0).toUpperCase() + selectedPlan.slice(1);
            }
        }

        console.log('Payments functionality initialized');
    }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    Payments.init();
});

// Export for use in other scripts
window.Payments = Payments;