<?php
/**
 * System Status Dashboard
 * 
 * Real-time monitoring dashboard for Drugmuk system
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Drugmuk\Core\Database;

header('Content-Type: text/html; charset=UTF-8');

$db = Database::getInstance();

// Get system statistics
$stats = [];

// Database stats
try {
    $stats['drugs_count'] = $db->query("SELECT COUNT(*) as count FROM drugs")->fetch()['count'];
    $stats['inventory_items'] = $db->query("SELECT COUNT(*) as count FROM inventory")->fetch()['count'];
    $stats['total_stock_value'] = $db->query("SELECT SUM(quantity * unit_price) as total FROM inventory")->fetch()['total'] ?? 0;
    $stats['low_stock_count'] = $db->query("SELECT COUNT(DISTINCT i.drug_id) as count FROM inventory i JOIN drugs d ON i.drug_id = d.id GROUP BY i.drug_id HAVING SUM(i.quantity) < d.min_stock")->fetch()['count'] ?? 0;
    $stats['expiring_soon'] = $db->query("SELECT COUNT(*) as count FROM inventory WHERE expiry_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 90 DAY)")->fetch()['count'] ?? 0;
    $stats['orders_pending'] = $db->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")->fetch()['count'] ?? 0;
    $stats['dispensing_today'] = $db->query("SELECT COUNT(*) as count FROM dispensing WHERE DATE(dispensed_at) = CURDATE()")->fetch()['count'] ?? 0;
} catch (Exception $e) {
    $stats['error'] = $e->getMessage();
}

// System health
$health = [
    'database' => 'healthy',
    'redis' => 'unknown',
    'disk_space' => disk_free_space('/') / disk_total_space('/') * 100,
    'memory_usage' => memory_get_usage(true) / 1024 / 1024
];

// Check Redis
try {
    if (extension_loaded('redis')) {
        $redis = new Redis();
        $redis->connect('localhost', 6379);
        $redis->ping();
        $health['redis'] = 'healthy';
    }
} catch (Exception $e) {
    $health['redis'] = 'error';
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drugmuk System Status</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #667eea;
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            font-size: 1.1em;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 0.9em;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        
        .stat-card .value {
            font-size: 2.5em;
            font-weight: bold;
            color: #667eea;
        }
        
        .stat-card.warning .value {
            color: #f59e0b;
        }
        
        .stat-card.danger .value {
            color: #ef4444;
        }
        
        .stat-card.success .value {
            color: #10b981;
        }
        
        .health-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .health-section h2 {
            color: #667eea;
            margin-bottom: 20px;
        }
        
        .health-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .health-item:last-child {
            border-bottom: none;
        }
        
        .health-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9em;
        }
        
        .status-healthy {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-error {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .refresh-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-size: 1em;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .refresh-btn:hover {
            background: #5568d3;
        }
        
        .timestamp {
            text-align: center;
            color: white;
            margin-top: 20px;
            font-size: 0.9em;
        }
        
        .progress-bar {
            width: 100%;
            height: 10px;
            background: #e5e7eb;
            border-radius: 5px;
            overflow: hidden;
            margin-top: 10px;
        }
        
        .progress-fill {
            height: 100%;
            background: #10b981;
            transition: width 0.3s ease;
        }
        
        .progress-fill.warning {
            background: #f59e0b;
        }
        
        .progress-fill.danger {
            background: #ef4444;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💊 Drugmuk System Status</h1>
            <p>Real-time monitoring dashboard - Version 3.0.0</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>📦 Total Drugs</h3>
                <div class="value"><?php echo number_format($stats['drugs_count']); ?></div>
            </div>
            
            <div class="stat-card">
                <h3>📊 Inventory Items</h3>
                <div class="value"><?php echo number_format($stats['inventory_items']); ?></div>
            </div>
            
            <div class="stat-card success">
                <h3>💰 Stock Value</h3>
                <div class="value">฿<?php echo number_format($stats['total_stock_value'], 0); ?></div>
            </div>
            
            <div class="stat-card <?php echo $stats['low_stock_count'] > 0 ? 'warning' : ''; ?>">
                <h3>⚠️ Low Stock Items</h3>
                <div class="value"><?php echo number_format($stats['low_stock_count']); ?></div>
            </div>
            
            <div class="stat-card <?php echo $stats['expiring_soon'] > 0 ? 'warning' : ''; ?>">
                <h3>📅 Expiring Soon (90 days)</h3>
                <div class="value"><?php echo number_format($stats['expiring_soon']); ?></div>
            </div>
            
            <div class="stat-card <?php echo $stats['orders_pending'] > 0 ? 'warning' : ''; ?>">
                <h3>🛒 Pending Orders</h3>
                <div class="value"><?php echo number_format($stats['orders_pending']); ?></div>
            </div>
            
            <div class="stat-card success">
                <h3>💉 Dispensing Today</h3>
                <div class="value"><?php echo number_format($stats['dispensing_today']); ?></div>
            </div>
        </div>
        
        <div class="health-section">
            <h2>🏥 System Health</h2>
            
            <div class="health-item">
                <span><strong>Database Connection</strong></span>
                <span class="health-status status-<?php echo $health['database']; ?>">
                    <?php echo strtoupper($health['database']); ?>
                </span>
            </div>
            
            <div class="health-item">
                <span><strong>Redis Cache</strong></span>
                <span class="health-status status-<?php echo $health['redis']; ?>">
                    <?php echo strtoupper($health['redis']); ?>
                </span>
            </div>
            
            <div class="health-item">
                <span><strong>Disk Space Available</strong></span>
                <span><?php echo number_format($health['disk_space'], 1); ?>%</span>
                <div class="progress-bar">
                    <div class="progress-fill <?php echo $health['disk_space'] < 20 ? 'danger' : ($health['disk_space'] < 50 ? 'warning' : ''); ?>" 
                         style="width: <?php echo $health['disk_space']; ?>%"></div>
                </div>
            </div>
            
            <div class="health-item">
                <span><strong>Memory Usage</strong></span>
                <span><?php echo number_format($health['memory_usage'], 2); ?> MB</span>
            </div>
            
            <div class="health-item">
                <span><strong>PHP Version</strong></span>
                <span><?php echo PHP_VERSION; ?></span>
            </div>
            
            <div style="text-align: center; margin-top: 20px;">
                <button class="refresh-btn" onclick="location.reload()">🔄 Refresh Status</button>
            </div>
        </div>
        
        <div class="timestamp">
            Last updated: <?php echo date('Y-m-d H:i:s'); ?>
            <br>
            Auto-refresh every 30 seconds
        </div>
    </div>
    
    <script>
        // Auto-refresh every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
