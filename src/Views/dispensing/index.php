<?php require_once __DIR__ . '/../layouts/header_responsive.php'; ?>

    <style>
        /* Page-specific styles */
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
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #bd2130;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 13px;
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
            min-width: 800px; /* Ensure table doesn't squash too much */
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
        }

        tr:hover {
            background: #f8f9fa;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .pagination a {
            min-width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
        }
        
        .pagination a:hover {
            border-color: #667eea;
            background: #f0f4ff;
        }

        .pagination a.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .badge {
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }
            
            .header-buttons {
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

    <?php if (isset($_SESSION['success'])): ?>
    <div class="message success">
        ✅ <?= htmlspecialchars($_SESSION['success']) ?>
    </div>
    <?php unset($_SESSION['success']); endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
    <div class="message error">
        ❌ <?= htmlspecialchars($_SESSION['error']) ?>
    </div>
    <?php unset($_SESSION['error']); endif; ?>

    <div class="page-header">
        <h1>💊 รายการจ่ายยาให้ผู้ป่วย</h1>
        <div class="header-buttons" style="display: flex; gap: 10px; flex-wrap: wrap; justify-content: flex-end;">
            <a href="/dispensing/statistics" class="btn btn-secondary"><i class="fas fa-chart-bar"></i> สถิติ</a>
            <a href="/dispensing/create" class="btn btn-primary"><i class="fas fa-plus"></i> จ่ายยาใหม่</a>
        </div>
    </div>

    <div class="card">
        <h2 style="margin-bottom: 20px; font-size: 18px; color: #4b5563;">รายการทั้งหมด (<?= number_format($total) ?> รายการ)</h2>
        
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>วันที่จ่าย</th>
                        <th>HN</th>
                        <th>VN</th>
                        <th>ชื่อผู้ป่วย</th>
                        <th>รายการ</th>
                        <th>ผู้จ่าย</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dispensings)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #999; padding: 40px;">
                            <i class="fas fa-inbox" style="font-size: 40px; margin-bottom: 10px; display: block; color: #e5e7eb;"></i>
                            ไม่มีข้อมูลการจ่ายยา
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($dispensings as $disp): ?>
                        <tr>
                            <td style="white-space: nowrap;"><?= date('d/m/Y H:i', strtotime($disp['dispense_date'])) ?></td>
                            <td><strong><?= htmlspecialchars($disp['hn']) ?></strong></td>
                            <td><?= htmlspecialchars($disp['vn'] ?? '-') ?></td>
                            <td style="font-weight: 500;"><?= htmlspecialchars($disp['patient_name']) ?></td>
                            <td><span class="badge badge-success"><?= $disp['item_count'] ?> รายการ</span></td>
                            <td><?= htmlspecialchars($disp['dispensed_by_name'] ?? 'N/A') ?></td>
                            <td>
                                <div class="actions">
                                    <a href="/dispensing/show/<?= $disp['id'] ?>" class="btn btn-primary btn-sm" title="ดูรายละเอียด"><i class="fas fa-eye"></i></a>
                                    <a href="/dispensing/print/<?= $disp['id'] ?>" target="_blank" class="btn btn-secondary btn-sm" title="พิมพ์ฉลาก"><i class="fas fa-print"></i></a>
                                    <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                                    <form method="POST" action="/dispensing/delete/<?= $disp['id'] ?>" style="display: inline;" onsubmit="return confirm('ต้องการลบข้อมูลนี้?');">
                                        <?= \App\Core\CSRF::field() ?>
                                        <button type="submit" class="btn btn-danger btn-sm" title="ลบข้อมูล"><i class="fas fa-trash"></i></button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>" class="<?= $i == $currentPage ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>

</main>
</body>
</html>
