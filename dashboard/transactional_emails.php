<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/User.php';
require_once __DIR__ . '/../core/TransactionalEmailManager.php';

// Check if user is logged in
$auth = Auth::getInstance();
if (!$auth->isLoggedIn()) {
    header('Location: /auth/login.php');
    exit;
}

$user = $auth->getCurrentUser();

// Check if user is admin
if (!$user->isAdmin()) {
    header('Location: /dashboard/');
    exit;
}

$currentPage = 'transactional_emails';
$transactionalManager = new TransactionalEmailManager();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $response = ['success' => false, 'message' => ''];
        
        switch ($_POST['action']) {
            case 'update_template':
                $templateId = $_POST['template_id'] ?? null;
                if ($templateId) {
                    $data = [
                        'name' => $_POST['name'] ?? '',
                        'subject' => $_POST['subject'] ?? '',
                        'html_template' => $_POST['html_template'] ?? '',
                        'is_enabled' => isset($_POST['is_enabled']) ? 1 : 0,
                        'trigger_event' => $_POST['trigger_event'] ?? null,
                        'delay_hours' => $_POST['delay_hours'] ?? 0,
                        'conditions' => $_POST['conditions'] ?? null
                    ];
                    
                    if ($transactionalManager->updateTemplate($templateId, $data)) {
                        $response['success'] = true;
                        $response['message'] = 'Template updated successfully';
                    } else {
                        $response['message'] = 'Failed to update template';
                    }
                }
                break;
                
            case 'send_test':
                $templateId = $_POST['template_id'] ?? null;
                $testEmail = $_POST['test_email'] ?? $user->getEmail();
                
                if ($templateId) {
                    $template = $transactionalManager->getTemplate($templateId);
                    if ($template) {
                        // Send test email using the template
                        require_once __DIR__ . '/../core/EmailSender.php';
                        $emailSender = new EmailSender();
                        
                        // Prepare test variables
                        $variables = [
                            'email' => $testEmail,
                            'name' => $user->getName(),
                            'first_name' => explode(' ', $user->getName())[0] ?: 'Test',
                            'verification_url' => 'https://example.com/verify?token=TEST',
                            'reset_url' => 'https://example.com/reset?token=TEST',
                            'current_email' => $user->getEmail(),
                            'new_email' => 'new@example.com',
                            'reactivation_url' => 'https://example.com/reactivate'
                        ];
                        
                        // Replace variables in template
                        $subject = $template['subject'];
                        $htmlContent = $template['html_template'];
                        
                        foreach ($variables as $key => $value) {
                            $subject = str_replace('{{' . $key . '}}', $value, $subject);
                            $htmlContent = str_replace('{{' . $key . '}}', $value, $htmlContent);
                        }
                        
                        // Send test email
                        $reflection = new ReflectionClass($emailSender);
                        $method = $reflection->getMethod('sendEmail');
                        $method->setAccessible(true);
                        
                        if ($method->invoke($emailSender, $testEmail, $subject, $htmlContent)) {
                            $response['success'] = true;
                            $response['message'] = 'Test email sent successfully to ' . $testEmail;
                        } else {
                            $response['message'] = 'Failed to send test email';
                        }
                    }
                }
                break;
        }
        
        // Return JSON response for AJAX requests
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
    }
}

// Get data for display
$templates = $transactionalManager->getTemplates();
$logs = $transactionalManager->getEmailLogs(50);
$queueItems = $transactionalManager->getQueueItems(null, 50);

