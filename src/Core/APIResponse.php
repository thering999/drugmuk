<?php
/**
 * API Response Helper
 * 
 * Provides utilities for API responses including pagination,
 * compression, caching, and standardized formatting
 * 
 * @package Drugmuk
 * @subpackage Core
 * @version 1.0
 * @since Phase 6.2
 */

namespace App\Core;

use App\Services\CacheService;

class APIResponse
{
    /**
     * Send JSON response
     * 
     * @param mixed $data Response data
     * @param int $statusCode HTTP status code
     * @param array $headers Additional headers
     */
    public static function json($data, $statusCode = 200, $headers = [])
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        
        foreach ($headers as $key => $value) {
            header("$key: $value");
        }
        
        // Check if compression is supported
        if (self::shouldCompress()) {
            $json = json_encode($data, JSON_UNESCAPED_UNICODE);
            $compressed = gzencode($json, 6);
            header('Content-Encoding: gzip');
            header('Content-Length: ' . strlen($compressed));
            echo $compressed;
        } else {
            echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
        
        exit;
    }
    
    /**
     * Send success response
     * 
     * @param mixed $data Response data
     * @param string $message Success message
     * @param int $statusCode HTTP status code
     */
    public static function success($data = null, $message = 'Success', $statusCode = 200)
    {
        $response = [
            'success' => true,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        self::json($response, $statusCode);
    }
    
    /**
     * Send error response
     * 
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @param array $errors Additional error details
     */
    public static function error($message, $statusCode = 400, $errors = [])
    {
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        
        self::json($response, $statusCode);
    }
    
    /**
     * Paginate query results
     * 
     * @param array $items All items
     * @param int $page Current page
     * @param int $perPage Items per page
     * @return array Paginated response
     */
    public static function paginate($items, $page = 1, $perPage = 20)
    {
        $total = count($items);
        $totalPages = ceil($total / $perPage);
        $page = max(1, min($page, $totalPages ?: 1));
        
        $offset = ($page - 1) * $perPage;
        $paginatedItems = array_slice($items, $offset, $perPage);
        
        return [
            'data' => $paginatedItems,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
                'from' => $offset + 1,
                'to' => min($offset + $perPage, $total),
                'has_more' => $page < $totalPages
            ]
        ];
    }
    
    /**
     * Paginate database query with PDO
     * 
     * @param \PDOStatement $stmt Prepared statement
     * @param int $page Current page
     * @param int $perPage Items per page
     * @return array Paginated response
     */
    public static function paginateQuery($stmt, $page = 1, $perPage = 20)
    {
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return self::paginate($items, $page, $perPage);
    }
    
