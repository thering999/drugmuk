<?php
/**
 * Automated Task Scheduler
 * Cron-like task scheduler for automated operations
 */

namespace App\Services;

class TaskSchedulerService
{
    private $db;
    private $notify;
    
    public function __construct($db)
    {
        $this->db = $db;
        $this->notify = new NotificationService();
    }
    
    /**
     * Run all scheduled tasks
     */
    public function runScheduledTasks(): void
    {
        $this->logTask('scheduler_start', 'Starting scheduled tasks');
        
        try {
            // Daily tasks
            if ($this->shouldRun('daily')) {
                $this->runDailyTasks();
            }
            
            // Weekly tasks
            if ($this->shouldRun('weekly')) {
                $this->runWeeklyTasks();
            }
            
            // Monthly tasks
            if ($this->shouldRun('monthly')) {
                $this->runMonthlyTasks();
            }
            
            $this->logTask('scheduler_complete', 'All tasks completed successfully');
        } catch (\Exception $e) {
            $this->logTask('scheduler_error', $e->getMessage());
            $this->notify->sendLine('❌ Scheduler Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Daily tasks
     */
    private function runDailyTasks(): void
    {
        $this->logTask('daily_tasks_start', 'Running daily tasks');
        
        // 1. Check expiring drugs
        $this->checkExpiringDrugs();
        
        // 2. Check low stock
        $this->checkLowStock();
        
        // 3. Sync JHCIS data
        $this->syncJHCISData();
        
        // 4. Clean old logs
        $this->cleanOldLogs();
        
        // 5. Update cache
        $this->updateCache();
        
        $this->logTask('daily_tasks_complete', 'Daily tasks completed');
    }
    
    /**
     * Weekly tasks
     */
    private function runWeeklyTasks(): void
    {
        $this->logTask('weekly_tasks_start', 'Running weekly tasks');
        
        // 1. Generate weekly report
        $this->generateWeeklyReport();
        
        // 2. Check slow-moving stock
        $this->checkSlowMovingStock();
        
        // 3. Optimize database
        $this->optimizeDatabase();
        
        $this->logTask('weekly_tasks_complete', 'Weekly tasks completed');
    }
    
    /**
     * Monthly tasks
     */
    private function runMonthlyTasks(): void
    {
        $this->logTask('monthly_tasks_start', 'Running monthly tasks');
        
        // 1. Generate monthly report
        $this->generateMonthlyReport();
        
        // 2. Archive old data
        $this->archiveOldData();
        
        // 3. Check contracts expiring
        $this->checkExpiringContracts();
        
        $this->logTask('monthly_tasks_complete', 'Monthly tasks completed');
    }
    
    /**
     * Check expiring drugs
     */
    private function checkExpiringDrugs(): void
    {
        $stmt = $this->db->query("
            SELECT 
                dr.name,
                i.lot_no,
                i.expire_date,
                i.quantity,
                dr.unit
            FROM inventory i
            JOIN drugs dr ON i.drug_id = dr.id
            WHERE i.expire_date <= DATE_ADD(NOW(), INTERVAL 30 DAY)
                AND i.expire_date > NOW()
                AND i.quantity > 0
            ORDER BY i.expire_date ASC
        ");
        
        $drugs = $stmt->fetchAll();
        
        if (!empty($drugs)) {
            $this->notify->notifyExpiringDrugs($drugs);
            $this->logTask('expiring_drugs_check', count($drugs) . ' drugs expiring soon');
        }
    }
    
    /**
     * Check low stock
     */
    private function checkLowStock(): void
    {
        $stmt = $this->db->query("
            SELECT 
                dr.name,
                COALESCE(SUM(i.quantity), 0) as quantity,
                dr.min_level,
                dr.unit
            FROM drugs dr
            LEFT JOIN inventory i ON dr.id = i.drug_id
            WHERE dr.is_active = 1
            GROUP BY dr.id
            HAVING quantity < dr.min_level
            ORDER BY (dr.min_level - quantity) DESC
        ");
        
        $drugs = $stmt->fetchAll();
        
        if (!empty($drugs)) {
            $this->notify->notifyLowStock($drugs);
            $this->logTask('low_stock_check', count($drugs) . ' drugs below minimum level');
        }
    }
    
    /**
     * Sync JHCIS data
     */
    private function syncJHCISData(): void
    {
        // Run JHCIS sync script
        $output = shell_exec('php scripts/auto_sync.php 2>&1');
        $this->logTask('jhcis_sync', 'JHCIS sync completed: ' . $output);
    }
    
    /**
     * Clean old logs
     */
    private function cleanOldLogs(): void
    {
        // Delete logs older than 90 days
        $stmt = $this->db->query("
            DELETE FROM audit_logs 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)
        ");
        
        $deleted = $stmt->rowCount();
        $this->logTask('clean_logs', "Deleted {$deleted} old log entries");
    }
    
    /**
     * Update cache
     */
    private function updateCache(): void
    {
        $cache = new CacheService();
        
        // Clear old cache
        $cache->clear();
        
        // Warm up important caches
        $analytics = new DashboardAnalyticsService($this->db);
        $analytics->getOverview();
        $analytics->getTopUsedDrugs(10);
        
        $this->logTask('cache_update', 'Cache updated successfully');
    }
    
    /**
     * Generate weekly report
     */
    private function generateWeeklyReport(): void
    {
        $reportGen = new ReportGeneratorService($this->db);
        
        $startDate = date('Y-m-d', strtotime('-7 days'));
        $endDate = date('Y-m-d');
        
        $usage = $reportGen->generateDrugUsageReport($startDate, $endDate);
        
        // Save report or send notification
        $this->logTask('weekly_report', 'Generated weekly report with ' . count($usage) . ' items');
    }
    
    /**
     * Check slow-moving stock
     */
    private function checkSlowMovingStock(): void
    {
        $optimizer = new InventoryOptimizationService($this->db);
        $slowMoving = $optimizer->identifySlowMovingStock(90);
        
        if (count($slowMoving) > 10) {
            $message = "⚠️ พบยาเคลื่อนไหวช้า {count($slowMoving)} รายการ\n";
            $message .= "ควรตรวจสอบและพิจารณาลดสต็อก";
            
            $this->notify->sendLine($message);
        }
        
        $this->logTask('slow_moving_check', count($slowMoving) . ' slow-moving items found');
    }
    
    /**
     * Optimize database
     */
    private function optimizeDatabase(): void
    {
        $tables = ['drugs', 'inventory', 'orders', 'dispensing', 'audit_logs'];
        
        foreach ($tables as $table) {
            $this->db->exec("OPTIMIZE TABLE {$table}");
        }
        
        $this->logTask('db_optimize', 'Database optimized');
    }
    
    /**
     * Generate monthly report
     */
    private function generateMonthlyReport(): void
    {
        $reportGen = new ReportGeneratorService($this->db);
        
        $startDate = date('Y-m-01', strtotime('-1 month'));
        $endDate = date('Y-m-t', strtotime('-1 month'));
        
        $financial = $reportGen->generateFinancialReport($startDate, $endDate);
        
        $this->logTask('monthly_report', 'Generated monthly financial report');
    }
    
    /**
     * Archive old data
     */
    private function archiveOldData(): void
    {
        // Archive dispensing records older than 2 years
        $stmt = $this->db->query("
            DELETE FROM dispensing 
            WHERE dispense_date < DATE_SUB(NOW(), INTERVAL 2 YEAR)
        ");
        
        $archived = $stmt->rowCount();
        $this->logTask('archive_data', "Archived {$archived} old dispensing records");
    }
    
    /**
     * Check expiring contracts
     */
    private function checkExpiringContracts(): void
    {
        $stmt = $this->db->query("
            SELECT 
                code,
                name,
                end_date,
                DATEDIFF(end_date, NOW()) as days_until_expiry
            FROM contracts
            WHERE end_date <= DATE_ADD(NOW(), INTERVAL 60 DAY)
                AND end_date > NOW()
                AND status = 'active'
            ORDER BY end_date ASC
        ");
        
        $contracts = $stmt->fetchAll();
        
        if (!empty($contracts)) {
            $message = "📋 แจ้งเตือน: สัญญาใกล้หมดอายุ\n\n";
            
            foreach ($contracts as $contract) {
                $message .= "• {$contract['name']}\n";
                $message .= "  หมดอายุ: {$contract['end_date']} ({$contract['days_until_expiry']} วัน)\n\n";
            }
            
            $this->notify->sendLine($message);
        }
        
        $this->logTask('contract_check', count($contracts) . ' contracts expiring soon');
    }
    
    /**
     * Check if task should run
     */
    private function shouldRun(string $frequency): bool
    {
        $lastRun = $this->getLastRun($frequency);
        
        if (!$lastRun) {
            return true;
        }
        
        $now = new \DateTime();
        $last = new \DateTime($lastRun);
        
        switch ($frequency) {
            case 'daily':
                return $now->format('Y-m-d') !== $last->format('Y-m-d');
            case 'weekly':
                return $now->format('Y-W') !== $last->format('Y-W');
            case 'monthly':
                return $now->format('Y-m') !== $last->format('Y-m');
            default:
                return false;
        }
    }
    
    /**
     * Get last run time
     */
    private function getLastRun(string $frequency): ?string
    {
        $stmt = $this->db->prepare("
            SELECT MAX(created_at) 
            FROM task_logs 
            WHERE task_name = :task_name
        ");
        
        $stmt->execute(['task_name' => "{$frequency}_tasks_complete"]);
        return $stmt->fetchColumn() ?: null;
    }
    
    /**
     * Log task execution
     */
    private function logTask(string $taskName, string $details): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO task_logs (task_name, details, created_at)
            VALUES (:task_name, :details, NOW())
        ");
        
        $stmt->execute([
            'task_name' => $taskName,
            'details' => $details
        ]);
    }
}

// Create task_logs table if not exists
/*
CREATE TABLE IF NOT EXISTS task_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_name VARCHAR(100) NOT NULL,
    details TEXT,
    created_at DATETIME NOT NULL,
    INDEX idx_task_name (task_name),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
*/
