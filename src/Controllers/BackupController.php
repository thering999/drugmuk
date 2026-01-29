<?php
/**
 * Backup Manager Controller
 * จัดการ Backup/Restore ฐานข้อมูล
 * 
 * @package Drugmuk
 * @version 3.4.0
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Services\BackupService;
use App\Services\ActivityLogService;

class BackupController extends Controller
{
    private BackupService $backupService;
    private ActivityLogService $logService;
    private string $backupDir;
    
    public function __construct()
    {
        $this->backupService = new BackupService();
        $this->logService = new ActivityLogService();
        $this->backupDir = __DIR__ . '/../../backups';
        
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }
    
    /**
     * หน้าจัดการ Backup
     */
    public function index()
    {
        $backups = $this->getBackupList();
        $diskUsage = $this->getDiskUsage();
        $lastBackup = $this->getLastBackupInfo();
        
        $this->view('backup/index', [
            'backups' => $backups,
            'disk_usage' => $diskUsage,
            'last_backup' => $lastBackup
        ]);
    }
    
    /**
     * สร้าง Backup
     */
    public function create()
    {
        $this->validateCSRF();
        
        try {
            $type = $_POST['type'] ?? 'full';
            $filename = $this->generateBackupFilename($type);
            $filepath = $this->backupDir . '/' . $filename;
            
            if ($type === 'database') {
                $result = $this->backupDatabase($filepath);
            } else {
                $result = $this->backupFull($filepath);
            }
            
            if ($result) {
                $this->logService->log('backup_create', 'system', "สร้าง backup: $filename");
                $_SESSION['success'] = "สร้าง Backup สำเร็จ: $filename";
            } else {
                $_SESSION['error'] = 'ไม่สามารถสร้าง Backup ได้';
            }
        } catch (\Exception $e) {
            $_SESSION['error'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        }
        
        header('Location: /backup');
        exit;
    }
    
    /**
     * Download Backup
     */
    public function download($filename)
    {
        $filepath = $this->backupDir . '/' . basename($filename);
        
        if (!file_exists($filepath)) {
            $_SESSION['error'] = 'ไม่พบไฟล์ Backup';
            header('Location: /backup');
            exit;
        }
        
        $this->logService->log('backup_download', 'system', "ดาวน์โหลด backup: $filename");
        
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    }
    
    /**
     * ลบ Backup
     */
    public function delete()
    {
        $this->validateCSRF();
        
        $filename = $_POST['filename'] ?? '';
        $filepath = $this->backupDir . '/' . basename($filename);
        
        if (file_exists($filepath) && unlink($filepath)) {
            $this->logService->log('backup_delete', 'system', "ลบ backup: $filename");
            $_SESSION['success'] = "ลบ Backup สำเร็จ: $filename";
        } else {
            $_SESSION['error'] = 'ไม่สามารถลบ Backup ได้';
        }
        
        header('Location: /backup');
        exit;
    }
    
    /**
     * Restore from Backup
     */
    public function restore()
    {
        $this->validateCSRF();
        
        $filename = $_POST['filename'] ?? '';
        $filepath = $this->backupDir . '/' . basename($filename);
        
        if (!file_exists($filepath)) {
            $_SESSION['error'] = 'ไม่พบไฟล์ Backup';
            header('Location: /backup');
            exit;
        }
        
        try {
            // Create backup before restore
            $preRestoreBackup = $this->generateBackupFilename('pre-restore');
            $this->backupDatabase($this->backupDir . '/' . $preRestoreBackup);
            
            // Restore
            $result = $this->restoreDatabase($filepath);
            
            if ($result) {
                $this->logService->log('backup_restore', 'system', "Restore จาก: $filename");
                $_SESSION['success'] = "Restore สำเร็จจาก: $filename";
            } else {
                $_SESSION['error'] = 'ไม่สามารถ Restore ได้';
            }
        } catch (\Exception $e) {
            $_SESSION['error'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        }
        
        header('Location: /backup');
        exit;
    }
    
    /**
     * API: ดึง Backup Status
     */
    public function apiStatus()
    {
        header('Content-Type: application/json');
        
        echo json_encode([
            'success' => true,
            'data' => [
                'backup_count' => count($this->getBackupList()),
                'disk_usage' => $this->getDiskUsage(),
                'last_backup' => $this->getLastBackupInfo()
            ]
        ]);
    }
    
    /**
     * Cron: Auto Backup
     */
    public function cronBackup()
    {
        try {
            $filename = $this->generateBackupFilename('auto');
            $filepath = $this->backupDir . '/' . $filename;
            
            $result = $this->backupDatabase($filepath);
            
            if ($result) {
                // Cleanup old auto backups (keep last 14 days)
                $this->cleanupOldBackups(14);
                
                $this->logService->log('backup_auto', 'system', "Auto backup: $filename");
                
                echo json_encode(['success' => true, 'filename' => $filename]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Backup failed']);
            }
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    // ===== Private Methods =====
    
    private function getBackupList(): array
    {
        $files = glob($this->backupDir . '/*.sql*');
        $backups = [];
        
        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'size' => filesize($file),
                'size_human' => $this->formatBytes(filesize($file)),
                'created' => filemtime($file),
                'created_human' => date('d/m/Y H:i:s', filemtime($file)),
                'type' => $this->getBackupType(basename($file))
            ];
        }
        
        // Sort by date descending
        usort($backups, fn($a, $b) => $b['created'] - $a['created']);
        
        return $backups;
    }
    
    private function getBackupType(string $filename): string
    {
        if (strpos($filename, 'auto') !== false) return 'auto';
        if (strpos($filename, 'pre-restore') !== false) return 'pre-restore';
        if (strpos($filename, 'full') !== false) return 'full';
        return 'manual';
    }
    
    private function getDiskUsage(): array
    {
        $totalSize = 0;
        $files = glob($this->backupDir . '/*');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $totalSize += filesize($file);
            }
        }
        
        return [
            'used' => $totalSize,
            'used_human' => $this->formatBytes($totalSize),
            'free' => disk_free_space($this->backupDir),
            'free_human' => $this->formatBytes(disk_free_space($this->backupDir))
        ];
    }
    
    private function getLastBackupInfo(): ?array
    {
        $backups = $this->getBackupList();
        return !empty($backups) ? $backups[0] : null;
    }
    
    private function generateBackupFilename(string $type): string
    {
        return sprintf(
            'drugmuk_%s_%s.sql.gz',
            $type,
            date('Y-m-d_His')
        );
    }
    
    private function backupDatabase(string $filepath): bool
    {
        $db = \App\Core\Database::getInstance()->getConnection();
        
        $host = $_ENV['DB_HOST'] ?? 'db';
        $name = $_ENV['DB_NAME'] ?? 'drugmuk';
        $user = $_ENV['DB_USER'] ?? 'root';
        $pass = $_ENV['DB_PASSWORD'] ?? '';
        
        // Use mysqldump
        $command = sprintf(
            'mysqldump -h %s -u %s %s %s | gzip > %s',
            escapeshellarg($host),
            escapeshellarg($user),
            $pass ? '-p' . escapeshellarg($pass) : '',
            escapeshellarg($name),
            escapeshellarg($filepath)
        );
        
        exec($command, $output, $returnCode);
        
        return $returnCode === 0 && file_exists($filepath);
    }
    
    private function backupFull(string $filepath): bool
    {
        // For now, just backup database
        // TODO: Add file backup
        return $this->backupDatabase($filepath);
    }
    
    private function restoreDatabase(string $filepath): bool
    {
        $host = $_ENV['DB_HOST'] ?? 'db';
        $name = $_ENV['DB_NAME'] ?? 'drugmuk';
        $user = $_ENV['DB_USER'] ?? 'root';
        $pass = $_ENV['DB_PASSWORD'] ?? '';
        
        // Decompress and restore
        $command = sprintf(
            'gunzip -c %s | mysql -h %s -u %s %s %s',
            escapeshellarg($filepath),
            escapeshellarg($host),
            escapeshellarg($user),
            $pass ? '-p' . escapeshellarg($pass) : '',
            escapeshellarg($name)
        );
        
        exec($command, $output, $returnCode);
        
        return $returnCode === 0;
    }
    
    private function cleanupOldBackups(int $daysToKeep): void
    {
        $files = glob($this->backupDir . '/drugmuk_auto_*.sql*');
        $threshold = time() - ($daysToKeep * 86400);
        
        foreach ($files as $file) {
            if (filemtime($file) < $threshold) {
                unlink($file);
            }
        }
    }
    
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    private function validateCSRF(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            http_response_code(403);
            die('Invalid CSRF token');
        }
    }
}
