<?php
require_once __DIR__ . '/core/Auth.php';

$auth = Auth::getInstance();
$isLoggedIn = $auth->isLoggedIn();
$user = $isLoggedIn ? $auth->getCurrentUser() : null;

// Page configuration
$pageTitle = "Terms of Service";
$pageDescription = "Read the terms and conditions for using MorningNewsletter's services.";
include __DIR__ . '/includes/page-header.php';
?>
<body class="bg-white">
    <?php include __DIR__ . '/includes/navigation.php'; ?>
    
    <?php 
    // Hero section configuration
    $heroTitle = "Terms of Service";
    $heroSubtitle = "The legal agreement for using MorningNewsletter";
    include __DIR__ . '/includes/hero-section.php';
    ?>

    <?php include __DIR__ . '/includes/content-wrapper-start.php'; ?>
            <p class="text-gray-500 mb-8 text-sm">Last updated: June 23, 2025</p>

            <div class="prose prose-lg max-w-none">
                <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4 gradient-text">1. Agreement to Terms</h2>
                <p class="text-gray-700 mb-4">
                    By accessing and using MorningNewsletter.com (the "Service"), you accept and agree to be bound by the terms and provision of this agreement. These Terms of Service ("Terms") govern your use of this website and services provided by MorningNewsletter.com ("I," "me," or "my").
                </p>

                <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">2. Description of Service</h2>
                <p class="text-gray-700 mb-4">
                    MorningNewsletter is a personalized morning brief service that aggregates and delivers customized content including KPIs, financial data, weather updates, news, and social media messages via email.
                </p>

                <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">3. User Accounts</h2>
                <p class="text-gray-700 mb-4">
                    To access certain features of the Service, you must register for an account. You agree to:
                </p>
                <ul class="list-disc list-inside text-gray-700 mb-4 ml-4">
                    <li>Provide accurate, current, and complete information during registration</li>
                    <li>Maintain and update your account information</li>
                    <li>Maintain the security of your password and account</li>
                    <li>Accept responsibility for all activities under your account</li>
                </ul>

                <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">4. Subscription Plans and Billing</h2>
                <p class="text-gray-700 mb-4">
                    I offer various subscription plans with different features and pricing. By subscribing to a paid plan, you agree to:
                </p>
                <ul class="list-disc list-inside text-gray-700 mb-4 ml-4">
                    <li>Pay all applicable fees for your chosen subscription plan</li>
                    <li>Automatic renewal of your subscription unless cancelled</li>
                    <li>My right to change pricing with 30 days advance notice</li>
                    <li>No refunds for partial months of service</li>
                </ul>

                <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">5. Acceptable Use</h2>
                <p class="text-gray-700 mb-4">
                    You agree not to:
                </p>
                <ul class="list-disc list-inside text-gray-700 mb-4 ml-4">
                    <li>Use the Service for any unlawful purpose or illegal activity</li>
                    <li>Share your account credentials with others</li>
                    <li>Attempt to gain unauthorized access to my systems</li>
                    <li>Interfere with or disrupt the Service or servers</li>
                    <li>Use automated systems to access the Service without permission</li>
                    <li>Reverse engineer or attempt to extract source code</li>
                </ul>

                <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">6. Content and Data</h2>
                <p class="text-gray-700 mb-4">
                    The Service aggregates content from various sources. I do not guarantee the accuracy, completeness, or timeliness of any content. You acknowledge that:
                </p>
                <ul class="list-disc list-inside text-gray-700 mb-4 ml-4">
                    <li>Content is provided for informational purposes only</li>
                    <li>You should not rely solely on the content for financial or business decisions</li>
                    <li>I may modify or discontinue content sources at any time</li>
                    <li>I respect intellectual property rights of content providers</li>
                </ul>

                <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">7. Privacy and Data Protection</h2>
                <p class="text-gray-700 mb-4">
                    Your privacy is important to me. My collection and use of personal information is governed by the Privacy Policy, which is incorporated into these Terms by reference.
                </p>

                <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">8. Intellectual Property</h2>
                <p class="text-gray-700 mb-4">
                    The Service and its original content, features, and functionality are owned by MorningNewsletter.com and are protected by international copyright, trademark, patent, trade secret, and other intellectual property laws.
                </p>

                <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">9. Disclaimers and Limitation of Liability</h2>
                <p class="text-gray-700 mb-4">
                    THE SERVICE IS PROVIDED "AS IS" WITHOUT WARRANTIES OF ANY KIND. TO THE MAXIMUM EXTENT PERMITTED BY LAW, WE DISCLAIM ALL WARRANTIES, EXPRESS OR IMPLIED. WE SHALL NOT BE LIABLE FOR ANY INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL, OR PUNITIVE DAMAGES.
                </p>

                <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">10. Termination</h2>
                <p class="text-gray-700 mb-4">
                    I may terminate or suspend your account immediately, without prior notice, for conduct that I believe violates these Terms or is harmful to other users, me, or third parties.
                </p>

                <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">11. Changes to Terms</h2>
                <p class="text-gray-700 mb-4">
                    I reserve the right to modify these Terms at any time. I will notify users of significant changes via email or through the Service. Continued use after changes constitutes acceptance of the new Terms.
                </p>

                <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">12. Governing Law</h2>
                <p class="text-gray-700 mb-4">
                    These Terms shall be governed by and construed in accordance with the laws of the jurisdiction where MorningNewsletter.com is incorporated, without regard to conflict of law provisions.
                </p>

                <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">13. Contact Information</h2>
                <p class="text-gray-700 mb-4">
                    If you have any questions about these Terms of Service, please contact me at:
                </p>
                <p class="text-gray-700 mb-4">
                    Email: hello@morningnewsletter.com<br>
                    Website: MorningNewsletter.com
                </p>
            </div>
    <?php include __DIR__ . '/includes/content-wrapper-end.php'; ?>

<?php include __DIR__ . '/includes/page-footer.php'; ?>