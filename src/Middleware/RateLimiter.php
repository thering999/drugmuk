<?php
/**
 * API Rate Limiter Middleware
 * Prevents abuse by limiting requests per IP address
 */

namespace App\Middleware;

class RateLimiter
{
    private $redis;
    private $maxRequests = 100; // Max requests per window
    private $windowSeconds = 60; // Time window in seconds
    
    public function __construct()
    {
        // Use Redis if available, otherwise use file-based storage
        if (extension_loaded('redis')) {
            $this->redis = new \Redis();
            $this->redis->connect('127.0.0.1', 6379);
        }
    }
    
    /**
     * Check if request is allowed
     */
    public function isAllowed(string $ip, string $endpoint = ''): bool
    {
        $key = $this->getKey($ip, $endpoint);
        
        if ($this->redis) {
            return $this->checkRedis($key);
        } else {
            return $this->checkFile($key);
        }
    }
    
    /**
     * Get remaining requests
     */
    public function getRemaining(string $ip, string $endpoint = ''): int
    {
        $key = $this->getKey($ip, $endpoint);
        
        if ($this->redis) {
            $current = (int) $this->redis->get($key);
            return max(0, $this->maxRequests - $current);
        } else {
            $data = $this->getFileData($key);
            return max(0, $this->maxRequests - $data['count']);
        }
    }
    
    /**
     * Get rate limit headers
     */
    public function getHeaders(string $ip, string $endpoint = ''): array
    {
        $remaining = $this->getRemaining($ip, $endpoint);
        $resetTime = time() + $this->windowSeconds;
        
        return [
            'X-RateLimit-Limit' => $this->maxRequests,
            'X-RateLimit-Remaining' => $remaining,
            'X-RateLimit-Reset' => $resetTime
        ];
    }
    
    /**
     * Check rate limit using Redis
     */
    private function checkRedis(string $key): bool
    {
        $current = $this->redis->incr($key);
        
        if ($current === 1) {
            $this->redis->expire($key, $this->windowSeconds);
        }
        
        return $current <= $this->maxRequests;
    }
    
    /**
     * Check rate limit using file storage
     */
    private function checkFile(string $key): bool
    {
        $data = $this->getFileData($key);
        
        // Check if window has expired
        if (time() - $data['timestamp'] > $this->windowSeconds) {
            $data = ['count' => 0, 'timestamp' => time()];
        }
        
        $data['count']++;
        $this->saveFileData($key, $data);
        
        return $data['count'] <= $this->maxRequests;
    }
    
    /**
     * Get key for rate limiting
     */
    private function getKey(string $ip, string $endpoint): string
    {
        $endpoint = $endpoint ? ':' . md5($endpoint) : '';
        return "rate_limit:{$ip}{$endpoint}";
    }
    
    /**
     * Get data from file
     */
    private function getFileData(string $key): array
    {
        $file = sys_get_temp_dir() . '/' . md5($key) . '.json';
        
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            return $data ?: ['count' => 0, 'timestamp' => time()];
        }
        
        return ['count' => 0, 'timestamp' => time()];
    }
    
    /**
     * Save data to file
     */
    private function saveFileData(string $key, array $data): void
    {
        $file = sys_get_temp_dir() . '/' . md5($key) . '.json';
        file_put_contents($file, json_encode($data));
    }
    
    /**
     * Middleware handler
     */
    public function handle(): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $endpoint = $_SERVER['REQUEST_URI'] ?? '';
        
        if (!$this->isAllowed($ip, $endpoint)) {
            // Rate limit exceeded
            http_response_code(429);
            
            $headers = $this->getHeaders($ip, $endpoint);
            foreach ($headers as $name => $value) {
                header("{$name}: {$value}");
            }
            
            header('Retry-After: ' . $this->windowSeconds);
            
            echo json_encode([
                'error' => 'Too Many Requests',
                'message' => 'Rate limit exceeded. Please try again later.',
                'retry_after' => $this->windowSeconds
            ]);
            
            exit;
        }
        
        // Add rate limit headers to response
        $headers = $this->getHeaders($ip, $endpoint);
        foreach ($headers as $name => $value) {
            header("{$name}: {$value}");
        }
    }
}

// Usage example:
// $rateLimiter = new RateLimiter();
// $rateLimiter->handle();
