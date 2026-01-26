<?php

namespace App\Services\JHCIS;

use App\Services\LoggerService;
use App\Services\JHCIS\JHCISConnectionPool;
use App\Services\JHCIS\JHCISCacheService;

/**
 * JHCIS Batch Processor
 * 
 * Processes large JHCIS datasets in batches
 */
class JHCISBatchProcessor
{
    private int $batchSize = 1000;
    private LoggerService $logger;
    private JHCISCacheService $cache;
    private $progressCallback;
    
    public function __construct(int $batchSize = 1000)
    {
        $this->batchSize = $batchSize;
        $this->logger = new LoggerService();
        $this->cache = new JHCISCacheService();
    }
    
    /**
     * Set progress callback
     * 
     * @param callable $callback
     * @return void
     */
    public function setProgressCallback(callable $callback): void
    {
        $this->progressCallback = $callback;
    }
    
    /**
     * Sync dispensing data in batches
     * 
     * @param string $fromDate
     * @param string $toDate
     * @param int $hospitalId
     * @return array
     */
    public function syncDispensingInBatches(string $fromDate, string $toDate, int $hospitalId): array
    {
        $this->logger->info("Starting batch sync", [
            'hospital_id' => $hospitalId,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'batch_size' => $this->batchSize
        ]);
        
        $offset = 0;
        $totalProcessed = 0;
        $totalSuccess = 0;
        $totalFailed = 0;
        $totalSkipped = 0;
        
        $startTime = microtime(true);
        
        do {
            // Fetch batch
            $batch = $this->fetchDispensingBatch($fromDate, $toDate, $hospitalId, $offset, $this->batchSize);
            
            if (empty($batch)) {
                break;
            }
            
            $batchCount = count($batch);
            
            // Process batch
            foreach ($batch as $record) {
                try {
                    // Check if already synced (idempotent)
                    if ($this->cache->isRecordSynced($record['id'], $hospitalId)) {
                        $totalSkipped++;
                        continue;
                    }
                    
                    // Process record
                    $result = $this->processDispensingRecord($record, $hospitalId);
                    
                    if ($result) {
                        $totalSuccess++;
                        
                        // Mark as synced
                        $this->cache->markRecordSynced($record['id'], $hospitalId, $result);
                    } else {
                        $totalFailed++;
                    }
                    
                } catch (\Exception $e) {
                    $totalFailed++;
                    
                    $this->logger->error("Record processing failed", [
                        'hospital_id' => $hospitalId,
                        'record_id' => $record['id'],
                        'error' => $e->getMessage()
                    ]);
                }
                
                $totalProcessed++;
            }
            
            $offset += $this->batchSize;
            
            // Report progress
            $this->reportProgress([
                'total_processed' => $totalProcessed,
                'success' => $totalSuccess,
                'failed' => $totalFailed,
                'skipped' => $totalSkipped,
                'batch_number' => ceil($offset / $this->batchSize)
            ]);
            
            // Prevent memory leak
            gc_collect_cycles();
            
        } while ($batchCount === $this->batchSize);
        
        $duration = microtime(true) - $startTime;
        
        $result = [
            'processed' => $totalProcessed,
            'success' => $totalSuccess,
            'failed' => $totalFailed,
            'skipped' => $totalSkipped,
            'duration_seconds' => round($duration, 2),
            'records_per_second' => $totalProcessed > 0 ? round($totalProcessed / $duration, 2) : 0
        ];
        
        $this->logger->info("Batch sync completed", array_merge($result, [
            'hospital_id' => $hospitalId
        ]));
        
        return $result;
    }
    
    /**
     * Fetch dispensing batch from JHCIS
     * 
     * @param string $fromDate
     * @param string $toDate
     * @param int $hospitalId
     * @param int $offset
     * @param int $limit
     * @return array
     */
    private function fetchDispensingBatch(string $fromDate, string $toDate, int $hospitalId, int $offset, int $limit): array
    {
        $pdo = JHCISConnectionPool::getConnection($hospitalId);
        
        $sql = "SELECT 
                    CONCAT(visitno, '-', drugcode, '-', datestart) as id,
                    visitno,
                    drugcode,
                    datestart as dispense_date,
                    qty as quantity,
                    unit,
                    hn as patient_hn,
                    ptname as patient_name
                FROM visitdrug
                WHERE datestart BETWEEN :from_date AND :to_date
                ORDER BY datestart, visitno
                LIMIT :limit OFFSET :offset";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':from_date', $fromDate);
        $stmt->bindValue(':to_date', $toDate);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Process single dispensing record
     * 
     * @param array $record
     * @param int $hospitalId
     * @return int|null Dispensing ID
     */
    private function processDispensingRecord(array $record, int $hospitalId): ?int
    {
        // Get drug mapping
        $drugId = $this->cache->getDrugMapping($record['drugcode'], $hospitalId);
        
        if (!$drugId) {
            throw new \Exception("Drug mapping not found: {$record['drugcode']}");
        }
        
        // Save to Drugmuk
        $db = \App\Core\Database::getInstance()->getConnection();
        
        $db->beginTransaction();
        
        try {
            // Insert dispensing
            $stmt = $db->prepare(
                "INSERT INTO dispensing (patient_hn, patient_name, dispense_date, hospital_id, jhcis_record_id, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                $record['patient_hn'],
                $record['patient_name'] ?? '',
                $record['dispense_date'],
                $hospitalId,
                $record['id']
            ]);
            
            $dispensingId = $db->lastInsertId();
            
            // Insert dispensing item
            $stmt = $db->prepare(
                "INSERT INTO dispensing_items (dispensing_id, drug_id, quantity, unit, created_at)
                 VALUES (?, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                $dispensingId,
                $drugId,
                $record['quantity'],
                $record['unit']
            ]);
            
            $db->commit();
            
            return (int) $dispensingId;
            
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Report progress
     * 
     * @param array $progress
     * @return void
     */
    private function reportProgress(array $progress): void
    {
        if ($this->progressCallback) {
            call_user_func($this->progressCallback, $progress);
        }
    }
}
