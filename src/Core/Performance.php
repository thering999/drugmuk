<?php

namespace App\Core;

class Performance
{
    /**
     * Simple Cache Implementation
     */
    private static $cache = [];

    /**
     * Get from cache
     */
    public static function cacheGet($key)
    {
        if (isset(self::$cache[$key])) {
            $data = self::$cache[$key];
            if ($data['expires'] > time()) {
                return $data['value'];
            }
            unset(self::$cache[$key]);
        }
        return null;
    }

    /**
     * Set to cache
     */
    public static function cacheSet($key, $value, $ttl = 3600)
    {
        self::$cache[$key] = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
    }

    /**
     * Clear cache
     */
    public static function cacheClear($key = null)
    {
        if ($key === null) {
            self::$cache = [];
        } else {
            unset(self::$cache[$key]);
        }
    }

    /**
     * Minify HTML
     */
    public static function minifyHTML($html)
    {
        $search = [
            '/\>[^\S ]+/s',     // strip whitespaces after tags
            '/[^\S ]+\</s',     // strip whitespaces before tags
            '/(\s)+/s',         // shorten multiple whitespace sequences
            '/<!--(.|\s)*?-->/' // Remove HTML comments
        ];

        $replace = [
            '>',
            '<',
            '\\1',
            ''
        ];

        return preg_replace($search, $replace, $html);
    }

    /**
     * Compress Output
     */
    public static function compressOutput()
    {
        if (!ob_start('ob_gzhandler')) {
            ob_start();
        }
    }

    /**
     * Set Cache Headers
     */
    public static function setCacheHeaders($maxAge = 3600)
    {
        header('Cache-Control: public, max-age=' . $maxAge);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT');
    }

    /**
     * Lazy Load Images
     */
    public static function lazyLoadImage($src, $alt = '', $class = '')
    {
        return '<img data-src="' . htmlspecialchars($src) . '" 
                     alt="' . htmlspecialchars($alt) . '" 
                     class="' . htmlspecialchars($class) . ' lazy" 
                     src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7">';
    }

    /**
     * Database Query Cache
     */
    public static function queryCacheGet($query, $params = [])
    {
        $key = 'query_' . md5($query . serialize($params));
        return self::cacheGet($key);
    }

    /**
     * Database Query Cache Set
     */
    public static function queryCacheSet($query, $params, $result, $ttl = 300)
    {
        $key = 'query_' . md5($query . serialize($params));
        self::cacheSet($key, $result, $ttl);
    }

    /**
     * Measure Execution Time
     */
    public static function measureTime($callback, $label = 'Execution')
    {
        $start = microtime(true);
        $result = $callback();
        $end = microtime(true);
        
        $time = round(($end - $start) * 1000, 2);
        error_log("$label took {$time}ms");
        
        return $result;
    }

    /**
     * Memory Usage
     */
    public static function getMemoryUsage()
    {
        return [
            'current' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB',
            'peak' => round(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB'
        ];
    }

    /**
     * Optimize Database Connection
     */
    public static function optimizeDBConnection($pdo)
    {
        // Use persistent connections
        $pdo->setAttribute(\PDO::ATTR_PERSISTENT, true);
        
        // Use prepared statement emulation
        $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
        
        // Set fetch mode
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        
        return $pdo;
    }

    /**
     * Batch Process
     */
    public static function batchProcess($items, $callback, $batchSize = 100)
    {
        $batches = array_chunk($items, $batchSize);
        $results = [];

        foreach ($batches as $batch) {
            $results = array_merge($results, $callback($batch));
        }

        return $results;
    }

    /**
     * Asset Version for Cache Busting
     */
    public static function assetVersion($file)
    {
        $path = __DIR__ . '/../../public/' . $file;
        if (file_exists($path)) {
            return $file . '?v=' . filemtime($path);
        }
        return $file;
    }
}
