<?php

namespace App\Core;

/**
 * CSRF Protection
 * 
 * Provides Cross-Site Request Forgery protection for forms and AJAX requests
 */
class CSRF
{
    /**
     * Generate CSRF token
     * 
     * @return string
     */
    public static function generateToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Validate CSRF token
     * 
     * @param string|null $token
     * @return bool
     */
    public static function validateToken(?string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Get token from request
     * 
     * Checks POST data, headers, and query string
     * 
     * @return string|null
     */
    public static function getTokenFromRequest(): ?string
    {
        // Check POST data
        if (isset($_POST['csrf_token'])) {
            return $_POST['csrf_token'];
        }

        // Check headers (for AJAX requests)
        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            return $_SERVER['HTTP_X_CSRF_TOKEN'];
        }

        // Check query string (not recommended, but supported)
        if (isset($_GET['csrf_token'])) {
            return $_GET['csrf_token'];
        }

        return null;
    }

    /**
     * Verify request has valid CSRF token
     * 
     * @throws \Exception if token is invalid
     * @return bool
     */
    public static function verifyRequest(): bool
    {
        $token = self::getTokenFromRequest();
        
        if (!self::validateToken($token)) {
            throw new \Exception('Invalid CSRF token', 403);
        }

        return true;
    }

    /**
     * Generate hidden input field with CSRF token
     * 
     * @return string
     */
    public static function field(): string
    {
        $token = self::generateToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Generate meta tag for AJAX requests
     * 
     * @return string
     */
    public static function metaTag(): string
    {
        $token = self::generateToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Regenerate CSRF token
     * 
     * Useful after login or sensitive operations
     * 
     * @return string
     */
    public static function regenerateToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }
}
