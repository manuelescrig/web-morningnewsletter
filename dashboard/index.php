<?php
/**
 * Multi-Newsletter Dashboard
 * 
 * Main dashboard for managing multiple newsletters per user
 */

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Newsletter.php';
require_once __DIR__ . '/../core/Scheduler.php';

$auth = Auth::getInstance();
$auth->requireAuth();

$user = $auth->getCurrentUser();
$scheduler = new Scheduler();
$newsletters = $user->getNewsletters();
$error = '';
$success = '';

$currentPage = 'dashboard';

// Common timezones for dropdown
$timezones = [
    'UTC' => 'UTC',
    'America/New_York' => 'Eastern Time',
    'America/Chicago' => 'Central Time', 
    'America/Denver' => 'Mountain Time',
    'America/Los_Angeles' => 'Pacific Time',
    'Europe/London' => 'London',
    'Europe/Paris' => 'Paris',
    'Asia/Tokyo' => 'Tokyo',
    'Australia/Sydney' => 'Sydney'
];

// Handle newsletter management actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!$auth->validateCSRFToken($csrfToken)) {
        $error = 'Invalid request. Please try again.';
    } else {
        switch ($action) {
            case 'create_newsletter':
                $title = trim($_POST['title'] ?? '');
                $timezone = $_POST['timezone'] ?? 'UTC';
                $sendTime = $_POST['send_time'] ?? '06:00';
                
                if (empty($title)) {
                    $error = 'Newsletter title is required.';
                } else {
                    try {
                        $newsletterId = $user->createNewsletter($title, $timezone, $sendTime);
                        if ($newsletterId) {
                            $success = "Newsletter '$title' created successfully!";
                            // Refresh newsletters
                            $newsletters = $user->getNewsletters();
                        } else {
                            $error = 'Failed to create newsletter.';
                        }
                    } catch (Exception $e) {
                        $error = 'Error: ' . $e->getMessage();
                    }
                }
                break;
                
            case 'update_newsletter':
                $newsletterId = (int)$_POST['newsletter_id'];
                $newsletter = $user->getNewsletter($newsletterId);
                
                if ($newsletter) {
                    $updateData = [
                        'title' => trim($_POST['title'] ?? ''),
                        'timezone' => $_POST['timezone'] ?? 'UTC',
                        'send_time' => $_POST['send_time'] ?? '06:00'
                    ];
                    
                    if ($newsletter->update($updateData)) {
                        $success = 'Newsletter updated successfully!';
                        // Refresh newsletters
                        $newsletters = $user->getNewsletters();
                    } else {
                        $error = 'Failed to update newsletter.';
                    }
                } else {
                    $error = 'Newsletter not found.';
                }
                break;
                
            case 'delete_newsletter':
                $newsletterId = (int)$_POST['newsletter_id'];
                $newsletter = $user->getNewsletter($newsletterId);
                
                if ($newsletter) {
                    if (count($newsletters) <= 1) {
                        $error = 'Cannot delete your last newsletter. You must have at least one newsletter.';
                    } else {
                        if ($newsletter->delete()) {
                            $success = 'Newsletter deleted successfully!';
                            // Refresh newsletters
                            $newsletters = $user->getNewsletters();
                        } else {
                            $error = 'Failed to delete newsletter.';
                        }
                    }
                } else {
                    $error = 'Newsletter not found.';
                }
                break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Newsletters - MorningNewsletter</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/dashboard.css">
</head>
<body class="bg-gray-50">
    <?php include __DIR__ . '/includes/navigation.php'; ?>
    
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">My Newsletters</h1>
            <p class="text-gray-600 mt-2">Create and manage your personalized morning briefings</p>
        </div>

        <!-- Messages -->
        <?php if ($error): ?>
            <div class="mb-6 p-4 rounded-md bg-red-50 text-red-800 border border-red-200">
                <div class="flex">
                    <i class="fas fa-exclamation-triangle mr-2 mt-0.5"></i>
                    <div><?php echo htmlspecialchars($error); ?></div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-6 p-4 rounded-md bg-green-50 text-green-800 border border-green-200">
                <div class="flex">
                    <i class="fas fa-check-circle mr-2 mt-0.5"></i>
                    <div><?php echo htmlspecialchars($success); ?></div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Create New Newsletter Section -->
        <div class="bg-white rounded-lg shadow mb-8">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">
                    Create New Newsletter
                </h2>
                <p class="text-gray-600 mt-1">Add another personalized newsletter with different sources and schedule</p>
            </div>
            <div class="p-6">
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($auth->generateCSRFToken()); ?>">
                    <input type="hidden" name="action" value="create_newsletter">
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                                Newsletter Title *
                            </label>
                            <input type="text" name="title" id="title" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="e.g., Work Brief, Personal Digest">
                        </div>
                        
                        <div>
                            <label for="timezone" class="block text-sm font-medium text-gray-700 mb-2">
                                Timezone
                            </label>
                            <select name="timezone" id="timezone" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <?php foreach ($timezones as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" <?php echo $value === 'UTC' ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="send_time" class="block text-sm font-medium text-gray-700 mb-2">
                                Send Time
                            </label>
                            <input type="time" name="send_time" id="send_time" value="06:00" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md font-medium transition-colors duration-200">
                            <i class="fas fa-plus mr-2"></i>
                            Create Newsletter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Existing Newsletters -->
        <div class="space-y-6">
            <h2 class="text-2xl font-bold text-gray-900">
                Your Newsletters (<?php echo count($newsletters); ?>)
            </h2>
            
            <?php if (empty($newsletters)): ?>
                <div class="bg-white rounded-lg shadow p-8 text-center">
                    <div class="text-gray-400 text-6xl mb-4">📰</div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No newsletters yet</h3>
                    <p class="text-gray-600">Create your first newsletter to get started with personalized morning briefings.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <?php foreach ($newsletters as $newsletter): ?>
                        <?php 
                        $scheduleStatus = $scheduler->getScheduleStatus($newsletter);
                        $sources = $newsletter->getSources();
                        $sourceCount = count($sources);
                        ?>
                        <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow duration-200 cursor-pointer" onclick="window.location.href='/dashboard/newsletter.php?id=<?php echo $newsletter->getId(); ?>'">
                            <!-- Newsletter Header -->
                            <div class="p-6 border-b border-gray-200">
                                <div class="flex justify-between items-start mb-3">
                                    <h3 class="text-lg font-semibold text-gray-900 flex-1 mr-2">
                                        <?php echo htmlspecialchars($newsletter->getTitle()); ?>
                                    </h3>
                                    <div class="flex space-x-1">
                                        <a href="/preview.php?newsletter_id=<?php echo $newsletter->getId(); ?>" 
                                           target="_blank"
                                           onclick="event.stopPropagation();"
                                           class="inline-flex items-center px-3 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full hover:bg-blue-200 transition-colors duration-200">
                                            <i class="fas fa-eye mr-1"></i>
                                            Preview
                                        </a>
                                        <?php if (count($newsletters) > 1): ?>
                                            <button onclick="event.stopPropagation(); deleteNewsletter(<?php echo $newsletter->getId(); ?>)" 
                                                    class="text-gray-400 hover:text-red-600 p-1">
                                                <i class="fas fa-trash text-sm"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="space-y-2 text-sm text-gray-600">
                                    <p class="flex items-center">
                                        <i class="fas fa-clock mr-2 text-blue-500"></i>
                                        <?php echo $newsletter->getSendTime(); ?> (<?php echo $newsletter->getTimezone(); ?>)
                                    </p>
                                    <p class="flex items-center">
                                        <i class="fas fa-calendar mr-2 text-green-500"></i>
                                        Next: <?php echo date('M j, g:i A', strtotime($scheduleStatus['next_send'])); ?>
                                    </p>
                                    <p class="flex items-center">
                                        <i class="fas fa-plug mr-2 text-purple-500"></i>
                                        <?php echo $sourceCount; ?> source<?php echo $sourceCount !== 1 ? 's' : ''; ?> configured
                                    </p>
                                </div>
                                
                                <?php if ($scheduleStatus['sent_today']): ?>
                                    <div class="mt-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        Sent today
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Newsletter Content -->
                            <div class="p-6">
                                
                                <?php if ($sourceCount > 0): ?>
                                    <div>
                                        <p class="text-xs font-medium text-gray-700 mb-2">Active Sources:</p>
                                        <div class="flex flex-wrap gap-1">
                                            <?php foreach (array_slice($sources, 0, 3) as $source): ?>
                                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded">
                                                    <?php echo htmlspecialchars($source['name'] ?: ucfirst($source['type'])); ?>
                                                </span>
                                            <?php endforeach; ?>
                                            <?php if ($sourceCount > 3): ?>
                                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded">
                                                    +<?php echo $sourceCount - 3; ?> more
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                                        <p class="text-sm text-yellow-800">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>
                                            No sources configured yet
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit Newsletter Modal -->
    <div id="editModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Edit Newsletter</h3>
                <form id="editForm" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($auth->generateCSRFToken()); ?>">
                    <input type="hidden" name="action" value="update_newsletter">
                    <input type="hidden" name="newsletter_id" id="editNewsletterIdInput">
                    
                    <div class="space-y-4">
                        <div>
                            <label for="editTitle" class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                            <input type="text" name="title" id="editTitle" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label for="editTimezone" class="block text-sm font-medium text-gray-700 mb-2">Timezone</label>
                            <select name="timezone" id="editTimezone" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <?php foreach ($timezones as $value => $label): ?>
                                    <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="editSendTime" class="block text-sm font-medium text-gray-700 mb-2">Send Time</label>
                            <input type="time" name="send_time" id="editSendTime" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end space-x-2">
                        <button type="button" onclick="closeEditModal()" 
                                class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md transition-colors duration-200">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors duration-200">
                            Update Newsletter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/dashboard.js"></script>
    <script>
        // Newsletter data for the edit modal
        const newsletters = <?php echo json_encode(array_map(function($newsletter) {
            return [
                'id' => $newsletter->getId(),
                'title' => $newsletter->getTitle(),
                'timezone' => $newsletter->getTimezone(),
                'send_time' => $newsletter->getSendTime()
            ];
        }, $newsletters)); ?>;
        
        function editNewsletter(newsletterId) {
            const newsletter = newsletters.find(n => n.id == newsletterId);
            if (!newsletter) return;
            
            document.getElementById('editNewsletterIdInput').value = newsletterId;
            document.getElementById('editTitle').value = newsletter.title;
            document.getElementById('editTimezone').value = newsletter.timezone;
            document.getElementById('editSendTime').value = newsletter.send_time;
            
            Dashboard.modal.open('editModal');
        }
        
        function closeEditModal() {
            Dashboard.modal.close('editModal');
        }
        
        function deleteNewsletter(newsletterId) {
            const newsletter = newsletters.find(n => n.id == newsletterId);
            if (!newsletter) return;
            
            Dashboard.form.submitWithConfirmation({
                csrf_token: '<?php echo htmlspecialchars($auth->generateCSRFToken()); ?>',
                action: 'delete_newsletter',
                newsletter_id: newsletterId
            }, `Are you sure you want to delete "${newsletter.title}"? This action cannot be undone and will delete all associated sources.`);
        }
        
        // Initialize modal functionality
        document.addEventListener('DOMContentLoaded', function() {
            Dashboard.modal.closeOnOutsideClick('editModal');
        });
    </script>
</body>
</html>