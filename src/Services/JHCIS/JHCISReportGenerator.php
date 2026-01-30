<?php

namespace App\Services\JHCIS;

use App\Services\LoggerService;

/**
 * JHCIS Report Generator
 * 
 * Generate advanced reports for JHCIS integration
 */
class JHCISReportGenerator
{
    private LoggerService $logger;
    private $db;
    
    public function __construct()
    {
        $this->logger = new LoggerService();
        $this->db = \App\Core\Database::getInstance()->getConnection();
    }
    
    /**
     * Generate sync performance report
     * 
     * @param int $hospitalId
     * @param string $fromDate
     * @param string $toDate
     * @return array
     */
    public function generateSyncPerformanceReport(int $hospitalId, string $fromDate, string $toDate): array
    {
        // Overall stats
        $stmt = $this->db->prepare(
            "SELECT 
                COUNT(*) as total_syncs,
                SUM(records_processed) as total_records,
                SUM(records_success) as total_success,
                SUM(records_failed) as total_failed,
                AVG(TIMESTAMPDIFF(SECOND, started_at, completed_at)) as avg_duration,
                SUM(CASE WHEN sync_status = 'completed' THEN 1 ELSE 0 END) as successful_syncs,
                SUM(CASE WHEN sync_status = 'failed' THEN 1 ELSE 0 END) as failed_syncs
             FROM jhcis_sync_log
             WHERE hospital_id = ?
             AND started_at BETWEEN ? AND ?"
        );
        $stmt->execute([$hospitalId, $fromDate, $toDate]);
        $overall = $stmt->fetch();
        
        // Daily breakdown
        $stmt = $this->db->prepare(
            "SELECT 
                DATE(started_at) as date,
                COUNT(*) as syncs,
                SUM(records_processed) as records,
                SUM(records_success) as success,
                SUM(records_failed) as failed,
                AVG(TIMESTAMPDIFF(SECOND, started_at, completed_at)) as avg_duration
             FROM jhcis_sync_log
             WHERE hospital_id = ?
             AND started_at BETWEEN ? AND ?
             GROUP BY DATE(started_at)
             ORDER BY date DESC"
        );
        $stmt->execute([$hospitalId, $fromDate, $toDate]);
        $daily = $stmt->fetchAll();
        
        // Success rate
        $successRate = $overall['total_syncs'] > 0 
            ? ($overall['successful_syncs'] / $overall['total_syncs'] * 100) 
            : 0;
        
        return [
            'overall' => array_merge($overall, [
                'success_rate' => round($successRate, 2),
                'avg_records_per_sync' => $overall['total_syncs'] > 0 
                    ? round($overall['total_records'] / $overall['total_syncs'], 0) 
                    : 0
            ]),
            'daily' => $daily,
            'period' => [
                'from' => $fromDate,
                'to' => $toDate
            ]
        ];
    }
    
    /**
     * Generate data quality report
     * 
     * @param int $hospitalId
     * @return array
     */
    public function generateDataQualityReport(int $hospitalId): array
    {
        // Mapping coverage
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as mapped_drugs FROM jhcis_drug_mapping WHERE hospital_id = ?"
        );
        $stmt->execute([$hospitalId]);
        $mappedDrugs = $stmt->fetchColumn();
        
        // Mapping by confidence
        $stmt = $this->db->prepare(
            "SELECT 
                CASE 
                    WHEN confidence_score >= 0.9 THEN 'High (90%+)'
                    WHEN confidence_score >= 0.7 THEN 'Medium (70-89%)'
                    ELSE 'Low (<70%)'
                END as confidence_level,
                COUNT(*) as count
             FROM jhcis_drug_mapping
             WHERE hospital_id = ?
             GROUP BY confidence_level"
        );
        $stmt->execute([$hospitalId]);
        $confidenceLevels = $stmt->fetchAll();
        
        // Recent errors
        $stmt = $this->db->prepare(
            "SELECT 
                error_type,
                COUNT(*) as count
             FROM jhcis_sync_errors
             WHERE sync_log_id IN (
                 SELECT id FROM jhcis_sync_log 
                 WHERE hospital_id = ? 
                 AND started_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             )
             GROUP BY error_type
             ORDER BY count DESC
             LIMIT 10"
        );
        $stmt->execute([$hospitalId]);
        $errorTypes = $stmt->fetchAll();
        
