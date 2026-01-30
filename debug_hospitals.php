<?php
require __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Connect to database
$db = new PDO(
    'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'],
    $_ENV['DB_USER'],
    $_ENV['DB_PASS']
);

// Query hospitals
$stmt = $db->query("SELECT id, code, name, pcucode, is_active FROM jhcis_hospitals ORDER BY name");
$hospitals = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "=== JHCIS Hospitals Debug ===\n";
echo "Total hospitals found: " . count($hospitals) . "\n\n";

if (empty($hospitals)) {
    echo "❌ NO HOSPITALS FOUND!\n";
} else {
    foreach ($hospitals as $h) {
        echo sprintf(
            "ID: %s | Name: %s | Code: %s | PCU: %s | Active: %s\n",
            $h['id'],
            $h['name'],
            $h['code'],
            $h['pcucode'] ?? 'NULL',
            $h['is_active'] ? 'Yes' : 'No'
        );
    }
}
