<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Database Connection Manager
 * รองรับ multiple database connections (Drugmuk และ JHCIS)
 */
class Database
{
    private static $instances = [];
    private $pdo;

    /**
     * Constructor
     * 
     * @param string $connection ชื่อ connection (drugmuk หรือ jhcis)
     */
    private function __construct($connection = 'drugmuk')
    {
        // โหลด config
        Config::load();

        // ดึงค่า database config
        $config = Config::database($connection);

        $host = $config['host'];
        $port = $config['port'] ?? '3306';
        $db = $config['database'];
        $user = $config['username'];
        $pass = $config['password'];
        $charset = $config['charset'] ?? 'utf8mb4';

        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $charset",
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            // Log error
            error_log("Database connection error [$connection]: " . $e->getMessage());
            throw new PDOException("Failed to connect to $connection database: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * ดึง instance ของ database connection
     * 
     * @param string $connection ชื่อ connection (drugmuk หรือ jhcis)
     * @return Database
     */
    public static function getInstance($connection = 'drugmuk')
    {
        if (!isset(self::$instances[$connection])) {
            self::$instances[$connection] = new Database($connection);
        }
        return self::$instances[$connection];
    }

    /**
     * ดึง PDO connection
     * 
     * @return PDO
     */
    public function getConnection()
    {
        return $this->pdo;
    }

    /**
     * ทดสอบการเชื่อมต่อ
     * 
     * @param string $connection
     * @return array ['success' => bool, 'message' => string]
     */
    public static function testConnection($connection = 'drugmuk')
    {
        try {
            $db = self::getInstance($connection);
            $pdo = $db->getConnection();
            
            // ทดสอบ query
            $stmt = $pdo->query('SELECT 1');
            $result = $stmt->fetch();

            return [
                'success' => true,
                'message' => "Connected to $connection database successfully",
                'server_version' => $pdo->getAttribute(PDO::ATTR_SERVER_VERSION),
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ];
        }
    }

    /**
     * ปิดการเชื่อมต่อ
     * 
     * @param string $connection
     */
    public static function closeConnection($connection = 'drugmuk')
    {
        if (isset(self::$instances[$connection])) {
            self::$instances[$connection]->pdo = null;
            unset(self::$instances[$connection]);
        }
    }

    /**
     * ปิดการเชื่อมต่อทั้งหมด
     */
    public static function closeAllConnections()
    {
        foreach (self::$instances as $connection => $instance) {
            self::closeConnection($connection);
        }
    }
}
