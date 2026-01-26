<?php

namespace App\Services;

use PDO;

/**
 * Drug Allergy Service
 * 
 * ตรวจสอบประวัติแพ้ยาของผู้ป่วยจาก JHCIS
 * และแจ้งเตือนเมื่อจ่ายยาที่ผู้ป่วยแพ้
 */
class DrugAllergyService
{
    private $jhcisDb;
    private $drugmukDb;
    
    public function __construct()
    {
        $this->jhcisDb = $this->getJHCISConnection();
        $this->drugmukDb = $this->getDrugmukConnection();
    }
    
    /**
     * Get JHCIS database connection
     */
    private function getJHCISConnection()
    {
        $host = $_ENV['JHCIS_DB_HOST'] ?? 'localhost';
        $port = $_ENV['JHCIS_DB_PORT'] ?? '3306';
        $dbname = $_ENV['JHCIS_DB_NAME'] ?? 'jhcisdb';
        $username = $_ENV['JHCIS_DB_USER'] ?? 'root';
        $password = $_ENV['JHCIS_DB_PASS'] ?? '';
        
        try {
            $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
            return new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (\PDOException $e) {
            error_log("JHCIS Connection Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get Drugmuk database connection
     */
    private function getDrugmukConnection()
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
     * Get patient drug allergies from JHCIS
     * 
     * @param string $hn Hospital Number
     * @return array List of drug allergies
     */
    public function getPatientAllergies($hn)
    {
        if (!$this->jhcisDb) {
            return [];
        }
        
        try {
            // Query drug allergy from JHCIS
            // Table structure may vary, adjust according to your JHCIS version
            $sql = "SELECT 
                        da.drugallergy AS allergy_name,
                        da.symptom,
                        da.typedx AS severity,
                        da.daterecord,
                        da.informant
                    FROM drugallergy da
                    WHERE da.hn = :hn
                    AND da.drugallergy IS NOT NULL
                    AND da.drugallergy != ''
                    ORDER BY da.daterecord DESC";
            
            $stmt = $this->jhcisDb->prepare($sql);
            $stmt->execute(['hn' => $hn]);
            
            return $stmt->fetchAll();
            
        } catch (\PDOException $e) {
            error_log("Error fetching allergies: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if drug is in patient's allergy list
     * 
     * @param string $hn Hospital Number
     * @param string $drugName Drug name to check
     * @return array|null Allergy info if found, null otherwise
     */
    public function checkDrugAllergy($hn, $drugName)
    {
        $allergies = $this->getPatientAllergies($hn);
        
        foreach ($allergies as $allergy) {
            // Check if drug name contains allergy name or vice versa
            if (stripos($drugName, $allergy['allergy_name']) !== false ||
                stripos($allergy['allergy_name'], $drugName) !== false) {
                return $allergy;
            }
        }
        
        return null;
    }
    
    /**
     * Check multiple drugs for allergies
     * 
     * @param string $hn Hospital Number
     * @param array $drugs Array of drug names
     * @return array List of allergies found
     */
    public function checkMultipleDrugs($hn, $drugs)
    {
        $allergies = $this->getPatientAllergies($hn);
        $found = [];
        
        foreach ($drugs as $drug) {
            foreach ($allergies as $allergy) {
                if (stripos($drug, $allergy['allergy_name']) !== false ||
                    stripos($allergy['allergy_name'], $drug) !== false) {
                    $found[] = [
                        'drug' => $drug,
                        'allergy' => $allergy
                    ];
                }
            }
        }
        
        return $found;
    }
    
    /**
     * Get allergy severity level
     * 
     * @param string $severity Severity code from JHCIS
     * @return array Severity info
     */
    public function getSeverityInfo($severity)
    {
        $severityMap = [
            '1' => ['level' => 'mild', 'label' => 'เล็กน้อย', 'color' => 'warning'],
            '2' => ['level' => 'moderate', 'label' => 'ปานกลาง', 'color' => 'warning'],
            '3' => ['level' => 'severe', 'label' => 'รุนแรง', 'color' => 'danger'],
            '4' => ['level' => 'life-threatening', 'label' => 'คุกคามชีวิต', 'color' => 'danger'],
        ];
        
        return $severityMap[$severity] ?? ['level' => 'unknown', 'label' => 'ไม่ระบุ', 'color' => 'secondary'];
    }
    
    /**
     * Log allergy check
     * 
     * @param string $hn Hospital Number
     * @param string $drugName Drug name
     * @param bool $allergyFound Whether allergy was found
     * @param int $userId User who performed the check
     */
    public function logAllergyCheck($hn, $drugName, $allergyFound, $userId)
    {
        try {
            $sql = "INSERT INTO allergy_check_log 
                    (hn, drug_name, allergy_found, checked_by, checked_at) 
                    VALUES (?, ?, ?, ?, NOW())";
            
            $stmt = $this->drugmukDb->prepare($sql);
            $stmt->execute([
                $hn,
                $drugName,
                $allergyFound ? 1 : 0,
                $userId
            ]);
            
        } catch (\PDOException $e) {
            error_log("Error logging allergy check: " . $e->getMessage());
        }
    }
    
    /**
     * Get allergy check statistics
     * 
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Statistics
     */
    public function getStatistics($startDate, $endDate)
    {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_checks,
                        SUM(allergy_found) as allergies_found,
                        COUNT(DISTINCT hn) as unique_patients,
                        COUNT(DISTINCT checked_by) as unique_users
                    FROM allergy_check_log
                    WHERE checked_at BETWEEN ? AND ?";
            
            $stmt = $this->drugmukDb->prepare($sql);
            $stmt->execute([$startDate, $endDate]);
            
            return $stmt->fetch();
            
        } catch (\PDOException $e) {
            error_log("Error getting statistics: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Sync allergies from JHCIS to local cache
     * 
     * @param string $hn Hospital Number
     * @return bool Success status
     */
    public function syncAllergies($hn)
    {
        $allergies = $this->getPatientAllergies($hn);
        
        if (empty($allergies)) {
            return true;
        }
        
        try {
            // Delete existing cached allergies
            $sql = "DELETE FROM patient_allergies_cache WHERE hn = ?";
            $stmt = $this->drugmukDb->prepare($sql);
            $stmt->execute([$hn]);
            
            // Insert new allergies
            $sql = "INSERT INTO patient_allergies_cache 
                    (hn, allergy_name, symptom, severity, date_recorded, synced_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->drugmukDb->prepare($sql);
            
            foreach ($allergies as $allergy) {
                $stmt->execute([
                    $hn,
                    $allergy['allergy_name'],
                    $allergy['symptom'] ?? '',
                    $allergy['severity'] ?? '',
                    $allergy['daterecord'] ?? null
                ]);
            }
            
            return true;
            
        } catch (\PDOException $e) {
            error_log("Error syncing allergies: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get cached allergies (faster than querying JHCIS)
     * 
     * @param string $hn Hospital Number
     * @param int $maxAge Maximum age in hours (default: 24)
     * @return array|null Cached allergies or null if cache is stale
     */
    public function getCachedAllergies($hn, $maxAge = 24)
    {
        try {
            $sql = "SELECT * FROM patient_allergies_cache 
                    WHERE hn = ? 
                    AND synced_at > DATE_SUB(NOW(), INTERVAL ? HOUR)
                    ORDER BY date_recorded DESC";
            
            $stmt = $this->drugmukDb->prepare($sql);
            $stmt->execute([$hn, $maxAge]);
            
            $results = $stmt->fetchAll();
            
            return !empty($results) ? $results : null;
            
        } catch (\PDOException $e) {
            error_log("Error getting cached allergies: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get allergies with auto-sync if cache is stale
     * 
     * @param string $hn Hospital Number
     * @return array Allergies
     */
    public function getAllergiesWithCache($hn)
    {
        // Try to get from cache first
        $cached = $this->getCachedAllergies($hn);
        
        if ($cached !== null) {
            return $cached;
        }
        
        // Cache is stale or doesn't exist, sync from JHCIS
        $this->syncAllergies($hn);
        
        // Return fresh data
        return $this->getCachedAllergies($hn, 1) ?? [];
    }
}
