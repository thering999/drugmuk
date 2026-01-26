<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use Drugmuk\Models\Hospital;

class HospitalTest extends TestCase
{
    private $hospital;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hospital = new Hospital();
    }

    /**
     * Test getting all hospitals
     */
    public function testGetAll()
    {
        $result = $this->hospital->getAll();
        
        $this->assertIsArray($result);
    }

    /**
     * Test getting active hospitals only
     */
    public function testGetActiveOnly()
    {
        $result = $this->hospital->getAll(true);
        
        $this->assertIsArray($result);
        
        foreach ($result as $hosp) {
            $this->assertEquals(1, $hosp['is_active']);
        }
    }

    /**
     * Test creating hospital
     */
    public function testCreateHospital()
    {
        $data = [
            'code' => 'HOSP' . time(),
            'name' => 'โรงพยาบาลทดสอบ',
            'db_host' => 'localhost',
            'db_port' => '3306',
            'db_name' => 'jhcisdb_test',
            'db_user' => 'test_user',
            'db_password' => 'test_password',
            'is_active' => 1
        ];
        
        $result = $this->hospital->create($data);
        
        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    /**
     * Test getting hospital by ID
     */
    public function testFindById()
    {
        $data = [
            'code' => 'HOSP' . time(),
            'name' => 'โรงพยาบาลทดสอบ 2',
            'db_host' => 'localhost',
            'db_port' => '3306',
            'db_name' => 'jhcisdb_test2',
            'db_user' => 'test_user2',
            'db_password' => 'test_password2',
            'is_active' => 1
        ];
        
        $id = $this->hospital->create($data);
        $result = $this->hospital->findById($id);
        
        $this->assertIsArray($result);
        $this->assertEquals($id, $result['id']);
    }

    /**
     * Test getting hospital by code
     */
    public function testFindByCode()
    {
        $code = 'HOSP' . time();
        
        $data = [
            'code' => $code,
            'name' => 'โรงพยาบาลทดสอบ 3',
            'db_host' => 'localhost',
            'db_port' => '3306',
            'db_name' => 'jhcisdb_test3',
            'db_user' => 'test_user3',
            'db_password' => 'test_password3',
            'is_active' => 1
        ];
        
        $this->hospital->create($data);
        $result = $this->hospital->findByCode($code);
        
        $this->assertIsArray($result);
        $this->assertEquals($code, $result['code']);
    }

    /**
     * Test updating hospital
     */
    public function testUpdateHospital()
    {
        $data = [
            'code' => 'HOSP' . time(),
            'name' => 'โรงพยาบาลทดสอบ 4',
            'db_host' => 'localhost',
            'db_port' => '3306',
            'db_name' => 'jhcisdb_test4',
            'db_user' => 'test_user4',
            'db_password' => 'test_password4',
            'is_active' => 1
        ];
        
        $id = $this->hospital->create($data);
        
        $updateData = [
            'name' => 'โรงพยาบาลทดสอบ 4 (แก้ไข)',
            'is_active' => 0
        ];
        
        $result = $this->hospital->update($id, $updateData);
        
        $this->assertTrue($result);
    }

    /**
     * Test getting active hospitals for sync
     */
    public function testGetActiveForSync()
    {
        $result = $this->hospital->getActiveForSync();
        
        $this->assertIsArray($result);
        
        foreach ($result as $hosp) {
            $this->assertEquals(1, $hosp['is_active']);
        }
    }

    /**
     * Test getting statistics
     */
    public function testGetStatistics()
    {
        $result = $this->hospital->getStatistics();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_hospitals', $result);
        $this->assertArrayHasKey('active_hospitals', $result);
    }

    /**
     * Test deleting hospital
     */
    public function testDeleteHospital()
    {
        $data = [
            'code' => 'HOSP' . time(),
            'name' => 'โรงพยาบาลทดสอบลบ',
            'db_host' => 'localhost',
            'db_port' => '3306',
            'db_name' => 'jhcisdb_test_delete',
            'db_user' => 'test_user_delete',
            'db_password' => 'test_password_delete',
            'is_active' => 1
        ];
        
        $id = $this->hospital->create($data);
        $result = $this->hospital->delete($id);
        
        $this->assertTrue($result);
    }
}
