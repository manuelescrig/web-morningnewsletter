<?php require_once __DIR__ . '/logo.php'; ?>

<!-- Footer -->
<footer class="bg-gray-900 text-gray-400 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
                <?php renderLogo('md', '/', 'block'); ?>
                <p class="mt-4 text-gray-500 text-sm">Your personalized morning brief, delivered daily.</p>
                <div class="mt-6 flex space-x-6">
                    <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-github"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-white tracking-wider uppercase">Solutions</h3>
                <ul class="mt-4 space-y-4">
                    <li><a href="#" class="text-base text-gray-500 hover:text-gray-300">KPI Tracking</a></li>
                    <li><a href="#" class="text-base text-gray-500 hover:text-gray-300">Finance & Crypto</a></li>
                    <li><a href="#" class="text-base text-gray-500 hover:text-gray-300">Weather & News</a></li>
                    <li><a href="#" class="text-base text-gray-500 hover:text-gray-300">Social Media DMs</a></li>
                    <li><a href="#" class="text-base text-gray-500 hover:text-gray-300">Custom Integrations</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-white tracking-wider uppercase">Support</h3>
                <ul class="mt-4 space-y-4">
                    <li><a href="#" class="text-base text-gray-500 hover:text-gray-300">Submit Ticket</a></li>
                    <li><a href="#" class="text-base text-gray-500 hover:text-gray-300">Documentation</a></li>
                    <li><a href="#" class="text-base text-gray-500 hover:text-gray-300">Guides</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-white tracking-wider uppercase">Company</h3>
                <ul class="mt-4 space-y-4">
                    <li><a href="#" class="text-base text-gray-500 hover:text-gray-300">About</a></li>
                    <li><a href="#" class="text-base text-gray-500 hover:text-gray-300">Blog</a></li>
                    <li><a href="#" class="text-base text-gray-500 hover:text-gray-300">Jobs</a></li>
                    <li><a href="#" class="text-base text-gray-500 hover:text-gray-300">Press</a></li>
                </ul>
            </div>
        </div>
        <div class="mt-8 border-t border-gray-700 pt-8 flex flex-col sm:flex-row items-center justify-between">
            <p class="text-gray-500 text-sm">&copy; 2025 MorningNewsletter.com. All rights reserved.</p>
            <div class="flex space-x-6 mt-4 sm:mt-0">
                <a href="/legal/terms.php" class="text-gray-500 hover:text-gray-300 text-sm">Terms of Service</a>
                <a href="/legal/privacy.php" class="text-gray-500 hover:text-gray-300 text-sm">Privacy Policy</a>
            </div>
        </div>
    </div>
</footer>