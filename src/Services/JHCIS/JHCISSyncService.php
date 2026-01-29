<?php

namespace App\Services\JHCIS;

use App\Core\Database;
use PDO;

/**
 * JHCIS Sync Service
 * Handles synchronization between Drugmuk and JHCIS databases
 */
class JHCISSyncService
{
    private $drugmukDb;
    private $jhcisDb;
    
    public function __construct()
    {
        $this->drugmukDb = Database::getInstance()->getConnection();
    }
    
    /**
     * Connect to JHCIS database for a specific hospital
     */
    private function connectJHCIS(int $hospitalId): void
    {
        // Get hospital config
        $stmt = $this->drugmukDb->prepare(
            "SELECT * FROM jhcis_hospitals WHERE id = ? AND is_active = 1"
        );
        $stmt->execute([$hospitalId]);
        $hospital = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$hospital) {
            throw new \Exception("Hospital not found or inactive");
        }
        
        // Connect to JHCIS database
        $host = $hospital['db_host'];
        if ($host === 'localhost' || $host === '127.0.0.1') {
            $host = 'host.docker.internal';
        }

        $dsn = "mysql:host=$host;port={$hospital['db_port']};dbname={$hospital['db_name']};charset=utf8mb4";
        
        $this->jhcisDb = new PDO($dsn, $hospital['db_user'], $hospital['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }
    
    /**
     * Test connection to JHCIS database
     */
    public function testConnection(int $hospitalId): array
    {
        try {
            $this->connectJHCIS($hospitalId);
            
            // Get available tables
            $stmt = $this->jhcisDb->query("SHOW TABLES");
            $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Try to count drugs
            $drugCount = 0;
            if (in_array('cdrug', $allTables)) {
                $stmt = $this->jhcisDb->query("SELECT COUNT(*) as total FROM cdrug");
                $result = $stmt->fetch();
                $drugCount = $result['total'];
            }
            
            // Check for common dispensing tables
            $dispensingTables = array_intersect(['visitdrug', 'opd_dispensing', 'dispensing'], $allTables);
            
            return [
                'success' => true,
                'message' => 'เชื่อมต่อสำเร็จ',
                'total_drugs' => $drugCount,
                'total_tables' => count($allTables),
                'dispensing_tables' => array_values($dispensingTables),
                'sample_tables' => array_slice($allTables, 0, 20)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'เชื่อมต่อไม่สำเร็จ: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Sync dispensing data from JHCIS
     */
    public function syncDispensing(int $hospitalId, string $fromDate, string $toDate): array
    {
        $this->connectJHCIS($hospitalId);
        
        // Start sync log
        $logId = $this->startSyncLog($hospitalId, 'dispensing');
        
        try {
            // First, detect what tables are available in JHCIS
            $availableTables = $this->getAvailableTables();
            
            // Check if we have the required tables
            if (!in_array('cdrug', $availableTables)) {
                throw new \Exception("JHCIS database missing required 'cdrug' table. Available tables: " . implode(', ', array_slice($availableTables, 0, 10)));
            }
            
            // Detect dispensing table (visitdrug is the most common)
            $dispensingTable = $this->detectTable(['visitdrug', 'opd_dispensing', 'dispensing']);
            if (!$dispensingTable) {
                throw new \Exception("No dispensing table found in JHCIS. Available tables: " . implode(', ', array_slice($availableTables, 0, 10)));
            }
            
            // Get columns from the dispensing table
            $dispensingColumns = $this->getTableColumns($dispensingTable);
            
            // Detect VN/Visit field (this should exist)
            $vnField = $this->detectColumnFromList($dispensingColumns, ['visitno', 'vn', 'visit_no', 'vstno', 'visit_id']);
            if (!$vnField) {
                throw new \Exception("Cannot find VN (visit number) column in $dispensingTable table. Available columns: " . implode(', ', $dispensingColumns));
            }
            
            // Detect drug code field
            $drugCodeField = $this->detectColumnFromList($dispensingColumns, ['drugcode', 'drug_code', 'did', 'drug_id', 'drugid']);
            if (!$drugCodeField) {
                throw new \Exception("Cannot find drug code column in $dispensingTable table. Available columns: " . implode(', ', $dispensingColumns));
            }
            
            // Detect quantity field (might not exist in visitdrug)
            $qtyField = $this->detectColumnFromList($dispensingColumns, ['qty', 'quantity', 'amount', 'drug_qty', 'dispense_qty', 'unit']);
            
            // Check if HN exists in dispensing table
            $hnInDispensing = $this->detectColumnFromList($dispensingColumns, ['hn', 'patient_hn', 'HN', 'cid', 'patient_id', 'pid']);
            $dateInDispensing = $this->detectColumnFromList($dispensingColumns, ['datestart', 'dispense_date', 'vstdate', 'date', 'created_date', 'date_visit', 'visitdate', 'dateupdate']);
            
            // If HN or date not in dispensing table, we need to join with visit table
            $visitJoin = "";
            $hnField = $hnInDispensing;
            $dateField = $dateInDispensing;
            
            if (!$hnInDispensing || !$dateInDispensing) {
                // Find visit/OPD table
                $visitTable = $this->detectTable(['visit', 'opd', 'opd_visit', 'visitopd']);
                if ($visitTable) {
                    $visitColumns = $this->getTableColumns($visitTable);
                    $visitVnField = $this->detectColumnFromList($visitColumns, ['visitno', 'vn', 'visit_no', 'vstno', 'id']);
                    
                    if (!$hnInDispensing) {
                        $hnField = $this->detectColumnFromList($visitColumns, ['hn', 'patient_hn', 'HN', 'cid', 'patient_id', 'pid']);
                    }
                    if (!$dateInDispensing) {
                        $dateField = $this->detectColumnFromList($visitColumns, ['datestart', 'vstdate', 'visit_date', 'date', 'created_date', 'dateupdate']);
                    }
                    
                    if ($visitVnField && ($hnField || $dateField)) {
                        $visitJoin = "INNER JOIN $visitTable vst ON d.$vnField = vst.$visitVnField";
                        if ($hnField) $hnField = "vst.$hnField";
                        if ($dateField) $dateField = "vst.$dateField";
                    }
                }
            } else {
                $hnField = "d.$hnField";
                $dateField = "d.$dateField";
            }
            
            // If still no HN, use visitno as identifier
            if (!$hnField) {
                $hnField = "d.$vnField"; // Use visitno as HN
            }
            
            // If still no date, use dateupdate or current date
            if (!$dateField) {
                $updateField = $this->detectColumnFromList($dispensingColumns, ['dateupdate', 'updated_at', 'created_at']);
                if ($updateField) {
                    $dateField = "d.$updateField";
                } else {
                    $dateField = "CURDATE()";
                }
            }
            
            // Detect patient table for names
            $personTable = $this->detectTable(['patient', 'person']);
            $patientJoin = "";
            $nameField = "'Unknown'";
            
            if ($personTable && strpos($hnField, 'vst.') !== false) {
                $personColumns = $this->getTableColumns($personTable);
                $personHnField = $this->detectColumnFromList($personColumns, ['hn', 'patient_hn', 'HN', 'cid']);
                $fnameField = $this->detectColumnFromList($personColumns, ['fname', 'first_name', 'name']);
                $lnameField = $this->detectColumnFromList($personColumns, ['lname', 'last_name', 'surname']);
                
                if ($personHnField && $fnameField) {
                    $patientJoin = "LEFT JOIN $personTable p ON vst." . str_replace('vst.', '', $hnField) . " = p.$personHnField";
                    if ($lnameField) {
                        $nameField = "CONCAT(COALESCE(p.$fnameField, ''), ' ', COALESCE(p.$lnameField, ''))";
                    } else {
                        $nameField = "COALESCE(p.$fnameField, 'Unknown')";
                    }
                }
            }

            // Build the query
            $qtySelect = $qtyField ? "d.$qtyField" : "1";
            
            $sql = "SELECT 
                        $hnField as hn,
                        d.$vnField as vn,
                        $nameField as patient_name,
                        $dateField as vstdate,
                        d.$drugCodeField as drugcode,
                        dr.drugname as drug_name,
                        $qtySelect as qty
                    FROM $dispensingTable d
                    $visitJoin
                    $patientJoin
                    INNER JOIN cdrug dr ON d.$drugCodeField = dr.drugcode
                    WHERE $dateField BETWEEN ? AND ?
                    ORDER BY $dateField DESC
                    LIMIT 500";
            
            $stmt = $this->jhcisDb->prepare($sql);
            $stmt->execute([$fromDate, $toDate]);
            $records = $stmt->fetchAll();
            
            $imported = 0;
            $failed = 0;
            
            // Group by VN
            $dispensingGroups = [];
            foreach ($records as $record) {
                $vn = $record['vn'];
                if (!isset($dispensingGroups[$vn])) {
                    $dispensingGroups[$vn] = [
                        'hn' => $record['hn'],
                        'vn' => $record['vn'],
                        'patient_name' => trim($record['patient_name']) ?: 'Unknown',
                        'dispense_date' => $record['vstdate'],
                        'items' => []
                    ];
                }
                
                // Find drug in Drugmuk - first check mapping table
                $mappingStmt = $this->drugmukDb->prepare("
                    SELECT drugmuk_drug_id 
                    FROM jhcis_drug_mapping 
                    WHERE jhcis_drug_code = ? AND (hospital_id = ? OR hospital_id IS NULL)
                ");
                $mappingStmt->execute([$record['drugcode'], $hospitalId]);
                $mapping = $mappingStmt->fetch();
                
                $drugId = null;
                if ($mapping) {
                    $drugId = $mapping['drugmuk_drug_id'];
                } else {
                    // Fallback to direct code match in drugs table
                    $drugStmt = $this->drugmukDb->prepare("SELECT id FROM drugs WHERE code = ?");
                    $drugStmt->execute([$record['drugcode']]);
                    $drug = $drugStmt->fetch();
                    if ($drug) {
                        $drugId = $drug['id'];
                    }
                }
                
                if ($drugId) {
                    $dispensingGroups[$vn]['items'][] = [
                        'drug_id' => $drugId,
                        'quantity' => $record['qty'] ?: 1
                    ];
                }
            }
            
            // Insert into Drugmuk
            foreach ($dispensingGroups as $vn => $group) {
                if (empty($group['items'])) {
                    $failed++;
                    continue;
                }
                
                // Check if already exists
                $checkStmt = $this->drugmukDb->prepare("SELECT id FROM dispensing WHERE vn = ?");
                $checkStmt->execute([$vn]);
                if ($checkStmt->fetch()) {
                    continue; // Skip duplicates
                }
                
                try {
                    $this->drugmukDb->beginTransaction();
                    
                    // Insert dispensing header
                    $insertStmt = $this->drugmukDb->prepare(
                        "INSERT INTO dispensing (hn, vn, patient_name, dispense_date, user_id, created_at)
                         VALUES (?, ?, ?, ?, 1, NOW())"
                    );
                    $insertStmt->execute([
                        $group['hn'],
                        $group['vn'],
                        $group['patient_name'],
                        $group['dispense_date']
                    ]);
                    
                    $dispenseId = $this->drugmukDb->lastInsertId();
                    
                    // Insert items
                    $itemStmt = $this->drugmukDb->prepare(
                        "INSERT INTO dispensing_items (dispense_id, drug_id, quantity)
                         VALUES (?, ?, ?)"
                    );
                    
                    foreach ($group['items'] as $item) {
                        $itemStmt->execute([
                            $dispenseId,
                            $item['drug_id'],
                            $item['quantity']
                        ]);
                    }
                    
                    $this->drugmukDb->commit();
                    $imported++;
                    
                } catch (\Exception $e) {
                    $this->drugmukDb->rollBack();
                    $failed++;
                }
            }
            
            // Complete sync log
            $this->completeSyncLog($logId, count($records), $imported, $failed);
            
            // Update hospital last sync
            $this->drugmukDb->prepare("UPDATE jhcis_hospitals SET last_sync_at = NOW() WHERE id = ?")
                ->execute([$hospitalId]);
            
            return [
                'success' => true,
                'total_records' => count($records),
                'imported' => $imported,
                'failed' => $failed
            ];
            
        } catch (\Exception $e) {
            $this->failSyncLog($logId, $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Sync all active hospitals
     */
    public function syncAllHospitals(string $fromDate, string $toDate): array
    {
        $stmt = $this->drugmukDb->query(
            "SELECT id, name FROM jhcis_hospitals WHERE is_active = 1"
        );
        $hospitals = $stmt->fetchAll();
        
        $results = [];
        foreach ($hospitals as $hospital) {
            try {
                $result = $this->syncDispensing($hospital['id'], $fromDate, $toDate);
                $results[$hospital['name']] = $result;
            } catch (\Exception $e) {
                $results[$hospital['name']] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Start sync log
     */
    private function startSyncLog(int $hospitalId, string $syncType): int
    {
        $stmt = $this->drugmukDb->prepare(
            "INSERT INTO jhcis_sync_log 
             (hospital_id, sync_type, sync_status, started_at, created_at)
             VALUES (?, ?, 'started', NOW(), NOW())"
        );
        $stmt->execute([$hospitalId, $syncType]);
        
        return $this->drugmukDb->lastInsertId();
    }
    
    /**
     * Complete sync log
     */
    private function completeSyncLog(int $logId, int $processed, int $success, int $failed): void
    {
        $stmt = $this->drugmukDb->prepare(
            "UPDATE jhcis_sync_log 
             SET sync_status = 'completed',
                 records_processed = ?,
                 records_success = ?,
                 records_failed = ?,
                 completed_at = NOW()
             WHERE id = ?"
        );
        $stmt->execute([$processed, $success, $failed, $logId]);
    }
    
    /**
     * Fail sync log
     */
    private function failSyncLog(int $logId, string $error): void
    {
        $stmt = $this->drugmukDb->prepare(
            "UPDATE jhcis_sync_log 
             SET sync_status = 'failed',
                 error_message = ?,
                 completed_at = NOW()
             WHERE id = ?"
        );
        $stmt->execute([$error, $logId]);
    }
    /**
     * Detect which table exists in JHCIS
     */
    private function detectTable(array $tables): ?string
    {
        foreach ($tables as $table) {
            try {
                $this->jhcisDb->query("SELECT 1 FROM $table LIMIT 1");
                return $table;
            } catch (\Exception $e) {
                continue;
            }
        }
        return $tables[0]; // Fallback to first one
    }

    /**
     * Detect which column exists in a table
     */
    private function detectColumn(string $table, array $columns): string
    {
        foreach ($columns as $col) {
            try {
                $this->jhcisDb->query("SELECT $col FROM $table LIMIT 1");
                return $col;
            } catch (\Exception $e) {
                continue;
            }
        }
        return $columns[0]; // Fallback to first one
    }
    
    /**
     * Get all available tables in JHCIS database
     */
    private function getAvailableTables(): array
    {
        try {
            $stmt = $this->jhcisDb->query("SHOW TABLES");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Get all columns in a table
     */
    private function getTableColumns(string $table): array
    {
        try {
            $stmt = $this->jhcisDb->query("SHOW COLUMNS FROM $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return array_column($columns, 'Field');
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Detect which column exists from a list of column names
     */
    private function detectColumnFromList(array $availableColumns, array $searchColumns): ?string
    {
        foreach ($searchColumns as $col) {
            if (in_array($col, $availableColumns)) {
                return $col;
            }
        }
        return null;
    }
}
