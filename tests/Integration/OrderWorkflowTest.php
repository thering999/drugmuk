<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\Order;
use App\Models\Drug;
use App\Models\Inventory;

/**
 * Order Workflow Integration Test
 * 
 * ทดสอบ workflow การสั่งซื้อ-รับยา-เข้าคลัง แบบครบวงจร
 */
class OrderWorkflowTest extends TestCase
{
    private $orderModel;
    private $drugModel;
    private $inventoryModel;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->orderModel = new Order();
        $this->drugModel = new Drug();
        $this->inventoryModel = new Inventory();
        
        // Inject database connection
        foreach ([$this->orderModel, $this->drugModel, $this->inventoryModel] as $model) {
            $reflection = new \ReflectionClass($model);
            $property = $reflection->getProperty('db');
            $property->setAccessible(true);
            $property->setValue($model, $this->db);
        }
    }
    
    /**
     * Test: Workflow สมบูรณ์ - สั่งซื้อ → รับยา → เข้าคลัง
     */
    public function testCompleteOrderWorkflow()
    {
        // 1. สร้างยา
        $drugId = $this->createTestDrug([
            'code' => 'WF001',
            'name' => 'Workflow Test Drug',
            'price' => 10.00,
        ]);
        
        // 2. สร้างคำสั่งซื้อ
        $orderId = $this->createTestOrder([
            'order_no' => 'PO_WF_001',
            'status' => 'pending',
            'total_amount' => 1000.00,
        ]);
        
        // 3. เพิ่มรายการในคำสั่งซื้อ
        $sql = "INSERT INTO order_items (order_id, drug_id, quantity, unit_price) 
                VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId, $drugId, 100, 10.00]);
        
        // 4. อนุมัติคำสั่งซื้อ
        $sql = "UPDATE orders SET status = 'approved' WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        
        // 5. รับยาเข้าคลัง
        $this->inventoryModel->receive([
            'drug_id' => $drugId,
            'lot_no' => 'WF_LOT001',
            'expire_date' => date('Y-m-d', strtotime('+1 year')),
            'quantity' => 100,
            'cost_price' => 10.00,
        ]);
        
        // 6. ตรวจสอบสต็อก
        $stock = $this->inventoryModel->getCurrentStock($drugId);
        $this->assertEquals(100, $stock);
        
        // 7. อัพเดทสถานะคำสั่งซื้อเป็น completed
        $sql = "UPDATE orders SET status = 'completed' WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        
        // 8. ตรวจสอบสถานะสุดท้าย
        $sql = "SELECT status FROM orders WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        
        $this->assertEquals('completed', $order['status']);
    }
    
    /**
     * Test: รับยาบางส่วน (Partial Receiving)
     */
    public function testPartialReceiving()
    {
        // สั่งซื้อ 100 แต่รับมาเพียง 60
        $drugId = $this->createTestDrug();
        $orderId = $this->createTestOrder();
        
        // เพิ่มรายการสั่งซื้อ 100
        $sql = "INSERT INTO order_items (order_id, drug_id, quantity, unit_price) 
                VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId, $drugId, 100, 10.00]);
        
        // รับยาเพียง 60
        $this->inventoryModel->receive([
            'drug_id' => $drugId,
            'lot_no' => 'PARTIAL_LOT001',
            'expire_date' => date('Y-m-d', strtotime('+1 year')),
            'quantity' => 60,
            'cost_price' => 10.00,
        ]);
        
        // ตรวจสอบสต็อก
        $stock = $this->inventoryModel->getCurrentStock($drugId);
        $this->assertEquals(60, $stock);
        
        // สถานะควรเป็น partial
        $sql = "UPDATE orders SET status = 'partial' WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        
        $sql = "SELECT status FROM orders WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        
        $this->assertEquals('partial', $order['status']);
    }
    
    /**
     * Test: ยกเลิกคำสั่งซื้อ
     */
    public function testCancelOrder()
    {
        $orderId = $this->createTestOrder(['status' => 'pending']);
        
        // ยกเลิก
        $sql = "UPDATE orders SET status = 'cancelled' WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([$orderId]);
        
        $this->assertTrue($result);
        
        // ตรวจสอบสถานะ
        $sql = "SELECT status FROM orders WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        
        $this->assertEquals('cancelled', $order['status']);
    }
}
