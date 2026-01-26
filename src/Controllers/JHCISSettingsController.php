<?php

namespace App\Controllers;

use PDO;
use PDOException;

/**
 * JHCIS Settings Controller
 * จัดการการตั้งค่าการเชื่อมต่อ JHCIS
 */
class JHCISSettingsController
{
    private $configFile;

    public function __construct()
    {
        $this->configFile = __DIR__ . '/../../config/jhcis_config.json';
    }

    /**
     * หน้าตั้งค่า JHCIS
     */
    public function index()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        // โหลดการตั้งค่าปัจจุบัน
        $config = $this->loadConfig();

        require_once __DIR__ . '/../Views/jhcis/settings.php';
    }

    /**
     * ทดสอบการเชื่อมต่อ
     */
    public function testConnection()
    {
        ob_start(); // Start output buffering
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            ob_clean(); // Clear any output
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        $host = $input['host'] ?? 'localhost';
        $port = $input['port'] ?? '3306';
        $dbname = $input['dbname'] ?? 'jhcisdb';
        $user = $input['user'] ?? 'root';
        $pass = $input['pass'] ?? '';

        // แก้ปัญหา Docker: ถ้าเป็น localhost ให้ใช้ host.docker.internal
        if ($host === 'localhost' || $host === '127.0.0.1') {
            $host = 'host.docker.internal';
        }

        try {
            $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_TIMEOUT => 5, // Timeout 5 วินาที
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // ทดสอบ query
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // ตรวจสอบตารางสำคัญ
            $importantTables = ['drug', 'drugitems', 'dispensing', 'opd_dispensing', 'opitemrece'];
            $foundTables = array_intersect($importantTables, $tables);

            ob_clean(); // Clear any output before JSON
            echo json_encode([
                'success' => true,
                'message' => 'เชื่อมต่อสำเร็จ!',
                'details' => [
                    'total_tables' => count($tables),
                    'found_tables' => array_values($foundTables),
                    'sample_tables' => array_slice($tables, 0, 10)
                ]
            ]);

        } catch (PDOException $e) {
            ob_clean(); // Clear any output before JSON
            echo json_encode([
                'success' => false,
                'message' => 'เชื่อมต่อล้มเหลว: ' . $e->getMessage()
            ]);
        }
        ob_end_flush(); // End output buffering
    }

    /**
     * บันทึกการตั้งค่า
     */
    public function save()
    {
        ob_start();
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        $config = [
            'host' => $input['host'] ?? 'localhost',
            'port' => $input['port'] ?? '3306',
            'dbname' => $input['dbname'] ?? 'jhcisdb',
            'user' => $input['user'] ?? 'root',
            'pass' => $input['pass'] ?? '',
            'tables' => [
                'drug' => $input['drug_table'] ?? 'drug',
                'dispensing' => $input['dispensing_table'] ?? 'dispensing'
            ],
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $_SESSION['user_id']
        ];

        try {
            // สร้างโฟลเดอร์ config ถ้ายังไม่มี
            $configDir = dirname($this->configFile);
            if (!is_dir($configDir)) {
                mkdir($configDir, 0755, true);
            }

            // บันทึกเป็น JSON
            file_put_contents($this->configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            ob_clean();
            echo json_encode([
                'success' => true,
                'message' => 'บันทึกการตั้งค่าสำเร็จ!'
            ]);

        } catch (\Exception $e) {
            ob_clean();
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ]);
        }
        ob_end_flush();
    }

    /**
     * โหลดการตั้งค่า
     */
    private function loadConfig()
    {
        if (file_exists($this->configFile)) {
            $json = file_get_contents($this->configFile);
            return json_decode($json, true);
        }

        // ค่าเริ่มต้น
        return [
            'host' => 'localhost',
            'port' => '3306',
            'dbname' => 'jhcisdb',
            'user' => 'root',
            'pass' => '',
            'tables' => [
                'drug' => 'drug',
                'dispensing' => 'dispensing'
            ]
        ];
    }

    public function inspectTables()
    {
        ob_start();
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $config = $this->loadConfig();

        // ตรวจสอบว่ามีการตั้งค่าหรือยัง
        if (empty($config['host']) || empty($config['dbname']) || empty($config['user'])) {
            echo json_encode([
                'success' => false,
                'message' => 'กรุณาบันทึกการตั้งค่าการเชื่อมต่อก่อน'
            ]);
            return;
        }

        // แก้ปัญหา Docker
        $host = $config['host'];
        if ($host === 'localhost' || $host === '127.0.0.1') {
            $host = 'host.docker.internal';
        }

        try {
            $dsn = "mysql:host=$host;port={$config['port']};dbname={$config['dbname']};charset=utf8";
            $pdo = new PDO($dsn, $config['user'], $config['pass'], [
                PDO::ATTR_TIMEOUT => 5,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // ดึงรายชื่อตาราง
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $tableStructures = [];

            // ตรวจสอบโครงสร้างตารางที่น่าสนใจ
            $interestingTables = ['drug', 'drugitems', 'dispensing', 'opd_dispensing', 'opitemrece'];
            
            foreach ($interestingTables as $table) {
                if (in_array($table, $tables)) {
                    $stmt = $pdo->query("DESCRIBE $table");
                    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $tableStructures[$table] = array_map(function($col) {
                        return [
                            'field' => $col['Field'],
                            'type' => $col['Type'],
                            'null' => $col['Null'],
                            'key' => $col['Key']
                        ];
                    }, $columns);
                }
            }

            ob_clean();
            echo json_encode([
                'success' => true,
                'tables' => $tables,
                'structures' => $tableStructures
            ]);

        } catch (PDOException $e) {
            ob_clean();
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        ob_end_flush();
    }

    /**
     * วิเคราะห์ตารางยาทั้งหมดใน JHCIS
     */
    public function analyzeDrugTables()
    {
        ob_start();
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $config = $this->loadConfig();

        // ตรวจสอบว่ามีการตั้งค่าหรือยัง
        if (empty($config['host']) || empty($config['dbname']) || empty($config['user'])) {
            echo json_encode([
                'success' => false,
                'message' => 'กรุณาบันทึกการตั้งค่าการเชื่อมต่อก่อน'
            ]);
            return;
        }

        // แก้ปัญหา Docker
        $host = $config['host'];
        if ($host === 'localhost' || $host === '127.0.0.1') {
            $host = 'host.docker.internal';
        }

        try {
            $dsn = "mysql:host=$host;port={$config['port']};dbname={$config['dbname']};charset=utf8";
            $pdo = new PDO($dsn, $config['user'], $config['pass'], [
                PDO::ATTR_TIMEOUT => 10,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);

            // ตารางยาที่สำคัญใน JHCIS
            $drugTables = [
                // ตารางหลัก
                'cdrug' => 'ข้อมูลรายการยาหลัก (Master Drug)',
                'cdrugremaiin' => 'ข้อมูลคงเหลือยา (Drug Inventory)',
                'visitdrug' => 'ประวัติการจ่ายยาให้ผู้ป่วย (Drug Dispensing History)',
                
                // ตารางอ้างอิง
                'cdrugmaptype' => 'ประเภทยา (Drug Types)',
                'cdrugunitsell' => 'หน่วยจำหน่ายยา (Drug Units)',
                'cdruggallergysymtom' => 'อาการแพ้ยา (Drug Allergy Symptoms)',
                'cdrugmappowerful' => 'ยาที่มีฤทธิ์แรง (Powerful Drugs)',
                'cdrugmapmixer' => 'แผนที่การผสมยา (Drug Mixing Map)',
                'cdrugmapmodel' => 'โมเดลการจัดการยา (Drug Management Model)',
                
                // ตารางทันตกรรม
                'visitdrugdental' => 'การจ่ายยาทันตกรรม (Dental Drug Dispensing)',
                
                // ตารางคลังยา
                'drugstore' => 'ข้อมูลคลังยา (Drug Store)',
                'drugstorereceive' => 'การรับยาเข้าคลัง (Drug Receiving)',
                'drugstorereceivedetail' => 'รายละเอียดการรับยา (Receiving Details)',
                'drugstoretoloan' => 'การยืมยา (Drug Loan)',
                'drugrepositoryout' => 'การเบิกยาออก (Drug Withdrawal)',
                'drugrepositoryoutdetail' => 'รายละเอียดการเบิกยา (Withdrawal Details)',
                'drugrepositorytaken' => 'การรับยา (Drug Taken)',
                'drugrepositorytakendetail' => 'รายละเอียดการรับยา (Taken Details)',
                
                // ตารางอื่นๆ
                'drug_to_draw' => 'ยาที่ต้องเบิก (Drugs to Draw)',
                '_tmp_drugremaiin' => 'ข้อมูลคงเหลือยาชั่วคราว (Temp Drug Inventory)',
                '_tmp_drugstock' => 'สต็อกยาชั่วคราว (Temp Drug Stock)',
                '_tmp_drugtakendetail' => 'รายละเอียดการรับยาชั่วคราว (Temp Taken Details)',
                
                // ตารางระบบสูตรยา
                'sysdrugdose' => 'ขนาดยา (Drug Dose)',
                'sysdrugformula' => 'สูตรยา (Drug Formula)',
                'sysdrugformuladetail' => 'รายละเอียดสูตรยา (Formula Details)',
                'sysdrugformuladetaildiag' => 'การวินิจฉัยสูตรยา (Formula Diagnosis)',
                'sysdrugrights' => 'สิทธิ์การใช้ยา (Drug Rights)',
                'sysdrugrights_2' => 'สิทธิ์การใช้ยา 2 (Drug Rights 2)',
                
                // ตารางข้อมูลยาจาก TMT
                '_tmpdrugmap' => 'แผนที่ยาชั่วคราว (Temp Drug Map)',
                '_tmpdrugmap_2' => 'แผนที่ยาชั่วคราว 2 (Temp Drug Map 2)',
                '_tmpdrugthaiicode24' => 'รหัสยาไทย 24 หลัก (Thai Drug Code 24)',
                
                // ตารางความขัดแย้งของยา
                'cdiseaseconflictdrug' => 'ยาที่ขัดแย้งกับโรค (Disease-Drug Conflict)',
                
                // ตารางอื่นๆที่อาจมี
                '_hdc_drug' => 'ข้อมูลยา HDC (HDC Drug Data)',
                '_rit_druguse0' => 'การใช้ยา RIT 0',
                '_rit_druguse1' => 'การใช้ยา RIT 1',
                '_rit_druguse2' => 'การใช้ยา RIT 2',
            ];

            $analysis = [];
            $foundTables = [];
            $missingTables = [];

            // ดึงรายชื่อตารางทั้งหมด
            $stmt = $pdo->query("SHOW TABLES");
            $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($drugTables as $tableName => $description) {
                if (in_array($tableName, $allTables)) {
                    // นับจำนวนแถว
                    $countStmt = $pdo->query("SELECT COUNT(*) as count FROM `$tableName`");
                    $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];

                    // ดึงโครงสร้างตาราง
                    $structStmt = $pdo->query("DESCRIBE `$tableName`");
                    $structure = $structStmt->fetchAll(PDO::FETCH_ASSOC);

                    // ดึงข้อมูลตัวอย่าง 3 แถว
                    $sampleStmt = $pdo->query("SELECT * FROM `$tableName` LIMIT 3");
                    $samples = $sampleStmt->fetchAll(PDO::FETCH_ASSOC);

                    $foundTables[] = $tableName;
                    $analysis[$tableName] = [
                        'description' => $description,
                        'exists' => true,
                        'row_count' => $count,
                        'columns' => array_map(function($col) {
                            return [
                                'field' => $col['Field'],
                                'type' => $col['Type'],
                                'null' => $col['Null'],
                                'key' => $col['Key'],
                                'default' => $col['Default'] ?? null,
                                'extra' => $col['Extra'] ?? null
                            ];
                        }, $structure),
                        'sample_data' => $samples
                    ];
                } else {
                    $missingTables[] = $tableName;
                    $analysis[$tableName] = [
                        'description' => $description,
                        'exists' => false
                    ];
                }
            }

            // หาตารางที่มีคำว่า 'drug' ในชื่อแต่ไม่อยู่ในรายการ
            $additionalDrugTables = array_filter($allTables, function($table) use ($drugTables) {
                return (stripos($table, 'drug') !== false) && !isset($drugTables[$table]);
            });

            ob_clean();
            echo json_encode([
                'success' => true,
                'summary' => [
                    'total_tables_checked' => count($drugTables),
                    'found_tables' => count($foundTables),
                    'missing_tables' => count($missingTables),
                    'additional_drug_tables' => count($additionalDrugTables)
                ],
                'analysis' => $analysis,
                'found_tables_list' => $foundTables,
                'missing_tables_list' => $missingTables,
                'additional_drug_tables' => array_values($additionalDrugTables),
                'recommendations' => $this->generateRecommendations($analysis)
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        } catch (PDOException $e) {
            ob_clean();
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ]);
        }
        ob_end_flush();
    }

    /**
     * สร้างคำแนะนำจากการวิเคราะห์
     */
    private function generateRecommendations($analysis)
    {
        $recommendations = [];

        // ตรวจสอบตารางหลัก
        if (isset($analysis['cdrug']) && $analysis['cdrug']['exists']) {
            $recommendations[] = [
                'type' => 'success',
                'message' => 'พบตาราง cdrug (ข้อมูลยาหลัก) มี ' . number_format($analysis['cdrug']['row_count']) . ' รายการ - พร้อมใช้งาน'
            ];
        } else {
            $recommendations[] = [
                'type' => 'error',
                'message' => 'ไม่พบตาราง cdrug - ตารางนี้จำเป็นสำหรับระบบ'
            ];
        }

        // ตรวจสอบตารางคงเหลือ
        if (isset($analysis['cdrugremaiin']) && $analysis['cdrugremaiin']['exists']) {
            $recommendations[] = [
                'type' => 'success',
                'message' => 'พบตาราง cdrugremaiin (คงเหลือยา) มี ' . number_format($analysis['cdrugremaiin']['row_count']) . ' รายการ - สามารถติดตามสต็อกได้'
            ];
        }

        // ตรวจสอบตารางการจ่ายยา
        if (isset($analysis['visitdrug']) && $analysis['visitdrug']['exists']) {
            $recommendations[] = [
                'type' => 'success',
                'message' => 'พบตาราง visitdrug (ประวัติการจ่ายยา) มี ' . number_format($analysis['visitdrug']['row_count']) . ' รายการ - สามารถวิเคราะห์การใช้ยาได้'
            ];
        }

        // คำแนะนำการใช้งาน
        $recommendations[] = [
            'type' => 'info',
            'message' => 'แนะนำให้ sync ข้อมูลจากตาราง: cdrug (รายการยา), cdrugremaiin (คงเหลือ), visitdrug (การจ่ายยา)'
        ];

        $recommendations[] = [
            'type' => 'info',
            'message' => 'ตารางอ้างอิง: cdrugmaptype (ประเภท), cdrugunitsell (หน่วย), cdruggallergysymtom (อาการแพ้)'
        ];

        return $recommendations;
    }
}
