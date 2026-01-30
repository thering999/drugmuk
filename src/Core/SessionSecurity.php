<?php

namespace App\Core;

/**
 * Session Security Manager
 * 
 * Provides secure session management with enhanced security features
 */
class SessionSecurity
{
    /**
     * Start secure session
     * 
     * @return void
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        // Configure secure session settings
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_samesite', 'Lax');
        
        // Enable secure cookie only if HTTPS
        if (self::isHTTPS()) {
            ini_set('session.cookie_secure', '1');
        }

        // Set session lifetime (1 hour)
        ini_set('session.gc_maxlifetime', '3600');
        ini_set('session.cookie_lifetime', '3600');

        // Use strong session ID
        ini_set('session.sid_length', '48');
        ini_set('session.sid_bits_per_character', '6');

        // Start session
        session_start();

        // Regenerate session ID periodically
        self::regenerateIfNeeded();

        // Check session timeout
        self::checkTimeout();

        // Validate session fingerprint
        self::validateFingerprint();
    }

    /**
     * Check if connection is HTTPS
     * 
     * @return bool
     */
    private static function isHTTPS(): bool
    {
        return (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        );
    }

    /**
     * Regenerate session ID if needed
     * 
     * @return void
     */
    private static function regenerateIfNeeded(): void
    {
        // Regenerate every 30 minutes
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }

    /**
     * Check session timeout
     * 
     * @return void
     */
    private static function checkTimeout(): void
    {
        $timeout = 3600; // 1 hour

        if (isset($_SESSION['last_activity'])) {
            $elapsed = time() - $_SESSION['last_activity'];
            
            if ($elapsed > $timeout) {
                self::destroy();
                throw new \Exception('Session expired', 401);
            }
        }

        $_SESSION['last_activity'] = time();
    }

    /**
     * Validate session fingerprint
     * 
     * @return void
     */
    private static function validateFingerprint(): void
    {
        $fingerprint = self::generateFingerprint();

        if (!isset($_SESSION['fingerprint'])) {
            $_SESSION['fingerprint'] = $fingerprint;
        } elseif ($_SESSION['fingerprint'] !== $fingerprint) {
            // Fingerprint mismatch - log warning but don't destroy session
            // This can happen legitimately due to browser updates, extensions, etc.
            error_log("WARNING: Session fingerprint mismatch for user: " . ($_SESSION['username'] ?? 'unknown'));
            error_log("Expected: " . $_SESSION['fingerprint']);
            error_log("Received: " . $fingerprint);
            
            // Update fingerprint instead of destroying session
            $_SESSION['fingerprint'] = $fingerprint;
            
            // Uncomment below to enforce strict validation (will log users out)
            // self::destroy();
            // throw new \Exception('Session validation failed', 403);
        }
    }

    /**
     * Generate session fingerprint
     * 
     * @return string
     */
    private static function generateFingerprint(): string
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        
        // Note: IP checking can be problematic with proxies/load balancers
        // Consider making this configurable
        return hash('sha256', $userAgent . $acceptLanguage);
    }

    /**
     * Regenerate session ID
     * 
     * Should be called after login or privilege escalation
     * 
     * @return void
     */
    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
            $_SESSION['fingerprint'] = self::generateFingerprint();
        }
    }

    /**
     * Destroy session
     * 
     * @return void
     */
    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];

            // Delete session cookie
            if (isset($_COOKIE[session_name()])) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }

            session_destroy();
        }
    }

    /**
     * Set session value
     * 
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function set(string $key, $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    /**
     * Get session value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Check if session has key
     * 
     * @param string $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    /**
     * Remove session value
     * 
     * @param string $key
     * @return void
     */
    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    /**
     * Flash message - set and retrieve once
     * 
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function flash(string $key, $value = null)
    {
        self::start();

        if ($value === null) {
            // Get and remove
            $flashValue = $_SESSION['_flash'][$key] ?? null;
            unset($_SESSION['_flash'][$key]);
            return $flashValue;
        }

        // Set
        $_SESSION['_flash'][$key] = $value;
    }

    /**
     * Get user ID from session
     * 
     * @return int|null
     */
    public static function getUserId(): ?int
    {
        return self::get('user_id');
    }

    /**
     * Check if user is logged in
     * 
     * @return bool
     */
    public static function isLoggedIn(): bool
    {
        return self::has('user_id');
    }

    /**
     * Set user as logged in
     * 
     * @param int $userId
     * @param array $userData
     * @return void
     */
    public static function login(int $userId, array $userData = []): void
    {
        self::regenerate();
        self::set('user_id', $userId);
        self::set('user_data', $userData);
        self::set('logged_in_at', time());
    }

    /**
     * Logout user
     * 
     * @return void
     */
    public static function logout(): void
    {
        self::destroy();
    }
}
