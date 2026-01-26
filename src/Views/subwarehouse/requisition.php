<!DOCTYPE html>
<html lang="th">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ขอเบิกยา - <?= htmlspecialchars($subwarehouse['name']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh; padding: 20px;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        .back-button {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.75rem 1.5rem; background: rgba(255, 255, 255, 0.2);
            color: white; text-decoration: none; border-radius: 10px;
            margin-bottom: 1rem; transition: all 0.3s ease; font-weight: 600;
        }
        .back-button:hover { background: rgba(255, 255, 255, 0.3); transform: translateX(-5px); }
        .header {
            background: rgba(255, 255, 255, 0.95); padding: 20px 30px;
            border-radius: 15px; margin-bottom: 20px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        .header h1 { color: #667eea; font-size: 28px; margin-bottom: 5px; }
        .section {
            background: rgba(255, 255, 255, 0.95); padding: 25px;
            border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .section-title { font-size: 20px; font-weight: 600; color: #333; margin-bottom: 15px; }
        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 24px; background: #667eea; color: white;
            border: none; border-radius: 8px; font-size: 16px;
            font-weight: 600; cursor: pointer; transition: all 0.3s ease;
        }
        .btn:hover { background: #5568d3; transform: scale(1.05); }
        .btn-success { background: #10b981; }
        .btn-success:hover { background: #059669; }
        .table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #f0f0f0; }
        .table th { background: #f8f9fa; font-weight: 600; color: #333; }
        .badge {
            display: inline-block; padding: 4px 12px; border-radius: 12px;
            font-size: 12px; font-weight: 600;
        }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .alert {
            padding: 15px 20px; border-radius: 8px; margin-bottom: 20px;
            display: none;
        }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #10b981; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #ef4444; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Back Button -->
        <a href="/subwarehouse/dashboard/<?= $subwarehouse['code'] ?>" class="back-button">
            <span style="font-size: 1.2rem;">←</span>
            <span>กลับคลังย่อย</span>
        </a>

        <!-- Header -->
        <div class="header">
            <h1>📋 ขอเบิกยาจากคลังใหญ่</h1>
            <p>คลัง: <?= htmlspecialchars($subwarehouse['name']) ?></p>
        </div>

        <!-- Alert -->
        <div id="alert" class="alert"></div>

        <!-- Auto Requisition -->
        <div class="section">
            <div class="section-title">⚡ สร้างใบขอเบิกอัตโนมัติ</div>
            <p style="color: #666; margin-bottom: 15px;">
                ระบบจะคำนวณปริมาณที่ต้องเบิกตามสูตรที่กำหนดไว้ สำหรับยาที่ใกล้หมดหรือต่ำกว่า Min
            </p>
            <button class="btn btn-success" onclick="autoRequisition()">
                <span>🚀</span>
                <span>สร้างใบขอเบิกอัตโนมัติ</span>
            </button>
        </div>

        <!-- Low Stock Drugs -->
        <?php if (!empty($lowStockDrugs)): ?>
        <div class="section">
            <div class="section-title">⚠️ ยาที่ควรเบิก (<?= count($lowStockDrugs) ?> รายการ)</div>
            <table class="table">
                <thead>
                    <tr>
                        <th>รหัสยา</th>
                        <th>ชื่อยา</th>
                        <th>คงเหลือ</th>
                        <th>Min</th>
                        <th>Max</th>
                        <th>ควรเบิก</th>
                        <th>สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lowStockDrugs as $drug): ?>
                    <tr>
                        <td><?= htmlspecialchars($drug['drug_code']) ?></td>
                        <td><?= htmlspecialchars($drug['drug_name']) ?></td>
                        <td><?= number_format($drug['current_stock'], 2) ?> <?= htmlspecialchars($drug['unit']) ?></td>
                        <td><?= number_format($drug['min_quantity'], 2) ?></td>
                        <td><?= number_format($drug['max_quantity'], 2) ?></td>
                        <td><strong><?= number_format($drug['max_quantity'] - $drug['current_stock'], 2) ?></strong></td>
                        <td>
                            <?php if ($drug['stock_status'] === 'critical'): ?>
                                <span class="badge badge-danger">หมด/ใกล้หมด</span>
                            <?php else: ?>
                                <span class="badge badge-warning">น้อยกว่า Min</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Pending Requisitions -->
        <?php if (!empty($pendingRequisitions)): ?>
        <div class="section">
            <div class="section-title">📝 ใบขอเบิกที่รออนุมัติ</div>
            <table class="table">
                <thead>
                    <tr>
                        <th>เลขที่</th>
                        <th>วันที่</th>
                        <th>จำนวนรายการ</th>
                        <th>ยอดรวม</th>
                        <th>สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingRequisitions as $req): ?>
                    <tr>
                        <td><?= htmlspecialchars($req['requisition_no']) ?></td>
                        <td><?= date('d/m/Y', strtotime($req['request_date'])) ?></td>
                        <td><?= $req['item_count'] ?? 0 ?> รายการ</td>
                        <td><?= number_format($req['total_requested'] ?? 0, 2) ?></td>
                        <td><span class="badge badge-warning">รออนุมัติ</span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <script>
        async function autoRequisition() {
            if (!confirm('ต้องการสร้างใบขอเบิกอัตโนมัติหรือไม่?')) return;
            
            const btn = event.target.closest('button');
            btn.disabled = true;
            btn.innerHTML = '<span>⏳</span><span>กำลังสร้าง...</span>';
            
            try {
                const res = await fetch('/api/subwarehouse/<?= $subwarehouse['code'] ?>/requisitions/auto', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });
                
                const data = await res.json();
                
                if (data.success) {
                    showAlert('success', '✅ สร้างใบขอเบิกสำเร็จ! เลขที่: ' + (data.requisition_id || ''));
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showAlert('danger', '❌ ' + data.message);
                }
            } catch (e) {
                showAlert('danger', '❌ เกิดข้อผิดพลาด: ' + e.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<span>🚀</span><span>สร้างใบขอเบิกอัตโนมัติ</span>';
            }
        }
        
        function showAlert(type, message) {
            const alert = document.getElementById('alert');
            alert.className = 'alert alert-' + type;
            alert.textContent = message;
            alert.style.display = 'block';
            setTimeout(() => alert.style.display = 'none', 5000);
        }
    </script>
</body>
</html>
