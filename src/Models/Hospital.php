<?php

namespace Drugmuk\Models;

use Drugmuk\Core\Database;
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
        $sql = "SELECT * FROM hospitals";
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
        $sql = "SELECT * FROM hospitals WHERE id = ?";
        return $this->db->query($sql, [$id])->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get hospital by code
     */
    public function findByCode($code)
    {
        $sql = "SELECT * FROM hospitals WHERE code = ?";
        return $this->db->query($sql, [$code])->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create new hospital
     */
    public function create($data)
    {
        // Encrypt password before storing
        if (isset($data['jhcis_password'])) {
            $data['jhcis_password'] = $this->encryptPassword($data['jhcis_password']);
        }
        
        return $this->db->insert('hospitals', $data);
    }
    
    /**
     * Update hospital
     */
    public function update($id, $data)
    {
        // Encrypt password if provided
        if (isset($data['jhcis_password']) && !empty($data['jhcis_password'])) {
            $data['jhcis_password'] = $this->encryptPassword($data['jhcis_password']);
        } else {
            unset($data['jhcis_password']); // Don't update if empty
        }
        
        return $this->db->update('hospitals', $data, ['id' => $id]);
    }
    
    /**
     * Delete hospital
     */
    public function delete($id)
    {
        return $this->db->delete('hospitals', ['id' => $id]);
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
            $password = $this->decryptPassword($hospital['jhcis_password']);
            
            // Create PDO connection
            $dsn = "mysql:host={$hospital['jhcis_host']};port={$hospital['jhcis_port']};dbname={$hospital['jhcis_database']};charset=utf8mb4";
            $pdo = new PDO($dsn, $hospital['jhcis_username'], $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5
            ]);
            
            // Test query
            $pdo->query("SELECT 1");
            
            $responseTime = round((microtime(true) - $startTime) * 1000);
            
            // Update hospital status
            $this->db->update('hospitals', [
                'is_connected' => 1,
                'last_connection_test' => date('Y-m-d H:i:s'),
                'last_connection_status' => 'success'
            ], ['id' => $id]);
            
            // Log connection test
            $this->db->insert('hospital_connection_tests', [
                'hospital_id' => $id,
                'test_type' => 'manual',
                'status' => 'success',
                'response_time_ms' => $responseTime
            ]);
            
            return [
                'success' => true,
                'message' => 'Connection successful',
                'response_time_ms' => $responseTime
            ];
            
        } catch (\Exception $e) {
            // Update hospital status
            $this->db->update('hospitals', [
                'is_connected' => 0,
                'last_connection_test' => date('Y-m-d H:i:s'),
                'last_connection_status' => 'failed'
            ], ['id' => $id]);
            
            // Log connection test
            $this->db->insert('hospital_connection_tests', [
                'hospital_id' => $id,
                'test_type' => 'manual',
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
            
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
        
        $password = $this->decryptPassword($hospital['jhcis_password']);
        
        $dsn = "mysql:host={$hospital['jhcis_host']};port={$hospital['jhcis_port']};dbname={$hospital['jhcis_database']};charset=utf8mb4";
        
        return new PDO($dsn, $hospital['jhcis_username'], $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }
    
    /**
     * Get sync summary for all hospitals
     */
    public function getSyncSummary()
    {
        $sql = "SELECT * FROM v_hospital_sync_summary ORDER BY code ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get sync logs for hospital
     */
    public function getSyncLogs($hospitalId, $limit = 50)
    {
        $sql = "SELECT * FROM hospital_sync_logs 
                WHERE hospital_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?";
        return $this->db->query($sql, [$hospitalId, $limit])->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create sync log
     */
    public function createSyncLog($data)
    {
        return $this->db->insert('hospital_sync_logs', $data);
    }
    
    /**
     * Update sync log
     */
    public function updateSyncLog($id, $data)
    {
        return $this->db->update('hospital_sync_logs', $data, ['id' => $id]);
    }
    
    /**
     * Get active hospitals for auto-sync
     */
    public function getActiveForSync()
    {
        $sql = "SELECT * FROM hospitals 
                WHERE is_active = 1 
                AND auto_sync_enabled = 1 
                AND is_connected = 1
                ORDER BY code ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
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
    private function decryptPassword($encrypted)
    {
        if (empty($encrypted)) {
            return '';
        }
        
        $key = getenv('ENCRYPTION_KEY') ?: 'drugmuk_default_key_change_this';
        $data = base64_decode($encrypted);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
    
    /**
     * Get statistics
     */
    public function getStatistics()
    {
        return [
            'total_hospitals' => $this->db->query("SELECT COUNT(*) as count FROM hospitals")->fetch()['count'],
            'active_hospitals' => $this->db->query("SELECT COUNT(*) as count FROM hospitals WHERE is_active = 1")->fetch()['count'],
            'connected_hospitals' => $this->db->query("SELECT COUNT(*) as count FROM hospitals WHERE is_connected = 1")->fetch()['count'],
            'auto_sync_enabled' => $this->db->query("SELECT COUNT(*) as count FROM hospitals WHERE auto_sync_enabled = 1")->fetch()['count'],
            'total_syncs_today' => $this->db->query("SELECT COUNT(*) as count FROM hospital_sync_logs WHERE DATE(created_at) = CURDATE()")->fetch()['count'],
            'successful_syncs_today' => $this->db->query("SELECT COUNT(*) as count FROM hospital_sync_logs WHERE DATE(created_at) = CURDATE() AND status = 'success'")->fetch()['count']
        ];
    }
}
