<?php

namespace Tests\Integration;

use Tests\TestCase;

/**
 * Database Connection Test
 * 
 * ทดสอบการเชื่อมต่อ database และ basic operations
 */
class DatabaseConnectionTest extends TestCase
{
    /**
     * Test: เชื่อมต่อ database สำเร็จ
     */
    public function testDatabaseConnection()
    {
        $this->assertNotNull($this->db);
        $this->assertInstanceOf(\PDO::class, $this->db);
    }
    
    /**
     * Test: ทดสอบ query พื้นฐาน
     */
    public function testBasicQuery()
    {
        $result = $this->db->query("SELECT 1 as test")->fetch();
        
        $this->assertEquals(1, $result['test']);
    }
    
    /**
     * Test: ทดสอบ prepared statement
     */
    public function testPreparedStatement()
    {
        $stmt = $this->db->prepare("SELECT ? as value");
        $stmt->execute([42]);
        $result = $stmt->fetch();
        
        $this->assertEquals(42, $result['value']);
    }
    
    /**
     * Test: ทดสอบ transaction
     */
    public function testTransaction()
    {
        $this->db->beginTransaction();
        
        $this->assertTrue($this->db->inTransaction());
        
        $this->db->rollBack();
        
        $this->assertFalse($this->db->inTransaction());
    }
    
    /**
     * Test: ตรวจสอบตารางที่จำเป็น
     */
    public function testRequiredTablesExist()
    {
        $requiredTables = [
            'drugs',
            'inventory',
            'orders',
            'users',
            'dispensing',
            'transactions',
        ];
        
        foreach ($requiredTables as $table) {
            $sql = "SHOW TABLES LIKE ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$table]);
            $result = $stmt->fetch();
            
            $this->assertNotFalse($result, "Table $table should exist");
        }
    }
}
