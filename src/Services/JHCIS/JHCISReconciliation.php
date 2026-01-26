<?php

namespace App\Services\JHCIS;

use App\Services\LoggerService;
use App\Services\JHCIS\JHCISConnectionPool;
use App\Services\JHCIS\JHCISCacheService;

/**
 * JHCIS Inventory Reconciliation Service
 * 
 * Compares inventory between JHCIS and Drugmuk, detects discrepancies
 */
class JHCISReconciliation
{
    private LoggerService $logger;
    private JHCISCacheService $cache;
    private $db;
    
    public function __construct()
    {
        $this->logger = new LoggerService();
        $this->cache = new JHCISCacheService();
        $this->db = \App\Core\Database::getInstance()->getConnection();
    }
    
    /**
     * Find inventory discrepancies
     * 
     * @param int $hospitalId
     * @param float $tolerancePercent Acceptable difference percentage
     * @return array
     */
    public function findDiscrepancies(int $hospitalId, float $tolerancePercent = 5.0): array
    {
        $this->logger->info("Starting inventory reconciliation", [
            'hospital_id' => $hospitalId,
            'tolerance' => $tolerancePercent
        ]);
        
        $discrepancies = [];
        
        // Get mapped drugs
        $mappings = $this->getDrugMappings($hospitalId);
        
        foreach ($mappings as $mapping) {
            $jhcisQty = $this->getJHCISStock($hospitalId, $mapping['jhcis_drug_code']);
            $drugmukQty = $this->getDrugmukStock($mapping['drugmuk_drug_id']);
            
            $difference = $jhcisQty - $drugmukQty;
            $percentDiff = $drugmukQty > 0 ? abs($difference / $drugmukQty * 100) : 100;
            
            if ($percentDiff > $tolerancePercent) {
                $discrepancies[] = [
                    'drug_id' => $mapping['drugmuk_drug_id'],
                    'drug_name' => $mapping['drug_name'],
                    'jhcis_code' => $mapping['jhcis_drug_code'],
                    'jhcis_qty' => $jhcisQty,
                    'drugmuk_qty' => $drugmukQty,
                    'difference' => $difference,
                    'percent_diff' => round($percentDiff, 2),
                    'severity' => $this->getSeverity($percentDiff)
                ];
            }
        }
        
        // Sort by severity and difference
        usort($discrepancies, function($a, $b) {
            $severityOrder = ['CRITICAL' => 3, 'HIGH' => 2, 'MEDIUM' => 1];
            $aSev = $severityOrder[$a['severity']] ?? 0;
            $bSev = $severityOrder[$b['severity']] ?? 0;
            
            if ($aSev !== $bSev) {
                return $bSev - $aSev;
            }
            
            return abs($b['difference']) - abs($a['difference']);
        });
        
        $this->logger->info("Reconciliation completed", [
            'hospital_id' => $hospitalId,
            'total_checked' => count($mappings),
            'discrepancies_found' => count($discrepancies)
        ]);
        
        return $discrepancies;
    }
    
    /**
     * Get drug mappings
     * 
     * @param int $hospitalId
     * @return array
     */
    private function getDrugMappings(int $hospitalId): array
    {
        $stmt = $this->db->prepare(
            "SELECT m.jhcis_drug_code, m.drugmuk_drug_id, d.name as drug_name
             FROM jhcis_drug_mapping m
             JOIN drugs d ON m.drugmuk_drug_id = d.id
             WHERE m.hospital_id = ?"
        );
        $stmt->execute([$hospitalId]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get JHCIS stock quantity
     * 
     * @param int $hospitalId
     * @param string $drugCode
     * @return int
     */
    private function getJHCISStock(int $hospitalId, string $drugCode): int
    {
        try {
            $pdo = JHCISConnectionPool::getConnection($hospitalId);
            
            // Try cdrugremain table first
            $stmt = $pdo->prepare(
                "SELECT COALESCE(SUM(remain), 0) as total
                 FROM cdrugremain
                 WHERE drugcode = ?"
            );
            $stmt->execute([$drugCode]);
            $result = $stmt->fetch();
            
            return (int) ($result['total'] ?? 0);
            
        } catch (\Exception $e) {
            $this->logger->warning("Failed to get JHCIS stock", [
                'hospital_id' => $hospitalId,
                'drug_code' => $drugCode,
                'error' => $e->getMessage()
            ]);
            
            return 0;
        }
    }
    
    /**
     * Get Drugmuk stock quantity
     * 
     * @param int $drugId
     * @return int
     */
    private function getDrugmukStock(int $drugId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(quantity), 0) as total
             FROM inventory
             WHERE drug_id = ? AND quantity > 0"
        );
        $stmt->execute([$drugId]);
        $result = $stmt->fetch();
        
        return (int) ($result['total'] ?? 0);
    }
    
