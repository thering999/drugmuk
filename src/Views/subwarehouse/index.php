<!DOCTYPE html>
<html lang="th">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>คลังย่อย - Drugmuk</title>
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
        .header h1 { color: #667eea; font-size: 28px; margin-bottom: 10px; }
        .header p { color: #666; font-size: 14px; }
        .grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px; margin-bottom: 20px;
        }
        .card {
            background: rgba(255, 255, 255, 0.95); border-radius: 15px;
            padding: 25px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease; cursor: pointer;
        }
        .card:hover { transform: translateY(-5px); box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15); }
        .card-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 15px; padding-bottom: 15px; border-bottom: 2px solid #f0f0f0;
        }
        .card-title { font-size: 20px; font-weight: 600; color: #333; }
        .card-code {
            background: #667eea; color: white; padding: 5px 15px;
            border-radius: 20px; font-size: 14px; font-weight: 600;
        }
        .card-info { margin-bottom: 10px; color: #666; font-size: 14px; }
        .card-info strong { color: #333; }
        .stats {
            display: grid; grid-template-columns: repeat(2, 1fr);
            gap: 10px; margin-top: 15px;
        }
        .stat-item {
            background: #f8f9fa; padding: 10px; border-radius: 8px;
            text-align: center;
        }
        .stat-value { font-size: 24px; font-weight: 700; color: #667eea; }
        .stat-label { font-size: 12px; color: #666; margin-top: 5px; }
        .btn {
            display: inline-block; padding: 10px 20px; background: #667eea;
            color: white; text-decoration: none; border-radius: 8px;
            font-weight: 600; transition: all 0.3s ease; margin-top: 15px;
        }
        .btn:hover { background: #5568d3; transform: scale(1.05); }
        .badge {
            display: inline-block; padding: 4px 12px; border-radius: 12px;
            font-size: 12px; font-weight: 600;
        }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Back Button -->
        <a href="/dashboard" class="back-button">
            <span style="font-size: 1.2rem;">←</span>
            <span>กลับไปหน้า Dashboard</span>
        </a>

        <!-- Header -->
        <div class="header">
            <h1>📦 คลังย่อย</h1>
            <p>จัดการคลังย่อยทั้งหมดในระบบ</p>
        </div>

        <!-- Subwarehouses Grid -->
        <div class="grid">
            <?php foreach ($subwarehouses as $sub): ?>
            <div class="card" onclick="location.href='/subwarehouse/dashboard/<?= $sub['code'] ?>'">
                <div class="card-header">
                    <div class="card-title"><?= htmlspecialchars($sub['name']) ?></div>
                    <div class="card-code"><?= htmlspecialchars($sub['code']) ?></div>
                </div>
                
                <div class="card-info">
                    <strong>📍 สถานที่:</strong> <?= htmlspecialchars($sub['location'] ?? '-') ?>
                </div>
                
                <div class="card-info">
                    <strong>👤 ผู้รับผิดชอบ:</strong> <?= htmlspecialchars($sub['manager_name'] ?? '-') ?>
                </div>
                
                <div class="card-info">
                    <strong>สถานะ:</strong> 
                    <?php if ($sub['status'] === 'active'): ?>
                        <span class="badge badge-success">ใช้งาน</span>
                    <?php else: ?>
                        <span class="badge badge-danger">ไม่ใช้งาน</span>
                    <?php endif; ?>
                </div>
                
                <a href="/subwarehouse/dashboard/<?= $sub['code'] ?>" class="btn">
                    เข้าสู่คลัง →
                </a>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($subwarehouses)): ?>
            <div class="card" style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                <p style="color: #999; font-size: 18px;">ยังไม่มีคลังย่อยในระบบ</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
