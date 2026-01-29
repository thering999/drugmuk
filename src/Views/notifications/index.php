<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= \App\Core\CSRF::metaTag() ?>
    <title>การแจ้งเตือน - Drugmuk</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh; padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .header {
            background: white; padding: 25px 30px; border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-bottom: 25px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .header h1 { color: #333; font-size: 26px; }
        .back-link {
            color: white; text-decoration: none; display: inline-block;
            background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 8px; margin-bottom: 15px;
        }
        .back-link:hover { background: rgba(255,255,255,0.3); }
        
        .stats-row {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px; margin-bottom: 25px;
        }
        .stat-card {
            background: white; padding: 20px; border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center;
        }
        .stat-card .number { font-size: 36px; font-weight: bold; }
        .stat-card .label { color: #666; margin-top: 5px; }
        .stat-card.unread .number { color: #ef4444; }
        .stat-card.total .number { color: #667eea; }
        
        .card {
            background: white; padding: 25px; border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 20px;
        }
        .card h2 { color: #333; margin-bottom: 20px; font-size: 20px; }
        
        .notification-item {
            padding: 15px; border-radius: 10px; margin-bottom: 10px;
            display: flex; gap: 15px; align-items: flex-start;
            transition: all 0.3s ease; cursor: pointer;
        }
        .notification-item:hover { transform: translateX(5px); }
        .notification-item.unread { background: #fef2f2; border-left: 4px solid #ef4444; }
        .notification-item.read { background: #f9fafb; border-left: 4px solid #e5e7eb; }
        
        .notification-icon { font-size: 24px; }
        .notification-content { flex: 1; }
        .notification-title { font-weight: 600; color: #333; }
        .notification-message { color: #666; margin-top: 5px; font-size: 14px; }
        .notification-time { color: #999; font-size: 12px; margin-top: 5px; }
        
        .severity-info { border-left-color: #3b82f6 !important; }
        .severity-warning { border-left-color: #f59e0b !important; }
        .severity-danger { border-left-color: #ef4444 !important; }
        .severity-success { border-left-color: #10b981 !important; }
        
        .btn {
            padding: 10px 20px; border: none; border-radius: 8px;
            font-weight: 600; cursor: pointer; transition: all 0.3s ease;
            text-decoration: none; display: inline-block;
        }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5a6fd6; }
        .btn-secondary { background: #e5e7eb; color: #374151; }
        .btn-secondary:hover { background: #d1d5db; }
        
        .empty-state {
            text-align: center; padding: 60px 20px; color: #999;
        }
        .empty-state .icon { font-size: 64px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <a href="/dashboard" class="back-link">← กลับหน้าหลัก</a>
        
        <div class="header">
            <h1>🔔 การแจ้งเตือน</h1>
            <div>
                <button class="btn btn-secondary" onclick="markAllRead()">✓ อ่านทั้งหมด</button>
                <a href="/notifications/settings" class="btn btn-primary">⚙️ ตั้งค่า</a>
            </div>
        </div>

        <div class="stats-row">
            <div class="stat-card unread">
                <div class="number"><?php echo $unreadCount; ?></div>
                <div class="label">ยังไม่ได้อ่าน</div>
            </div>
            <div class="stat-card total">
                <div class="number"><?php echo count($notifications); ?></div>
                <div class="label">ทั้งหมด</div>
            </div>
        </div>

        <div class="card">
            <h2>รายการแจ้งเตือน</h2>
            
            <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <div class="icon">🔕</div>
                <h3>ไม่มีการแจ้งเตือน</h3>
                <p>คุณไม่มีการแจ้งเตือนใดๆ ในขณะนี้</p>
            </div>
            <?php else: ?>
                <?php foreach ($notifications as $notif): ?>
                <div class="notification-item <?php echo $notif['is_read'] ? 'read' : 'unread'; ?> severity-<?php echo $notif['severity']; ?>" 
                     onclick="handleClick(<?php echo $notif['id']; ?>, '<?php echo $notif['link'] ?? ''; ?>')">
                    <div class="notification-icon">
                        <?php
                        $icons = [
                            'low_stock' => '📦',
                            'expiring' => '⏰',
                            'data_quality' => '🧹',
                            'order' => '🛒',
                            'system' => '⚙️'
                        ];
                        echo $icons[$notif['type']] ?? '🔔';
                        ?>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title"><?php echo htmlspecialchars($notif['title']); ?></div>
                        <div class="notification-message"><?php echo htmlspecialchars($notif['message']); ?></div>
                        <div class="notification-time"><?php echo date('d/m/Y H:i', strtotime($notif['created_at'])); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function getCSRFToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        }

        async function handleClick(id, link) {
            // Mark as read
            await fetch('/api/notifications/mark-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-Token': getCSRFToken()
                },
                body: 'id=' + id
            });
            
            if (link) {
                window.location.href = link;
            } else {
                location.reload();
            }
        }

        async function markAllRead() {
            await fetch('/api/notifications/mark-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-Token': getCSRFToken()
                }
            });
            location.reload();
        }
    </script>
</body>
</html>
