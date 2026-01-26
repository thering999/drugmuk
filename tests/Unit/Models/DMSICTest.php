<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\DMSIC;

class DMSICTest extends TestCase
{
    private $dmsic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dmsic = new DMSIC();
    }

    /**
     * Test getting export history
     */
    public function testGetExportHistory()
    {
        $result = $this->dmsic->getExportHistory(10);
        
        $this->assertIsArray($result);
        $this->assertLessThanOrEqual(10, count($result));
    }

    /**
     * Test getting configuration
     */
    public function testGetConfig()
    {
        $result = $this->dmsic->getConfig();
        
        // May be null or array
        $this->assertTrue($result === null || $result === false || is_array($result));
    }

    /**
     * Test gathering export data
     */
    public function testGatherExportData()
    {
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-d');
        
        $result = $this->dmsic->gatherExportData($startDate, $endDate);
        
        $this->assertIsArray($result);
    }

    /**
     * Test validating export data
     */
    public function testValidateData()
    {
        $data = [
            [
                'drug_code' => 'D001',
                'drug_name' => 'ยาทดสอบ',
                'quantity' => 10,
                'dispense_date' => date('Y-m-d'),
                'unit_price' => 100.00,
                'hn' => 'HN001'
            ]
        ];
        
        $result = $this->dmsic->validateData($data);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertTrue($result['valid']);
    }

    /**
     * Test validating invalid data
     */
    public function testValidateInvalidData()
    {
        $data = [
            [
                'drug_code' => '', // Missing drug code
                'quantity' => -5,  // Invalid quantity
                'dispense_date' => ''
            ]
        ];
        
        $result = $this->dmsic->validateData($data);
        
        $this->assertIsArray($result);
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    /**
     * Test formatting data as DMSIC standard
     */
    public function testFormatAsDMSIC()
    {
        $data = [
            [
                'drug_code' => 'D001',
                'drug_name' => 'ยาทดสอบ',
                'quantity' => 10,
                'dispense_date' => '2024-01-15',
                'unit_price' => 100.00,
                'hn' => 'HN001',
                'dispenser' => 'Pharmacist'
            ]
        ];
        
        $config = [
            'hospcode' => '12345'
        ];
        
        $result = $this->dmsic->formatAsDMSIC($data, $config);
        
        $this->assertIsString($result);
        $this->assertStringContainsString('HOSPCODE', $result);
        $this->assertStringContainsString('12345', $result);
    }

    /**
     * Test getting statistics
     */
    public function testGetStatistics()
    {
        $result = $this->dmsic->getStatistics();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_exports', $result);
    }

    /**
     * Test exporting to file
     */
    public function testExportToFile()
    {
        $content = "Test DMSIC Export Content";
        $fileName = 'test_export_' . time() . '.txt';
        
        $result = $this->dmsic->exportToFile($content, $fileName);
        
        $this->assertIsString($result);
        $this->assertFileExists($result);
        
        // Cleanup
        if (file_exists($result)) {
            unlink($result);
        }
    }
}
