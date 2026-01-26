<?php

namespace Tests\Integration;

use Tests\TestCase;
use PDO;

class JHCISIntegrationTest extends TestCase
{
    /**
     * Test JHCIS database connection
     */
    public function testJHCISDatabaseConnection()
    {
        // Get JHCIS connection details from environment
        $jhcisHost = getenv('JHCIS_DB_HOST') ?: 'localhost';
        $jhcisPort = getenv('JHCIS_DB_PORT') ?: '3306';
        $jhcisDb = getenv('JHCIS_DB_NAME') ?: 'jhcisdb';
        $jhcisUser = getenv('JHCIS_DB_USER') ?: 'root';
        $jhcisPass = getenv('JHCIS_DB_PASS') ?: '';
        
        try {
            $dsn = "mysql:host={$jhcisHost};port={$jhcisPort};dbname={$jhcisDb};charset=utf8mb4";
            $jhcisDb = new PDO($dsn, $jhcisUser, $jhcisPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            $this->assertInstanceOf(PDO::class, $jhcisDb);
            
            // Test simple query
            $stmt = $jhcisDb->query("SELECT 1 as test");
            $result = $stmt->fetch();
            
            $this->assertEquals(1, $result['test']);
            
        } catch (\PDOException $e) {
            // If JHCIS is not configured, skip this test
            $this->markTestSkipped('JHCIS database not configured: ' . $e->getMessage());
        }
    }

    /**
     * Test reading drug data from JHCIS drugitems table
     */
    public function testReadJHCISDrugItems()
    {
        try {
            $jhcisHost = getenv('JHCIS_DB_HOST') ?: 'localhost';
            $jhcisPort = getenv('JHCIS_DB_PORT') ?: '3306';
            $jhcisDb = getenv('JHCIS_DB_NAME') ?: 'jhcisdb';
            $jhcisUser = getenv('JHCIS_DB_USER') ?: 'root';
            $jhcisPass = getenv('JHCIS_DB_PASS') ?: '';
            
            $dsn = "mysql:host={$jhcisHost};port={$jhcisPort};dbname={$jhcisDb};charset=utf8mb4";
            $jhcisDb = new PDO($dsn, $jhcisUser, $jhcisPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            // Check if drugitems table exists
            $stmt = $jhcisDb->query("SHOW TABLES LIKE 'drugitems'");
            $tableExists = $stmt->fetch();
            
            if ($tableExists) {
                // Try to read some drug items
                $stmt = $jhcisDb->query("SELECT * FROM drugitems LIMIT 5");
                $drugs = $stmt->fetchAll();
                
                $this->assertIsArray($drugs);
                
                if (!empty($drugs)) {
                    // Verify structure
                    $this->assertArrayHasKey('drugcode', $drugs[0]);
                    $this->assertArrayHasKey('drugname', $drugs[0]);
                }
            } else {
                $this->markTestSkipped('drugitems table does not exist in JHCIS database');
            }
            
        } catch (\PDOException $e) {
            $this->markTestSkipped('JHCIS database not configured: ' . $e->getMessage());
        }
    }

    /**
     * Test reading drug data from JHCIS cdrug table (alternative)
     */
    public function testReadJHCISCDrug()
    {
        try {
            $jhcisHost = getenv('JHCIS_DB_HOST') ?: 'localhost';
            $jhcisPort = getenv('JHCIS_DB_PORT') ?: '3306';
            $jhcisDb = getenv('JHCIS_DB_NAME') ?: 'jhcisdb';
            $jhcisUser = getenv('JHCIS_DB_USER') ?: 'root';
            $jhcisPass = getenv('JHCIS_DB_PASS') ?: '';
            
            $dsn = "mysql:host={$jhcisHost};port={$jhcisPort};dbname={$jhcisDb};charset=utf8mb4";
            $jhcisDb = new PDO($dsn, $jhcisUser, $jhcisPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            // Check if cdrug table exists
            $stmt = $jhcisDb->query("SHOW TABLES LIKE 'cdrug'");
            $tableExists = $stmt->fetch();
            
            if ($tableExists) {
                // Try to read some drugs
                $stmt = $jhcisDb->query("SELECT * FROM cdrug LIMIT 5");
                $drugs = $stmt->fetchAll();
                
                $this->assertIsArray($drugs);
                
                if (!empty($drugs)) {
                    // Verify structure
                    $this->assertArrayHasKey('drugcode', $drugs[0]);
                    $this->assertArrayHasKey('drugname', $drugs[0]);
                }
            } else {
                $this->markTestSkipped('cdrug table does not exist in JHCIS database');
            }
            
        } catch (\PDOException $e) {
            $this->markTestSkipped('JHCIS database not configured: ' . $e->getMessage());
        }
    }

    /**
     * Test data synchronization concept
     */
    public function testDataSynchronizationConcept()
    {
        // This is a conceptual test to verify the sync logic
        // In production, this would sync data from JHCIS to Drugmuk
        
        try {
            // Simulate reading from JHCIS
            $jhcisData = [
                [
                    'drugcode' => 'JHCIS001',
                    'drugname' => 'ยาทดสอบจาก JHCIS',
                    'unitprice' => 100.00
                ]
            ];
            
            // Simulate importing to Drugmuk
            $drug = $this->createTestDrug([
                'code' => $jhcisData[0]['drugcode'],
                'name' => $jhcisData[0]['drugname'],
                'price' => $jhcisData[0]['unitprice']
            ]);
            
            $this->assertNotNull($drug);
            $this->assertEquals('JHCIS001', $drug['code']);
            $this->assertEquals('ยาทดสอบจาก JHCIS', $drug['name']);
            
        } catch (\Exception $e) {
            $this->fail('Synchronization concept test failed: ' . $e->getMessage());
        }
    }
}
