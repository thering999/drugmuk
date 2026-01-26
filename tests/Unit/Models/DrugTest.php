<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Drug;

/**
 * Drug Model Test
 * 
 * ทดสอบ CRUD operations และ business logic ของ Drug model
 */
class DrugTest extends TestCase
{
    private $drugModel;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->drugModel = new Drug();
        
        // Inject test database connection
        $reflection = new \ReflectionClass($this->drugModel);
        $property = $reflection->getProperty('db');
        $property->setAccessible(true);
        $property->setValue($this->drugModel, $this->db);
    }
    
    /**
     * Test: สร้างยาใหม่สำเร็จ
     */
    public function testCreateDrugSuccess()
    {
        $data = [
            'code' => 'TEST001',
            'name' => 'Paracetamol 500mg',
            'generic_name' => 'Paracetamol',
            'unit' => 'tablet',
            'pack_size' => 100,
            'price' => 2.50,
            'min_stock' => 100,
            'max_stock' => 1000,
            'category' => 'analgesic',
            'is_active' => 1,
        ];
        
        $result = $this->drugModel->create($data);
        
        $this->assertTrue($result);
    }
    
    /**
     * Test: ดึงข้อมูลยาทั้งหมด
     */
    public function testGetAllDrugs()
    {
        // สร้างยาทดสอบ 3 รายการ
        $this->createTestDrug(['name' => 'Drug A']);
        $this->createTestDrug(['name' => 'Drug B']);
        $this->createTestDrug(['name' => 'Drug C']);
        
        $drugs = $this->drugModel->getAll();
        
        $this->assertIsArray($drugs);
        $this->assertGreaterThanOrEqual(3, count($drugs));
    }
    
    /**
     * Test: ดึงข้อมูลยาตาม ID
     */
    public function testGetDrugById()
    {
        $drugId = $this->createTestDrug([
            'code' => 'TEST002',
            'name' => 'Amoxicillin 500mg',
        ]);
        
        $drug = $this->drugModel->getById($drugId);
        
        $this->assertNotNull($drug);
        $this->assertEquals($drugId, $drug['id']);
        $this->assertEquals('TEST002', $drug['code']);
        $this->assertEquals('Amoxicillin 500mg', $drug['name']);
    }
    
    /**
     * Test: ดึงข้อมูลยาที่ไม่มีอยู่
     */
    public function testGetNonExistentDrug()
    {
        $drug = $this->drugModel->getById(999999);
        
        $this->assertFalse($drug);
    }
    
    /**
     * Test: ดึงเฉพาะยาที่ active
     */
    public function testGetActiveDrugsOnly()
    {
        // สร้างยา active
        $this->createTestDrug(['name' => 'Active Drug', 'is_active' => 1]);
        
        // สร้างยา inactive
        $this->createTestDrug(['name' => 'Inactive Drug', 'is_active' => 0]);
        
        $activeDrugs = $this->drugModel->getActiveDrugs();
        
        $this->assertIsArray($activeDrugs);
        
        // ตรวจสอบว่าทุกรายการเป็น active
        foreach ($activeDrugs as $drug) {
            $this->assertEquals(1, $drug['is_active']);
        }
    }
    
    /**
     * Test: ค้นหายาด้วยชื่อ
     */
    public function testSearchDrugsByName()
    {
        $this->createTestDrug(['name' => 'Paracetamol 500mg']);
        $this->createTestDrug(['name' => 'Amoxicillin 250mg']);
        
        $results = $this->drugModel->search('para');
        
        $this->assertIsArray($results);
        $this->assertGreaterThan(0, count($results));
        
        // ตรวจสอบว่าผลลัพธ์มีคำว่า 'para'
        $found = false;
        foreach ($results as $drug) {
            if (stripos($drug['name'], 'para') !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }
    
    /**
     * Test: ค้นหายาด้วยรหัส
     */
    public function testSearchDrugsByCode()
    {
        $this->createTestDrug(['code' => 'ABC123', 'name' => 'Test Drug']);
        
        $results = $this->drugModel->search('ABC');
        
        $this->assertIsArray($results);
        $this->assertGreaterThan(0, count($results));
    }
    
    /**
     * Test: ค้นหายาที่ไม่มี
     */
    public function testSearchNonExistentDrug()
    {
        $results = $this->drugModel->search('NONEXISTENT999');
        
        $this->assertIsArray($results);
        $this->assertEquals(0, count($results));
    }
    
    /**
     * Test: อัพเดทข้อมูลยา
     */
    public function testUpdateDrug()
    {
        $drugId = $this->createTestDrug([
            'code' => 'TEST003',
            'name' => 'Original Name',
            'price' => 10.00,
        ]);
        
        $updateData = [
            'code' => 'TEST003',
            'name' => 'Updated Name',
            'generic_name' => 'Updated Generic',
            'unit' => 'tablet',
            'pack_size' => 100,
            'price' => 15.00,
            'min_stock' => 50,
            'max_stock' => 500,
            'category' => 'antibiotic',
            'is_active' => 1,
        ];
        
        $result = $this->drugModel->update($drugId, $updateData);
        
        $this->assertTrue($result);
        
        // ตรวจสอบว่าข้อมูลถูกอัพเดทจริง
        $updatedDrug = $this->drugModel->getById($drugId);
        $this->assertEquals('Updated Name', $updatedDrug['name']);
        $this->assertEquals(15.00, $updatedDrug['price']);
    }
    
    /**
     * Test: ลบยา (soft delete)
     */
    public function testDeleteDrug()
    {
        $drugId = $this->createTestDrug(['name' => 'Drug to Delete']);
        
        $result = $this->drugModel->delete($drugId);
        
        $this->assertTrue($result);
        
        // ตรวจสอบว่ายาถูก soft delete (is_active = 0)
        $deletedDrug = $this->drugModel->getById($drugId);
        $this->assertEquals(0, $deletedDrug['is_active']);
    }
    
    /**
     * Test: ดึงยาตามหมวดหมู่
     */
    public function testGetDrugsByCategory()
    {
        $this->createTestDrug(['category' => 'antibiotic', 'name' => 'Antibiotic 1']);
        $this->createTestDrug(['category' => 'antibiotic', 'name' => 'Antibiotic 2']);
        $this->createTestDrug(['category' => 'analgesic', 'name' => 'Analgesic 1']);
        
        $antibiotics = $this->drugModel->getByCategory('antibiotic');
        
        $this->assertIsArray($antibiotics);
        $this->assertGreaterThanOrEqual(2, count($antibiotics));
        
        // ตรวจสอบว่าทุกรายการเป็น antibiotic
        foreach ($antibiotics as $drug) {
            $this->assertEquals('antibiotic', $drug['category']);
        }
    }
    
    /**
     * Test: ตรวจสอบโครงสร้างข้อมูลยา
     */
    public function testDrugStructure()
    {
        $drugId = $this->createTestDrug();
        $drug = $this->drugModel->getById($drugId);
        
        $requiredFields = [
            'id', 'code', 'name', 'generic_name', 'unit', 
            'price', 'min_stock', 'max_stock', 'is_active'
        ];
        
        $this->assertArrayHasKeys($requiredFields, $drug);
    }
    
    /**
     * Test: ราคาต้องเป็นตัวเลข
     */
    public function testDrugPriceIsNumeric()
    {
        $drugId = $this->createTestDrug(['price' => 25.50]);
        $drug = $this->drugModel->getById($drugId);
        
        $this->assertIsNumeric($drug['price']);
        $this->assertEquals(25.50, floatval($drug['price']));
    }
    
    /**
     * Test: min_stock ต้องน้อยกว่า max_stock
     */
    public function testMinStockLessThanMaxStock()
    {
        $drugId = $this->createTestDrug([
            'min_stock' => 50,
            'max_stock' => 500,
        ]);
        
        $drug = $this->drugModel->getById($drugId);
        
        $this->assertLessThan($drug['max_stock'], $drug['min_stock']);
    }
}
