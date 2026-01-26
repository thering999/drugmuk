<?php
/**
 * API Controller
 * RESTful API endpoints for mobile/external integrations
 */

namespace App\Controllers;

class APIController
{
    private $db;
    private $rateLimiter;
    
    public function __construct()
    {
        global $db;
        $this->db = $db;
        $this->rateLimiter = new \App\Middleware\RateLimiter();
        
        // Set JSON response headers
        header('Content-Type: application/json; charset=UTF-8');
        
        // Apply rate limiting
        $this->rateLimiter->handle();
    }
    
    /**
     * GET /api/drugs - Get all drugs
     */
    public function getDrugs(): void
    {
        try {
            $search = $_GET['search'] ?? '';
            $page = (int) ($_GET['page'] ?? 1);
            $limit = (int) ($_GET['limit'] ?? 20);
            $offset = ($page - 1) * $limit;
            
            $where = 'is_active = 1';
            $params = [];
            
            if ($search) {
                $where .= ' AND (code LIKE :search OR name LIKE :search)';
                $params['search'] = "%{$search}%";
            }
            
            // Get total count
            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM drugs WHERE {$where}");
            $countStmt->execute($params);
            $total = (int) $countStmt->fetchColumn();
            
            // Get data
            $stmt = $this->db->prepare("
                SELECT id, code, name, generic_name, unit, price, min_level, max_level
                FROM drugs 
                WHERE {$where}
                ORDER BY name
                LIMIT :limit OFFSET :offset
            ");
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();
            
            $drugs = $stmt->fetchAll();
            
            $this->jsonResponse([
                'success' => true,
                'data' => $drugs,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
        } catch (\Exception $e) {
            $this->jsonError($e->getMessage(), 500);
        }
    }
    
    /**
     * GET /api/inventory - Get inventory status
     */
    public function getInventory(): void
    {
        try {
            $drugId = $_GET['drug_id'] ?? null;
            
            if ($drugId) {
                // Get specific drug inventory
                $stmt = $this->db->prepare("
                    SELECT 
                        i.id,
                        i.lot_no,
                        i.expire_date,
                        i.quantity,
                        dr.name as drug_name,
                        dr.unit
                    FROM inventory i
                    JOIN drugs dr ON i.drug_id = dr.id
                    WHERE i.drug_id = :drug_id AND i.quantity > 0
                    ORDER BY i.expire_date ASC
                ");
                $stmt->execute(['drug_id' => $drugId]);
            } else {
                // Get all inventory summary
                $stmt = $this->db->query("
                    SELECT 
                        dr.id as drug_id,
                        dr.code,
                        dr.name,
                        SUM(i.quantity) as total_quantity,
                        dr.unit,
                        dr.min_level,
                        CASE 
                            WHEN SUM(i.quantity) < dr.min_level THEN 'low'
                            ELSE 'normal'
                        END as status
                    FROM drugs dr
                    LEFT JOIN inventory i ON dr.id = i.drug_id
                    WHERE dr.is_active = 1
                    GROUP BY dr.id
                    ORDER BY dr.name
                ");
            }
            
            $inventory = $stmt->fetchAll();
            
            $this->jsonResponse([
                'success' => true,
                'data' => $inventory
            ]);
        } catch (\Exception $e) {
            $this->jsonError($e->getMessage(), 500);
        }
    }
    
    /**
     * GET /api/dashboard - Get dashboard statistics
     */
    public function getDashboard(): void
    {
        try {
            $analytics = new \App\Services\DashboardAnalyticsService($this->db);
            
            $data = [
                'overview' => $analytics->getOverview(),
                'top_drugs' => $analytics->getTopUsedDrugs(5),
                'expiring_soon' => $analytics->getExpiringDrugsAlert(30),
                'low_stock' => $analytics->getLowStockAlerts(),
            ];
            
            $this->jsonResponse([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            $this->jsonError($e->getMessage(), 500);
        }
    }
    
    /**
     * POST /api/dispensing - Create dispensing record
     */
    public function createDispensing(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            $required = ['patient_hn', 'patient_name', 'items'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    $this->jsonError("Missing required field: {$field}", 400);
                    return;
                }
            }
            
            $this->db->beginTransaction();
            
            // Create dispensing record
            $stmt = $this->db->prepare("
                INSERT INTO dispensing (patient_hn, patient_name, dispense_date, dispensed_by)
                VALUES (:patient_hn, :patient_name, NOW(), :dispensed_by)
            ");
            
            $stmt->execute([
                'patient_hn' => $input['patient_hn'],
                'patient_name' => $input['patient_name'],
                'dispensed_by' => $_SESSION['user_id'] ?? 1
            ]);
            
            $dispensingId = (int) $this->db->lastInsertId();
            
            // Add items
            foreach ($input['items'] as $item) {
                $stmt = $this->db->prepare("
                    INSERT INTO dispensing_items (dispensing_id, drug_id, inventory_id, quantity)
                    VALUES (:dispensing_id, :drug_id, :inventory_id, :quantity)
                ");
                
                $stmt->execute([
                    'dispensing_id' => $dispensingId,
                    'drug_id' => $item['drug_id'],
                    'inventory_id' => $item['inventory_id'],
                    'quantity' => $item['quantity']
                ]);
                
                // Update inventory
                $stmt = $this->db->prepare("
                    UPDATE inventory 
                    SET quantity = quantity - :quantity 
                    WHERE id = :inventory_id
                ");
                
                $stmt->execute([
                    'quantity' => $item['quantity'],
                    'inventory_id' => $item['inventory_id']
                ]);
            }
            
            $this->db->commit();
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Dispensing created successfully',
                'data' => ['id' => $dispensingId]
            ], 201);
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->jsonError($e->getMessage(), 500);
        }
    }
    
    /**
     * GET /api/reports/:type - Generate report
     */
    public function generateReport(string $type): void
    {
        try {
            $reportGen = new \App\Services\ReportGeneratorService($this->db);
            
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-t');
            
            $data = match($type) {
                'inventory' => $reportGen->generateInventorySummary(),
                'usage' => $reportGen->generateDrugUsageReport($startDate, $endDate),
                'expiry' => $reportGen->generateExpiryReport(6),
                'financial' => $reportGen->generateFinancialReport($startDate, $endDate),
                default => throw new \Exception('Invalid report type')
            };
            
            $this->jsonResponse([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            $this->jsonError($e->getMessage(), 500);
        }
    }
    
    /**
     * Helper: Send JSON response
     */
    private function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Helper: Send JSON error
     */
    private function jsonError(string $message, int $statusCode = 400): void
    {
        $this->jsonResponse([
            'success' => false,
            'error' => $message
        ], $statusCode);
    }
}
