<?php

namespace App\Services\JHCIS;

use App\Services\LoggerService;
use App\Services\JHCIS\JHCISConnectionPool;

/**
 * JHCIS Auto Drug Mapper
 * 
 * Automatically maps JHCIS drugs to Drugmuk drugs using multiple strategies
 */
class JHCISAutoMapper
{
    private LoggerService $logger;
    private $db;
    
    public function __construct()
    {
        $this->logger = new LoggerService();
        $this->db = \App\Core\Database::getInstance()->getConnection();
    }
    
    /**
     * Suggest drug mappings for hospital
     * 
     * @param int $hospitalId
     * @param float $minConfidence Minimum confidence score (0-1)
     * @return array
     */
    public function suggestMappings(int $hospitalId, float $minConfidence = 0.8): array
    {
        $this->logger->info("Starting auto drug mapping", [
            'hospital_id' => $hospitalId,
            'min_confidence' => $minConfidence
        ]);
        
        // Get unmapped JHCIS drugs
        $jhcisDrugs = $this->getUnmappedJHCISDrugs($hospitalId);
        
        $suggestions = [];
        
        foreach ($jhcisDrugs as $jhcisDrug) {
            $candidates = $this->findMappingCandidates($jhcisDrug);
            
            if (!empty($candidates)) {
                $bestMatch = $candidates[0];
                
                if ($bestMatch['confidence'] >= $minConfidence) {
                    $suggestions[] = [
                        'jhcis_code' => $jhcisDrug['drugcode'],
                        'jhcis_name' => $jhcisDrug['drugname'],
                        'drugmuk_id' => $bestMatch['drug_id'],
                        'drugmuk_name' => $bestMatch['name'],
                        'confidence' => $bestMatch['confidence'],
                        'match_type' => $bestMatch['match_type']
                    ];
                }
            }
        }
        
        $this->logger->info("Auto mapping completed", [
            'hospital_id' => $hospitalId,
            'total_unmapped' => count($jhcisDrugs),
            'suggestions' => count($suggestions)
        ]);
        
        return $suggestions;
    }
    
    /**
     * Get unmapped JHCIS drugs
     * 
     * @param int $hospitalId
     * @return array
     */
    private function getUnmappedJHCISDrugs(int $hospitalId): array
    {
        $pdo = JHCISConnectionPool::getConnection($hospitalId);
        
        // Get all drugs from JHCIS
        $stmt = $pdo->query(
            "SELECT DISTINCT drugcode, drugname, druggenericname as genericname, '' as tmtcode
             FROM cdrug
             WHERE drugcode IS NOT NULL
             LIMIT 1000"
        );
        
        $jhcisDrugs = $stmt->fetchAll();
        
        // Filter out already mapped drugs
        $unmapped = [];
        
        foreach ($jhcisDrugs as $drug) {
            $stmt = $this->db->prepare(
                "SELECT id FROM jhcis_drug_mapping
                 WHERE jhcis_drug_code = ? AND hospital_id = ?"
            );
            $stmt->execute([$drug['drugcode'], $hospitalId]);
            
            if (!$stmt->fetch()) {
                $unmapped[] = $drug;
            }
        }
        
        return $unmapped;
    }
    
    /**
     * Find mapping candidates for JHCIS drug
     * 
     * @param array $jhcisDrug
     * @return array Sorted by confidence (highest first)
     */
    private function findMappingCandidates(array $jhcisDrug): array
    {
        $candidates = [];
        
        // Strategy 1: TMT Code exact match (highest confidence)
        if (!empty($jhcisDrug['tmtcode'])) {
            $tmtMatches = $this->matchByTMT($jhcisDrug['tmtcode']);
            foreach ($tmtMatches as $match) {
                $candidates[] = [
                    'drug_id' => $match['id'],
                    'name' => $match['name'],
                    'confidence' => 1.0,
                    'match_type' => 'TMT_EXACT'
                ];
            }
        }
        
        // Strategy 2: Drug name fuzzy matching
        if (!empty($jhcisDrug['drugname'])) {
            $nameMatches = $this->matchByName($jhcisDrug['drugname']);
            foreach ($nameMatches as $match) {
                $candidates[] = [
                    'drug_id' => $match['id'],
                    'name' => $match['name'],
                    'confidence' => $match['similarity'],
                    'match_type' => 'NAME_FUZZY'
                ];
            }
        }
        
        // Strategy 3: Generic name matching
        if (!empty($jhcisDrug['genericname'])) {
            $genericMatches = $this->matchByGenericName($jhcisDrug['genericname']);
            foreach ($genericMatches as $match) {
                $candidates[] = [
                    'drug_id' => $match['id'],
                    'name' => $match['name'],
                    'confidence' => $match['similarity'] * 0.9, // Slightly lower confidence
                    'match_type' => 'GENERIC_FUZZY'
                ];
            }
        }
        
        // Remove duplicates and sort by confidence
        $candidates = $this->deduplicateAndSort($candidates);
        
        return $candidates;
    }
    
