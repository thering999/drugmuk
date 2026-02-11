<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Health Portal - Drugmuk</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #818cf8;
            --secondary: #ec4899;
            --success: #10b981;
            --warning: #f59e0b;
            --dark: #1e1b4b;
            --light: #eef2ff;
            --text-main: #1f2937;
            --text-muted: #6b7280;
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.5);
            --card-shadow: 0 12px 40px -8px rgba(0, 0, 0, 0.1);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Prompt', sans-serif;
            background: #f1f5f9;
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            justify-content: center;
        }

        .mobile-container {
            width: 100%;
            max-width: 480px;
            background: #f8fafc;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
            box-shadow: 0 0 100px rgba(0,0,0,0.1);
        }

        header {
            background: linear-gradient(135deg, #4f46e5 0%, #1e1b4b 100%);
            color: white;
            padding: 40px 24px 60px;
            border-bottom-left-radius: 40px;
            border-bottom-right-radius: 40px;
            position: relative;
            z-index: 10;
        }

        .user-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .user-info h2 { font-size: 26px; font-weight: 600; }
        .user-info p { font-size: 14px; opacity: 0.8; }

        .avatar {
            width: 55px; height: 55px;
            background: rgba(255,255,255,0.2);
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; border: 1.5px solid rgba(255,255,255,0.4);
        }

        .adherence-widget {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(12px);
            border-radius: 28px;
            padding: 22px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            display: flex; align-items: center; gap: 20px;
        }

        .progress-circle {
            position: relative;
            width: 65px; height: 65px;
        }
        .progress-circle svg { width: 65px; height: 65px; transform: rotate(-90deg); }
        .progress-circle circle { fill: none; stroke-width: 6; stroke-linecap: round; }

        .stat-text h3 { font-size: 24px; font-weight: 700; color: #4ade80; }
        .stat-text p { font-size: 13px; opacity: 0.9; }

        .content {
            padding: 0 20px 100px;
            margin-top: -30px;
            position: relative;
            z-index: 20;
        }

        .card {
            background: white;
            border-radius: 28px;
            padding: 24px;
            box-shadow: var(--card-shadow);
            margin-bottom: 20px;
            border: 1px solid #f1f5f9;
        }

        .section-title {
            font-size: 18px; font-weight: 700; color: var(--dark);
            margin: 24px 0 16px; padding-left: 5px;
            display: flex; justify-content: space-between; align-items: center;
        }

        /* AI Insight Feature */
        .ai-insight-card {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
            color: white;
            overflow: hidden;
            position: relative;
        }
        .ai-insight-card::after {
            content: '\f0d0'; font-family: 'Font Awesome 6 Free'; font-weight: 900;
            position: absolute; right: -10px; bottom: -10px;
            font-size: 80px; opacity: 0.1; color: white;
        }
        .ai-label { 
            background: rgba(79, 70, 229, 0.4); border: 1px solid rgba(255,255,255,0.2);
            padding: 4px 12px; border-radius: 50px; font-size: 11px; font-weight: 600;
            display: inline-flex; align-items: center; gap: 6px; margin-bottom: 12px;
        }
        .ai-text { font-size: 14px; line-height: 1.6; color: #e2e8f0; }

        /* Med List Hooks */
        .med-item {
            display: flex; gap: 16px; align-items: center;
            padding: 16px; border-radius: 20px;
            background: #f8fafc; margin-bottom: 12px;
            transition: all 0.3s;
        }
        .med-item:active { background: #f1f5f9; transform: scale(0.98); }
        .med-icon {
            width: 50px; height: 50px; background: white;
            border-radius: 16px; display: flex; align-items: center; justify-content: center;
            font-size: 20px; color: var(--primary); box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        .med-info { flex: 1; }
        .med-info h4 { font-size: 15px; font-weight: 600; color: var(--dark); }
        .med-info p { font-size: 12px; color: var(--text-muted); margin-top: 2px; }

        .time-badges { display: flex; gap: 6px; margin-top: 8px; }
        .time-badge {
            width: 24px; height: 24px; border-radius: 6px;
            display: flex; align-items: center; justify-content: center;
            font-size: 11px; background: #e2e8f0; color: #94a3b8;
        }
        .time-badge.active { background: var(--primary); color: white; }
        .time-badge.active.morning { background: #f59e0b; }
        .time-badge.active.noon { background: #fbbf24; }
        .time-badge.active.evening { background: #f97316; }
        .time-badge.active.night { background: #6366f1; }

        .btn-check {
            width: 36px; height: 36px; border-radius: 12px; border: 2px solid #e2e8f0;
            background: white; color: transparent; display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: all 0.3s;
        }
        .btn-check.taken { background: var(--success); border-color: var(--success); color: white; }

        /* Actions Grid */
        /* Actions Grid */
        .menu-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        .menu-item {
            background: white; border-radius: 20px; padding: 15px 10px;
            display: flex; flex-direction: column; align-items: center; gap: 10px;
            text-decoration: none; color: var(--text-muted); font-size: 11px; font-weight: 500;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }
        .menu-item i { font-size: 20px; color: var(--primary); }

        /* AI Personalized Insights */
        .ai-card {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            border-radius: 25px;
            padding: 20px;
            color: white;
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.3);
        }
        .ai-card::after {
            content: "\f0eb"; font-family: "Font Awesome 5 Free"; font-weight: 900;
            position: absolute; right: -20px; bottom: -20px; font-size: 100px;
            opacity: 0.1; transform: rotate(-15deg);
        }
        .ai-card h3 { font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
        .ai-card p { font-size: 13px; line-height: 1.6; opacity: 0.95; }

        .diet-badge {
            background: rgba(255,255,255,0.15); border-radius: 12px; padding: 10px; margin-top: 10px;
            border-left: 3px solid #fbbf24;
        }
        .diet-badge strong { display: block; font-size: 12px; color: #fbbf24; margin-bottom: 3px; }
        .diet-badge span { font-size: 11px; }

        /* Floating AI Chat */
        .ai-chat-bubble {
            position: fixed; bottom: 85px; right: 20px;
            width: 60px; height: 60px; border-radius: 30px;
            background: var(--primary); color: white;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; box-shadow: 0 10px 25px rgba(79, 70, 229, 0.4);
            z-index: 1000; animation: float 3s infinite ease-in-out;
            cursor: pointer; border: 2px solid white;
        }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }

        /* Bottom Nav */
        .nav-bar {
            position: fixed; bottom: 0; left: 50%; transform: translateX(-50%);
            width: 100%; max-width: 480px; height: 75px;
            background: white; border-top: 1px solid #f1f5f9;
            display: flex; justify-content: space-around; align-items: center;
            padding: 0 20px; z-index: 100;
        }
        .nav-btn { color: #94a3b8; font-size: 20px; display: flex; flex-direction: column; align-items: center; gap: 4px; text-decoration: none; }
        .nav-btn.active { color: var(--primary); }
        .nav-btn span { font-size: 10px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="mobile-container">
        <header>
            <div class="user-header">
                <div class="user-info">
                    <p>อรุณสวัสดิ์,</p>
                    <h2>คุณ <?= htmlspecialchars($patient['first_name'] ?? 'ผู้ป่วย') ?></h2>
                </div>
                <div class="avatar">
                    <i class="fas fa-heartbeat" style="color: #fda4af;"></i>
                </div>
            </div>

            <?php
                $totalTaken = 0;
                $totalRecords = count($adherenceStats) ? array_sum(array_column($adherenceStats, 'count')) : 0;
                foreach($adherenceStats as $stat) { if($stat['status'] === 'taken') $totalTaken += $stat['count']; }
                $score = $totalRecords > 0 ? round(($totalTaken / $totalRecords) * 100) : 0;
                
                $radius = 28; $circumference = 2 * 3.14159 * $radius;
                $offset = $circumference - (($score / 100) * $circumference);
            ?>

            <div class="adherence-widget">
                <div style="width: 70px; height: 70px; position: relative;">
                    <canvas id="adherenceChart"></canvas>
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center;">
                        <span style="font-size: 14px; font-weight: 700; color: #4ade80;"><?= $score ?>%</span>
                    </div>
                </div>
                <div class="stat-text" style="flex: 1;">
                    <p style="font-weight: 600; font-size: 15px;">ความสม่ำเสมอในการทานยา</p>
                    <p style="font-size: 11px; opacity: 0.8; margin-top: 2px;">
                        <?php 
                        if($score >= 90) echo "รักษาวินัยได้ดีเยี่ยม!";
                        elseif($score >= 70) echo "ดูแลตัวเองได้ดีมากครับ";
                        else echo "พยายามทานยาให้ตรงเวลานะครับ";
                        ?>
                    </p>
                </div>
                <div style="border-left: 1px solid rgba(255,255,255,0.1); padding-left: 15px; text-align: center;">
                    <div style="font-size: 18px; font-weight: 800; color: #fbbf24;"><?= round($mpr) ?>%</div>
                    <div style="font-size: 9px; opacity: 0.7; text-transform: uppercase;">ความมีวินัย</div>
                </div>
            </div>
        </header>

        <div class="content">
            <!-- AI PERSONALIZED INSIGHTS (NEW) -->
            <div class="ai-card">
                <h3><i class="fas fa-magic"></i> AI วิเคราะห์สุขภาพเฉพาะคุณ</h3>
                <div style="background: rgba(255,255,255,0.1); border-radius: 15px; padding: 15px; margin-bottom: 15px;">
                    <p style="font-size: 14px; white-space: pre-line;"><?= $aiAdvice ?></p>
                </div>
                
                <?php if (!empty($dietAdvice)): ?>
                <h4 style="font-size: 14px; margin-bottom: 10px;"><i class="fas fa-utensils"></i> คำแนะนำโภชนาการวันนี้</h4>
                <?php foreach($dietAdvice as $diet): ?>
                    <div class="diet-badge">
                        <strong><?= htmlspecialchars($diet['topic']) ?></strong>
                        <div style="margin-bottom: 4px; color: #fecaca; font-size: 11px;">❌ เลี่ยง: <?= htmlspecialchars($diet['avoid']) ?></div>
                        <div style="color: #bbf7d0; font-size: 11px;">✅ แนะนำ: <?= htmlspecialchars($diet['suggest']) ?></div>
                    </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- AI Visit Summary -->
            <?php if (!empty($visitSummary)): ?>
                <div class="card" style="margin-bottom: 20px; border-left: 4px solid #4a5568; background: #f7fafc;">
                    <div style="padding: 12px 16px;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; color: #2d3748; font-weight: 600;">
                            <i class="fas fa-file-medical-alt" style="color: #4a5568;"></i>
                            สรุปประวัติการตรวจครั้งล่าสุด
                        </div>
                        <div style="font-size: 14px; color: #4a5568; white-space: pre-line; line-height: 1.6;">
                            <?= htmlspecialchars($visitSummary) ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- AI Safety Report -->
            <div class="card" style="background: #fff5f5; border: 1px solid #feb2b2; margin-bottom: 20px;">
                <div style="font-size: 11px; font-weight: 700; color: #c53030; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-shield-alt"></i> รายงานความปลอดภัยจาก AI
                </div>
                <div style="font-size: 13px; line-height: 1.5; color: #742a2a;">
                    <?= nl2br(htmlspecialchars($safetyReport)) ?>
                </div>
            </div>

            <!-- Last Telehealth Summary -->
            <?php if (!empty($telehealthHistory)): ?>
            <?php $last = $telehealthHistory[0]; ?>
            <div class="section-title">สรุปการปรึกษาเภสัชกรครั้งล่าสุด</div>
            <div class="card" style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 20px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span style="font-size: 12px; font-weight: 600; color: #166534;"><?= htmlspecialchars($last['consultant_name']) ?></span>
                    <span style="font-size: 11px; color: #166534; opacity: 0.7;"><?= date('d M Y', strtotime($last['consult_date'])) ?></span>
                </div>
                <p style="font-size: 14px; margin-bottom: 8px; color: #14532d;"><strong>สรุป:</strong> <?= htmlspecialchars($last['summary']) ?></p>
                <div style="background: white; padding: 10px; border-radius: 12px; font-size: 13px; color: #15803d; border-left: 4px solid #22c55e;">
                    <strong>คำแนะนำ:</strong> <?= htmlspecialchars($last['advice']) ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Dashboard Menu -->
            <div class="menu-grid">
                <a href="#" class="menu-item"><i class="fas fa-file-medical"></i><span>ประวัติ</span></a>
                <a href="#" class="menu-item" onclick="startTele(); return false;"><i class="fas fa-video"></i><span>วิดีโอคอล</span></a>
                <a href="#" class="menu-item"><i class="fas fa-box-open"></i><span>ขอรับยา</span></a>
                <a href="#" class="menu-item"><i class="fas fa-calendar-alt"></i><span>นัดหมาย</span></a>
            </div>

            <!-- Medications -->
            <div class="section-title">
                รายการยาประจำวัน
                <a href="#" style="font-size: 13px; color: var(--primary); text-decoration: none;">ดูทั้งหมด</a>
            </div>

            <div id="medication-list">
                <?php if (empty($instructions)): ?>
                    <div style="text-align: center; color: #94a3b8; padding: 40px 0;">
                        <i class="fas fa-pills" style="font-size: 40px; margin-bottom: 10px; opacity: 0.3;"></i>
                        <p>ไม่มีรายการยาที่ต้องดูแลขณะนี้</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($instructions as $med): ?>
                        <?php
                            $text = $med['instruction_text'];
                            $isMorning = (stripos($text, 'เช้า') !== false || stripos($text, '1x3') !== false || stripos($text, '1x2') !== false || stripos($text, '1x1 ac') !== false);
                            $isNoon = (stripos($text, 'กลางวัน') !== false || stripos($text, '1x3') !== false || stripos($text, 'qid') !== false);
                            $isEvening = (stripos($text, 'เย็น') !== false || stripos($text, '1x3') !== false || stripos($text, '1x2') !== false);
                            $isNight = (stripos($text, 'ก่อนนอน') !== false || stripos($text, 'hs') !== false);
                        ?>
                        <div class="med-item">
                            <div class="med-icon">
                                <?php if (!empty($med['image_url'])): ?>
                                    <img src="<?= htmlspecialchars($med['image_url']) ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 16px;">
                                <?php else: ?>
                                    <i class="fas fa-capsules"></i>
                                <?php endif; ?>
                            </div>
                            <div class="med-info">
                                <h4><?= htmlspecialchars($med['drug_name']) ?></h4>
                                <p><?= htmlspecialchars($text) ?></p>
                                <div class="time-badges">
                                    <div class="time-badge morning <?= $isMorning ? 'active' : '' ?>"><i class="fas fa-sun"></i></div>
                                    <div class="time-badge noon <?= $isNoon ? 'active' : '' ?>"><i class="fas fa-cloud-sun"></i></div>
                                    <div class="time-badge evening <?= $isEvening ? 'active' : '' ?>"><i class="fas fa-cloud-moon"></i></div>
                                    <div class="time-badge night <?= $isNight ? 'active' : '' ?>"><i class="fas fa-moon"></i></div>
                                </div>
                            </div>
                            <button class="btn-check" onclick="markTaken(this, '<?= $med['hn'] ?>', '<?= $med['drug_id'] ?>')">
                                <i class="fas fa-check"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Lab Results -->
            <?php if (!empty($patient['lab_results'])): ?>
            <div class="section-title">ผลตรวจทางห้องปฏิบัติการล่าสุด</div>
            <div class="card" style="padding: 15px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <?php 
                        $importantLabs = ['eGFR', 'FBS', 'LDL', 'Potassium', 'HbA1c', 'Cr'];
                        $count = 0;
                        foreach ($patient['lab_results'] as $lab): 
                            $name = $lab['lab_name'];
                            $isImportant = false;
                            foreach($importantLabs as $imp) if(stripos($name, $imp) !== false) $isImportant = true;
                            if(!$isImportant) continue;
                            if($count++ >= 4) break;
                    ?>
                        <div style="background: #f8fafc; padding: 10px; border-radius: 12px; border: 1px solid #f1f5f9;">
                            <div style="font-size: 11px; color: #64748b;"><?= htmlspecialchars($name) ?></div>
                            <div style="font-size: 16px; font-weight: 700; color: var(--dark);"><?= htmlspecialchars($lab['lab_value']) ?></div>
                            <div style="font-size: 10px; color: #94a3b8;"><?= htmlspecialchars($lab['vstdate']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($count == 0): ?>
                    <p style="text-align: center; color: #94a3b8; font-size: 13px; padding: 10px;">ไม่มีข้อมูลผลเลือดที่สำคัญในขณะนี้</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Appointment Card -->
            <?php if ($nextAppointment): ?>
            <div class="section-title">นัดหมายครั้งถัดไป</div>
            <div class="card" style="display: flex; gap: 20px; padding: 20px; align-items: center;">
                <div style="background: #f1f5f9; padding: 12px 18px; border-radius: 18px; text-align: center;">
                    <div style="font-size: 11px; font-weight: 700; color: #64748b;"><?= date('M', strtotime($nextAppointment['date'])) ?></div>
                    <div style="font-size: 22px; font-weight: 700; color: var(--dark);"><?= date('d', strtotime($nextAppointment['date'])) ?></div>
                </div>
                <div>
                    <h4 style="font-size: 16px; margin-bottom: 4px;"><?= htmlspecialchars($nextAppointment['department']) ?></h4>
                    <p style="font-size: 13px; color: #64748b;"><i class="fas fa-clock"></i> <?= $nextAppointment['time'] ?> น.</p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="ai-chat-bubble" onclick="openAIChat()">
            <i class="fas fa-robot"></i>
        </div>

        <!-- AI Chat Modal -->
        <div id="ai-chat-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: flex-end; justify-content: center;">
            <div style="width: 100%; max-width: 480px; background: white; height: 80%; border-top-left-radius: 30px; border-top-right-radius: 30px; display: flex; flex-direction: column; overflow: hidden;">
                <div style="padding: 20px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: var(--primary); color: white;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-robot"></i>
                        <span style="font-weight: 600;">AI Pharmacist Assistant</span>
                    </div>
                    <button onclick="closeAIChat()" style="background: none; border: none; color: white; font-size: 20px;"><i class="fas fa-times"></i></button>
                </div>
                <div id="chat-messages" style="flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 15px; background: #f8fafc;">
                    <div style="align-self: flex-start; background: white; padding: 12px 16px; border-radius: 18px; border-bottom-left-radius: 4px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); max-width: 85%; font-size: 14px;">
                        สวัสดีครับ! ผมเป็น AI ผู้ช่วยเภสัชกรส่วนตัวของคุณ มีอะไรให้ผมช่วยแนะนำเกี่ยวกับเรื่องยาหรือสุขภาพไหมครับ?
                    </div>
                </div>
                <div style="padding: 15px; border-top: 1px solid #f1f5f9; display: flex; gap: 10px; background: white;">
                    <input type="text" id="chat-input" placeholder="พิมพ์ข้อความที่นี่..." style="flex: 1; padding: 12px 20px; border-radius: 25px; border: 1px solid #e2e8f0; outline: none; font-size: 14px;">
                    <button onclick="sendMessage()" style="width: 45px; height: 45px; border-radius: 25px; background: var(--primary); color: white; border: none; font-size: 18px;"><i class="fas fa-paper-plane"></i></button>
                </div>
            </div>
        </div>

        <nav class="nav-bar">
            <a href="#" class="nav-btn active"><i class="fas fa-home"></i><span>Home</span></a>
            <a href="#" class="nav-btn"><i class="fas fa-heartbeat"></i><span>Health</span></a>
            <a href="#" class="nav-btn"><i class="fas fa-comment-dots"></i><span>Chat</span></a>
            <a href="#" class="nav-btn"><i class="fas fa-cog"></i><span>Settings</span></a>
        </nav>
    </div>

    <script>
        const token = '<?= $token ?>';
        
        function markTaken(btn, hn, drugId) {
            if (btn.classList.contains('taken')) return;
            
            btn.classList.add('taken');
            
            fetch('/api/engagement/record-adherence', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ hn, drugId, status: 'taken', token: token })
            }).then(res => res.json())
              .then(data => {
                  if(!data.success) {
                      btn.classList.remove('taken');
                      alert('เกิดข้อผิดพลาดในการบันทึก กรุณาลองใหม่');
                  }
              });
        }

        function loadAIAdvice() {
            fetch(`/api/engagement/ai-advice?token=${token}`)
                .then(res => res.json())
                .then(data => {
                    const text = document.getElementById('ai-advice-text');
                    if(data.success && data.advice) {
                        text.innerHTML = data.advice.replace(/\n/g, '<br>');
                    } else {
                        text.innerHTML = 'สุขภาพของคุณสำคัญที่สุด อย่าลืมวางแผนการออกกำลังกายและทายยาให้ตรงเวลานะครับ';
                    }
                });
        }

        loadAIAdvice();

        function startTele() {
            if (!confirm('ต้องการเริ่มวิดีโอคอลปรึกษาเภสัชกร?')) return;
            fetch('/api/engagement/teleconsult/start', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ token: token })
            }).then(res => res.json())
              .then(data => {
                  if(data.success) window.open(data.url, '_blank');
                  else alert('ไม่สามารถเริ่มการโทรได้: ' + data.message);
              });
        }

        // AI Chat Logic
        function openAIChat() {
            document.getElementById('ai-chat-modal').style.display = 'flex';
        }

        function closeAIChat() {
            document.getElementById('ai-chat-modal').style.display = 'none';
        }

        function sendMessage() {
            const input = document.getElementById('chat-input');
            const term = input.value.trim();
            if (!term) return;

            addChatMessage('user', term);
            input.value = '';

            // Simple simulated responses for now
            setTimeout(() => {
                let response = "ขออภัยครับ ผมยังอยู่ระหว่างการเรียนรู้ข้อมูลเชิงลึก แต่จากการเบื้องต้น แนะนำให้ท่านปฏิบัติตามคำแนะนำของเภสัชกรอย่างเคร่งครัด หากมีอาการผิดปกติสามารถกดปุ่ม วิดีโอคอล เพื่อคุยกับเภสัชกรได้ทันทีครับ";
                
                if (term.includes('ปวด')) response = "หากมีอาการปวด สามารถทานยาแก้ปวดที่ได้รับไปตามขนาดที่ระบุไว้ครับ แต่หากปวดต่อเนื่องเกิน 3 วัน หรือปวดรุนแรงผิดปกติ แนะนำให้มาพบแพทย์นะครับ";
                if (term.includes('ลืม')) response = "หากลืมทานยา ให้ทานทันทีที่นึกได้ แต่ถ้าใกล้เวลาทานมื้อถัดไปแล้ว ให้ข้ามมื้อที่ลืมไปเลยครับ ห้ามทานเพิ่มเป็น 2 เท่าเด็ดขาด";
                if (term.includes('ผลข้างเคียง') || term.includes('แพ้')) response = "หากมีผื่นคัน บวม หรือหายใจลำบากหลังทานยา ให้หยุดยาทันทีและรีบมาโรงพยาบาลที่ใกล้ที่สุดคครับ";

                addChatMessage('bot', response);
            }, 800);
        }

        function addChatMessage(role, text) {
            const container = document.getElementById('chat-messages');
            const div = document.createElement('div');
            
            if (role === 'user') {
                div.style.cssText = "align-self: flex-end; background: var(--primary); color: white; padding: 12px 16px; border-radius: 18px; border-bottom-right-radius: 4px; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.1); max-width: 85%; font-size: 14px;";
            } else {
                div.style.cssText = "align-self: flex-start; background: white; padding: 12px 16px; border-radius: 18px; border-bottom-left-radius: 4px; border: 1px solid #f1f5f9; box-shadow: 0 4px 10px rgba(0,0,0,0.03); max-width: 85%; font-size: 14px;";
            }
            
            div.textContent = text;
            container.appendChild(div);
            container.scrollTop = container.scrollHeight;
        }

        document.getElementById('chat-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') sendMessage();
        });

        // Adherence Chart
        const ctx = document.getElementById('adherenceChart').getContext('2d');
        const score = <?= $score ?>;
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [score, 100 - score],
                    backgroundColor: ['#4f46e5', '#f1f5f9'],
                    borderWidth: 0
                }]
            },
            options: {
                cutout: '80%',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: false }
                }
            }
        });
    </script>
</body>
</html>
