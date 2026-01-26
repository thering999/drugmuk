<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Requisition;

class RequisitionTest extends TestCase
{
    private $requisition;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requisition = new Requisition($this->db);
    }

    /**
     * Test getting all requisitions
     */
    public function testGetAll()
    {
        $result = $this->requisition->getAll();
        
        $this->assertIsArray($result);
    }

    /**
     * Test getting requisitions by status
     */
    public function testGetByStatus()
    {
        $result = $this->requisition->getAll(null, 'pending');
        
        $this->assertIsArray($result);
        
        foreach ($result as $req) {
            $this->assertEquals('pending', $req['status']);
        }
    }

    /**
     * Test creating requisition
     */
    public function testCreateRequisition()
    {
        $drug = $this->createTestDrug();
        
        $data = [
            'subwarehouse_id' => 1,
            'requisition_no' => 'REQ' . time(),
            'requisition_date' => date('Y-m-d'),
            'requested_by' => 1,
            'status' => 'pending'
        ];
        
        $items = [
            [
                'drug_id' => $drug['id'],
                'requested_quantity' => 100
            ]
        ];
        
        $result = $this->requisition->create($data, $items);
        
        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    /**
     * Test getting requisition by ID
     */
    public function testGetById()
    {
        $drug = $this->createTestDrug();
        
        $data = [
            'subwarehouse_id' => 1,
            'requisition_no' => 'REQ' . time(),
            'requisition_date' => date('Y-m-d'),
            'requested_by' => 1,
            'status' => 'pending'
        ];
        
        $items = [
            [
                'drug_id' => $drug['id'],
                'requested_quantity' => 50
            ]
        ];
        
        $id = $this->requisition->create($data, $items);
        $result = $this->requisition->getById($id);
        
        $this->assertIsArray($result);
        $this->assertEquals($id, $result['id']);
    }

    /**
     * Test getting requisition items
     */
    public function testGetItems()
    {
        $drug = $this->createTestDrug();
        
        $data = [
            'subwarehouse_id' => 1,
            'requisition_no' => 'REQ' . time(),
            'requisition_date' => date('Y-m-d'),
            'requested_by' => 1,
            'status' => 'pending'
        ];
        
        $items = [
            [
                'drug_id' => $drug['id'],
                'requested_quantity' => 75
            ]
        ];
        
        $id = $this->requisition->create($data, $items);
        $result = $this->requisition->getItems($id);
        
        $this->assertIsArray($result);
        $this->assertGreaterThan(0, count($result));
    }

    /**
     * Test approving requisition
     */
    public function testApproveRequisition()
    {
        $drug = $this->createTestDrug();
        $this->createTestInventory($drug['id'], 200);
        
        $data = [
            'subwarehouse_id' => 1,
            'requisition_no' => 'REQ' . time(),
            'requisition_date' => date('Y-m-d'),
            'requested_by' => 1,
            'status' => 'pending'
        ];
        
        $items = [
            [
                'drug_id' => $drug['id'],
                'requested_quantity' => 100
            ]
        ];
        
        $id = $this->requisition->create($data, $items);
        
        $approveItems = [
            [
                'drug_id' => $drug['id'],
                'approved_quantity' => 100
            ]
        ];
        
        $result = $this->requisition->approve($id, 1, $approveItems);
        
        $this->assertTrue($result);
    }

    /**
     * Test rejecting requisition
     */
    public function testRejectRequisition()
    {
        $drug = $this->createTestDrug();
        
        $data = [
            'subwarehouse_id' => 1,
            'requisition_no' => 'REQ' . time(),
            'requisition_date' => date('Y-m-d'),
            'requested_by' => 1,
            'status' => 'pending'
        ];
        
        $items = [
            [
                'drug_id' => $drug['id'],
                'requested_quantity' => 50
            ]
        ];
        
        $id = $this->requisition->create($data, $items);
        $result = $this->requisition->reject($id, 1, 'ไม่อนุมัติ');
        
        $this->assertTrue($result);
    }

    /**
     * Test canceling requisition
     */
    public function testCancelRequisition()
    {
        $drug = $this->createTestDrug();
        
        $data = [
            'subwarehouse_id' => 1,
            'requisition_no' => 'REQ' . time(),
            'requisition_date' => date('Y-m-d'),
            'requested_by' => 1,
            'status' => 'pending'
        ];
        
        $items = [
            [
                'drug_id' => $drug['id'],
                'requested_quantity' => 25
            ]
        ];
        
        $id = $this->requisition->create($data, $items);
        $result = $this->requisition->cancel($id, 'ยกเลิกการขอเบิก');
        
        $this->assertTrue($result);
    }
}