    /**
     * Check if response should be compressed
     * 
     * @return bool
     */
    private static function shouldCompress()
    {
        // Check if client supports gzip
        if (!isset($_SERVER['HTTP_ACCEPT_ENCODING'])) {
            return false;
        }
        
        if (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') === false) {
            return false;
        }
        
        // Don't compress if already compressed
        if (headers_sent()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Cache API response
     * 
     * @param string $key Cache key
     * @param callable $callback Function to generate data
     * @param int $ttl Time to live in seconds
     * @return mixed Cached or fresh data
     */
    public static function cached($key, $callback, $ttl = 600)
    {
        $cache = new CacheService();
        
        return $cache->remember($key, $callback, $ttl);
    }
    
    /**
     * Send cached JSON response
     * 
     * @param string $cacheKey Cache key
     * @param callable $callback Function to generate data
     * @param int $ttl Cache TTL
     * @param int $statusCode HTTP status code
     */
    public static function cachedJson($cacheKey, $callback, $ttl = 600, $statusCode = 200)
    {
        $data = self::cached($cacheKey, $callback, $ttl);
        self::json($data, $statusCode);
    }
    
    /**
     * Filter fields in response
     * 
     * @param array $data Data to filter
     * @param array $fields Fields to include
     * @return array Filtered data
     */
    public static function filterFields($data, $fields = [])
    {
        if (empty($fields)) {
            return $data;
        }
        
        // Handle array of objects
        if (isset($data[0]) && is_array($data[0])) {
            return array_map(function($item) use ($fields) {
                return array_intersect_key($item, array_flip($fields));
            }, $data);
        }
        
        // Handle single object
        return array_intersect_key($data, array_flip($fields));
    }
    
    /**
     * Add CORS headers
     * 
     * @param array $allowedOrigins Allowed origins
     * @param array $allowedMethods Allowed HTTP methods
     */
    public static function cors($allowedOrigins = ['*'], $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE'])
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
        
        if (in_array('*', $allowedOrigins) || in_array($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: $origin");
        }
        
        header('Access-Control-Allow-Methods: ' . implode(', ', $allowedMethods));
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Max-Age: 86400');
        
        // Handle preflight
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
    
    /**
     * Rate limit check
     * 
     * @param string $key Rate limit key (e.g., user ID or IP)
     * @param int $maxRequests Maximum requests allowed
     * @param int $window Time window in seconds
     * @return bool True if allowed, false if rate limited
     */
    public static function rateLimit($key, $maxRequests = 60, $window = 60)
    {
        $cache = new CacheService();
        $rateLimitKey = "rate_limit:$key";
        
        $requests = $cache->get($rateLimitKey) ?? 0;
        
        if ($requests >= $maxRequests) {
            header('X-RateLimit-Limit: ' . $maxRequests);
            header('X-RateLimit-Remaining: 0');
            header('X-RateLimit-Reset: ' . (time() + $window));
            header('Retry-After: ' . $window);
            
            self::error('Rate limit exceeded', 429);
            return false;
        }
        
        // Increment counter
        if ($requests == 0) {
            $cache->set($rateLimitKey, 1, $window);
        } else {
            $cache->set($rateLimitKey, $requests + 1, $window);
        }
        
        // Add rate limit headers
        header('X-RateLimit-Limit: ' . $maxRequests);
        header('X-RateLimit-Remaining: ' . ($maxRequests - $requests - 1));
        
        return true;
    }
    
    /**
     * Transform data using a transformer function
     * 
     * @param mixed $data Data to transform
     * @param callable $transformer Transformer function
     * @return mixed Transformed data
     */
    public static function transform($data, $transformer)
    {
        if (is_array($data) && isset($data[0])) {
            return array_map($transformer, $data);
        }
        
        return $transformer($data);
    }
    
    /**
     * Add ETag for caching
     * 
     * @param mixed $data Data to generate ETag from
     * @return string ETag value
     */
    public static function etag($data)
    {
        $etag = md5(json_encode($data));
        header("ETag: \"$etag\"");
        
        // Check if client has cached version
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && 
            trim($_SERVER['HTTP_IF_NONE_MATCH'], '"') === $etag) {
            http_response_code(304);
            exit;
        }
        
        return $etag;
    }
    
    /**
     * Send file download response
     * 
     * @param string $filePath Path to file
     * @param string $filename Download filename
     * @param string $mimeType MIME type
     */
    public static function download($filePath, $filename = null, $mimeType = 'application/octet-stream')
    {
        if (!file_exists($filePath)) {
            self::error('File not found', 404);
        }
        
        $filename = $filename ?? basename($filePath);
        
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache, must-revalidate');
        
        readfile($filePath);
        exit;
    }
    
    /**
     * Create standardized API response structure
     * 
     * @param bool $success Success status
     * @param mixed $data Response data
     * @param string $message Message
     * @param array $meta Additional metadata
     * @return array Response structure
     */
    public static function format($success, $data = null, $message = '', $meta = [])
    {
        $response = [
            'success' => $success,
            'timestamp' => date('c'),
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        if (!empty($meta)) {
            $response['meta'] = $meta;
        }
        
        return $response;
    }
}
