<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Inventory;

/**
 * Inventory Model Test
 * 
 * ทดสอบการจัดการสต็อก FEFO logic และ business logic ต่างๆ
 */
class InventoryTest extends TestCase
{
    private $inventoryModel;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->inventoryModel = new Inventory();
        
        // Inject test database connection
        $reflection = new \ReflectionClass($this->inventoryModel);
        $property = $reflection->getProperty('db');
        $property->setAccessible(true);
        $property->setValue($this->inventoryModel, $this->db);
    }
    
    /**
     * Test: รับยาเข้าคลัง
     */
    public function testReceiveInventory()
    {
        $drugId = $this->createTestDrug();
        
        $data = [
            'drug_id' => $drugId,
            'lot_no' => 'LOT001',
            'expire_date' => date('Y-m-d', strtotime('+1 year')),
            'quantity' => 100,
            'cost_price' => 10.00,
        ];
        
        $this->inventoryModel->receive($data);
        
        // ตรวจสอบว่ามีสต็อกเพิ่มขึ้น
        $stock = $this->inventoryModel->getCurrentStock($drugId);
        $this->assertEquals(100, $stock);
    }
    
    /**
     * Test: ดึงข้อมูลสต็อกทั้งหมด
     */
    public function testGetAllInventoryWithDrugs()
    {
        $drugId = $this->createTestDrug();
        $this->createTestInventory($drugId, ['quantity' => 50]);
        
        $inventory = $this->inventoryModel->getAllWithDrugs();
        
        $this->assertIsArray($inventory);
        $this->assertGreaterThan(0, count($inventory));
    }
    
    /**
     * Test: ดึงสรุปสต็อก
     */
    public function testGetStockSummary()
    {
        $drugId1 = $this->createTestDrug();
        $drugId2 = $this->createTestDrug();
        
        $this->createTestInventory($drugId1, ['quantity' => 100, 'cost_price' => 10.00]);
        $this->createTestInventory($drugId2, ['quantity' => 50, 'cost_price' => 20.00]);
        
        $summary = $this->inventoryModel->getStockSummary();
        
        $this->assertArrayHasKey('total_items', $summary);
        $this->assertArrayHasKey('total_quantity', $summary);
        $this->assertArrayHasKey('total_value', $summary);
        
        $this->assertGreaterThanOrEqual(2, $summary['total_items']);
        $this->assertGreaterThanOrEqual(150, $summary['total_quantity']);
    }
    
    /**
     * Test: ดึงรายการสต็อกต่ำ
     */
    public function testGetLowStockItems()
    {
        // สร้างยาที่มีสต็อกต่ำกว่า min_stock
        $drugId = $this->createTestDrug([
            'min_stock' => 100,
            'max_stock' => 500,
        ]);
        
        // เพิ่มสต็อกเพียง 50 (ต่ำกว่า min_stock)
        $this->createTestInventory($drugId, ['quantity' => 50]);
        
        $lowStock = $this->inventoryModel->getLowStockItems();
        
        $this->assertIsArray($lowStock);
        $this->assertGreaterThan(0, count($lowStock));
        
        // ตรวจสอบว่ามียาที่เราสร้างอยู่ในรายการ
        $found = false;
        foreach ($lowStock as $item) {
            if ($item['id'] == $drugId) {
                $found = true;
                $this->assertLessThan($item['min_stock'], $item['current_stock']);
                break;
            }
        }
        $this->assertTrue($found);
    }
    
    /**
     * Test: นับจำนวนสต็อกต่ำ
     */
    public function testGetLowStockCount()
    {
        $count = $this->inventoryModel->getLowStockCount();
        
        $this->assertIsNumeric($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }
    
    /**
     * Test: ดึงรายการยาใกล้หมดอายุ
     */
    public function testGetExpiringItems()
    {
        $drugId = $this->createTestDrug();
        
        // สร้างสต็อกที่จะหมดอายุใน 30 วัน
        $expireDate = date('Y-m-d', strtotime('+30 days'));
        $this->createTestInventory($drugId, [
            'expire_date' => $expireDate,
            'quantity' => 100,
        ]);
        
        // ดึงรายการที่จะหมดอายุใน 90 วัน
        $expiring = $this->inventoryModel->getExpiringItems(90);
        
        $this->assertIsArray($expiring);
        $this->assertGreaterThan(0, count($expiring));
    }
    
    /**
     * Test: นับจำนวนยาใกล้หมดอายุ
     */
    public function testGetExpiringSoonCount()
    {
        $count = $this->inventoryModel->getExpiringSoonCount(90);
        
        $this->assertIsNumeric($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }
    
    /**
     * Test: ดึงสต็อกปัจจุบันของยา
     */
    public function testGetCurrentStock()
    {
        $drugId = $this->createTestDrug();
        
        // เพิ่มสต็อก 2 lot
        $this->createTestInventory($drugId, ['quantity' => 100]);
        $this->createTestInventory($drugId, ['quantity' => 50]);
        
        $stock = $this->inventoryModel->getCurrentStock($drugId);
        
        $this->assertEquals(150, $stock);
    }
    
    /**
     * Test: ดึงสต็อกของยาที่ไม่มีในคลัง
     */
    public function testGetCurrentStockForNonExistentDrug()
    {
        $stock = $this->inventoryModel->getCurrentStock(999999);
        
        $this->assertEquals(0, $stock);
    }
    
    /**
     * Test: FEFO Logic - จ่ายยาที่ใกล้หมดอายุก่อน
     */
    public function testFEFOLogic()
    {
        $drugId = $this->createTestDrug();
        
        // สร้าง 2 lots ที่มีวันหมดอายุต่างกัน
        $lot1Id = $this->createTestInventory($drugId, [
            'lot_no' => 'LOT001',
            'expire_date' => date('Y-m-d', strtotime('+6 months')), // หมดอายุก่อน
            'quantity' => 50,
        ]);
        
        $lot2Id = $this->createTestInventory($drugId, [
            'lot_no' => 'LOT002',
            'expire_date' => date('Y-m-d', strtotime('+1 year')), // หมดอายุทีหลัง
            'quantity' => 50,
        ]);
        
        // จ่ายยา 30 เม็ด (ควรจ่ายจาก LOT001 ก่อน)
        $result = $this->inventoryModel->disburseFEFO([
            'drug_id' => $drugId,
            'quantity' => 30,
        ]);
        
        $this->assertTrue($result);
        
        // ตรวจสอบว่า LOT001 ลดลง
        $lot1 = $this->db->query("SELECT quantity FROM inventory WHERE id = $lot1Id")->fetch();
        $this->assertEquals(20, $lot1['quantity']); // 50 - 30 = 20
        
        // ตรวจสอบว่า LOT002 ยังเท่าเดิม
        $lot2 = $this->db->query("SELECT quantity FROM inventory WHERE id = $lot2Id")->fetch();
        $this->assertEquals(50, $lot2['quantity']);
    }
    
    /**
     * Test: FEFO Logic - จ่ายยาข้าม lot
     */
    public function testFEFOLogicAcrossMultipleLots()
    {
        $drugId = $this->createTestDrug();
        
        // สร้าง 2 lots
        $this->createTestInventory($drugId, [
            'lot_no' => 'LOT001',
            'expire_date' => date('Y-m-d', strtotime('+6 months')),
            'quantity' => 30,
        ]);
        
        $this->createTestInventory($drugId, [
            'lot_no' => 'LOT002',
            'expire_date' => date('Y-m-d', strtotime('+1 year')),
            'quantity' => 50,
        ]);
        
        // จ่ายยา 60 เม็ด (ต้องใช้ทั้ง 2 lots)
        $result = $this->inventoryModel->disburseFEFO([
            'drug_id' => $drugId,
            'quantity' => 60,
        ]);
        
        $this->assertTrue($result);
        
        // ตรวจสอบสต็อกรวม
        $stock = $this->inventoryModel->getCurrentStock($drugId);
        $this->assertEquals(20, $stock); // 80 - 60 = 20
    }
    
    /**
     * Test: สร้างคำขอเบิกยา
     */
    public function testCreatePendingDisbursement()
    {
        $drugId = $this->createTestDrug();
        
        $data = [
            'warehouse_code' => 'sub1',
            'drug_id' => $drugId,
            'quantity_pending' => 50,
        ];
        
        $result = $this->inventoryModel->createPendingDisbursement($data);
        
        $this->assertTrue($result);
    }
    
    /**
     * Test: ดึงรายการคำขอเบิกที่รออนุมัติ
     */
    public function testGetPendingDisbursements()
    {
        $pending = $this->inventoryModel->getPendingDisbursements();
        
        $this->assertIsArray($pending);
    }
    
    /**
     * Test: นับจำนวนคำขอเบิกที่รออนุมัติ
     */
    public function testGetPendingDisbursementsCount()
    {
        $count = $this->inventoryModel->getPendingDisbursementsCount();
        
        $this->assertIsNumeric($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }
    
    /**
     * Test: ดึง Stock Card Transactions
     */
    public function testGetStockCardTransactions()
    {
        $drugId = $this->createTestDrug();
        
        // เพิ่มสต็อก (จะสร้าง transaction)
        $this->inventoryModel->receive([
            'drug_id' => $drugId,
            'lot_no' => 'LOT001',
            'expire_date' => date('Y-m-d', strtotime('+1 year')),
            'quantity' => 100,
            'cost_price' => 10.00,
        ]);
        
        $transactions = $this->inventoryModel->getStockCardTransactions($drugId);
        
        $this->assertIsArray($transactions);
        $this->assertGreaterThan(0, count($transactions));
    }
    
    /**
     * Test: ดึงรายการรับยาล่าสุด
     */
    public function testGetRecentReceives()
    {
        $receives = $this->inventoryModel->getRecentReceives(5);
        
        $this->assertIsArray($receives);
    }
}
