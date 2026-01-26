<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\PurchasingPlan;

class PurchasingPlanTest extends TestCase
{
    private $purchasingPlan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->purchasingPlan = new PurchasingPlan();
    }

    /**
     * Test getting all plans with details
     */
    public function testGetAllWithDetails()
    {
        $result = $this->purchasingPlan->getAllWithDetails();
        
        $this->assertIsArray($result);
    }

    /**
     * Test creating purchasing plan
     */
    public function testCreatePlan()
    {
        $drug = $this->createTestDrug();
        
        $data = [
            'fiscal_year_id' => 1,
            'drug_id' => $drug['id'],
            'planned_quantity' => 1000,
            'estimated_price' => 100.00,
            'total_amount' => 100000.00,
            'abc_class' => 'A',
            'ven_class' => 'V'
        ];
        
        $result = $this->purchasingPlan->create($data);
        
        $this->assertTrue($result);
    }

    /**
     * Test calculating plan from 3-year history
     */
    public function testCalculateFrom3YearHistory()
    {
        $fiscalYearId = 1;
        $increasePercent = 5.0;
        
        $result = $this->purchasingPlan->calculateFrom3YearHistory($fiscalYearId, $increasePercent);
        
        $this->assertIsArray($result);
    }

    /**
     * Test ABC analysis
     */
    public function testPerformABCAnalysis()
    {
        $plans = [
            ['drug_id' => 1, 'total_amount' => 100000],
            ['drug_id' => 2, 'total_amount' => 50000],
            ['drug_id' => 3, 'total_amount' => 10000]
        ];
        
        $result = $this->purchasingPlan->performABCAnalysis($plans);
        
        $this->assertIsArray($result);
        
        foreach ($result as $plan) {
            $this->assertArrayHasKey('abc_class', $plan);
            $this->assertContains($plan['abc_class'], ['A', 'B', 'C']);
        }
    }

    /**
     * Test VEN classification
     */
    public function testAssignVENClass()
    {
        $plans = [
            ['drug_id' => 1, 'drug_name' => 'ยา A'],
            ['drug_id' => 2, 'drug_name' => 'ยา B']
        ];
        
        $result = $this->purchasingPlan->assignVENClass($plans);
        
        $this->assertIsArray($result);
        
        foreach ($result as $plan) {
            $this->assertArrayHasKey('ven_class', $plan);
            $this->assertContains($plan['ven_class'], ['V', 'E', 'N']);
        }
    }

    /**
     * Test saving plans
     */
    public function testSavePlans()
    {
        $drug = $this->createTestDrug();
        
        $fiscalYearId = 1;
        $plans = [
            [
                'drug_id' => $drug['id'],
                'planned_quantity' => 500,
                'estimated_price' => 50.00,
                'total_amount' => 25000.00,
                'abc_class' => 'B',
                'ven_class' => 'E'
            ]
        ];
        
        $result = $this->purchasingPlan->savePlans($fiscalYearId, $plans);
        
        $this->assertTrue($result);
    }

    /**
     * Test exporting to CSV
     */
    public function testExportToCSV()
    {
        $plans = [
            [
                'drug_code' => 'D001',
                'drug_name' => 'ยา A',
                'planned_quantity' => 1000,
                'estimated_price' => 100.00,
                'total_amount' => 100000.00,
                'abc_class' => 'A',
                'ven_class' => 'V'
            ]
        ];
        
        $result = $this->purchasingPlan->exportToCSV($plans);
        
        $this->assertIsString($result);
        $this->assertStringContainsString('D001', $result);
    }

    /**
     * Test getting quarterly adjustments
     */
    public function testGetQuarterlyAdjustments()
    {
        $planId = 1;
        
        $result = $this->purchasingPlan->getQuarterlyAdjustments($planId);
        
        $this->assertIsArray($result);
    }

    /**
     * Test saving quarterly adjustment
     */
    public function testSaveQuarterlyAdjustment()
    {
        $drug = $this->createTestDrug();
        
        $planData = [
            'fiscal_year_id' => 1,
            'drug_id' => $drug['id'],
            'planned_quantity' => 1000,
            'estimated_price' => 100.00,
            'total_amount' => 100000.00,
            'abc_class' => 'A',
            'ven_class' => 'V'
        ];
        
        $this->purchasingPlan->create($planData);
        
        $adjustmentData = [
            'plan_id' => 1,
            'quarter' => 1,
            'adjusted_quantity' => 250,
            'reason' => 'ปรับแผนตามความต้องการ'
        ];
        
        $result = $this->purchasingPlan->saveQuarterlyAdjustment($adjustmentData);
        
        $this->assertTrue($result);
    }
}
