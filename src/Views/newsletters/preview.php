<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-gray-900">Preview Newsletter</h1>
            <div class="flex space-x-3">
                <a href="/newsletters/<?= $newsletter['id'] ?>/edit" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <i class="fas fa-edit mr-2"></i> Edit
                </a>
                <button type="button" onclick="window.print()" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <i class="fas fa-print mr-2"></i> Print
                </button>
            </div>
        </div>
    </div>

    <div class="mt-8 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <!-- Newsletter Header -->
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <h2 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($newsletter['title']) ?></h2>
                <?php if ($newsletter['description']): ?>
                    <p class="mt-1 text-sm text-gray-500"><?= htmlspecialchars($newsletter['description']) ?></p>
                <?php endif; ?>
                <div class="mt-2 text-sm text-gray-500">
                    <p>Schedule: <?= ucfirst($newsletter['schedule_type']) ?> at <?= date('g:i A', strtotime($newsletter['schedule_time'])) ?></p>
                    <?php if ($newsletter['schedule_type'] === 'weekly' && $newsletter['schedule_days']): ?>
                        <p>Days: <?= ucwords(str_replace(',', ', ', $newsletter['schedule_days'])) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Newsletter Content -->
            <div class="px-4 py-5 sm:p-6">
                <?php if (empty($sections)): ?>
                    <div class="text-center text-gray-500 py-8">
                        No sections added to this newsletter yet.
                    </div>
                <?php else: ?>
                    <?php foreach ($sections as $section): ?>
                        <div class="mb-8 last:mb-0">
                            <h3 class="text-lg font-medium text-gray-900 mb-4"><?= htmlspecialchars($section['title']) ?></h3>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <?= $section['content'] ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Newsletter Footer -->
            <div class="px-4 py-4 sm:px-6 border-t border-gray-200">
                <div class="text-sm text-gray-500">
                    <p>Generated on <?= date('F j, Y g:i A') ?></p>
                    <p class="mt-1">© <?= date('Y') ?> <?= $config['app']['name'] ?>. All rights reserved.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    .bg-white.shadow.overflow-hidden.sm\:rounded-lg,
    .bg-white.shadow.overflow-hidden.sm\:rounded-lg * {
        visibility: visible;
    }
    .bg-white.shadow.overflow-hidden.sm\:rounded-lg {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
}
</style> 