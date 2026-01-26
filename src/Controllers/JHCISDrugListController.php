<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use PDO;

/**
 * JHCIS Drug List Controller
 * แสดงรายการยาจาก JHCIS และ mapping กับยาใน Drugmuk
 */
class JHCISDrugListController extends Controller {
    
    private $db;
    private $jhcisDb;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * หน้าแสดงรายการยา JHCIS
     */
    public function index() {
        $search = $_GET['search'] ?? '';
        $page = $_GET['page'] ?? 1;
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        try {
            $this->connectJHCIS();
            
            // ตรวจสอบว่าใช้ตารางอะไร
            $tableName = $this->detectDrugTable();
            
            // ดึงข้อมูลยาจาก JHCIS
            $jhcisDrugs = $this->getJHCISDrugs($tableName, $search, $perPage, $offset);
            $totalDrugs = $this->countJHCISDrugs($tableName, $search);
            $totalPages = ceil($totalDrugs / $perPage);
            
            // ดึงข้อมูลยาที่ map แล้ว
            $mappedDrugs = $this->getMappedDrugs();
            
            $this->view('jhcis-drugs/index', [
                'jhcisDrugs' => $jhcisDrugs,
                'mappedDrugs' => $mappedDrugs,
                'tableName' => $tableName,
                'search' => $search,
                'page' => $page,
                'totalPages' => $totalPages,
                'totalDrugs' => $totalDrugs
            ]);
            
        } catch (\Exception $e) {
            $this->view('jhcis-drugs/index', [
                'error' => $e->getMessage(),
                'jhcisDrugs' => [],
                'mappedDrugs' => [],
                'tableName' => null
            ]);
        }
    }

    /**
     * ตรวจสอบว่าใช้ตารางอะไร
     */
    private function detectDrugTable() {
        try {
            $this->jhcisDb->query("SELECT 1 FROM drugitems LIMIT 1");
            return 'drugitems';
        } catch (\PDOException $e) {
            try {
                $this->jhcisDb->query("SELECT 1 FROM cdrug LIMIT 1");
                return 'cdrug';
            } catch (\PDOException $e2) {
                throw new \Exception('ไม่พบตารางยาใน JHCIS');
            }
        }
    }

