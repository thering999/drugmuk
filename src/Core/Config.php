<?php

namespace App\Core;

/**
 * Configuration Manager
 * จัดการการอ่านค่า configuration จากไฟล์ .env
 */
class Config
{
    private static $config = [];
    private static $loaded = false;

    /**
     * โหลดค่า configuration จากไฟล์ .env
     */
    public static function load($envFile = null)
    {
        if (self::$loaded) {
            return;
        }

        if ($envFile === null) {
            $envFile = dirname(__DIR__, 2) . '/.env';
        }

        if (!file_exists($envFile)) {
            // ถ้าไม่มีไฟล์ .env ให้ใช้ค่า default
            self::loadDefaults();
            self::$loaded = true;
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // ข้ามบรรทัดที่เป็น comment
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // แยก key=value
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // ลบ quotes ออก
                $value = trim($value, '"\'');

                self::$config[$key] = $value;
                
                // Set เป็น environment variable ด้วย
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }

        self::$loaded = true;
    }

    /**
     * โหลดค่า default configuration
     */
    private static function loadDefaults()
    {
        self::$config = [
            // Database
            'DB_HOST' => 'db',
            'DB_PORT' => '3306',
            'DB_NAME' => 'drugmuk',
            'DB_USER' => 'root',
            'DB_PASS' => '123456',
            'DB_CHARSET' => 'utf8mb4',

            // JHCIS Database
            'JHCIS_DB_HOST' => 'localhost',
            'JHCIS_DB_PORT' => '3306',
            'JHCIS_DB_NAME' => 'jhcis',
            'JHCIS_DB_USER' => 'root',
            'JHCIS_DB_PASS' => '',
            'JHCIS_DB_CHARSET' => 'utf8mb4',

            // Application
            'APP_NAME' => 'Drugmuk',
            'APP_ENV' => 'development',
            'APP_DEBUG' => 'true',
            'APP_TIMEZONE' => 'Asia/Bangkok',

            // Session
            'SESSION_LIFETIME' => '7200',
            'SESSION_NAME' => 'drugmuk_session',

            // Security
            'APP_SECRET_KEY' => 'change-this-in-production',

            // JHCIS Sync
            'JHCIS_SYNC_DAYS_BACK' => '30',
            'JHCIS_AUTO_SYNC_ENABLED' => 'false',
            'JHCIS_AUTO_SYNC_TIME' => '02:00',

            // Logging
            'LOG_LEVEL' => 'info',
            'LOG_FILE' => 'logs/drugmuk.log',

            // Email
            'MAIL_ENABLED' => 'false',
            'NOTIFY_ON_SYNC_FAILURE' => 'true',
            'NOTIFY_EXPIRY_DAYS' => '90',
        ];
    }

    /**
     * ดึงค่า configuration
     * 
     * @param string $key ชื่อ key
     * @param mixed $default ค่า default ถ้าไม่เจอ
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        if (!self::$loaded) {
            self::load();
        }

        return self::$config[$key] ?? $default;
    }

    /**
     * ตั้งค่า configuration
     * 
     * @param string $key
     * @param mixed $value
     */
    public static function set($key, $value)
    {
        self::$config[$key] = $value;
        putenv("$key=$value");
        $_ENV[$key] = $value;
    }

    /**
     * ดึงค่า configuration ทั้งหมด
     * 
     * @return array
     */
    public static function all()
    {
        if (!self::$loaded) {
            self::load();
        }

        return self::$config;
    }

    /**
     * ตรวจสอบว่าเป็น production environment หรือไม่
     */
    public static function isProduction()
    {
        return self::get('APP_ENV') === 'production';
    }

    /**
     * ตรวจสอบว่าเปิด debug mode หรือไม่
     * 
     * @return bool
     */
    public static function isDebug()
    {
        return self::get('APP_DEBUG') === 'true';
    }

    /**
     * ดึงค่า database configuration
     * 
     * @param string $connection ชื่อ connection (drugmuk หรือ jhcis)
     * @return array
     */
    public static function database($connection = 'drugmuk')
    {
        if (!self::$loaded) {
            self::load();
        }

        if ($connection === 'jhcis') {
            return [
                'host' => self::get('JHCIS_DB_HOST'),
                'port' => self::get('JHCIS_DB_PORT'),
                'database' => self::get('JHCIS_DB_NAME'),
                'username' => self::get('JHCIS_DB_USER'),
                'password' => self::get('JHCIS_DB_PASS'),
                'charset' => self::get('JHCIS_DB_CHARSET', 'utf8mb4'),
            ];
        }

        return [
            'host' => self::get('DB_HOST'),
            'port' => self::get('DB_PORT'),
            'database' => self::get('DB_NAME'),
            'username' => self::get('DB_USER'),
            'password' => self::get('DB_PASSWORD', self::get('DB_PASS')),
            'charset' => self::get('DB_CHARSET', 'utf8mb4'),
        ];
    }
}
