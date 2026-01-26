<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\FiscalYear;

class FiscalYearTest extends TestCase
{
    private $fiscalYear;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fiscalYear = new FiscalYear();
    }

    /**
     * Test getting all fiscal years
     */
    public function testGetAll()
    {
        $result = $this->fiscalYear->getAll();
        
        $this->assertIsArray($result);
    }

    /**
     * Test getting active fiscal years
     */
    public function testGetActiveYears()
    {
        $result = $this->fiscalYear->getActiveYears();
        
        $this->assertIsArray($result);
        
        foreach ($result as $year) {
            $this->assertEquals(1, $year['is_active']);
        }
    }

    /**
     * Test fiscal year structure
     */
    public function testFiscalYearStructure()
    {
        $result = $this->fiscalYear->getAll();
        
        if (!empty($result)) {
            $year = $result[0];
            
            $this->assertArrayHasKey('id', $year);
            $this->assertArrayHasKey('year', $year);
            $this->assertArrayHasKey('is_active', $year);
        } else {
            $this->assertTrue(true); // No fiscal years in database
        }
    }
}
