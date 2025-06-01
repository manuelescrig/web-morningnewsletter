<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold text-gray-900">Dashboard</h1>
    </div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
        <!-- Welcome Section -->
        <div class="py-4">
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-medium text-gray-900">Welcome back, <?= htmlspecialchars($this->user['name']) ?>!</h2>
                <p class="mt-1 text-sm text-gray-500">Here's an overview of your newsletters and recent activity.</p>
            </div>
        </div>

        <!-- Newsletters Section -->
        <div class="mt-8">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-medium text-gray-900">Your Newsletters</h2>
                <a href="/newsletters/create" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Create Newsletter
                </a>
            </div>

            <div class="mt-4 bg-white shadow overflow-hidden sm:rounded-md">
                <ul class="divide-y divide-gray-200">
                    <?php if (empty($newsletters)): ?>
                        <li class="px-6 py-4 text-center text-gray-500">
                            You haven't created any newsletters yet.
                        </li>
                    <?php else: ?>
                        <?php foreach ($newsletters as $newsletter): ?>
                            <li>
                                <div class="px-6 py-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1 min-w-0">
                                            <h3 class="text-lg font-medium text-gray-900 truncate">
                                                <?= htmlspecialchars($newsletter['title']) ?>
                                            </h3>
                                            <p class="mt-1 text-sm text-gray-500">
                                                <?= htmlspecialchars($newsletter['description'] ?? 'No description') ?>
                                            </p>
                                        </div>
                                        <div class="ml-4 flex-shrink-0 flex space-x-4">
                                            <a href="/newsletters/<?= $newsletter['id'] ?>/edit" class="text-indigo-600 hover:text-indigo-900">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="/newsletters/<?= $newsletter['id'] ?>/preview" class="text-indigo-600 hover:text-indigo-900">
                                                <i class="fas fa-eye"></i> Preview
                                            </a>
                                        </div>
                                    </div>
                                    <div class="mt-2 flex items-center text-sm text-gray-500">
                                        <div class="flex items-center mr-4">
                                            <i class="fas fa-users mr-1"></i>
                                            <?= $newsletter['recipient_count'] ?> recipients
                                        </div>
                                        <div class="flex items-center">
                                            <i class="fas fa-paper-plane mr-1"></i>
                                            <?= $newsletter['delivery_count'] ?> deliveries
                                        </div>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- Recent Deliveries Section -->
        <div class="mt-8">
            <h2 class="text-lg font-medium text-gray-900">Recent Deliveries</h2>
            <div class="mt-4 bg-white shadow overflow-hidden sm:rounded-md">
                <ul class="divide-y divide-gray-200">
                    <?php if (empty($recent_deliveries)): ?>
                        <li class="px-6 py-4 text-center text-gray-500">
                            No recent deliveries.
                        </li>
                    <?php else: ?>
                        <?php foreach ($recent_deliveries as $delivery): ?>
                            <li>
                                <div class="px-6 py-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate">
                                                <?= htmlspecialchars($delivery['newsletter_title']) ?>
                                            </p>
                                            <p class="mt-1 text-sm text-gray-500">
                                                <?= date('F j, Y g:i A', strtotime($delivery['created_at'])) ?>
                                            </p>
                                        </div>
                                        <div class="ml-4 flex-shrink-0">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?= $delivery['status'] === 'sent' ? 'bg-green-100 text-green-800' : 
                                                    ($delivery['status'] === 'failed' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') ?>">
                                                <?= ucfirst($delivery['status']) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div> 