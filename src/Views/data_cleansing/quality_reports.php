<?php
/**
 * Data Quality Reports View
 * แสดงรายงานคุณภาพข้อมูลและแนวโน้ม
 */

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานคุณภาพข้อมูล - Drugmuk</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh; padding: 2rem;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        .header {
            background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(20px);
            border-radius: 20px; padding: 2rem; margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.3); color: white;
        }
        .back-button {
            display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem;
            background: rgba(255, 255, 255, 0.2); color: white; text-decoration: none;
            border-radius: 10px; margin-bottom: 1rem; transition: all 0.3s ease;
        }
        .back-button:hover { background: rgba(255, 255, 255, 0.3); }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-bottom: 2rem; }
        .card {
            background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(20px);
            border-radius: 20px; padding: 2rem; border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .card-title { color: white; font-size: 1.5rem; font-weight: 600; margin-bottom: 1rem; }
        .stat-number { font-size: 3rem; font-weight: 700; color: white; margin: 1rem 0; }
        .stat-label { font-size: 1rem; color: rgba(255, 255, 255, 0.8); }
        .chart-container {
            background: white; border-radius: 15px; padding: 2rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1); margin-bottom: 2rem;
        }
        .chart-title { font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem; color: #374151; }
    </style>
</head>
<body>
    <div class="container">
        <a href="/dashboard" class="back-button">
            <span>←</span><span>กลับหน้าหลัก</span>
        </a>

        <div class="header">
            <h1>📊 รายงานคุณภาพข้อมูล</h1>
            <p>ติดตามและวิเคราะห์คุณภาพข้อมูลในระบบ</p>
        </div>

        <!-- Summary Cards -->
        <div class="grid">
            <div class="card">
                <div class="card-title">คะแนนคุณภาพรวม</div>
                <div class="stat-number"><?php echo number_format($qualitySummary['overall_score'] ?? 0, 1); ?>%</div>
                <div class="stat-label">Overall Quality Score</div>
            </div>

            <div class="card">
                <div class="card-title">ยาที่ซ้ำกัน</div>
                <div class="stat-number"><?php echo number_format($qualitySummary['duplicate_count'] ?? 0); ?></div>
                <div class="stat-label">รายการที่ตรวจพบ</div>
            </div>

            <div class="card">
                <div class="card-title">Orphaned Records</div>
                <div class="stat-number"><?php echo number_format($qualitySummary['orphaned_count'] ?? 0); ?></div>
                <div class="stat-label">รายการที่ต้องแก้ไข</div>
            </div>
        </div>

        <!-- Trend Chart -->
        <div class="chart-container">
            <h2 class="chart-title">แนวโน้มคุณภาพข้อมูล (30 วันล่าสุด)</h2>
            <canvas id="qualityTrendChart"></canvas>
        </div>
    </div>

    <script>
        // Prepare data for chart
        const trendData = <?php echo json_encode(array_reverse($qualityTrends)); ?>;
        const labels = trendData.map(item => item.check_date);
        const totalChecks = trendData.map(item => parseInt(item.total_checks));
        const resolvedCount = trendData.map(item => parseInt(item.resolved_count));

        // Create chart
        const ctx = document.getElementById('qualityTrendChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'การตรวจสอบทั้งหมด',
                        data: totalChecks,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'แก้ไขแล้ว',
                        data: resolvedCount,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                    title: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    </script>
</body>
</html>
