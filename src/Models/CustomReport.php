<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class CustomReport
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get all reports
     */
    public function getAll()
    {
        $sql = "SELECT * FROM custom_reports ORDER BY created_at DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get report by ID
     */
    public function getById($id)
    {
        $sql = "SELECT * FROM custom_reports WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create report
     */
    public function create($data)
    {
        $sql = "INSERT INTO custom_reports 
                (name, description, query, columns, filters, created_by)
                VALUES (:name, :description, :query, :columns, :filters, :created_by)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return $this->db->lastInsertId();
    }

    /**
     * Update report
     */
    public function update($id, $data)
    {
        $sql = "UPDATE custom_reports 
                SET name = :name,
                    description = :description,
                    query = :query,
                    columns = :columns,
                    filters = :filters
                WHERE id = :id";
        $data['id'] = $id;
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Delete report
     */
    public function delete($id)
    {
        $sql = "DELETE FROM custom_reports WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Execute report query
     */
    public function executeReport($id, $params = [])
    {
        $report = $this->getById($id);
        
        if (!$report) {
            throw new \Exception('Report not found');
        }

        $query = $report['query'];
        
        // Replace parameters
        foreach ($params as $key => $value) {
            $query = str_replace(':' . $key, $this->db->quote($value), $query);
        }

        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get predefined reports
     */
    public function getPredefinedReports()
    {
        return [
            [
                'id' => 'inventory_summary',
                'name' => 'สรุปสต็อกคงเหลือ',
                'description' => 'รายงานสต็อกยาคงเหลือทั้งหมด',
                'query' => 'SELECT d.code, d.name, COALESCE(SUM(i.quantity), 0) as total_qty, d.min_stock 
                           FROM drugs d 
                           LEFT JOIN inventory i ON d.id = i.drug_id 
                           GROUP BY d.id, d.code, d.name, d.min_stock 
                           ORDER BY d.name'
            ],
            [
                'id' => 'expiring_drugs',
                'name' => 'ยาใกล้หมดอายุ',
                'description' => 'รายงานยาที่จะหมดอายุภายใน 90 วัน',
                'query' => 'SELECT d.code, d.name, i.lot_no, i.expire_date, i.quantity 
                           FROM inventory i 
                           JOIN drugs d ON i.drug_id = d.id 
                           WHERE i.expire_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) 
                           ORDER BY i.expire_date'
            ],
            [
                'id' => 'dispensing_summary',
                'name' => 'สรุปการจ่ายยา',
                'description' => 'รายงานสรุปการจ่ายยารายเดือน',
                'query' => 'SELECT DATE_FORMAT(d.dispense_date, "%Y-%m") as month, 
                           COUNT(DISTINCT d.id) as total_dispenses, 
                           COALESCE(SUM(di.quantity), 0) as total_quantity 
                           FROM dispensing d 
                           LEFT JOIN dispensing_items di ON d.id = di.dispense_id 
                           GROUP BY month 
                           ORDER BY month DESC'
            ],
            [
                'id' => 'top_drugs',
                'name' => 'ยาที่จ่ายบ่อยที่สุด',
                'description' => 'รายงาน Top 20 ยาที่จ่ายบ่อยที่สุด',
                'query' => 'SELECT d.code, d.name, COALESCE(SUM(di.quantity), 0) as total_qty, COUNT(di.id) as dispense_count 
                           FROM drugs d
                           LEFT JOIN dispensing_items di ON di.drug_id = d.id 
                           GROUP BY d.id, d.code, d.name 
                           ORDER BY total_qty DESC 
                           LIMIT 20'
            ],
            [
                'id' => 'low_stock',
                'name' => 'ยาสต็อกต่ำกว่าขั้นต่ำ',
                'description' => 'รายงานยาที่สต็อกต่ำกว่าที่กำหนด',
                'query' => 'SELECT d.code, d.name, COALESCE(SUM(i.quantity), 0) as current_stock, d.min_stock 
                           FROM drugs d 
                           LEFT JOIN inventory i ON d.id = i.drug_id 
                           GROUP BY d.id, d.code, d.name, d.min_stock 
                           HAVING current_stock < d.min_stock 
                           ORDER BY d.name'
            ]
        ];
    }

    /**
     * Export to CSV
     */
    public function exportToCSV($data, $filename)
    {
        $output = fopen('php://temp', 'r+');
        
        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Add headers
        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]));
        }
        
        // Add data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }

    /**
     * Export to Excel (using PHPSpreadsheet would be better, but this is simplified)
     */
    public function exportToExcel($data, $filename)
    {
        // For now, just use CSV with .xls extension
        // In production, use PHPSpreadsheet library
        return $this->exportToCSV($data, $filename);
    }
}
