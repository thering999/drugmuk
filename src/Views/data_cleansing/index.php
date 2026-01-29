<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php echo \App\Core\CSRF::metaTag(); ?>
    <title>ทำความสะอาดข้อมูล - Drugmuk</title>
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
            max-width: 1400px;
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
            color: #333;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
            font-size: 16px;
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
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .stat-card .value {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-card .label {
            color: #999;
            font-size: 14px;
        }

        .stat-card.excellent .value { color: #10b981; }
        .stat-card.good .value { color: #3b82f6; }
        .stat-card.fair .value { color: #f59e0b; }
        .stat-card.poor .value { color: #ef4444; }

        .actions-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .actions-section h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 24px;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .btn {
            padding: 15px 25px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .btn-success:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4);
        }

        .btn-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }

        .btn-warning:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(245, 158, 11, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .btn-danger:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.4);
        }

        .quality-table {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .quality-table h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 24px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f8f9fa;
            color: #333;
            font-weight: 600;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .badge-excellent {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-good {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-fair {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-poor {
            background: #fee2e2;
            color: #991b1b;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .loading.active {
            display: block;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: white;
            text-decoration: none;
            font-size: 16px;
            padding: 10px 20px;
            background: rgba(255,255,255,0.2);
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            background: rgba(255,255,255,0.3);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: none;
        }

        .alert.show {
            display: block;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="/dashboard" class="back-link">← กลับไปหน้าหลัก</a>

        <div class="header">
            <h1>🧹 ทำความสะอาดข้อมูล</h1>
            <p>ตรวจสอบและปรับปรุงคุณภาพข้อมูลในระบบ</p>
        </div>

        <div id="alert-container"></div>

        <div class="stats-grid">
            <?php 
            // คำนวณคะแนนจาก summary
            $totalDuplicates = 0;
            $totalOrphaned = 0;
            $totalCleanup = 0;
            
            foreach ($qualitySummary as $summary) {
                if ($summary['metric_name'] == 'duplicate_candidates') {
                    $totalDuplicates = $summary['metric_value'];
                } elseif ($summary['metric_name'] == 'orphaned_records') {
                    $totalOrphaned = $summary['metric_value'];
                } elseif ($summary['metric_name'] == 'cleanup_operations') {
                    $totalCleanup = $summary['metric_value'];
                }
            }
            
            // คำนวณคะแนนโดยรวม (100 - จำนวนปัญหา)
            $totalIssues = $totalDuplicates + $totalOrphaned;
            $overallScore = max(0, 100 - ($totalIssues * 0.5)); // ลดคะแนน 0.5 ต่อปัญหา 1 รายการ
            
            $scoreClass = 'excellent';
            if ($overallScore < 90) $scoreClass = 'good';
            if ($overallScore < 75) $scoreClass = 'fair';
            if ($overallScore < 60) $scoreClass = 'poor';
            ?>
            <div class="stat-card <?php echo $scoreClass; ?>">
                <h3>คะแนนคุณภาพโดยรวม</h3>
                <div class="value"><?php echo number_format($overallScore, 1); ?></div>
                <div class="label">จาก 100 คะแนน</div>
            </div>

            <div class="stat-card <?php echo $totalDuplicates > 0 ? 'warning' : 'excellent'; ?>">
                <h3>ข้อมูลซ้ำที่พบ</h3>
                <div class="value"><?php echo $totalDuplicates; ?></div>
                <div class="label">รายการ</div>
            </div>

            <div class="stat-card <?php echo $totalOrphaned > 0 ? 'warning' : 'excellent'; ?>">
                <h3>ข้อมูลที่ไม่มี Parent</h3>
                <div class="value"><?php echo $totalOrphaned; ?></div>
                <div class="label">รายการ</div>
            </div>

            <div class="stat-card">
                <h3>การทำความสะอาด (30 วัน)</h3>
                <div class="value"><?php echo $totalCleanup; ?></div>
                <div class="label">ครั้ง</div>
            </div>
        </div>

        <div class="actions-section">
            <h2>การดำเนินการ</h2>
            <div class="action-buttons">
                <button class="btn btn-primary" onclick="runFullCheck()">
                    🔍 ตรวจสอบทั้งหมด
                </button>
                <button class="btn btn-success" onclick="detectDuplicates()">
                    👥 ตรวจหายาซ้ำ
                </button>
                <button class="btn btn-warning" onclick="detectOrphaned()">
                    🔗 ตรวจหา Orphaned Records
                </button>
                <button class="btn btn-danger" onclick="detectQualityIssues()">
                    ⚠️ ตรวจหาสินค้าที่มีปัญหา (ราคา/ข้อมูลขาด)
                </button>
                <a href="/admin/data-cleansing/duplicates" class="btn btn-primary">
                    📋 ดูรายการซ้ำ
                </a>
                <a href="/admin/data-cleansing/orphaned" class="btn btn-warning">
                    📋 ดู Orphaned & Issues
                </a>
                <a href="/admin/data-cleansing/quality-reports" class="btn btn-info" style="background: #06b6d4;">
                    📊 รายงานคุณภาพข้อมูล
                </a>
                <a href="/admin/data-cleansing/bulk-edit" class="btn" style="background: #f59e0b; color: white;">
                    ⚡ แก้ไขแบบกลุ่ม
                </a>
                <a href="/export/quality-report?format=csv" class="btn" style="background: #10b981; color: white;">
                    📥 Export CSV
                </a>
            </div>
        </div>

        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p style="margin-top: 15px; color: white;">กำลังประมวลผล...</p>
        </div>

        <div class="quality-table">
            <h2>สรุปคุณภาพข้อมูล</h2>
            <table>
                <thead>
                    <tr>
                        <th>ประเภท</th>
                        <th>จำนวน</th>
                        <th>คำอธิบาย</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($qualitySummary)): ?>
                        <tr>
                            <td colspan="3" style="text-align: center; color: #999;">
                                ยังไม่มีข้อมูล - กรุณาคลิก "ตรวจหายาซ้ำ" หรือ "ตรวจหา Orphaned Records" เพื่อเริ่มต้น
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($qualitySummary as $summary): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($summary['metric_description']); ?></strong></td>
                                <td>
                                    <span style="font-size: 20px; font-weight: bold; color: <?php echo $summary['metric_value'] > 0 ? '#f59e0b' : '#10b981'; ?>;">
                                        <?php echo $summary['metric_value']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($summary['metric_name']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($cleanupHistory)): ?>
        <div class="quality-table">
            <h2>ประวัติการทำความสะอาด (10 รายการล่าสุด)</h2>
            <table>
                <thead>
                    <tr>
                        <th>วันที่</th>
                        <th>การดำเนินการ</th>
                        <th>ตาราง</th>
                        <th>จำนวนรายการ</th>
                        <th>ผู้ดำเนินการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cleanupHistory as $history): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($history['performed_at'])); ?></td>
                            <td><?php echo htmlspecialchars($history['operation_type']); ?></td>
                            <td><?php echo htmlspecialchars($history['table_name']); ?></td>
                            <td><?php echo $history['records_affected']; ?></td>
                            <td><?php echo htmlspecialchars($history['performed_by_name'] ?? 'N/A'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Get CSRF token from meta tag or generate one
        function getCSRFToken() {
            const metaTag = document.querySelector('meta[name="csrf-token"]');
            return metaTag ? metaTag.content : '';
        }

        function showAlert(message, type = 'success') {
            const container = document.getElementById('alert-container');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} show`;
            alert.textContent = message;
            container.appendChild(alert);
            
            setTimeout(() => {
                alert.classList.remove('show');
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        }

        function showLoading() {
            document.getElementById('loading').classList.add('active');
        }

        function hideLoading() {
            document.getElementById('loading').classList.remove('active');
        }

        async function runFullCheck() {
            if (!confirm('คุณต้องการตรวจสอบคุณภาพข้อมูลทั้งหมดใช่หรือไม่?')) {
                return;
            }

            showLoading();
            try {
                const formData = new FormData();
                
                const response = await fetch('/api/data-cleansing/run-full-check', {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': getCSRFToken() },
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    showAlert('ตรวจสอบข้อมูลเสร็จสิ้น กำลังโหลดผลลัพธ์...', 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showAlert(data.message || 'เกิดข้อผิดพลาด', 'error');
                }
            } catch (error) {
                showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            } finally {
                hideLoading();
            }
        }

        async function detectDuplicates() {
            showLoading();
            try {
                const formData = new FormData();
                formData.append('threshold', 75);
                
                const response = await fetch('/api/data-cleansing/detect-duplicates', {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': getCSRFToken() },
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    showAlert(data.message, 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showAlert(data.message || 'เกิดข้อผิดพลาด', 'error');
                }
            } catch (error) {
                showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            } finally {
                hideLoading();
            }
        }

        async function detectOrphaned() {
            showLoading();
            try {
                const formData = new FormData();
                
                const response = await fetch('/api/data-cleansing/detect-orphaned', {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': getCSRFToken() },
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    let message = 'ตรวจสอบเสร็จสิ้น: ';
                    message += `Transactions: ${data.results.transactions.orphaned_found}, `;
                    message += `Order Items: ${data.results.order_items.orphaned_found}`;
                    showAlert(message, 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showAlert('เกิดข้อผิดพลาด', 'error');
                }
            } catch (error) {
                showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            } finally {
                hideLoading();
            }
        }

        async function detectQualityIssues() {
            showLoading();
            try {
                const formData = new FormData();
                
                // We'll create a new endpoint or just use runFullCheck if preferred
                // But let's assume we want a specific one or just use the controller methods via runFullCheck
                const response = await fetch('/api/data-cleansing/run-full-check', {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': getCSRFToken() },
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    let message = 'ตรวจสอบเสร็จสิ้น: ';
                    message += `หาราคาผิดปกติ: ${data.results.invalid_prices.found}, `;
                    message += `หาชื่อ/หน่วยขาด: ${data.results.missing_data.found}`;
                    showAlert(message, 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showAlert('เกิดข้อผิดพลาด', 'error');
                }
            } catch (error) {
                showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            } finally {
                hideLoading();
            }
        }
    </script>
</body>
</html>