    /**
     * ดึงข้อมูลยาจาก JHCIS
     */
    private function getJHCISDrugs($tableName, $search, $limit, $offset) {
        if ($tableName === 'drugitems') {
            // JHCIS ใหม่
            $sql = "SELECT drugcode, name, genericname, units, unitprice 
                    FROM drugitems 
                    WHERE name LIKE ? OR genericname LIKE ? OR drugcode LIKE ?
                    ORDER BY name 
                    LIMIT ?, ?";
        } else {
            // JHCIS เก่า - ตรวจสอบ column ที่มีจริง
            $columns = $this->getCdrugColumns();
            
            $nameCol = $columns['name'] ?? 'drugcode';
            $genericCol = $columns['generic'] ?? 'drugcode';
            $unitCol = $columns['unit'] ?? "''";
            $priceCol = $columns['price'] ?? '0';
            
            $sql = "SELECT drugcode, 
                           {$nameCol} as name, 
                           {$genericCol} as genericname, 
                           {$unitCol} as units, 
                           {$priceCol} as unitprice 
                    FROM cdrug 
                    WHERE {$nameCol} LIKE ? OR {$genericCol} LIKE ? OR drugcode LIKE ?
                    ORDER BY {$nameCol} 
                    LIMIT ?, ?";
        }
        
        $searchTerm = "%{$search}%";
        $stmt = $this->jhcisDb->prepare($sql);
        $stmt->bindValue(1, $searchTerm, PDO::PARAM_STR);
        $stmt->bindValue(2, $searchTerm, PDO::PARAM_STR);
        $stmt->bindValue(3, $searchTerm, PDO::PARAM_STR);
        $stmt->bindValue(4, (int)$offset, PDO::PARAM_INT);
        $stmt->bindValue(5, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * ตรวจสอบ column ที่มีในตาราง cdrug
     */
    private function getCdrugColumns() {
        static $columns = null;
        
        if ($columns !== null) {
            return $columns;
        }
        
        $columns = [];
        
        // ดึงรายชื่อ column ทั้งหมด
        $stmt = $this->jhcisDb->query("SHOW COLUMNS FROM cdrug");
        $allColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // หาชื่อ column สำหรับชื่อยา
        if (in_array('name', $allColumns)) {
            $columns['name'] = 'name';
        } elseif (in_array('sname', $allColumns)) {
            $columns['name'] = 'sname';
        } elseif (in_array('drugname', $allColumns)) {
            $columns['name'] = 'drugname';
        }
        
        // หาชื่อ column สำหรับชื่อสามัญ
        if (in_array('genericname', $allColumns)) {
            $columns['generic'] = 'genericname';
        } elseif (in_array('gname', $allColumns)) {
            $columns['generic'] = 'gname';
        } elseif (in_array('generic', $allColumns)) {
            $columns['generic'] = 'generic';
        }
        
        // หาชื่อ column สำหรับหน่วย
        if (in_array('units', $allColumns)) {
            $columns['unit'] = 'units';
        } elseif (in_array('unitsale', $allColumns)) {
            $columns['unit'] = 'unitsale';
        } elseif (in_array('unit', $allColumns)) {
            $columns['unit'] = 'unit';
        }
        
        // หาชื่อ column สำหรับราคา
        if (in_array('unitprice', $allColumns)) {
            $columns['price'] = 'unitprice';
        } elseif (in_array('price', $allColumns)) {
            $columns['price'] = 'price';
        } elseif (in_array('sellprice', $allColumns)) {
            $columns['price'] = 'sellprice';
        } elseif (in_array('cost', $allColumns)) {
            $columns['price'] = 'cost';
        } else {
            $columns['price'] = '0'; // default ถ้าไม่มี
        }
        
        return $columns;
    }

    /**
     * นับจำนวนยาใน JHCIS
     */
    private function countJHCISDrugs($tableName, $search) {
        if ($tableName === 'drugitems') {
            $sql = "SELECT COUNT(*) as total FROM drugitems 
                    WHERE name LIKE ? OR genericname LIKE ? OR drugcode LIKE ?";
        } else {
            $columns = $this->getCdrugColumns();
            $nameCol = $columns['name'] ?? 'drugcode';
            $genericCol = $columns['generic'] ?? 'drugcode';
            
            $sql = "SELECT COUNT(*) as total FROM cdrug 
                    WHERE {$nameCol} LIKE ? OR {$genericCol} LIKE ? OR drugcode LIKE ?";
        }
        
        $searchTerm = "%{$search}%";
        $stmt = $this->jhcisDb->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    /**
     * ดึงข้อมูลยาที่ map แล้ว
     */
    private function getMappedDrugs() {
        $sql = "SELECT code FROM drugs";
        $stmt = $this->db->query($sql);
        $drugs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $mapped = [];
        foreach ($drugs as $drug) {
            $mapped[$drug['code']] = true;
        }
        
        return $mapped;
    }

    /**
     * เชื่อมต่อกับ JHCIS Database
     */
    private function connectJHCIS() {
        // Load .env file if exists
        $this->loadEnv();
        
        $host = $this->getEnv('JHCIS_DB_HOST', 'host.docker.internal');
        $port = $this->getEnv('JHCIS_DB_PORT', '3306');
        $database = $this->getEnv('JHCIS_DB_NAME', 'jhcisdb');
        $username = $this->getEnv('JHCIS_DB_USER', 'root');
        $password = $this->getEnv('JHCIS_DB_PASS', '123456');
        
        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
            
            $this->jhcisDb = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
        } catch (\PDOException $e) {
            throw new \Exception("ไม่สามารถเชื่อมต่อ JHCIS Database ได้: " . $e->getMessage());
        }
    }

    /**
     * Load .env file
     */
    private function loadEnv() {
        $envFile = __DIR__ . '/../../.env';
        
        if (!file_exists($envFile)) {
            return;
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                $value = trim($value, '"\'');
                
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }

    /**
     * Get environment variable with fallback
     */
    private function getEnv($key, $default = null) {
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        
        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }
        
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }
        
        return $default;
    }

    /**
     * Export JHCIS drugs to Excel
     */
    public function export() {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        try {
            $this->connectJHCIS();
            $tableName = $this->detectDrugTable();
            $search = $_GET['search'] ?? '';
            
            // ดึงข้อมูลทั้งหมด (ไม่จำกัดจำนวน)
            $drugs = $this->getJHCISDrugs($tableName, $search, 10000, 0);
            
            // สร้าง CSV content
            $output = fopen('php://temp', 'r+');
            
            // Header
            fputcsv($output, ['Drug Code', 'Name', 'Generic Name', 'Unit', 'Price']);
            
            // Data rows
            foreach ($drugs as $drug) {
                fputcsv($output, [
                    $drug['drugcode'] ?? '',
                    $drug['name'] ?? '',
                    $drug['genericname'] ?? '',
                    $drug['units'] ?? '',
                    $drug['unitprice'] ?? '0'
                ]);
            }
            
            rewind($output);
            $csv = stream_get_contents($output);
            fclose($output);
            
            // Send as download
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="jhcis_drugs_' . date('Ymd_His') . '.csv"');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            // Add BOM for Excel UTF-8 support
            echo "\xEF\xBB\xBF";
            echo $csv;
            exit;
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }
}
