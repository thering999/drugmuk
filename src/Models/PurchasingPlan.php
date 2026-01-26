<?php

namespace App\Models;

use App\Core\Model;

class PurchasingPlan extends Model {
    protected $table = 'purchasing_plans';

    /**
     * Get all plans with details
     */
    public function getAllWithDetails() {
        $sql = "SELECT pp.*, d.name as drug_name, d.code as drug_code, fy.year as fiscal_year 
                FROM {$this->table} pp 
                JOIN drugs d ON pp.drug_id = d.id 
                JOIN fiscal_years fy ON pp.fiscal_year_id = fy.id
                ORDER BY fy.year DESC, d.name ASC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Create purchasing plan
     */
    public function create($data) {
        $sql = "INSERT INTO {$this->table} (fiscal_year_id, drug_id, quantity_plan, budget_plan, abc_class, ven_class) 
                VALUES (:fiscal_year_id, :drug_id, :quantity_plan, :budget_plan, :abc_class, :ven_class)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Calculate purchasing plan from 3-year historical data
     * 
     * @param int $fiscalYearId Target fiscal year
     * @param float $increasePercent Percentage increase (default 0%)
     * @return array Calculated plans
     */
    public function calculateFrom3YearHistory($fiscalYearId, $increasePercent = 0) {
        // Get 3-year historical usage from transactions
        $sql = "SELECT 
                    d.id as drug_id,
                    d.code,
                    d.name,
                    d.price,
                    d.category,
                    -- Year 1 (3 years ago)
                    COALESCE(SUM(CASE 
                        WHEN YEAR(t.transaction_date) = YEAR(CURDATE()) - 3 
                        THEN t.quantity 
                        ELSE 0 
                    END), 0) as year1_qty,
                    -- Year 2 (2 years ago)
                    COALESCE(SUM(CASE 
                        WHEN YEAR(t.transaction_date) = YEAR(CURDATE()) - 2 
                        THEN t.quantity 
                        ELSE 0 
                    END), 0) as year2_qty,
                    -- Year 3 (last year)
                    COALESCE(SUM(CASE 
                        WHEN YEAR(t.transaction_date) = YEAR(CURDATE()) - 1 
                        THEN t.quantity 
                        ELSE 0 
                    END), 0) as year3_qty,
                    -- Total 3 years
                    COALESCE(SUM(t.quantity), 0) as total_3years
                FROM drugs d
                LEFT JOIN transactions t ON d.id = t.drug_id 
                    AND t.transaction_type IN ('dispense', 'transfer_out')
                    AND t.transaction_date >= DATE_SUB(CURDATE(), INTERVAL 3 YEAR)
                WHERE d.is_active = 1
                GROUP BY d.id, d.code, d.name, d.price, d.category
                HAVING total_3years > 0
                ORDER BY d.name";
        
        $stmt = $this->db->query($sql);
        $historicalData = $stmt->fetchAll();

        $plans = [];
        foreach ($historicalData as $drug) {
            // Calculate average per year
            $avgYearly = ($drug['year1_qty'] + $drug['year2_qty'] + $drug['year3_qty']) / 3;
            
            // Convert to 12 months in current year
            $plannedQty = ceil($avgYearly);
            
            // Apply increase percentage
            if ($increasePercent > 0) {
                $plannedQty = ceil($plannedQty * (1 + ($increasePercent / 100)));
            }
            
            // Calculate minimum stock (1 month average)
            $minStock = ceil($avgYearly / 12);
            
            // Calculate budget
            $budget = $plannedQty * $drug['price'];
            
            $plans[] = [
                'drug_id' => $drug['drug_id'],
                'drug_code' => $drug['code'],
                'drug_name' => $drug['name'],
                'year1_qty' => $drug['year1_qty'],
                'year2_qty' => $drug['year2_qty'],
                'year3_qty' => $drug['year3_qty'],
                'avg_yearly' => $avgYearly,
                'planned_qty' => $plannedQty,
                'min_stock' => $minStock,
                'unit_price' => $drug['price'],
                'budget' => $budget,
                'category' => $drug['category']
            ];
        }

        return $plans;
    }

    /**
     * ABC Analysis
     * A = 80% of value (top items)
     * B = 15% of value (medium items)
     * C = 5% of value (low items)
     */
    public function performABCAnalysis($plans) {
        // Sort by budget descending
        usort($plans, function($a, $b) {
            return $b['budget'] <=> $a['budget'];
        });

        // Calculate total budget
        $totalBudget = array_sum(array_column($plans, 'budget'));
        
        $cumulativePercent = 0;
        foreach ($plans as &$plan) {
            $percentOfTotal = ($plan['budget'] / $totalBudget) * 100;
            $cumulativePercent += $percentOfTotal;
            
            if ($cumulativePercent <= 80) {
                $plan['abc_class'] = 'A';
            } elseif ($cumulativePercent <= 95) {
                $plan['abc_class'] = 'B';
            } else {
                $plan['abc_class'] = 'C';
            }
            
            $plan['percent_of_total'] = $percentOfTotal;
            $plan['cumulative_percent'] = $cumulativePercent;
        }

        return $plans;
    }

    /**
     * VEN Classification
     * V = Vital (จำเป็นต่อชีวิต)
     * E = Essential (จำเป็นต่อการรักษา)
     * N = Non-essential (ไม่จำเป็น)
     * 
     * Note: This is a placeholder. In real implementation, 
     * VEN should be classified by pharmacists based on drug importance
     */
    public function assignVENClass($plans) {
        foreach ($plans as &$plan) {
            // Default classification based on category
            // This should be customized based on hospital policy
            $category = strtoupper($plan['category'] ?? '');
            
            if (strpos($category, 'ED') !== false || strpos($category, 'VITAL') !== false) {
                $plan['ven_class'] = 'V'; // Vital
            } elseif (strpos($category, 'NED') !== false || strpos($category, 'ESSENTIAL') !== false) {
                $plan['ven_class'] = 'E'; // Essential
            } else {
                $plan['ven_class'] = 'N'; // Non-essential
            }
        }

        return $plans;
    }

    /**
     * Save calculated plans to database
     */
    public function savePlans($fiscalYearId, $plans) {
        try {
            $this->db->beginTransaction();

            // Delete existing plans for this fiscal year
            $deleteSql = "DELETE FROM {$this->table} WHERE fiscal_year_id = ?";
            $deleteStmt = $this->db->prepare($deleteSql);
            $deleteStmt->execute([$fiscalYearId]);

            // Insert new plans
            $insertSql = "INSERT INTO {$this->table} 
                         (fiscal_year_id, drug_id, quantity_plan, budget_plan, abc_class, ven_class, min_stock)
                         VALUES (?, ?, ?, ?, ?, ?, ?)";
            $insertStmt = $this->db->prepare($insertSql);

            foreach ($plans as $plan) {
                $insertStmt->execute([
                    $fiscalYearId,
                    $plan['drug_id'],
                    $plan['planned_qty'],
                    $plan['budget'],
                    $plan['abc_class'] ?? 'C',
                    $plan['ven_class'] ?? 'N',
                    $plan['min_stock']
                ]);
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Save plans failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Export plans to CSV for Excel
     */
    public function exportToCSV($plans, $filename = 'purchasing_plan.csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel UTF-8 support
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        fputcsv($output, [
            'รหัสยา',
            'ชื่อยา',
            'ปี 1',
            'ปี 2', 
            'ปี 3',
            'เฉลี่ย/ปี',
            'แผนซื้อ',
            'สต็อกขั้นต่ำ',
            'ราคา/หน่วย',
            'งบประมาณ',
            'ABC',
            'VEN',
            'หมวดหมู่'
        ]);
        
        // Data
        foreach ($plans as $plan) {
            fputcsv($output, [
                $plan['drug_code'],
                $plan['drug_name'],
                $plan['year1_qty'],
                $plan['year2_qty'],
                $plan['year3_qty'],
                number_format($plan['avg_yearly'], 2),
                $plan['planned_qty'],
                $plan['min_stock'],
                number_format($plan['unit_price'], 2),
                number_format($plan['budget'], 2),
                $plan['abc_class'] ?? '',
                $plan['ven_class'] ?? '',
                $plan['category'] ?? ''
            ]);
        }
        
        fclose($output);
        exit;
    }

    /**
     * Import plans from CSV
     */
    public function importFromCSV($file, $fiscalYearId) {
        if (!file_exists($file)) {
            return ['success' => false, 'message' => 'File not found'];
        }

        $handle = fopen($file, 'r');
        if (!$handle) {
            return ['success' => false, 'message' => 'Cannot open file'];
        }

        // Skip header
        fgetcsv($handle);

        $imported = 0;
        $errors = [];

        try {
            $this->db->beginTransaction();

            while (($data = fgetcsv($handle)) !== false) {
                // Map CSV columns
                $drugCode = $data[0];
                $plannedQty = (int)$data[6];
                $minStock = (int)$data[7];
                $abcClass = $data[10] ?? 'C';
                $venClass = $data[11] ?? 'N';

                // Find drug by code
                $drugStmt = $this->db->prepare("SELECT id, price FROM drugs WHERE code = ?");
                $drugStmt->execute([$drugCode]);
                $drug = $drugStmt->fetch();

                if (!$drug) {
                    $errors[] = "Drug code {$drugCode} not found";
                    continue;
                }

                // Update or insert plan
                $sql = "INSERT INTO {$this->table} 
                       (fiscal_year_id, drug_id, quantity_plan, budget_plan, abc_class, ven_class, min_stock)
                       VALUES (?, ?, ?, ?, ?, ?, ?)
                       ON DUPLICATE KEY UPDATE
                       quantity_plan = VALUES(quantity_plan),
                       budget_plan = VALUES(budget_plan),
                       abc_class = VALUES(abc_class),
                       ven_class = VALUES(ven_class),
                       min_stock = VALUES(min_stock)";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    $fiscalYearId,
                    $drug['id'],
                    $plannedQty,
                    $plannedQty * $drug['price'],
                    $abcClass,
                    $venClass,
                    $minStock
                ]);

                $imported++;
            }

            $this->db->commit();
            fclose($handle);

            return [
                'success' => true,
                'imported' => $imported,
                'errors' => $errors
            ];
        } catch (\Exception $e) {
            $this->db->rollBack();
            fclose($handle);
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get quarterly adjustments
     */
    public function getQuarterlyAdjustments($planId) {
        $sql = "SELECT * FROM purchasing_plan_adjustments 
                WHERE purchasing_plan_id = ? 
                ORDER BY quarter ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$planId]);
        return $stmt->fetchAll();
    }

    /**
     * Save quarterly adjustment
     */
    public function saveQuarterlyAdjustment($data) {
        $sql = "INSERT INTO purchasing_plan_adjustments 
               (purchasing_plan_id, quarter, adjusted_quantity, adjusted_budget, adjustment_reason, adjusted_by)
               VALUES (:plan_id, :quarter, :quantity, :budget, :reason, :user_id)
               ON DUPLICATE KEY UPDATE
               adjusted_quantity = VALUES(adjusted_quantity),
               adjusted_budget = VALUES(adjusted_budget),
               adjustment_reason = VALUES(adjustment_reason)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }
}