    /**
     * Match by TMT code
     * 
     * @param string $tmtCode
     * @return array
     */
    private function matchByTMT(string $tmtCode): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, name, code
             FROM drugs
             WHERE code = ? OR tmt_code = ?"
        );
        $stmt->execute([$tmtCode, $tmtCode]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Match by drug name (fuzzy)
     * 
     * @param string $name
     * @return array
     */
    private function matchByName(string $name): array
    {
        // Clean name
        $cleanName = $this->cleanDrugName($name);
        
        // Get all drugs
        $stmt = $this->db->query("SELECT id, name FROM drugs");
        $allDrugs = $stmt->fetchAll();
        
        $matches = [];
        
        foreach ($allDrugs as $drug) {
            $cleanDrugName = $this->cleanDrugName($drug['name']);
            $similarity = $this->calculateSimilarity($cleanName, $cleanDrugName);
            
            if ($similarity >= 0.7) { // 70% similarity threshold
                $matches[] = [
                    'id' => $drug['id'],
                    'name' => $drug['name'],
                    'similarity' => $similarity
                ];
            }
        }
        
        // Sort by similarity
        usort($matches, fn($a, $b) => $b['similarity'] <=> $a['similarity']);
        
        return array_slice($matches, 0, 5); // Top 5 matches
    }
    
    /**
     * Match by generic name
     * 
     * @param string $genericName
     * @return array
     */
    private function matchByGenericName(string $genericName): array
    {
        $cleanGeneric = $this->cleanDrugName($genericName);
        
        $stmt = $this->db->query("SELECT id, name, generic_name FROM drugs WHERE generic_name IS NOT NULL");
        $allDrugs = $stmt->fetchAll();
        
        $matches = [];
        
        foreach ($allDrugs as $drug) {
            if (empty($drug['generic_name'])) continue;
            
            $cleanDrugGeneric = $this->cleanDrugName($drug['generic_name']);
            $similarity = $this->calculateSimilarity($cleanGeneric, $cleanDrugGeneric);
            
            if ($similarity >= 0.75) {
                $matches[] = [
                    'id' => $drug['id'],
                    'name' => $drug['name'],
                    'similarity' => $similarity
                ];
            }
        }
        
        usort($matches, fn($a, $b) => $b['similarity'] <=> $a['similarity']);
        
        return array_slice($matches, 0, 5);
    }
    
    /**
     * Clean drug name for comparison
     * 
     * @param string $name
     * @return string
     */
    private function cleanDrugName(string $name): string
    {
        // Remove common suffixes/prefixes
        $name = preg_replace('/\s+(tab|cap|inj|syrup|cream|ointment|solution)/i', '', $name);
        
        // Remove dosage information
        $name = preg_replace('/\d+\s*(mg|mcg|g|ml|%)/i', '', $name);
        
        // Remove special characters
        $name = preg_replace('/[^a-zA-Zก-๙0-9\s]/', '', $name);
        
        // Normalize whitespace
        $name = preg_replace('/\s+/', ' ', $name);
        
        return trim(strtolower($name));
    }
    
    /**
     * Calculate similarity between two strings
     * 
     * @param string $str1
     * @param string $str2
     * @return float 0-1
     */
    private function calculateSimilarity(string $str1, string $str2): float
    {
        // Levenshtein distance
        $lev = levenshtein($str1, $str2);
        $maxLen = max(strlen($str1), strlen($str2));
        
        if ($maxLen === 0) return 1.0;
        
        $levSimilarity = 1 - ($lev / $maxLen);
        
        // Similar text
        similar_text($str1, $str2, $percent);
        $similarTextScore = $percent / 100;
        
        // Combined score (weighted average)
        return ($levSimilarity * 0.6) + ($similarTextScore * 0.4);
    }
    
    /**
     * Deduplicate and sort candidates
     * 
     * @param array $candidates
     * @return array
     */
    private function deduplicateAndSort(array $candidates): array
    {
        $unique = [];
        
        foreach ($candidates as $candidate) {
            $drugId = $candidate['drug_id'];
            
            if (!isset($unique[$drugId]) || $candidate['confidence'] > $unique[$drugId]['confidence']) {
                $unique[$drugId] = $candidate;
            }
        }
        
        $result = array_values($unique);
        usort($result, fn($a, $b) => $b['confidence'] <=> $a['confidence']);
        
        return $result;
    }
    
    /**
     * Apply mappings
     * 
     * @param array $mappings
     * @param int $hospitalId
     * @return array
     */
    public function applyMappings(array $mappings, int $hospitalId): array
    {
        $applied = 0;
        $failed = 0;
        
        foreach ($mappings as $mapping) {
            try {
                $stmt = $this->db->prepare(
                    "INSERT INTO jhcis_drug_mapping 
                     (hospital_id, jhcis_drug_code, jhcis_drug_name, drugmuk_drug_id, confidence_score, mapping_method, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, NOW())"
                );
                
                $stmt->execute([
                    $hospitalId,
                    $mapping['jhcis_code'],
                    $mapping['jhcis_name'],
                    $mapping['drugmuk_id'],
                    $mapping['confidence'],
                    $mapping['match_type']
                ]);
                
                $applied++;
                
            } catch (\Exception $e) {
                $failed++;
                $this->logger->error("Failed to apply mapping", [
                    'jhcis_code' => $mapping['jhcis_code'],
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return [
            'applied' => $applied,
            'failed' => $failed
        ];
    }
}
