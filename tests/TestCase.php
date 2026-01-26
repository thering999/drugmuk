<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use PDO;

/**
 * Base Test Case
 * 
 * ให้ทุก test class extends จาก class นี้
 * มี helper methods สำหรับการทดสอบ
 */
abstract class TestCase extends BaseTestCase
{
    protected $db;
    protected $testDbName = 'drugmuk';
    
    /**
     * Setup ก่อนแต่ละ test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Load environment variables
        $this->loadEnv();
        
        // Setup test database connection
        $this->setupDatabase();
        
        // Begin transaction
        $this->db->beginTransaction();
    }
    
    /**
     * Cleanup หลังแต่ละ test
     */
    protected function tearDown(): void
    {
        // Rollback transaction
        if ($this->db && $this->db->inTransaction()) {
            $this->db->rollBack();
        }
        
        parent::tearDown();
    }
    
    /**
     * Load environment variables
     */
    protected function loadEnv()
    {
        $envFile = __DIR__ . '/../.env';
        
        if (!file_exists($envFile)) {
            return;
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            if (!array_key_exists($name, $_ENV)) {
                $_ENV[$name] = $value;
            }
        }
    }
    
    /**
     * Setup database connection
     */
    protected function setupDatabase()
    {
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $username = $_ENV['DB_USER'] ?? 'root';
        $password = $_ENV['DB_PASSWORD'] ?? '123456';
        $testDbName = $_ENV['DB_TEST_NAME'] ?? ($_ENV['DB_NAME'] ?? $this->testDbName);
        
        $dsn = "mysql:host=$host;port=$port;dbname={$testDbName};charset=utf8mb4";
        
        try {
            $this->db = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (\PDOException $e) {
            $this->markTestSkipped('Cannot connect to test database: ' . $e->getMessage());
        }
    }
    
    /**
     * Helper: Create test drug
     * 
     * @param array $data
     * @return int Drug ID
     */
    protected function createTestDrug($data = [])
    {
        $defaults = [
            'code' => 'TEST' . rand(1000, 9999),
            'name' => 'Test Drug ' . rand(1, 999),
            'generic_name' => 'Test Generic',
            'unit' => 'tablet',
            'price' => 10.50,
            'min_stock' => 10,
            'max_stock' => 100,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        
        $data = array_merge($defaults, $data);
        
        $sql = "INSERT INTO drugs (code, name, generic_name, unit, price, min_stock, max_stock, is_active, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['code'],
            $data['name'],
            $data['generic_name'],
            $data['unit'],
            $data['price'],
            $data['min_stock'],
            $data['max_stock'],
            $data['is_active'],
            $data['created_at'],
        ]);
        
        return (int) $this->db->lastInsertId();
    }
    
    /**
     * Helper: Create test user
     * 
     * @param array $data
     * @return int User ID
     */
    protected function createTestUser($data = [])
    {
        $defaults = [
            'username' => 'test' . rand(1000, 9999),
            'password' => password_hash('password', PASSWORD_BCRYPT),
            'full_name' => 'Test User',
            'role' => 'staff',
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        
        $data = array_merge($defaults, $data);
        
        $sql = "INSERT INTO users (username, password, full_name, role, is_active, created_at) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['username'],
            $data['password'],
            $data['full_name'],
            $data['role'],
            $data['is_active'],
            $data['created_at'],
        ]);
        
        return (int) $this->db->lastInsertId();
    }
    
    /**
     * Helper: Create test inventory
     * 
     * @param int $drugId
     * @param array $data
     * @return int Inventory ID
     */
    protected function createTestInventory($drugId, $data = [])
    {
        $defaults = [
            'lot_no' => 'LOT' . rand(1000, 9999),
            'expire_date' => date('Y-m-d', strtotime('+1 year')),
            'quantity' => 100,
            'cost_price' => 10.00,
            'received_date' => date('Y-m-d'),
            'created_at' => date('Y-m-d H:i:s'),
        ];
        
        $data = array_merge($defaults, $data);
        
        $sql = "INSERT INTO inventory (drug_id, lot_no, expire_date, quantity, cost_price, received_date, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $drugId,
            $data['lot_no'],
            $data['expire_date'],
            $data['quantity'],
            $data['cost_price'],
            $data['received_date'],
            $data['created_at'],
        ]);
        
        return (int) $this->db->lastInsertId();
    }
    
    /**
     * Helper: Create test order
     * 
     * @param array $data
     * @return int Order ID
     */
    protected function createTestOrder($data = [])
    {
        $defaults = [
            'order_no' => 'PO' . date('Ymd') . rand(1000, 9999),
            'order_date' => date('Y-m-d'),
            'status' => 'pending',
            'total_amount' => 0,
            'created_by' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        
        $data = array_merge($defaults, $data);
        
        $sql = "INSERT INTO orders (order_no, order_date, status, total_amount, created_by, created_at) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['order_no'],
            $data['order_date'],
            $data['status'],
            $data['total_amount'],
            $data['created_by'],
            $data['created_at'],
        ]);
        
        return (int) $this->db->lastInsertId();
    }
    
    /**
     * Helper: Assert array has keys
     * 
     * @param array $keys
     * @param array $array
     */
    protected function assertArrayHasKeys($keys, $array)
    {
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $array, "Array should have key: $key");
        }
    }
    
    /**
     * Helper: Get database connection
     * 
     * @return PDO
     */
    protected function getDb()
    {
        return $this->db;
    }
}
