<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Config;
use App\Core\APIResponse;

/**
 * System Settings Controller
 * Handles unified database and system configurations
 */
class SystemSettingsController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Unified Database Settings Page
     */
    public function index()
    {
        // Get Drugmuk config from environment
        $drugmukConfig = [
            'host' => getenv('DB_HOST') ?: 'localhost',
            'port' => getenv('DB_PORT') ?: '3306',
            'database' => getenv('DB_NAME') ?: 'drugmuk',
            'username' => getenv('DB_USER') ?: 'root',
            'password' => getenv('DB_PASS') ?: getenv('DB_PASSWORD') ?: ''
        ];

        // Get JHCIS hospitals
        $stmt = $this->db->query("SELECT * FROM jhcis_hospitals ORDER BY name");
        $jhcisHospitals = $stmt->fetchAll();

        // Get LINE settings
        $lineConfig = [
            'access_token' => getenv('LINE_ACCESS_TOKEN') ?: '',
            'channel_secret' => getenv('LINE_CHANNEL_SECRET') ?: '',
            'admin_user_id' => getenv('LINE_ADMIN_USER_ID') ?: ''
        ];

        // Get AI settings
        $aiConfig = [
            'python_path' => getenv('PYTHON_PATH') ?: 'python3',
            'forecasting_model' => getenv('AI_FORECASTING_MODEL') ?: 'ENHANCED_EMA'
        ];

        // Get JHCIS settings
        $jhcisConfig = [
            'host' => getenv('JHCIS_DB_HOST') ?: 'localhost',
            'port' => getenv('JHCIS_DB_PORT') ?: '3333',
            'database' => getenv('JHCIS_DB_NAME') ?: 'jhcisdb',
            'username' => getenv('JHCIS_DB_USER') ?: 'root',
            'password' => getenv('JHCIS_DB_PASS') ?: ''
        ];

        include __DIR__ . '/../Views/settings/unified.php';
    }

    /**
     * Update Unified Settings (Generic Environment Updater)
     */
    public function update()
    {
        header('Content-Type: application/json');

        try {
            $type = $_POST['type'] ?? '';
            $keys = [];

            switch ($type) {
                case 'drugmuk':
                    $keys = [
                        'DB_HOST' => $_POST['host'] ?? 'localhost',
                        'DB_PORT' => $_POST['port'] ?? '3306',
                        'DB_NAME' => $_POST['database'] ?? 'drugmuk',
                        'DB_USER' => $_POST['username'] ?? 'root',
                        'DB_PASSWORD' => $_POST['password'] ?? ''
                    ];
                    break;
                case 'line':
                    $keys = [
                        'LINE_ACCESS_TOKEN' => $_POST['access_token'] ?? '',
                        'LINE_CHANNEL_SECRET' => $_POST['channel_secret'] ?? '',
                        'LINE_ADMIN_USER_ID' => $_POST['admin_user_id'] ?? ''
                    ];
                    break;
                case 'ai':
                    $keys = [
                        'PYTHON_PATH' => $_POST['python_path'] ?? 'python3',
                        'AI_FORECASTING_MODEL' => $_POST['forecasting_model'] ?? 'ENHANCED_EMA'
                    ];
                    break;
                case 'jhcis':
                    $keys = [
                        'JHCIS_DB_HOST' => $_POST['host'] ?? 'localhost',
                        'JHCIS_DB_PORT' => $_POST['port'] ?? '3333',
                        'JHCIS_DB_NAME' => $_POST['database'] ?? 'jhcisdb',
                        'JHCIS_DB_USER' => $_POST['username'] ?? 'root',
                        'JHCIS_DB_PASS' => $_POST['password'] ?? ''
                    ];
                    break;
                default:
                    throw new \Exception("Invalid settings type");
            }

            $this->updateEnv($keys);

            echo json_encode(['success' => true, 'message' => 'บันทึกการตั้งค่าสำเร็จ (กรุณา Restart Server หากจำเป็น)']);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Test Database Connection Live
     */
    public function testConnection()
    {
        header('Content-Type: application/json');
        
        $host = $_POST['host'] ?? '';
        $port = $_POST['port'] ?? '';
        $database = $_POST['database'] ?? '';
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        $startTime = microtime(true);

        try {
            // ป้องกันการแฮงค์ด้วย Timeout 3 วินาที
            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_TIMEOUT => 3, 
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            
            $pdo = new \PDO($dsn, $username, $password, $options);
            
            $endTime = microtime(true);
            $latency = round(($endTime - $startTime) * 1000);

            echo json_encode([
                'success' => true, 
                'message' => 'เชื่อมต่อสำเร็จ',
                'latency' => $latency
            ]);
        } catch (\PDOException $e) {
            echo json_encode([
                'success' => false, 
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    private function updateEnv($keys)
    {
        $envPath = dirname(__DIR__, 2) . '/.env';
        if (!file_exists($envPath)) {
            file_put_contents($envPath, "");
        }

        $content = file_get_contents($envPath);
        $lines = explode("\n", $content);
        $newLines = [];
        $foundKeys = [];

        foreach ($lines as $line) {
            if (empty(trim($line)) || strpos(trim($line), '#') === 0) {
                $newLines[] = $line;
                continue;
            }

            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                if (isset($keys[$key])) {
                    $newLines[] = "{$key}=\"{$keys[$key]}\"";
                    $foundKeys[$key] = true;
                    continue;
                }
            }
            $newLines[] = $line;
        }

        // Add missing keys
        foreach ($keys as $key => $value) {
            if (!isset($foundKeys[$key])) {
                $newLines[] = "{$key}=\"{$value}\"";
            }
        }

        file_put_contents($envPath, implode("\n", $newLines));
    }
}
