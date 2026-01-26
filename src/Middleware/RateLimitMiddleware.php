<?php

namespace App\Middleware;

use App\Services\CacheService;
use App\Core\APIResponse;

/**
 * Rate Limiting Middleware
 * 
 * Prevents abuse by limiting the number of requests per time window
 */
class RateLimitMiddleware
{
    private CacheService $cache;
    private int $maxRequests;
    private int $windowSeconds;

    /**
     * Constructor
     * 
     * @param int $maxRequests Maximum requests allowed
     * @param int $windowSeconds Time window in seconds
     */
    public function __construct(int $maxRequests = 60, int $windowSeconds = 60)
    {
        $this->cache = new CacheService();
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
    }

    /**
     * Handle rate limiting
     * 
     * @param string $identifier Unique identifier (IP, user ID, etc.)
     * @return bool True if allowed, false if rate limited
     */
    public function handle(string $identifier): bool
    {
        $key = $this->getRateLimitKey($identifier);
        $requests = $this->cache->get($key) ?? 0;

        // Check if rate limit exceeded
        if ($requests >= $this->maxRequests) {
            $this->sendRateLimitHeaders($requests);
            APIResponse::error('Rate limit exceeded. Please try again later.', 429);
            return false;
        }

        // Increment request count
        if ($requests == 0) {
            $this->cache->set($key, 1, $this->windowSeconds);
        } else {
            $this->cache->set($key, $requests + 1, $this->windowSeconds);
        }

        // Send rate limit headers
        $this->sendRateLimitHeaders($requests + 1);

        return true;
    }

    /**
     * Get rate limit key
     * 
     * @param string $identifier
     * @return string
     */
    private function getRateLimitKey(string $identifier): string
    {
        return "rate_limit:{$identifier}";
    }

    /**
     * Send rate limit headers
     * 
     * @param int $currentRequests
     * @return void
     */
    private function sendRateLimitHeaders(int $currentRequests): void
    {
        header('X-RateLimit-Limit: ' . $this->maxRequests);
        header('X-RateLimit-Remaining: ' . max(0, $this->maxRequests - $currentRequests));
        header('X-RateLimit-Reset: ' . (time() + $this->windowSeconds));

        if ($currentRequests >= $this->maxRequests) {
            header('Retry-After: ' . $this->windowSeconds);
        }
    }

    /**
     * Get client identifier (IP address)
     * 
     * @return string
     */
    public static function getClientIdentifier(): string
    {
        // Check for proxy headers
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }

        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            return $_SERVER['HTTP_X_REAL_IP'];
        }

        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Rate limit by IP address
     * 
     * @param int $maxRequests
     * @param int $windowSeconds
     * @return bool
     */
    public static function byIP(int $maxRequests = 60, int $windowSeconds = 60): bool
    {
        $middleware = new self($maxRequests, $windowSeconds);
        return $middleware->handle(self::getClientIdentifier());
    }

    /**
     * Rate limit by user ID
     * 
     * @param int $userId
     * @param int $maxRequests
     * @param int $windowSeconds
     * @return bool
     */
    public static function byUser(int $userId, int $maxRequests = 100, int $windowSeconds = 60): bool
    {
        $middleware = new self($maxRequests, $windowSeconds);
        return $middleware->handle("user:{$userId}");
    }

    /**
     * Rate limit by API key
     * 
     * @param string $apiKey
     * @param int $maxRequests
     * @param int $windowSeconds
     * @return bool
     */
    public static function byApiKey(string $apiKey, int $maxRequests = 1000, int $windowSeconds = 3600): bool
    {
        $middleware = new self($maxRequests, $windowSeconds);
        return $middleware->handle("api:{$apiKey}");
    }

    /**
     * Rate limit for login attempts
     * 
     * @param string $username
     * @return bool
     */
    public static function loginAttempt(string $username): bool
    {
        $middleware = new self(5, 300); // 5 attempts per 5 minutes
        return $middleware->handle("login:{$username}");
    }

    /**
     * Clear rate limit for identifier
     * 
     * @param string $identifier
     * @return void
     */
    public function clear(string $identifier): void
    {
        $key = $this->getRateLimitKey($identifier);
        $this->cache->delete($key);
    }

    /**
     * Get remaining requests
     * 
     * @param string $identifier
     * @return int
     */
    public function getRemaining(string $identifier): int
    {
        $key = $this->getRateLimitKey($identifier);
        $requests = $this->cache->get($key) ?? 0;
        return max(0, $this->maxRequests - $requests);
    }

    /**
     * Check if rate limited without incrementing
     * 
     * @param string $identifier
     * @return bool
     */
    public function isRateLimited(string $identifier): bool
    {
        $key = $this->getRateLimitKey($identifier);
        $requests = $this->cache->get($key) ?? 0;
        return $requests >= $this->maxRequests;
    }
}
