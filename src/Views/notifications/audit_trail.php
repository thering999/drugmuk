<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= \App\Core\CSRF::metaTag() ?>
    <title>Audit Trail - Drugmuk</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%);
            min-height: 100vh; padding: 20px;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        .header {
            background: white; padding: 25px 30px; border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-bottom: 25px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .header h1 { color: #333; font-size: 26px; }
        .back-link {
            color: white; text-decoration: none; display: inline-block;
            background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 8px; margin-bottom: 15px;
        }
        
        .stats-row {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px; margin-bottom: 25px;
        }
        .stat-card {
            background: white; padding: 20px; border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center;
        }
        .stat-card .number { font-size: 28px; font-weight: bold; color: #1e3a5f; }
        .stat-card .label { color: #666; margin-top: 5px; font-size: 14px; }
        
        .grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
        @media (max-width: 1024px) { .grid { grid-template-columns: 1fr; } }
        
        .card {
            background: white; padding: 25px; border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 20px;
        }
        .card h2 { color: #333; margin-bottom: 20px; font-size: 18px; }
        
        .filters {
            display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 20px;
            background: #f9fafb; padding: 15px; border-radius: 10px;
        }
        .filters select, .filters input {
            padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;
        }
        .filters button {
            padding: 8px 16px; background: #1e3a5f; color: white; border: none;
            border-radius: 6px; cursor: pointer;
        }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f9fafb; font-weight: 600; color: #374151; font-size: 13px; }
        td { font-size: 14px; }
        tr:hover { background: #f9fafb; }
        
        .action-badge {
            padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;
        }
        .action-create { background: #d1fae5; color: #065f46; }
        .action-update { background: #dbeafe; color: #1e40af; }
        .action-delete { background: #fee2e2; color: #991b1b; }
        .action-merge { background: #fef3c7; color: #92400e; }
        .action-import { background: #e0e7ff; color: #3730a3; }
        
        .changes-preview {
            max-width: 300px; font-size: 12px; color: #666;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        
        .top-users-list { list-style: none; }
        .top-users-list li {
            display: flex; justify-content: space-between; padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .top-users-list li:last-child { border-bottom: none; }
        .user-name { font-weight: 500; }
        .user-count { color: #1e3a5f; font-weight: bold; }
        
        .pagination { display: flex; justify-content: center; gap: 5px; margin-top: 20px; }
        .pagination a, .pagination span {
            padding: 8px 14px; border-radius: 6px; text-decoration: none;
            background: #f3f4f6; color: #374151;
        }
        .pagination a:hover { background: #e5e7eb; }
        .pagination .current { background: #1e3a5f; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <a href="/dashboard" class="back-link">← กลับหน้าหลัก</a>
        
        <div class="header">
            <h1>📜 Audit Trail - ประวัติการเปลี่ยนแปลงข้อมูล</h1>
            <div>
                <span style="color: #666;">ทั้งหมด <?php echo number_format($totalCount); ?> รายการ</span>
            </div>
        </div>

        <div class="stats-row">
            <?php
            $actionCounts = [];
            foreach ($statistics as $stat) {
                $actionCounts[$stat['action']] = ($actionCounts[$stat['action']] ?? 0) + $stat['count'];
            }
            ?>
            <div class="stat-card">
                <div class="number"><?php echo number_format($actionCounts['create'] ?? 0); ?></div>
                <div class="label">สร้างใหม่</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo number_format($actionCounts['update'] ?? 0); ?></div>
                <div class="label">แก้ไข</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo number_format($actionCounts['delete'] ?? 0); ?></div>
                <div class="label">ลบ</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo number_format($actionCounts['merge'] ?? 0); ?></div>
                <div class="label">รวม</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo number_format($actionCounts['import'] ?? 0); ?></div>
                <div class="label">นำเข้า</div>
            </div>
        </div>

        <div class="grid">
            <div class="card">
                <h2>รายการล่าสุด</h2>
                
                <form class="filters" method="GET">
                    <select name="table">
                        <option value="">-- ทุกตาราง --</option>
                        <option value="drugs" <?php echo ($_GET['table'] ?? '') === 'drugs' ? 'selected' : ''; ?>>drugs</option>
                        <option value="inventory" <?php echo ($_GET['table'] ?? '') === 'inventory' ? 'selected' : ''; ?>>inventory</option>
                        <option value="orders" <?php echo ($_GET['table'] ?? '') === 'orders' ? 'selected' : ''; ?>>orders</option>
                    </select>
                    <select name="action">
                        <option value="">-- ทุกการกระทำ --</option>
                        <option value="create" <?php echo ($_GET['action'] ?? '') === 'create' ? 'selected' : ''; ?>>สร้าง</option>
                        <option value="update" <?php echo ($_GET['action'] ?? '') === 'update' ? 'selected' : ''; ?>>แก้ไข</option>
                        <option value="delete" <?php echo ($_GET['action'] ?? '') === 'delete' ? 'selected' : ''; ?>>ลบ</option>
                    </select>
                    <input type="date" name="from" value="<?php echo $_GET['from'] ?? ''; ?>" placeholder="จากวันที่">
                    <input type="date" name="to" value="<?php echo $_GET['to'] ?? ''; ?>" placeholder="ถึงวันที่">
                    <button type="submit">🔍 กรอง</button>
                </form>
                
                <table>
                    <thead>
                        <tr>
                            <th>เวลา</th>
                            <th>ตาราง</th>
                            <th>ID</th>
                            <th>การกระทำ</th>
                            <th>ผู้ดำเนินการ</th>
                            <th>ฟิลด์ที่เปลี่ยน</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $record): ?>
                        <tr>
                            <td style="white-space: nowrap;"><?php echo date('d/m H:i', strtotime($record['created_at'])); ?></td>
                            <td><code><?php echo htmlspecialchars($record['table_name']); ?></code></td>
                            <td><?php echo $record['record_id']; ?></td>
                            <td>
                                <span class="action-badge action-<?php echo $record['action']; ?>">
                                    <?php echo $record['action']; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($record['user_name'] ?? 'System'); ?></td>
                            <td class="changes-preview"><?php echo htmlspecialchars($record['changed_fields'] ?: '-'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= min($totalPages, 10); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_filter($filters)); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>

            <div>
                <div class="card">
                    <h2>👥 ผู้ใช้ที่มีการเปลี่ยนแปลงมากที่สุด</h2>
                    <ul class="top-users-list">
                        <?php foreach ($topUsers as $user): ?>
                        <li>
                            <span class="user-name"><?php echo htmlspecialchars($user['user_name'] ?? 'Unknown'); ?></span>
                            <span class="user-count"><?php echo number_format($user['total_changes']); ?></span>
                        </li>
                        <?php endforeach; ?>
                        <?php if (empty($topUsers)): ?>
                        <li style="color: #999;">ไม่มีข้อมูล</li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="card">
                    <h2>📊 กิจกรรม 30 วัน</h2>
                    <canvas id="activityChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Prepare chart data
        const stats = <?php echo json_encode($statistics); ?>;
        const dateMap = {};
        
        stats.forEach(s => {
            if (!dateMap[s.date]) dateMap[s.date] = { create: 0, update: 0, delete: 0 };
            dateMap[s.date][s.action] = parseInt(s.count);
        });
        
        const labels = Object.keys(dateMap).sort().slice(-14);
        const creates = labels.map(d => dateMap[d]?.create || 0);
        const updates = labels.map(d => dateMap[d]?.update || 0);
        const deletes = labels.map(d => dateMap[d]?.delete || 0);

        new Chart(document.getElementById('activityChart'), {
            type: 'bar',
            data: {
                labels: labels.map(d => d.substring(5)),
                datasets: [
                    { label: 'สร้าง', data: creates, backgroundColor: '#10b981' },
                    { label: 'แก้ไข', data: updates, backgroundColor: '#3b82f6' },
                    { label: 'ลบ', data: deletes, backgroundColor: '#ef4444' }
                ]
            },
            options: {
                responsive: true,
                scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } },
                plugins: { legend: { position: 'bottom' } }
            }
        });
    </script>
</body>
</html>
