<?php

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;

class JhcisController extends Controller {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function syncInventory() {
        $this->checkApiKey();

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $this->json(['status' => 'error', 'message' => 'Invalid JSON']);
        }

        $hospitalCode = $input['hospital_code'] ?? null;
        $items = $input['items'] ?? [];

        if (!$hospitalCode || empty($items)) {
            $this->json(['status' => 'error', 'message' => 'Missing required data']);
        }

        // Process items
        $sql = "INSERT INTO inventory (hospital_code, drug_id, lot_no, expire_date, quantity, cost_price, location, received_date) 
                VALUES (:hospital_code, :drug_id, :lot_no, :expire_date, :quantity, :cost_price, :location, CURDATE())
                ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), expire_date = VALUES(expire_date)";
        
        $stmt = $this->db->prepare($sql);

        foreach ($items as $item) {
            // Find drug_id by code (assuming JHCIS sends standard codes like TMT)
            $drugId = $this->findDrugIdByCode($item['drug_code']);
            $location = $item['location'] ?? 'sub'; // Default to sub (external)
            
            if ($drugId) {
                $stmt->execute([
                    'hospital_code' => $hospitalCode,
                    'drug_id' => $drugId,
                    'lot_no' => $item['lot_no'],
                    'expire_date' => $item['expire_date'],
                    'quantity' => $item['quantity'],
                    'cost_price' => $item['cost_price'] ?? 0,
                    'location' => $location
                ]);
            }
        }

        $this->json(['status' => 'success', 'message' => 'Inventory synced successfully']);
    }

    public function syncDispensing() {
        $this->checkApiKey();
        // Placeholder for dispensing sync logic
        $this->json(['status' => 'success', 'message' => 'Dispensing synced (Mock)']);
    }

    private function checkApiKey() {
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
        
        // Simple hardcoded key for demo
        if ($apiKey !== 'drugmuk-secret-key') {
            http_response_code(401);
            $this->json(['status' => 'error', 'message' => 'Unauthorized']);
        }
    }

    private function findDrugIdByCode($code) {
        $stmt = $this->db->prepare("SELECT id FROM drugs WHERE code = :code");
        $stmt->execute(['code' => $code]);
        $result = $stmt->fetch();
        return $result ? $result['id'] : null;
    }
}
