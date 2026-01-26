<?php

namespace App\Services;

use App\Services\LoggerService;

/**
 * Backup Service
 * 
 * Handles database and file backups
 */
class BackupService
{
    private $logger;
    private $db;
    private $backupDir;
    
    public function __construct()
    {
        $this->logger = new LoggerService();
        $this->db = \App\Core\Database::getInstance()->getConnection();
        $this->backupDir = __DIR__ . '/../../storage/backups';
        
        // Create backup directory if not exists
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }
    
    /**
     * Create full backup (database + files)
     */
    public function createFullBackup(string $description = ''): array
    {
        $timestamp = date('YmdHis');
        $backupName = "backup_{$timestamp}";
        
        try {
            // 1. Backup database
            $dbFile = $this->backupDatabase($backupName);
            
            // 2. Backup files
            $filesArchive = $this->backupFiles($backupName);
            
            // 3. Create manifest
            $manifest = $this->createManifest($backupName, $dbFile, $filesArchive, $description);
            
            // 4. Log backup
            $this->logBackup($backupName, $manifest);
            
            $this->logger->info('Backup created successfully', ['backup' => $backupName]);
            
            return [
                'success' => true,
                'backup_name' => $backupName,
                'manifest' => $manifest
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Backup failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Backup database to SQL file
     */
    private function backupDatabase(string $backupName): string
    {
        $filename = "{$this->backupDir}/{$backupName}_database.sql";
        
        $dbHost = getenv('DB_HOST') ?: 'localhost';
        $dbName = getenv('DB_NAME') ?: 'drugmuk';
        $dbUser = getenv('DB_USER') ?: 'root';
        $dbPass = getenv('DB_PASS') ?: '';
        
        // Use mysqldump
        $command = sprintf(
            'mysqldump -h%s -u%s -p%s %s > %s 2>&1',
            escapeshellarg($dbHost),
            escapeshellarg($dbUser),
            escapeshellarg($dbPass),
            escapeshellarg($dbName),
            escapeshellarg($filename)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new \Exception("Database backup failed: " . implode("\n", $output));
        }
        
        // Compress
        $gzFilename = $filename . '.gz';
        $this->compressFile($filename, $gzFilename);
        unlink($filename);
        
        return basename($gzFilename);
    }
    
    /**
     * Backup important files
     */
    private function backupFiles(string $backupName): string
    {
        $filename = "{$this->backupDir}/{$backupName}_files.tar.gz";
        
        $sourceDirs = [
            __DIR__ . '/../../public/uploads',
            __DIR__ . '/../../config',
            __DIR__ . '/../../.env'
        ];
        
        $tarCommand = sprintf(
            'tar -czf %s %s 2>&1',
            escapeshellarg($filename),
            implode(' ', array_map('escapeshellarg', $sourceDirs))
        );
        
        exec($tarCommand, $output, $returnCode);
        
        if ($returnCode !== 0) {
            $this->logger->warning('File backup had warnings', ['output' => $output]);
        }
        
        return basename($filename);
    }
    
    /**
     * Create backup manifest
     */
    private function createManifest(string $backupName, string $dbFile, string $filesArchive, string $description): array
    {
        $manifest = [
            'backup_name' => $backupName,
            'created_at' => date('Y-m-d H:i:s'),
            'description' => $description,
            'database_file' => $dbFile,
            'files_archive' => $filesArchive,
            'database_size' => filesize("{$this->backupDir}/{$dbFile}"),
            'files_size' => filesize("{$this->backupDir}/{$filesArchive}"),
            'total_size' => filesize("{$this->backupDir}/{$dbFile}") + filesize("{$this->backupDir}/{$filesArchive}"),
            'php_version' => PHP_VERSION,
            'mysql_version' => $this->db->query('SELECT VERSION()')->fetchColumn()
        ];
        
        // Save manifest
        file_put_contents(
            "{$this->backupDir}/{$backupName}_manifest.json",
            json_encode($manifest, JSON_PRETTY_PRINT)
        );
        
        return $manifest;
    }
    
    /**
     * Log backup to database
     */
    private function logBackup(string $backupName, array $manifest): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO backup_logs (backup_name, description, database_size, files_size, total_size, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())"
        );
        
        $stmt->execute([
            $backupName,
            $manifest['description'],
            $manifest['database_size'],
            $manifest['files_size'],
            $manifest['total_size']
        ]);
    }
    
    /**
     * Get backup history
     */
    public function getBackupHistory(int $limit = 20): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM backup_logs ORDER BY created_at DESC LIMIT ?"
        );
        $stmt->execute([$limit]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Delete old backups
     */
    public function deleteOldBackups(int $daysToKeep = 30): int
    {
        $deleted = 0;
        $cutoffDate = date('Y-m-d', strtotime("-{$daysToKeep} days"));
        
        $stmt = $this->db->prepare(
            "SELECT backup_name FROM backup_logs WHERE created_at < ?"
        );
        $stmt->execute([$cutoffDate]);
        $oldBackups = $stmt->fetchAll();
        
        foreach ($oldBackups as $backup) {
            $this->deleteBackup($backup['backup_name']);
            $deleted++;
        }
        
        return $deleted;
    }
    
    /**
     * Delete specific backup
     */
    public function deleteBackup(string $backupName): bool
    {
        try {
            // Delete files
            $files = glob("{$this->backupDir}/{$backupName}*");
            foreach ($files as $file) {
                unlink($file);
            }
            
            // Delete from database
            $stmt = $this->db->prepare("DELETE FROM backup_logs WHERE backup_name = ?");
            $stmt->execute([$backupName]);
            
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete backup', [
                'backup' => $backupName,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Compress file with gzip
     */
    private function compressFile(string $source, string $destination): void
    {
        $fp = gzopen($destination, 'w9');
        $content = file_get_contents($source);
        gzwrite($fp, $content);
        gzclose($fp);
    }
    
    /**
     * Get backup file path
     */
    public function getBackupPath(string $backupName, string $type = 'database'): ?string
    {
        $pattern = "{$this->backupDir}/{$backupName}_{$type}.*";
        $files = glob($pattern);
        
        return $files[0] ?? null;
    }
}
