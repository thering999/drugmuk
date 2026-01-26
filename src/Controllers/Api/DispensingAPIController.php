<?php

namespace App\Controllers\Api;

use App\Core\Database;

class DispensingAPIController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get dispensing statistics with period filtering
     * GET /api/dispensing/statistics?period=6months|12months|all
     */
    public function getStatistics()
    {
        header('Content-Type: application/json');

        try {
            $period = $_GET['period'] ?? '6months';

            // Calculate date range based on period
            $dateCondition = $this->getDateCondition($period);

            // Get monthly trend data
            $monthlyTrend = $this->getMonthlyTrend($dateCondition);

            // Get daily activity (last 7 days)
            $dailyActivity = $this->getDailyActivity();

            // Get summary statistics
            $stats = $this->getSummaryStats($dateCondition);

            echo json_encode([
                'success' => true,
                'data' => [
                    'monthlyTrend' => $monthlyTrend,
                    'dailyActivity' => $dailyActivity,
                    'stats' => $stats
                ]
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get date condition SQL based on period
     */
    private function getDateCondition($period)
    {
        switch ($period) {
            case '6months':
                return "dispense_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
            case '12months':
                return "dispense_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)";
            case 'all':
                return "1=1"; // No date restriction
            default:
                return "dispense_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
        }
    }

    /**
     * Get monthly trend data
     */
    private function getMonthlyTrend($dateCondition)
    {
        $sql = "
            SELECT 
                DATE_FORMAT(dispense_date, '%Y-%m') as month,
                DATE_FORMAT(dispense_date, '%b %Y') as month_label,
                COUNT(DISTINCT id) as count,
                COUNT(DISTINCT hn) as patient_count,
                SUM((SELECT COUNT(*) FROM dispensing_items WHERE dispense_id = dispensing.id)) as item_count
            FROM dispensing
            WHERE {$dateCondition}
            GROUP BY DATE_FORMAT(dispense_date, '%Y-%m')
            ORDER BY month ASC
        ";

        $stmt = $this->db->query($sql);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Format for Chart.js
        return array_map(function($row) {
            return [
                'month' => $row['month_label'],
                'count' => (int)$row['count'],
                'patient_count' => (int)$row['patient_count'],
                'item_count' => (int)$row['item_count']
            ];
        }, $results);
    }

    /**
     * Get daily activity for last 7 days
     */
    private function getDailyActivity()
    {
        $sql = "
            SELECT 
                DATE(dispense_date) as date,
                DATE_FORMAT(dispense_date, '%d/%m') as date_label,
                COUNT(DISTINCT id) as count
            FROM dispensing
            WHERE dispense_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(dispense_date)
            ORDER BY date ASC
        ";

        $stmt = $this->db->query($sql);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_map(function($row) {
            return [
                'date' => $row['date_label'],
                'count' => (int)$row['count']
            ];
        }, $results);
    }

    /**
     * Get summary statistics
     */
    private function getSummaryStats($dateCondition)
    {
        $sql = "
            SELECT 
                COUNT(DISTINCT d.id) as total_dispensing,
                COUNT(DISTINCT d.hn) as total_patients,
                COUNT(di.id) as total_items,
                ROUND(COUNT(di.id) / NULLIF(COUNT(DISTINCT d.id), 0), 1) as avg_items_per_dispensing
            FROM dispensing d
            LEFT JOIN dispensing_items di ON d.id = di.dispense_id
            WHERE {$dateCondition}
        ";

        $stmt = $this->db->query($sql);
        $stats = $stmt->fetch(\PDO::FETCH_ASSOC);

        return [
            'total_dispensing' => (int)($stats['total_dispensing'] ?? 0),
            'total_patients' => (int)($stats['total_patients'] ?? 0),
            'total_items' => (int)($stats['total_items'] ?? 0),
            'avg_items_per_dispensing' => (float)($stats['avg_items_per_dispensing'] ?? 0)
        ];
    }

    /**
     * Get top dispensed drugs
     * GET /api/dispensing/top-drugs?period=6months&limit=20
     */
    public function getTopDrugs()
    {
        header('Content-Type: application/json');

        try {
            $period = $_GET['period'] ?? '6months';
            $limit = (int)($_GET['limit'] ?? 20);

            $dateCondition = $this->getDateCondition($period);

            $sql = "
                SELECT 
                    d.code as drug_code,
                    d.name as drug_name,
                    d.generic_name,
                    d.unit,
                    COUNT(DISTINCT di.dispense_id) as dispense_count,
                    SUM(di.quantity) as total_quantity
                FROM dispensing_items di
                INNER JOIN drugs d ON di.drug_id = d.id
                INNER JOIN dispensing disp ON di.dispense_id = disp.id
                WHERE {$dateCondition}
                GROUP BY di.drug_id
                ORDER BY dispense_count DESC
                LIMIT {$limit}
            ";

            $stmt = $this->db->query($sql);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $results
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }
}
