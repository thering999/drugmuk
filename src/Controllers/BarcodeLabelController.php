<?php
/**
 * Barcode Label Controller
 * 
 * Handles barcode label generation and printing
 * 
 * @package Drugmuk
 * @subpackage Controllers
 * @version 1.0
 * @since Phase 6
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Services\BarcodeLabelService;

class BarcodeLabelController extends Controller
{
    private $labelService;
    
    public function __construct()
    {
        $this->labelService = new BarcodeLabelService();
    }
    
    /**
     * Show label printing page
     */
    public function index()
    {
        $history = $this->labelService->getPrintHistory(20);
        
        $this->view('labels/index', [
            'history' => $history
        ]);
    }
    
    /**
     * Generate single label
     * GET /labels/generate/{drugId}
     */
    public function generate($drugId)
    {
        try {
            $lotNo = $_GET['lot'] ?? null;
            $quantity = $_GET['quantity'] ?? 1;
            
            $labelData = $this->labelService->generateLabel($drugId, $lotNo, $quantity);
            
            $this->view('labels/preview', [
                'label' => $labelData
            ]);
            
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/labels');
        }
    }
    
    /**
     * Generate batch labels
     * POST /labels/batch
     */
    public function batch()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/labels');
            return;
        }
        
        try {
            $items = json_decode($_POST['items'], true);
            
            if (!$items || !is_array($items)) {
                throw new \Exception('Invalid items data');
            }
            
            $labels = $this->labelService->batchGenerate($items);
            
            $this->view('labels/batch_preview', [
                'labels' => $labels
            ]);
            
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/labels');
        }
    }
    
    /**
     * Print label (log and return printable HTML)
     * POST /labels/print
     */
    public function print()
    {
        header('Content-Type: application/json');
        
        try {
            $drugId = $_POST['drug_id'] ?? null;
            $lotNo = $_POST['lot_no'] ?? null;
            $quantity = $_POST['quantity'] ?? 1;
            
            if (!$drugId) {
                throw new \Exception('Drug ID is required');
            }
            
            // Generate label
            $labelData = $this->labelService->generateLabel($drugId, $lotNo, $quantity);
            
            // Log printing
            $this->labelService->logPrinting(
                $drugId,
                $quantity,
                $_SESSION['user_id'] ?? 1
            );
            
            echo json_encode([
                'success' => true,
                'label' => $labelData
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
     * Get label template for drug
     * GET /api/labels/template/{drugId}
     */
    public function getTemplate($drugId)
    {
        header('Content-Type: application/json');
        
        try {
            $labelData = $this->labelService->generateLabel($drugId);
            
            echo json_encode([
                'success' => true,
                'data' => $labelData
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
     * Get printing history
     * GET /api/labels/history
     */
    public function history()
    {
        header('Content-Type: application/json');
        
        try {
            $limit = $_GET['limit'] ?? 50;
            $history = $this->labelService->getPrintHistory($limit);
            
            echo json_encode([
                'success' => true,
                'data' => $history
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
