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

        include __DIR__ . '/../Views/settings/unified.php';
    }

    /**
     * Update Drugmuk Environment Settings
     */
    public function updateDrugmuk()
    {
        header('Content-Type: application/json');

        try {
            $host = $_POST['host'] ?? 'localhost';
            $port = $_POST['port'] ?? '3306';
            $database = $_POST['database'] ?? 'drugmuk';
            $username = $_POST['username'] ?? 'root';
            $password = $_POST['password'] ?? '';

            // Update .env file
            $envPath = dirname(__DIR__, 2) . '/.env';
            if (!file_exists($envPath)) {
                file_put_contents($envPath, "");
            }

            $content = file_get_contents($envPath);
            $lines = explode("\n", $content);
            $newLines = [];
            $keys = ['DB_HOST' => $host, 'DB_PORT' => $port, 'DB_NAME' => $database, 'DB_USER' => $username, 'DB_PASSWORD' => $password];
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
                        $newLines[] = "{$key}={$keys[$key]}";
                        $foundKeys[$key] = true;
                        continue;
                    }
                }
                $newLines[] = $line;
            }

            // Add missing keys
            foreach ($keys as $key => $value) {
                if (!isset($foundKeys[$key])) {
                    $newLines[] = "{$key}={$value}";
                }
            }

            file_put_contents($envPath, implode("\n", $newLines));

            echo json_encode(['success' => true, 'message' => 'เพิ่มการตั้งค่า Drugmuk สำเร็จ (กรุณา Restart Server หากจำเป็น)']);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}
