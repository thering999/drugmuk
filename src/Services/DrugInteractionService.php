<?php
/**
 * Drug Interaction Service
 * ตรวจสอบปฏิกิริยาระหว่างยา (Drug-Drug Interaction)
 * 
 * @package Drugmuk
 * @version 3.4.0
 */

namespace App\Services;

use App\Core\Database;

class DrugInteractionService
{
    private \PDO $db;
    
    // Severity Levels
    const SEVERITY_MINOR = 'minor';
    const SEVERITY_MODERATE = 'moderate';
    const SEVERITY_MAJOR = 'major';
    const SEVERITY_CONTRAINDICATED = 'contraindicated';
    
    // Common drug interactions (simplified database)
    private array $knownInteractions = [
        // Warfarin interactions
        ['drug1' => 'warfarin', 'drug2' => 'aspirin', 'severity' => 'major', 'effect' => 'เพิ่มความเสี่ยงเลือดออก'],
        ['drug1' => 'warfarin', 'drug2' => 'ibuprofen', 'severity' => 'major', 'effect' => 'เพิ่มความเสี่ยงเลือดออก'],
        ['drug1' => 'warfarin', 'drug2' => 'naproxen', 'severity' => 'major', 'effect' => 'เพิ่มความเสี่ยงเลือดออก'],
        ['drug1' => 'warfarin', 'drug2' => 'fluconazole', 'severity' => 'major', 'effect' => 'เพิ่มฤทธิ์ warfarin'],
        ['drug1' => 'warfarin', 'drug2' => 'metronidazole', 'severity' => 'major', 'effect' => 'เพิ่มฤทธิ์ warfarin'],
        
        // Metformin interactions
        ['drug1' => 'metformin', 'drug2' => 'contrast', 'severity' => 'major', 'effect' => 'เสี่ยง lactic acidosis'],
        ['drug1' => 'metformin', 'drug2' => 'alcohol', 'severity' => 'moderate', 'effect' => 'เสี่ยงน้ำตาลต่ำ'],
        
        // ACE Inhibitors
        ['drug1' => 'enalapril', 'drug2' => 'potassium', 'severity' => 'major', 'effect' => 'เสี่ยง hyperkalemia'],
        ['drug1' => 'lisinopril', 'drug2' => 'potassium', 'severity' => 'major', 'effect' => 'เสี่ยง hyperkalemia'],
        ['drug1' => 'enalapril', 'drug2' => 'spironolactone', 'severity' => 'major', 'effect' => 'เสี่ยง hyperkalemia'],
        
        // Statins
        ['drug1' => 'simvastatin', 'drug2' => 'erythromycin', 'severity' => 'contraindicated', 'effect' => 'เสี่ยง rhabdomyolysis'],
        ['drug1' => 'simvastatin', 'drug2' => 'clarithromycin', 'severity' => 'contraindicated', 'effect' => 'เสี่ยง rhabdomyolysis'],
        ['drug1' => 'simvastatin', 'drug2' => 'itraconazole', 'severity' => 'contraindicated', 'effect' => 'เสี่ยง rhabdomyolysis'],
        ['drug1' => 'atorvastatin', 'drug2' => 'clarithromycin', 'severity' => 'major', 'effect' => 'เพิ่มระดับ statin'],
        
        // Digoxin
        ['drug1' => 'digoxin', 'drug2' => 'amiodarone', 'severity' => 'major', 'effect' => 'เพิ่มระดับ digoxin'],
        ['drug1' => 'digoxin', 'drug2' => 'verapamil', 'severity' => 'major', 'effect' => 'เพิ่มระดับ digoxin'],
        ['drug1' => 'digoxin', 'drug2' => 'quinidine', 'severity' => 'major', 'effect' => 'เพิ่มระดับ digoxin'],
        
        // SSRIs and MAOIs
        ['drug1' => 'fluoxetine', 'drug2' => 'phenelzine', 'severity' => 'contraindicated', 'effect' => 'เสี่ยง serotonin syndrome'],
        ['drug1' => 'sertraline', 'drug2' => 'phenelzine', 'severity' => 'contraindicated', 'effect' => 'เสี่ยง serotonin syndrome'],
        ['drug1' => 'fluoxetine', 'drug2' => 'tramadol', 'severity' => 'major', 'effect' => 'เสี่ยง serotonin syndrome และชัก'],
        
        // Quinolones
        ['drug1' => 'ciprofloxacin', 'drug2' => 'theophylline', 'severity' => 'major', 'effect' => 'เพิ่มระดับ theophylline'],
        ['drug1' => 'ciprofloxacin', 'drug2' => 'tizanidine', 'severity' => 'contraindicated', 'effect' => 'เพิ่มระดับ tizanidine มาก'],
        ['drug1' => 'ciprofloxacin', 'drug2' => 'antacid', 'severity' => 'moderate', 'effect' => 'ลดการดูดซึม ciprofloxacin'],
        
        // Amlodipine
        ['drug1' => 'amlodipine', 'drug2' => 'simvastatin', 'severity' => 'moderate', 'effect' => 'เพิ่มระดับ simvastatin'],
        
        // Clopidogrel
        ['drug1' => 'clopidogrel', 'drug2' => 'omeprazole', 'severity' => 'moderate', 'effect' => 'ลดประสิทธิภาพ clopidogrel'],
        ['drug1' => 'clopidogrel', 'drug2' => 'esomeprazole', 'severity' => 'moderate', 'effect' => 'ลดประสิทธิภาพ clopidogrel'],
        
        // Methotrexate
        ['drug1' => 'methotrexate', 'drug2' => 'nsaid', 'severity' => 'major', 'effect' => 'เพิ่มพิษ methotrexate'],
        ['drug1' => 'methotrexate', 'drug2' => 'trimethoprim', 'severity' => 'major', 'effect' => 'เพิ่มพิษ methotrexate'],
        
        // Lithium
        ['drug1' => 'lithium', 'drug2' => 'ibuprofen', 'severity' => 'major', 'effect' => 'เพิ่มระดับ lithium'],
        ['drug1' => 'lithium', 'drug2' => 'enalapril', 'severity' => 'major', 'effect' => 'เพิ่มระดับ lithium'],
        ['drug1' => 'lithium', 'drug2' => 'hydrochlorothiazide', 'severity' => 'major', 'effect' => 'เพิ่มระดับ lithium'],
    ];
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * ตรวจสอบ Drug Interactions สำหรับรายการยาที่กำหนด
     */
    public function checkInteractions(array $drugIds): array
    {
        if (count($drugIds) < 2) {
            return [];
        }
        
        // ดึงชื่อยา
        $drugNames = $this->getDrugNames($drugIds);
        
        $interactions = [];
        
        // ตรวจสอบทุกคู่ของยา
        for ($i = 0; $i < count($drugNames); $i++) {
            for ($j = $i + 1; $j < count($drugNames); $j++) {
                $drug1 = $drugNames[$i];
                $drug2 = $drugNames[$j];
                
                $interaction = $this->findInteraction($drug1['name'], $drug2['name']);
                
                if ($interaction) {
                    $interactions[] = [
                        'drug1_id' => $drug1['id'],
                        'drug1_name' => $drug1['name'],
                        'drug2_id' => $drug2['id'],
                        'drug2_name' => $drug2['name'],
                        'severity' => $interaction['severity'],
                        'effect' => $interaction['effect'],
                        'recommendation' => $this->getRecommendation($interaction['severity'])
                    ];
                }
            }
        }
        
        // เรียงตามความรุนแรง
        usort($interactions, function($a, $b) {
            return $this->getSeverityOrder($b['severity']) - $this->getSeverityOrder($a['severity']);
        });
        
        return $interactions;
    }
    
