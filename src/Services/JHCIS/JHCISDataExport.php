<?php

namespace App\Services\JHCIS;

use App\Services\LoggerService;

/**
 * JHCIS Data Export Service
 * 
 * Export JHCIS data to Excel/CSV formats
 */
class JHCISDataExport
{
    private LoggerService $logger;
    private $db;
    
    public function __construct()
    {
        $this->logger = new LoggerService();
        $this->db = \App\Core\Database::getInstance()->getConnection();
    }
    
    /**
     * Export drug mappings to Excel
     * 
     * @param int $hospitalId
     * @return string File path
     */
    public function exportDrugMappings(int $hospitalId): string
    {
        $stmt = $this->db->prepare(
            "SELECT 
                m.jhcis_drug_code,
                m.jhcis_drug_name,
                d.code as drugmuk_code,
                d.name as drugmuk_name,
                d.generic_name,
                m.confidence_score,
                m.mapping_method as match_type,
                m.created_at
             FROM jhcis_drug_mapping m
             JOIN drugs d ON m.drugmuk_drug_id = d.id
             WHERE m.hospital_id = ?
             ORDER BY m.jhcis_drug_code"
        );
        $stmt->execute([$hospitalId]);
        $data = $stmt->fetchAll();
        
        return $this->generateExcel($data, 'drug_mappings', [
            'JHCIS Code',
            'JHCIS Name',
            'Drugmuk Code',
            'Drugmuk Name',
            'Generic Name',
            'Confidence',
            'Match Type',
            'Created At'
        ]);
    }
    
    /**
     * Export discrepancies to Excel
     * 
     * @param array $discrepancies
     * @return string File path
     */
    public function exportDiscrepancies(array $discrepancies): string
    {
        $data = array_map(function($disc) {
            return [
                $disc['drug_name'],
                $disc['jhcis_code'],
                $disc['jhcis_qty'],
                $disc['drugmuk_qty'],
                $disc['difference'],
                $disc['percent_diff'] . '%',
                $disc['severity']
            ];
        }, $discrepancies);
        
        return $this->generateExcel($data, 'discrepancies', [
            'Drug Name',
            'JHCIS Code',
            'JHCIS Qty',
            'Drugmuk Qty',
            'Difference',
            'Percent Diff',
            'Severity'
        ]);
    }
    
    /**
     * Export sync logs to Excel
     * 
     * @param int $hospitalId
     * @param string $fromDate
     * @param string $toDate
     * @return string File path
     */
    public function exportSyncLogs(int $hospitalId, string $fromDate, string $toDate): string
    {
        $stmt = $this->db->prepare(
            "SELECT 
                sync_type,
                sync_status,
                records_processed,
                records_success,
                records_failed,
                started_at,
                completed_at,
                TIMESTAMPDIFF(SECOND, started_at, completed_at) as duration_seconds
             FROM jhcis_sync_log
             WHERE hospital_id = ?
             AND started_at BETWEEN ? AND ?
             ORDER BY started_at DESC"
        );
        $stmt->execute([$hospitalId, $fromDate, $toDate]);
        $data = $stmt->fetchAll(\PDO::FETCH_NUM);
        
        return $this->generateExcel($data, 'sync_logs', [
            'Type',
            'Status',
            'Processed',
            'Success',
            'Failed',
            'Started At',
            'Completed At',
            'Duration (sec)'
        ]);
    }
    
    /**
     * Generate Excel file
     * 
     * @param array $data
     * @param string $filename
     * @param array $headers
     * @return string File path
     */
    private function generateExcel(array $data, string $filename, array $headers): string
    {
        // Create CSV (simple implementation)
        $filepath = sys_get_temp_dir() . '/' . $filename . '_' . date('YmdHis') . '.csv';
        $fp = fopen($filepath, 'w');
        
        // Add BOM for UTF-8
        fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write headers
        fputcsv($fp, $headers);
        
        // Write data
        foreach ($data as $row) {
            fputcsv($fp, is_array($row) ? $row : (array)$row);
        }
        
        fclose($fp);
        
        $this->logger->info("Excel file generated", [
            'filename' => $filename,
            'rows' => count($data),
            'path' => $filepath
        ]);
        
        return $filepath;
    }
    
    /**
     * Import drug mappings from Excel
     * 
     * @param string $filepath
     * @param int $hospitalId
     * @return array
     */
    public function importDrugMappings(string $filepath, int $hospitalId): array
    {
        if (!file_exists($filepath)) {
            throw new \Exception("File not found: {$filepath}");
        }
        
        $fp = fopen($filepath, 'r');
        
        // Skip header
        fgetcsv($fp);
        
        $imported = 0;
        $skipped = 0;
        $errors = [];
        
        while (($row = fgetcsv($fp)) !== false) {
            try {
                // Validate row
                if (count($row) < 3) {
                    $skipped++;
                    continue;
                }
                
                list($jhcisCode, $jhcisName, $drugmukCode) = $row;
                
                // Find drugmuk drug
                $stmt = $this->db->prepare("SELECT id FROM drugs WHERE code = ?");
                $stmt->execute([$drugmukCode]);
                $drug = $stmt->fetch();
                
                if (!$drug) {
                    $errors[] = "Drug not found: {$drugmukCode}";
                    $skipped++;
                    continue;
                }
                
                // Insert mapping
                $stmt = $this->db->prepare(
                    "INSERT INTO jhcis_drug_mapping 
                     (hospital_id, jhcis_drug_code, jhcis_drug_name, drugmuk_drug_id, confidence_score, mapping_method, created_at)
                     VALUES (?, ?, ?, ?, 1.0, 'manual', NOW())
                     ON DUPLICATE KEY UPDATE drugmuk_drug_id = VALUES(drugmuk_drug_id)"
                );
                
                $stmt->execute([
                    $hospitalId,
                    $jhcisCode,
                    $jhcisName,
                    $drug['id']
                ]);
                
                $imported++;
                
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
                $skipped++;
            }
        }
        
        fclose($fp);
        
        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors
        ];
    }
    
    /**
     * Download mapping template
     * 
     * @return string File path
     */
    public function downloadMappingTemplate(): string
    {
        $headers = [
            'JHCIS Drug Code',
            'JHCIS Drug Name',
            'Drugmuk Drug Code'
        ];
        
        $sampleData = [
            ['PAR500', 'Paracetamol 500mg', 'PARA500'],
            ['AMO500', 'Amoxicillin 500mg', 'AMOX500']
        ];
        
        return $this->generateExcel($sampleData, 'mapping_template', $headers);
    }
}
