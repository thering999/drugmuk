<?php require_once __DIR__ . '/../layouts/header_responsive.php'; ?>

<style>
    .forecast-header {
        background: white;
        padding: 20px 30px;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }

    .forecast-header h1 {
        color: #667eea;
        font-size: 24px;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        padding: 30px;
        margin-bottom: 30px;
    }

    .table-responsive {
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        min-width: 900px;
    }

    th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px;
        text-align: left;
        font-weight: 600;
        /* border-bottom: 2px solid #e2e8f0; */
    }
    
    th:first-child { border-top-left-radius: 10px; }
    th:last-child { border-top-right-radius: 10px; }

    td {
        padding: 15px;
        border-bottom: 1px solid #eee;
        vertical-align: middle;
        color: #4b5563;
    }

    tr:hover {
        background: #f8f9fa;
    }

    .status-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 5px;
    }
    
    .status-critical { background: #ef4444; box-shadow: 0 0 5px #ef4444; }
    .status-warning { background: #f59e0b; box-shadow: 0 0 5px #f59e0b; }
    .status-good { background: #10b981; }

    .progress-bar {
        width: 100px;
        height: 6px;
        background: #e5e7eb;
        border-radius: 3px;
        overflow: hidden;
        display: inline-block;
        vertical-align: middle;
        margin-top: 5px;
    }
    
    .progress-fill {
        height: 100%;
        border-radius: 3px;
    }

    .btn-action {
        padding: 6px 12px;
        background: #f3f4f6;
        color: #4b5563;
        border-radius: 6px;
        text-decoration: none;
        font-size: 13px;
        transition: all 0.2s;
        border: 1px solid #e5e7eb;
    }
    
    .btn-action:hover {
        background: #e5e7eb;
        color: #1f2937;
    }

    .stock-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-weight: 600;
        font-size: 12px;
    }
    
    .bg-critical { background: #fee2e2; color: #991b1b; }
    .bg-warning { background: #fef3c7; color: #92400e; }
    .bg-good { background: #d1fae5; color: #065f46; }

    @media (max-width: 768px) {
        .forecast-header {
            flex-direction: column;
            text-align: center;
        }
    }
</style>

<div class="forecast-header">
    <h1><i class="fas fa-brain"></i> พยากรณ์ความต้องการยา (AI Forecast)</h1>
    <div style="font-size: 14px; color: #666;">
        วิเคราะห์จากข้อมูลการใช้ย้อนหลัง 90 วัน
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th width="25%">ชื่อยา / Drug Name</th>
                    <th>คงเหลือ</th>
                    <th>ใช้เฉลี่ย/วัน</th>
                    <th>ใช้หมดใน (วัน)</th>
                    <!-- <th>Min Stock</th> -->
                    <th>คำแนะนำ (Recommendation)</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($forecasts)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: #9ca3af;">
                            <i class="fas fa-chart-line" style="font-size: 40px; margin-bottom: 10px;"></i><br>
                            ยังไม่มีข้อมูลเพียงพอสำหรับการวิเคราะห์
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($forecasts as $item): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 600; color: #1f2937;"><?= htmlspecialchars($item['drug_name']) ?></div>
                            <small style="color: #9ca3af;">Unit: <?= htmlspecialchars($item['unit']) ?></small>
                        </td>
                        <td>
                            <span style="font-weight: bold; font-size: 15px;"><?= number_format($item['current_stock']) ?></span>
                        </td>
                        <td>
                            <?= number_format($item['avg_daily_usage'], 2) ?>
                        </td>
                        <td>
                            <?php 
                                $days = $item['days_remaining'];
                                $displayDays = ($days > 365) ? '> 1 ปี' : number_format($days) . ' วัน';
                                $colorClass = ($days < 30) ? 'status-critical' : (($days < 60) ? 'status-warning' : 'status-good');
                                $textColor = ($days < 30) ? '#ef4444' : (($days < 60) ? '#d97706' : '#059669');
                            ?>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span class="status-dot <?= $colorClass ?>"></span>
                                <span style="font-weight: 600; color: <?= $textColor ?>;"><?= $displayDays ?></span>
                            </div>
                            
                            <!-- Simple visual bar for days remaining (capped at 90 days for visual) -->
                            <?php 
                                $percent = min(100, ($days / 90) * 100);
                                $barColor = ($days < 30) ? '#ef4444' : (($days < 60) ? '#f59e0b' : '#10b981');
                            ?>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?= $percent ?>%; background: <?= $barColor ?>;"></div>
                            </div>
                        </td>
                        <!-- <td><?= number_format($item['min_stock']) ?></td> -->
                        <td>
                            <?php if ($item['status'] == 'critical'): ?>
                                <span class="stock-badge bg-critical">
                                    <i class="fas fa-exclamation-circle"></i> <?= $item['recommendation'] ?>
                                </span>
                            <?php elseif ($item['status'] == 'warning'): ?>
                                <span class="stock-badge bg-warning">
                                    <i class="fas fa-exclamation-triangle"></i> <?= $item['recommendation'] ?>
                                </span>
                            <?php else: ?>
                                <span class="stock-badge bg-good">
                                    <i class="fas fa-check-circle"></i> <?= $item['recommendation'] ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($item['status'] != 'good'): ?>
                            <a href="/orders/create?drug_id=<?= $item['id'] ?>" class="btn-action">
                                <i class="fas fa-shopping-cart"></i> สั่งซื้อ
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</main>
</body>
</html>