    /**
     * ตรวจสอบ Drug Interactions สำหรับผู้ป่วย
     */
    public function checkPatientInteractions(string $patientHN, array $newDrugIds): array
    {
        // ดึงยาปัจจุบันของผู้ป่วย
        $currentDrugs = $this->getPatientCurrentDrugs($patientHN);
        
        // รวมกับยาใหม่ที่จะจ่าย
        $allDrugIds = array_unique(array_merge($currentDrugs, $newDrugIds));
        
        return $this->checkInteractions($allDrugIds);
    }
    
    /**
     * ตรวจสอบ Drug-Allergy Interaction
     */
    public function checkAllergyInteraction(string $patientHN, array $drugIds): array
    {
        $allergies = $this->getPatientAllergies($patientHN);
        
        if (empty($allergies)) {
            return [];
        }
        
        $drugNames = $this->getDrugNames($drugIds);
        $warnings = [];
        
        foreach ($drugNames as $drug) {
            foreach ($allergies as $allergy) {
                if ($this->isRelatedDrug($drug['name'], $allergy['drug_name'])) {
                    $warnings[] = [
                        'type' => 'allergy',
                        'drug_id' => $drug['id'],
                        'drug_name' => $drug['name'],
                        'allergy_drug' => $allergy['drug_name'],
                        'reaction' => $allergy['reaction'] ?? 'ไม่ระบุ',
                        'severity' => 'contraindicated'
                    ];
                }
            }
        }
        
        return $warnings;
    }
    
