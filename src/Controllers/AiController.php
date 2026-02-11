<?php

namespace App\Controllers;

use App\Services\AiService;
use App\Core\Controller;

class AiController extends Controller {
    private $aiService;

    public function __construct() {
        $this->aiService = new AiService();
    }

    public function handle() {
        header('Content-Type: application/json');
        
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method Not Allowed']);
                exit;
            }

            // Handle both JSON and Multipart/Form-data
            $message = $_POST['message'] ?? '';
            $file = $_FILES['file'] ?? null;

            if (empty($message) && empty($file)) {
                 $input = json_decode(file_get_contents('php://input'), true);
                 $message = $input['message'] ?? '';
            }

            if (empty($message) && empty($file)) {
                echo json_encode(['type' => 'text', 'message' => 'สวัสดีครับ มีอะไรให้ช่วยไหมครับ?']);
                exit;
            }

            $reply = $this->aiService->processMessage($message, $file);
            echo json_encode($reply);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'type' => 'text', 
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ]);
        }
        exit;
    }
}
