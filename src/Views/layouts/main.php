<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $config['app']['name'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="/" class="text-2xl font-bold text-indigo-600">
                            <?= $config['app']['name'] ?>
                        </a>
                    </div>
                    <?php if ($this->user): ?>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="/dashboard" class="border-transparent text-gray-500 hover:border-indigo-500 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Dashboard
                        </a>
                        <a href="/newsletters" class="border-transparent text-gray-500 hover:border-indigo-500 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Newsletters
                        </a>
                        <?php if ($this->user['is_admin']): ?>
                        <a href="/admin" class="border-transparent text-gray-500 hover:border-indigo-500 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Admin
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="hidden sm:ml-6 sm:flex sm:items-center">
                    <?php if ($this->user): ?>
                        <div class="ml-3 relative">
                            <div class="flex items-center">
                                <span class="text-gray-700 mr-4"><?= htmlspecialchars($this->user['name']) ?></span>
                                <a href="/logout" class="text-gray-500 hover:text-gray-700">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="/login" class="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium">Login</a>
                        <a href="/register" class="bg-indigo-600 text-white hover:bg-indigo-700 px-3 py-2 rounded-md text-sm font-medium">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="mb-4 p-4 rounded-md <?= $_SESSION['flash_type'] === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' ?>">
                <?= htmlspecialchars($_SESSION['flash_message']) ?>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <?= $content ?>
    </main>

    <!-- Footer -->
    <footer class="bg-white shadow-lg mt-8">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="text-center text-gray-500 text-sm">
                &copy; <?= date('Y') ?> <?= $config['app']['name'] ?>. All rights reserved.
            </div>
        </div>
    </footer>

    <script>
        // Add any JavaScript functionality here
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            const mobileMenuButton = document.querySelector('[data-mobile-menu]');
            const mobileMenu = document.querySelector('[data-mobile-menu-items]');
            
            if (mobileMenuButton && mobileMenu) {
                mobileMenuButton.addEventListener('click', () => {
                    mobileMenu.classList.toggle('hidden');
                });
            }
        });
    </script>
</body>
</html> 