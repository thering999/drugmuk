<?php

namespace App\Models;

use App\Core\Database;
use PDO;
use PDOException;

/**
 * Data Cleansing Model
 * จัดการการทำความสะอาดข้อมูลและตรวจสอบคุณภาพข้อมูล
 */
class DataCleansing
{
    private $db;
    private $pdo;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    /**
     * ตรวจหายาที่อาจซ้ำกัน
     * 
     * @param int $userId ผู้ตรวจสอบ
     * @param float $threshold คะแนนความคล้ายคลึงขั้นต่ำ (0-100)
     * @return array
     */
    public function detectDuplicateDrugs($userId, $threshold = 75.0)
    {
        try {
            // หายาที่มีชื่อคล้ายกัน
            $sql = "
                SELECT 
                    d1.id as drug1_id,
                    d1.code as drug1_code,
                    d1.name as drug1_name,
                    d2.id as drug2_id,
                    d2.code as drug2_code,
                    d2.name as drug2_name,
                    CASE 
                        WHEN d1.name = d2.name THEN 100
                        WHEN SOUNDEX(d1.name) = SOUNDEX(d2.name) THEN 90
                        WHEN d1.name LIKE CONCAT('%', d2.name, '%') OR d2.name LIKE CONCAT('%', d1.name, '%') THEN 80
                        ELSE 70
                    END as similarity_score
                FROM drugs d1
                INNER JOIN drugs d2 ON d1.id < d2.id
                WHERE (
                    d1.name = d2.name
                    OR SOUNDEX(d1.name) = SOUNDEX(d2.name)
                    OR d1.name LIKE CONCAT('%', d2.name, '%')
                    OR d2.name LIKE CONCAT('%', d1.name, '%')
                    OR d1.code = d2.code
                )
                HAVING similarity_score >= ?
                LIMIT 100
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$threshold]);
            $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // บันทึกผลลัพธ์ลงตาราง duplicate_candidates
            $insertedCount = 0;
            foreach ($duplicates as $dup) {
                $insertSql = "
                    INSERT INTO duplicate_candidates (
                        table_name, record1_id, record2_id, 
                        similarity_score, detected_by, status
                    ) VALUES (?, ?, ?, ?, ?, 'pending')
                    ON DUPLICATE KEY UPDATE 
                        similarity_score = VALUES(similarity_score),
                        detected_at = NOW()
                ";
                
                $insertStmt = $this->pdo->prepare($insertSql);
                $insertStmt->execute([
                    'drugs',
                    $dup['drug1_id'],
                    $dup['drug2_id'],
                    $dup['similarity_score'],
                    $userId
                ]);
                
                if ($insertStmt->rowCount() > 0) {
                    $insertedCount++;
                }
            }
            
