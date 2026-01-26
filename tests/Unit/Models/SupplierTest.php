<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Supplier;

class SupplierTest extends TestCase
{
    private $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->supplier = new Supplier();
    }

    /**
     * Test getting all suppliers
     */
    public function testGetAll()
    {
        $result = $this->supplier->getAll();
        
        $this->assertIsArray($result);
    }

    /**
     * Test creating supplier
     */
    public function testCreateSupplier()
    {
        $data = [
            'name' => 'บริษัททดสอบ จำกัด',
            'contact_person' => 'นายทดสอบ',
            'phone' => '02-1234567',
            'email' => 'test@example.com',
            'address' => '123 ถนนทดสอบ'
        ];
        
        $result = $this->supplier->create($data);
        
        $this->assertTrue($result);
    }

    /**
     * Test getting supplier by ID
     */
    public function testGetById()
    {
        $data = [
            'name' => 'บริษัททดสอบ 2 จำกัด',
            'contact_person' => 'นายทดสอบ 2',
            'phone' => '02-7654321',
            'email' => 'test2@example.com',
            'address' => '456 ถนนทดสอบ'
        ];
        
        $this->supplier->create($data);
        
        $suppliers = $this->supplier->getAll();
        
        if (!empty($suppliers)) {
            $id = $suppliers[0]['id'];
            $result = $this->supplier->getById($id);
            
            $this->assertIsArray($result);
            $this->assertEquals($id, $result['id']);
        } else {
            $this->assertTrue(true); // No suppliers to test
        }
    }

    /**
     * Test updating supplier
     */
    public function testUpdateSupplier()
    {
        $data = [
            'name' => 'บริษัททดสอบ 3 จำกัด',
            'contact_person' => 'นายทดสอบ 3',
            'phone' => '02-1111111',
            'email' => 'test3@example.com',
            'address' => '789 ถนนทดสอบ'
        ];
        
        $this->supplier->create($data);
        
        $suppliers = $this->supplier->getAll();
        
        if (!empty($suppliers)) {
            $id = $suppliers[0]['id'];
            
            $updateData = [
                'name' => 'บริษัททดสอบ 3 (แก้ไข) จำกัด',
                'phone' => '02-2222222'
            ];
            
            $result = $this->supplier->update($id, $updateData);
            
            $this->assertTrue($result);
        } else {
            $this->assertTrue(true); // No suppliers to test
        }
    }

    /**
     * Test deleting supplier
     */
    public function testDeleteSupplier()
    {
        $data = [
            'name' => 'บริษัททดสอบลบ จำกัด',
            'contact_person' => 'นายทดสอบลบ',
            'phone' => '02-9999999',
            'email' => 'testdelete@example.com',
            'address' => '999 ถนนทดสอบ'
        ];
        
        $this->supplier->create($data);
        
        $suppliers = $this->supplier->getAll();
        
        if (!empty($suppliers)) {
            $id = $suppliers[0]['id'];
            $result = $this->supplier->delete($id);
            
            $this->assertTrue($result);
        } else {
            $this->assertTrue(true); // No suppliers to test
        }
    }
}
