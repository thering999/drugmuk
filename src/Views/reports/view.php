<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($report['name']) ?> - Drugmuk</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        .header {
            background: white;
            padding: 25px 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .header h1 { color: #667eea; font-size: 28px; margin-bottom: 10px; }
        .header p { color: #666; font-size: 16px; }
        .btn-group { display: flex; gap: 10px; margin-top: 15px; }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 25px;
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
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
            font-size: 18px;
        }
        .export-form {
            display: inline-block;
            margin-left: 10px;
        }
        .export-form select {
            padding: 10px 15px;
            border: 2px solid #667eea;
            border-radius: 8px;
            font-size: 16px;
            font-family: 'Sarabun', sans-serif;
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 <?= htmlspecialchars($report['name']) ?></h1>
            <?php if (!empty($report['description'])): ?>
                <p><?= htmlspecialchars($report['description']) ?></p>
            <?php endif; ?>
            
            <div class="btn-group">
                <a href="/reports" class="btn btn-secondary">← กลับหน้ารายงาน</a>
                
                <form method="POST" action="/reports/export" class="export-form">
                    <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                    <select name="format">
                        <option value="csv">CSV</option>
                        <option value="excel">Excel</option>
                        <option value="json">JSON</option>
                    </select>
                    <button type="submit" class="btn btn-success">📥 Export</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="table-container">
                <?php if (!empty($data)): ?>
                    <table>
                        <thead>
                            <tr>
                                <?php foreach (array_keys($data[0]) as $column): ?>
                                    <th><?= htmlspecialchars($column) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $row): ?>
                                <tr>
                                    <?php foreach ($row as $value): ?>
                                        <td><?= htmlspecialchars($value ?? '-') ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div style="margin-top: 20px; color: #666;">
                        <strong>จำนวนรายการทั้งหมด:</strong> <?= count($data) ?> รายการ
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        ไม่พบข้อมูล
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
