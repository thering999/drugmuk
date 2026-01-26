<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

class AuthController extends Controller {
    
    public function __construct() {
        // Start session if not already started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Show login form
     */
    public function login() {
        // If already logged in, redirect to dashboard
        if (isset($_SESSION['user_id'])) {
            header('Location: /dashboard');
            exit;
        }

        $error = $_SESSION['error'] ?? null;
        unset($_SESSION['error']);
        
        $this->view('auth/login', ['error' => $error]);
    }

    /**
     * Process login
     */
    public function authenticate() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /login');
            exit;
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validation
        if (empty($username) || empty($password)) {
            $_SESSION['error'] = 'กรุณากรอก Username และ Password';
            header('Location: /login');
            exit;
        }

        $userModel = new User();
        $user = $userModel->findByUsername($username);

        if ($user && password_verify($password, $user['password'])) {
            // Check if user is active
            if (!$user['is_active']) {
                $_SESSION['error'] = 'บัญชีผู้ใช้ถูกระงับการใช้งาน';
                header('Location: /login');
                exit;
            }

            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            
            // Update last login
            $userModel->updateLastLogin($user['id']);

            // Set success message
            $_SESSION['success'] = 'เข้าสู่ระบบสำเร็จ ยินดีต้อนรับ ' . $user['full_name'];
            
            // Redirect to dashboard
            header('Location: /dashboard');
            exit;
        } else {
            $_SESSION['error'] = 'Username หรือ Password ไม่ถูกต้อง';
            header('Location: /login');
            exit;
        }
    }

    /**
     * Logout
     */
    public function logout() {
        // Clear all session data
        $_SESSION = array();
        
        // Destroy session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy session
        session_destroy();
        
        // Redirect to login
        header('Location: /login');
        exit;
    }

    /**
     * Check if user is authenticated
     */
    public static function check() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user_id']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Require authentication
     */
    public static function requireAuth() {
        if (!self::check()) {
            $_SESSION['error'] = 'กรุณาเข้าสู่ระบบก่อน';
            header('Location: /login');
            exit;
        }
    }
}
