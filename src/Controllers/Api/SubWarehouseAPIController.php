<?php

namespace App\Controllers\Api;

use App\Core\Database;

class SubWarehouseAPIController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Save formula configuration for a subwarehouse
     * POST /api/subwarehouse/formula/save
     */
    public function saveFormula()
    {
        header('Content-Type: application/json');

        try {
            // Get JSON input
            $input = json_decode(file_get_contents('php://input'), true);

            // Validate required fields
            if (!isset($input['subwarehouse_id']) || !isset($input['formula_type'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Missing required fields: subwarehouse_id, formula_type'
                ]);
                return;
            }

            $subwarehouseId = (int)$input['subwarehouse_id'];
            $formulaType = $input['formula_type'];
            $config = $input['config'] ?? [];

            // Validate formula type
            $validTypes = ['max_min', 'average_usage', 'custom'];
            if (!in_array($formulaType, $validTypes)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid formula_type. Must be one of: ' . implode(', ', $validTypes)
                ]);
                return;
            }

            // Verify subwarehouse exists
            $stmt = $this->db->prepare("SELECT id FROM subwarehouses WHERE id = ?");
            $stmt->execute([$subwarehouseId]);
            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Subwarehouse not found'
                ]);
                return;
            }

            // Map formula type to database enum
            $formulaTypeMap = [
                'max_min' => 'min_max',
                'average_usage' => 'consumption_based',
                'custom' => 'fixed'
            ];
            $dbFormulaType = $formulaTypeMap[$formulaType] ?? 'min_max';

            // Extract configuration parameters
            $bufferPercentage = $config['buffer_percentage'] ?? 10;
            $periodDays = $config['period_days'] ?? 30;

            // Check if formula already exists for this subwarehouse
            $stmt = $this->db->prepare("
                SELECT id FROM subwarehouse_formulas 
                WHERE subwarehouse_id = ? AND drug_id IS NULL
                LIMIT 1
            ");
            $stmt->execute([$subwarehouseId]);
            $existing = $stmt->fetch();

            if ($existing) {
                // Update existing formula
                $stmt = $this->db->prepare("
                    UPDATE subwarehouse_formulas 
                    SET formula_type = ?,
                        min_days_supply = ?,
                        max_days_supply = ?,
                        safety_stock = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $dbFormulaType,
                    $periodDays,
                    $periodDays + 7, // max is min + 7 days
                    $bufferPercentage,
                    $existing['id']
                ]);
            } else {
                // Insert new formula
                $stmt = $this->db->prepare("
                    INSERT INTO subwarehouse_formulas 
                    (subwarehouse_id, drug_id, formula_type, min_days_supply, max_days_supply, safety_stock)
                    VALUES (?, NULL, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $subwarehouseId,
                    $dbFormulaType,
                    $periodDays,
                    $periodDays + 7,
                    $bufferPercentage
                ]);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Formula configuration saved successfully',
                'data' => [
                    'subwarehouse_id' => $subwarehouseId,
                    'formula_type' => $formulaType,
                    'config' => $config
                ]
            ]);

        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
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
     * Get formula configuration for a subwarehouse
     * GET /api/subwarehouse/formula/{id}
     */
    public function getFormula($id)
    {
        header('Content-Type: application/json');

        try {
            $stmt = $this->db->prepare("
                SELECT * FROM subwarehouse_formulas 
                WHERE subwarehouse_id = ? AND drug_id IS NULL
                LIMIT 1
            ");
            $stmt->execute([$id]);
            $formula = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($formula) {
                // Map database enum back to API format
                $formulaTypeMap = [
                    'min_max' => 'max_min',
                    'consumption_based' => 'average_usage',
                    'fixed' => 'custom'
                ];

                echo json_encode([
                    'success' => true,
                    'data' => [
                        'formula_type' => $formulaTypeMap[$formula['formula_type']] ?? 'max_min',
                        'config' => [
                            'buffer_percentage' => (int)$formula['safety_stock'],
                            'period_days' => (int)$formula['min_days_supply']
                        ]
                    ]
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'formula_type' => 'max_min',
                        'config' => [
                            'buffer_percentage' => 10,
                            'period_days' => 30
                        ]
                    ]
                ]);
            }

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }
}
