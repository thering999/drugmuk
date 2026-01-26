<?php

namespace App\Core;

use App\Services\LoggerService;

/**
 * Centralized Error Handler
 * 
 * Handles all errors and exceptions in the application
 */
class ErrorHandler
{
    private static bool $registered = false;

    /**
     * Register error and exception handlers
     * 
     * @return void
     */
    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        // Set error handler
        set_error_handler([self::class, 'handleError']);

        // Set exception handler
        set_exception_handler([self::class, 'handleException']);

        // Set shutdown handler for fatal errors
        register_shutdown_function([self::class, 'handleShutdown']);

        self::$registered = true;
    }

    /**
     * Handle PHP errors
     * 
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @return bool
     */
    public static function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        // Don't handle errors that are suppressed with @
        if (!(error_reporting() & $errno)) {
            return false;
        }

        $errorType = self::getErrorType($errno);

        // Log error
        self::logError($errorType, $errstr, $errfile, $errline);

        // Convert to exception for consistency
        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    /**
     * Handle uncaught exceptions
     * 
     * @param \Throwable $exception
     * @return void
     */
    public static function handleException(\Throwable $exception): void
    {
        // Log exception
        self::logException($exception);

        // Send appropriate response
        if (self::isAjaxRequest()) {
            self::sendJsonErrorResponse($exception);
        } else {
            self::sendHtmlErrorResponse($exception);
        }

        exit(1);
    }

    /**
     * Handle fatal errors on shutdown
     * 
     * @return void
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            self::logError(
                self::getErrorType($error['type']),
                $error['message'],
                $error['file'],
                $error['line']
            );

            if (self::isAjaxRequest()) {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'A fatal error occurred',
                    'error' => Config::get('APP_ENV') === 'development' ? $error['message'] : null
                ]);
            } else {
                http_response_code(500);
                echo self::getErrorPage('Fatal Error', 'A fatal error occurred. Please try again later.');
            }
        }
    }

    /**
     * Log error
     * 
     * @param string $type
     * @param string $message
     * @param string $file
     * @param int $line
     * @return void
     */
    private static function logError(string $type, string $message, string $file, int $line): void
    {
        $logMessage = sprintf(
            "[%s] %s in %s on line %d",
            $type,
            $message,
            $file,
            $line
        );

        error_log($logMessage);

        // Use LoggerService if available
        if (class_exists(LoggerService::class)) {
            try {
                $logger = new LoggerService();
                $logger->error($logMessage, [
                    'type' => $type,
                    'file' => $file,
                    'line' => $line
                ]);
            } catch (\Exception $e) {
                // Fallback to error_log if LoggerService fails
                error_log("LoggerService failed: " . $e->getMessage());
            }
        }
    }

    /**
     * Log exception
     * 
     * @param \Throwable $exception
     * @return void
     */
    private static function logException(\Throwable $exception): void
    {
        $logMessage = sprintf(
            "[%s] %s in %s on line %d\nStack trace:\n%s",
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );

        error_log($logMessage);

        // Use LoggerService if available
        if (class_exists(LoggerService::class)) {
            try {
                $logger = new LoggerService();
                $logger->error($exception->getMessage(), [
                    'exception' => get_class($exception),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTraceAsString()
                ]);
            } catch (\Exception $e) {
                error_log("LoggerService failed: " . $e->getMessage());
            }
        }
    }

    /**
     * Send JSON error response
     * 
     * @param \Throwable $exception
     * @return void
     */
    private static function sendJsonErrorResponse(\Throwable $exception): void
    {
        $statusCode = method_exists($exception, 'getStatusCode') 
            ? $exception->getStatusCode() 
            : 500;

        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');

        $response = [
            'success' => false,
            'message' => $exception->getMessage(),
            'timestamp' => time()
        ];

        // Include details in development mode
        if (Config::get('APP_ENV') === 'development') {
            $response['debug'] = [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => explode("\n", $exception->getTraceAsString())
            ];
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Send HTML error response
     * 
     * @param \Throwable $exception
     * @return void
     */
    private static function sendHtmlErrorResponse(\Throwable $exception): void
    {
        $statusCode = method_exists($exception, 'getStatusCode') 
            ? $exception->getStatusCode() 
            : 500;

        http_response_code($statusCode);

        if (Config::get('APP_ENV') === 'development') {
            echo self::getDetailedErrorPage($exception);
        } else {
            echo self::getErrorPage(
                'เกิดข้อผิดพลาด',
                'ขออภัย เกิดข้อผิดพลาดในระบบ กรุณาลองใหม่อีกครั้ง'
            );
        }
    }

    /**
     * Get simple error page
     * 
     * @param string $title
     * @param string $message
     * @return string
     */
    private static function getErrorPage(string $title, string $message): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <style>
        body { font-family: 'Sarabun', sans-serif; background: #f5f5f5; padding: 50px; }
        .error-container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #e74c3c; margin-bottom: 20px; }
        p { color: #555; line-height: 1.6; }
        .back-link { display: inline-block; margin-top: 20px; color: #3498db; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>{$title}</h1>
        <p>{$message}</p>
        <a href="/" class="back-link">← กลับหน้าหลัก</a>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Get detailed error page (development mode)
     * 
     * @param \Throwable $exception
     * @return string
     */
    private static function getDetailedErrorPage(\Throwable $exception): string
    {
        $class = get_class($exception);
        $message = htmlspecialchars($exception->getMessage());
        $file = htmlspecialchars($exception->getFile());
        $line = $exception->getLine();
        $trace = htmlspecialchars($exception->getTraceAsString());

        return <<<HTML
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error: {$class}</title>
    <style>
        body { font-family: monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; }
        .error-header { background: #e74c3c; color: white; padding: 20px; border-radius: 4px; margin-bottom: 20px; }
        .error-details { background: #2d2d2d; padding: 20px; border-radius: 4px; margin-bottom: 20px; }
        .error-trace { background: #2d2d2d; padding: 20px; border-radius: 4px; white-space: pre-wrap; }
        h1 { margin: 0; font-size: 24px; }
        h2 { color: #61afef; margin-top: 0; }
        .file-info { color: #98c379; }
    </style>
</head>
<body>
    <div class="error-header">
        <h1>{$class}</h1>
        <p>{$message}</p>
    </div>
    <div class="error-details">
        <h2>Location</h2>
        <p class="file-info">{$file} on line {$line}</p>
    </div>
    <div class="error-trace">
        <h2>Stack Trace</h2>
{$trace}
    </div>
</body>
</html>
HTML;
    }

    /**
     * Check if request is AJAX
     * 
     * @return bool
     */
    private static function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Get error type name
     * 
     * @param int $errno
     * @return string
     */
    private static function getErrorType(int $errno): string
    {
        $types = [
            E_ERROR => 'ERROR',
            E_WARNING => 'WARNING',
            E_PARSE => 'PARSE',
            E_NOTICE => 'NOTICE',
            E_CORE_ERROR => 'CORE_ERROR',
            E_CORE_WARNING => 'CORE_WARNING',
            E_COMPILE_ERROR => 'COMPILE_ERROR',
            E_COMPILE_WARNING => 'COMPILE_WARNING',
            E_USER_ERROR => 'USER_ERROR',
            E_USER_WARNING => 'USER_WARNING',
            E_USER_NOTICE => 'USER_NOTICE',
            E_STRICT => 'STRICT',
            E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
            E_DEPRECATED => 'DEPRECATED',
            E_USER_DEPRECATED => 'USER_DEPRECATED',
        ];

        return $types[$errno] ?? 'UNKNOWN';
    }
}
