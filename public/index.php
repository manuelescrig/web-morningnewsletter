<?php

session_start();

// Load configuration
$config = require_once __DIR__ . '/../config/config.php';

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', $config['app']['debug'] ? 1 : 0);

// Autoloader
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/../src/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// Database connection
try {
    $db = new SQLite3($config['database']['database']);
} catch (Exception $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// Router
$request = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Remove query string and trailing slash
$request = strtok($request, '?');
$request = rtrim($request, '/');

// Basic routing
switch ($request) {
    case '':
    case '/':
        require __DIR__ . '/../src/Controllers/HomeController.php';
        $controller = new HomeController();
        $controller->index();
        break;

    case '/login':
        require __DIR__ . '/../src/Controllers/AuthController.php';
        $controller = new AuthController();
        if ($method === 'POST') {
            $controller->login();
        } else {
            $controller->showLogin();
        }
        break;

    case '/register':
        require __DIR__ . '/../src/Controllers/AuthController.php';
        $controller = new AuthController();
        if ($method === 'POST') {
            $controller->register();
        } else {
            $controller->showRegister();
        }
        break;

    case '/dashboard':
        require __DIR__ . '/../src/Controllers/DashboardController.php';
        $controller = new DashboardController();
        $controller->index();
        break;

    case '/newsletters':
        require __DIR__ . '/../src/Controllers/NewsletterController.php';
        $controller = new NewsletterController();
        if ($method === 'POST') {
            $controller->create();
        } else {
            $controller->index();
        }
        break;

    case '/newsletters/create':
        require __DIR__ . '/../src/Controllers/NewsletterController.php';
        $controller = new NewsletterController();
        $controller->showCreate();
        break;

    case '/newsletters/preview':
        require __DIR__ . '/../src/Controllers/NewsletterController.php';
        $controller = new NewsletterController();
        $controller->preview();
        break;

    case '/admin':
        require __DIR__ . '/../src/Controllers/AdminController.php';
        $controller = new AdminController();
        $controller->index();
        break;

    default:
        http_response_code(404);
        require __DIR__ . '/../src/Views/404.php';
        break;
} 