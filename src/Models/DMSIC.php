<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class DMSIC
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get export history
     */
    public function getExportHistory($limit = 50)
    {
        $sql = "SELECT * FROM dmsic_exports 
                ORDER BY export_date DESC 
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create export record
     */
    public function createExport($data)
    {
        $sql = "INSERT INTO dmsic_exports 
                (export_date, file_name, file_path, record_count, status, exported_by)
                VALUES (:export_date, :file_name, :file_path, :record_count, :status, :exported_by)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return $this->db->lastInsertId();
    }

    /**
     * Update export status
     */
    public function updateExportStatus($id, $status, $message = null)
    {
        $sql = "UPDATE dmsic_exports 
                SET status = :status, error_message = :message
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'status' => $status,
            'message' => $message
        ]);
    }

    /**
     * Get configuration
     */
    public function getConfig()
    {
        $sql = "SELECT * FROM dmsic_config LIMIT 1";
        $stmt = $this->db->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update configuration
     */
    public function updateConfig($data)
    {
        // Check if config exists
        $existing = $this->getConfig();
        
        if ($existing) {
            $sql = "UPDATE dmsic_config 
                    SET hospcode = :hospcode,
                        hospname = :hospname,
                        api_url = :api_url,
                        api_key = :api_key,
                        auto_send = :auto_send,
                        send_schedule = :send_schedule
                    WHERE id = :id";
            $data['id'] = $existing['id'];
        } else {
            $sql = "INSERT INTO dmsic_config 
                    (hospcode, hospname, api_url, api_key, auto_send, send_schedule)
                    VALUES (:hospcode, :hospname, :api_url, :api_key, :auto_send, :send_schedule)";
        }
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Gather data for export
     */
    public function gatherExportData($startDate, $endDate)
    {
        // Get dispensing data
        $sql = "SELECT 
                    d.id,
                    d.dispense_date,
                    d.hn,
                    d.patient_name,
                    dr.code as drug_code,
                    dr.name as drug_name,
                    di.quantity,
                    COALESCE(dr.price, 0) as unit_price,
                    '' as dispenser
                FROM dispensing d
                JOIN dispensing_items di ON d.id = di.dispense_id
                JOIN drugs dr ON di.drug_id = dr.id
                WHERE d.dispense_date BETWEEN :start_date AND :end_date
                ORDER BY d.dispense_date, d.id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Validate export data
     */
    public function validateData($data)
    {
        $errors = [];
        
        foreach ($data as $index => $row) {
            $rowErrors = [];
            
            // Check required fields
            if (empty($row['drug_code'])) {
                $rowErrors[] = "Missing drug code";
            }
            if (empty($row['quantity']) || $row['quantity'] <= 0) {
                $rowErrors[] = "Invalid quantity";
            }
            if (empty($row['dispense_date'])) {
                $rowErrors[] = "Missing dispense date";
            }
            
            if (!empty($rowErrors)) {
                $errors[$index] = $rowErrors;
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'total_records' => count($data),
            'valid_records' => count($data) - count($errors)
        ];
    }

    /**
     * Format data as DMSIC standard
     */
    public function formatAsDMSIC($data, $config)
    {
        $output = [];
        $output[] = "HOSPCODE|DRUGCODE|DRUGNAME|QTY|PRICE|DISPENSEDATE|HN|DISPENSER";
        
        foreach ($data as $row) {
            $line = implode('|', [
                $config['hospcode'] ?? '00000',
                $row['drug_code'],
                $row['drug_name'],
                $row['quantity'],
                $row['unit_price'],
                date('Ymd', strtotime($row['dispense_date'])),
                $row['hn'] ?? '',
                $row['dispenser'] ?? ''
            ]);
            $output[] = $line;
        }
        
        return implode("\n", $output);
    }

    /**
     * Export to file
     */
    public function exportToFile($content, $fileName)
    {
        $exportDir = __DIR__ . '/../../exports/dmsic';
        
        // Create directory if not exists
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }
        
        $filePath = $exportDir . '/' . $fileName;
        file_put_contents($filePath, $content);
        
        return $filePath;
    }

    /**
     * Send to DMSIC API
     */
    public function sendToAPI($filePath, $config)
    {
        // Mock API call - replace with actual implementation
        if (empty($config['api_url'])) {
            throw new \Exception('API URL not configured');
        }
        
        // Simulate API call
        sleep(1);
        
        return [
            'success' => true,
            'transaction_id' => 'TRX' . time() . rand(100, 999),
            'message' => 'Data sent successfully',
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Get statistics
     */
    public function getStatistics()
    {
        $sql = "SELECT 
                    COUNT(*) as total_exports,
                    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful_exports,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_exports,
                    SUM(record_count) as total_records,
                    MAX(export_date) as last_export_date
                FROM dmsic_exports";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
