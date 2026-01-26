<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการ Import - Drugmuk</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 32px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .stat-value {
            font-size: 42px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        .filters {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .filter-row {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .filter-group label {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        
        .filter-group input, .filter-group select {
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .filter-group input:focus, .filter-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
        }
        
        .timeline-item {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            position: relative;
            transition: all 0.3s;
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -26px;
            top: 25px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: white;
            border: 3px solid #667eea;
            box-shadow: 0 0 0 4px white;
        }
        
        .timeline-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .timeline-title {
            font-size: 18px;
            font-weight: 700;
            color: #333;
        }
        
        .timeline-date {
            color: #999;
            font-size: 13px;
        }
        
        .timeline-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .timeline-stat {
            text-align: center;
            padding: 10px;
            background: #f8f9ff;
            border-radius: 8px;
        }
        
        .timeline-stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #667eea;
        }
        
        .timeline-stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .timeline-actions {
            display: flex;
            gap: 10px;
        }
        
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            padding: 20px;
            background: white;
            border-radius: 15px;
            margin-top: 20px;
        }
        
        .pagination button {
            padding: 8px 16px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .pagination button:hover:not(:disabled) {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .pagination button.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .empty-state {
            background: white;
            padding: 60px;
            border-radius: 15px;
            text-align: center;
            color: #999;
        }
        
        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .chart-container {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .chart-title {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            margin-bottom: 20px;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #11998e 0%, #38ef7d 100%);
            transition: width 0.3s ease;
        }
        
        .back-btn {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }
        
        .back-btn:hover {
            background: #667eea;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>📜 ประวัติการ Import ข้อมูล</h1>
            <a href="/dashboard" class="btn back-btn">← กลับหน้าหลัก</a>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= number_format($stats['total_imports'] ?? 0) ?></div>
                <div class="stat-label">ครั้งทั้งหมด</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format($stats['total_imported'] ?? 0) ?></div>
                <div class="stat-label">Import สำเร็จ</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format($stats['total_updated'] ?? 0) ?></div>
                <div class="stat-label">อัพเดท</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format($stats['total_skipped'] ?? 0) ?></div>
                <div class="stat-label">ข้าม</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format($stats['total_processed'] ?? 0) ?></div>
                <div class="stat-label">ประมวลผลทั้งหมด</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters">
            <form method="GET" action="/import-history">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>วันที่เริ่มต้น</label>
                        <input type="date" name="start_date" value="<?= htmlspecialchars($startDate ?? '') ?>">
                    </div>
                    <div class="filter-group">
                        <label>วันที่สิ้นสุด</label>
                        <input type="date" name="end_date" value="<?= htmlspecialchars($endDate ?? '') ?>">
                    </div>
                    <div class="filter-group">
                        <label>แหล่งที่มา</label>
                        <select name="source">
                            <option value="">ทั้งหมด</option>
                            <?php foreach ($sources ?? [] as $source): ?>
                                <option value="<?= htmlspecialchars($source) ?>" <?= ($selectedSource ?? '') === $source ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($source) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary">🔍 ค้นหา</button>
                    </div>
                    <div class="filter-group">
                        <label>&nbsp;</label>
                        <a href="/import-history/export?start_date=<?= urlencode($startDate ?? '') ?>&end_date=<?= urlencode($endDate ?? '') ?>&source=<?= urlencode($selectedSource ?? '') ?>" class="btn btn-success">
                            📊 Export Excel
                        </a>
                    </div>
                    <div class="filter-group">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-danger" onclick="clearOldHistory()">
                            🗑️ ลบประวัติเก่า
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Success Rate Chart -->
        <?php if (!empty($history)): ?>
        <div class="chart-container">
            <div class="chart-title">📈 อัตราความสำเร็จ</div>
            <?php 
                $totalProcessed = $stats['total_processed'] ?? 1;
                $successRate = $totalProcessed > 0 ? (($stats['total_imported'] ?? 0) / $totalProcessed * 100) : 0;
                $updateRate = $totalProcessed > 0 ? (($stats['total_updated'] ?? 0) / $totalProcessed * 100) : 0;
                $skipRate = $totalProcessed > 0 ? (($stats['total_skipped'] ?? 0) / $totalProcessed * 100) : 0;
            ?>
            <div style="margin-bottom: 15px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span>Import สำเร็จ</span>
                    <span style="font-weight: 700; color: #11998e;"><?= number_format($successRate, 1) ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= $successRate ?>%; background: linear-gradient(90deg, #11998e 0%, #38ef7d 100%);"></div>
                </div>
            </div>
            <div style="margin-bottom: 15px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span>อัพเดท</span>
                    <span style="font-weight: 700; color: #667eea;"><?= number_format($updateRate, 1) ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= $updateRate ?>%; background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);"></div>
                </div>
            </div>
            <div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span>ข้าม</span>
                    <span style="font-weight: 700; color: #f59e0b;"><?= number_format($skipRate, 1) ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= $skipRate ?>%; background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%);"></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Timeline -->
        <?php if (!empty($history)): ?>
            <div class="timeline">
                <?php foreach ($history as $record): ?>
                    <div class="timeline-item">
                        <div class="timeline-header">
                            <div>
                                <div class="timeline-title">
                                    <?= htmlspecialchars($record['source']) ?>
                                    <?php 
                                        $totalCount = $record['total_count'] ?? ($record['imported_count'] + $record['updated_count'] + $record['skipped_count']);
                                        $successRate = $totalCount > 0 ? (($record['imported_count'] + $record['updated_count']) / $totalCount * 100) : 0;
                                    ?>
                                    <?php if ($successRate >= 90): ?>
                                        <span class="badge badge-success">✓ สำเร็จ</span>
                                    <?php elseif ($successRate >= 50): ?>
                                        <span class="badge badge-warning">⚠ บางส่วน</span>
                                    <?php else: ?>
                                        <span class="badge badge-info">ℹ ข้อมูล</span>
                                    <?php endif; ?>
                                </div>
                                <div class="timeline-date">
                                    📅 <?= date('d/m/Y H:i:s', strtotime($record['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="timeline-stats">
                            <div class="timeline-stat">
                                <div class="timeline-stat-value" style="color: #11998e;">
                                    <?= number_format($record['imported_count']) ?>
                                </div>
                                <div class="timeline-stat-label">Import สำเร็จ</div>
                            </div>
                            <div class="timeline-stat">
                                <div class="timeline-stat-value" style="color: #667eea;">
                                    <?= number_format($record['updated_count']) ?>
                                </div>
                                <div class="timeline-stat-label">อัพเดท</div>
                            </div>
                            <div class="timeline-stat">
                                <div class="timeline-stat-value" style="color: #f59e0b;">
                                    <?= number_format($record['skipped_count']) ?>
                                </div>
                                <div class="timeline-stat-label">ข้าม</div>
                            </div>
                            <div class="timeline-stat">
                                <div class="timeline-stat-value" style="color: #333;">
                                    <?= number_format($totalCount) ?>
                                </div>
                                <div class="timeline-stat-label">ทั้งหมด</div>
                            </div>
                        </div>
                        
                        <?php if (!empty($record['error_message'])): ?>
                            <div style="padding: 10px; background: #fee2e2; border-left: 4px solid #ef4444; border-radius: 5px; margin-bottom: 10px;">
                                <strong style="color: #991b1b;">❌ Error:</strong>
                                <span style="color: #991b1b;"><?= htmlspecialchars($record['error_message']) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="timeline-actions">
                            <button class="btn btn-danger" onclick="deleteHistory(<?= $record['id'] ?>)" style="padding: 8px 16px; font-size: 13px;">
                                🗑️ ลบ
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if (($totalPages ?? 0) > 1): ?>
                <div class="pagination">
                    <button onclick="goToPage(<?= max(1, ($page ?? 1) - 1) ?>)" <?= ($page ?? 1) <= 1 ? 'disabled' : '' ?>>
                        ← ก่อนหน้า
                    </button>
                    
                    <?php for ($i = 1; $i <= min($totalPages, 10); $i++): ?>
                        <button onclick="goToPage(<?= $i ?>)" class="<?= $i == ($page ?? 1) ? 'active' : '' ?>">
                            <?= $i ?>
                        </button>
                    <?php endfor; ?>
                    
                    <button onclick="goToPage(<?= min($totalPages, ($page ?? 1) + 1) ?>)" <?= ($page ?? 1) >= $totalPages ? 'disabled' : '' ?>>
                        ถัดไป →
                    </button>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <h2>ยังไม่มีประวัติการ Import</h2>
                <p>เมื่อมีการ Import ข้อมูล ประวัติจะแสดงที่นี่</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function goToPage(page) {
            const params = new URLSearchParams(window.location.search);
            params.set('page', page);
            window.location.href = '/import-history?' + params.toString();
        }

        async function deleteHistory(id) {
            if (!confirm('ต้องการลบประวัติรายการนี้?')) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('id', id);

                const response = await fetch('/import-history/delete', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                if (data.success) {
                    alert('✅ ' + data.message);
                    location.reload();
                } else {
                    alert('❌ ' + data.message);
                }
            } catch (error) {
                alert('❌ เกิดข้อผิดพลาด: ' + error.message);
            }
        }

        async function clearOldHistory() {
            const days = prompt('ลบประวัติที่เก่ากว่ากี่วัน?', '90');
            if (!days || isNaN(days)) {
                return;
            }

            if (!confirm(`ต้องการลบประวัติที่เก่ากว่า ${days} วัน?`)) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('days', days);

                const response = await fetch('/import-history/clear-old', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                if (data.success) {
                    alert('✅ ' + data.message);
                    location.reload();
                } else {
                    alert('❌ ' + data.message);
                }
            } catch (error) {
                alert('❌ เกิดข้อผิดพลาด: ' + error.message);
            }
        }
    </script>
</body>
</html>
