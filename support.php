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
                $success = 'Thank you for contacting us! We\'ll get back to you within 24 hours.';
                // Clear form fields
                $name = $email = $subject = $message = '';
            } else {
                $error = 'There was an error sending your message. Please try again or email us directly at hello@morningnewsletter.com';
            }
        } catch (Exception $e) {
            $error = 'There was an error sending your message. Please try again or email us directly at hello@morningnewsletter.com';
        }
    }
}

$csrfToken = $auth->generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support - MorningNewsletter</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        html {
            scroll-behavior: smooth;
        }
        .gradient-bg {
            background: linear-gradient(135deg, #ffffff 0%, #f3f4f6 100%);
        }
        .nav-scrolled {
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        .gradient-text {
            background: linear-gradient(135deg, #0041EC 0%, #9333ea 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body class="bg-white">
    <?php include __DIR__ . '/includes/navigation.php'; ?>

    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-blue-50 to-purple-50 py-20 pt-32">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-gray-900 mb-6">
                    Get <span class="gradient-text">Support</span>
                </h1>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Need help with your MorningNewsletter account? Have a question or suggestion? We're here to help you get the most out of your personalized morning briefings.
                </p>
            </div>
        </div>
    </div>

    <!-- Contact Options Section -->
    <div class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-envelope text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Email Support</h3>
                    <p class="text-gray-600 mb-4">Send us an email and we'll respond within 24 hours.</p>
                    <a href="mailto:hello@morningnewsletter.com" class="text-blue-600 hover:text-blue-700 font-medium">
                        hello@morningnewsletter.com
                    </a>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-question-circle text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Common Questions</h3>
                    <p class="text-gray-600 mb-4">Check our FAQ section for quick answers to common questions.</p>
                    <a href="/#faq" class="text-blue-600 hover:text-blue-700 font-medium">
                        View FAQ
                    </a>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-comments text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Contact Form</h3>
                    <p class="text-gray-600 mb-4">Fill out the form below and we'll get back to you quickly.</p>
                    <a href="#contact-form" class="text-blue-600 hover:text-blue-700 font-medium">
                        Send Message
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Form Section -->
    <div id="contact-form" class="py-20 bg-gray-50">
        <div class="max-w-3xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Send Us a Message</h2>
                <p class="text-xl text-gray-600">We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
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
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                Email Address *
                            </label>
                            <input type="email" id="email" name="email" required
                                   value="<?php echo htmlspecialchars($email ?? ''); ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        </div>
                    </div>
                    
                    <div>
                        <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">
                            Subject *
                        </label>
                        <select id="subject" name="subject" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
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
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-vertical"><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" 
                                class="btn-pill inline-flex items-center px-8 py-4 text-lg font-semibold text-white bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 transition-all duration-200 hover:scale-105 shadow-lg">
                            <i class="fas fa-paper-plane mr-3"></i>
                            Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Response Time Section -->
    <div class="py-16 bg-white">
        <div class="max-w-4xl mx-auto px-6 lg:px-8 text-center">
            <div class="bg-blue-50 rounded-2xl p-8">
                <h3 class="text-2xl font-bold text-gray-900 mb-4">We're Here to Help</h3>
                <p class="text-lg text-gray-600 mb-6">
                    Our support team typically responds within 24 hours during business days. 
                    For urgent issues, please email us directly at hello@morningnewsletter.com
                </p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-left">
                    <div class="flex items-start">
                        <i class="fas fa-clock text-blue-600 mt-1 mr-3"></i>
                        <div>
                            <h4 class="font-semibold text-gray-900">Response Time</h4>
                            <p class="text-gray-600">Within 24 hours on business days</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-globe text-blue-600 mt-1 mr-3"></i>
                        <div>
                            <h4 class="font-semibold text-gray-900">Support Hours</h4>
                            <p class="text-gray-600">Monday - Friday, 9 AM - 6 PM PST</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>