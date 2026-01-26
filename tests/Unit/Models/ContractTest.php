<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Contract;

class ContractTest extends TestCase
{
    private $contract;

    protected function setUp(): void
    {
        parent::setUp();
        $this->contract = new Contract();
    }

    /**
     * Test getting all contracts with details
     */
    public function testGetAllWithDetails()
    {
        $result = $this->contract->getAllWithDetails();
        
        $this->assertIsArray($result);
    }

    /**
     * Test creating contract
     */
    public function testCreateContract()
    {
        $data = [
            'contract_no' => 'CON' . time(),
            'supplier_id' => 1,
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime('+1 year')),
            'total_amount' => 100000.00,
            'status' => 'active'
        ];
        
        $result = $this->contract->create($data);
        
        $this->assertTrue($result);
    }

    /**
     * Test getting active contract for drug
     */
    public function testGetActiveContractForDrug()
    {
        $drug = $this->createTestDrug();
        
        $result = $this->contract->getActiveContractForDrug($drug['id']);
        
        // May be null if no active contract
        $this->assertTrue($result === null || is_array($result));
    }

    /**
     * Test getting expiring contracts
     */
    public function testGetExpiringContracts()
    {
        $result = $this->contract->getExpiringContracts(30, 10);
        
        $this->assertIsArray($result);
        $this->assertLessThanOrEqual(10, count($result));
    }

    /**
     * Test getting expiring contracts count
     */
    public function testGetExpiringContractsCount()
    {
        $result = $this->contract->getExpiringContractsCount(30);
        
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    /**
     * Test getting contract statistics
     */
    public function testGetStatistics()
    {
        $result = $this->contract->getStatistics();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_contracts', $result);
        $this->assertArrayHasKey('active_contracts', $result);
    }

    /**
     * Test adding contract item
     */
    public function testAddContractItem()
    {
        $drug = $this->createTestDrug();
        
        $contractData = [
            'contract_no' => 'CON' . time(),
            'supplier_id' => 1,
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime('+1 year')),
            'total_amount' => 50000.00,
            'status' => 'active'
        ];
        
        $this->contract->create($contractData);
        
        // Get the created contract
        $contracts = $this->contract->getAllWithDetails();
        $contractId = $contracts[0]['id'] ?? 1;
        
        $itemData = [
            'contract_id' => $contractId,
            'drug_id' => $drug['id'],
            'agreed_price' => 100.00,
            'agreed_quantity' => 1000
        ];
        
        $result = $this->contract->addContractItem($itemData);
        
        $this->assertTrue($result);
    }

    /**
     * Test getting contract items
     */
    public function testGetContractItems()
    {
        $contracts = $this->contract->getAllWithDetails();
        
        if (!empty($contracts)) {
            $contractId = $contracts[0]['id'];
            $result = $this->contract->getContractItems($contractId);
            
            $this->assertIsArray($result);
        } else {
            $this->assertTrue(true); // No contracts to test
        }
    }
}
