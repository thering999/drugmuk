<?php

namespace App\Controllers;

use App\Services\JHCIS\JHCISAutoMapper;
use App\Services\JHCIS\JHCISReconciliation;
use App\Services\JHCIS\JHCISRealtimeSync;
use App\Services\JHCIS\JHCISAlertService;
use App\Services\JHCIS\JHCISDataExport;
use App\Services\JHCIS\JHCISReportGenerator;
use App\Services\JHCIS\JHCISConnectionPool;
use App\Core\APIResponse;

/**
 * Enhanced JHCIS Controller
 * 
 * Web interface for JHCIS integration management
 */
class JHCISEnhancedController
{
    private $db;
    
    public function __construct()
    {
        $this->db = \App\Core\Database::getInstance()->getConnection();
        
        // Auto-create JHCIS tables if not exist
        $this->ensureTablesExist();
    }
    
    /**
     * Ensure JHCIS tables exist with correct schema
     */
    private function ensureTablesExist()
    {
        try {
            // First, check if jhcis_sync_log exists and has correct schema
            $stmt = $this->db->query("SHOW TABLES LIKE 'jhcis_sync_log'");
            $tableExists = $stmt->fetch();
            
            if (!$tableExists) {
                // Tables don't exist, create them
                $this->createJHCISTables();
                return;
            }
            
            // Check if sync_status column exists in jhcis_sync_log
            $stmt = $this->db->query("SHOW COLUMNS FROM jhcis_sync_log WHERE Field = 'sync_status'");
            $hasSyncStatus = $stmt->fetch();
            
            if (!$hasSyncStatus) {
                // Schema is outdated, recreate all tables
                error_log("JHCIS tables have outdated schema, recreating...");
                $this->db->exec("DROP TABLE IF EXISTS jhcis_sync_errors");
                $this->db->exec("DROP TABLE IF EXISTS jhcis_alerts");
                $this->db->exec("DROP TABLE IF EXISTS jhcis_sync_log");
                $this->db->exec("DROP TABLE IF EXISTS jhcis_drug_mapping");
                // Don't drop jhcis_hospitals to preserve data
                
                $this->createJHCISTables();
                return;
            }

            // Check jhcis_alerts has the status column
            $stmt = $this->db->query("SHOW COLUMNS FROM jhcis_alerts WHERE Field = 'status'");
            $hasStatus = $stmt->fetch();
            
            if (!$hasStatus) {
                // Recreate jhcis_alerts
                $this->db->exec("DROP TABLE IF EXISTS jhcis_alerts");
                $this->db->exec("
                    CREATE TABLE `jhcis_alerts` (
                      `id` INT(11) NOT NULL AUTO_INCREMENT,
                      `hospital_id` INT(11) NULL,
                      `alert_type` ENUM('sync_failure', 'discrepancy', 'low_stock', 'expiring', 'mapping') NOT NULL,
                      `severity` ENUM('info', 'warning', 'critical') DEFAULT 'info',
                      `title` VARCHAR(255) NOT NULL,
                      `message` TEXT NOT NULL,
                      `status` ENUM('active', 'resolved', 'dismissed') DEFAULT 'active',
                      `is_read` TINYINT(1) DEFAULT 0,
                      `is_resolved` TINYINT(1) DEFAULT 0,
                      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                      `resolved_at` DATETIME NULL,
                      PRIMARY KEY (`id`),
                      KEY `hospital_id` (`hospital_id`),
                      KEY `status` (`status`),
                      KEY `alert_type` (`alert_type`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                ");
            }

            // Ensure other required columns exist
            $this->ensureColumnExists('jhcis_drug_mapping', 'hospital_id', 'INT(11) NULL AFTER id');
            $this->ensureColumnExists('jhcis_drug_mapping', 'jhcis_drug_code', 'VARCHAR(50) NOT NULL AFTER hospital_id');
            $this->ensureColumnExists('jhcis_sync_log', 'hospital_id', 'INT(11) NULL AFTER id');
            $this->ensureColumnExists('jhcis_sync_log', 'started_at', 'DATETIME NOT NULL AFTER sync_status');
            $this->ensureColumnExists('jhcis_sync_log', 'completed_at', 'DATETIME NULL AFTER started_at');
            
        } catch (\PDOException $e) {
            // Table doesn't exist or other DB error, create tables
            error_log("Error checking JHCIS tables: " . $e->getMessage());
            $this->createJHCISTables();
        }
    }
    
    /**
     * Ensure a column exists in a table
     */
    private function ensureColumnExists($table, $column, $definition)
    {
        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM $table WHERE Field = '$column'");
            if (!$stmt->fetch()) {
                $this->db->exec("ALTER TABLE $table ADD COLUMN $column $definition");
                error_log("Added column $column to $table");
            }
        } catch (\PDOException $e) {
            error_log("Error adding column $column to $table: " . $e->getMessage());
        }
    }
    
    /**
     * Create JHCIS tables automatically
     */
    private function createJHCISTables()
    {
        // Create jhcis_hospitals
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS `jhcis_hospitals` (
              `id` INT(11) NOT NULL AUTO_INCREMENT,
              `code` VARCHAR(20) NOT NULL,
              `name` VARCHAR(255) NOT NULL,
              `db_host` VARCHAR(255) NOT NULL,
              `db_port` INT(11) DEFAULT 3306,
              `db_name` VARCHAR(100) NOT NULL,
              `db_user` VARCHAR(100) NOT NULL,
              `db_pass` VARCHAR(255) NOT NULL,
              `is_active` TINYINT(1) DEFAULT 1,
              `last_sync_at` DATETIME NULL,
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `code` (`code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        // Insert default hospital (using host.docker.internal for Windows Docker environment)
        $this->db->exec("
            INSERT IGNORE INTO `jhcis_hospitals` (`code`, `name`, `db_host`, `db_name`, `db_user`, `db_pass`, `is_active`) 
            VALUES ('MAIN', 'โรงพยาบาลหลัก', 'host.docker.internal', 'jhcisdb', 'root', '123456', 1)
        ");
        
        // Create jhcis_drug_mapping
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS `jhcis_drug_mapping` (
              `id` INT(11) NOT NULL AUTO_INCREMENT,
              `hospital_id` INT(11) NULL,
              `jhcis_drug_code` VARCHAR(50) NOT NULL,
              `jhcis_drug_name` VARCHAR(255) NULL,
              `drugmuk_drug_id` INT(11) NOT NULL,
              `mapping_method` VARCHAR(50) DEFAULT 'manual',
              `confidence_score` DECIMAL(5,2) DEFAULT 0.00,
              `is_verified` TINYINT(1) DEFAULT 0,
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `unique_mapping` (`hospital_id`, `jhcis_drug_code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        // Create jhcis_sync_log
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS `jhcis_sync_log` (
              `id` INT(11) NOT NULL AUTO_INCREMENT,
              `hospital_id` INT(11) NULL,
              `sync_type` VARCHAR(50) NOT NULL,
              `sync_status` ENUM('started', 'completed', 'failed') DEFAULT 'started',
              `records_processed` INT(11) DEFAULT 0,
              `records_success` INT(11) DEFAULT 0,
              `records_failed` INT(11) DEFAULT 0,
              `error_message` TEXT NULL,
              `started_at` DATETIME NOT NULL,
              `completed_at` DATETIME NULL,
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        // Drop and recreate jhcis_alerts to ensure correct schema
        $this->db->exec("DROP TABLE IF EXISTS `jhcis_alerts`");
        
        // Create jhcis_alerts with all required columns
        $this->db->exec("
            CREATE TABLE `jhcis_alerts` (
              `id` INT(11) NOT NULL AUTO_INCREMENT,
              `hospital_id` INT(11) NULL,
              `alert_type` ENUM('sync_failure', 'discrepancy', 'low_stock', 'expiring', 'mapping') NOT NULL,
              `severity` ENUM('info', 'warning', 'critical') DEFAULT 'info',
              `title` VARCHAR(255) NOT NULL,
              `message` TEXT NOT NULL,
              `status` ENUM('active', 'resolved', 'dismissed') DEFAULT 'active',
              `is_read` TINYINT(1) DEFAULT 0,
              `is_resolved` TINYINT(1) DEFAULT 0,
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              `resolved_at` DATETIME NULL,
              PRIMARY KEY (`id`),
              KEY `hospital_id` (`hospital_id`),
              KEY `status` (`status`),
              KEY `alert_type` (`alert_type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Create jhcis_sync_errors
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS `jhcis_sync_errors` (
              `id` INT(11) NOT NULL AUTO_INCREMENT,
              `sync_log_id` INT(11) NOT NULL,
              `error_type` VARCHAR(50) NULL,
              `error_message` TEXT NULL,
              `record_data` JSON NULL,
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `sync_log_id` (`sync_log_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    
    /**
     * Main Dashboard
     * GET /admin/jhcis/dashboard
     */
    public function dashboard()
    {
        // Get all hospitals
        $stmt = $this->db->query(
            "SELECT id, name, code, is_active FROM jhcis_hospitals ORDER BY name"
        );
        $hospitals = $stmt->fetchAll();
        
        // Get summary for each hospital
        $summaries = [];
        
        foreach ($hospitals as $hospital) {
            if ($hospital['is_active']) {
                try {
                    // Get sync statistics from jhcis_sync_log
                    $stmt = $this->db->prepare("
                        SELECT 
                            COUNT(*) as total_syncs,
                            SUM(CASE WHEN sync_status = 'completed' THEN 1 ELSE 0 END) as successful_syncs,
                            SUM(records_success) as total_records,
                            MAX(created_at) as last_sync
                        FROM jhcis_sync_log 
                        WHERE hospital_id = ?
                    ");
                    $stmt->execute([$hospital['id']]);
                    $syncStats = $stmt->fetch();
                    
                    // Get drug mapping count
                    $mappingStmt = $this->db->prepare("
                        SELECT COUNT(*) as mapped_drugs 
                        FROM jhcis_drug_mapping 
                        WHERE hospital_id = ?
                    ");
                    $mappingStmt->execute([$hospital['id']]);
                    $mappingStats = $mappingStmt->fetch();
                    
                    // Calculate success rate
                    $successRate = 0;
                    if ($syncStats['total_syncs'] > 0) {
                        $successRate = round(($syncStats['successful_syncs'] / $syncStats['total_syncs']) * 100, 1);
                    }
                    
                    $summaries[$hospital['id']] = [
                        'sync_performance' => [
                            'success_rate' => $successRate,
                            'total_syncs' => $syncStats['total_syncs'],
                            'total_records' => $syncStats['total_records'] ?? 0
                        ],
                        'data_quality' => [
                            'mapped_drugs' => $mappingStats['mapped_drugs'] ?? 0
                        ],
                        'alerts' => [
                            'active_count' => 0
                        ],
                        'last_sync' => $syncStats['last_sync']
                    ];
                    
                } catch (\Exception $e) {
                    // If no sync data yet, show initial state
                    $summaries[$hospital['id']] = [
                        'sync_performance' => [
                            'success_rate' => 0,
                            'total_syncs' => 0,
                            'total_records' => 0
                        ],
                        'data_quality' => [
                            'mapped_drugs' => 0
                        ],
                        'alerts' => [
                            'active_count' => 0
                        ],
                        'last_sync' => null,
                        'no_data' => true
                    ];
                }
            }
        }
        
        include __DIR__ . '/../Views/jhcis/enhanced_dashboard.php';
    }
    
    /**
     * Auto Mapping Page
     * GET /admin/jhcis/auto-mapping
     */
    public function autoMappingPage()
    {
        $hospitalId = $_GET['hospital_id'] ?? null;
        
        if (!$hospitalId) {
            header('Location: /admin/jhcis/dashboard');
            return;
        }
        
        include __DIR__ . '/../Views/jhcis/auto_mapping.php';
    }
    
    /**
     * Get Mapping Suggestions (API)
     * POST /api/jhcis/auto-mapping/suggest
     */
    public function suggestMappings()
    {
        $hospitalId = $_POST['hospital_id'] ?? null;
        $minConfidence = $_POST['min_confidence'] ?? 0.8;
        
        if (!$hospitalId) {
            APIResponse::error('Hospital ID required', 400);
            return;
        }
        
        try {
            $mapper = new JHCISAutoMapper();
            $suggestions = $mapper->suggestMappings($hospitalId, $minConfidence);
            
            APIResponse::success([
                'suggestions' => $suggestions,
                'count' => count($suggestions)
            ]);
            
        } catch (\Exception $e) {
            APIResponse::error($e->getMessage(), 500);
        }
    }
    
    /**
     * Apply Mappings (API)
     * POST /api/jhcis/auto-mapping/apply
     */
    public function applyMappings()
    {
        $hospitalId = $_POST['hospital_id'] ?? null;
        $mappings = json_decode($_POST['mappings'] ?? '[]', true);
        
        if (!$hospitalId || empty($mappings)) {
            APIResponse::error('Invalid request', 400);
            return;
        }
        
        try {
            $mapper = new JHCISAutoMapper();
            $result = $mapper->applyMappings($mappings, $hospitalId);
            
            APIResponse::success($result, 'Mappings applied successfully');
            
        } catch (\Exception $e) {
            APIResponse::error($e->getMessage(), 500);
        }
    }
    
    /**
     * Reconciliation Page
     * GET /admin/jhcis/reconciliation
     */
    public function reconciliationPage()
    {
        $hospitalId = $_GET['hospital_id'] ?? null;
        
        if (!$hospitalId) {
            header('Location: /admin/jhcis/dashboard');
            return;
        }
        
        include __DIR__ . '/../Views/jhcis/reconciliation.php';
    }
    
    /**
     * Run Reconciliation (API)
     * POST /api/jhcis/reconciliation/run
     */
    public function runReconciliation()
    {
        $hospitalId = $_POST['hospital_id'] ?? null;
        $tolerance = $_POST['tolerance'] ?? 5.0;
        
        if (!$hospitalId) {
            APIResponse::error('Hospital ID required', 400);
            return;
        }
        
        try {
            $reconciler = new JHCISReconciliation();
            $discrepancies = $reconciler->findDiscrepancies($hospitalId, $tolerance);
            
            APIResponse::success([
                'discrepancies' => $discrepancies,
                'count' => count($discrepancies)
            ]);
            
        } catch (\Exception $e) {
            APIResponse::error($e->getMessage(), 500);
        }
    }
    
    /**
     * Apply Adjustments (API)
     * POST /api/jhcis/reconciliation/adjust
     */
    public function applyAdjustments()
    {
        $adjustments = json_decode($_POST['adjustments'] ?? '[]', true);
        $userId = $_SESSION['user_id'] ?? 1;
        $requireApproval = $_POST['require_approval'] ?? true;
        
        if (empty($adjustments)) {
            APIResponse::error('No adjustments provided', 400);
            return;
        }
        
        try {
            $reconciler = new JHCISReconciliation();
            $result = $reconciler->applyAdjustments($adjustments, $userId, $requireApproval);
            
            APIResponse::success($result, 'Adjustments processed');
            
        } catch (\Exception $e) {
            APIResponse::error($e->getMessage(), 500);
        }
    }
    
    /**
     * Sync Settings Page
     * GET /admin/jhcis/sync-settings
     */
    public function syncSettingsPage()
    {
        $hospitalId = $_GET['hospital_id'] ?? null;
        
        if (!$hospitalId) {
            header('Location: /admin/jhcis/dashboard');
            return;
        }
        
        $sync = new JHCISRealtimeSync();
        $status = $sync->getStatus($hospitalId);
        
        include __DIR__ . '/../Views/jhcis/sync_settings.php';
    }
    
    /**
     * Update Sync Settings (API)
     * POST /api/jhcis/sync/settings
     */
    public function updateSyncSettings()
    {
        $hospitalId = $_POST['hospital_id'] ?? null;
        $enabled = $_POST['enabled'] ?? false;
        $interval = $_POST['interval'] ?? 15;
        
        if (!$hospitalId) {
            APIResponse::error('Hospital ID required', 400);
            return;
        }
        
        try {
            $sync = new JHCISRealtimeSync();
            
            if ($enabled) {
                $sync->enable($hospitalId, $interval);
            } else {
                $sync->disable($hospitalId);
            }
            
            APIResponse::success(null, 'Sync settings updated');
            
        } catch (\Exception $e) {
            APIResponse::error($e->getMessage(), 500);
        }
    }
    
    /**
     * Export Data
     * GET /admin/jhcis/export
     */
    public function exportData()
    {
        $type = $_GET['type'] ?? 'mappings';
        $hospitalId = $_GET['hospital_id'] ?? null;
        
        if (!$hospitalId) {
            APIResponse::error('Hospital ID required', 400);
            return;
        }
        
        try {
            $export = new JHCISDataExport();
            
            switch ($type) {
                case 'mappings':
                    $filepath = $export->exportDrugMappings($hospitalId);
                    break;
                    
                case 'sync_logs':
                    $fromDate = $_GET['from_date'] ?? date('Y-m-d', strtotime('-30 days'));
                    $toDate = $_GET['to_date'] ?? date('Y-m-d');
                    $filepath = $export->exportSyncLogs($hospitalId, $fromDate, $toDate);
                    break;
                    
                default:
                    APIResponse::error('Invalid export type', 400);
                    return;
            }
            
            // Download file
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
            readfile($filepath);
            unlink($filepath);
            
        } catch (\Exception $e) {
            APIResponse::error($e->getMessage(), 500);
        }
    }
    
    /**
     * Reports Page
     * GET /admin/jhcis/reports
     */
    public function reportsPage()
    {
        $hospitalId = $_GET['hospital_id'] ?? null;
        
        if (!$hospitalId) {
            header('Location: /admin/jhcis/dashboard');
            return;
        }
        
        include __DIR__ . '/../Views/jhcis/reports.php';
    }
    
    /**
     * Generate Report (API)
     * POST /api/jhcis/reports/generate
     */
    public function generateReport()
    {
        $type = $_POST['type'] ?? 'performance';
        $hospitalId = $_POST['hospital_id'] ?? null;
        
        if (!$hospitalId) {
            APIResponse::error('Hospital ID required', 400);
            return;
        }
        
        try {
            $reporter = new JHCISReportGenerator();
            
            switch ($type) {
                case 'performance':
                    $fromDate = $_POST['from_date'] ?? date('Y-m-d', strtotime('-30 days'));
                    $toDate = $_POST['to_date'] ?? date('Y-m-d');
                    $report = $reporter->generateSyncPerformanceReport($hospitalId, $fromDate, $toDate);
                    break;
                    
                case 'quality':
                    $report = $reporter->generateDataQualityReport($hospitalId);
                    break;
                    
                case 'summary':
                    $report = $reporter->generateExecutiveSummary($hospitalId);
                    break;
                    
                default:
                    APIResponse::error('Invalid report type', 400);
                    return;
            }
            
            APIResponse::success($report);
            
        } catch (\Exception $e) {
            APIResponse::error($e->getMessage(), 500);
        }
    }
    
    /**
     * Test Connection (API)
     * POST /api/jhcis/connection/test
     */
    public function testConnection()
    {
        $hospitalId = $_POST['hospital_id'] ?? null;
        
        if (!$hospitalId) {
            APIResponse::error('Hospital ID required', 400);
            return;
        }
        
        try {
            $result = JHCISConnectionPool::testConnection($hospitalId);
            APIResponse::success($result);
            
        } catch (\Exception $e) {
            APIResponse::error($e->getMessage(), 500);
        }
    }
    
    /**
     * Test Connection By ID (API)
     * GET /api/jhcis/test-connection/{id}
     */
    public function testConnectionById($id)
    {
        header('Content-Type: application/json');
        
        try {
            $syncService = new \App\Services\JHCIS\JHCISSyncService();
            $result = $syncService->testConnection($id);
            
            echo json_encode($result);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    /**
     * Sync Now (API)
     * POST /api/jhcis/sync-now/{id}
     */
    public function syncNow($id)
    {
        header('Content-Type: application/json');
        
        try {
            $syncService = new \App\Services\JHCIS\JHCISSyncService();
            
            // Sync last 30 days
            $fromDate = date('Y-m-d', strtotime('-30 days'));
            $toDate = date('Y-m-d');
            
            $result = $syncService->syncDispensing($id, $fromDate, $toDate);
            
            echo json_encode($result);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
}
