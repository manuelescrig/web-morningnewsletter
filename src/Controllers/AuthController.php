<?php

class AuthController extends Controller {
    public function showLogin() {
        if ($this->user) {
            $this->redirect('/dashboard');
        }
        $this->render('auth/login', [
            'title' => 'Login',
            'csrf_token' => $this->generateCSRFToken()
        ]);
    }

    public function showRegister() {
        if ($this->user) {
            $this->redirect('/dashboard');
        }
        $this->render('auth/register', [
            'title' => 'Register',
            'csrf_token' => $this->generateCSRFToken()
        ]);
    }

    public function login() {
        try {
            $this->validateCSRF();

            $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
            $password = $_POST['password'] ?? '';

            if (!$email) {
                throw new Exception('Invalid email address');
            }

            $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email');
            $stmt->bindValue(':email', $email, SQLITE3_TEXT);
            $result = $stmt->execute();
            $user = $result->fetchArray(SQLITE3_ASSOC);

            if (!$user || !password_verify($password, $user['password'])) {
                throw new Exception('Invalid email or password');
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['flash_message'] = 'Welcome back!';
            $_SESSION['flash_type'] = 'success';

            $this->redirect('/dashboard');
        } catch (Exception $e) {
            $_SESSION['flash_message'] = $e->getMessage();
            $_SESSION['flash_type'] = 'error';
            $this->redirect('/login');
        }
    }

    public function register() {
        try {
            $this->validateCSRF();

            $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
            $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
            $password = $_POST['password'] ?? '';
            $password_confirm = $_POST['password_confirm'] ?? '';

            if (!$name || !$email) {
                throw new Exception('Please fill in all required fields');
            }

            if (strlen($password) < $this->config['security']['password_min_length']) {
                throw new Exception('Password must be at least ' . $this->config['security']['password_min_length'] . ' characters long');
            }

            if ($password !== $password_confirm) {
                throw new Exception('Passwords do not match');
            }

            // Check if email already exists
            $stmt = $this->db->prepare('SELECT id FROM users WHERE email = :email');
            $stmt->bindValue(':email', $email, SQLITE3_TEXT);
            $result = $stmt->execute();
            if ($result->fetchArray()) {
                throw new Exception('Email address already registered');
            }

            // Create user
            $stmt = $this->db->prepare('INSERT INTO users (name, email, password) VALUES (:name, :email, :password)');
            $stmt->bindValue(':name', $name, SQLITE3_TEXT);
            $stmt->bindValue(':email', $email, SQLITE3_TEXT);
            $stmt->bindValue(':password', password_hash($password, PASSWORD_DEFAULT), SQLITE3_TEXT);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to create account');
            }

            $_SESSION['user_id'] = $this->db->lastInsertRowID();
            $_SESSION['flash_message'] = 'Account created successfully!';
            $_SESSION['flash_type'] = 'success';

            $this->redirect('/dashboard');
        } catch (Exception $e) {
            $_SESSION['flash_message'] = $e->getMessage();
            $_SESSION['flash_type'] = 'error';
            $this->redirect('/register');
        }
    }

    public function logout() {
        session_destroy();
        $this->redirect('/');
    }
} 