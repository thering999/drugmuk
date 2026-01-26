<?php

namespace App\Controllers;

use App\Core\CSRF;
use App\Middleware\AuthMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Services\ValidationService;
use App\Services\SanitizationService;
use App\Services\LoggerService;

/**
 * Base Controller with Security Features
 * 
 * All controllers should extend this class to inherit security features
 */
abstract class BaseController
{
    protected $db;
    protected $validator;
    protected $logger;
    
    public function __construct()
    {
        $this->db = \App\Core\Database::getInstance()->getConnection();
        $this->validator = new ValidationService();
        $this->logger = new LoggerService();
    }
    
    /**
     * Verify CSRF token for POST requests
     */
    protected function verifyCsrf(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRF::verifyRequest();
        }
    }
    
    /**
     * Check authentication
     */
    protected function requireAuth(): void
    {
        AuthMiddleware::check();
    }
    
    /**
     * Check specific role
     */
    protected function requireRole(string $role): void
    {
        AuthMiddleware::requireRole($role);
    }
    
    /**
     * Apply rate limiting
     */
    protected function rateLimit(string $key = null, int $maxAttempts = 60, int $decayMinutes = 1): void
    {
        if ($key === null) {
            $key = $_SERVER['REMOTE_ADDR'];
        }
        RateLimitMiddleware::limit($key, $maxAttempts, $decayMinutes);
    }
    
    /**
     * Validate request data
     */
    protected function validate(array $data, array $rules): array
    {
        return $this->validator->validate($data, $rules);
    }
    
    /**
     * Sanitize input string
     */
    protected function sanitize(string $input): string
    {
        return SanitizationService::sanitizeString($input);
    }
    
    /**
     * Sanitize HTML
     */
    protected function sanitizeHtml(string $html): string
    {
        return SanitizationService::sanitizeHTML($html);
    }
    
    /**
     * Escape output for HTML
     */
    protected function escape(string $output): string
    {
        return SanitizationService::escapeHTML($output);
    }
    
    /**
     * Get current user ID
     */
    protected function getUserId(): ?int
    {
        return AuthMiddleware::getUserId();
    }
    
    /**
     * Get current user role
     */
    protected function getUserRole(): ?string
    {
        return AuthMiddleware::getUserRole();
    }
    
    /**
     * Redirect to URL
     */
    protected function redirect(string $url, int $statusCode = 302): void
    {
        header("Location: {$url}", true, $statusCode);
        exit;
    }
    
    /**
     * Return JSON response
     */
    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Return success JSON
     */
    protected function jsonSuccess($data = null, string $message = 'Success'): void
    {
        $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }
    
    /**
     * Return error JSON
     */
    protected function jsonError(string $message, int $statusCode = 400, $errors = null): void
    {
        $this->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $statusCode);
    }
    
    /**
     * Log activity
     */
    protected function logActivity(string $action, array $context = []): void
    {
        $this->logger->info($action, array_merge([
            'user_id' => $this->getUserId(),
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ], $context));
    }
    
    /**
     * Log error
     */
    protected function logError(string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }
    
    /**
     * Get POST data with sanitization
     */
    protected function getPost(string $key, $default = null)
    {
        if (!isset($_POST[$key])) {
            return $default;
        }
        
        $value = $_POST[$key];
        
        if (is_string($value)) {
            return $this->sanitize($value);
        }
        
        return $value;
    }
    
    /**
     * Get GET data with sanitization
     */
    protected function getQuery(string $key, $default = null)
    {
        if (!isset($_GET[$key])) {
            return $default;
        }
        
        $value = $_GET[$key];
        
        if (is_string($value)) {
            return $this->sanitize($value);
        }
        
        return $value;
    }
    
    /**
     * Render view with data
     */
    protected function view(string $viewPath, array $data = []): void
    {
        // Extract data to variables
        extract($data);
        
        // Include view file
        $viewFile = __DIR__ . "/../Views/{$viewPath}.php";
        
        if (!file_exists($viewFile)) {
            throw new \Exception("View not found: {$viewPath}");
        }
        
        include $viewFile;
    }
}
