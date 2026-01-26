<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\Dispensing;
use App\Models\Inventory;

class DispensingWorkflowTest extends TestCase
{
    /**
     * Test complete dispensing workflow
     * 
     * This test covers:
     * 1. Creating a drug
     * 2. Adding inventory
     * 3. Creating a dispensing record
     * 4. Verifying stock deduction
     * 5. Checking transaction history
     */
    public function testCompleteDispensingWorkflow()
    {
        $dispensing = new Dispensing();
        $inventory = new Inventory();
        
        // Step 1: Create test drug
        $drug = $this->createTestDrug();
        $this->assertNotNull($drug);
        $this->assertArrayHasKey('id', $drug);
        
        // Step 2: Add inventory (100 units)
        $inventoryData = $this->createTestInventory($drug['id'], 100);
        $this->assertNotNull($inventoryData);
        
        // Verify initial stock
        $initialStock = $inventory->getCurrentStock($drug['id']);
        $this->assertEquals(100, $initialStock);
        
        // Step 3: Create dispensing record (dispense 30 units)
        $dispenseData = [
            'dispense_date' => date('Y-m-d'),
            'hn' => 'HN' . time(),
            'patient_name' => 'ทดสอบ Workflow',
            'items' => [
                [
                    'drug_id' => $drug['id'],
                    'quantity' => 30
                ]
            ]
        ];
        
        $dispenseId = $dispensing->create($dispenseData);
        $this->assertIsInt($dispenseId);
        $this->assertGreaterThan(0, $dispenseId);
        
        // Step 4: Verify stock was deducted
        $finalStock = $inventory->getCurrentStock($drug['id']);
        $this->assertEquals(70, $finalStock, 'Stock should be reduced from 100 to 70');
        
        // Step 5: Verify dispensing record was created
        $dispenseRecord = $dispensing->getById($dispenseId);
        $this->assertIsArray($dispenseRecord);
        $this->assertEquals($dispenseId, $dispenseRecord['id']);
        
        // Step 6: Verify dispensing items
        $items = $dispensing->getItems($dispenseId);
        $this->assertIsArray($items);
        $this->assertCount(1, $items);
        $this->assertEquals(30, $items[0]['quantity']);
    }

    /**
     * Test dispensing with multiple drugs
     */
    public function testDispensingMultipleDrugs()
    {
        $dispensing = new Dispensing();
        $inventory = new Inventory();
        
        // Create two drugs
        $drug1 = $this->createTestDrug();
        $drug2 = $this->createTestDrug();
        
        // Add inventory for both
        $this->createTestInventory($drug1['id'], 100);
        $this->createTestInventory($drug2['id'], 200);
        
        // Dispense both drugs
        $dispenseData = [
            'dispense_date' => date('Y-m-d'),
            'hn' => 'HN' . time(),
            'patient_name' => 'ทดสอบ Multiple Drugs',
            'items' => [
                [
                    'drug_id' => $drug1['id'],
                    'quantity' => 25
                ],
                [
                    'drug_id' => $drug2['id'],
                    'quantity' => 50
                ]
            ]
        ];
        
        $dispenseId = $dispensing->create($dispenseData);
        $this->assertIsInt($dispenseId);
        
        // Verify both stocks were deducted
        $stock1 = $inventory->getCurrentStock($drug1['id']);
        $stock2 = $inventory->getCurrentStock($drug2['id']);
        
        $this->assertEquals(75, $stock1);
        $this->assertEquals(150, $stock2);
        
        // Verify items count
        $items = $dispensing->getItems($dispenseId);
        $this->assertCount(2, $items);
    }
}
