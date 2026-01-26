<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Drugmuk</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5a67d8;
            --secondary: #764ba2;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --dark: #1f2937;
            --light: #f3f4f6;
            --white: #ffffff;
            --shadow: 0 10px 40px rgba(0,0,0,0.1);
            --shadow-hover: 0 20px 60px rgba(0,0,0,0.15);
            --radius: 16px;
            --radius-sm: 10px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Sarabun', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
        }

        /* Header */
        .header {
            background: var(--white);
            padding: 20px 30px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: var(--primary);
            font-size: 26px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-info span {
            color: #666;
            font-size: 14px;
            background: var(--light);
            padding: 8px 15px;
            border-radius: 20px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        /* Navigation */
        .nav-bar {
            background: var(--white);
            padding: 15px 25px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 25px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .nav-bar a {
            padding: 10px 18px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            text-decoration: none;
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .nav-bar a:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.35);
        }

        .nav-bar a.active {
            background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
        }

        .nav-bar a.warning {
            background: linear-gradient(135deg, var(--warning) 0%, #d97706 100%);
        }

        .nav-bar a.info {
            background: linear-gradient(135deg, var(--info) 0%, #2563eb 100%);
        }

        /* Session Messages */
        .session-message {
            padding: 15px 20px;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
            animation: slideIn 0.4s ease-out;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .session-message.success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border-left: 4px solid var(--success);
            color: #065f46;
        }

        .session-message.error {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border-left: 4px solid var(--danger);
            color: #991b1b;
        }

        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Metrics Grid */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .metric-card {
            background: var(--white);
            padding: 25px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, var(--primary) 0%, var(--secondary) 100%);
        }

        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .metric-card h3 {
            color: #666;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .metric-card .value {
            font-size: 38px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .metric-card.warning::before { background: var(--warning); }
        .metric-card.danger::before { background: var(--danger); }
        .metric-card.success::before { background: var(--success); }

        /* Charts Grid */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 25px;
            margin-bottom: 25px;
        }

        .chart-card {
            background: var(--white);
            padding: 25px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .chart-card h2 {
            color: var(--dark);
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-container {
            position: relative;
            height: 280px;
        }

        /* Alerts Section */
        .alerts-section {
            background: var(--white);
            padding: 25px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 25px;
        }

        .alerts-section h2 {
            color: var(--dark);
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-item {
            padding: 15px 20px;
            margin-bottom: 12px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
        }

        .alert-item:hover {
            transform: translateX(5px);
        }

        .alert-item.warning {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-left: 4px solid var(--warning);
        }

        .alert-item.danger {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border-left: 4px solid var(--danger);
        }

        .alert-item.info {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border-left: 4px solid var(--info);
        }

        .alert-icon {
            font-size: 24px;
        }

        .alert-item a {
            color: var(--primary);
            font-weight: 500;
            text-decoration: none;
            margin-left: auto;
        }

        .alert-item a:hover {
            text-decoration: underline;
        }

        /* Activity Grid */
        .activity-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
        }

        .activity-card {
            background: var(--white);
            padding: 25px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .activity-card h2 {
            color: var(--dark);
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .activity-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            transition: all 0.3s ease;
        }

        .activity-item:hover {
            background: var(--light);
            border-radius: var(--radius-sm);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-item .date {
            color: #999;
            font-size: 12px;
        }

        .activity-item .title {
            color: var(--dark);
            font-weight: 500;
            margin-top: 5px;
        }

        .no-data {
            color: #999;
            text-align: center;
            padding: 20px;
        }

        /* Top Drugs Table */
        .top-drugs-table {
            width: 100%;
            border-collapse: collapse;
        }

        .top-drugs-table th,
        .top-drugs-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .top-drugs-table th {
            background: var(--light);
            font-weight: 600;
            color: var(--dark);
        }

        .top-drugs-table tr:hover {
            background: #f9fafb;
        }

        /* Real-time indicator */
        .realtime-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: #666;
        }

        .realtime-dot {
            width: 8px;
            height: 8px;
            background: var(--success);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .charts-grid, .activity-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .nav-bar {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($_SESSION['success'])): ?>
        <div class="session-message success">
            ✅ <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
        <div class="session-message error">
            ❌ <?= htmlspecialchars($_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); endif; ?>

        <!-- Header -->
        <div class="header">
            <h1>🏥 Drugmuk - ระบบบริหารคลังเวชภัณฑ์ยา</h1>
            <div class="user-info">
                <div class="realtime-indicator">
                    <span class="realtime-dot"></span>
                    <span>Real-time</span>
                </div>
                <span>👤 <?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?> (<?= htmlspecialchars($_SESSION['role'] ?? 'N/A') ?>)</span>
                <a href="/logout" class="btn btn-danger">🚪 ออก</a>
            </div>
        </div>

        <!-- Navigation -->
        <div class="nav-bar">
            <a href="/dashboard" class="active">🏠 หน้าหลัก</a>
            <a href="/purchasing">📊 แผนซื้อ</a>
            <a href="/orders">🛒 สั่งซื้อ</a>
            <a href="/warehouse">🏭 คลังใหญ่</a>
            <a href="/subwarehouse">🏪 คลังย่อย</a>
            <a href="/dispensing" class="warning">💊 จ่ายยา</a>
            <a href="/contracts">📄 สัญญา</a>
            <a href="/admin/jhcis/dashboard" class="info">🔗 JHCIS</a>
            <a href="/admin/intelligence">🧠 AI Center</a>
            <a href="/export">📥 Export</a>
            <a href="/notifications/settings">🔔 แจ้งเตือน</a>
            <a href="/reports">📊 รายงาน</a>
            <a href="/settings/database">⚙️ ตั้งค่า</a>
        </div>

        <!-- Metrics -->
        <div class="metrics-grid">
            <div class="metric-card warning">
                <h3>📦 ยาใกล้หมดสต็อก</h3>
                <div class="value"><?= $metrics['low_stock_count'] ?? 0 ?></div>
            </div>
            <div class="metric-card danger">
                <h3>⏰ ยาใกล้หมดอายุ (90 วัน)</h3>
                <div class="value"><?= $metrics['expiring_soon_count'] ?? 0 ?></div>
            </div>
            <div class="metric-card">
                <h3>📋 ใบสั่งซื้อค้างรับ</h3>
                <div class="value"><?= $metrics['pending_orders_count'] ?? 0 ?></div>
            </div>
            <div class="metric-card warning">
                <h3>📄 สัญญาใกล้หมดอายุ</h3>
                <div class="value"><?= $metrics['expiring_contracts_count'] ?? 0 ?></div>
            </div>
            <div class="metric-card success">
                <h3>🔔 คำขอเบิกค้างอนุมัติ</h3>
                <div class="value"><?= $metrics['pending_disbursements_count'] ?? 0 ?></div>
            </div>
        </div>

        <!-- Charts -->
        <div class="charts-grid">
            <div class="chart-card">
                <h2>📈 การจ่ายยา 7 วันย้อนหลัง</h2>
                <div class="chart-container">
                    <canvas id="dispensingChart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <h2>🥧 มูลค่าสต็อกแยกตามหมวดหมู่</h2>
                <div class="chart-container">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <h2>⏰ ยาใกล้หมดอายุแยกตามช่วงเวลา</h2>
                <div class="chart-container">
                    <canvas id="expiringChart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <h2>📊 การสั่งซื้อ 6 เดือนย้อนหลัง</h2>
                <div class="chart-container">
                    <canvas id="orderChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (!empty($alerts)): ?>
        <div class="alerts-section">
            <h2>🔔 การแจ้งเตือน</h2>
            <?php foreach (array_slice($alerts, 0, 10) as $alert): ?>
            <div class="alert-item <?= $alert['type'] ?>">
                <span class="alert-icon"><?= $alert['icon'] === 'box' ? '📦' : ($alert['icon'] === 'clock' ? '⏰' : '📄') ?></span>
                <div><?= htmlspecialchars($alert['message']) ?></div>
                <?php if (isset($alert['link'])): ?>
                <a href="<?= $alert['link'] ?>">ดูรายละเอียด →</a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Activity & Top Drugs -->
        <div class="activity-grid">
            <div class="activity-card">
                <h2>📋 ใบสั่งซื้อล่าสุด</h2>
                <?php if (!empty($recent_orders)): ?>
                    <?php foreach ($recent_orders as $order): ?>
                    <div class="activity-item">
                        <div class="date"><?= date('d/m/Y', strtotime($order['order_date'])) ?></div>
                        <div class="title">
                            <?= htmlspecialchars($order['order_no']) ?> - 
                            <?= htmlspecialchars($order['supplier_name'] ?? 'N/A') ?>
                            (<?= number_format($order['total_amount'], 2) ?> บาท)
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-data">ไม่มีข้อมูล</p>
                <?php endif; ?>
            </div>

            <div class="activity-card">
                <h2>🏆 Top 10 ยาที่ใช้มากที่สุด (30 วัน)</h2>
                <?php if (!empty($chart_data['top_drugs'])): ?>
                <table class="top-drugs-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>ชื่อยา</th>
                            <th>จำนวน</th>
                            <th>ครั้ง</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($chart_data['top_drugs'] as $i => $drug): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($drug['name']) ?></td>
                            <td><?= number_format($drug['total_quantity']) ?></td>
                            <td><?= number_format($drug['dispense_count']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p class="no-data">ไม่มีข้อมูล</p>
                <?php endif; ?>
            </div>

            <div class="activity-card">
                <h2>📦 การรับยาล่าสุด</h2>
                <?php if (!empty($recent_receives)): ?>
                    <?php foreach ($recent_receives as $receive): ?>
                    <div class="activity-item">
                        <div class="date"><?= date('d/m/Y H:i', strtotime($receive['transaction_date'])) ?></div>
                        <div class="title">
                            <?= htmlspecialchars($receive['drug_name']) ?> 
                            (<?= $receive['quantity'] ?> หน่วย)
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-data">ไม่มีข้อมูล</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Chart Data from PHP
        const chartData = <?= json_encode($chart_data ?? []) ?>;

        // Color palette
        const colors = {
            primary: '#667eea',
            secondary: '#764ba2',
            success: '#10b981',
            warning: '#f59e0b',
            danger: '#ef4444',
            info: '#3b82f6'
        };

        // 1. Dispensing Trend Chart
        if (chartData.dispensing_trend) {
            new Chart(document.getElementById('dispensingChart'), {
                type: 'line',
                data: {
                    labels: chartData.dispensing_trend.labels,
                    datasets: [{
                        label: 'จำนวนครั้ง',
                        data: chartData.dispensing_trend.counts,
                        borderColor: colors.primary,
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 6,
                        pointBackgroundColor: colors.primary
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }

        // 2. Stock by Category Chart
        if (chartData.stock_by_category) {
            new Chart(document.getElementById('categoryChart'), {
                type: 'doughnut',
                data: {
                    labels: chartData.stock_by_category.labels,
                    datasets: [{
                        data: chartData.stock_by_category.values,
                        backgroundColor: [
                            '#667eea', '#764ba2', '#10b981', '#f59e0b', 
                            '#ef4444', '#3b82f6', '#8b5cf6', '#ec4899'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: { font: { size: 11 } }
                        }
                    }
                }
            });
        }

        // 3. Expiring by Period Chart
        if (chartData.expiring_by_period) {
            new Chart(document.getElementById('expiringChart'), {
                type: 'bar',
                data: {
                    labels: chartData.expiring_by_period.labels,
                    datasets: [{
                        label: 'จำนวน Lot',
                        data: chartData.expiring_by_period.counts,
                        backgroundColor: chartData.expiring_by_period.colors
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }

        // 4. Order Trend Chart
        if (chartData.order_trend) {
            new Chart(document.getElementById('orderChart'), {
                type: 'bar',
                data: {
                    labels: chartData.order_trend.labels,
                    datasets: [{
                        label: 'มูลค่า (บาท)',
                        data: chartData.order_trend.amounts,
                        backgroundColor: 'rgba(102, 126, 234, 0.7)',
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString() + ' ฿';
                                }
                            }
                        }
                    }
                }
            });
        }

        // Auto-refresh metrics every 5 minutes
        setInterval(() => {
            fetch('/api/dashboard/metrics')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        console.log('Metrics updated:', data.data);
                    }
                });
        }, 300000);
    </script>
</body>
</html>
