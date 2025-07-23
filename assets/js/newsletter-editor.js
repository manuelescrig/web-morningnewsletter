/**
 * Newsletter Editor JavaScript
 * Complex functionality for managing newsletter sources and settings
 */

const NewsletterEditor = {
    /**
     * Filter functionality for source categories
     */
    filter: {
        /**
         * Filter sources by category
         */
        byCategory: function(category) {
            const categories = document.querySelectorAll('.source-category');
            categories.forEach(cat => {
                if (category === 'all' || cat.dataset.category === category) {
                    cat.style.display = 'block';
                } else {
                    cat.style.display = 'none';
                }
            });
            
            // Update filter buttons
            const filterButtons = document.querySelectorAll('.category-filter');
            filterButtons.forEach(btn => {
                if (btn.dataset.category === category) {
                    btn.classList.add('bg-primary', 'text-primary-lightest');
                    btn.classList.remove('bg-gray-200', 'text-gray-700');
                } else {
                    btn.classList.remove('bg-primary', 'text-primary-lightest');
                    btn.classList.add('bg-gray-200', 'text-gray-700');
                }
            });
        },
        
        /**
         * Initialize category filter buttons
         */
        init: function() {
            // Add filter buttons to the sources section
            const sourcesGrid = document.getElementById('sourcesGrid');
            if (sourcesGrid) {
                const filterContainer = document.createElement('div');
                filterContainer.className = 'mb-6 flex flex-wrap gap-2';
                filterContainer.innerHTML = `
                    <span class="text-sm font-medium text-gray-700 mr-3 py-2">Filter by category:</span>
                    <button class="category-filter bg-primary text-primary-lightest px-3 py-1 rounded-full text-sm font-medium transition-colors duration-200" 
                            data-category="all" onclick="NewsletterEditor.filter.byCategory('all')">
                        All
                    </button>
                    <button class="category-filter bg-gray-200 text-gray-700 hover:bg-gray-300 hover:text-gray-800 px-3 py-1 rounded-full text-sm font-medium transition-colors duration-200" 
                            data-category="crypto" onclick="NewsletterEditor.filter.byCategory('crypto')">
                        Crypto
                    </button>
                    <button class="category-filter bg-gray-200 text-gray-700 hover:bg-gray-300 hover:text-gray-800 px-3 py-1 rounded-full text-sm font-medium transition-colors duration-200" 
                            data-category="finance" onclick="NewsletterEditor.filter.byCategory('finance')">
                        Finance
                    </button>
                    <button class="category-filter bg-gray-200 text-gray-700 hover:bg-gray-300 hover:text-gray-800 px-3 py-1 rounded-full text-sm font-medium transition-colors duration-200" 
                            data-category="lifestyle" onclick="NewsletterEditor.filter.byCategory('lifestyle')">
                        Lifestyle
                    </button>
                    <button class="category-filter bg-gray-200 text-gray-700 hover:bg-gray-300 hover:text-gray-800 px-3 py-1 rounded-full text-sm font-medium transition-colors duration-200" 
                            data-category="news" onclick="NewsletterEditor.filter.byCategory('news')">
                        News
                    </button>
                    <button class="category-filter bg-gray-200 text-gray-700 hover:bg-gray-300 hover:text-gray-800 px-3 py-1 rounded-full text-sm font-medium transition-colors duration-200" 
                            data-category="business" onclick="NewsletterEditor.filter.byCategory('business')">
                        Business
                    </button>
                `;
                sourcesGrid.parentNode.insertBefore(filterContainer, sourcesGrid);
            }
        }
    },
    /**
     * Source management functionality
     */
    sources: {
        /**
         * Add a new source
         */
        add: function(type) {
            Dashboard.modal.open('addSourceModal');
            
            // Set the source type in the form
            const typeInput = document.getElementById('addSourceType');
            if (typeInput) {
                typeInput.value = type;
            }
            
            // Show/hide relevant configuration fields
            this.showConfigFields('add', type);
        },

        /**
         * Edit an existing source
         */
        edit: function(sourceId, type, config) {
            Dashboard.modal.open('editSourceModal');
            
            // Populate form with source data
            const idInput = document.getElementById('editSourceId');
            const typeInput = document.getElementById('editSourceType');
            
            if (idInput) idInput.value = sourceId;
            if (typeInput) typeInput.value = type;
            
            // Populate configuration fields
            this.populateConfigFields('edit', type, config);
            this.showConfigFields('edit', type);
        },

        /**
         * Delete a source
         */
        delete: function(sourceId, sourceName) {
            const message = `Are you sure you want to delete "${sourceName}"? This action cannot be undone.`;
            
            Dashboard.form.submitWithConfirmation({
                action: 'delete_source',
                source_id: sourceId,
                csrf_token: window.csrfToken || Dashboard.csrf.getToken()
            }, message);
        },

        /**
         * Show configuration fields based on source type
         */
        showConfigFields: function(mode, type) {
            const prefix = mode === 'add' ? 'add' : 'edit';
            
            // Hide all config sections first
            const configSections = document.querySelectorAll(`[id^="${prefix}Config"]`);
            configSections.forEach(section => section.classList.add('hidden'));
            
            // Show relevant config section
            const targetSection = document.getElementById(`${prefix}Config${type.charAt(0).toUpperCase() + type.slice(1)}`);
            if (targetSection) {
                targetSection.classList.remove('hidden');
            }
        },

        /**
         * Populate configuration fields for editing
         */
        populateConfigFields: function(mode, type, config) {
            if (!config) return;
            
            const prefix = mode === 'add' ? 'add' : 'edit';
            
            // Populate fields based on source type
            switch (type) {
                case 'weather':
                    this.setFieldValue(`${prefix}_weather_api_key`, config.api_key);
                    this.setFieldValue(`${prefix}_weather_city`, config.city);
                    break;
                case 'news':
                    this.setFieldValue(`${prefix}_news_api_key`, config.api_key);
                    this.setFieldValue(`${prefix}_news_country`, config.country);
                    this.setFieldValue(`${prefix}_news_category`, config.category);
                    this.setFieldValue(`${prefix}_news_limit`, config.limit);
                    break;
                case 'stripe':
                    this.setFieldValue(`${prefix}_stripe_api_key`, config.api_key);
                    break;
                case 'sp500':
                    this.setFieldValue(`${prefix}_sp500_api_key`, config.api_key);
                    break;
                // Add other source types as needed
            }
        },

        /**
         * Helper to set field value safely
         */
        setFieldValue: function(fieldId, value) {
            const field = document.getElementById(fieldId);
            if (field && value) {
                field.value = value;
            }
        }
    },

    /**
     * Location search functionality
     */
    location: {
        /**
         * Search for locations using OpenWeatherMap API
         */
        search: async function(mode, query) {
            if (!query || query.length < 2) return;
            
            const resultsId = `${mode}_location_results`;
            const resultsContainer = document.getElementById(resultsId);
            
            if (!resultsContainer) return;
            
            try {
                // Note: This would need an actual API key in production
                const response = await fetch(`https://api.openweathermap.org/geo/1.0/direct?q=${encodeURIComponent(query)}&limit=5&appid=YOUR_API_KEY`);
                const locations = await response.json();
                
                this.displayResults(resultsId, locations, mode);
            } catch (error) {
                console.error('Location search error:', error);
                resultsContainer.innerHTML = '<div class="p-2 text-red-600">Error searching locations</div>';
            }
        },

        /**
         * Display location search results
         */
        displayResults: function(resultsId, locations, mode) {
            const resultsContainer = document.getElementById(resultsId);
            if (!resultsContainer) return;
            
            if (locations.length === 0) {
                resultsContainer.innerHTML = '<div class="p-2 text-gray-500">No locations found</div>';
                return;
            }
            
            const html = locations.map(location => {
                const displayName = `${location.name}${location.state ? ', ' + location.state : ''}${location.country ? ', ' + location.country : ''}`;
                return `
                    <div class="p-2 hover:bg-gray-100 cursor-pointer border-b" 
                         onclick="NewsletterEditor.location.select('${mode}', '${location.name}', ${location.lat}, ${location.lon})">
                        ${displayName}
                    </div>
                `;
            }).join('');
            
            resultsContainer.innerHTML = html;
        },

        /**
         * Select a location from search results
         */
        select: function(mode, name, lat, lon) {
            const cityInput = document.getElementById(`${mode}_weather_city`);
            if (cityInput) {
                cityInput.value = name;
            }
            
            // Hide results
            const resultsContainer = document.getElementById(`${mode}_location_results`);
            if (resultsContainer) {
                resultsContainer.innerHTML = '';
            }
        }
    },

    /**
     * Drag and drop functionality for source ordering
     */
    dragDrop: {
        /**
         * Initialize sortable functionality
         */
        init: function() {
            const sourcesList = document.getElementById('sources-list');
            if (!sourcesList) return;
            
            // Enable drag and drop for source items
            const sourceItems = sourcesList.querySelectorAll('.source-item');
            sourceItems.forEach(item => {
                item.draggable = true;
                item.addEventListener('dragstart', this.handleDragStart.bind(this));
                item.addEventListener('dragover', this.handleDragOver.bind(this));
                item.addEventListener('drop', this.handleDrop.bind(this));
                item.addEventListener('dragend', this.handleDragEnd.bind(this));
            });
        },

        handleDragStart: function(e) {
            e.dataTransfer.setData('text/plain', e.target.dataset.sourceId);
            e.target.classList.add('opacity-50');
        },

        handleDragOver: function(e) {
            e.preventDefault();
        },

        handleDrop: function(e) {
            e.preventDefault();
            const draggedId = e.dataTransfer.getData('text/plain');
            const targetId = e.target.closest('.source-item').dataset.sourceId;
            
            if (draggedId !== targetId) {
                // Reorder sources
                this.reorderSources(draggedId, targetId);
            }
        },

        handleDragEnd: function(e) {
            e.target.classList.remove('opacity-50');
        },

        /**
         * Reorder sources on the server
         */
        reorderSources: function(draggedId, targetId) {
            // This would make an AJAX call to reorder sources
            console.log('Reordering sources:', draggedId, 'to position of', targetId);
            // Implementation would depend on backend API
        }
    },

    /**
     * Schedule management
     */
    schedule: {
        /**
         * Update frequency options based on selection
         */
        updateFrequencyOptions: function() {
            const frequency = document.getElementById('frequency').value;
            
            // Hide all options first
            const allOptions = document.querySelectorAll('[id$="-options"]');
            allOptions.forEach(option => option.classList.add('hidden'));
            
            // Show relevant options
            switch (frequency) {
                case 'weekly':
                    document.getElementById('weekly-options')?.classList.remove('hidden');
                    break;
                case 'monthly':
                    document.getElementById('monthly-options')?.classList.remove('hidden');
                    break;
                case 'quarterly':
                    document.getElementById('monthly-options')?.classList.remove('hidden');
                    document.getElementById('quarterly-options')?.classList.remove('hidden');
                    break;
            }
        },

        /**
         * Initialize checkbox styling for days/months
         */
        initializeCheckboxStyling: function() {
            const checkboxes = document.querySelectorAll('input[type="checkbox"][name^="days_"], input[type="checkbox"][name^="months"]');
            
            checkboxes.forEach(checkbox => {
                const label = checkbox.closest('label');
                if (!label) return;
                
                const updateStyle = () => {
                    if (checkbox.checked) {
                        label.classList.add('bg-primary', 'text-primary-lightest', 'border-primary');
                        label.classList.remove('bg-white', 'text-gray-700', 'border-gray-300');
                    } else {
                        label.classList.add('bg-white', 'text-gray-700', 'border-gray-300');
                        label.classList.remove('bg-primary', 'text-primary-lightest', 'border-primary');
                    }
                };
                
                updateStyle(); // Initial styling
                checkbox.addEventListener('change', updateStyle);
            });
        }
    },

    /**
     * Initialize newsletter editor functionality
     */
    init: function() {
        // Initialize category filtering
        this.filter.init();
        
        // Initialize drag and drop
        this.dragDrop.init();
        
        // Initialize schedule management
        this.schedule.initializeCheckboxStyling();
        
        // Set up frequency change handler
        const frequencySelect = document.getElementById('frequency');
        if (frequencySelect) {
            frequencySelect.addEventListener('change', this.schedule.updateFrequencyOptions);
            // Initial call to set correct state
            this.schedule.updateFrequencyOptions();
        }
        
        // Initialize location search with debouncing
        const addCityInput = document.getElementById('add_weather_city');
        const editCityInput = document.getElementById('edit_weather_city');
        
        if (addCityInput) {
            this.debounce(addCityInput, (value) => this.location.search('add', value), 300);
        }
        
        if (editCityInput) {
            this.debounce(editCityInput, (value) => this.location.search('edit', value), 300);
        }
        
        console.log('Newsletter editor functionality initialized');
    },

    /**
     * Utility: Debounce function
     */
    debounce: function(input, callback, delay) {
        let timeoutId;
        input.addEventListener('input', function() {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => callback(this.value), delay);
        });
    }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    NewsletterEditor.init();
});

// Export for use in other scripts
window.NewsletterEditor = NewsletterEditor;