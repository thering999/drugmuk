<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log - Drugmuk</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --dark: #1f2937;
            --light: #f3f4f6;
            --white: #ffffff;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container { max-width: 1400px; margin: 0 auto; }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: white;
            text-decoration: none;
            margin-bottom: 20px;
            padding: 10px 20px;
            background: rgba(255,255,255,0.2);
            border-radius: 8px;
            transition: all 0.3s;
        }

        .back-link:hover { background: rgba(255,255,255,0.3); }

        .card {
            background: var(--white);
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h1 {
            font-size: 24px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-body { padding: 25px; }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: var(--light);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }

        .stat-card .icon { font-size: 32px; margin-bottom: 10px; }
        .stat-card .value { font-size: 28px; font-weight: 700; color: var(--primary); }
        .stat-card .label { font-size: 14px; color: #666; }

        /* Filters */
        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
            padding: 20px;
            background: var(--light);
            border-radius: 12px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-group label {
            font-size: 12px;
            font-weight: 600;
            color: #666;
        }

        .filter-group input,
        .filter-group select {
            padding: 10px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            min-width: 150px;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            border-color: var(--primary);
            outline: none;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
        }

        .btn-primary:hover { transform: translateY(-2px); }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn-danger { background: var(--danger); color: white; }

        /* Table */
        .log-table {
            width: 100%;
            border-collapse: collapse;
        }

        .log-table th,
        .log-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .log-table th {
            background: var(--light);
            font-weight: 600;
            color: var(--dark);
            position: sticky;
            top: 0;
        }

        .log-table tr:hover { background: #f9fafb; }

        .action-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
        }

        .module-badge {
            background: var(--light);
            color: var(--dark);
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 25px;
        }

        .pagination a,
        .pagination span {
            padding: 10px 15px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
        }

        .pagination a {
            background: var(--light);
            color: var(--dark);
        }

        .pagination a:hover { background: var(--primary); color: white; }

        .pagination .active {
            background: var(--primary);
            color: white;
        }

        /* Charts */
        .charts-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .chart-container {
            background: var(--white);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .chart-container h3 {
            font-size: 16px;
            color: var(--dark);
            margin-bottom: 15px;
        }

        /* Session Messages */
        .session-message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .session-message.success {
            background: #d1fae5;
            color: #065f46;
        }

        .session-message.error {
            background: #fee2e2;
            color: #991b1b;
        }

        .text-muted { color: #999; font-size: 13px; }
        .text-truncate { max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        @media (max-width: 768px) {
            .filters { flex-direction: column; }
            .filter-group input, .filter-group select { width: 100%; }
            .log-table { font-size: 13px; }
            .log-table th, .log-table td { padding: 10px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="/dashboard" class="back-link">← กลับหน้าหลัก</a>

        <?php if (isset($_SESSION['success'])): ?>
        <div class="session-message success">✅ <?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); endif; ?>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon">📊</div>
                <div class="value"><?= number_format($total_logs) ?></div>
                <div class="label">Activity Logs ทั้งหมด</div>
            </div>
            <?php foreach (array_slice($statistics ?? [], 0, 4) as $stat): ?>
            <?php $format = \App\Services\ActivityLogService::formatAction($stat['action']); ?>
            <div class="stat-card">
                <div class="icon"><?= $format['icon'] ?></div>
                <div class="value"><?= number_format($stat['count']) ?></div>
                <div class="label"><?= $format['label'] ?> (7 วัน)</div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Charts -->
        <div class="charts-row">
            <div class="chart-container">
                <h3>📈 Activity 7 วันย้อนหลัง</h3>
                <canvas id="dailyChart" height="200"></canvas>
            </div>
            <div class="chart-container">
                <h3>🥧 แยกตามประเภท</h3>
                <canvas id="actionChart" height="200"></canvas>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h1>📋 Activity Log</h1>
                <div style="display: flex; gap: 10px;">
                    <a href="/activity-log/export?<?= http_build_query($filters ?? []) ?>" class="btn btn-outline" style="border-color: white; color: white;">
                        📥 Export CSV
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <form method="GET" class="filters">
                    <div class="filter-group">
                        <label>ค้นหา</label>
                        <input type="text" name="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" placeholder="ค้นหา...">
                    </div>
                    <div class="filter-group">
                        <label>โมดูล</label>
                        <select name="module">
                            <option value="">ทั้งหมด</option>
                            <option value="auth" <?= ($filters['module'] ?? '') === 'auth' ? 'selected' : '' ?>>Auth</option>
                            <option value="dispensing" <?= ($filters['module'] ?? '') === 'dispensing' ? 'selected' : '' ?>>Dispensing</option>
                            <option value="inventory" <?= ($filters['module'] ?? '') === 'inventory' ? 'selected' : '' ?>>Inventory</option>
                            <option value="orders" <?= ($filters['module'] ?? '') === 'orders' ? 'selected' : '' ?>>Orders</option>
                            <option value="drugs" <?= ($filters['module'] ?? '') === 'drugs' ? 'selected' : '' ?>>Drugs</option>
                            <option value="reports" <?= ($filters['module'] ?? '') === 'reports' ? 'selected' : '' ?>>Reports</option>
                            <option value="settings" <?= ($filters['module'] ?? '') === 'settings' ? 'selected' : '' ?>>Settings</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>การกระทำ</label>
                        <select name="action">
                            <option value="">ทั้งหมด</option>
                            <option value="login" <?= ($filters['action'] ?? '') === 'login' ? 'selected' : '' ?>>เข้าสู่ระบบ</option>
                            <option value="logout" <?= ($filters['action'] ?? '') === 'logout' ? 'selected' : '' ?>>ออกจากระบบ</option>
                            <option value="create" <?= ($filters['action'] ?? '') === 'create' ? 'selected' : '' ?>>สร้าง</option>
                            <option value="update" <?= ($filters['action'] ?? '') === 'update' ? 'selected' : '' ?>>แก้ไข</option>
                            <option value="delete" <?= ($filters['action'] ?? '') === 'delete' ? 'selected' : '' ?>>ลบ</option>
                            <option value="dispense" <?= ($filters['action'] ?? '') === 'dispense' ? 'selected' : '' ?>>จ่ายยา</option>
                            <option value="receive" <?= ($filters['action'] ?? '') === 'receive' ? 'selected' : '' ?>>รับยา</option>
                            <option value="export" <?= ($filters['action'] ?? '') === 'export' ? 'selected' : '' ?>>Export</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>ตั้งแต่วันที่</label>
                        <input type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
                    </div>
                    <div class="filter-group">
                        <label>ถึงวันที่</label>
                        <input type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
                    </div>
                    <div class="filter-group" style="justify-content: flex-end;">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary">🔍 ค้นหา</button>
                    </div>
                    <div class="filter-group" style="justify-content: flex-end;">
                        <label>&nbsp;</label>
                        <a href="/activity-log" class="btn btn-outline">↻ รีเซ็ต</a>
                    </div>
                </form>

                <!-- Table -->
                <div style="overflow-x: auto;">
                    <table class="log-table">
                        <thead>
                            <tr>
                                <th>วันที่/เวลา</th>
                                <th>ผู้ใช้</th>
                                <th>การกระทำ</th>
                                <th>โมดูล</th>
                                <th>รายละเอียด</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #999; padding: 40px;">
                                    ไม่พบข้อมูล Activity Log
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                            <?php $format = \App\Services\ActivityLogService::formatAction($log['action']); ?>
                            <tr>
                                <td>
                                    <div><?= date('d/m/Y', strtotime($log['created_at'])) ?></div>
                                    <div class="text-muted"><?= date('H:i:s', strtotime($log['created_at'])) ?></div>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($log['username'] ?? 'System') ?></strong>
                                </td>
                                <td>
                                    <span class="action-badge" style="background: <?= $format['color'] ?>20; color: <?= $format['color'] ?>;">
                                        <?= $format['icon'] ?> <?= $format['label'] ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="module-badge"><?= htmlspecialchars($log['module']) ?></span>
                                </td>
                                <td>
                                    <div class="text-truncate" title="<?= htmlspecialchars($log['description'] ?? '') ?>">
                                        <?= htmlspecialchars($log['description'] ?? '-') ?>
                                    </div>
                                </td>
                                <td class="text-muted"><?= htmlspecialchars($log['ip_address'] ?? '-') ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                    <a href="?<?= http_build_query(array_merge($filters ?? [], ['page' => $page - 1])) ?>">← ก่อนหน้า</a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <?php if ($i == $page): ?>
                    <span class="active"><?= $i ?></span>
                    <?php else: ?>
                    <a href="?<?= http_build_query(array_merge($filters ?? [], ['page' => $i])) ?>"><?= $i ?></a>
                    <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <a href="?<?= http_build_query(array_merge($filters ?? [], ['page' => $page + 1])) ?>">ถัดไป →</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Cleanup Section -->
        <div class="card">
            <div class="card-header" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                <h1>🗑️ ลบ Logs เก่า</h1>
            </div>
            <div class="card-body">
                <form method="POST" action="/activity-log/cleanup" onsubmit="return confirm('ยืนยันการลบ Activity Logs เก่า?');">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <div style="display: flex; gap: 15px; align-items: center;">
                        <label>ลบ Logs เก่ากว่า</label>
                        <select name="retention_days" style="padding: 10px; border-radius: 8px; border: 2px solid #e5e7eb;">
                            <option value="30">30 วัน</option>
                            <option value="60">60 วัน</option>
                            <option value="90" selected>90 วัน</option>
                            <option value="180">180 วัน</option>
                            <option value="365">1 ปี</option>
                        </select>
                        <button type="submit" class="btn btn-danger">🗑️ ลบ Logs เก่า</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Chart Data
        const dailyStats = <?= json_encode($daily_stats ?? []) ?>;
        const statistics = <?= json_encode($statistics ?? []) ?>;

        // Daily Activity Chart
        if (dailyStats.length > 0) {
            new Chart(document.getElementById('dailyChart'), {
                type: 'line',
                data: {
                    labels: dailyStats.map(s => {
                        const d = new Date(s.date);
                        return d.toLocaleDateString('th-TH', { day: '2-digit', month: 'short' });
                    }),
                    datasets: [{
                        label: 'จำนวน Activity',
                        data: dailyStats.map(s => s.count),
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        }

        // Action Distribution Chart
        if (statistics.length > 0) {
            const colors = ['#667eea', '#10b981', '#f59e0b', '#ef4444', '#3b82f6', '#8b5cf6', '#ec4899'];
            new Chart(document.getElementById('actionChart'), {
                type: 'doughnut',
                data: {
                    labels: statistics.map(s => s.action),
                    datasets: [{
                        data: statistics.map(s => s.count),
                        backgroundColor: colors.slice(0, statistics.length)
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'right', labels: { font: { size: 11 } } }
                    }
                }
            });
        }
    </script>
</body>
</html>
