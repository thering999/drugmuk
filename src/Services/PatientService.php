<?php

namespace App\Services;

use PDO;

/**
 * Patient Service
 * 
 * ดึงข้อมูลผู้ป่วยจาก JHCIS และจัดการข้อมูลผู้ป่วย
 */
class PatientService
{
    private $jhcisDb;
    private $drugmukDb;
    
    public function __construct()
    {
        $this->jhcisDb = $this->getJHCISConnection();
        $this->drugmukDb = \App\Core\Database::getInstance()->getConnection();
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
     * Get patient profile from JHCIS
     * 
     * @param string $hn Hospital Number
     * @return array|null Patient data
     */
    public function getPatientProfile($hn)
    {
        if (!$this->jhcisDb) {
            return null;
        }
        
        try {
            $sql = "SELECT 
                        p.hn,
                        p.pname,
                        p.fname,
                        p.lname,
                        p.birth,
                        p.sex,
                        p.addrpart,
                        p.moopart,
                        p.tmbpart,
                        p.amppart,
                        p.chwpart,
                        p.telephoneh as phone,
                        p.pttype,
                        p.bloodgroup,
                        p.nationality,
                        p.occupation,
                        p.cid,
                        TIMESTAMPDIFF(YEAR, p.birth, CURDATE()) as age,
                        CASE 
                            WHEN p.sex = '1' THEN 'ชาย'
                            WHEN p.sex = '2' THEN 'หญิง'
                            ELSE 'ไม่ระบุ'
                        END as sex_label
                    FROM patient p
                    WHERE p.hn = :hn
                    LIMIT 1";
            
            $stmt = $this->jhcisDb->prepare($sql);
            $stmt->execute(['hn' => $hn]);
            
            $patient = $stmt->fetch();
            
            if ($patient) {
                // Get additional data
                $patient['chronic_diseases'] = $this->getPatientChronicDiseases($hn);
                $patient['allergies'] = $this->getPatientAllergies($hn);
                $patient['recent_visits'] = $this->getRecentVisits($hn, 10);
                $patient['current_medications'] = $this->getCurrentMedications($hn);
                $patient['lab_results'] = $this->getLabResults($hn, 20);
                $patient['vaccines'] = $this->getVaccinationHistory($hn);
                $patient['screening'] = $this->getScreeningHistory($hn);
            }
            
            return $patient;
            
        } catch (\PDOException $e) {
            error_log("Error fetching patient profile: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Search patients by keyword
     * 
     * @param string $keyword Search keyword (HN, name, CID)
     * @param int $limit Maximum results
     * @return array List of patients
     */
    public function searchPatients($keyword, $limit = 20)
    {
        if (!$this->jhcisDb || empty($keyword)) {
            return [];
        }
        
        try {
            $sql = "SELECT 
                        p.hn,
                        p.pname,
                        p.fname,
                        p.lname,
                        p.birth,
                        p.sex,
                        p.cid,
                        TIMESTAMPDIFF(YEAR, p.birth, CURDATE()) as age,
                        CONCAT(p.pname, p.fname, ' ', p.lname) as full_name
                    FROM patient p
                    WHERE p.hn LIKE :keyword1
                       OR p.fname LIKE :keyword2
                       OR p.lname LIKE :keyword3
                       OR p.cid LIKE :keyword4
                       OR CONCAT(p.fname, ' ', p.lname) LIKE :keyword5
                    ORDER BY p.hn DESC
                    LIMIT :limit";
            
            $stmt = $this->jhcisDb->prepare($sql);
            $searchTerm = "%{$keyword}%";
            $stmt->bindValue(':keyword1', $searchTerm, PDO::PARAM_STR);
            $stmt->bindValue(':keyword2', $searchTerm, PDO::PARAM_STR);
            $stmt->bindValue(':keyword3', $searchTerm, PDO::PARAM_STR);
            $stmt->bindValue(':keyword4', $searchTerm, PDO::PARAM_STR);
            $stmt->bindValue(':keyword5', $searchTerm, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (\PDOException $e) {
            error_log("Error searching patients: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get patient chronic diseases
     * 
     * @param string $hn Hospital Number
     * @return array List of chronic diseases
     */
    public function getPatientChronicDiseases($hn)
    {
        if (!$this->jhcisDb) {
            return [];
        }
        
        try {
            $sql = "SELECT 
                        c.chronic,
                        c.chronicname,
                        c.datediag,
                        c.dateupdate,
                        c.icd10
                    FROM chronic c
                    WHERE c.hn = :hn
                    AND c.chronic IS NOT NULL
                    ORDER BY c.datediag DESC";
            
            $stmt = $this->jhcisDb->prepare($sql);
            $stmt->execute(['hn' => $hn]);
            
            return $stmt->fetchAll();
            
        } catch (\PDOException $e) {
            error_log("Error fetching chronic diseases: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get patient allergies (using DrugAllergyService)
     */
    private function getPatientAllergies($hn)
    {
        $allergyService = new DrugAllergyService();
        return $allergyService->getPatientAllergies($hn);
    }
    
    /**
     * Get recent visits
     * 
     * @param string $hn Hospital Number
     * @param int $limit Number of visits
     * @return array List of visits
     */
    public function getRecentVisits($hn, $limit = 10)
    {
        if (!$this->jhcisDb) {
            return [];
        }
        
        try {
            $sql = "SELECT 
                        o.vn,
                        o.hn,
                        o.vstdate,
                        o.vsttime,
                        o.hospmain,
                        o.hospsub,
                        o.pttype,
                        o.dx1 as diagnosis_code,
                        o.weight,
                        o.height,
                        o.bps as bp_systolic,
                        o.bpd as bp_diastolic,
                        o.temperature as temp,
                        o.pulse,
                        o.rr as respiratory_rate,
                        CONCAT(o.vstdate, ' ', o.vsttime) as visit_datetime
                    FROM opd o
                    WHERE o.hn = :hn
                    ORDER BY o.vstdate DESC, o.vsttime DESC
                    LIMIT :limit";
            
            $stmt = $this->jhcisDb->prepare($sql);
            $stmt->bindValue(':hn', $hn, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (\PDOException $e) {
            error_log("Error fetching recent visits: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get current medications (last 3 months)
     * 
     * @param string $hn Hospital Number
     * @return array List of current medications
     */
    public function getCurrentMedications($hn)
    {
        if (!$this->jhcisDb) {
            return [];
        }
        
        try {
            $sql = "SELECT 
                        od.vn,
                        od.vstdate,
                        od.drugname,
                        od.qty,
                        od.unit,
                        od.usage,
                        od.drugprice,
                        od.drugcost,
                        COUNT(*) OVER (PARTITION BY od.drugname) as prescription_count
                    FROM opd_drug od
                    WHERE od.hn = :hn
                    AND od.vstdate >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
                    ORDER BY od.vstdate DESC, od.drugname
                    LIMIT 50";
            
            $stmt = $this->jhcisDb->prepare($sql);
            $stmt->execute(['hn' => $hn]);
            
            return $stmt->fetchAll();
            
        } catch (\PDOException $e) {
            error_log("Error fetching current medications: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get patient lab results from JHCIS
     * 
     * @param string $hn Hospital Number
     * @param int $limit Number of results
     * @return array Lab results
     */
    public function getLabResults($hn, $limit = 20)
    {
        if (!$this->jhcisDb) return [];
        
        try {
            // Common JHCIS lab table is lab_fu or lab_check_up
            $sql = "SELECT 
                        l.vstdate,
                        l.labtest as lab_name,
                        l.labresult as lab_value,
                        '' as lab_unit, -- Units often aren't in main table
                        l.labresult_status as status
                    FROM lab_fu l
                    WHERE l.hn = :hn
                    ORDER BY l.vstdate DESC
                    LIMIT :limit";
            
            $stmt = $this->jhcisDb->prepare($sql);
            $stmt->bindValue(':hn', $hn, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (\PDOException $e) {
            error_log("Error fetching lab results: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get vaccination history from JHCIS
     * 
     * @param string $hn Hospital Number
     * @return array Vaccination history
     */
    public function getVaccinationHistory($hn)
    {
        if (!$this->jhcisDb) return [];
        
        try {
            $sql = "SELECT 
                        vv.vstdate,
                        v.vaccinename,
                        v.vaccinetype,
                        vv.lotno
                    FROM visit_vaccine vv
                    JOIN vaccine v ON vv.vaccinecode = v.vaccinecode
                    WHERE vv.hn = :hn
                    ORDER BY vv.vstdate DESC";
            
            $stmt = $this->jhcisDb->prepare($sql);
            $stmt->execute(['hn' => $hn]);
            
            return $stmt->fetchAll();
            
        } catch (\PDOException $e) {
            error_log("Error fetching vaccinations: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get screening and physical exams from JHCIS
     * 
     * @param string $hn Hospital Number
     * @return array Screening data
     */
    public function getScreeningHistory($hn)
    {
        if (!$this->jhcisDb) return [];
        
        try {
            // JHCIS screening often in visit_screening or similar
            $sql = "SELECT 
                        vstdate,
                        weight,
                        height,
                        bps as bp_systolic,
                        bpd as bp_diastolic,
                        waist,
                        bmi,
                        fbs,
                        waist
                    FROM visit_screening
                    WHERE hn = :hn
                    ORDER BY vstdate DESC
                    LIMIT 20";
            
            $stmt = $this->jhcisDb->prepare($sql);
            $stmt->execute(['hn' => $hn]);
            
            return $stmt->fetchAll();
            
        } catch (\PDOException $e) {
            // Some JHCIS versions might not have this table, fallback to empty
            return [];
        }
    }
    
    /**
     * Get patient vital signs trends
     * 
     * @param string $hn Hospital Number
     * @param int $months Number of months to look back
     * @return array Vital signs data
     */
    public function getVitalSignsTrends($hn, $months = 6)
    {
        if (!$this->jhcisDb) {
            return [];
        }
        
        try {
            $sql = "SELECT 
                        o.vstdate,
                        o.weight,
                        o.height,
                        o.bps as bp_systolic,
                        o.bpd as bp_diastolic,
                        o.temperature,
                        o.pulse,
                        o.rr as respiratory_rate,
                        CASE 
                            WHEN o.weight > 0 AND o.height > 0 
                            THEN ROUND(o.weight / POWER(o.height/100, 2), 2)
                            ELSE NULL
                        END as bmi
                    FROM opd o
                    WHERE o.hn = :hn
                    AND o.vstdate >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
                    AND (o.weight > 0 OR o.bps > 0 OR o.temperature > 0)
                    ORDER BY o.vstdate DESC";
            
            $stmt = $this->jhcisDb->prepare($sql);
            $stmt->bindValue(':hn', $hn, PDO::PARAM_STR);
            $stmt->bindValue(':months', $months, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (\PDOException $e) {
            error_log("Error fetching vital signs: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Cache patient profile locally
     * 
     * @param string $hn Hospital Number
     * @return bool Success status
     */
    public function cachePatientProfile($hn)
    {
        $profile = $this->getPatientProfile($hn);
        
        if (!$profile) {
            return false;
        }
        
        try {
            // 1. Base Profile Cache
            $sql = "DELETE FROM patient_profile_cache WHERE hn = ?";
            $this->drugmukDb->prepare($sql)->execute([$hn]);
            
            $sql = "INSERT INTO patient_profile_cache 
                    (hn, full_name, birth_date, sex, age, phone, pttype, cached_at, data_json) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)";
            
            $fullName = trim(($profile['pname'] ?? '') . ($profile['fname'] ?? '') . ' ' . ($profile['lname'] ?? ''));
            
            $this->drugmukDb->prepare($sql)->execute([
                $hn,
                $fullName,
                $profile['birth'] ?? null,
                $profile['sex'] ?? null,
                $profile['age'] ?? null,
                $profile['phone'] ?? null,
                $profile['pttype'] ?? null,
                json_encode($profile, JSON_UNESCAPED_UNICODE)
            ]);
            
            // 2. Cache Labs
            $this->cachePatientLabs($hn, $profile['lab_results'] ?? []);
            
            // 3. Cache Vaccines
            $this->cachePatientVaccines($hn, $profile['vaccines'] ?? []);
            
            // 4. Cache Screening
            $this->cachePatientScreening($hn, $profile['screening'] ?? []);
            
            // 5. Cache Chronic Diseases
            $this->cachePatientChronicDiseases($hn, $profile['chronic_diseases'] ?? []);
            
            return true;
            
        } catch (\PDOException $e) {
            error_log("Error caching patient data: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cache Patient Labs
     */
    private function cachePatientLabs($hn, $labs) {
        $this->drugmukDb->prepare("DELETE FROM patient_lab_results WHERE hn = ?")->execute([$hn]);
        if (empty($labs)) return;
        
        $sql = "INSERT INTO patient_lab_results (hn, vstdate, lab_name, lab_value, lab_unit) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->drugmukDb->prepare($sql);
        foreach ($labs as $lab) {
            $stmt->execute([
                $hn,
                $lab['vstdate'],
                $lab['lab_name'],
                floatval($lab['lab_value']),
                $lab['lab_unit'] ?? ''
            ]);
        }
    }

    /**
     * Cache Patient Vaccines
     */
    private function cachePatientVaccines($hn, $vaccines) {
        $this->drugmukDb->prepare("DELETE FROM patient_vaccines_cache WHERE hn = ?")->execute([$hn]);
        if (empty($vaccines)) return;
        
        $sql = "INSERT INTO patient_vaccines_cache (hn, vstdate, vaccine_name, vaccine_type, lot_no) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->drugmukDb->prepare($sql);
        foreach ($vaccines as $v) {
            $stmt->execute([
                $hn,
                $v['vstdate'],
                $v['vaccinename'],
                $v['vaccinetype'],
                $v['lotno']
            ]);
        }
    }

    /**
     * Cache Patient Screening
     */
    private function cachePatientScreening($hn, $screening) {
        $this->drugmukDb->prepare("DELETE FROM patient_screening_cache WHERE hn = ?")->execute([$hn]);
        if (empty($screening)) return;
        
        $sql = "INSERT INTO patient_screening_cache (hn, vstdate, weight, height, bp_systolic, bp_diastolic, waist, bmi, fbs) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->drugmukDb->prepare($sql);
        foreach ($screening as $s) {
            $stmt->execute([
                $hn,
                $s['vstdate'],
                $s['weight'],
                $s['height'],
                $s['bp_systolic'],
                $s['bp_diastolic'],
                $s['waist'],
                $s['bmi'],
                $s['fbs']
            ]);
        }
    }

    /**
     * Cache Patient Chronic Diseases
     */
    private function cachePatientChronicDiseases($hn, $chronic) {
        $this->drugmukDb->prepare("DELETE FROM patient_chronic_diseases_cache WHERE hn = ?")->execute([$hn]);
        if (empty($chronic)) return;
        
        $sql = "INSERT INTO patient_chronic_diseases_cache (hn, disease_code, disease_name, diagnosed_date) VALUES (?, ?, ?, ?)";
        $stmt = $this->drugmukDb->prepare($sql);
        foreach ($chronic as $c) {
            $stmt->execute([
                $hn,
                $c['icd10'],
                $c['chronicname'],
                $c['datediag']
            ]);
        }
    }
    
    /**
     * Get cached patient profile
     * 
     * @param string $hn Hospital Number
     * @param int $maxAge Maximum cache age in hours
     * @return array|null Cached profile
     */
    public function getCachedProfile($hn, $maxAge = 24)
    {
        try {
            $sql = "SELECT * FROM patient_profile_cache 
                    WHERE hn = ? 
                    AND cached_at > DATE_SUB(NOW(), INTERVAL ? HOUR)
                    LIMIT 1";
            
            $stmt = $this->drugmukDb->prepare($sql);
            $stmt->execute([$hn, $maxAge]);
            
            $cached = $stmt->fetch();
            
            if ($cached && !empty($cached['data_json'])) {
                return json_decode($cached['data_json'], true);
            }
            
            return null;
            
        } catch (\PDOException $e) {
            error_log("Error getting cached profile: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get patient profile with auto-cache
     * 
     * @param string $hn Hospital Number
     * @return array|null Patient profile
     */
    public function getProfileWithCache($hn)
    {
        // Try cache first
        $cached = $this->getCachedProfile($hn);
        
        if ($cached !== null) {
            return $cached;
        }
        
        // Cache miss, fetch from JHCIS
        $profile = $this->getPatientProfile($hn);
        
        if ($profile) {
            // Cache for next time
            $this->cachePatientProfile($hn);
        }
        
        return $profile;
    }
    
    /**
     * Get recent patients for tele-pharmacy dashboard
     * 
     * @param int $limit Number of patients to return
     * @return array List of recent patients
     */
    public function getRecentPatients($limit = 10)
    {
        if (!$this->jhcisDb) {
            return [];
        }
        
        try {
            // Get patients who have visited recently and have chronic diseases
            $sql = "SELECT DISTINCT
                        p.hn,
                        p.pname,
                        p.fname,
                        p.lname,
                        p.birth as birth_date,
                        p.sex,
                        CONCAT(p.pname, p.fname, ' ', p.lname) as full_name,
                        CONCAT(p.fname, ' ', p.lname) as first_name,
                        p.lname as last_name,
                        TIMESTAMPDIFF(YEAR, p.birth, CURDATE()) as age,
                        MAX(o.vstdate) as last_visit,
                        GROUP_CONCAT(DISTINCT c.chronicname SEPARATOR ', ') as chronic_diseases
                    FROM patient p
                    LEFT JOIN opd o ON p.hn = o.hn
                    LEFT JOIN chronic c ON p.hn = c.hn
                    WHERE o.vstdate >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                    GROUP BY p.hn
                    ORDER BY MAX(o.vstdate) DESC
                    LIMIT :limit";
            
            $stmt = $this->jhcisDb->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (\PDOException $e) {
            error_log("Error fetching recent patients: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get patient by HN (alias for backward compatibility)
     * 
     * @param string $hn Hospital Number
     * @return array|null Patient data
     */
    public function getPatientByHN($hn)
    {
        return $this->getPatientProfile($hn);
    }
}
