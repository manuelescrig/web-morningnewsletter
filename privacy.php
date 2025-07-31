<?php
require_once __DIR__ . '/core/Auth.php';

$auth = Auth::getInstance();
$isLoggedIn = $auth->isLoggedIn();
$user = $isLoggedIn ? $auth->getCurrentUser() : null;

// Page configuration
$pageTitle = "Privacy Policy";
$pageDescription = "Learn how MorningNewsletter protects and respects your personal data.";
include __DIR__ . '/includes/page-header.php';
?>
<body class="bg-white">
    <?php include __DIR__ . '/includes/navigation.php'; ?>
    
    <?php 
    // Hero section configuration
    $heroTitle = "Privacy Policy";
    $heroSubtitle = "How I handle and protect your personal data";
    include __DIR__ . '/includes/hero-section.php';
    ?>

    <?php include __DIR__ . '/includes/content-wrapper-start.php'; ?>
            <p class="text-gray-500 mb-8 text-sm">Last updated: June 23, 2025</p>

            <div class="prose prose-lg max-w-none">
                <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">1. Introduction</h2>
                <p class="text-gray-700 mb-4">
                    MorningNewsletter.com ("I," "me," or "my") respects your privacy and is committed to protecting your personal data. This Privacy Policy explains how I collect, use, disclose, and safeguard your information when you use this website and services.
                </p>

                <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">2. Information I Collect</h2>
                
                <h3 class="text-xl font-bold text-gray-900 mt-6 mb-3">2.1 Personal Information</h3>
                <p class="text-gray-700 mb-4">
                    I collect information you provide directly to me, including:
                </p>
                <ul class="list-disc list-inside text-gray-700 mb-4 ml-4">
                    <li>Email address (required for account creation and service delivery)</li>
                    <li>Name and profile information (optional)</li>
                    <li>Billing information for paid subscriptions</li>
                    <li>Communication preferences and settings</li>
                    <li>Custom integrations and API credentials you provide</li>
                </ul>

                <h3 class="text-xl font-bold text-gray-900 mt-6 mb-3">2.2 Usage Information</h3>
                <p class="text-gray-700 mb-4">
                    I automatically collect certain information about your use of the Service:
                </p>
                <ul class="list-disc list-inside text-gray-700 mb-4 ml-4">
                    <li>Log data (IP address, browser type, operating system)</li>
                    <li>Device information and identifiers</li>
                    <li>Usage patterns and feature interactions</li>
                    <li>Email engagement metrics (opens, clicks)</li>
                    <li>Performance and error data</li>
                </ul>

                <h3 class="text-xl font-bold text-gray-900 mt-6 mb-3">2.3 Third-Party Data</h3>
                <p class="text-gray-700 mb-4">
                    With your permission, I access data from third-party services you connect:
                </p>
                <ul class="list-disc list-inside text-gray-700 mb-4 ml-4">
                    <li>Social media platforms (Twitter, LinkedIn, etc.)</li>
                    <li>Financial and cryptocurrency services</li>
                    <li>Weather and news APIs</li>
                    <li>Business analytics and KPI tools</li>
                    <li>Communication platforms (Slack, Discord)</li>
                </ul>

                <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">3. How I Use Your Information</h2>
                <p class="text-gray-700 mb-4">
                    I use your information to:
                </p>
                <ul class="list-disc list-inside text-gray-700 mb-4 ml-4">
                    <li>Provide and deliver the personalized newsletter service</li>
                    <li>Process payments and manage subscriptions</li>
                    <li>Personalize content and improve the algorithms</li>
                    <li>Send service-related communications and updates</li>
                    <li>Provide customer support and respond to inquiries</li>
                    <li>Analyze usage patterns and improve the Service</li>
                    <li>Detect and prevent fraud and abuse</li>
                    <li>Comply with legal obligations</li>
                </ul>

                <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">4. Information Sharing and Disclosure</h2>
                <p class="text-gray-700 mb-4">
                    I do not sell, trade, or rent your personal information to third parties. I may share your information in limited circumstances:
                </p>

                <h3 class="text-xl font-bold text-gray-900 mt-6 mb-3">4.1 Service Providers</h3>
                <p class="text-gray-700 mb-4">
                    I work with trusted third-party service providers who assist me in operating the Service:
                </p>
                <ul class="list-disc list-inside text-gray-700 mb-4 ml-4">
                    <li>Email delivery services</li>
                    <li>Payment processors</li>
                    <li>Cloud hosting providers</li>
                    <li>Analytics and monitoring tools</li>
                    <li>Customer support platforms</li>
                </ul>

                <h3 class="text-xl font-bold text-gray-900 mt-6 mb-3">4.2 Legal Requirements</h3>
                <p class="text-gray-700 mb-4">
                    I may disclose your information if required by law or in response to valid legal processes, such as court orders or government requests.
                </p>

                <h3 class="text-xl font-bold text-gray-900 mt-6 mb-3">4.3 Business Transfers</h3>
                <p class="text-gray-700 mb-4">
                    In the event of a merger, acquisition, or sale of assets, your information may be transferred as part of that transaction.
                </p>

                <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">5. Data Security</h2>
                <p class="text-gray-700 mb-4">
                    I implement appropriate technical and organizational measures to protect your personal information:
                </p>
                <ul class="list-disc list-inside text-gray-700 mb-4 ml-4">
                    <li>Encryption of data in transit and at rest</li>
                    <li>Regular security assessments and monitoring</li>
                    <li>Access controls and authentication measures</li>
                    <li>Secure coding practices and regular updates</li>
                    <li>Incident response and breach notification procedures</li>
                </ul>

                <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">6. Data Retention</h2>
                <p class="text-gray-700 mb-4">
                    I retain your personal information for as long as necessary to provide the Service and fulfill the purposes outlined in this Privacy Policy. When you delete your account, I will delete your personal information within 30 days, except where I'm required to retain it for legal compliance.
                </p>

                <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">7. Your Privacy Rights</h2>
                <p class="text-gray-700 mb-4">
                    Depending on your location, you may have certain rights regarding your personal information:
                </p>
                <ul class="list-disc list-inside text-gray-700 mb-4 ml-4">
                    <li><strong>Access:</strong> Request a copy of your personal information</li>
                    <li><strong>Correction:</strong> Update or correct inaccurate information</li>
                    <li><strong>Deletion:</strong> Request deletion of your personal information</li>
                    <li><strong>Portability:</strong> Request transfer of your data</li>
                    <li><strong>Restriction:</strong> Limit how I process your information</li>
                    <li><strong>Objection:</strong> Object to certain types of processing</li>
                </ul>

                <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">8. Cookies and Tracking Technologies</h2>
                <p class="text-gray-700 mb-4">
                    I use cookies and similar technologies to:
                </p>
                <ul class="list-disc list-inside text-gray-700 mb-4 ml-4">
                    <li>Remember your preferences and settings</li>
                    <li>Authenticate your identity</li>
                    <li>Analyze website usage and performance</li>
                    <li>Provide personalized content and features</li>
                </ul>
                <p class="text-gray-700 mb-4">
                    You can control cookies through your browser settings, but disabling them may affect Service functionality.
                </p>

                <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">9. International Data Transfers</h2>
                <p class="text-gray-700 mb-4">
                    Your information may be transferred to and processed in countries other than your own. I ensure appropriate safeguards are in place to protect your information during international transfers.
                </p>

                <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">10. Children's Privacy</h2>
                <p class="text-gray-700 mb-4">
                    The Service is not intended for children under 13 years of age. I do not knowingly collect personal information from children under 13. If I become aware that I have collected such information, I will delete it promptly.
                </p>

                <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">11. Changes to This Privacy Policy</h2>
                <p class="text-gray-700 mb-4">
                    I may update this Privacy Policy from time to time. I will notify you of any material changes by email or through the Service. The updated policy will be effective when posted.
                </p>

                <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">12. Contact Me</h2>
                <p class="text-gray-700 mb-4">
                    If you have any questions about this Privacy Policy or my privacy practices, please contact me:
                </p>
                <p class="text-gray-700 mb-4">
                    Email: hello@morningnewsletter.com<br>
                    Website: MorningNewsletter.com<br>
                    Subject Line: Privacy Policy Inquiry
                </p>
                <p class="text-gray-700 mb-4">
                    For data protection matters in the EU, you can also contact your local data protection authority.
                </p>
            </div>
    <?php include __DIR__ . '/includes/content-wrapper-end.php'; ?>

<?php include __DIR__ . '/includes/page-footer.php'; ?>