        return [
            'mapping_coverage' => [
                'total_mapped' => $mappedDrugs,
                'by_confidence' => $confidenceLevels
            ],
            'error_analysis' => $errorTypes,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Generate usage comparison report
     * 
     * @param int $hospitalId
     * @param string $fromDate
     * @param string $toDate
     * @return array
     */
    public function generateUsageComparisonReport(int $hospitalId, string $fromDate, string $toDate): array
    {
        // Top drugs from JHCIS
        $jhcisTop = $this->getTopDrugsJHCIS($hospitalId, $fromDate, $toDate, 20);
        
        // Top drugs from Drugmuk
        $drugmukTop = $this->getTopDrugsDrugmuk($fromDate, $toDate, 20);
        
        // Compare
        $comparison = $this->compareDrugUsage($jhcisTop, $drugmukTop);
        
        return [
            'jhcis_top_20' => $jhcisTop,
            'drugmuk_top_20' => $drugmukTop,
            'comparison' => $comparison,
            'period' => [
                'from' => $fromDate,
                'to' => $toDate
            ]
        ];
    }
    
    /**
     * Get top drugs from JHCIS
     * 
     * @param int $hospitalId
     * @param string $fromDate
     * @param string $toDate
     * @param int $limit
     * @return array
     */
    private function getTopDrugsJHCIS(int $hospitalId, string $fromDate, string $toDate, int $limit): array
    {
        try {
            $pdo = \App\Services\JHCIS\JHCISConnectionPool::getConnection($hospitalId);
            
            $stmt = $pdo->prepare(
                "SELECT 
                    drugcode,
                    drugname,
                    SUM(qty) as total_qty,
                    COUNT(*) as transaction_count
                 FROM visitdrug
                 WHERE datestart BETWEEN ? AND ?
                 GROUP BY drugcode, drugname
                 ORDER BY total_qty DESC
                 LIMIT ?"
            );
            $stmt->execute([$fromDate, $toDate, $limit]);
            
            return $stmt->fetchAll();
            
        } catch (\Exception $e) {
            $this->logger->error("Failed to get JHCIS top drugs", [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Get top drugs from Drugmuk
     * 
     * @param string $fromDate
     * @param string $toDate
     * @param int $limit
     * @return array
     */
    private function getTopDrugsDrugmuk(string $fromDate, string $toDate, int $limit): array
    {
        $stmt = $this->db->prepare(
            "SELECT 
                d.code,
                d.name,
                SUM(di.quantity) as total_qty,
                COUNT(DISTINCT disp.id) as transaction_count
             FROM dispensing disp
             JOIN dispensing_items di ON disp.id = di.dispensing_id
             JOIN drugs d ON di.drug_id = d.id
             WHERE disp.dispense_date BETWEEN ? AND ?
             GROUP BY d.id, d.code, d.name
             ORDER BY total_qty DESC
             LIMIT ?"
        );
        $stmt->execute([$fromDate, $toDate, $limit]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Compare drug usage
     * 
     * @param array $jhcisTop
     * @param array $drugmukTop
     * @return array
     */
    private function compareDrugUsage(array $jhcisTop, array $drugmukTop): array
    {
        $jhcisCodes = array_column($jhcisTop, 'drugcode');
        $drugmukCodes = array_column($drugmukTop, 'code');
        
        $inBoth = array_intersect($jhcisCodes, $drugmukCodes);
        $onlyJHCIS = array_diff($jhcisCodes, $drugmukCodes);
        $onlyDrugmuk = array_diff($drugmukCodes, $jhcisCodes);
        
        return [
            'in_both' => count($inBoth),
            'only_jhcis' => count($onlyJHCIS),
            'only_drugmuk' => count($onlyDrugmuk),
            'match_rate' => count($jhcisCodes) > 0 
                ? round(count($inBoth) / count($jhcisCodes) * 100, 2) 
                : 0
        ];
    }
    
    
    /**
     * Generate multi-hospital comparison report
     * 
     * @return array
     */
    public function generateMultiHospitalComparisonReport(): array
    {
        $stmt = $this->db->query(
            "SELECT 
                h.id, h.name, h.code, h.pcucode,
                (SELECT COUNT(*) FROM jhcis_drug_mapping WHERE hospital_id = h.id) as mapped_count,
                (SELECT MAX(completed_at) FROM jhcis_sync_log WHERE hospital_id = h.id AND sync_status = 'completed') as last_success_sync,
                (SELECT SUM(records_success) FROM jhcis_sync_log WHERE hospital_id = h.id AND started_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as records_30d,
                (SELECT COUNT(*) FROM jhcis_sync_log WHERE hospital_id = h.id AND sync_status = 'failed' AND started_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as failures_7d
             FROM jhcis_hospitals h
             WHERE h.is_active = 1
             ORDER BY h.name ASC"
        );
        $hospitals = $stmt->fetchAll();
        
        $totalMapped = 0;
        foreach ($hospitals as $h) {
            $totalMapped += $h['mapped_count'];
        }
        
        return [
            'hospitals' => $hospitals,
            'summary' => [
                'total_hospitals' => count($hospitals),
                'total_mappings' => $totalMapped,
                'avg_mappings' => count($hospitals) > 0 ? round($totalMapped / count($hospitals), 1) : 0
            ],
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Generate consolidated consumption report across all hospitals
     */
    public function generateConsolidatedConsumptionReport(string $fromDate, string $toDate): array
    {
        $stmt = $this->db->query("SELECT code, name FROM jhcis_hospitals WHERE is_active = 1");
        $hospitals = $stmt->fetchAll();
        
        if (empty($hospitals)) return ['error' => 'No active hospitals found'];
        
        $sql = "
            SELECT 
                d.name as drug_name,
                d.code as drug_code,
                disp.hospital_code,
                SUM(di.quantity) as total_qty
            FROM dispensing disp
            JOIN dispensing_items di ON disp.id = di.dispense_id
            JOIN drugs d ON di.drug_id = d.id
            WHERE disp.dispense_date BETWEEN ? AND ?
            GROUP BY d.id, disp.hospital_code
            ORDER BY drug_name ASC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
        $rawData = $stmt->fetchAll();
        
        $matrix = [];
        $drugTotals = [];
        
        foreach ($rawData as $row) {
            $name = $row['drug_name'];
            $hCode = $row['hospital_code'];
            $matrix[$name][$hCode] = (float)$row['total_qty'];
            $drugTotals[$name] = ($drugTotals[$name] ?? 0) + (float)$row['total_qty'];
        }
        
        arsort($drugTotals);
        
        $finalData = [];
        foreach ($drugTotals as $name => $total) {
            $finalData[] = [
                'name' => $name,
                'total' => $total,
                'breakdown' => $matrix[$name]
            ];
        }
        
        return [
            'hospitals' => $hospitals,
            'data' => array_slice($finalData, 0, 50),
            'period' => ['from' => $fromDate, 'to' => $toDate]
        ];
    }
    
    /**
     * Generate executive summary report
     * 
     * @param int $hospitalId
     * @return array
     */
    public function generateExecutiveSummary(int $hospitalId): array
    {
        try {
            // Get hospital info
            $stmt = $this->db->prepare("SELECT code, name FROM jhcis_hospitals WHERE id = ?");
            $stmt->execute([$hospitalId]);
            $hospital = $stmt->fetch();
            
            if (!$hospital) {
                return ['error' => 'Hospital not found'];
            }
            
            // Sync statistics (last 30 days)
            $stmt = $this->db->prepare(
                "SELECT 
                    COUNT(*) as total_syncs,
                    SUM(records_processed) as total_records,
                    SUM(CASE WHEN sync_status = 'completed' THEN 1 ELSE 0 END) as successful_syncs,
                    MAX(completed_at) as last_sync
                 FROM jhcis_sync_log
                 WHERE hospital_id = ?
                 AND started_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
            );
            $stmt->execute([$hospitalId]);
            $syncStats = $stmt->fetch();
            
            // Data quality metrics
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) as mapped_drugs 
                 FROM jhcis_drug_mapping 
                 WHERE hospital_id = ?"
            );
            $stmt->execute([$hospitalId]);
            $dataQuality = $stmt->fetch();
            
            // Recent alerts
            $stmt = $this->db->prepare(
                "SELECT 
                    alert_type as type,
                    message,
                    created_at
                 FROM jhcis_alerts
                 WHERE hospital_id = ?
                 AND status = 'active'
                 ORDER BY created_at DESC
                 LIMIT 5"
            );
            $stmt->execute([$hospitalId]);
            $alerts = $stmt->fetchAll();
            
            // Active alerts count
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) as count 
                 FROM jhcis_alerts 
                 WHERE hospital_id = ? AND status = 'active'"
            );
            $stmt->execute([$hospitalId]);
            $activeAlertsCount = $stmt->fetchColumn();
            
            $totalSyncs = (int)($syncStats['total_syncs'] ?? 0);
            $successSyncs = (int)($syncStats['successful_syncs'] ?? 0);
            $successRate = $totalSyncs > 0 ? round(($successSyncs / $totalSyncs) * 100, 1) : 0;

            return [
                'hospital' => $hospital,
                'sync_performance' => [
                    'total_syncs' => $totalSyncs,
                    'total_records' => (int)($syncStats['total_records'] ?? 0),
                    'successful_syncs' => $successSyncs,
                    'success_rate' => $successRate,
                    'last_sync' => $syncStats['last_sync'] ?? null
                ],
                'data_quality' => [
                    'mapped_drugs' => (int)($dataQuality['mapped_drugs'] ?? 0)
                ],
                'alerts' => [
                    'active_count' => (int)$activeAlertsCount,
                    'recent' => $alerts
                ],
                'generated_at' => date('Y-m-d H:i:s')
            ];
            
        } catch (\Exception $e) {
            $this->logger->error("Failed to generate executive summary", [
                'hospital_id' => $hospitalId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'error' => 'Failed to generate report: ' . $e->getMessage(),
                'hospital' => ['code' => 'N/A', 'name' => 'Unknown'],
                'sync_statistics' => [
                    'total_syncs' => 0,
                    'total_records' => 0,
                    'successful_syncs' => 0,
                    'last_sync' => null
                ],
                'data_quality' => ['mapped_drugs' => 0],
                'alerts' => ['active_count' => 0, 'recent' => []],
                'generated_at' => date('Y-m-d H:i:s')
            ];
        }
    }
}
