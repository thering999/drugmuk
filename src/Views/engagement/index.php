<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Adherence Monitoring - Drugmuk</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background: #f3f4f6; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1 { color: #4f46e5; margin-bottom: 20px; border-bottom: 2px solid #e5e7eb; padding-bottom: 15px; }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card {
            background: #fff; border: 1px solid #e5e7eb; padding: 20px; border-radius: 10px; text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05); transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-number { font-size: 32px; font-weight: bold; color: #4f46e5; margin: 10px 0; }
        .stat-label { color: #6b7280; font-size: 14px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #f9fafb; padding: 12px; text-align: left; font-weight: 600; color: #374151; border-bottom: 2px solid #e5e7eb; }
        td { padding: 12px; border-bottom: 1px solid #e5e7eb; color: #4b5563; }
        tr:hover { background: #f9fafb; }
        
        .status-badge {
            padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;
        }
        .status-good { background: #d1fae5; color: #065f46; }
        .status-risk { background: #fee2e2; color: #991b1b; }
        
        .btn-action {
            background: #4f46e5; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 12px;
            text-decoration: none; display: inline-block;
        }
        .btn-action:hover { background: #4338ca; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Patient Adherence Monitoring (ระบบติดตามการกินยา)</h1>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Active Patients (ผู้ป่วยในระบบ)</div>
                <div class="stat-number"><?= number_format($stats['active_patients']) ?></div>
                <div style="color: green; font-size: 12px;">Based on 30-day activity</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Low Adherence (กินยาไม่ครบ)</div>
                <div class="stat-number" style="color: #ef4444;"><?= count($lowAdherencePatients) ?></div>
                <div style="color: #ef4444; font-size: 12px;">Needs Attention</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Today's Reminders (ส่งแจ้งเตือนวันนี้)</div>
                <div class="stat-number"><?= number_format($stats['reminders_today']) ?></div>
                <div style="color: green; font-size: 12px;">Daily Target</div>
            </div>
        </div>
        
        <h3>⚠️ Low Adherence Alerts (ผู้ป่วยที่ต้องติดตาม)
            <button onclick="startTelePharmacy()" class="btn-action" style="float: right; background: #ec4899;">
                <i class="fas fa-video"></i> เปิดห้อง Tele-Pharmacy
            </button>
        </h3>
        <table>
            <thead>
                <tr>
                    <th>HN</th>
                    <th>ชื่อ-นามสกุล</th>
                    <th>ยาที่ใช้อยู่</th>
                    <th>Adherence Score</th>
                    <th>สถานะ</th>
                    <th>การจัดการ</th>
                </tr>
            </thead>
            <tbody>
            <tbody>
                <?php if (empty($lowAdherencePatients)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 20px;">
                            <i class="fas fa-check-circle" style="color: #10b981; font-size: 24px;"></i><br>
                            ไม่มีผู้ป่วยที่มีความเสี่ยงต่ำในขณะนี้ (No Low Adherence Alerts)
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($lowAdherencePatients as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['hn']) ?></td>
                        <td><?= htmlspecialchars($p['full_name']) ?></td>
                        <td><?= htmlspecialchars($p['medications']) ?></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 100px; height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden;">
                                    <?php 
                                        $score = $p['score'];
                                        $color = $score < 50 ? '#ef4444' : ($score < 80 ? '#f59e0b' : '#10b981');
                                    ?>
                                    <div style="width: <?= $score ?>%; height: 100%; background: <?= $color ?>;"></div>
                                </div>
                                <span style="color: <?= $color ?>; font-weight: bold;"><?= number_format($score, 1) ?>%</span>
                            </div>
                        </td>
                        <td>
                            <span class="status-badge" style="background: <?= $score < 50 ? '#fee2e2' : '#fef3c7' ?>; color: <?= $score < 50 ? '#991b1b' : '#92400e' ?>">
                                <?= $score < 50 ? 'High Risk' : 'Monitor' ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn-action" onclick="alert('ส่งข้อความเตือนทาง LINE แล้ว')"><i class="fab fa-line"></i> ทักแชท</button>
                            <button class="btn-action" style="background: #e5e7eb; color: #374151;" onclick="startTelePharmacy('<?= $p['hn'] ?>')"><i class="fas fa-video"></i> Video</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        function startTelePharmacy() {
            // In a real app, this would likely take an HN or create a general lobby
            const hn = prompt("กรุณาระบุ HN ผู้ป่วยที่ต้องการติดต่อ (Optional):", "");
            if (hn === null) return;
            
            const roomName = 'Drugmuk-Consult-' + (hn ? hn : 'General') + '-' + Date.now();
            const url = 'https://meet.jit.si/' + roomName;
            
            window.open(url, '_blank');
        }
    </script>
</body>
</html>