    /**
     * Get severity level
     * 
     * @param float $percentDiff
     * @return string
     */
    private function getSeverity(float $percentDiff): string
    {
        if ($percentDiff >= 50) {
            return 'CRITICAL';
        } elseif ($percentDiff >= 20) {
            return 'HIGH';
        } else {
            return 'MEDIUM';
        }
    }
    
    /**
     * Generate adjustment suggestions
     * 
     * @param array $discrepancies
     * @return array
     */
    public function generateAdjustments(array $discrepancies): array
    {
        $adjustments = [];
        
        foreach ($discrepancies as $disc) {
            $adjustments[] = [
                'drug_id' => $disc['drug_id'],
                'drug_name' => $disc['drug_name'],
                'current_qty' => $disc['drugmuk_qty'],
                'target_qty' => $disc['jhcis_qty'],
                'adjustment' => $disc['difference'],
                'action' => $disc['difference'] > 0 ? 'INCREASE' : 'DECREASE',
                'reason' => 'JHCIS Reconciliation',
                'severity' => $disc['severity']
            ];
        }
        
        return $adjustments;
    }
    
    /**
     * Apply adjustments (with approval)
     * 
     * @param array $adjustments
     * @param int $userId
     * @param bool $requireApproval
     * @return array
     */
    public function applyAdjustments(array $adjustments, int $userId, bool $requireApproval = true): array
    {
        $applied = 0;
        $pending = 0;
        $failed = 0;
        
        $this->db->beginTransaction();
        
        try {
            foreach ($adjustments as $adjustment) {
                // Create adjustment record
                $stmt = $this->db->prepare(
                    "INSERT INTO stock_adjustments 
                     (drug_id, quantity_before, quantity_after, difference, reason, adjusted_by, status, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
                );
                
                $status = $requireApproval ? 'pending' : 'approved';
                
                $stmt->execute([
                    $adjustment['drug_id'],
                    $adjustment['current_qty'],
                    $adjustment['target_qty'],
                    $adjustment['adjustment'],
                    $adjustment['reason'],
                    $userId,
                    $status
                ]);
                
                $adjustmentId = $this->db->lastInsertId();
                
                if (!$requireApproval) {
                    // Apply immediately
                    $this->applyStockAdjustment($adjustmentId);
                    $applied++;
                } else {
                    $pending++;
                }
            }
            
            $this->db->commit();
            
            $this->logger->info("Adjustments processed", [
                'applied' => $applied,
                'pending' => $pending,
                'failed' => $failed
            ]);
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            
            $this->logger->error("Failed to apply adjustments", [
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
        
        return [
            'applied' => $applied,
            'pending' => $pending,
            'failed' => $failed
        ];
    }
    
    /**
     * Apply stock adjustment
     * 
     * @param int $adjustmentId
     * @return void
     */
    private function applyStockAdjustment(int $adjustmentId): void
    {
        // Get adjustment details
        $stmt = $this->db->prepare(
            "SELECT * FROM stock_adjustments WHERE id = ?"
        );
        $stmt->execute([$adjustmentId]);
        $adjustment = $stmt->fetch();
        
        if (!$adjustment) {
            throw new \Exception("Adjustment not found: {$adjustmentId}");
        }
        
        // Update inventory
        // This is simplified - in production, you'd need to handle lot numbers, expiry dates, etc.
        $stmt = $this->db->prepare(
            "UPDATE inventory 
             SET quantity = quantity + ?
             WHERE drug_id = ?
             LIMIT 1"
        );
        $stmt->execute([
            $adjustment['difference'],
            $adjustment['drug_id']
        ]);
        
        // Update adjustment status
        $stmt = $this->db->prepare(
            "UPDATE stock_adjustments 
             SET status = 'completed', completed_at = NOW()
             WHERE id = ?"
        );
        $stmt->execute([$adjustmentId]);
    }
    
    /**
     * Get reconciliation report
     * 
     * @param int $hospitalId
     * @param string $fromDate
     * @param string $toDate
     * @return array
     */
    public function getReconciliationReport(int $hospitalId, string $fromDate, string $toDate): array
    {
        $stmt = $this->db->prepare(
            "SELECT 
                DATE(created_at) as date,
                COUNT(*) as total_adjustments,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(ABS(difference)) as total_difference
             FROM stock_adjustments
             WHERE reason = 'JHCIS Reconciliation'
             AND created_at BETWEEN ? AND ?
             GROUP BY DATE(created_at)
             ORDER BY date DESC"
        );
        
        $stmt->execute([$fromDate, $toDate]);
        
        return $stmt->fetchAll();
    }
}
