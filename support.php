<?php
require_once __DIR__ . '/core/Auth.php';

$auth = Auth::getInstance();
$isLoggedIn = $auth->isLoggedIn();
$user = $isLoggedIn ? $auth->getCurrentUser() : null;

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!$auth->validateCSRFToken($csrfToken)) {
        $error = 'Invalid request. Please try again.';
    } elseif (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Prepare email content
        $emailSubject = "Support Request: " . $subject;
        $emailBody = "Name: {$name}\nEmail: {$email}\nSubject: {$subject}\n\nMessage:\n{$message}";
        
        // Send email using the EmailSender
        try {
            require_once __DIR__ . '/core/EmailSender.php';
            $emailSender = new EmailSender();
            
            $result = $emailSender->sendEmail(
                'hello@morningnewsletter.com',
                $emailSubject,
                $emailBody
            );
            
            if ($result['success']) {
                $success = 'Thank you for contacting me! I\'ll get back to you within 24 hours.';
                // Clear form fields
                $name = $email = $subject = $message = '';
            } else {
                $error = 'There was an error sending your message. Please try again or email me directly at hello@morningnewsletter.com';
            }
        } catch (Exception $e) {
            $error = 'There was an error sending your message. Please try again or email us directly at hello@morningnewsletter.com';
        }
    }
}

$csrfToken = $auth->generateCSRFToken();

// Page configuration
$pageTitle = "Support";
$pageDescription = "Get help with MorningNewsletter. I'm here to assist you.";
include __DIR__ . '/includes/page-header.php';
?>
<body class="bg-white">
    <?php include __DIR__ . '/includes/navigation.php'; ?>
    
    <?php 
    // Hero section configuration
    $heroTitle = "How Can I Help?";
    $heroSubtitle = "Find help here";
    include __DIR__ . '/includes/hero-section.php';
    ?>

    <!-- Contact Form Section -->
    <div id="contact-form" class="py-20 bg-gray-50">
        <div class="max-w-3xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Send Me a Message</h2>
                <p class="text-xl text-gray-600 mb-6">I'd love to hear from you. Send me a message and I'll respond as soon as possible.</p>
                
                <div class="inline-flex items-center bg-blue-50 border border-blue-200 text-blue-700 px-5 py-3 rounded-lg">
                    <i class="fas fa-info-circle mr-2"></i>
                    Check the <a href="/#faq" class="text-blue-700 hover:text-blue-800 font-medium underline">FAQ section</a> for quick answers to common questions.
                </div>
            </div>
            
            <div class="bg-white rounded-2xl p-8 shadow-lg">
                <?php if ($success): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-6">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                Name *
                            </label>
                            <input type="text" id="name" name="name" required
                                   value="<?php echo htmlspecialchars($name ?? ''); ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus-ring-primary transition-colors">
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                Email Address *
                            </label>
                            <input type="email" id="email" name="email" required
                                   value="<?php echo htmlspecialchars($email ?? ''); ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus-ring-primary transition-colors">
                        </div>
                    </div>
                    
                    <div>
                        <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">
                            Subject *
                        </label>
                        <select id="subject" name="subject" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus-ring-primary transition-colors">
                            <option value="">Select a subject</option>
                            <option value="Account Help" <?php echo (($subject ?? '') === 'Account Help') ? 'selected' : ''; ?>>Account Help</option>
                            <option value="Billing Question" <?php echo (($subject ?? '') === 'Billing Question') ? 'selected' : ''; ?>>Billing Question</option>
                            <option value="Technical Issue" <?php echo (($subject ?? '') === 'Technical Issue') ? 'selected' : ''; ?>>Technical Issue</option>
                            <option value="Feature Request" <?php echo (($subject ?? '') === 'Feature Request') ? 'selected' : ''; ?>>Feature Request</option>
                            <option value="General Question" <?php echo (($subject ?? '') === 'General Question') ? 'selected' : ''; ?>>General Question</option>
                            <option value="Other" <?php echo (($subject ?? '') === 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-700 mb-2">
                            Message *
                        </label>
                        <textarea id="message" name="message" rows="6" required
                                  placeholder="Please describe your question or issue in detail..."
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus-ring-primary transition-colors resize-vertical"><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" 
                                class="inline-flex items-center px-6 py-3 text-base font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-full transition-colors">
                            <i class="fas fa-paper-plane mr-2"></i>
                            Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


<?php include __DIR__ . '/includes/page-footer.php'; ?>