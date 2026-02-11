<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Load .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

function checkEnvironment() {
    $errors = [];
    $warnings = [];

    echo "Running System Health Check...\n";
    echo "============================\n";

    // 1. PHP Version
    echo "[PHP] Version: " . phpversion() . "\n";
    if (version_compare(phpversion(), '7.4.0', '<')) {
        $errors[] = "PHP version is too old. Required >= 7.4.0";
    }

    // 2. Directory Permissions
    $dirs = ['logs', 'storage'];
    foreach ($dirs as $dir) {
        $path = __DIR__ . '/../' . $dir;
        if (!is_dir($path)) {
            $warnings[] = "Directory does not exist: $dir";
        } elseif (!is_writable($path)) {
            $errors[] = "Directory is not writable: $dir";
        } else {
            echo "[ OK] Directory writable: $dir\n";
        }
    }

    // 3. Database Connection
    try {
        $host = $_ENV['DB_HOST'] ?? 'db';
        $db = $_ENV['DB_NAME'] ?? 'drugmuk';
        $user = $_ENV['DB_USER'] ?? 'root';
        $pass = $_ENV['DB_PASSWORD'] ?? '123456';
        
        $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "[ OK] Database Connected: $db on $host\n";
    } catch (PDOException $e) {
        $errors[] = "Database Connection Failed: " . $e->getMessage();
    }

    // 4. Redis Connection (if enabled)
    if (!empty($_ENV['REDIS_HOST'])) {
        try {
            $redis = new Redis();
            $redisHost = $_ENV['REDIS_HOST'];
            $redisPort = $_ENV['REDIS_PORT'] ?? 6379;
            $redisPass = $_ENV['REDIS_PASSWORD'] ?? null;
            
            error_reporting(E_ALL & ~E_WARNING); // Suppress warnings for simple check
            $connected = $redis->connect($redisHost, $redisPort, 2);
            error_reporting(E_ALL);

            if ($connected) {
                if ($redisPass) {
                    $auth = $redis->auth($redisPass);
                    if ($auth) {
                        echo "[ OK] Redis Connected\n";
                    } else {
                        $errors[] = "Redis Auth Failed";
                    }
                } else {
                    echo "[ OK] Redis Connected (No Auth)\n";
                }
            } else {
                $warnings[] = "Could not connect to Redis at $redisHost";
            }
        } catch (Exception $e) {
            $warnings[] = "Redis Error: " . $e->getMessage();
        }
    }

    // 5. Check for recent syntax errors in logs
    $logFile = __DIR__ . '/../logs/errors-' . date('Y-m-d') . '.log';
    if (file_exists($logFile)) {
        $logContent = file_get_contents($logFile);
        if (strpos($logContent, 'ParseError') !== false || strpos($logContent, 'Fatal error') !== false) {
            $warnings[] = "Found Critical Errors in today's log ($logFile). Please check manually.";
        } else {
             echo "[ OK] No critical PHP errors found in today's log yet.\n";
        }
    } else {
        echo "[INFO] No error log for today ($logFile).\n";
    }

    echo "\nSummary\n";
    echo "=======\n";
    if (empty($errors) && empty($warnings)) {
        echo "[PASS] System appears healthy.\n";
    } else {
        if (!empty($errors)) {
            echo "[FAIL] Critical Issues Found:\n";
            foreach ($errors as $err) echo " - ❌ $err\n";
        }
        if (!empty($warnings)) {
            echo "[WARN] Warnings:\n";
            foreach ($warnings as $warn) echo " - ⚠️ $warn\n";
        }
    }
}

checkEnvironment();
