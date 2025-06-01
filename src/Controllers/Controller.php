<?php

class Controller {
    protected $db;
    protected $config;
    protected $user;

    public function __construct() {
        global $db, $config;
        $this->db = $db;
        $this->config = $config;
        $this->user = $this->getCurrentUser();
    }

    protected function getCurrentUser() {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);
        $result = $stmt->execute();
        
        return $result->fetchArray(SQLITE3_ASSOC);
    }

    protected function requireAuth() {
        if (!$this->user) {
            header('Location: /login');
            exit;
        }
    }

    protected function requireAdmin() {
        $this->requireAuth();
        if (!$this->user['is_admin']) {
            header('Location: /dashboard');
            exit;
        }
    }

    protected function render($view, $data = []) {
        extract($data);
        
        $viewPath = __DIR__ . '/../Views/' . $view . '.php';
        if (!file_exists($viewPath)) {
            throw new Exception("View {$view} not found");
        }

        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/main.php';
    }

    protected function json($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function redirect($url) {
        header("Location: {$url}");
        exit;
    }

    protected function validateCSRF() {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('Invalid CSRF token');
        }
    }

    protected function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
} 