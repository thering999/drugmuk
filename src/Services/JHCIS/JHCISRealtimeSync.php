<?php

namespace App\Services\JHCIS;

use App\Services\LoggerService;
use App\Services\JHCIS\JHCISBatchProcessor;

/**
 * JHCIS Real-time Sync Scheduler
 * 
 * Manages automatic synchronization at regular intervals
 */
class JHCISRealtimeSync
{
    private LoggerService $logger;
    private $db;
    
    public function __construct()
    {
        $this->logger = new LoggerService();
        $this->db = \App\Core\Database::getInstance()->getConnection();
    }
    
    /**
     * Enable real-time sync for hospital
     * 
     * @param int $hospitalId
     * @param int $intervalMinutes
     * @return void
     */
    public function enable(int $hospitalId, int $intervalMinutes = 15): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO jhcis_sync_schedules (hospital_id, interval_minutes, enabled, created_at)
             VALUES (?, ?, 1, NOW())
             ON DUPLICATE KEY UPDATE interval_minutes = ?, enabled = 1, updated_at = NOW()"
        );
        
        $stmt->execute([$hospitalId, $intervalMinutes, $intervalMinutes]);
        
        $this->logger->info("Real-time sync enabled", [
            'hospital_id' => $hospitalId,
            'interval_minutes' => $intervalMinutes
        ]);
    }
    
    /**
     * Disable real-time sync
     * 
     * @param int $hospitalId
     * @return void
     */
    public function disable(int $hospitalId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE jhcis_sync_schedules SET enabled = 0, updated_at = NOW() WHERE hospital_id = ?"
        );
        $stmt->execute([$hospitalId]);
        
        $this->logger->info("Real-time sync disabled", [
            'hospital_id' => $hospitalId
        ]);
    }
    
    /**
     * Process scheduled syncs (called by cron)
     * 
     * @return array
     */
    public function processScheduledSyncs(): array
    {
        $results = [];
        
        // Get hospitals due for sync
        $hospitals = $this->getHospitalsDueForSync();
        
        foreach ($hospitals as $hospital) {
            try {
                $result = $this->syncHospital($hospital['hospital_id']);
                $results[] = array_merge(['hospital_id' => $hospital['hospital_id']], $result);
                
                // Update last sync time
                $this->updateLastSyncTime($hospital['hospital_id']);
                
            } catch (\Exception $e) {
                $this->logger->error("Scheduled sync failed", [
                    'hospital_id' => $hospital['hospital_id'],
                    'error' => $e->getMessage()
                ]);
                
                $results[] = [
                    'hospital_id' => $hospital['hospital_id'],
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Get hospitals due for sync
     * 
     * @return array
     */
    private function getHospitalsDueForSync(): array
    {
        $stmt = $this->db->query(
            "SELECT hospital_id, interval_minutes
             FROM jhcis_sync_schedules
             WHERE enabled = 1
             AND (last_sync_at IS NULL OR 
                  last_sync_at <= DATE_SUB(NOW(), INTERVAL interval_minutes MINUTE))"
        );
        
        return $stmt->fetchAll();
    }
    
    /**
     * Sync hospital data
     * 
     * @param int $hospitalId
     * @return array
     */
    private function syncHospital(int $hospitalId): array
    {
        $processor = new JHCISBatchProcessor(500); // Smaller batches for real-time
        
        // Sync today's data only (incremental)
        $today = date('Y-m-d');
        
        $result = $processor->syncDispensingInBatches($today, $today, $hospitalId);
        
        return [
            'success' => true,
            'processed' => $result['processed'],
            'success_count' => $result['success'],
            'failed_count' => $result['failed']
        ];
    }
    
    /**
     * Update last sync time
     * 
     * @param int $hospitalId
     * @return void
     */
    private function updateLastSyncTime(int $hospitalId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE jhcis_sync_schedules SET last_sync_at = NOW() WHERE hospital_id = ?"
        );
        $stmt->execute([$hospitalId]);
    }
    
    /**
     * Get sync status
     * 
     * @param int $hospitalId
     * @return array|null
     */
    public function getStatus(int $hospitalId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM jhcis_sync_schedules WHERE hospital_id = ?"
        );
        $stmt->execute([$hospitalId]);
        
        return $stmt->fetch() ?: null;
    }
}