    /**
     * ค้นหา Interaction จาก database
     */
    private function findInteraction(string $drug1, string $drug2): ?array
    {
        $drug1Lower = strtolower($this->extractGenericName($drug1));
        $drug2Lower = strtolower($this->extractGenericName($drug2));
        
        foreach ($this->knownInteractions as $interaction) {
            $known1 = strtolower($interaction['drug1']);
            $known2 = strtolower($interaction['drug2']);
            
            if (($this->matchDrug($drug1Lower, $known1) && $this->matchDrug($drug2Lower, $known2)) ||
                ($this->matchDrug($drug1Lower, $known2) && $this->matchDrug($drug2Lower, $known1))) {
                return $interaction;
            }
        }
        
        return null;
    }
    
    /**
     * ตรวจสอบว่ายาตรงกันหรือไม่
     */
    private function matchDrug(string $drugName, string $knownDrug): bool
    {
        // ตรวจสอบ exact match
        if (strpos($drugName, $knownDrug) !== false) {
            return true;
        }
        
        // ตรวจสอบ class match (NSAIDs)
        if ($knownDrug === 'nsaid') {
            $nsaids = ['ibuprofen', 'naproxen', 'diclofenac', 'indomethacin', 'piroxicam', 'meloxicam', 'celecoxib'];
            foreach ($nsaids as $nsaid) {
                if (strpos($drugName, $nsaid) !== false) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * ดึงชื่อ generic จากชื่อยา
     */
    private function extractGenericName(string $drugName): string
    {
        // ลบข้อมูลความแรง เช่น 500mg, 10mg
        $name = preg_replace('/\s*\d+\s*(mg|mcg|g|ml|%)\s*/i', '', $drugName);
        // ลบวงเล็บ
        $name = preg_replace('/\s*\([^)]*\)\s*/', '', $name);
        return trim($name);
    }
    
    /**
     * ตรวจสอบว่าเป็นยาที่เกี่ยวข้องกัน (สำหรับ allergy)
     */
    private function isRelatedDrug(string $drug, string $allergyDrug): bool
    {
        $drugLower = strtolower($this->extractGenericName($drug));
        $allergyLower = strtolower($allergyDrug);
        
        // Exact match
        if (strpos($drugLower, $allergyLower) !== false || strpos($allergyLower, $drugLower) !== false) {
            return true;
        }
        
        // Check drug class relationships
        $drugClasses = [
            'penicillin' => ['amoxicillin', 'ampicillin', 'penicillin', 'amoxil', 'augmentin'],
            'cephalosporin' => ['cephalexin', 'cefazolin', 'ceftriaxone', 'cefixime'],
            'sulfa' => ['sulfamethoxazole', 'cotrimoxazole', 'bactrim', 'septra'],
            'nsaid' => ['ibuprofen', 'naproxen', 'diclofenac', 'indomethacin', 'piroxicam', 'aspirin']
        ];
        
        foreach ($drugClasses as $class => $members) {
            $drugInClass = false;
            $allergyInClass = false;
            
            foreach ($members as $member) {
                if (strpos($drugLower, $member) !== false) $drugInClass = true;
                if (strpos($allergyLower, $member) !== false) $allergyInClass = true;
            }
            
            if ($drugInClass && $allergyInClass) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * ดึงชื่อยาจาก IDs
     */
    private function getDrugNames(array $drugIds): array
    {
        if (empty($drugIds)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($drugIds), '?'));
        $sql = "SELECT id, name, generic_name FROM drugs WHERE id IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($drugIds);
        
        $results = $stmt->fetchAll();
        
        // ใช้ generic_name ถ้ามี
        return array_map(function($row) {
            return [
                'id' => $row['id'],
                'name' => $row['generic_name'] ?: $row['name']
            ];
        }, $results);
    }
    
    /**
     * ดึงยาปัจจุบันของผู้ป่วย (จ่ายภายใน 30 วัน)
     */
    private function getPatientCurrentDrugs(string $patientHN): array
    {
        $sql = "SELECT DISTINCT di.drug_id 
                FROM dispensing d
                JOIN dispensing_items di ON d.id = di.dispensing_id
                WHERE d.patient_hn = :hn 
                AND d.dispense_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['hn' => $patientHN]);
        
        return array_column($stmt->fetchAll(), 'drug_id');
    }
    
    /**
     * ดึงประวัติแพ้ยาของผู้ป่วย
     */
    private function getPatientAllergies(string $patientHN): array
    {
        $sql = "SELECT drug_name, reaction, severity FROM patient_allergies WHERE patient_hn = :hn";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['hn' => $patientHN]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * รับคำแนะนำตามความรุนแรง
     */
    private function getRecommendation(string $severity): string
    {
        $recommendations = [
            self::SEVERITY_CONTRAINDICATED => '❌ ห้ามใช้ร่วมกัน - ควรเลือกยาอื่นแทน',
            self::SEVERITY_MAJOR => '⚠️ ปรึกษาแพทย์/เภสัชกรก่อนใช้ - อาจต้องปรับขนาดหรือติดตามใกล้ชิด',
            self::SEVERITY_MODERATE => '⚡ ควรระวังการใช้ร่วมกัน - ติดตามอาการ',
            self::SEVERITY_MINOR => '📝 มีปฏิกิริยาเล็กน้อย - สังเกตอาการ'
        ];
        
        return $recommendations[$severity] ?? 'ปรึกษาเภสัชกร';
    }
    
    /**
     * คำนวณลำดับความรุนแรง
     */
    private function getSeverityOrder(string $severity): int
    {
        $order = [
            self::SEVERITY_MINOR => 1,
            self::SEVERITY_MODERATE => 2,
            self::SEVERITY_MAJOR => 3,
            self::SEVERITY_CONTRAINDICATED => 4
        ];
        
        return $order[$severity] ?? 0;
    }
    
    /**
     * Format Severity สำหรับแสดงผล
     */
    public static function formatSeverity(string $severity): array
    {
        $formats = [
            self::SEVERITY_CONTRAINDICATED => [
                'label' => 'ห้ามใช้ร่วมกัน',
                'color' => '#991b1b',
                'bg' => '#fee2e2',
                'icon' => '🚫'
            ],
            self::SEVERITY_MAJOR => [
                'label' => 'รุนแรง',
                'color' => '#dc2626',
                'bg' => '#fecaca',
                'icon' => '⚠️'
            ],
            self::SEVERITY_MODERATE => [
                'label' => 'ปานกลาง',
                'color' => '#d97706',
                'bg' => '#fef3c7',
                'icon' => '⚡'
            ],
            self::SEVERITY_MINOR => [
                'label' => 'เล็กน้อย',
                'color' => '#059669',
                'bg' => '#d1fae5',
                'icon' => '📝'
            ]
        ];
        
        return $formats[$severity] ?? [
            'label' => $severity,
            'color' => '#6b7280',
            'bg' => '#f3f4f6',
            'icon' => '❓'
        ];
    }
}
