<?php
/**
 * Cache Service
 * Simple caching layer with Redis or file-based storage
 */

namespace App\Services;

class CacheService
{
    private $redis;
    private $useRedis = false;
    private $cacheDir;
    private $defaultTTL = 3600; // 1 hour
    
    public function __construct()
    {
        // Try to connect to Redis
        if (extension_loaded('redis')) {
            try {
                $this->redis = new \Redis();
                $this->redis->connect('127.0.0.1', 6379);
                $this->useRedis = true;
            } catch (\Exception $e) {
                $this->useRedis = false;
            }
        }
        
        // Setup file cache directory
        $this->cacheDir = sys_get_temp_dir() . '/drugmuk_cache';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Get cached value
     */
    public function get(string $key)
    {
        if ($this->useRedis) {
            $value = $this->redis->get($key);
            return $value !== false ? json_decode($value, true) : null;
        } else {
            return $this->getFromFile($key);
        }
    }
    
    /**
     * Set cached value
     */
    public function set(string $key, $value, int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->defaultTTL;
        
        if ($this->useRedis) {
            return $this->redis->setex($key, $ttl, json_encode($value));
        } else {
            return $this->setToFile($key, $value, $ttl);
        }
    }
    
    /**
     * Delete cached value
     */
    public function delete(string $key): bool
    {
        if ($this->useRedis) {
            return $this->redis->del($key) > 0;
        } else {
            return $this->deleteFromFile($key);
        }
    }
    
    /**
     * Check if key exists
     */
    public function has(string $key): bool
    {
        if ($this->useRedis) {
            return $this->redis->exists($key) > 0;
        } else {
            return $this->hasInFile($key);
        }
    }
    
    /**
     * Clear all cache
     */
    public function clear(): bool
    {
        if ($this->useRedis) {
            return $this->redis->flushDB();
        } else {
            return $this->clearFiles();
        }
    }
    
    /**
     * Remember (get or set)
     */
    public function remember(string $key, callable $callback, int $ttl = null)
    {
        if ($this->has($key)) {
            return $this->get($key);
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }
    
    /**
     * Get from file cache
     */
    private function getFromFile(string $key)
    {
        $file = $this->getCacheFile($key);
        
        if (!file_exists($file)) {
            return null;
        }
        
        $data = json_decode(file_get_contents($file), true);
        
        // Check if expired
        if ($data['expires_at'] < time()) {
            unlink($file);
            return null;
        }
        
        return $data['value'];
    }
    
    /**
     * Set to file cache
     */
    private function setToFile(string $key, $value, int $ttl): bool
    {
        $file = $this->getCacheFile($key);
        
        $data = [
            'value' => $value,
            'expires_at' => time() + $ttl
        ];
        
        return file_put_contents($file, json_encode($data)) !== false;
    }
    
    /**
     * Delete from file cache
     */
    private function deleteFromFile(string $key): bool
    {
        $file = $this->getCacheFile($key);
        
        if (file_exists($file)) {
            return unlink($file);
        }
        
        return true;
    }
    
    /**
     * Check if exists in file cache
     */
    private function hasInFile(string $key): bool
    {
        return $this->getFromFile($key) !== null;
    }
    
    /**
     * Clear all file cache
     */
    private function clearFiles(): bool
    {
        $files = glob($this->cacheDir . '/*');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        return true;
    }
    
    /**
     * Get cache file path
     */
    private function getCacheFile(string $key): string
    {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }
    
    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        if ($this->useRedis) {
            $info = $this->redis->info();
            return [
                'driver' => 'redis',
                'keys' => $this->redis->dbSize(),
                'memory' => $info['used_memory_human'] ?? 'N/A',
                'hits' => $info['keyspace_hits'] ?? 0,
                'misses' => $info['keyspace_misses'] ?? 0,
            ];
        } else {
            $files = glob($this->cacheDir . '/*');
            $totalSize = 0;
            
            foreach ($files as $file) {
                $totalSize += filesize($file);
            }
            
            return [
                'driver' => 'file',
                'keys' => count($files),
                'size' => $this->formatBytes($totalSize),
            ];
        }
    }
    
    /**
     * Format bytes to human readable
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}

// Usage example:
// $cache = new CacheService();
// 
// // Simple get/set
// $cache->set('drugs_list', $drugs, 3600);
// $drugs = $cache->get('drugs_list');
// 
// // Remember pattern
// $drugs = $cache->remember('drugs_list', function() use ($drugModel) {
//     return $drugModel->getAll();
// }, 3600);
