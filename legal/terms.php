<?php
require_once __DIR__ . '/../core/Auth.php';

$auth = Auth::getInstance();
$isLoggedIn = $auth->isLoggedIn();
$user = $isLoggedIn ? $auth->getCurrentUser() : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - MorningNewsletter</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .nav-scrolled {
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include __DIR__ . '/../includes/navigation.php'; ?>

    <!-- Content -->
    <div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8 pt-24">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-8">Terms of Service</h1>
            <p class="text-gray-600 mb-8">Last updated: June 23, 2025</p>

            <div class="prose max-w-none">
                <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">1. Agreement to Terms</h2>
                <p class="text-gray-700 mb-4">
                    By accessing and using MorningNewsletter.com (the "Service"), you accept and agree to be bound by the terms and provision of this agreement. These Terms of Service ("Terms") govern your use of our website and services provided by MorningNewsletter.com ("we," "us," or "our").
                </p>

                <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">2. Description of Service</h2>
                <p class="text-gray-700 mb-4">
                    MorningNewsletter is a personalized morning brief service that aggregates and delivers customized content including KPIs, financial data, weather updates, news, and social media messages via email.
                </p>

                <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">3. User Accounts</h2>
                <p class="text-gray-700 mb-4">
                    To access certain features of our Service, you must register for an account. You agree to:
                </p>
                <ul class="list-disc list-inside text-gray-700 mb-4 ml-4">
                    <li>Provide accurate, current, and complete information during registration</li>
                    <li>Maintain and update your account information</li>
                    <li>Maintain the security of your password and account</li>
                    <li>Accept responsibility for all activities under your account</li>
                </ul>

                <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">4. Subscription Plans and Billing</h2>
                <p class="text-gray-700 mb-4">
                    We offer various subscription plans with different features and pricing. By subscribing to a paid plan, you agree to:
                </p>
                <ul class="list-disc list-inside text-gray-700 mb-4 ml-4">
                    <li>Pay all applicable fees for your chosen subscription plan</li>
                    <li>Automatic renewal of your subscription unless cancelled</li>
                    <li>Our right to change pricing with 30 days advance notice</li>
                    <li>No refunds for partial months of service</li>
                </ul>

                <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">5. Acceptable Use</h2>
                <p class="text-gray-700 mb-4">
                    You agree not to:
                </p>
                <ul class="list-disc list-inside text-gray-700 mb-4 ml-4">
                    <li>Use the Service for any unlawful purpose or illegal activity</li>
                    <li>Share your account credentials with others</li>
                    <li>Attempt to gain unauthorized access to our systems</li>
                    <li>Interfere with or disrupt the Service or servers</li>
                    <li>Use automated systems to access the Service without permission</li>
                    <li>Reverse engineer or attempt to extract source code</li>
                </ul>

                <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">6. Content and Data</h2>
                <p class="text-gray-700 mb-4">
                    Our Service aggregates content from various sources. We do not guarantee the accuracy, completeness, or timeliness of any content. You acknowledge that:
                </p>
                <ul class="list-disc list-inside text-gray-700 mb-4 ml-4">
                    <li>Content is provided for informational purposes only</li>
                    <li>You should not rely solely on our content for financial or business decisions</li>
                    <li>We may modify or discontinue content sources at any time</li>
                    <li>We respect intellectual property rights of content providers</li>
                </ul>

                <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">7. Privacy and Data Protection</h2>
                <p class="text-gray-700 mb-4">
                    Your privacy is important to us. Our collection and use of personal information is governed by our Privacy Policy, which is incorporated into these Terms by reference.
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
                    We may terminate or suspend your account immediately, without prior notice, for conduct that we believe violates these Terms or is harmful to other users, us, or third parties.
                </p>

                <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">11. Changes to Terms</h2>
                <p class="text-gray-700 mb-4">
                    We reserve the right to modify these Terms at any time. We will notify users of significant changes via email or through the Service. Continued use after changes constitutes acceptance of the new Terms.
                </p>

                <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">12. Governing Law</h2>
                <p class="text-gray-700 mb-4">
                    These Terms shall be governed by and construed in accordance with the laws of the jurisdiction where MorningNewsletter.com is incorporated, without regard to conflict of law provisions.
                </p>

                <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">13. Contact Information</h2>
                <p class="text-gray-700 mb-4">
                    If you have any questions about these Terms of Service, please contact us at:
                </p>
                <p class="text-gray-700 mb-4">
                    Email: legal@morningnewsletter.com<br>
                    Website: MorningNewsletter.com
                </p>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>