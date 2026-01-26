<?php
/**
 * Performance Monitoring Service
 * 
 * Tracks and monitors application performance metrics
 * 
 * @package Drugmuk
 * @subpackage Services
 * @version 1.0
 * @since Phase 6.2
 */

namespace App\Services;

class PerformanceMonitor
{
    private static $startTime;
    private static $queries = [];
    private static $cacheHits = 0;
    private static $cacheMisses = 0;
    
    /**
     * Start performance monitoring
     */
    public static function start()
    {
        self::$startTime = microtime(true);
        self::$queries = [];
        self::$cacheHits = 0;
        self::$cacheMisses = 0;
    }
    
    /**
     * Log database query
     * 
     * @param string $query SQL query
     * @param float $executionTime Execution time in seconds
     */
    public static function logQuery($query, $executionTime)
    {
        self::$queries[] = [
            'query' => $query,
            'time' => $executionTime,
            'timestamp' => microtime(true)
        ];
    }
    
    /**
     * Log cache hit
     */
    public static function cacheHit()
    {
        self::$cacheHits++;
    }
    
    /**
     * Log cache miss
     */
    public static function cacheMiss()
    {
        self::$cacheMisses++;
    }
    
    /**
     * Get performance metrics
     * 
     * @return array Performance metrics
     */
    public static function getMetrics()
    {
        $totalTime = microtime(true) - self::$startTime;
        $queryTime = array_sum(array_column(self::$queries, 'time'));
        
        $totalCacheRequests = self::$cacheHits + self::$cacheMisses;
        $cacheHitRate = $totalCacheRequests > 0 
            ? round((self::$cacheHits / $totalCacheRequests) * 100, 2)
            : 0;
        
        return [
            'total_time' => round($totalTime * 1000, 2) . 'ms',
            'query_count' => count(self::$queries),
            'query_time' => round($queryTime * 1000, 2) . 'ms',
            'cache_hits' => self::$cacheHits,
            'cache_misses' => self::$cacheMisses,
            'cache_hit_rate' => $cacheHitRate . '%',
            'memory_usage' => self::formatBytes(memory_get_usage(true)),
            'peak_memory' => self::formatBytes(memory_get_peak_usage(true))
        ];
    }
    
    /**
     * Get slow queries (> 100ms)
     * 
     * @return array Slow queries
     */
    public static function getSlowQueries()
    {
        return array_filter(self::$queries, function($query) {
            return $query['time'] > 0.1; // 100ms
        });
    }
    
    /**
     * Format bytes to human readable
     */
    private static function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * Add performance metrics to response headers
     */
    public static function addHeaders()
    {
        $metrics = self::getMetrics();
        
        header('X-Response-Time: ' . $metrics['total_time']);
        header('X-Query-Count: ' . $metrics['query_count']);
        header('X-Cache-Hit-Rate: ' . $metrics['cache_hit_rate']);
    }
}
