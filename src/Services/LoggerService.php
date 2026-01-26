<?php

namespace App\Services;

/**
 * Logger Service
 * 
 * Provides structured logging with different log levels
 */
class LoggerService
{
    private string $logPath;
    private string $logLevel;
    private array $levels = [
        'DEBUG' => 0,
        'INFO' => 1,
        'WARNING' => 2,
        'ERROR' => 3,
        'CRITICAL' => 4
    ];

    public function __construct()
    {
        $this->logPath = __DIR__ . '/../../logs/';
        $this->logLevel = $_ENV['LOG_LEVEL'] ?? 'INFO';

        // Create logs directory if it doesn't exist
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }

    /**
     * Log debug message
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log('DEBUG', $message, $context);
    }

    /**
     * Log info message
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    /**
     * Log warning message
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    /**
     * Log error message
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    /**
     * Log critical message
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log('CRITICAL', $message, $context);
    }

    /**
     * Log message with specified level
     * 
     * @param string $level
     * @param string $message
     * @param array $context
     * @return void
     */
    private function log(string $level, string $message, array $context = []): void
    {
        // Check if this level should be logged
        if ($this->levels[$level] < $this->levels[$this->logLevel]) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $contextString = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        
        $logMessage = sprintf(
            "[%s] [%s] %s%s\n",
            $timestamp,
            $level,
            $message,
            $contextString
        );

        // Write to daily log file
        $filename = $this->logPath . date('Y-m-d') . '.log';
        file_put_contents($filename, $logMessage, FILE_APPEND);

        // Also write errors to separate error log
        if (in_array($level, ['ERROR', 'CRITICAL'])) {
            $errorFilename = $this->logPath . 'errors-' . date('Y-m-d') . '.log';
            file_put_contents($errorFilename, $logMessage, FILE_APPEND);
        }
    }

    /**
     * Log exception
     * 
     * @param \Throwable $exception
     * @param array $context
     * @return void
     */
    public function logException(\Throwable $exception, array $context = []): void
    {
        $context['exception'] = get_class($exception);
        $context['file'] = $exception->getFile();
        $context['line'] = $exception->getLine();
        $context['trace'] = $exception->getTraceAsString();

        $this->error($exception->getMessage(), $context);
    }

    /**
     * Clean old log files
     * 
     * @param int $days Number of days to keep
     * @return int Number of files deleted
     */
    public function cleanOldLogs(int $days = 30): int
    {
        $deleted = 0;
        $cutoffTime = time() - ($days * 24 * 60 * 60);

        $files = glob($this->logPath . '*.log');
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Get log file path for today
     * 
     * @return string
     */
    public function getTodayLogPath(): string
    {
        return $this->logPath . date('Y-m-d') . '.log';
    }

    /**
     * Read log file
     * 
     * @param string $date Date in Y-m-d format
     * @param int $lines Number of lines to read (0 = all)
     * @return array
     */
    public function readLog(string $date, int $lines = 0): array
    {
        $filename = $this->logPath . $date . '.log';
        
        if (!file_exists($filename)) {
            return [];
        }

        $content = file($filename, FILE_IGNORE_NEW_LINES);
        
        if ($lines > 0) {
            return array_slice($content, -$lines);
        }

        return $content;
    }
}
