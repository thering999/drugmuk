<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สถิติการจ่ายยา - Drugmuk</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

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
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #667eea;
            font-size: 28px;
        }

        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
        }

        .stat-value {
            font-size: 42px;
            font-weight: bold;
            margin: 10px 0;
        }

        .stat-label {
            font-size: 16px;
            opacity: 0.9;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            display: inline-block;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 500;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .chart-container {
            position: relative;
            height: 400px;
            margin: 20px 0;
        }

        .period-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .period-btn {
            padding: 8px 16px;
            border: 2px solid #667eea;
            background: white;
            color: #667eea;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .period-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .period-btn:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 สถิติการจ่ายยา</h1>
            <a href="/dispensing" class="btn btn-secondary">← กลับ</a>
        </div>

        <!-- Summary Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">จ่ายยาทั้งหมด</div>
                <div class="stat-value"><?= number_format($stats['total_dispensing'] ?? 0) ?></div>
                <div class="stat-label">ครั้ง</div>
            </div>

            <div class="stat-card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                <div class="stat-label">ผู้ป่วยทั้งหมด</div>
                <div class="stat-value"><?= number_format($stats['total_patients'] ?? 0) ?></div>
                <div class="stat-label">คน</div>
            </div>

            <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                <div class="stat-label">รายการยาทั้งหมด</div>
                <div class="stat-value"><?= number_format($stats['total_items'] ?? 0) ?></div>
                <div class="stat-label">รายการ</div>
            </div>

            <div class="stat-card" style="background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);">
                <div class="stat-label">เฉลี่ยต่อครั้ง</div>
                <div class="stat-value"><?= number_format($stats['avg_items_per_dispensing'] ?? 0, 1) ?></div>
                <div class="stat-label">รายการ</div>
            </div>
        </div>

        <!-- Top Dispensed Drugs -->
        <div class="card">
            <h2 style="margin-bottom: 20px;">🏆 ยาที่จ่ายบ่อยที่สุด (Top 20)</h2>
            
            <table>
                <thead>
                    <tr>
                        <th style="width: 10%;">อันดับ</th>
                        <th style="width: 20%;">รหัสยา</th>
                        <th style="width: 40%;">ชื่อยา</th>
                        <th style="width: 15%;">จำนวนครั้ง</th>
                        <th style="width: 15%;">จำนวนรวม</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $rank = 1; foreach ($topDrugs as $drug): ?>
                    <tr>
                        <td style="text-align: center; font-weight: bold;">
                            <?php if ($rank == 1): ?>
                                🥇
                            <?php elseif ($rank == 2): ?>
                                🥈
                            <?php elseif ($rank == 3): ?>
                                🥉
                            <?php else: ?>
                                <?= $rank ?>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($drug['drug_code']) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($drug['drug_name']) ?></strong>
                            <?php if ($drug['generic_name']): ?>
                            <br><small style="color: #666;">(<?= htmlspecialchars($drug['generic_name']) ?>)</small>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <strong><?= number_format($drug['dispense_count']) ?></strong> ครั้ง
                        </td>
                        <td style="text-align: right;">
                            <strong><?= number_format($drug['total_quantity']) ?></strong> <?= htmlspecialchars($drug['unit']) ?>
                        </td>
                    </tr>
                    <?php $rank++; endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Monthly Trend Chart -->
        <div class="card">
            <h2 style="margin-bottom: 20px;">📈 แนวโน้มการจ่ายยารายเดือน</h2>
            
            <div class="period-selector">
                <button class="period-btn active" onclick="changePeriod('6months')">6 เดือน</button>
                <button class="period-btn" onclick="changePeriod('12months')">12 เดือน</button>
                <button class="period-btn" onclick="changePeriod('all')">ทั้งหมด</button>
            </div>

            <div class="chart-container">
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>

        <!-- Daily Activity Chart -->
        <div class="card">
            <h2 style="margin-bottom: 20px;">📅 การจ่ายยารายวัน (7 วันล่าสุด)</h2>
            
            <div class="chart-container">
                <canvas id="dailyChart"></canvas>
            </div>
        </div>
    </div>

    <script>
        // Monthly Trend Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyChart = new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($monthlyTrend, 'month')) ?>,
                datasets: [{
                    label: 'จำนวนครั้งที่จ่ายยา',
                    data: <?= json_encode(array_column($monthlyTrend, 'count')) ?>,
                    borderColor: 'rgb(102, 126, 234)',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });

        // Daily Activity Chart
        const dailyCtx = document.getElementById('dailyChart').getContext('2d');
        const dailyChart = new Chart(dailyCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($dailyActivity, 'date')) ?>,
                datasets: [{
                    label: 'จำนวนครั้งที่จ่ายยา',
                    data: <?= json_encode(array_column($dailyActivity, 'count')) ?>,
                    backgroundColor: 'rgba(102, 126, 234, 0.8)',
                    borderColor: 'rgb(102, 126, 234)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });

        function changePeriod(period) {
            // Update active button
            document.querySelectorAll('.period-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');

            // Fetch new data from API
            fetch(`/api/dispensing/statistics?period=${period}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update monthly chart
                        monthlyChart.data.labels = data.data.monthlyTrend.map(item => item.month);
                        monthlyChart.data.datasets[0].data = data.data.monthlyTrend.map(item => item.count);
                        monthlyChart.update();
                        
                        console.log('Chart updated for period:', period);
                    } else {
                        console.error('Failed to load data:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error fetching statistics:', error);
                });
        }
    </script>
</body>
</html>
