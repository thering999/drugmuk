<?php

namespace App\Controllers;

use App\Models\DataCleansing;
use App\Models\AuditTrail;

/**
 * Export Controller
 * จัดการการส่งออกรายงาน PDF/Excel
 */
class ExportController
{
    private $cleansingModel;
    private $auditModel;

    public function __construct()
    {
        $this->cleansingModel = new DataCleansing();
        $this->auditModel = new AuditTrail();
    }

    /**
     * Export รายงานคุณภาพข้อมูล
     */
    public function qualityReport()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $format = $_GET['format'] ?? 'csv';
        $qualitySummary = $this->cleansingModel->getDataQualitySummary();
        $qualityTrends = $this->cleansingModel->getQualityTrends(30);

        if ($format === 'csv') {
            $this->exportCSV($qualitySummary, $qualityTrends);
        } else {
            $this->exportHTML($qualitySummary, $qualityTrends);
        }
    }

    /**
     * Export เป็น CSV
     */
    private function exportCSV($summary, $trends)
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="quality_report_' . date('Ymd_His') . '.csv"');
        
        // BOM for UTF-8
        echo "\xEF\xBB\xBF";
        
        $output = fopen('php://output', 'w');
        
        // Summary section
        fputcsv($output, ['รายงานคุณภาพข้อมูล - Drugmuk']);
        fputcsv($output, ['วันที่', date('d/m/Y H:i:s')]);
        fputcsv($output, []);
        
        fputcsv($output, ['สรุปคุณภาพข้อมูล']);
        fputcsv($output, ['ตัวชี้วัด', 'ค่า', 'คำอธิบาย']);
        
        foreach ($summary as $item) {
            fputcsv($output, [
                $item['metric_name'] ?? '',
                $item['metric_value'] ?? 0,
                $item['metric_description'] ?? ''
            ]);
        }
        
        fputcsv($output, []);
        fputcsv($output, ['แนวโน้ม 30 วัน']);
        fputcsv($output, ['วันที่', 'จำนวนตรวจสอบ', 'แก้ไขแล้ว']);
        
        foreach ($trends as $trend) {
            fputcsv($output, [
                $trend['check_date'],
                $trend['total_checks'],
                $trend['resolved_count']
            ]);
        }
        
        fclose($output);
        exit;
    }

    /**
     * Export เป็น HTML (สำหรับพิมพ์)
     */
    private function exportHTML($summary, $trends)
    {
        header('Content-Type: text/html; charset=utf-8');
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>รายงานคุณภาพข้อมูล - Drugmuk</title>
            <style>
                body { font-family: 'Sarabun', sans-serif; padding: 40px; }
                h1 { color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
                .meta { color: #666; margin-bottom: 30px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
                th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
                th { background: #667eea; color: white; }
                tr:nth-child(even) { background: #f9fafb; }
                .score { font-size: 48px; font-weight: bold; color: #10b981; text-align: center; margin: 30px 0; }
                @media print {
                    body { padding: 20px; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <h1>📊 รายงานคุณภาพข้อมูล</h1>
            <div class="meta">
                <p>วันที่พิมพ์: <?php echo date('d/m/Y H:i:s'); ?></p>
                <p>ระบบ: Drugmuk - Pharmaceutical Inventory Management</p>
            </div>

            <?php 
            $score = 0;
            foreach ($summary as $item) {
                if (isset($item['quality_score'])) {
                    $score = $item['quality_score'];
                    break;
                }
            }
            ?>
            <div class="score"><?php echo number_format($score, 1); ?>%</div>
            <p style="text-align: center; color: #666;">คะแนนคุณภาพโดยรวม</p>

            <h2>สรุปคุณภาพข้อมูล</h2>
            <table>
                <tr>
                    <th>ตัวชี้วัด</th>
                    <th>ค่า</th>
                    <th>คำอธิบาย</th>
                </tr>
                <?php foreach ($summary as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['metric_name'] ?? ''); ?></td>
                    <td><?php echo number_format($item['metric_value'] ?? 0); ?></td>
                    <td><?php echo htmlspecialchars($item['metric_description'] ?? ''); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>

            <h2>แนวโน้ม 30 วัน</h2>
            <table>
                <tr>
                    <th>วันที่</th>
                    <th>จำนวนตรวจสอบ</th>
                    <th>แก้ไขแล้ว</th>
                </tr>
                <?php foreach ($trends as $trend): ?>
                <tr>
                    <td><?php echo date('d/m/Y', strtotime($trend['check_date'])); ?></td>
                    <td><?php echo number_format($trend['total_checks']); ?></td>
                    <td><?php echo number_format($trend['resolved_count']); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>

            <div class="no-print" style="text-align: center; margin-top: 30px;">
                <button onclick="window.print()" style="padding: 12px 30px; background: #667eea; color: white; border: none; border-radius: 8px; font-size: 16px; cursor: pointer;">
                    🖨️ พิมพ์รายงาน
                </button>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    /**
     * Export Audit Trail
     */
    public function auditTrail()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $filters = [
            'table_name' => $_GET['table'] ?? null,
            'action' => $_GET['action'] ?? null,
            'date_from' => $_GET['from'] ?? null,
            'date_to' => $_GET['to'] ?? null
        ];

        $records = $this->auditModel->getAll($filters, 1000, 0);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="audit_trail_' . date('Ymd_His') . '.csv"');
        
        echo "\xEF\xBB\xBF";
        $output = fopen('php://output', 'w');
        
        fputcsv($output, ['Audit Trail - Drugmuk']);
        fputcsv($output, ['Exported:', date('d/m/Y H:i:s')]);
        fputcsv($output, []);
        
        fputcsv($output, ['ID', 'วันเวลา', 'ตาราง', 'Record ID', 'การกระทำ', 'ผู้ดำเนินการ', 'ฟิลด์ที่เปลี่ยน', 'IP Address']);
        
        foreach ($records as $rec) {
            fputcsv($output, [
                $rec['id'],
                $rec['created_at'],
                $rec['table_name'],
                $rec['record_id'],
                $rec['action'],
                $rec['user_name'] ?? 'System',
                $rec['changed_fields'] ?? '',
                $rec['ip_address'] ?? ''
            ]);
        }
        
        fclose($output);
        exit;
    }

    /**
     * Export รายการยา
     */
    public function drugs()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->query("
            SELECT d.id, d.code, d.name, d.generic_name, d.unit, d.price, d.min_stock,
                   COALESCE(SUM(i.quantity), 0) as current_stock
            FROM drugs d
            LEFT JOIN inventory i ON d.id = i.drug_id
            GROUP BY d.id
            ORDER BY d.name
        ");
        $drugs = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="drugs_' . date('Ymd_His') . '.csv"');
        
        echo "\xEF\xBB\xBF";
        $output = fopen('php://output', 'w');
        
        fputcsv($output, ['รายการยา - Drugmuk', date('d/m/Y H:i:s')]);
        fputcsv($output, []);
        fputcsv($output, ['รหัส', 'ชื่อยา', 'ชื่อสามัญ', 'หน่วย', 'ราคา', 'สต็อกขั้นต่ำ', 'สต็อกปัจจุบัน']);
        
        foreach ($drugs as $drug) {
            fputcsv($output, [
                $drug['code'],
                $drug['name'],
                $drug['generic_name'],
                $drug['unit'],
                $drug['price'],
                $drug['min_stock'],
                $drug['current_stock']
            ]);
        }
        
        fclose($output);
        exit;
    }
}
