<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>My Drugmuk - <?= htmlspecialchars($patient['first_name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --accent: #10b981;
            --bg: #f3f4f6;
            --card-bg: #ffffff;
            --text: #1f2937;
        }
        
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { margin: 0; font-family: 'Kanit', sans-serif; background: var(--bg); color: var(--text); padding-bottom: 80px; }
        
        .header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 30px 20px 50px;
            border-bottom-left-radius: 25px;
            border-bottom-right-radius: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .profile-head { display: flex; align-items: center; gap: 15px; }
        .avatar { width: 60px; height: 60px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 30px; }
        .info h1 { margin: 0; font-size: 22px; font-weight: 500; }
        .info p { margin: 5px 0 0; opacity: 0.9; font-size: 14px; }
        
        .card-container { padding: 0 20px; margin-top: -30px; }
        .card { background: var(--card-bg); border-radius: 15px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card h2 { margin: 0 0 15px; font-size: 18px; color: var(--secondary); display: flex; align-items: center; gap: 10px; }
        
        .action-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; }
        .action-btn { background: white; border: none; padding: 15px; border-radius: 12px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); display: flex; flex-direction: column; align-items: center; gap: 10px; color: var(--text); font-family: inherit; font-size: 14px; text-decoration: none; transition: transform 0.2s; }
        .action-btn:active { transform: scale(0.98); }
        .action-btn i { font-size: 24px; color: var(--primary); }
        .action-btn.primary { background: linear-gradient(135deg, #10b981, #059669); color: white; grid-column: span 2; flex-direction: row; justify-content: center; font-size: 18px; }
        .action-btn.primary i { color: white; }
        
        .med-item { display: flex; align-items: flex-start; gap: 15px; padding: 12px 0; border-bottom: 1px solid #f0f0f0; }
        .med-item:last-child { border-bottom: none; }
        .med-icon { width: 40px; height: 40px; background: #e0e7ff; color: var(--primary); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .med-info h3 { margin: 0; font-size: 16px; font-weight: 500; }
        .med-info p { margin: 4px 0 0; color: #6b7280; font-size: 13px; }
        
        .ai-fab { position: fixed; bottom: 20px; right: 20px; width: 60px; height: 60px; background: linear-gradient(135deg, #ec4899, #8b5cf6); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 25px; box-shadow: 0 5px 15px rgba(236, 72, 153, 0.4); z-index: 100; cursor: pointer; border: none; animation: bounce 2s infinite; }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
            40% {transform: translateY(-6px);}
            60% {transform: translateY(-3px);}
        }

        /* AI Chat Overlay */
        .chat-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: white; z-index: 1000;
            display: none; flex-direction: column;
        }
        .chat-header {
            padding: 15px; background: white; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;
        }
        .chat-close { font-size: 24px; background: none; border: none; color: #666; cursor: pointer; }
        
        .alert-safe { background: #d1fae5; color: #065f46; padding: 10px; border-radius: 8px; font-size: 13px; margin-top: 5px; display: flex; align-items: center; gap: 8px; }

    </style>
</head>
<body>

    <div class="header">
        <div class="profile-head">
            <div class="avatar"><i class="fas fa-user"></i></div>
            <div class="info">
                <h1>คุณ<?= htmlspecialchars($patient['first_name']) ?></h1>
                <p>HN: <?= $patient['hn'] ?></p>
                <p><i class="fas fa-hospital-alt"></i> โรงพยาบาลส่งเสริมสุขภาพตำบลบ้านดงเย็น</p>
            </div>
        </div>
    </div>

    <div class="card-container">
        <!-- Quick Actions -->
        <div class="action-grid">
            <a href="/tele-pharmacy/room/<?= $patient['hn'] ?>" class="action-btn primary">
                <i class="fas fa-video"></i> ปรึกษาเภสัชกรออนไลน์
            </a>
            <button class="action-btn">
                <i class="fas fa-calendar-alt"></i>
                <span>นัดหมาย</span>
            </button>
            <button class="action-btn">
                <i class="fas fa-history"></i>
                <span>ประวัติ</span>
            </button>
        </div>

        <!-- Current Medications -->
        <div class="card">
            <h2><i class="fas fa-pills"></i> ยาที่ใช้ปัจจุบัน</h2>
            <?php if (!empty($meds)): ?>
                <?php foreach ($meds as $med): ?>
                <div class="med-item">
                    <div class="med-icon"><i class="fas fa-prescription-bottle"></i></div>
                    <div class="med-info">
                        <h3><?= htmlspecialchars($med['drug_name']) ?></h3>
                        <p><?= htmlspecialchars($med['usage_line1'] ?? 'ทานตามแพทย์สั่ง') ?></p>
                        <?php if(isset($med['compliance_score']) && $med['compliance_score'] > 80): ?>
                            <div class="alert-safe"><i class="fas fa-check-circle"></i> ทานยาดีเยี่ยม</div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: #999; text-align: center; padding: 20px;">ไม่มีรายการยาในปัจจุบัน</p>
            <?php endif; ?>
        </div>

        <!-- Health Summary -->
        <div class="card">
            <h2><i class="fas fa-heartbeat"></i> สุขภาพของคุณ</h2>
            <div style="display: flex; justify-content: space-between; text-align: center;">
                <div>
                    <div style="font-size: 24px; color: var(--primary); font-weight: bold;">120/80</div>
                    <div style="font-size: 12px; color: #999;">ความดันโลหิต</div>
                </div>
                <div>
                    <div style="font-size: 24px; color: var(--accent); font-weight: bold;">82</div>
                    <div style="font-size: 12px; color: #999;">น้ำหนัก (กก.)</div>
                </div>
                <div>
                    <div style="font-size: 24px; color: #f59e0b; font-weight: bold;">98</div>
                    <div style="font-size: 12px; color: #999;">น้ำตาล</div>
                </div>
            </div>
        </div>
    </div>

    <!-- AI Chatbot Button -->
    <button class="ai-fab" onclick="document.querySelector('.chat-overlay').style.display='flex'">
        <i class="fas fa-robot"></i>
    </button>

    <!-- Chat Overlay -->
    <div class="chat-overlay">
        <div class="chat-header">
            <h3 style="margin:0;">AI Pharmacist Assistant</h3>
            <button class="chat-close" onclick="document.querySelector('.chat-overlay').style.display='none'">&times;</button>
        </div>
        <div style="flex:1;">
            <?php 
            $aiChatUrl = \App\Core\Config::get('AI_CHAT_URL');
            if ($aiChatUrl): ?>
                <iframe src="<?= htmlspecialchars($aiChatUrl) ?>" style="width: 100%; height: 100%; border: none;"></iframe>
            <?php else: ?>
                <div style="padding: 20px; text-align: center;">Coming Soon</div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
