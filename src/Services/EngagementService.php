<?php

namespace App\Services;

use App\Core\Database;
use App\Services\NotificationService;
use PDO;

/**
 * Engagement Service (Phase 3)
 * 
 * Handles patient communication, instructions, and adherence
 */
class EngagementService
{
    private $db;
    private $notificationService;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->notificationService = new NotificationService();
    }
    
    /**
     * Send Medication Reminder to Patient
     */
    public function sendMedicationReminder($hn, $drugName, $instruction)
    {
        $message = "⏰ สวัสดีครับ/ค่ะ คุณผู้ป่วย (HN: $hn)\nอย่าลืมทานยา: $drugName\nวิธีใช้: $instruction\nด้วยความห่วงใยจากห้องยาครับ/ค่ะ";
        
        // In a real scenario, we would look up the patient's LINE token or Phone number
        $sentStatus = $this->notificationService->sendLine($message);
        
        // Log notification
        $stmt = $this->db->prepare("
            INSERT INTO patient_notifications (hn, type, channel, message, status)
            VALUES (?, 'remind_med', 'LINE', ?, ?)
        ");
        $stmt->execute([$hn, $message, $sentStatus ? 'sent' : 'failed']);
        
        return $sentStatus;
    }
    
    /**
     * Generate Personalized Instructions
     * (Simulated AI logic for transforming complex pharma terms to simple Thai)
     */
    public function generateEasyInstruction($drugName, $rawInstruction)
    {
        // Simple mapping for demonstration
        $replacements = [
            'q.i.d.' => 'วันละ 4 ครั้ง (เช้า กลางวัน เย็น ก่อนนอน)',
            't.i.d.' => 'วันละ 3 ครั้ง (เช้า กลางวัน เย็น)',
            'b.i.d.' => 'วันละ 2 ครั้ง (เช้า เย็น)',
            'o.d.' => 'วันละ 1 ครั้ง',
            'p.c.' => 'หลังอาหารทันที',
            'a.c.' => 'ก่อนอาหาร 30 นาที',
            'h.s.' => 'ก่อนนอน',
            'stat' => 'ทันทีที่มีอาการ'
        ];
        
        $easyText = $rawInstruction;
        foreach ($replacements as $shorthand => $meaning) {
            $easyText = str_ireplace($shorthand, $meaning, $easyText);
        }
        
        return "ทาน $drugName $easyText";
    }
    
    /**
     * Store/Update Personalized Instruction
     */
    public function savePersonalizedInstruction($hn, $drugId, $drugName, $instructionText)
    {
        $stmt = $this->db->prepare("
            INSERT INTO patient_engagement_instructions (hn, drug_id, drug_name, instruction_text)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE instruction_text = ?
        ");
        return $stmt->execute([$hn, $drugId, $drugName, $instructionText, $instructionText]);
    }
    
    /**
     * Get Patient Instructions
     */
    public function getPatientInstructions($hn)
    {
        $stmt = $this->db->prepare("SELECT * FROM patient_engagement_instructions WHERE hn = ?");
        $stmt->execute([$hn]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Record Patient Adherence (Self-Report)
     */
    public function recordAdherence($hn, $drugId, $status, $notes = '')
    {
        $stmt = $this->db->prepare("
            INSERT INTO patient_adherence_logs (hn, drug_id, taken_at, status, notes)
            VALUES (?, ?, NOW(), ?, ?)
        ");
        return $stmt->execute([$hn, $drugId, $status, $notes]);
    }
    
    /**
     * Get Adherence Statistics for a patient
     */
    public function getAdherenceStats($hn)
    {
        $sql = "
            SELECT 
                status, 
                COUNT(*) as count,
                ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM patient_adherence_logs WHERE hn = ?), 2) as percentage
            FROM patient_adherence_logs 
            WHERE hn = ?
            GROUP BY status
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$hn, $hn]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