            return [
                'success' => true,
                'duplicates_found' => $insertedCount,
                'message' => 'ตรวจพบยาที่อาจซ้ำกัน ' . $insertedCount . ' รายการ'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ตรวจหา transactions ที่ไม่มี parent
     * 
     * @param int $userId ผู้ตรวจสอบ
     * @return array
     */
    public function detectOrphanedTransactions($userId)
    {
        try {
            // หา transactions ที่ไม่มี drug_id ที่ถูกต้อง
            $sql = "
                SELECT t.id, t.drug_id, t.type, t.quantity, t.transaction_date
                FROM transactions t
                LEFT JOIN drugs d ON t.drug_id = d.id
                WHERE d.id IS NULL
                LIMIT 100
            ";
            
            $stmt = $this->pdo->query($sql);
            $orphaned = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // บันทึกผลลัพธ์
            $insertedCount = 0;
            foreach ($orphaned as $record) {
                $insertSql = "
                    INSERT INTO orphaned_records (
                        table_name, record_id, reason, detected_by, status
                    ) VALUES (?, ?, ?, ?, 'pending')
                    ON DUPLICATE KEY UPDATE detected_at = NOW()
                ";
                
                $insertStmt = $this->pdo->prepare($insertSql);
                $insertStmt->execute([
                    'transactions',
                    $record['id'],
                    'ไม่พบ drug_id: ' . $record['drug_id'],
                    $userId
                ]);
                
                if ($insertStmt->rowCount() > 0) {
                    $insertedCount++;
                }
            }
            
            return [
                'success' => true,
                'orphaned_found' => $insertedCount,
                'message' => 'ตรวจพบ transactions ที่ไม่มี parent ' . $insertedCount . ' รายการ'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ตรวจหา order_items ที่ไม่มี parent
     * 
     * @param int $userId ผู้ตรวจสอบ
     * @return array
     */
    public function detectOrphanedOrderItems($userId)
    {
        try {
            // หา order_items ที่ไม่มี order_id ที่ถูกต้อง
            $sql = "
                SELECT oi.id, oi.order_id, oi.drug_id, oi.quantity
                FROM order_items oi
                LEFT JOIN orders o ON oi.order_id = o.id
                WHERE o.id IS NULL
                LIMIT 100
            ";
            
            $stmt = $this->pdo->query($sql);
            $orphaned = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // บันทึกผลลัพธ์
            $insertedCount = 0;
            foreach ($orphaned as $record) {
                $insertSql = "
                    INSERT INTO orphaned_records (
                        table_name, record_id, reason, detected_by, status
                    ) VALUES (?, ?, ?, ?, 'pending')
                    ON DUPLICATE KEY UPDATE detected_at = NOW()
                ";
                
                $insertStmt = $this->pdo->prepare($insertSql);
                $insertStmt->execute([
                    'order_items',
                    $record['id'],
                    'ไม่พบ order_id: ' . $record['order_id'],
                    $userId
                ]);
                
                if ($insertStmt->rowCount() > 0) {
                    $insertedCount++;
                }
            }
            
            return [
                'success' => true,
                'orphaned_found' => $insertedCount,
                'message' => 'ตรวจพบ order items ที่ไม่มี parent ' . $insertedCount . ' รายการ'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ดึงรายการ duplicates ที่รอดำเนินการ
     * 
     * @param string $tableName ชื่อตาราง (ถ้าไม่ระบุจะดึงทั้งหมด)
     * @return array
     */
    public function getPendingDuplicates($tableName = null)
    {
        try {
            // Query โดยตรงจากตาราง และ join กับ drugs เพื่อดึงข้อมูลยา
            $sql = "
                SELECT 
                    dc.*,
                    d1.code as drug1_code,
                    d1.name as drug1_name,
                    d1.unit as drug1_unit,
                    d1.price as drug1_price,
                    d2.code as drug2_code,
                    d2.name as drug2_name,
                    d2.unit as drug2_unit,
                    d2.price as drug2_price,
                    CONCAT(d1.code, ' - ', d1.name) as record1_info,
                    CONCAT(d2.code, ' - ', d2.name) as record2_info
                FROM duplicate_candidates dc
                LEFT JOIN drugs d1 ON dc.record1_id = d1.id AND dc.table_name = 'drugs'
                LEFT JOIN drugs d2 ON dc.record2_id = d2.id AND dc.table_name = 'drugs'
                WHERE dc.status = 'pending'
            ";
            
            if ($tableName) {
                $sql .= " AND dc.table_name = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$tableName]);
            } else {
                $stmt = $this->pdo->query($sql);
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting pending duplicates: " . $e->getMessage());
            return [];
        }
    }

    /**
     * ดึงรายการ orphaned records ที่รอดำเนินการ
     * 
     * @param string $tableName ชื่อตาราง
     * @return array
     */
    public function getPendingOrphanedRecords($tableName = null)
    {
        try {
            $sql = "SELECT * FROM orphaned_records WHERE status = 'pending'";
            if ($tableName) {
                $sql .= " AND table_name = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$tableName]);
            } else {
                $stmt = $this->pdo->query($sql);
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting orphaned records: " . $e->getMessage());
            return [];
        }
    }

    /**
     * รวม (merge) รายการที่ซ้ำกัน
     * 
     * @param int $duplicateId ID ของ duplicate candidate
     * @param int $keepId ID ที่จะเก็บไว้
     * @param int $removeId ID ที่จะลบ
     * @param int $userId ผู้ดำเนินการ
     * @return array
     */
    public function mergeDuplicates($duplicateId, $keepId, $removeId, $userId)
    {
        try {
            $this->pdo->beginTransaction();
            
            // ดึงข้อมูล duplicate candidate
            $stmt = $this->pdo->prepare("
                SELECT * FROM duplicate_candidates 
                WHERE id = ? AND status = 'pending'
            ");
            $stmt->execute([$duplicateId]);
            $duplicate = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$duplicate) {
                throw new \Exception('ไม่พบรายการ duplicate หรือถูกดำเนินการไปแล้ว');
            }
            
            // ตรวจสอบว่า keepId และ removeId ตรงกับ duplicate candidate
            if (!in_array($keepId, [$duplicate['record1_id'], $duplicate['record2_id']]) ||
                !in_array($removeId, [$duplicate['record1_id'], $duplicate['record2_id']])) {
                throw new \Exception('ID ไม่ตรงกับรายการ duplicate');
            }
            
            $tableName = $duplicate['table_name'];
            $recordsAffected = 0;
            
            // ดำเนินการ merge ตามประเภทตาราง
            if ($tableName === 'drugs') {
                $recordsAffected = $this->mergeDrugs($keepId, $removeId);
            }
            
            // อัพเดทสถานะ duplicate candidate
            $stmt = $this->pdo->prepare("
                UPDATE duplicate_candidates 
                SET status = 'merged', 
                    merged_to = ?,
                    resolved_by = ?,
                    resolved_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$keepId, $userId, $duplicateId]);
            
            // บันทึก cleanup history
            $stmt = $this->pdo->prepare("
                INSERT INTO cleanup_history (
                    operation_type,
                    table_name,
                    records_affected,
                    operation_details,
                    performed_by
                ) VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                'merge',
                $tableName,
                $recordsAffected,
                json_encode([
                    'kept_id' => $keepId,
                    'removed_id' => $removeId,
                    'duplicate_id' => $duplicateId
                ]),
                $userId
            ]);
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'message' => 'รวมข้อมูลสำเร็จ',
                'records_affected' => $recordsAffected
            ];
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ];
        }
    }

    /**
     * รวมยาที่ซ้ำกัน
     * 
     * @param int $keepId ID ที่จะเก็บไว้
     * @param int $removeId ID ที่จะลบ
     * @return int จำนวนรายการที่ได้รับผลกระทบ
     */
    private function mergeDrugs($keepId, $removeId)
    {
        $recordsAffected = 0;
        
        // อัพเดท inventory
        $stmt = $this->pdo->prepare("
            UPDATE inventory SET drug_id = ? WHERE drug_id = ?
        ");
        $stmt->execute([$keepId, $removeId]);
        $recordsAffected += $stmt->rowCount();
        
        // อัพเดท inventory_lots
        $stmt = $this->pdo->prepare("
            UPDATE inventory_lots SET drug_id = ? WHERE drug_id = ?
        ");
        $stmt->execute([$keepId, $removeId]);
        $recordsAffected += $stmt->rowCount();
        
        // อัพเดท order_items
        $stmt = $this->pdo->prepare("
            UPDATE order_items SET drug_id = ? WHERE drug_id = ?
        ");
        $stmt->execute([$keepId, $removeId]);
        $recordsAffected += $stmt->rowCount();
        
        // อัพเดท purchasing_plans
        $stmt = $this->pdo->prepare("
            UPDATE purchasing_plans SET drug_id = ? WHERE drug_id = ?
        ");
        $stmt->execute([$keepId, $removeId]);
        $recordsAffected += $stmt->rowCount();
        
        // อัพเดท transactions
        $stmt = $this->pdo->prepare("
            UPDATE transactions SET drug_id = ? WHERE drug_id = ?
        ");
        $stmt->execute([$keepId, $removeId]);
        $recordsAffected += $stmt->rowCount();
        
        // ลบยาที่ซ้ำ
        $stmt = $this->pdo->prepare("DELETE FROM drugs WHERE id = ?");
        $stmt->execute([$removeId]);
        $recordsAffected += $stmt->rowCount();
        
        return $recordsAffected;
    }

    /**
     * ลบ orphaned records
     * 
     * @param array $recordIds รายการ ID ที่จะลบ
     * @param int $userId ผู้ดำเนินการ
     * @return array
     */
    public function deleteOrphanedRecords($recordIds, $userId)
    {
        try {
            $this->pdo->beginTransaction();
            
            $totalDeleted = 0;
            
            foreach ($recordIds as $recordId) {
                // ดึงข้อมูล orphaned record
                $stmt = $this->pdo->prepare("
                    SELECT * FROM orphaned_records 
                    WHERE id = ? AND status = 'pending'
                ");
                $stmt->execute([$recordId]);
                $orphaned = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$orphaned) {
                    continue;
                }
                
                // ลบรายการจริง
                $stmt = $this->pdo->prepare("
                    DELETE FROM {$orphaned['table_name']} WHERE id = ?
                ");
                $stmt->execute([$orphaned['record_id']]);
                $totalDeleted += $stmt->rowCount();
                
                // อัพเดทสถานะ orphaned record
                $stmt = $this->pdo->prepare("
                    UPDATE orphaned_records 
                    SET status = 'deleted',
                        resolved_by = ?,
                        resolved_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$userId, $recordId]);
            }
            
            // บันทึก cleanup history
            $stmt = $this->pdo->prepare("
                INSERT INTO cleanup_history (
                    operation_type,
                    table_name,
                    records_affected,
                    operation_details,
                    performed_by
                ) VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                'delete',
                'orphaned_records',
                $totalDeleted,
                json_encode(['record_ids' => $recordIds]),
                $userId
            ]);
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'message' => 'ลบข้อมูลสำเร็จ ' . $totalDeleted . ' รายการ',
                'deleted_count' => $totalDeleted
            ];
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ];
        }
    }

    /**
     * คำนวณคะแนนคุณภาพข้อมูล
     * 
     * @return array
     */
    public function getDataQualityScore()
    {
        try {
            // คำนวณคะแนนโดยตรงแทนการใช้ stored procedure
            $stmt = $this->pdo->query("
                SELECT 
                    'drugs' as table_name,
                    COUNT(*) as total_records,
                    (SELECT COUNT(DISTINCT record1_id) 
                     FROM duplicate_candidates 
                     WHERE table_name = 'drugs' AND status = 'pending') as records_with_issues,
                    ROUND(
                        ((COUNT(*) - (SELECT COUNT(DISTINCT record1_id) 
                                      FROM duplicate_candidates 
                                      WHERE table_name = 'drugs' AND status = 'pending')) 
                         / COUNT(*)) * 100, 2
                    ) as quality_score,
                    CASE 
                        WHEN ROUND(((COUNT(*) - (SELECT COUNT(DISTINCT record1_id) 
                                                  FROM duplicate_candidates 
                                                  WHERE table_name = 'drugs' AND status = 'pending')) 
                                    / COUNT(*)) * 100, 2) >= 95 THEN 'excellent'
                        WHEN ROUND(((COUNT(*) - (SELECT COUNT(DISTINCT record1_id) 
                                                  FROM duplicate_candidates 
                                                  WHERE table_name = 'drugs' AND status = 'pending')) 
                                    / COUNT(*)) * 100, 2) >= 85 THEN 'good'
                        WHEN ROUND(((COUNT(*) - (SELECT COUNT(DISTINCT record1_id) 
                                                  FROM duplicate_candidates 
                                                  WHERE table_name = 'drugs' AND status = 'pending')) 
                                    / COUNT(*)) * 100, 2) >= 70 THEN 'fair'
                        ELSE 'poor'
                    END as quality_rating
                FROM drugs
            ");
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error calculating data quality score: " . $e->getMessage());
            return [];
        }
    }

    /**
     * ดึงสรุปคุณภาพข้อมูล
     * 
     * @return array
     */
    public function getDataQualitySummary()
    {
        try {
            // Query โดยตรงแทนการใช้ view
            $stmt = $this->pdo->query("
                SELECT 
                    'duplicate_candidates' as metric_name,
                    COUNT(*) as metric_value,
                    'รายการที่อาจซ้ำกัน' as metric_description
                FROM duplicate_candidates
                WHERE status = 'pending'
                UNION ALL
                SELECT 
                    'orphaned_records' as metric_name,
                    COUNT(*) as metric_value,
                    'รายการที่ไม่มี parent' as metric_description
                FROM orphaned_records
                WHERE status = 'pending'
                UNION ALL
                SELECT 
                    'cleanup_operations' as metric_name,
                    COUNT(*) as metric_value,
                    'การทำความสะอาดทั้งหมด' as metric_description
                FROM cleanup_history
                WHERE DATE(performed_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting data quality summary: " . $e->getMessage());
            return [];
        }
    }

    /**
     * ดึงประวัติการทำความสะอาด
     * 
     * @param int $limit จำนวนรายการ
     * @return array
     */
    public function getCleanupHistory($limit = 50)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    ch.*,
                    u.username as performed_by_name
                FROM cleanup_history ch
                LEFT JOIN users u ON ch.performed_by = u.id
                ORDER BY ch.performed_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting cleanup history: " . $e->getMessage());
            return [];
        }
    }

    /**
     * ทำการตรวจสอบคุณภาพข้อมูลทั้งหมด
     * 
     * @param int $userId ผู้ตรวจสอบ
     * @return array
     */
    public function runFullDataQualityCheck($userId)
    {
        $results = [];
        
        // ตรวจหายาซ้ำ
        $results['duplicate_drugs'] = $this->detectDuplicateDrugs($userId);
        
        // ตรวจหา orphaned transactions
        $results['orphaned_transactions'] = $this->detectOrphanedTransactions($userId);
        
        // ตรวจหา orphaned order items
        $results['orphaned_order_items'] = $this->detectOrphanedOrderItems($userId);
        
        // คำนวณคะแนนคุณภาพ
        $results['quality_scores'] = $this->getDataQualityScore();
        
        return $results;
    }

    /**
     * ทำเครื่องหมาย duplicate เป็น false positive
     * 
     * @param int $duplicateId ID ของ duplicate candidate
     * @param int $userId ผู้ดำเนินการ
     * @return array
     */
    public function markAsFalsePositive($duplicateId, $userId)
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE duplicate_candidates 
                SET status = 'false_positive',
                    resolved_by = ?,
                    resolved_at = NOW()
                WHERE id = ? AND status = 'pending'
            ");
            $stmt->execute([$userId, $duplicateId]);
            
            if ($stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'ทำเครื่องหมายสำเร็จ'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'ไม่พบรายการหรือถูกดำเนินการไปแล้ว'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ดึงข้อมูล Quality Trends ย้อนหลัง
     * 
     * @param int $days จำนวนวันย้อนหลัง
     * @return array
     */
    public function getQualityTrends($days = 30)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    DATE(created_at) as check_date,
                    COUNT(*) as total_checks,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_count
                FROM cleanup_history
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                ORDER BY check_date DESC
            ");
            $stmt->execute([$days]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting quality trends: " . $e->getMessage());
            return [];
        }
    }
}

