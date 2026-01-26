<?php

namespace App\Services;

use PDO;

/**
 * Chronic Disease Service
 * 
 * จัดการผู้ป่วยโรคเรื้อรัง, ติดตามการรับยา, และแจ้งเตือน
 */
class ChronicDiseaseService
{
    private $db;
    private $patientService;
    
    public function __construct()
    {
        $this->db = $this->getConnection();
        $this->patientService = new PatientService();
    }
    
    /**
     * Get database connection
     */
    private function getConnection()
    {
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $dbname = $_ENV['DB_NAME'] ?? 'drugmuk';
        $username = $_ENV['DB_USER'] ?? 'root';
        $password = $_ENV['DB_PASSWORD'] ?? '';
        
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        return new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    
    /**
     * Get all chronic patients
     * 
     * @param array $filters Filters (disease, status, etc.)
     * @param int $limit Limit results
     * @return array List of chronic patients
     */
    public function getChronicPatients($filters = [], $limit = 100)
    {
        try {
            $sql = "SELECT DISTINCT
                        pcd.hn,
                        ppc.full_name,
                        ppc.age,
                        ppc.sex,
                        ppc.phone,
                        COUNT(DISTINCT pcd.disease_code) as disease_count,
                        GROUP_CONCAT(DISTINCT pcd.disease_name SEPARATOR ', ') as diseases,
                        MAX(pcd.diagnosed_date) as latest_diagnosis,
                        pvs.last_visit_date,
                        pvs.total_visits
                    FROM patient_chronic_diseases pcd
                    INNER JOIN patient_profile_cache ppc ON pcd.hn = ppc.hn
                    LEFT JOIN patient_visit_summary pvs ON pcd.hn = pvs.hn
                    WHERE pcd.status = 'active'";
            
            $params = [];
            
            // Apply filters
            if (!empty($filters['disease'])) {
                $sql .= " AND pcd.disease_code = ?";
                $params[] = $filters['disease'];
            }
            
            if (!empty($filters['search'])) {
                $sql .= " AND (pcd.hn LIKE ? OR ppc.full_name LIKE ?)";
                $searchTerm = "%{$filters['search']}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $sql .= " GROUP BY pcd.hn
                     ORDER BY pcd.hn
                     LIMIT ?";
            
            $params[] = $limit;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
            
        } catch (\PDOException $e) {
            error_log("Error getting chronic patients: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get patient refill schedule
     * 
     * @param string $hn Hospital Number
     * @return array Refill schedule
     */
    public function getRefillSchedule($hn)
    {
        try {
            $sql = "SELECT 
                        cpr.*,
                        d.name as drug_full_name,
                        DATEDIFF(cpr.next_refill_date, CURDATE()) as days_until_refill,
                        CASE 
                            WHEN cpr.next_refill_date < CURDATE() THEN 'overdue'
                            WHEN DATEDIFF(cpr.next_refill_date, CURDATE()) <= 7 THEN 'due_soon'
                            ELSE 'on_time'
                        END as refill_status_calculated
                    FROM chronic_patient_refills cpr
                    LEFT JOIN drugs d ON cpr.drug_id = d.id
                    WHERE cpr.hn = ?
                    ORDER BY cpr.next_refill_date";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$hn]);
            
            return $stmt->fetchAll();
            
        } catch (\PDOException $e) {
            error_log("Error getting refill schedule: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Calculate next refill date
     * 
     * @param string $hn Hospital Number
     * @param int $drugId Drug ID
     * @param int $daysSupply Days supply
     * @return string Next refill date
     */
    public function calculateNextRefillDate($hn, $drugId, $daysSupply)
    {
        $lastRefillDate = date('Y-m-d');
        
        // Try to get last refill date from database
        try {
            $sql = "SELECT last_refill_date FROM chronic_patient_refills 
                    WHERE hn = ? AND drug_id = ? 
                    ORDER BY last_refill_date DESC LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$hn, $drugId]);
            
            $result = $stmt->fetch();
            if ($result && $result['last_refill_date']) {
                $lastRefillDate = $result['last_refill_date'];
            }
        } catch (\PDOException $e) {
            error_log("Error getting last refill date: " . $e->getMessage());
        }
        
        // Calculate next refill date (last refill + days supply - 3 days buffer)
        $bufferDays = 3;
        $nextRefillDate = date('Y-m-d', strtotime($lastRefillDate . " + " . ($daysSupply - $bufferDays) . " days"));
        
        return $nextRefillDate;
    }
    
    /**
     * Update refill schedule
     * 
     * @param string $hn Hospital Number
     * @param int $drugId Drug ID
     * @param string $drugName Drug name
     * @param int $daysSupply Days supply
     * @return bool Success status
     */
    public function updateRefillSchedule($hn, $drugId, $drugName, $daysSupply)
    {
        try {
            $lastRefillDate = date('Y-m-d');
            $nextRefillDate = $this->calculateNextRefillDate($hn, $drugId, $daysSupply);
            
            // Check if record exists
            $sql = "SELECT id FROM chronic_patient_refills WHERE hn = ? AND drug_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$hn, $drugId]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Update existing record
                $sql = "UPDATE chronic_patient_refills 
                        SET last_refill_date = ?,
                            next_refill_date = ?,
                            days_supply = ?,
                            refill_status = 'on_time',
                            reminder_sent = 0,
                            updated_at = NOW()
                        WHERE hn = ? AND drug_id = ?";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    $lastRefillDate,
                    $nextRefillDate,
                    $daysSupply,
                    $hn,
                    $drugId
                ]);
            } else {
                // Insert new record
                $sql = "INSERT INTO chronic_patient_refills 
                        (hn, drug_id, drug_name, last_refill_date, next_refill_date, days_supply, refill_status) 
                        VALUES (?, ?, ?, ?, ?, ?, 'on_time')";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    $hn,
                    $drugId,
                    $drugName,
                    $lastRefillDate,
                    $nextRefillDate,
                    $daysSupply
                ]);
            }
            
            return true;
            
        } catch (\PDOException $e) {
            error_log("Error updating refill schedule: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get patients due for refill
     * 
     * @param int $daysAhead Days to look ahead
     * @return array Patients due for refill
     */
    public function getPatientsDueForRefill($daysAhead = 7)
    {
        try {
            $sql = "SELECT 
                        cpr.hn,
                        ppc.full_name,
                        ppc.phone,
                        cpr.drug_name,
                        cpr.next_refill_date,
                        DATEDIFF(cpr.next_refill_date, CURDATE()) as days_until_refill,
                        cpr.reminder_sent,
                        cpr.reminder_sent_at
                    FROM chronic_patient_refills cpr
                    INNER JOIN patient_profile_cache ppc ON cpr.hn = ppc.hn
                    WHERE cpr.next_refill_date >= CURDATE()
                    AND cpr.next_refill_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
                    AND cpr.reminder_sent = 0
                    ORDER BY cpr.next_refill_date, cpr.hn";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$daysAhead]);
            
            return $stmt->fetchAll();
            
        } catch (\PDOException $e) {
            error_log("Error getting patients due for refill: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get overdue patients
     * 
     * @return array Overdue patients
     */
    public function getOverduePatients()
    {
        try {
            $sql = "SELECT 
                        cpr.hn,
                        ppc.full_name,
                        ppc.phone,
                        cpr.drug_name,
                        cpr.next_refill_date,
                        DATEDIFF(CURDATE(), cpr.next_refill_date) as days_overdue
                    FROM chronic_patient_refills cpr
                    INNER JOIN patient_profile_cache ppc ON cpr.hn = ppc.hn
                    WHERE cpr.next_refill_date < CURDATE()
                    ORDER BY cpr.next_refill_date, cpr.hn";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (\PDOException $e) {
            error_log("Error getting overdue patients: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Send refill reminder
     * 
     * @param string $hn Hospital Number
     * @param string $drugName Drug name
     * @param string $nextRefillDate Next refill date
     * @param string $channel Channel (sms, line, email)
     * @return bool Success status
     */
    public function sendRefillReminder($hn, $drugName, $nextRefillDate, $channel = 'sms')
    {
        try {
            // Get patient info
            $profile = $this->patientService->getCachedProfile($hn);
            
            if (!$profile || empty($profile['phone'])) {
                return false;
            }
            
            // Create notification message
            $message = "แจ้งเตือนรับยา: {$drugName}\n";
            $message .= "กำหนดรับยา: " . $this->formatThaiDate($nextRefillDate) . "\n";
            $message .= "กรุณามารับยาตามนัด";
            
            // Log notification
            $this->logNotification($hn, 'refill_reminder', $message, $channel);
            
            // Send notification (implement actual sending here)
            // For now, just mark as sent
            $this->markReminderSent($hn, $drugName);
            
            return true;
            
        } catch (\Exception $e) {
            error_log("Error sending refill reminder: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark reminder as sent
     */
    private function markReminderSent($hn, $drugName)
    {
        try {
            $sql = "UPDATE chronic_patient_refills 
                    SET reminder_sent = 1,
                        reminder_sent_at = NOW()
                    WHERE hn = ? AND drug_name = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$hn, $drugName]);
            
        } catch (\PDOException $e) {
            error_log("Error marking reminder sent: " . $e->getMessage());
        }
    }
    
    /**
     * Log notification
     */
    private function logNotification($hn, $type, $message, $channel)
    {
        try {
            $sql = "INSERT INTO patient_notifications 
                    (hn, notification_type, title, message, channel, sent_at, delivery_status) 
                    VALUES (?, ?, ?, ?, ?, NOW(), 'sent')";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $hn,
                $type,
                'แจ้งเตือนรับยา',
                $message,
                $channel
            ]);
            
        } catch (\PDOException $e) {
            error_log("Error logging notification: " . $e->getMessage());
        }
    }
    
    /**
     * Format Thai date
     */
    private function formatThaiDate($date)
    {
        $thaiMonths = [
            '01' => 'ม.ค.', '02' => 'ก.พ.', '03' => 'มี.ค.',
            '04' => 'เม.ย.', '05' => 'พ.ค.', '06' => 'มิ.ย.',
            '07' => 'ก.ค.', '08' => 'ส.ค.', '09' => 'ก.ย.',
            '10' => 'ต.ค.', '11' => 'พ.ย.', '12' => 'ธ.ค.'
        ];
        
        $parts = explode('-', $date);
        $year = (int)$parts[0] + 543;
        $month = $thaiMonths[$parts[1]];
        $day = (int)$parts[2];
        
        return "{$day} {$month} {$year}";
    }
    
    /**
     * Get medication adherence rate
     * 
     * @param string $hn Hospital Number
     * @param int $months Number of months to calculate
     * @return float Adherence rate (0-100)
     */
    public function getMedicationAdherence($hn, $months = 3)
    {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_refills,
                        SUM(CASE WHEN next_refill_date >= last_refill_date THEN 1 ELSE 0 END) as on_time_refills
                    FROM chronic_patient_refills
                    WHERE hn = ?
                    AND last_refill_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$hn, $months]);
            
            $result = $stmt->fetch();
            
            if ($result && $result['total_refills'] > 0) {
                return round(($result['on_time_refills'] / $result['total_refills']) * 100, 2);
            }
            
            return 0;
            
        } catch (\PDOException $e) {
            error_log("Error calculating adherence: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get chronic disease statistics
     * 
     * @return array Statistics
     */
    public function getStatistics()
    {
        try {
            $stats = [];
            
            // Total chronic patients
            $sql = "SELECT COUNT(DISTINCT hn) as total FROM patient_chronic_diseases WHERE status = 'active'";
            $stmt = $this->db->query($sql);
            $stats['total_patients'] = $stmt->fetch()['total'];
            
            // Patients by disease
            $sql = "SELECT disease_name, COUNT(*) as count 
                    FROM patient_chronic_diseases 
                    WHERE status = 'active'
                    GROUP BY disease_name 
                    ORDER BY count DESC 
                    LIMIT 10";
            $stmt = $this->db->query($sql);
            $stats['by_disease'] = $stmt->fetchAll();
            
            // Due for refill
            $sql = "SELECT COUNT(*) as count FROM chronic_patient_refills 
                    WHERE next_refill_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
            $stmt = $this->db->query($sql);
            $stats['due_for_refill'] = $stmt->fetch()['count'];
            
            // Overdue
            $sql = "SELECT COUNT(*) as count FROM chronic_patient_refills 
                    WHERE next_refill_date < CURDATE()";
            $stmt = $this->db->query($sql);
            $stats['overdue'] = $stmt->fetch()['count'];
            
            return $stats;
            
        } catch (\PDOException $e) {
            error_log("Error getting statistics: " . $e->getMessage());
            return [];
        }
    }
}
