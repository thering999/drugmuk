<?php require_once __DIR__ . '/../layouts/header_responsive.php'; ?>

<style>
    .page-header {
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

    .page-header h1 {
        color: #667eea;
        font-size: 24px;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .btn {
        padding: 10px 20px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
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
    
    .btn-outline {
        background: white;
        color: #667eea;
        border: 1px solid #667eea;
    }
    
    .btn-outline:hover {
        background: #f0f4ff;
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
        background: #f8fafc;
        color: #64748b;
        padding: 15px;
        text-align: left;
        font-weight: 600;
        border-bottom: 2px solid #e2e8f0;
    }

    td {
        padding: 15px;
        border-bottom: 1px solid #eee;
        vertical-align: middle;
        color: #4b5563;
    }

    tr:hover {
        background: #f8f9fa;
    }

    .badge {
        padding: 6px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
    }

    .badge.pending { background: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; }
    .badge.approved { background: #ecfccb; color: #3f6212; border: 1px solid #d9f99d; }
    .badge.completed { background: #ecfeff; color: #0e7490; border: 1px solid #cffafe; }
    .badge.cancelled { background: #fef2f2; color: #991b1b; border: 1px solid #fee2e2; }

    .no-data {
        text-align: center;
        padding: 60px 20px;
        color: #9ca3af;
    }
    
    .no-data i {
        font-size: 48px;
        margin-bottom: 15px;
        color: #e5e7eb;
    }

    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            align-items: stretch;
            text-align: center;
        }
        
        .header-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .btn {
            justify-content: center;
            width: 100%;
        }
    }
</style>

<div class="page-header">
    <h1><i class="fas fa-file-invoice-dollar"></i> รายการสั่งซื้อเวชภัณฑ์</h1>
    <div class="header-actions">
        <!-- <a href="/dashboard" class="btn btn-outline"><i class="fas fa-home"></i> กลับหน้าหลัก</a> -->
        <a href="/orders/what-to-buy" class="btn btn-outline"><i class="fas fa-magic"></i> แนะนำการสั่งซื้อ (AI)</a>
        <a href="/orders/create" class="btn btn-primary"><i class="fas fa-plus"></i> สร้างใบสั่งซื้อใหม่</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <?php if (!empty($orders)): ?>
        <table>
            <thead>
                <tr>
                    <th>เลขที่ใบสั่งซื้อ</th>
                    <th>ผู้จำหน่าย</th>
                    <th>วันที่สั่งซื้อ</th>
                    <th>วันที่ส่งมอบ</th>
                    <th>จำนวนเงิน</th>
                    <th>สถานะ</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td>
                        <div style="font-weight: 600; color: #667eea;"><?= htmlspecialchars($order['order_no']) ?></div>
                    </td>
                    <td>
                        <div style="font-weight: 500;"><?= htmlspecialchars($order['supplier_name'] ?? 'ไม่ระบุ') ?></div>
                    </td>
                    <td><?= date('d/m/Y', strtotime($order['order_date'])) ?></td>
                    <td><?= $order['delivery_date'] ? date('d/m/Y', strtotime($order['delivery_date'])) : '<span style="color:#999">-</span>' ?></td>
                    <td style="font-family: monospace; font-size: 15px; font-weight: 600;">
                        <?= number_format($order['total_amount'], 2) ?> ฿
                    </td>
                    <td>
                        <?php
                            $statusMap = [
                                'pending' => 'รอดำเนินการ',
                                'approved' => 'อนุมัติแล้ว',
                                'completed' => 'รับของแล้ว',
                                'cancelled' => 'ยกเลิก'
                            ];
                            $statusText = $statusMap[$order['status']] ?? $order['status'];
                        ?>
                        <span class="badge <?= $order['status'] ?>">
                            <?= $statusText ?>
                        </span>
                    </td>
                    <td>
                        <a href="/orders/show/<?= $order['id'] ?>" class="btn btn-outline" style="padding: 5px 10px; font-size: 12px; height: 30px;">
                            <i class="fas fa-eye"></i> รายละเอียด
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="no-data">
            <i class="fas fa-folder-open"></i>
            <p>ยังไม่มีรายการสั่งซื้อในระบบ</p>
            <p style="margin-top: 15px;">
                <a href="/orders/create" class="btn btn-primary">สร้างใบสั่งซื้อแรก</a>
            </p>
        </div>
        <?php endif; ?>
    </div>
</div>

</main>
</body>
</html>
