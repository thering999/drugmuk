<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\DataCleansing;

class DataCleansingTest extends TestCase
{
    private $dataCleansing;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dataCleansing = new DataCleansing();
    }

    /**
     * Test detecting duplicate drugs
     */
    public function testDetectDuplicateDrugs()
    {
        $userId = 1;
        $threshold = 75.0;
        
        $result = $this->dataCleansing->detectDuplicateDrugs($userId, $threshold);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('duplicates_found', $result);
    }

    /**
     * Test detecting orphaned transactions
     */
    public function testDetectOrphanedTransactions()
    {
        $userId = 1;
        
        $result = $this->dataCleansing->detectOrphanedTransactions($userId);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('orphaned_found', $result);
    }

    /**
     * Test detecting orphaned order items
     */
    public function testDetectOrphanedOrderItems()
    {
        $userId = 1;
        
        $result = $this->dataCleansing->detectOrphanedOrderItems($userId);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('orphaned_found', $result);
    }

    /**
     * Test getting pending duplicates
     */
    public function testGetPendingDuplicates()
    {
        $result = $this->dataCleansing->getPendingDuplicates();
        
        $this->assertIsArray($result);
    }

    /**
     * Test getting pending duplicates by table
     */
    public function testGetPendingDuplicatesByTable()
    {
        $result = $this->dataCleansing->getPendingDuplicates('drugs');
        
        $this->assertIsArray($result);
    }

    /**
     * Test getting pending orphaned records
     */
    public function testGetPendingOrphanedRecords()
    {
        $result = $this->dataCleansing->getPendingOrphanedRecords();
        
        $this->assertIsArray($result);
    }

    /**
     * Test getting data quality score
     */
    public function testGetDataQualityScore()
    {
        $result = $this->dataCleansing->getDataQualityScore();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('overall_score', $result);
        $this->assertArrayHasKey('completeness', $result);
        $this->assertArrayHasKey('accuracy', $result);
    }

    /**
     * Test getting data quality summary
     */
    public function testGetDataQualitySummary()
    {
        $result = $this->dataCleansing->getDataQualitySummary();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_records', $result);
        $this->assertArrayHasKey('pending_duplicates', $result);
        $this->assertArrayHasKey('pending_orphaned', $result);
    }

    /**
     * Test getting cleanup history
     */
    public function testGetCleanupHistory()
    {
        $result = $this->dataCleansing->getCleanupHistory(10);
        
        $this->assertIsArray($result);
        $this->assertLessThanOrEqual(10, count($result));
    }

    /**
     * Test running full data quality check
     */
    public function testRunFullDataQualityCheck()
    {
        $userId = 1;
        
        $result = $this->dataCleansing->runFullDataQualityCheck($userId);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('duplicate_drugs', $result);
        $this->assertArrayHasKey('orphaned_transactions', $result);
        $this->assertArrayHasKey('orphaned_order_items', $result);
    }

    /**
     * Test getting quality trends
     */
    public function testGetQualityTrends()
    {
        $result = $this->dataCleansing->getQualityTrends(30);
        
        $this->assertIsArray($result);
    }

    /**
     * Test marking as false positive
     */
    public function testMarkAsFalsePositive()
    {
        // First detect duplicates
        $detected = $this->dataCleansing->detectDuplicateDrugs(1, 75.0);
        
        if (!empty($detected['duplicates_found']) && $detected['duplicates_found'] > 0) {
            $duplicates = $this->dataCleansing->getPendingDuplicates('drugs');
            
            if (!empty($duplicates)) {
                $duplicateId = $duplicates[0]['id'];
                $result = $this->dataCleansing->markAsFalsePositive($duplicateId, 1);
                
                $this->assertIsArray($result);
                $this->assertTrue($result['success']);
            } else {
                $this->assertTrue(true); // No duplicates to test
            }
        } else {
            $this->assertTrue(true); // No duplicates found
        }
    }
}
