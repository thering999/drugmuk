<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Order;

/**
 * Order Model Test
 * 
 * ทดสอบการจัดการคำสั่งซื้อ order workflow และ business logic
 */
class OrderTest extends TestCase
{
    private $orderModel;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->orderModel = new Order();
        
        // Inject test database connection
        $reflection = new \ReflectionClass($this->orderModel);
        $property = $reflection->getProperty('db');
        $property->setAccessible(true);
        $property->setValue($this->orderModel, $this->db);
    }
    
    /**
     * Test: สร้างคำสั่งซื้อใหม่
     */
    public function testCreateOrder()
    {
        $orderId = $this->createTestOrder([
            'order_no' => 'PO2026001',
            'status' => 'pending',
        ]);
        
        $this->assertGreaterThan(0, $orderId);
    }
    
    /**
     * Test: ดึงข้อมูลคำสั่งซื้อทั้งหมด
     */
    public function testGetAllOrders()
    {
        $this->createTestOrder(['order_no' => 'PO2026001']);
        $this->createTestOrder(['order_no' => 'PO2026002']);
        
        $sql = "SELECT * FROM orders";
        $orders = $this->db->query($sql)->fetchAll();
        
        $this->assertIsArray($orders);
        $this->assertGreaterThanOrEqual(2, count($orders));
    }
    
    /**
     * Test: ดึงข้อมูลคำสั่งซื้อตาม ID
     */
    public function testGetOrderById()
    {
        $orderId = $this->createTestOrder(['order_no' => 'PO2026003']);
        
        $sql = "SELECT * FROM orders WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        
        $this->assertNotNull($order);
        $this->assertEquals('PO2026003', $order['order_no']);
    }
    
    /**
     * Test: อัพเดทสถานะคำสั่งซื้อ
     */
    public function testUpdateOrderStatus()
    {
        $orderId = $this->createTestOrder(['status' => 'pending']);
        
        // อัพเดทเป็น approved
        $sql = "UPDATE orders SET status = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute(['approved', $orderId]);
        
        $this->assertTrue($result);
        
        // ตรวจสอบ
        $sql = "SELECT status FROM orders WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        
        $this->assertEquals('approved', $order['status']);
    }
    
    /**
     * Test: คำนวณมูลค่ารวมของคำสั่งซื้อ
     */
    public function testCalculateOrderTotal()
    {
        $orderId = $this->createTestOrder(['total_amount' => 0]);
        
        // เพิ่มรายการสินค้า
        $drugId1 = $this->createTestDrug(['price' => 10.00]);
        $drugId2 = $this->createTestDrug(['price' => 20.00]);
        
        $sql = "INSERT INTO order_items (order_id, drug_id, quantity, unit_price) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId, $drugId1, 10, 10.00]); // 100
        $stmt->execute([$orderId, $drugId2, 5, 20.00]);  // 100
        
        // คำนวณมูลค่ารวม
        $sql = "SELECT SUM(quantity * unit_price) as total FROM order_items WHERE order_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        $result = $stmt->fetch();
        
        $this->assertEquals(200.00, $result['total']);
    }
    
    /**
     * Test: ดึงคำสั่งซื้อตามสถานะ
     */
    public function testGetOrdersByStatus()
    {
        $this->createTestOrder(['status' => 'pending']);
        $this->createTestOrder(['status' => 'approved']);
        $this->createTestOrder(['status' => 'pending']);
        
        $sql = "SELECT * FROM orders WHERE status = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['pending']);
        $pendingOrders = $stmt->fetchAll();
        
        $this->assertGreaterThanOrEqual(2, count($pendingOrders));
        
        foreach ($pendingOrders as $order) {
            $this->assertEquals('pending', $order['status']);
        }
    }
    
    /**
     * Test: ตรวจสอบโครงสร้างข้อมูลคำสั่งซื้อ
     */
    public function testOrderStructure()
    {
        $orderId = $this->createTestOrder();
        
        $sql = "SELECT * FROM orders WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        
        $requiredFields = [
            'id', 'order_no', 'order_date', 'status', 
            'total_amount', 'created_by', 'created_at'
        ];
        
        $this->assertArrayHasKeys($requiredFields, $order);
    }
    
    /**
     * Test: ลบคำสั่งซื้อ
     */
    public function testDeleteOrder()
    {
        $orderId = $this->createTestOrder();
        
        $sql = "DELETE FROM orders WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([$orderId]);
        
        $this->assertTrue($result);
        
        // ตรวจสอบว่าถูกลบแล้ว
        $sql = "SELECT * FROM orders WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        
        $this->assertFalse($order);
    }
    
    /**
     * Test: order_no ต้องไม่ซ้ำ
     */
    public function testUniqueOrderNo()
    {
        $orderNo = 'PO' . date('Ymd') . rand(1000, 9999);
        
        $this->createTestOrder(['order_no' => $orderNo]);
        
        // พยายามสร้างคำสั่งซื้อเลขที่ซ้ำ
        try {
            $this->createTestOrder(['order_no' => $orderNo]);
            $this->fail('Should throw exception for duplicate order_no');
        } catch (\PDOException $e) {
            // Expected exception
            $this->assertStringContainsString('Duplicate', $e->getMessage());
        }
    }
    
    /**
     * Test: total_amount ต้องเป็นตัวเลข
     */
    public function testOrderTotalIsNumeric()
    {
        $orderId = $this->createTestOrder(['total_amount' => 1250.50]);
        
        $sql = "SELECT total_amount FROM orders WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        
        $this->assertIsNumeric($order['total_amount']);
        $this->assertEquals(1250.50, floatval($order['total_amount']));
    }
}
