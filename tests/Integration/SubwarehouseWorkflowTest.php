<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\Subwarehouse;
use App\Models\Requisition;
use App\Models\Inventory;

class SubwarehouseWorkflowTest extends TestCase
{
    /**
     * Test complete subwarehouse requisition workflow
     * 
     * This test covers:
     * 1. Creating a subwarehouse
     * 2. Creating a requisition
     * 3. Approving the requisition
     * 4. Dispensing to subwarehouse
     * 5. Verifying stock updates
     */
    public function testCompleteRequisitionWorkflow()
    {
        $subwarehouse = new Subwarehouse($this->db);
        $requisition = new Requisition($this->db);
        $inventory = new Inventory();
        
        // Step 1: Create subwarehouse
        $warehouseData = [
            'code' => 'SW' . time(),
            'name' => 'คลังทดสอบ Workflow',
            'location' => 'ชั้น 1',
            'responsible_person' => 'ผู้รับผิดชอบ',
            'status' => 'active'
        ];
        
        $subwarehouse->create($warehouseData);
        $warehouse = $subwarehouse->getByCode($warehouseData['code']);
        $this->assertIsArray($warehouse);
        
        // Step 2: Create drug and add to main inventory
        $drug = $this->createTestDrug();
        $this->createTestInventory($drug['id'], 500);
        
        $initialStock = $inventory->getCurrentStock($drug['id']);
        $this->assertEquals(500, $initialStock);
        
        // Step 3: Create requisition
        $reqData = [
            'subwarehouse_id' => $warehouse['id'],
            'requisition_no' => 'REQ' . time(),
            'requisition_date' => date('Y-m-d'),
            'requested_by' => 1,
            'status' => 'pending'
        ];
        
        $reqItems = [
            [
                'drug_id' => $drug['id'],
                'requested_quantity' => 100
            ]
        ];
        
        $reqId = $requisition->create($reqData, $reqItems);
        $this->assertIsInt($reqId);
        $this->assertGreaterThan(0, $reqId);
        
        // Step 4: Approve requisition
        $approveItems = [
            [
                'drug_id' => $drug['id'],
                'approved_quantity' => 100
            ]
        ];
        
        $approved = $requisition->approve($reqId, 1, $approveItems);
        $this->assertTrue($approved);
        
        // Step 5: Verify main stock was deducted
        $finalStock = $inventory->getCurrentStock($drug['id']);
        $this->assertEquals(400, $finalStock, 'Main stock should be reduced from 500 to 400');
        
        // Step 6: Verify requisition status
        $reqRecord = $requisition->getById($reqId);
        $this->assertEquals('approved', $reqRecord['status']);
    }

    /**
     * Test requisition rejection workflow
     */
    public function testRequisitionRejectionWorkflow()
    {
        $subwarehouse = new Subwarehouse($this->db);
        $requisition = new Requisition($this->db);
        $inventory = new Inventory();
        
        // Create subwarehouse
        $warehouseData = [
            'code' => 'SW' . time(),
            'name' => 'คลังทดสอบ Rejection',
            'location' => 'ชั้น 2',
            'responsible_person' => 'ผู้รับผิดชอบ 2',
            'status' => 'active'
        ];
        
        $subwarehouse->create($warehouseData);
        $warehouse = $subwarehouse->getByCode($warehouseData['code']);
        
        // Create drug and inventory
        $drug = $this->createTestDrug();
        $this->createTestInventory($drug['id'], 300);
        
        $initialStock = $inventory->getCurrentStock($drug['id']);
        
        // Create requisition
        $reqData = [
            'subwarehouse_id' => $warehouse['id'],
            'requisition_no' => 'REQ' . time(),
            'requisition_date' => date('Y-m-d'),
            'requested_by' => 1,
            'status' => 'pending'
        ];
        
        $reqItems = [
            [
                'drug_id' => $drug['id'],
                'requested_quantity' => 50
            ]
        ];
        
        $reqId = $requisition->create($reqData, $reqItems);
        
        // Reject requisition
        $rejected = $requisition->reject($reqId, 1, 'ไม่อนุมัติเนื่องจากสต็อกไม่เพียงพอ');
        $this->assertTrue($rejected);
        
        // Verify stock was NOT deducted
        $finalStock = $inventory->getCurrentStock($drug['id']);
        $this->assertEquals($initialStock, $finalStock, 'Stock should remain unchanged after rejection');
        
        // Verify requisition status
        $reqRecord = $requisition->getById($reqId);
        $this->assertEquals('rejected', $reqRecord['status']);
    }
}
