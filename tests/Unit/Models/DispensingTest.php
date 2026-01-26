<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Dispensing;

class DispensingTest extends TestCase
{
    private $dispensing;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dispensing = new Dispensing();
    }

    /**
     * Test getting all dispensing records with pagination
     */
    public function testGetAllWithPagination()
    {
        $result = $this->dispensing->getAll(1, 10);
        
        $this->assertIsArray($result);
        $this->assertLessThanOrEqual(10, count($result));
    }

    /**
     * Test getting total count
     */
    public function testGetTotalCount()
    {
        $count = $this->dispensing->getTotalCount();
        
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    /**
     * Test creating dispensing record
     */
    public function testCreateDispensing()
    {
        $drug = $this->createTestDrug();
        $inventory = $this->createTestInventory($drug['id'], 100);
        
        $data = [
            'dispense_date' => date('Y-m-d'),
            'hn' => 'HN001',
            'patient_name' => 'ทดสอบ ผู้ป่วย',
            'items' => [
                [
                    'drug_id' => $drug['id'],
                    'quantity' => 10
                ]
            ]
        ];
        
        $result = $this->dispensing->create($data);
        
        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    /**
     * Test getting dispensing by ID
     */
    public function testGetById()
    {
        $drug = $this->createTestDrug();
        $inventory = $this->createTestInventory($drug['id'], 100);
        
        $data = [
            'dispense_date' => date('Y-m-d'),
            'hn' => 'HN002',
            'patient_name' => 'ทดสอบ ผู้ป่วย 2',
            'items' => [
                [
                    'drug_id' => $drug['id'],
                    'quantity' => 5
                ]
            ]
        ];
        
        $id = $this->dispensing->create($data);
        $result = $this->dispensing->getById($id);
        
        $this->assertIsArray($result);
        $this->assertEquals($id, $result['id']);
        $this->assertEquals('HN002', $result['hn']);
    }

    /**
     * Test searching patient by HN
     */
    public function testSearchPatientByHN()
    {
        $result = $this->dispensing->searchPatient('HN');
        
        $this->assertIsArray($result);
    }

    /**
     * Test getting patient history
     */
    public function testGetPatientHistory()
    {
        $result = $this->dispensing->getPatientHistory('HN001', 5);
        
        $this->assertIsArray($result);
        $this->assertLessThanOrEqual(5, count($result));
    }

    /**
     * Test getting dispensing statistics
     */
    public function testGetStatistics()
    {
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-d');
        
        $result = $this->dispensing->getStatistics($startDate, $endDate);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_dispenses', $result);
        $this->assertArrayHasKey('total_items', $result);
    }

    /**
     * Test getting top dispensed drugs
     */
    public function testGetTopDispensedDrugs()
    {
        $result = $this->dispensing->getTopDispensedDrugs(10);
        
        $this->assertIsArray($result);
        $this->assertLessThanOrEqual(10, count($result));
    }

    /**
     * Test getting monthly trend
     */
    public function testGetMonthlyTrend()
    {
        $result = $this->dispensing->getMonthlyTrend(6);
        
        $this->assertIsArray($result);
        $this->assertLessThanOrEqual(6, count($result));
    }

    /**
     * Test getting daily activity
     */
    public function testGetDailyActivity()
    {
        $result = $this->dispensing->getDailyActivity(7);
        
        $this->assertIsArray($result);
        $this->assertLessThanOrEqual(7, count($result));
    }

    /**
     * Test FEFO stock deduction
     */
    public function testDeductStockFEFO()
    {
        $drug = $this->createTestDrug();
        
        // Create multiple lots with different expiry dates
        $this->createTestInventory($drug['id'], 50, 'LOT001', date('Y-m-d', strtotime('+30 days')));
        $this->createTestInventory($drug['id'], 50, 'LOT002', date('Y-m-d', strtotime('+60 days')));
        
        $data = [
            'dispense_date' => date('Y-m-d'),
            'hn' => 'HN003',
            'patient_name' => 'ทดสอบ FEFO',
            'items' => [
                [
                    'drug_id' => $drug['id'],
                    'quantity' => 20
                ]
            ]
        ];
        
        $result = $this->dispensing->create($data);
        
        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    /**
     * Test deleting dispensing record
     */
    public function testDeleteDispensing()
    {
        $drug = $this->createTestDrug();
        $inventory = $this->createTestInventory($drug['id'], 100);
        
        $data = [
            'dispense_date' => date('Y-m-d'),
            'hn' => 'HN004',
            'patient_name' => 'ทดสอบ ลบ',
            'items' => [
                [
                    'drug_id' => $drug['id'],
                    'quantity' => 5
                ]
            ]
        ];
        
        $id = $this->dispensing->create($data);
        $result = $this->dispensing->delete($id);
        
        $this->assertTrue($result);
    }
}