// Available trigger events
$triggerEvents = [
    'user_registered' => 'User Registration',
    'email_verified' => 'Email Verified',
    'email_change_requested' => 'Email Change Verification',
    'password_reset' => 'Password Reset',
    'subscription_created' => 'Subscription Created',
    'subscription_cancelled' => 'Subscription Cancelled',
    'subscription_upgraded' => 'Subscription Upgraded',
    'subscription_downgraded' => 'Subscription Downgraded'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactional Emails - MorningNewsletter</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <style>
        .template-preview {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid var(--color-gray-200);
            border-radius: 0.5rem;
            background: var(--color-gray-50);
        }
        .template-editor {
            font-family: 'Monaco', 'Courier New', monospace;
            font-size: 0.875rem;
            line-height: 1.5;
        }
        .variable-tag {
            background: var(--pill-primary-bg-lightest);
            color: var(--color-primary);
            padding: 0.125rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-family: 'Monaco', 'Courier New', monospace;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include __DIR__ . '/includes/navigation.php'; ?>
    
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Transactional Emails</h1>
            <p class="text-gray-600 mt-2">Manage email templates and automated follow-up rules</p>
        </div>
        
        <!-- Tabs -->
        <div class="mb-6">
            <nav class="flex space-x-4" aria-label="Tabs">
                <button onclick="showTab('templates')" id="tab-templates" class="btn-pill tab-active px-4 py-2 text-sm font-medium">
                    Email Templates
                </button>
                <button onclick="showTab('logs')" id="tab-logs" class="btn-pill tab-inactive px-4 py-2 text-sm font-medium">
                    Email Logs
                </button>
                <button onclick="showTab('queue')" id="tab-queue" class="btn-pill tab-inactive px-4 py-2 text-sm font-medium">
                    Queue
                </button>
            </nav>
        </div>
        
        <!-- Templates Tab -->
        <div id="content-templates" class="tab-content">
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Email Templates</h2>
                </div>
                <div class="p-6 space-y-4">
                    <?php foreach ($templates as $template): ?>
                    <div class="border rounded-lg p-4">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($template['name']); ?></h3>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($template['description']); ?></p>
                                <div class="mt-2 flex items-center space-x-2">
                                    <span class="text-xs text-gray-500">Type: <code class="bg-gray-100 px-1 rounded"><?php echo htmlspecialchars($template['type']); ?></code></span>
                                    <?php if (!empty($template['trigger_event'])): ?>
                                    <span class="text-xs text-gray-500">
                                        <i class="fas fa-bolt mr-1"></i>
                                        Trigger: <?php echo htmlspecialchars($triggerEvents[$template['trigger_event']] ?? $template['trigger_event']); ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if (!empty($template['delay_hours']) && $template['delay_hours'] > 0): ?>
                                    <span class="text-xs text-gray-500">
                                        <i class="fas fa-clock mr-1"></i>
                                        Delay: <?php echo $template['delay_hours'] < 24 ? $template['delay_hours'] . 'h' : ($template['delay_hours'] / 24) . 'd'; ?>
                                    </span>
                                    <?php endif; ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $template['is_enabled'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <i class="fas fa-<?php echo $template['is_enabled'] ? 'check' : 'times'; ?> mr-1"></i>
                                        <?php echo $template['is_enabled'] ? 'Enabled' : 'Disabled'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <button onclick="editTemplate(<?php echo $template['id']; ?>)" class="btn-pill inline-flex items-center px-2 py-1 text-sm text-primary border border-primary-light hover:bg-primary-lightest">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="sendTestEmail(<?php echo $template['id']; ?>)" class="btn-pill inline-flex items-center px-2 py-1 text-sm text-green-600 border border-green-300 hover:bg-green-50">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Available Variables -->
                        <?php if ($template['variables']): ?>
                        <div class="mb-4">
                            <p class="text-sm font-medium text-gray-700 mb-2">Available Variables:</p>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach (json_decode($template['variables'], true) as $var): ?>
                                <span class="variable-tag"><?php echo htmlspecialchars('{{' . $var . '}}'); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Template Preview -->
                        <div class="template-preview p-4">
                            <div class="text-sm text-gray-700">
                                <strong>Subject:</strong> <?php echo htmlspecialchars($template['subject']); ?>
                            </div>
                            <div class="mt-2">
                                <iframe srcdoc="<?php echo htmlspecialchars($template['html_template']); ?>" 
                                        class="w-full h-64 border-0 rounded"></iframe>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Logs Tab -->
        <div id="content-logs" class="tab-content hidden">
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Email Logs</h2>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sent At</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Template</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M d, Y H:i', strtotime($log['sent_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($log['user_email']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($log['template_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <?php echo htmlspecialchars($log['subject']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="<?php echo $log['status'] === 'sent' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?> px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                            <?php echo ucfirst($log['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Queue Tab -->
        <div id="content-queue" class="tab-content hidden">
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Email Queue</h2>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Scheduled For</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Template</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rule</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Attempts</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($queueItems as $item): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M d, Y H:i', strtotime($item['scheduled_for'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($item['user_email']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($item['template_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($item['rule_name'] ?? 'Manual'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="<?php 
                                            echo $item['status'] === 'sent' ? 'bg-green-100 text-green-800' : 
                                                ($item['status'] === 'failed' ? 'bg-red-100 text-red-800' : 
                                                'bg-yellow-100 text-yellow-800'); 
                                        ?> px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                            <?php echo ucfirst($item['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $item['attempts']; ?>/3
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Template Modal -->
    <div id="edit-template-modal" class="hidden fixed z-10 inset-0 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <form id="edit-template-form" method="POST">
                    <input type="hidden" name="action" value="update_template">
                    <input type="hidden" name="template_id" id="edit-template-id">
                    
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Edit Email Template</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Template Name</label>
                                <input type="text" name="name" id="edit-template-name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus-ring-primary">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Subject</label>
                                <input type="text" name="subject" id="edit-template-subject" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus-ring-primary">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Trigger Event</label>
                                <select name="trigger_event" id="edit-template-trigger" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus-ring-primary">
                                    <option value="">No automatic trigger</option>
                                    <?php foreach ($triggerEvents as $value => $label): ?>
                                    <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="mt-1 text-sm text-gray-500">When this email should be automatically sent</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Delay (hours)</label>
                                <input type="number" name="delay_hours" id="edit-template-delay" min="0" value="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus-ring-primary">
                                <p class="mt-1 text-sm text-gray-500">0 for immediate, 24 for 1 day, 168 for 1 week</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Conditions (Optional)</label>
                                <textarea name="conditions" id="edit-template-conditions" rows="2" class="template-editor mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus-ring-primary" placeholder='{"subscription_plan": ["starter", "pro", "unlimited"]}'></textarea>
                                <p class="mt-1 text-sm text-gray-500">JSON conditions for when to send (leave empty for always)</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">HTML Template</label>
                                <textarea name="html_template" id="edit-template-html" rows="10" class="template-editor mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus-ring-primary"></textarea>
                            </div>
                            
                            <div class="flex items-center">
                                <input type="checkbox" name="is_enabled" id="edit-template-enabled" class="h-4 w-4 text-primary focus-ring-primary border-gray-300 rounded">
                                <label for="edit-template-enabled" class="ml-2 block text-sm text-gray-900">
                                    Enable this template
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="btn-pill w-full inline-flex justify-center border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover-bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus-ring-primary sm:ml-3 sm:w-auto sm:text-sm">
                            Save Changes
                        </button>
                        <button type="button" onclick="closeModal('edit-template-modal')" class="btn-pill mt-3 w-full inline-flex justify-center border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus-ring-primary sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/dashboard.js"></script>
    <script>
        // Tab functionality
        function showTab(tabName) {
            // Hide all content
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('[id^="tab-"]').forEach(tab => {
                tab.classList.remove('tab-active');
                tab.classList.add('tab-inactive');
            });
            
            // Show selected content
            document.getElementById('content-' + tabName).classList.remove('hidden');
            
            // Set active tab
            document.getElementById('tab-' + tabName).classList.remove('tab-inactive');
            document.getElementById('tab-' + tabName).classList.add('tab-active');
        }
        
        // Templates data for editing
        const templatesData = <?php echo json_encode($templates); ?>;
        
        // Edit template
        function editTemplate(templateId) {
            const template = templatesData.find(t => t.id == templateId);
            if (template) {
                document.getElementById('edit-template-id').value = template.id;
                document.getElementById('edit-template-name').value = template.name;
                document.getElementById('edit-template-subject').value = template.subject;
                document.getElementById('edit-template-html').value = template.html_template;
                document.getElementById('edit-template-enabled').checked = template.is_enabled == 1;
                document.getElementById('edit-template-trigger').value = template.trigger_event || '';
                document.getElementById('edit-template-delay').value = template.delay_hours || 0;
                document.getElementById('edit-template-conditions').value = template.conditions || '';
                
                document.getElementById('edit-template-modal').classList.remove('hidden');
            }
        }
        
        // Send test email
        function sendTestEmail(templateId) {
            const testEmail = prompt('Enter email address for test:', '<?php echo $user->getEmail(); ?>');
            if (testEmail) {
                const formData = new FormData();
                formData.append('action', 'send_test');
                formData.append('template_id', templateId);
                formData.append('test_email', testEmail);
                
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        MorningNewsletter.showAlert(data.message, 'success');
                    } else {
                        MorningNewsletter.showAlert(data.message || 'Failed to send test email', 'error');
                    }
                });
            }
        }
        
        
        // Close modal
        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }
        
        // Handle form submissions
        document.getElementById('edit-template-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    MorningNewsletter.showAlert(data.message || 'Failed to update template', 'error');
                }
            });
        });
        
        
        // Close modals on background click
        document.querySelectorAll('.fixed.z-10').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this || e.target.classList.contains('bg-gray-500')) {
                    this.classList.add('hidden');
                }
            });
        });
        
        // Tab styles - using CSS variables for consistency
        const style = document.createElement('style');
        style.textContent = `
            .tab-active {
                background-color: var(--color-primary) !important;
                color: var(--color-white) !important;
                border-color: var(--color-primary) !important;
            }
            .tab-inactive {
                background-color: var(--color-white);
                color: var(--color-gray-600);
                border: 1px solid var(--color-gray-200);
            }
            .tab-inactive:hover {
                background-color: var(--color-gray-50);
                color: var(--color-gray-800);
                border-color: var(--color-gray-300);
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>