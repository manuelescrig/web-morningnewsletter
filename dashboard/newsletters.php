<?php
/**
 * Newsletter Management Dashboard
 * 
 * This page allows users to manage multiple newsletters.
 * This demonstrates how to integrate the new multi-newsletter system with the UI.
 */

session_start();
require_once __DIR__ . '/../core/User.php';
require_once __DIR__ . '/../core/Newsletter.php';
require_once __DIR__ . '/../core/Scheduler.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

$user = User::findById($_SESSION['user_id']);
if (!$user) {
    header('Location: /auth/login.php');
    exit;
}

$message = '';
$messageType = '';

// Handle newsletter creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_newsletter') {
        $title = trim($_POST['title'] ?? '');
        $timezone = $_POST['timezone'] ?? 'UTC';
        $sendTime = $_POST['send_time'] ?? '06:00';
        
        if (empty($title)) {
            $message = 'Newsletter title is required.';
            $messageType = 'error';
        } else {
            try {
                $newsletterId = $user->createNewsletter($title, $timezone, $sendTime);
                if ($newsletterId) {
                    $message = "Newsletter '$title' created successfully!";
                    $messageType = 'success';
                } else {
                    $message = 'Failed to create newsletter.';
                    $messageType = 'error';
                }
            } catch (Exception $e) {
                $message = 'Error: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    } elseif ($_POST['action'] === 'update_newsletter') {
        $newsletterId = (int)$_POST['newsletter_id'];
        $newsletter = $user->getNewsletter($newsletterId);
        
        if ($newsletter) {
            $updateData = [
                'title' => trim($_POST['title'] ?? ''),
                'timezone' => $_POST['timezone'] ?? 'UTC',
                'send_time' => $_POST['send_time'] ?? '06:00'
            ];
            
            if ($newsletter->update($updateData)) {
                $message = 'Newsletter updated successfully!';
                $messageType = 'success';
            } else {
                $message = 'Failed to update newsletter.';
                $messageType = 'error';
            }
        } else {
            $message = 'Newsletter not found.';
            $messageType = 'error';
        }
    } elseif ($_POST['action'] === 'delete_newsletter') {
        $newsletterId = (int)$_POST['newsletter_id'];
        $newsletter = $user->getNewsletter($newsletterId);
        
        if ($newsletter) {
            if ($newsletter->delete()) {
                $message = 'Newsletter deleted successfully!';
                $messageType = 'success';
            } else {
                $message = 'Failed to delete newsletter.';
                $messageType = 'error';
            }
        } else {
            $message = 'Newsletter not found.';
            $messageType = 'error';
        }
    }
}

// Get user's newsletters
$newsletters = $user->getNewsletters();
$scheduler = new Scheduler();

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

$currentPage = 'dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Newsletters - MorningNewsletter</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?php include __DIR__ . '/includes/lucide-head.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/dashboard.css">
<body class="bg-gray-50">
    <?php include __DIR__ . '/includes/navigation.php'; ?>
    
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dashboard-title">My Newsletters</h1>
            <p class="mt-2 text-gray-600">Manage your personalized morning briefings</p>
        </div>

        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-md <?php echo $messageType === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Create New Newsletter -->
        <div class="bg-white rounded-lg shadow mb-8">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Create New Newsletter</h2>
            </div>
            <div class="p-6">
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="create_newsletter">
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Newsletter Title</label>
                            <input type="text" name="title" id="title" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus-ring-primary"
                                   placeholder="My Morning Brief">
                        </div>
                        
                        <div>
                            <label for="timezone" class="block text-sm font-medium text-gray-700 mb-2">Timezone</label>
                            <select name="timezone" id="timezone" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus-ring-primary">
                                <?php foreach ($timezones as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" <?php echo $value === 'UTC' ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="send_time" class="block text-sm font-medium text-gray-700 mb-2">Send Time</label>
                            <input type="time" name="send_time" id="send_time" value="06:00" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus-ring-primary">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-pill bg-primary hover-bg-primary-dark text-white px-6 py-2 font-medium">
                        Create Newsletter
                    </button>
                </form>
            </div>
        </div>

        <!-- Existing Newsletters -->
        <div class="space-y-6">
            <?php if (empty($newsletters)): ?>
                <div class="bg-white rounded-lg shadow p-8 text-center">
                    <div class="text-gray-400 text-6xl mb-4">📰</div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No newsletters yet</h3>
                    <p class="text-gray-600">Create your first newsletter to get started with personalized morning briefings.</p>
                </div>
            <?php else: ?>
                <?php foreach ($newsletters as $newsletter): ?>
                    <?php 
                    $scheduleStatus = $scheduler->getScheduleStatus($newsletter);
                    $sources = $newsletter->getSources();
                    ?>
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900"><?php echo htmlspecialchars($newsletter->getTitle()); ?></h3>
                                    <div class="mt-2 space-y-1">
                                        <p class="text-sm text-gray-600">
                                            <span class="font-medium">Schedule:</span> 
                                            <?php echo $newsletter->getSendTime(); ?> (<?php echo $newsletter->getTimezone(); ?>)
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            <span class="font-medium">Next send:</span> 
                                            <?php echo $scheduleStatus['next_send_formatted']; ?>
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            <span class="font-medium">Sources:</span> 
                                            <?php echo count($sources); ?> configured
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="flex space-x-2">
                                    <a href="/dashboard/sources.php?newsletter_id=<?php echo $newsletter->getId(); ?>" 
                                       class="bg-primary hover-bg-primary-dark text-white px-4 py-2 rounded-md text-sm font-medium">
                                        Manage Sources
                                    </a>
                                    <button onclick="editNewsletter(<?php echo $newsletter->getId(); ?>)" 
                                            class="btn-secondary-dark px-4 py-2 rounded-md text-sm font-medium">
                                        Edit
                                    </button>
                                    <button onclick="deleteNewsletter(<?php echo $newsletter->getId(); ?>)" 
                                            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($sources)): ?>
                            <div class="p-6">
                                <h4 class="text-sm font-medium text-gray-900 mb-3">Configured Sources</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                    <?php foreach ($sources as $source): ?>
                                        <div class="bg-gray-50 rounded-md p-3">
                                            <div class="font-medium text-sm text-gray-900">
                                                <?php echo htmlspecialchars($source['name'] ?: ucfirst($source['type'])); ?>
                                            </div>
                                            <div class="text-xs text-gray-600 mt-1">
                                                Type: <?php echo ucfirst($source['type']); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit Newsletter Modal (simplified) -->
    <div id="editModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Edit Newsletter</h3>
                <form id="editForm" method="POST">
                    <input type="hidden" name="action" value="update_newsletter">
                    <input type="hidden" name="newsletter_id" id="editNewsletterIdh">
                    <!-- Form fields would go here -->
                    <div class="mt-4 flex justify-end space-x-2">
                        <button type="button" onclick="closeEditModal()" 
                                class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md">Cancel</button>
                        <button type="submit" 
                                class="bg-primary hover-bg-primary-dark text-white px-4 py-2 rounded-md">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function editNewsletter(newsletterId) {
            // Implementation for edit modal
            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('editNewsletterIdh').value = newsletterId;
        }
        
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }
        
        function deleteNewsletter(newsletterId) {
            if (confirm('Are you sure you want to delete this newsletter? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_newsletter">
                    <input type="hidden" name="newsletter_id" value="${newsletterId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>

    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/dashboard.js"></script>
    <?php include __DIR__ . '/includes/lucide-init.php'; ?>
</body>
</html>