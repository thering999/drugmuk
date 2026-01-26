<?php

namespace App\Services\JHCIS;

use PDO;
use PDOException;
use App\Services\LoggerService;

/**
 * JHCIS Connection Pool
 * 
 * Manages persistent connections to multiple JHCIS databases
 */
class JHCISConnectionPool
{
    private static $pools = [];
    private static $maxConnections = 10;
    private static $logger;
    
    /**
     * Get connection for specific hospital
     * 
     * @param int $hospitalId
     * @return PDO
     * @throws \Exception
     */
    public static function getConnection(int $hospitalId): PDO
    {
        $key = "jhcis_{$hospitalId}";
        
        // Reuse existing connection if alive
        if (isset(self::$pools[$key]) && self::isAlive(self::$pools[$key])) {
            return self::$pools[$key];
        }
        
        // Create new connection
        $config = self::getHospitalConfig($hospitalId);
        
        $host = $config['host'];
        if ($host === 'localhost' || $host === '127.0.0.1') {
            $host = 'host.docker.internal';
        }

        try {
            $pdo = new PDO(
                "mysql:host=$host;port={$config['port']};dbname={$config['database']};charset=utf8mb4",
                $config['username'],
                $config['password'],
                [
                    PDO::ATTR_PERSISTENT => true, // Persistent connection
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                    PDO::ATTR_TIMEOUT => 5, // 5 second timeout
                    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false // Unbuffered for large datasets
                ]
            );
            
            self::$pools[$key] = $pdo;
            
            self::log('info', "JHCIS connection established", [
                'hospital_id' => $hospitalId,
                'host' => $config['host']
            ]);
            
            return $pdo;
            
        } catch (PDOException $e) {
            self::log('error', "JHCIS connection failed", [
                'hospital_id' => $hospitalId,
                'error' => $e->getMessage()
            ]);
            
            throw new \Exception("Cannot connect to JHCIS database for hospital {$hospitalId}: " . $e->getMessage());
        }
    }
    
    /**
     * Check if connection is alive
     * 
     * @param PDO $pdo
     * @return bool
     */
    private static function isAlive(PDO $pdo): bool
    {
        try {
            $pdo->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get hospital configuration
     * 
     * @param int $hospitalId
     * @return array
     * @throws \Exception
     */
    private static function getHospitalConfig(int $hospitalId): array
    {
        // Get from database
        $db = \App\Core\Database::getInstance()->getConnection();
        
        $stmt = $db->prepare(
            "SELECT db_host as host, db_port as port, db_name as database_name, db_user as username, db_pass as password, is_active
             FROM jhcis_hospitals
             WHERE id = ? AND is_active = 1"
        );
        $stmt->execute([$hospitalId]);
        $config = $stmt->fetch();
        
        if (!$config) {
            throw new \Exception("Hospital configuration not found or inactive: {$hospitalId}");
        }
        
        // Decrypt password if encrypted
        $password = self::decryptPassword($config['password']);
        
        return [
            'host' => $config['host'],
            'port' => $config['port'] ?? 3306,
            'database' => $config['database_name'],
            'username' => $config['username'],
            'password' => $password
        ];
    }
    
    /**
     * Decrypt password
     * 
     * @param string $encrypted
     * @return string
     */
    private static function decryptPassword(string $encrypted): string
    {
        // If it's valid base64, decode it, otherwise return as is
        if (base64_encode(base64_decode($encrypted, true)) === $encrypted) {
            return base64_decode($encrypted);
        }
        return $encrypted;
    }
    
    /**
     * Close connection for specific hospital
     * 
     * @param int $hospitalId
     * @return void
     */
    public static function closeConnection(int $hospitalId): void
    {
        $key = "jhcis_{$hospitalId}";
        
        if (isset(self::$pools[$key])) {
            unset(self::$pools[$key]);
            
            self::log('info', "JHCIS connection closed", [
                'hospital_id' => $hospitalId
            ]);
        }
    }
    
    /**
     * Close all connections
     * 
     * @return void
     */
    public static function closeAll(): void
    {
        foreach (array_keys(self::$pools) as $key) {
            unset(self::$pools[$key]);
        }
        
        self::log('info', "All JHCIS connections closed");
    }
    
    /**
     * Get active connection count
     * 
     * @return int
     */
    public static function getActiveConnectionCount(): int
    {
        return count(self::$pools);
    }
    
    /**
     * Test connection
     * 
     * @param int $hospitalId
     * @return array
     */
    public static function testConnection(int $hospitalId): array
    {
        $startTime = microtime(true);
        
        try {
            $pdo = self::getConnection($hospitalId);
            
            // Test query
            $stmt = $pdo->query("SELECT DATABASE() as db, VERSION() as version");
            $result = $stmt->fetch();
            
            $duration = microtime(true) - $startTime;
            
            return [
                'success' => true,
                'database' => $result['db'],
                'version' => $result['version'],
                'response_time_ms' => round($duration * 1000, 2)
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'response_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ];
        }
    }
    
    /**
     * Log message
     * 
     * @param string $level
     * @param string $message
     * @param array $context
     * @return void
     */
    private static function log(string $level, string $message, array $context = []): void
    {
        if (!self::$logger) {
            self::$logger = new LoggerService();
        }
        
        self::$logger->$level($message, $context);
    }
}
