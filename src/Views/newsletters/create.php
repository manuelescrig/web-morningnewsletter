<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold text-gray-900">Create Newsletter</h1>
    </div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
        <form action="/newsletters" method="POST" class="space-y-8 divide-y divide-gray-200">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            
            <div class="space-y-8 divide-y divide-gray-200">
                <div>
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Newsletter Information</h3>
                        <p class="mt-1 text-sm text-gray-500">Basic information about your newsletter.</p>
                    </div>

                    <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        <div class="sm:col-span-4">
                            <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
                            <div class="mt-1">
                                <input type="text" name="title" id="title" required
                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                            </div>
                        </div>

                        <div class="sm:col-span-6">
                            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                            <div class="mt-1">
                                <textarea id="description" name="description" rows="3"
                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-8">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Schedule</h3>
                        <p class="mt-1 text-sm text-gray-500">When should this newsletter be delivered?</p>
                    </div>

                    <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        <div class="sm:col-span-3">
                            <label for="schedule_type" class="block text-sm font-medium text-gray-700">Frequency</label>
                            <div class="mt-1">
                                <select id="schedule_type" name="schedule_type" required
                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="custom">Custom</option>
                                </select>
                            </div>
                        </div>

                        <div class="sm:col-span-3">
                            <label for="schedule_time" class="block text-sm font-medium text-gray-700">Time</label>
                            <div class="mt-1">
                                <input type="time" name="schedule_time" id="schedule_time" required
                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                            </div>
                        </div>

                        <div class="sm:col-span-6 schedule-days" style="display: none;">
                            <label class="block text-sm font-medium text-gray-700">Days</label>
                            <div class="mt-2 space-y-2">
                                <div class="flex items-center">
                                    <input type="checkbox" name="schedule_days[]" value="monday" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    <label class="ml-3 block text-sm font-medium text-gray-700">Monday</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="schedule_days[]" value="tuesday" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    <label class="ml-3 block text-sm font-medium text-gray-700">Tuesday</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="schedule_days[]" value="wednesday" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    <label class="ml-3 block text-sm font-medium text-gray-700">Wednesday</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="schedule_days[]" value="thursday" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    <label class="ml-3 block text-sm font-medium text-gray-700">Thursday</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="schedule_days[]" value="friday" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    <label class="ml-3 block text-sm font-medium text-gray-700">Friday</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="schedule_days[]" value="saturday" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    <label class="ml-3 block text-sm font-medium text-gray-700">Saturday</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="schedule_days[]" value="sunday" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    <label class="ml-3 block text-sm font-medium text-gray-700">Sunday</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-8">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Sections</h3>
                        <p class="mt-1 text-sm text-gray-500">Add content sections to your newsletter.</p>
                    </div>

                    <div class="mt-6">
                        <div id="sections-container" class="space-y-4">
                            <!-- Sections will be added here dynamically -->
                        </div>

                        <div class="mt-4">
                            <button type="button" id="add-section"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <i class="fas fa-plus mr-2"></i> Add Section
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-5">
                <div class="flex justify-end">
                    <a href="/newsletters"
                        class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Cancel
                    </a>
                    <button type="submit"
                        class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Create Newsletter
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const scheduleType = document.getElementById('schedule_type');
    const scheduleDays = document.querySelector('.schedule-days');
    const sectionsContainer = document.getElementById('sections-container');
    const addSectionButton = document.getElementById('add-section');
    let sectionCount = 0;

    // Handle schedule type change
    scheduleType.addEventListener('change', function() {
        scheduleDays.style.display = this.value === 'weekly' ? 'block' : 'none';
    });

    // Section templates
    const sectionTemplates = {
        weather: {
            title: 'Weather',
            fields: [
                { name: 'location', label: 'Location', type: 'text', required: true },
                { name: 'units', label: 'Units', type: 'select', options: ['metric', 'imperial'], required: true }
            ]
        },
        news: {
            title: 'News',
            fields: [
                { name: 'category', label: 'Category', type: 'select', options: ['general', 'business', 'technology', 'sports', 'entertainment'], required: true },
                { name: 'country', label: 'Country', type: 'text', required: true }
            ]
        },
        stripe: {
            title: 'Stripe Sales',
            fields: [
                { name: 'period', label: 'Period', type: 'select', options: ['today', 'yesterday', 'this_week', 'this_month'], required: true }
            ]
        },
        appstore: {
            title: 'App Store Revenue',
            fields: [
                { name: 'period', label: 'Period', type: 'select', options: ['today', 'yesterday', 'this_week', 'this_month'], required: true }
            ]
        },
        github: {
            title: 'GitHub Activity',
            fields: [
                { name: 'repository', label: 'Repository', type: 'text', required: true },
                { name: 'events', label: 'Events', type: 'select', options: ['all', 'commits', 'issues', 'pull_requests'], required: true }
            ]
        }
    };

    // Add section
    addSectionButton.addEventListener('click', function() {
        const sectionId = `section-${sectionCount++}`;
        const section = document.createElement('div');
        section.className = 'bg-white shadow sm:rounded-lg p-4';
        section.innerHTML = `
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-lg font-medium text-gray-900">New Section</h4>
                <button type="button" class="text-red-600 hover:text-red-900 remove-section">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                <div class="sm:col-span-3">
                    <label class="block text-sm font-medium text-gray-700">Section Type</label>
                    <select name="sections[${sectionCount}][type]" class="section-type mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        <option value="">Select a type</option>
                        ${Object.entries(sectionTemplates).map(([key, template]) => 
                            `<option value="${key}">${template.title}</option>`
                        ).join('')}
                    </select>
                </div>
                <div class="sm:col-span-3">
                    <label class="block text-sm font-medium text-gray-700">Title</label>
                    <input type="text" name="sections[${sectionCount}][title]" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
                <div class="sm:col-span-6 section-fields">
                    <!-- Fields will be added here dynamically -->
                </div>
            </div>
        `;

        sectionsContainer.appendChild(section);

        // Handle section type change
        const typeSelect = section.querySelector('.section-type');
        typeSelect.addEventListener('change', function() {
            const template = sectionTemplates[this.value];
            const fieldsContainer = section.querySelector('.section-fields');
            
            if (template) {
                fieldsContainer.innerHTML = template.fields.map(field => `
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">${field.label}</label>
                        ${field.type === 'select' 
                            ? `<select name="sections[${sectionCount}][config][${field.name}]" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                ${field.options.map(opt => `<option value="${opt}">${opt}</option>`).join('')}
                               </select>`
                            : `<input type="${field.type}" name="sections[${sectionCount}][config][${field.name}]" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">`
                        }
                    </div>
                `).join('');
            } else {
                fieldsContainer.innerHTML = '';
            }
        });

        // Handle section removal
        section.querySelector('.remove-section').addEventListener('click', function() {
            section.remove();
        });
    });
});
</script> 