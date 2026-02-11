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
     * @param string|null $token Token to validate
     * @return bool
     */
    public static function validateToken(?string $token): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Generate token if it doesn't exist
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = self::generateToken();
        }

        if (empty($token)) {
            return false;
        }

        // Handle case where header might be duplicated (comma separated)
        if (strpos($token, ',') !== false) {
            $parts = explode(',', $token);
            $token = trim($parts[0]);
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

        // Check JSON input (for some AJAX requests)
        $input = file_get_contents('php://input');
        if (!empty($input)) {
            $data = json_decode($input, true);
            if (isset($data['csrf_token'])) {
                return $data['csrf_token'];
            }
        }

        // Check headers (for AJAX requests)
        $headerKeys = [
            'HTTP_X_CSRF_TOKEN',
            'HTTP_X_XSRF_TOKEN',
            'HTTP_CSRF_TOKEN'
        ];

        foreach ($headerKeys as $key) {
            if (isset($_SERVER[$key]) && !empty($_SERVER[$key])) {
                return $_SERVER[$key];
            }
        }

        // Try getting all headers if on Apache/Nginx (works in some environments where $_SERVER is limited)
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (isset($headers['X-CSRF-TOKEN'])) return $headers['X-CSRF-TOKEN'];
            if (isset($headers['X-CSRF-Token'])) return $headers['X-CSRF-Token'];
            if (isset($headers['X-XSRF-TOKEN'])) return $headers['X-XSRF-TOKEN'];
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
            $receivedToken = $token ?? 'NULL';
            $sessionToken = $_SESSION['csrf_token'] ?? 'NULL';
            
            error_log("CSRF FAILURE: Received [$receivedToken] vs Session [$sessionToken]");
            error_log("POST Keys: " . implode(', ', array_keys($_POST)));
            error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);

             // For AJAX requests, return JSON
             if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                 header('Content-Type: application/json');
                 echo json_encode(['success' => false, 'message' => 'Invalid CSRF Token', 'debug' => "Received: $receivedToken"]);
                 die();
            }
            
            throw new \Exception('Invalid CSRF Token');
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
