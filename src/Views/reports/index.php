<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานและฟอร์ม - Drugmuk</title>
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
        .header h1 { color: #667eea; font-size: 28px; margin-bottom: 15px; }
        .btn-group { display: flex; gap: 10px; }
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
        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        .card h2 {
            color: #667eea;
            font-size: 22px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        .report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .report-item {
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #667eea;
            cursor: pointer;
            transition: all 0.3s;
        }
        .report-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .report-item h3 {
            color: #333;
            font-size: 18px;
            margin-bottom: 10px;
        }
        .report-item p {
            color: #666;
            font-size: 14px;
        }
        .report-item .actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }
        .btn-small {
            padding: 5px 12px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 รายงานและฟอร์ม</h1>
            <div class="btn-group">
                <a href="/dashboard" class="btn btn-secondary">← กลับหน้าหลัก</a>
                <a href="/reports/builder" class="btn btn-primary">➕ สร้างรายงานใหม่</a>
            </div>
        </div>

        <div class="card">
            <h2>รายงานสำเร็จรูป</h2>
            <div class="report-grid">
                <?php foreach ($predefined as $report): ?>
                <div class="report-item">
                    <h3><?= htmlspecialchars($report['name']) ?></h3>
                    <p><?= htmlspecialchars($report['description']) ?></p>
                    <div class="actions">
                        <a href="/reports/predefined/<?= $report['id'] ?>" class="btn btn-primary btn-small">ดูรายงาน</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (!empty($reports)): ?>
        <div class="card">
            <h2>รายงานที่สร้างเอง</h2>
            <div class="report-grid">
                <?php foreach ($reports as $report): ?>
                <div class="report-item">
                    <h3><?= htmlspecialchars($report['name']) ?></h3>
                    <p><?= htmlspecialchars($report['description']) ?></p>
                    <div class="actions">
                        <a href="/reports/generate/<?= $report['id'] ?>" class="btn btn-primary btn-small">ดูรายงาน</a>
                        <form method="POST" action="/reports/delete/<?= $report['id'] ?>" style="display: inline;">
                            <button type="submit" class="btn btn-secondary btn-small" onclick="return confirm('ต้องการลบรายงานนี้?')">ลบ</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
