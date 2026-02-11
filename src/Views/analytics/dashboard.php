<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📊 Analytics Dashboard - Drugmuk</title>
    <?= \App\Core\CSRF::metaTag() ?>
    <link rel="stylesheet" href="/css/style.css">
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
            max-width: 1600px;
            margin: 0 auto;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .back-button:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateX(-5px);
        }

        .analytics-header {
            background: rgba(255, 255, 255, 0.95);
            padding: 25px 30px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .analytics-header h1 {
            color: #667eea;
            font-size: 32px;
            margin: 0;
        }

        .date-range-selector select {
            padding: 12px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            background: white;
            cursor: pointer;
            font-weight: 600;
            color: #333;
            transition: all 0.3s;
        }

        .date-range-selector select:hover {
            border-color: #667eea;
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .kpi-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
        }

        .kpi-icon {
            font-size: 56px;
            opacity: 0.9;
        }

        .kpi-content {
            flex: 1;
        }

        .kpi-content h3 {
            font-size: 13px;
            color: #666;
            margin: 0 0 10px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
        }

        .kpi-value {
            font-size: 36px;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
            line-height: 1;
        }

        .kpi-change {
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 20px;
        }

        .kpi-change.positive {
            background: #d1fae5;
            color: #065f46;
        }

        .kpi-change.negative {
            background: #fee2e2;
            color: #991b1b;
        }

        .kpi-change.neutral {
            background: #e5e7eb;
            color: #666;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .chart-container h3 {
            font-size: 20px;
            color: #333;
            margin: 0 0 25px 0;
            font-weight: 700;
        }

        .chart-container canvas {
            max-height: 320px;
        }

        .analytics-tables {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .table-section h3 {
            font-size: 22px;
            color: #333;
            margin: 0 0 25px 0;
            font-weight: 700;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        th {
            padding: 16px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 16px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }

        tbody tr:hover {
            background: #f9fafb;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .badge-normal {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-low {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-expiring {
            background: #fef3c7;
            color: #92400e;
        }

        .loading {
            text-align: center;
            padding: 60px;
            color: #666;
        }

        .spinner {
            border: 4px solid #f3f4f6;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .export-button {
            padding: 10px 20px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }

        .export-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4);
        }

        @media (max-width: 768px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
            
            .kpi-grid {
                grid-template-columns: 1fr;
            }
            
            .analytics-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Back Button -->
        <a href="/dashboard" class="back-button">
            <span style="font-size: 1.2rem;">←</span>
            <span>กลับไป Dashboard</span>
        </a>
        
        <!-- Header -->
        <div class="analytics-header">
            <div>
                <h1>📊 Analytics Dashboard</h1>
                <p style="color: #666; margin-top: 5px;">วิเคราะห์ข้อมูลและรายงานแบบละเอียด</p>
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">
                <div class="date-range-selector">
                    <select id="analytics-period" onchange="loadAnalytics()">
                        <option value="today">วันนี้</option>
                        <option value="week" selected>7 วันที่ผ่านมา</option>
                        <option value="month">30 วันที่ผ่านมา</option>
                        <option value="quarter">90 วันที่ผ่านมา</option>
                        <option value="year">1 ปีที่ผ่านมา</option>
                    </select>
                </div>
                <button class="export-button" onclick="exportReport()">
                    📥 Export Report
                </button>
            </div>
        </div>

        <!-- Loading State -->
        <div id="loading-state" class="loading">
            <div class="spinner"></div>
            <p>กำลังโหลดข้อมูล...</p>
        </div>

        <!-- KPI Cards -->
        <div id="kpi-section" style="display: none;">
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-icon">💊</div>
                    <div class="kpi-content">
                        <h3>การจ่ายยา</h3>
                        <div class="kpi-value" id="kpi-dispensing">-</div>
                        <div class="kpi-change neutral" id="kpi-dispensing-change">-</div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon">📦</div>
                    <div class="kpi-content">
                        <h3>มูลค่าสต็อก</h3>
                        <div class="kpi-value" id="kpi-inventory">-</div>
                        <div class="kpi-change neutral" id="kpi-inventory-change">-</div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon">🛒</div>
                    <div class="kpi-content">
                        <h3>ใบสั่งซื้อ</h3>
                        <div class="kpi-value" id="kpi-orders">-</div>
                        <div class="kpi-change neutral" id="kpi-orders-change">-</div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon">⚠️</div>
                    <div class="kpi-content">
                        <h3>สต็อกต่ำ</h3>
                        <div class="kpi-value" id="kpi-low-stock">-</div>
                        <div class="kpi-change neutral" id="kpi-low-stock-change">-</div>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="charts-grid">
                <div class="chart-container">
                    <h3>📈 แนวโน้มการจ่ายยา</h3>
                    <canvas id="dispensing-trend-chart"></canvas>
                </div>

                <div class="chart-container">
                    <h3>🏆 ยาขายดี Top 10</h3>
                    <canvas id="top-drugs-chart"></canvas>
                </div>

                <div class="chart-container">
                    <h3>💰 มูลค่าสต็อกตามหมวดหมู่</h3>
                    <canvas id="inventory-value-chart"></canvas>
                </div>

                <div class="chart-container">
                    <h3>⏰ ยาใกล้หมดอายุ</h3>
                    <canvas id="expiring-drugs-chart"></canvas>
                </div>
            </div>

            <!-- Detailed Tables -->
            <div class="analytics-tables">
                <div class="table-section">
                    <h3>📊 สรุปรายการยา</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>ชื่อยา</th>
                                <th>จำนวนจ่าย</th>
                                <th>มูลค่า (บาท)</th>
                                <th>สต็อกคงเหลือ</th>
                                <th>สถานะ</th>
                            </tr>
                        </thead>
                        <tbody id="drug-summary-tbody">
                            <tr><td colspan="5" class="loading">กำลังโหลด...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        let charts = {};

        // Load analytics data
        async function loadAnalytics() {
            const period = document.getElementById('analytics-period').value;
            
            // Show loading
            document.getElementById('loading-state').style.display = 'block';
            document.getElementById('kpi-section').style.display = 'none';
            
            try {
                const response = await fetch(`/api/analytics/dashboard?period=${period}`);
                const data = await response.json();
                
                if (data.success) {
                    updateKPIs(data.kpis);
                    updateCharts(data.charts);
                    updateTables(data.tables);
                    
                    // Hide loading, show content
                    document.getElementById('loading-state').style.display = 'none';
                    document.getElementById('kpi-section').style.display = 'block';
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                }
            } catch (error) {
                console.error('Error loading analytics:', error);
                alert('ไม่สามารถโหลดข้อมูลได้');
            }
        }

        // Update KPI cards
        function updateKPIs(kpis) {
            // Dispensing
            document.getElementById('kpi-dispensing').textContent = kpis.dispensing.value.toLocaleString();
            updateChangeIndicator('kpi-dispensing-change', kpis.dispensing.change);
            
            // Inventory
            document.getElementById('kpi-inventory').textContent = `฿${kpis.inventory.value.toLocaleString()}`;
            updateChangeIndicator('kpi-inventory-change', kpis.inventory.change);
            
            // Orders
            document.getElementById('kpi-orders').textContent = kpis.orders.value.toLocaleString();
            updateChangeIndicator('kpi-orders-change', kpis.orders.change);
            
            // Low Stock
            document.getElementById('kpi-low-stock').textContent = kpis.lowStock.value.toLocaleString();
            updateChangeIndicator('kpi-low-stock-change', kpis.lowStock.change);
        }

        function updateChangeIndicator(elementId, change) {
            const element = document.getElementById(elementId);
            const prefix = change > 0 ? '↑ +' : change < 0 ? '↓ ' : '';
            element.textContent = `${prefix}${change}%`;
            
            element.className = 'kpi-change';
            if (change > 0) {
                element.classList.add('positive');
            } else if (change < 0) {
                element.classList.add('negative');
            } else {
                element.classList.add('neutral');
            }
        }

        // Update charts
        function updateCharts(chartsData) {
            // Dispensing Trend Chart
            if (charts.dispensingTrend) charts.dispensingTrend.destroy();
            charts.dispensingTrend = new Chart(document.getElementById('dispensing-trend-chart'), {
                type: 'line',
                data: {
                    labels: chartsData.dispensingTrend.labels,
                    datasets: [{
                        label: 'จำนวนการจ่ายยา',
                        data: chartsData.dispensingTrend.data,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Top Drugs Chart
            if (charts.topDrugs) charts.topDrugs.destroy();
            charts.topDrugs = new Chart(document.getElementById('top-drugs-chart'), {
                type: 'bar',
                data: {
                    labels: chartsData.topDrugs.labels,
                    datasets: [{
                        label: 'จำนวนจ่าย',
                        data: chartsData.topDrugs.data,
                        backgroundColor: '#10b981'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    indexAxis: 'y',
                    plugins: {
                        legend: { display: false }
                    }
                }
            });

            // Inventory Value Chart
            if (charts.inventoryValue) charts.inventoryValue.destroy();
            charts.inventoryValue = new Chart(document.getElementById('inventory-value-chart'), {
                type: 'doughnut',
                data: {
                    labels: chartsData.inventoryValue.labels,
                    datasets: [{
                        data: chartsData.inventoryValue.data,
                        backgroundColor: ['#667eea', '#764ba2', '#f59e0b', '#10b981', '#ef4444']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true
                }
            });

            // Expiring Drugs Chart
            if (charts.expiringDrugs) charts.expiringDrugs.destroy();
            charts.expiringDrugs = new Chart(document.getElementById('expiring-drugs-chart'), {
                type: 'bar',
                data: {
                    labels: chartsData.expiringDrugs.labels,
                    datasets: [{
                        label: 'จำนวนรายการ',
                        data: chartsData.expiringDrugs.data,
                        backgroundColor: ['#ef4444', '#f59e0b', '#10b981']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }

        // Update tables
        function updateTables(tables) {
            const tbody = document.getElementById('drug-summary-tbody');
            tbody.innerHTML = '';
            
            if (tables.drugSummary.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 40px; color: #666;">ไม่มีข้อมูล</td></tr>';
                return;
            }
            
            tables.drugSummary.forEach(drug => {
                const row = `
                    <tr>
                        <td><strong>${drug.name}</strong></td>
                        <td>${drug.dispensed.toLocaleString()}</td>
                        <td>฿${drug.value.toLocaleString()}</td>
                        <td>${drug.stock.toLocaleString()}</td>
                        <td><span class="badge badge-${drug.status}">${drug.statusText}</span></td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        }

        // Export report
        function exportReport() {
            const period = document.getElementById('analytics-period').value;
            window.location.href = `/api/analytics/export?period=${period}`;
        }

        // Load on page load
        document.addEventListener('DOMContentLoaded', loadAnalytics);
    </script>
</body>
</html>
