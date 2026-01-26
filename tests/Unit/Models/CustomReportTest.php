<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\CustomReport;

class CustomReportTest extends TestCase
{
    private $customReport;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customReport = new CustomReport();
    }

    /**
     * Test getting all reports
     */
    public function testGetAll()
    {
        $result = $this->customReport->getAll();
        
        $this->assertIsArray($result);
    }

    /**
     * Test creating custom report
     */
    public function testCreateReport()
    {
        $data = [
            'name' => 'รายงานทดสอบ',
            'description' => 'รายงานสำหรับทดสอบ',
            'query' => 'SELECT * FROM drugs LIMIT 10',
            'columns' => json_encode(['code', 'name', 'price']),
            'filters' => json_encode([]),
            'created_by' => 1
        ];
        
        $result = $this->customReport->create($data);
        
        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    /**
     * Test getting report by ID
     */
    public function testGetById()
    {
        $data = [
            'name' => 'รายงานทดสอบ 2',
            'description' => 'รายงานสำหรับทดสอบ 2',
            'query' => 'SELECT * FROM drugs LIMIT 5',
            'columns' => json_encode(['code', 'name']),
            'filters' => json_encode([]),
            'created_by' => 1
        ];
        
        $id = $this->customReport->create($data);
        $result = $this->customReport->getById($id);
        
        $this->assertIsArray($result);
        $this->assertEquals($id, $result['id']);
    }

    /**
     * Test getting predefined reports
     */
    public function testGetPredefinedReports()
    {
        $result = $this->customReport->getPredefinedReports();
        
        $this->assertIsArray($result);
        $this->assertGreaterThan(0, count($result));
        
        // Check structure
        foreach ($result as $report) {
            $this->assertArrayHasKey('id', $report);
            $this->assertArrayHasKey('name', $report);
            $this->assertArrayHasKey('query', $report);
        }
    }

    /**
     * Test executing report
     */
    public function testExecuteReport()
    {
        $data = [
            'name' => 'รายงานยาทั้งหมด',
            'description' => 'แสดงรายการยาทั้งหมด',
            'query' => 'SELECT id, code, name FROM drugs LIMIT 10',
            'columns' => json_encode(['id', 'code', 'name']),
            'filters' => json_encode([]),
            'created_by' => 1
        ];
        
        $id = $this->customReport->create($data);
        $result = $this->customReport->executeReport($id);
        
        $this->assertIsArray($result);
        $this->assertLessThanOrEqual(10, count($result));
    }

    /**
     * Test exporting to CSV
     */
    public function testExportToCSV()
    {
        $data = [
            ['code' => 'D001', 'name' => 'ยา A', 'price' => 100],
            ['code' => 'D002', 'name' => 'ยา B', 'price' => 200]
        ];
        
        $result = $this->customReport->exportToCSV($data, 'test.csv');
        
        $this->assertIsString($result);
        $this->assertStringContainsString('D001', $result);
        $this->assertStringContainsString('ยา A', $result);
    }

    /**
     * Test deleting report
     */
    public function testDeleteReport()
    {
        $data = [
            'name' => 'รายงานทดสอบลบ',
            'description' => 'รายงานสำหรับทดสอบการลบ',
            'query' => 'SELECT * FROM drugs LIMIT 1',
            'columns' => json_encode(['code']),
            'filters' => json_encode([]),
            'created_by' => 1
        ];
        
        $id = $this->customReport->create($data);
        $result = $this->customReport->delete($id);
        
        $this->assertTrue($result);
    }
}
