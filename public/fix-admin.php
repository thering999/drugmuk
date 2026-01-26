<?php
// Quick fix: Update admin password directly
require_once __DIR__ . '/../src/Core/Database.php';

use App\Core\Database;

$db = Database::getInstance()->getConnection();

$password = '123456';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h1>Fixing Admin Password</h1>";
echo "<p>Password: <code>$password</code></p>";
echo "<p>Hash: <code>$hash</code></p>";

// Update admin password
$stmt = $db->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
$result = $stmt->execute([$hash]);

if ($result) {
    echo "<p style='color: green;'>✅ Admin password updated successfully!</p>";
    echo "<p><a href='/login'>Go to Login</a></p>";
} else {
    echo "<p style='color: red;'>❌ Failed to update password</p>";
}
