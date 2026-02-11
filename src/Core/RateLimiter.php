<?php

namespace App\Core;

/**
 * Rate Limiter
 * 
 * Prevents abuse by limiting the number of requests per time window
 */
class RateLimiter
{
    private static $storage = [];
    
    /**
     * Check if request is allowed
     * 
     * @param string $key Unique identifier (e.g., IP address, user ID)
     * @param int $maxAttempts Maximum attempts allowed
     * @param int $decayMinutes Time window in minutes
     * @return bool
     */
    public static function attempt(string $key, int $maxAttempts = 60, int $decayMinutes = 1): bool
    {
        $key = self::resolveKey($key);
        
        // Clean up old entries
        self::cleanup();
        
        // Get current attempts
        $attempts = self::getAttempts($key);
        
        if ($attempts >= $maxAttempts) {
            return false;
        }
        
        // Increment attempts
        self::incrementAttempts($key, $decayMinutes);
        
        return true;
    }
    
    /**
     * Get remaining attempts
     * 
     * @param string $key
     * @param int $maxAttempts
     * @return int
     */
    public static function remaining(string $key, int $maxAttempts = 60): int
    {
        $key = self::resolveKey($key);
        $attempts = self::getAttempts($key);
        
        return max(0, $maxAttempts - $attempts);
    }
    
    /**
     * Get seconds until reset
     * 
     * @param string $key
     * @return int
     */
    public static function availableIn(string $key): int
    {
        $key = self::resolveKey($key);
        
        if (!isset(self::$storage[$key])) {
            return 0;
        }
        
        $resetTime = self::$storage[$key]['reset_at'];
        return max(0, $resetTime - time());
    }
    
    /**
     * Clear attempts for a key
     * 
     * @param string $key
     * @return void
     */
    public static function clear(string $key): void
    {
        $key = self::resolveKey($key);
        unset(self::$storage[$key]);
    }
    
    /**
     * Resolve the rate limiter key
     * 
     * @param string $key
     * @return string
     */
    private static function resolveKey(string $key): string
    {
        return 'rate_limit:' . $key;
    }
    
    /**
     * Get current attempts for a key
     * 
     * @param string $key
     * @return int
     */
    private static function getAttempts(string $key): int
    {
        if (!isset(self::$storage[$key])) {
            return 0;
        }
        
        // Check if expired
        if (self::$storage[$key]['reset_at'] < time()) {
            unset(self::$storage[$key]);
            return 0;
        }
        
        return self::$storage[$key]['attempts'];
    }
    
    /**
     * Increment attempts for a key
     * 
     * @param string $key
     * @param int $decayMinutes
     * @return void
     */
    private static function incrementAttempts(string $key, int $decayMinutes): void
    {
        if (!isset(self::$storage[$key])) {
            self::$storage[$key] = [
                'attempts' => 0,
                'reset_at' => time() + ($decayMinutes * 60)
            ];
        }
        
        self::$storage[$key]['attempts']++;
    }
    
    /**
     * Clean up expired entries
     * 
     * @return void
     */
    private static function cleanup(): void
    {
        $now = time();
        
        foreach (self::$storage as $key => $data) {
            if ($data['reset_at'] < $now) {
                unset(self::$storage[$key]);
            }
        }
    }
    
    /**
     * Get client IP address
     * 
     * @return string
     */
    public static function getClientIp(): string
    {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                
                // Handle comma-separated IPs (proxies)
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                
                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Middleware to check rate limit
     * 
     * @param string $identifier
     * @param int $maxAttempts
     * @param int $decayMinutes
     * @throws \Exception
     */
    public static function middleware(string $identifier = null, int $maxAttempts = 60, int $decayMinutes = 1): void
    {
        $key = $identifier ?? self::getClientIp();
        
        if (!self::attempt($key, $maxAttempts, $decayMinutes)) {
            $retryAfter = self::availableIn($key);
            
            header('HTTP/1.1 429 Too Many Requests');
            header('Retry-After: ' . $retryAfter);
            header('Content-Type: application/json');
            
            echo json_encode([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $retryAfter
            ]);
            
            exit;
        }
    }
}
