<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\SubInventory;

class SubInventoryTest extends TestCase
{
    private $subInventory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subInventory = new SubInventory();
    }

    /**
     * Test getting stock for warehouse
     */
    public function testGetStock()
    {
        $warehouseCode = 'SW001';
        
        $result = $this->subInventory->getStock($warehouseCode);
        
        $this->assertIsArray($result);
    }

    /**
     * Test calculating requisition quantities
     */
    public function testCalculateRequisitionQuantities()
    {
        $warehouseCode = 'SW001';
        
        $result = $this->subInventory->calculateRequisitionQuantities($warehouseCode);
        
        $this->assertIsArray($result);
    }

    /**
     * Test getting current stock
     */
    public function testGetCurrentStock()
    {
        $drug = $this->createTestDrug();
        $warehouseCode = 'SW001';
        
        $result = $this->subInventory->getCurrentStock($warehouseCode, $drug['id']);
        
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    /**
     * Test getting average usage rate
     */
    public function testGetAverageUsageRate()
    {
        $drug = $this->createTestDrug();
        $warehouseCode = 'SW001';
        
        $result = $this->subInventory->getAverageUsageRate($warehouseCode, $drug['id'], 30);
        
        $this->assertIsNumeric($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    /**
     * Test creating requisition
     */
    public function testCreateRequisition()
    {
        $drug = $this->createTestDrug();
        
        $data = [
            'warehouse_code' => 'SW001',
            'requisition_no' => 'REQ' . time(),
            'requisition_date' => date('Y-m-d'),
            'items' => [
                [
                    'drug_id' => $drug['id'],
                    'quantity' => 100
                ]
            ]
        ];
        
        $result = $this->subInventory->createRequisition($data);
        
        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    /**
     * Test getting pending requests
     */
    public function testGetPendingRequests()
    {
        $warehouseCode = 'SW001';
        
        $result = $this->subInventory->getPendingRequests($warehouseCode);
        
        $this->assertIsArray($result);
    }

    /**
     * Test FEFO stock deduction
     */
    public function testDeductStockFEFO()
    {
        $drug = $this->createTestDrug();
        $warehouseCode = 'SW001';
        
        // This will return array of lots or empty array
        $result = $this->subInventory->deductStockFEFO($warehouseCode, $drug['id'], 10);
        
        $this->assertIsArray($result);
    }

    /**
     * Test getting requisition formulas
     */
    public function testGetRequisitionFormulas()
    {
        $warehouseCode = 'SW001';
        
        $result = $this->subInventory->getRequisitionFormulas($warehouseCode);
        
        // May be null or array
        $this->assertTrue($result === null || $result === false || is_array($result));
    }

    /**
     * Test saving requisition formula
     */
    public function testSaveRequisitionFormula()
    {
        $data = [
            'warehouse_code' => 'SW001',
            'formula_type' => 'min_max',
            'config' => json_encode([
                'min_days' => 7,
                'max_days' => 30
            ])
        ];
        
        $result = $this->subInventory->saveRequisitionFormula($data);
        
        $this->assertTrue($result);
    }
}
