    /**
     * Test Connection By ID (API)
     * GET /api/jhcis/test-connection/{id}
     */
    public function testConnectionById($id)
    {
        header('Content-Type: application/json');
        
        try {
            $syncService = new \App\Services\JHCIS\JHCISSyncService();
            $result = $syncService->testConnection($id);
            
            echo json_encode($result);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    /**
     * Sync Now (API)
     * POST /api/jhcis/sync-now/{id}
     */
    public function syncNow($id)
    {
        header('Content-Type: application/json');
        
        try {
            $syncService = new \App\Services\JHCIS\JHCISSyncService();
            
            // Sync last 30 days
            $fromDate = date('Y-m-d', strtotime('-30 days'));
            $toDate = date('Y-m-d');
            
            $result = $syncService->syncDispensing($id, $fromDate, $toDate);
            
            echo json_encode($result);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
