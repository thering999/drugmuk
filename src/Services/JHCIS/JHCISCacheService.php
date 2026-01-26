<?php

namespace App\Services\JHCIS;

use App\Services\CacheService;

/**
 * JHCIS Cache Service
 * 
 * Caches frequently accessed JHCIS data
 */
class JHCISCacheService
{
    private CacheService $cache;
    private int $defaultTTL = 3600; // 1 hour
    
    public function __construct()
    {
        $this->cache = new CacheService();
    }
    
    /**
     * Get drug mapping (JHCIS code -> Drugmuk ID)
     * 
     * @param string $jhcisCode
     * @param int $hospitalId
     * @return int|null
     */
    public function getDrugMapping(string $jhcisCode, int $hospitalId): ?int
    {
        $key = $this->getDrugMappingKey($hospitalId, $jhcisCode);
        
        $cached = $this->cache->get($key);
        if ($cached !== null) {
            return (int) $cached;
        }
        
        // Query from database
        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "SELECT drugmuk_drug_id 
             FROM jhcis_drug_mapping 
             WHERE jhcis_drug_code = ? AND hospital_id = ?"
        );
        $stmt->execute([$jhcisCode, $hospitalId]);
        $result = $stmt->fetchColumn();
        
        if ($result) {
            $this->cache->set($key, $result, $this->defaultTTL);
            return (int) $result;
        }
        
        return null;
    }
    
    /**
     * Cache drug mapping
     * 
     * @param string $jhcisCode
     * @param int $hospitalId
     * @param int $drugmukId
     * @return void
     */
    public function cacheDrugMapping(string $jhcisCode, int $hospitalId, int $drugmukId): void
    {
        $key = $this->getDrugMappingKey($hospitalId, $jhcisCode);
        $this->cache->set($key, $drugmukId, $this->defaultTTL);
    }
    
    /**
     * Invalidate drug mapping cache
     * 
     * @param int $hospitalId
     * @param string|null $jhcisCode
     * @return void
     */
    public function invalidateDrugMapping(int $hospitalId, ?string $jhcisCode = null): void
    {
        if ($jhcisCode) {
            $key = $this->getDrugMappingKey($hospitalId, $jhcisCode);
            $this->cache->delete($key);
        } else {
            // Delete all mappings for hospital
            $pattern = "jhcis:drug_map:{$hospitalId}:*";
            $this->cache->deletePattern($pattern);
        }
    }
    
    /**
     * Cache dispensing data
     * 
     * @param int $hospitalId
     * @param string $date
     * @param array $data
     * @param int $ttl
     * @return void
     */
    public function cacheDispensingData(int $hospitalId, string $date, array $data, int $ttl = 1800): void
    {
        $key = "jhcis:dispensing:{$hospitalId}:{$date}";
        $this->cache->set($key, $data, $ttl);
    }
    
    /**
     * Get cached dispensing data
     * 
     * @param int $hospitalId
     * @param string $date
     * @return array|null
     */
    public function getCachedDispensingData(int $hospitalId, string $date): ?array
    {
        $key = "jhcis:dispensing:{$hospitalId}:{$date}";
        return $this->cache->get($key);
    }
    
    /**
     * Check if record already synced
     * 
     * @param string $recordId
     * @param int $hospitalId
     * @return bool
     */
    public function isRecordSynced(string $recordId, int $hospitalId): bool
    {
        $key = "jhcis:synced:{$hospitalId}:{$recordId}";
        return $this->cache->get($key) !== null;
    }
    
    /**
     * Mark record as synced
     * 
     * @param string $recordId
     * @param int $hospitalId
     * @param int $drugmukId
     * @return void
     */
    public function markRecordSynced(string $recordId, int $hospitalId, int $drugmukId): void
    {
        $key = "jhcis:synced:{$hospitalId}:{$recordId}";
        $this->cache->set($key, $drugmukId, 86400); // 24 hours
    }
    
    /**
     * Get sync statistics
     * 
     * @param int $hospitalId
     * @return array|null
     */
    public function getSyncStats(int $hospitalId): ?array
    {
        $key = "jhcis:stats:{$hospitalId}";
        return $this->cache->get($key);
    }
    
    /**
     * Update sync statistics
     * 
     * @param int $hospitalId
     * @param array $stats
     * @return void
     */
    public function updateSyncStats(int $hospitalId, array $stats): void
    {
        $key = "jhcis:stats:{$hospitalId}";
        $this->cache->set($key, $stats, 300); // 5 minutes
    }
    
    /**
     * Get drug mapping key
     * 
     * @param int $hospitalId
     * @param string $jhcisCode
     * @return string
     */
    private function getDrugMappingKey(int $hospitalId, string $jhcisCode): string
    {
        return "jhcis:drug_map:{$hospitalId}:{$jhcisCode}";
    }
    
    /**
     * Clear all JHCIS cache
     * 
     * @param int|null $hospitalId
     * @return void
     */
    public function clearAll(?int $hospitalId = null): void
    {
        if ($hospitalId) {
            $this->cache->deletePattern("jhcis:*:{$hospitalId}:*");
        } else {
            $this->cache->deletePattern("jhcis:*");
        }
    }
}
