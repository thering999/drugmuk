<?php

namespace App\Controllers\Api;

use App\Core\Database;
use PDO;

/**
 * Drugs API Controller
 * จัดการ API สำหรับข้อมูลยา
 */
class DrugsController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * GET /api/drugs
     * ดึงรายการยาทั้งหมด
     */
    public function index()
    {
        header('Content-Type: application/json');

        try {
            $search = $_GET['search'] ?? '';
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 1000;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

            $sql = "
                SELECT 
                    id,
                    code,
                    name,
                    generic_name,
                    unit,
                    price,
                    created_at
                FROM drugs
                WHERE 1=1
            ";

            $params = [];

            if ($search) {
                $sql .= " AND (code LIKE ? OR name LIKE ? OR generic_name LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }

            $sql .= " ORDER BY name ASC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $drugs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // นับจำนวนทั้งหมด
            $countSql = "SELECT COUNT(*) FROM drugs WHERE 1=1";
            $countParams = [];

            if ($search) {
                $countSql .= " AND (code LIKE ? OR name LIKE ? OR generic_name LIKE ?)";
                $countParams[] = $searchParam;
                $countParams[] = $searchParam;
                $countParams[] = $searchParam;
            }

            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($countParams);
            $total = $countStmt->fetchColumn();

            echo json_encode([
                'success' => true,
                'data' => $drugs,
                'total' => (int)$total,
                'limit' => $limit,
                'offset' => $offset
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * GET /api/drugs/{id}
     * ดึงข้อมูลยาตาม ID
     */
    public function show($id)
    {
        header('Content-Type: application/json');

        try {
            $stmt = $this->db->prepare("
                SELECT 
                    id,
                    code,
                    name,
                    generic_name,
                    unit,
                    price,
                    min_stock,
                    max_stock,
                    created_at,
                    updated_at
                FROM drugs
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            $drug = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$drug) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลยา'
                ]);
                return;
            }

            echo json_encode([
                'success' => true,
                'data' => $drug
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * GET /api/drugs/search
     * ค้นหายา
     */
    public function search()
    {
        header('Content-Type: application/json');

        try {
            $query = $_GET['q'] ?? '';
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;

            if (empty($query)) {
                echo json_encode([
                    'success' => true,
                    'data' => []
                ]);
                return;
            }

            $stmt = $this->db->prepare("
                SELECT 
                    id,
                    code,
                    name,
                    generic_name,
                    unit,
                    price
                FROM drugs
                WHERE code LIKE ? OR name LIKE ? OR generic_name LIKE ?
                ORDER BY name ASC
                LIMIT ?
            ");

            $searchParam = "%$query%";
            $stmt->execute([$searchParam, $searchParam, $searchParam, $limit]);
            $drugs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $drugs
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
