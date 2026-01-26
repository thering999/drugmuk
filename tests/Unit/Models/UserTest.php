<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;

/**
 * User Model Test
 * 
 * ทดสอบการจัดการผู้ใช้ authentication และ authorization
 */
class UserTest extends TestCase
{
    private $userModel;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->userModel = new User();
        
        // Inject test database connection
        $reflection = new \ReflectionClass($this->userModel);
        $property = $reflection->getProperty('db');
        $property->setAccessible(true);
        $property->setValue($this->userModel, $this->db);
    }
    
    /**
     * Test: สร้างผู้ใช้ใหม่
     */
    public function testCreateUser()
    {
        $userId = $this->createTestUser([
            'username' => 'testuser001',
            'full_name' => 'Test User 001',
        ]);
        
        $this->assertGreaterThan(0, $userId);
    }
    
    /**
     * Test: ดึงข้อมูลผู้ใช้ทั้งหมด
     */
    public function testGetAllUsers()
    {
        $this->createTestUser(['username' => 'user1']);
        $this->createTestUser(['username' => 'user2']);
        
        $sql = "SELECT * FROM users";
        $users = $this->db->query($sql)->fetchAll();
        
        $this->assertIsArray($users);
        $this->assertGreaterThanOrEqual(2, count($users));
    }
    
    /**
     * Test: ดึงข้อมูลผู้ใช้ตาม ID
     */
    public function testGetUserById()
    {
        $userId = $this->createTestUser([
            'username' => 'testuser002',
            'full_name' => 'Test User 002',
        ]);
        
        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        $this->assertNotNull($user);
        $this->assertEquals('testuser002', $user['username']);
    }
    
    /**
     * Test: ตรวจสอบรหัสผ่าน
     */
    public function testPasswordVerification()
    {
        $password = 'SecurePassword123!';
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        $userId = $this->createTestUser([
            'username' => 'testuser003',
            'password' => $hashedPassword,
        ]);
        
        $sql = "SELECT password FROM users WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        // ตรวจสอบว่ารหัสผ่านถูกต้อง
        $this->assertTrue(password_verify($password, $user['password']));
        
        // ตรวจสอบว่ารหัสผ่านผิด
        $this->assertFalse(password_verify('WrongPassword', $user['password']));
    }
    
    /**
     * Test: ตรวจสอบ role ของผู้ใช้
     */
    public function testUserRoles()
    {
        $adminId = $this->createTestUser(['role' => 'admin']);
        $staffId = $this->createTestUser(['role' => 'staff']);
        
        $sql = "SELECT role FROM users WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        $stmt->execute([$adminId]);
        $admin = $stmt->fetch();
        $this->assertEquals('admin', $admin['role']);
        
        $stmt->execute([$staffId]);
        $staff = $stmt->fetch();
        $this->assertEquals('staff', $staff['role']);
    }
    
    /**
     * Test: ผู้ใช้ที่ active
     */
    public function testActiveUsers()
    {
        $this->createTestUser(['username' => 'active_user', 'is_active' => 1]);
        $this->createTestUser(['username' => 'inactive_user', 'is_active' => 0]);
        
        $sql = "SELECT * FROM users WHERE is_active = 1";
        $activeUsers = $this->db->query($sql)->fetchAll();
        
        $this->assertIsArray($activeUsers);
        
        foreach ($activeUsers as $user) {
            $this->assertEquals(1, $user['is_active']);
        }
    }
    
    /**
     * Test: อัพเดทข้อมูลผู้ใช้
     */
    public function testUpdateUser()
    {
        $userId = $this->createTestUser([
            'username' => 'testuser004',
            'full_name' => 'Original Name',
        ]);
        
        // อัพเดท
        $sql = "UPDATE users SET full_name = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute(['Updated Name', $userId]);
        
        $this->assertTrue($result);
        
        // ตรวจสอบ
        $sql = "SELECT full_name FROM users WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        $this->assertEquals('Updated Name', $user['full_name']);
    }
    
    /**
     * Test: ลบผู้ใช้ (soft delete)
     */
    public function testSoftDeleteUser()
    {
        $userId = $this->createTestUser(['username' => 'user_to_delete']);
        
        // Soft delete
        $sql = "UPDATE users SET is_active = 0 WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([$userId]);
        
        $this->assertTrue($result);
        
        // ตรวจสอบ
        $sql = "SELECT is_active FROM users WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        $this->assertEquals(0, $user['is_active']);
    }
    
    /**
     * Test: username ต้องไม่ซ้ำ
     */
    public function testUniqueUsername()
    {
        $username = 'unique_user_' . rand(1000, 9999);
        
        $this->createTestUser(['username' => $username]);
        
        // พยายามสร้างผู้ใช้ชื่อซ้ำ
        try {
            $this->createTestUser(['username' => $username]);
            $this->fail('Should throw exception for duplicate username');
        } catch (\PDOException $e) {
            // Expected exception
            $this->assertStringContainsString('Duplicate', $e->getMessage());
        }
    }
    
    /**
     * Test: ตรวจสอบโครงสร้างข้อมูลผู้ใช้
     */
    public function testUserStructure()
    {
        $userId = $this->createTestUser();
        
        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        $requiredFields = [
            'id', 'username', 'password', 'full_name', 
            'role', 'is_active', 'created_at'
        ];
        
        $this->assertArrayHasKeys($requiredFields, $user);
    }
}
