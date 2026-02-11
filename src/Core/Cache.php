<?php

namespace App\Core;

/**
 * Simple Cache Manager
 * 
 * Provides basic caching functionality to improve performance
 */
class Cache
{
    private static $cache = [];
    private static $ttl = [];
    
    /**
     * Store an item in the cache
     * 
     * @param string $key
     * @param mixed $value
     * @param int $ttl Time to live in seconds (0 = forever)
     * @return bool
     */
    public static function put(string $key, $value, int $ttl = 3600): bool
    {
        self::$cache[$key] = $value;
        self::$ttl[$key] = $ttl > 0 ? time() + $ttl : 0;
        
        return true;
    }
    
    /**
     * Retrieve an item from the cache
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        // Check if exists
        if (!isset(self::$cache[$key])) {
            return $default;
        }
        
        // Check if expired
        if (self::isExpired($key)) {
            self::forget($key);
            return $default;
        }
        
        return self::$cache[$key];
    }
    
    /**
     * Check if an item exists in the cache
     * 
     * @param string $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        if (!isset(self::$cache[$key])) {
            return false;
        }
        
        if (self::isExpired($key)) {
            self::forget($key);
            return false;
        }
        
        return true;
    }
    
    /**
     * Remove an item from the cache
     * 
     * @param string $key
     * @return bool
     */
    public static function forget(string $key): bool
    {
        unset(self::$cache[$key]);
        unset(self::$ttl[$key]);
        
        return true;
    }
    
    /**
     * Remove all items from the cache
     * 
     * @return bool
     */
    public static function flush(): bool
    {
        self::$cache = [];
        self::$ttl = [];
        
        return true;
    }
    
    /**
     * Get an item from the cache, or execute the callback and store the result
     * 
     * @param string $key
     * @param int $ttl
     * @param callable $callback
     * @return mixed
     */
    public static function remember(string $key, int $ttl, callable $callback)
    {
        if (self::has($key)) {
            return self::get($key);
        }
        
        $value = $callback();
        self::put($key, $value, $ttl);
        
        return $value;
    }
    
    /**
     * Get an item from the cache, or execute the callback and store the result forever
     * 
     * @param string $key
     * @param callable $callback
     * @return mixed
     */
    public static function rememberForever(string $key, callable $callback)
    {
        return self::remember($key, 0, $callback);
    }
    
    /**
     * Increment the value of an item in the cache
     * 
     * @param string $key
     * @param int $value
     * @return int|bool
     */
    public static function increment(string $key, int $value = 1)
    {
        if (!self::has($key)) {
            self::put($key, $value);
            return $value;
        }
        
        $current = self::get($key);
        
        if (!is_numeric($current)) {
            return false;
        }
        
        $new = $current + $value;
        self::put($key, $new, self::getRemainingTtl($key));
        
        return $new;
    }
    
    /**
     * Decrement the value of an item in the cache
     * 
     * @param string $key
     * @param int $value
     * @return int|bool
     */
    public static function decrement(string $key, int $value = 1)
    {
        return self::increment($key, -$value);
    }
    
    /**
     * Check if a cache key is expired
     * 
     * @param string $key
     * @return bool
     */
    private static function isExpired(string $key): bool
    {
        if (!isset(self::$ttl[$key])) {
            return false;
        }
        
        // 0 means forever
        if (self::$ttl[$key] === 0) {
            return false;
        }
        
        return self::$ttl[$key] < time();
    }
    
    /**
     * Get remaining TTL for a key
     * 
     * @param string $key
     * @return int
     */
    private static function getRemainingTtl(string $key): int
    {
        if (!isset(self::$ttl[$key]) || self::$ttl[$key] === 0) {
            return 0;
        }
        
        return max(0, self::$ttl[$key] - time());
    }
    
    /**
     * Clean up expired cache entries
     * 
     * @return int Number of items removed
     */
    public static function cleanup(): int
    {
        $removed = 0;
        
        foreach (self::$cache as $key => $value) {
            if (self::isExpired($key)) {
                self::forget($key);
                $removed++;
            }
        }
        
        return $removed;
    }
    
    /**
     * Get cache statistics
     * 
     * @return array
     */
    public static function stats(): array
    {
        return [
            'items' => count(self::$cache),
            'size_bytes' => strlen(serialize(self::$cache)),
            'expired' => count(array_filter(array_keys(self::$cache), function($key) {
                return self::isExpired($key);
            }))
        ];
    }
}
