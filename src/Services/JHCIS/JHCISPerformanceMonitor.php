<?php

namespace App\Services\JHCIS;

use App\Services\LoggerService;

/**
 * JHCIS Performance Monitor
 * 
 * Tracks and logs performance metrics for JHCIS operations
 */
class JHCISPerformanceMonitor
{
    private LoggerService $logger;
    private array $metrics = [];
    
    public function __construct()
    {
        $this->logger = new LoggerService();
    }
    
    /**
     * Start monitoring operation
     * 
     * @param string $operation
     * @param int $hospitalId
     * @param array $context
     * @return void
     */
    public function startOperation(string $operation, int $hospitalId, array $context = []): void
    {
        $key = $this->getKey($operation, $hospitalId);
        
        $this->metrics[$key] = [
            'operation' => $operation,
            'hospital_id' => $hospitalId,
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(),
            'context' => $context
        ];
    }
    
    /**
     * End monitoring operation
     * 
     * @param string $operation
     * @param int $hospitalId
     * @param int $recordCount
     * @param array $additionalMetrics
     * @return void
     */
    public function endOperation(string $operation, int $hospitalId, int $recordCount = 0, array $additionalMetrics = []): void
    {
        $key = $this->getKey($operation, $hospitalId);
        
        if (!isset($this->metrics[$key])) {
            return;
        }
        
        $startMetrics = $this->metrics[$key];
        $duration = microtime(true) - $startMetrics['start_time'];
        $memoryUsed = memory_get_usage() - $startMetrics['start_memory'];
        $peakMemory = memory_get_peak_usage();
        
        $metrics = array_merge([
            'operation' => $operation,
            'hospital_id' => $hospitalId,
            'duration_seconds' => round($duration, 3),
            'memory_used_mb' => round($memoryUsed / 1024 / 1024, 2),
            'peak_memory_mb' => round($peakMemory / 1024 / 1024, 2),
            'records_processed' => $recordCount,
            'records_per_second' => $recordCount > 0 ? round($recordCount / $duration, 2) : 0
        ], $startMetrics['context'], $additionalMetrics);
        
        $this->logger->info("JHCIS operation completed", $metrics);
        
        // Store metrics for dashboard
        $this->storeMetrics($metrics);
        
        unset($this->metrics[$key]);
    }
    
    /**
     * Record error
     * 
     * @param string $operation
     * @param int $hospitalId
     * @param \Exception $exception
     * @return void
     */
    public function recordError(string $operation, int $hospitalId, \Exception $exception): void
    {
        $this->logger->error("JHCIS operation failed", [
            'operation' => $operation,
            'hospital_id' => $hospitalId,
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ]);
    }
    
    /**
     * Get operation key
     * 
     * @param string $operation
     * @param int $hospitalId
     * @return string
     */
    private function getKey(string $operation, int $hospitalId): string
    {
        return "{$operation}:{$hospitalId}:" . uniqid();
    }
    
    /**
     * Store metrics in database
     * 
     * @param array $metrics
     * @return void
     */
    private function storeMetrics(array $metrics): void
    {
        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            
            $stmt = $db->prepare(
                "INSERT INTO jhcis_performance_metrics 
                 (operation, hospital_id, duration_seconds, memory_used_mb, records_processed, records_per_second, metrics_data, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            
            $stmt->execute([
                $metrics['operation'],
                $metrics['hospital_id'],
                $metrics['duration_seconds'],
                $metrics['memory_used_mb'],
                $metrics['records_processed'],
                $metrics['records_per_second'],
                json_encode($metrics)
            ]);
            
        } catch (\Exception $e) {
            // Fail silently, don't break operation
            $this->logger->warning("Failed to store metrics", [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get performance statistics
     * 
     * @param int $hospitalId
     * @param int $days
     * @return array
     */
    public function getStatistics(int $hospitalId, int $days = 7): array
    {
        $db = \App\Core\Database::getInstance()->getConnection();
        
        $stmt = $db->prepare(
            "SELECT 
                operation,
                COUNT(*) as total_operations,
                AVG(duration_seconds) as avg_duration,
                MIN(duration_seconds) as min_duration,
                MAX(duration_seconds) as max_duration,
                AVG(records_per_second) as avg_throughput,
                SUM(records_processed) as total_records
             FROM jhcis_performance_metrics
             WHERE hospital_id = ?
             AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             GROUP BY operation"
        );
        
        $stmt->execute([$hospitalId, $days]);
        
        return $stmt->fetchAll();
    }
}
