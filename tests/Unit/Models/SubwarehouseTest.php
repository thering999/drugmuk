<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Subwarehouse;

class SubwarehouseTest extends TestCase
{
    private $subwarehouse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subwarehouse = new Subwarehouse($this->db);
    }

    /**
     * Test getting all subwarehouses
     */
    public function testGetAll()
    {
        $result = $this->subwarehouse->getAll();
        
        $this->assertIsArray($result);
    }

    /**
     * Test getting active subwarehouses only
     */
    public function testGetActiveOnly()
    {
        $result = $this->subwarehouse->getAll(true);
        
        $this->assertIsArray($result);
        
        foreach ($result as $warehouse) {
            $this->assertEquals('active', $warehouse['status']);
        }
    }

    /**
     * Test creating subwarehouse
     */
    public function testCreateSubwarehouse()
    {
        $data = [
            'code' => 'SW' . time(),
            'name' => 'คลังทดสอบ',
            'location' => 'ชั้น 1',
            'responsible_person' => 'ผู้รับผิดชอบ',
            'status' => 'active'
        ];
        
        $result = $this->subwarehouse->create($data);
        
        $this->assertTrue($result);
    }

    /**
     * Test getting subwarehouse by ID
     */
    public function testGetById()
    {
        $data = [
            'code' => 'SW' . time(),
            'name' => 'คลังทดสอบ 2',
            'location' => 'ชั้น 2',
            'responsible_person' => 'ผู้รับผิดชอบ 2',
            'status' => 'active'
        ];
        
        $this->subwarehouse->create($data);
        $result = $this->subwarehouse->getByCode($data['code']);
        
        $this->assertIsArray($result);
        $this->assertEquals($data['code'], $result['code']);
    }

    /**
     * Test updating subwarehouse
     */
    public function testUpdateSubwarehouse()
    {
        $data = [
            'code' => 'SW' . time(),
            'name' => 'คลังทดสอบ 3',
            'location' => 'ชั้น 3',
            'responsible_person' => 'ผู้รับผิดชอบ 3',
            'status' => 'active'
        ];
        
        $this->subwarehouse->create($data);
        $warehouse = $this->subwarehouse->getByCode($data['code']);
        
        $updateData = [
            'name' => 'คลังทดสอบ 3 (แก้ไข)',
            'location' => 'ชั้น 3 (แก้ไข)',
            'responsible_person' => 'ผู้รับผิดชอบใหม่',
            'status' => 'active'
        ];
        
        $result = $this->subwarehouse->update($warehouse['id'], $updateData);
        
        $this->assertTrue($result);
    }

    /**
     * Test getting inventory for subwarehouse
     */
    public function testGetInventory()
    {
        $data = [
            'code' => 'SW' . time(),
            'name' => 'คลังทดสอบ 4',
            'location' => 'ชั้น 4',
            'responsible_person' => 'ผู้รับผิดชอบ 4',
            'status' => 'active'
        ];
        
        $this->subwarehouse->create($data);
        $warehouse = $this->subwarehouse->getByCode($data['code']);
        
        $result = $this->subwarehouse->getInventory($warehouse['id']);
        
        $this->assertIsArray($result);
    }

    /**
     * Test getting low stock drugs
     */
    public function testGetLowStockDrugs()
    {
        $data = [
            'code' => 'SW' . time(),
            'name' => 'คลังทดสอบ 5',
            'location' => 'ชั้น 5',
            'responsible_person' => 'ผู้รับผิดชอบ 5',
            'status' => 'active'
        ];
        
        $this->subwarehouse->create($data);
        $warehouse = $this->subwarehouse->getByCode($data['code']);
        
        $result = $this->subwarehouse->getLowStockDrugs($warehouse['id']);
        
        $this->assertIsArray($result);
    }

    /**
     * Test getting statistics
     */
    public function testGetStatistics()
    {
        $data = [
            'code' => 'SW' . time(),
            'name' => 'คลังทดสอบ 6',
            'location' => 'ชั้น 6',
            'responsible_person' => 'ผู้รับผิดชอบ 6',
            'status' => 'active'
        ];
        
        $this->subwarehouse->create($data);
        $warehouse = $this->subwarehouse->getByCode($data['code']);
        
        $result = $this->subwarehouse->getStatistics($warehouse['id']);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_drugs', $result);
    }

    /**
     * Test deleting subwarehouse
     */
    public function testDeleteSubwarehouse()
    {
        $data = [
            'code' => 'SW' . time(),
            'name' => 'คลังทดสอบ 7',
            'location' => 'ชั้น 7',
            'responsible_person' => 'ผู้รับผิดชอบ 7',
            'status' => 'active'
        ];
        
        $this->subwarehouse->create($data);
        $warehouse = $this->subwarehouse->getByCode($data['code']);
        
        $result = $this->subwarehouse->delete($warehouse['id']);
        
        $this->assertTrue($result);
    }
}
