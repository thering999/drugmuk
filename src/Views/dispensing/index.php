<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการจ่ายยา - Drugmuk</title>
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

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
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

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 14px;
        }

        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 30px;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
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

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }

        .pagination a {
            padding: 8px 15px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 5px;
            border: 1px solid #667eea;
        }

        .pagination a.active {
            background: #667eea;
            color: white;
        }

        .badge {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .actions {
            display: flex;
            gap: 5px;
        }

        .message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
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

        <div class="header">
            <h1>💊 รายการจ่ายยาให้ผู้ป่วย</h1>
            <div style="display: flex; gap: 10px;">
                <a href="/dispensing/statistics" class="btn btn-secondary">📊 สถิติ</a>
                <a href="/dispensing/create" class="btn btn-primary">➕ จ่ายยาใหม่</a>
                <a href="/dashboard" class="btn btn-secondary">🏠 กลับหน้าหลัก</a>
            </div>
        </div>

        <div class="card">
            <h2 style="margin-bottom: 20px;">รายการทั้งหมด (<?= number_format($total) ?> รายการ)</h2>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>วันที่จ่าย</th>
                            <th>HN</th>
                            <th>VN</th>
                            <th>ชื่อผู้ป่วย</th>
                            <th>จำนวนรายการ</th>
                            <th>ผู้จ่าย</th>
                            <th>การดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dispensings)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: #999;">
                                ไม่มีข้อมูลการจ่ายยา
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($dispensings as $disp): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($disp['dispense_date'])) ?></td>
                                <td><strong><?= htmlspecialchars($disp['hn']) ?></strong></td>
                                <td><?= htmlspecialchars($disp['vn'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($disp['patient_name']) ?></td>
                                <td><span class="badge badge-success"><?= $disp['item_count'] ?> รายการ</span></td>
                                <td><?= htmlspecialchars($disp['dispensed_by_name'] ?? 'N/A') ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="/dispensing/show/<?= $disp['id'] ?>" class="btn btn-primary btn-sm">👁️ ดู</a>
                                        <a href="/dispensing/print/<?= $disp['id'] ?>" target="_blank" class="btn btn-secondary btn-sm">🖨️ พิมพ์</a>
                                        <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                                        <form method="POST" action="/dispensing/delete/<?= $disp['id'] ?>" style="display: inline;" onsubmit="return confirm('ต้องการลบข้อมูลนี้?');">
                                            <button type="submit" class="btn btn-danger btn-sm">🗑️ ลบ</button>
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
    </div>
</body>
</html>
