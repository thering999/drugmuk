<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Hospital
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all hospitals
     */
    public function getAll($activeOnly = false)
    {
        $sql = "SELECT * FROM jhcis_hospitals";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY code ASC";
        
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get hospital by ID
     */
    public function findById($id)
    {
        $sql = "SELECT * FROM jhcis_hospitals WHERE id = ?";
        return $this->db->query($sql, [$id])->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get hospital by code
     */
    public function findByCode($code)
    {
        $sql = "SELECT * FROM jhcis_hospitals WHERE code = ?";
        return $this->db->query($sql, [$code])->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create new hospital
     */
    public function create($data)
    {
        // Encrypt password before storing
        if (isset($data['db_pass'])) {
            $data['db_pass'] = $this->encryptPassword($data['db_pass']);
        }
        
        // Ensure keys match table columns
        // Table columns: code, name, db_host, db_port, db_name, db_user, db_pass, pcucode, is_active
        return $this->db->insert('jhcis_hospitals', $data);
    }
    
    /**
     * Update hospital
     */
    public function update($id, $data)
    {
        // Encrypt password if provided
        if (isset($data['db_pass']) && !empty($data['db_pass'])) {
            $data['db_pass'] = $this->encryptPassword($data['db_pass']);
        } else {
            unset($data['db_pass']); // Don't update if empty or not provided
        }
        
        return $this->db->update('jhcis_hospitals', $data, ['id' => $id]);
    }
    
    /**
     * Delete hospital
     */
    public function delete($id)
    {
        return $this->db->delete('jhcis_hospitals', ['id' => $id]);
    }
    
    /**
     * Test database connection to hospital
     */
    public function testConnection($id)
    {
        $hospital = $this->findById($id);
        if (!$hospital) {
            return ['success' => false, 'message' => 'Hospital not found'];
        }
        
        $startTime = microtime(true);
        
        try {
            // Decrypt password
            $password = $this->decryptPassword($hospital['db_pass']);
            
            // Handle localhost/docker special case
            $host = $hospital['db_host'];
            if ($host === 'localhost' || $host === '127.0.0.1') {
                $host = 'host.docker.internal';
            }
            
            // Create PDO connection
            $dsn = "mysql:host={$host};port={$hospital['db_port']};dbname={$hospital['db_name']};charset=utf8mb4";
            $pdo = new PDO($dsn, $hospital['db_user'], $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5
            ]);
            
            // Test query
            $pdo->query("SELECT 1");
            
            $responseTime = round((microtime(true) - $startTime) * 1000);
            
            // Update hospital status (if column exists, otherwise skip)
            try {
                $this->db->update('jhcis_hospitals', [
                    'last_sync_at' => date('Y-m-d H:i:s') // Using last_sync_at as connection test timestamp
                ], ['id' => $id]);
            } catch (\Exception $e) {
                // Ignore if column doesn't exist
            }
            
            return [
                'success' => true,
                'message' => 'Connection successful',
                'response_time_ms' => $responseTime
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get database connection for specific hospital
     */
    public function getConnection($id)
    {
        $hospital = $this->findById($id);
        if (!$hospital) {
            throw new \Exception('Hospital not found');
        }
        
        if (!$hospital['is_active']) {
            throw new \Exception('Hospital is not active');
        }
        
        $password = $this->decryptPassword($hospital['db_pass']);
        
        // Handle localhost/docker special case
        $host = $hospital['db_host'];
        if ($host === 'localhost' || $host === '127.0.0.1') {
            $host = 'host.docker.internal';
        }
        
        $dsn = "mysql:host={$host};port={$hospital['db_port']};dbname={$hospital['db_name']};charset=utf8mb4";
        
        return new PDO($dsn, $hospital['db_user'], $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }
    
    /**
     * Encrypt password
     */
    private function encryptPassword($password)
    {
        $key = getenv('ENCRYPTION_KEY') ?: 'drugmuk_default_key_change_this';
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($password, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt password
     */
    public function decryptPassword($encrypted)
    {
        // Check if password looks encrypted (Starts with iv length base64?)
        // Or simpler: just try to decrypt. If fails, return original (migration path)
        
        if (empty($encrypted)) {
            return '';
        }
        
        $key = getenv('ENCRYPTION_KEY') ?: 'drugmuk_default_key_change_this';
        $data = base64_decode($encrypted);
        
        if ($data === false || strlen($data) < 17) {
            // Not a valid encrypted string, assume plain text (legacy support)
            return $encrypted;
        }
        
        $iv = substr($data, 0, 16);
        $encryptedStr = substr($data, 16);
        $decrypted = openssl_decrypt($encryptedStr, 'AES-256-CBC', $key, 0, $iv);
        
        if ($decrypted === false) {
             // Decryption failed, return original
             return $encrypted;
        }
        
        return $decrypted;
    }
}
