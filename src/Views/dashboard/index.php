<?php require_once __DIR__ . '/../layouts/header_responsive.php'; ?>

    <style>
        /* Dashboard specific styles */
        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding-bottom: 40px;
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
            margin: 0;
        }

        /* Responsive Nav - Fix for Desktop */
        .nav {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: flex-start; /* Align nav items to the start */
            padding-bottom: 20px;
        }

        .nav a {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 160px; /* Fixed width for consistent grid look */
            height: 120px; /* Fixed height */
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 15px;
            transition: transform 0.2s, box-shadow 0.2s;
            text-align: center;
            font-size: 14px;
            line-height: 1.4;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            flex: 0 0 auto; /* Don't grow or shrink unexpectedly */
        }
        
        .nav a .nav-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .nav a:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.15);
        }

        .metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .metric-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .metric-card:hover {
            transform: translateY(-5px);
        }

        .metric-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .metric-card .value {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
        }
        
        .metric-card.quality-excellent .value { color: #10b981; }
        .metric-card.quality-good .value { color: #3b82f6; }
        .metric-card.quality-fair .value { color: #f59e0b; }
        .metric-card.quality-poor .value { color: #ef4444; }

        .alerts {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .alerts h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 22px;
        }

        .alert-item {
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .alert-item.warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
        }

        .alert-item.danger {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
        }

        .alert-item.info {
            background: #d1ecf1;
            border-left: 4px solid #17a2b8;
        }

        .alert-icon {
            font-size: 24px;
        }

        .recent-activity {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
        }

        .activity-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .activity-card h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 20px;
        }

        .activity-item {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-item .date {
            color: #999;
            font-size: 12px;
        }

        .activity-item .title {
            color: #333;
            font-weight: 500;
            margin-top: 5px;
        }
        .session-message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            animation: slideIn 0.3s ease-out;
        }
        .session-message.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .session-message.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
                display: none; /* Hide old header on mobile since we have navbar */
            }
            
            .nav {
                grid-template-columns: repeat(3, 1fr); /* 3 cols on mobile */
            }

            .nav a {
                height: 90px;
                font-size: 11px;
            }
            
            .nav a .nav-icon {
                font-size: 24px;
            }
            
            .recent-activity {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="dashboard-container">
        <?php if (isset($_SESSION['success'])): ?>
        <div class="session-message success">
            ✅ <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
        <div class="session-message error">
            ❌ <?= htmlspecialchars($_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); endif; ?>

        <!-- Welcome Header (Desktop Only) -->
        <div class="header d-none d-md-flex" style="background: rgba(255,255,255,0.9); margin-bottom: 20px;">
            <div>
                <h1 style="font-size: 1.5rem; margin-bottom: 5px;">👋 ยินดีต้อนรับ, <?= htmlspecialchars($_SESSION['full_name'] ?? 'Guest') ?></h1>
                <p style="color: #666; margin: 0;">ระบบบริหารคลังเวชภัณฑ์ Drugmuk (v3.6.0)</p>
            </div>
            <div style="text-align: right;">
                <span class="badge bg-primary" style="padding: 8px 12px; border-radius: 20px; background: #667eea; color: white;">
                    <?= htmlspecialchars($_SESSION['role'] ?? 'User') ?>
                </span>
            </div>
        </div>

        <!-- 🤖 AI Assistant Daily Briefing Widget -->
        <div id="ai-assistant-briefing" style="display:none; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:white; padding: 20px 30px; border-radius: 15px; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(118, 75, 162, 0.3); animation: slideIn 0.5s ease-out;">
            <div style="display: flex; align-items: start; gap: 20px;">
                <div style="background: rgba(255,255,255,0.2); padding: 15px; border-radius: 50%; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; font-size: 30px;">
                    🤖
                </div>
                <div style="flex-grow: 1;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                        <h2 style="margin:0; font-size:20px; color:white;" id="ai-brief-greeting">กำลังประมวลผล...</h2>
                        <span style="background:rgba(255,255,255,0.2); px:10px; py:4px; border-radius:20px; font-size:12px; padding: 4px 12px;" id="ai-brief-date"></span>
                    </div>
                    <div style="font-size:16px; line-height:1.6; opacity:0.95;">
                        <div id="ai-brief-status" style="font-weight:bold; margin-bottom:5px;"></div>
                        <div id="ai-brief-details"></div>
                        <div id="ai-brief-alert" style="margin-top:10px; background:rgba(255,255,255,0.15); padding:10px; border-radius:8px; display:none;"></div>
                    </div>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            fetchBriefing();
        });

        async function fetchBriefing() {
            try {
                const formData = new FormData();
                formData.append('message', '__get_briefing__');
                
                // Get CSRF Token if available
                const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
                const headers = {};
                if (csrfTokenMeta) {
                    headers['X-CSRF-Token'] = csrfTokenMeta.getAttribute('content');
                    headers['X-Requested-With'] = 'XMLHttpRequest';
                }

                const response = await fetch('/ai/chat', { // Assuming same endpoint
                    method: 'POST',
                    headers: headers,
                    body: formData
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.widget === 'daily_briefing') {
                        renderBriefing(data.data);
                    }
                }
            } catch (e) {
                console.error("AI Briefing Error:", e);
            }
        }

        function renderBriefing(data) {
            const container = document.getElementById('ai-assistant-briefing');
            document.getElementById('ai-brief-greeting').textContent = data.greeting;
            document.getElementById('ai-brief-date').textContent = data.date;
            document.getElementById('ai-brief-status').textContent = data.status;
            document.getElementById('ai-brief-details').innerHTML = data.details;
            
            if (data.alert) {
                const alertBox = document.getElementById('ai-brief-alert');
                alertBox.innerHTML = data.alert;
                alertBox.style.display = 'block';
                // Add shake animation for alerts
                if (data.low_stock_count > 0) {
                     alertBox.style.border = '1px solid #fea5a5';
                     alertBox.style.background = 'rgba(239, 68, 68, 0.2)';
                }
            }

            container.style.display = 'block';
        }
        </script>

        <div class="header" style="margin-top: 20px;">
            <nav class="nav">
                <a href="/dashboard"><div class="nav-icon">🏠</div>หน้าหลัก</a>
                <a href="/orders/auto-replenish" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); width: 150px; flex-grow: 1; min-width: 150px;"><div class="nav-icon">✨🤖</div>Smart Auto-PO</a>
                <a href="/purchasing"><div class="nav-icon">📊</div>แผนซื้อ</a>
                <a href="/orders"><div class="nav-icon">🛒</div>สั่งซื้อ</a>
                <a href="/warehouse"><div class="nav-icon">🏭</div>คลังใหญ่</a>
                <a href="/subwarehouse"><div class="nav-icon">🏪</div>คลังย่อย</a>
                <a href="/dispensing" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);"><div class="nav-icon">💊</div>จ่ายยา</a>
                <a href="/contracts"><div class="nav-icon">📄</div>สัญญา</a>
                <a href="/admin/jhcis/dashboard" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);"><div class="nav-icon">🔗</div>JHCIS Dashboard</a>
                <a href="/admin/jhcis/hospitals" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);"><div class="nav-icon">🏥</div>จัดการ รพ.สต.</a>
                <a href="/admin/jhcis/mapping" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);"><div class="nav-icon">💊</div>Drug Mapping</a>
                <a href="/jhcis-import" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);"><div class="nav-icon">⬇️</div>Import JHCIS</a>
                <a href="/jhcis-drugs" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);"><div class="nav-icon">📋</div>รายการยา JHCIS</a>
                <a href="/import-history" style="background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);"><div class="nav-icon">📊</div>ประวัติ Import</a>
                <a href="/admin/intelligence" style="background: linear-gradient(135deg, #a855f7 0%, #7e22ce 100%);"><div class="nav-icon">🧠</div>Intelligence Center</a>
            </nav>
        </div>

        <!-- Phase 3 Advanced Features -->
        <div class="header" style="margin-top: 20px; background: linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%);">
            <nav class="nav" style="flex-wrap: wrap;">
                <a href="/analytics" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); width: 150px; flex-grow: 1; min-width: 150px;"><div class="nav-icon">📊✨</div>Analytics Dashboard</a>
                <a href="/scan" style="background: rgba(255,255,255,0.2);"><div class="nav-icon">📷</div>สแกนบาร์โค้ด</a>
                <a href="/reports" style="background: rgba(255,255,255,0.2);"><div class="nav-icon">📊</div>รายงาน</a>
                <a href="/dmsic" style="background: rgba(255,255,255,0.2);"><div class="nav-icon">🏥</div>DMSIC</a>
                <a href="/data-cleansing" style="background: rgba(255,255,255,0.2);"><div class="nav-icon">🧹</div>Data Cleansing</a>
                <a href="/realtime-sync" style="background: rgba(255,255,255,0.2);"><div class="nav-icon">⚡</div>Real-time Sync</a>
                <a href="/notifications" style="background: rgba(255,255,255,0.2);"><div class="nav-icon">🔔</div>การแจ้งเตือน</a>
                <a href="/audit-trail" style="background: rgba(255,255,255,0.2);"><div class="nav-icon">📜</div>Audit Trail</a>
                <a href="/settings/database" style="background: rgba(255,255,255,0.4); border: 1px solid white;"><div class="nav-icon">⚙️</div>ตั้งค่าระบบ</a>
                <a href="/updates" style="background: rgba(255,255,255,0.2);"><div class="nav-icon">🔄</div>อัพเดท</a>
                <a href="/admin/jhcis/api-debug" style="background: rgba(255,255,255,0.2);"><div class="nav-icon">🔍</div>JHCIS API Debug</a>
            </nav>
        </div>

        <div class="metrics">
            <div class="metric-card">
                <h3>📦 ยาใกล้หมดสต็อก</h3>
                <div class="value"><?= $metrics['low_stock_count'] ?? 0 ?></div>
            </div>
            <div class="metric-card">
                <h3>⏰ ยาใกล้หมดอายุ (90 วัน)</h3>
                <div class="value"><?= $metrics['expiring_soon_count'] ?? 0 ?></div>
            </div>
            <div class="metric-card">
                <h3>📋 ใบสั่งซื้อค้างรับ</h3>
                <div class="value"><?= $metrics['pending_orders_count'] ?? 0 ?></div>
            </div>
            <div class="metric-card">
                <h3>📄 สัญญาใกล้หมดอายุ</h3>
                <div class="value"><?= $metrics['expiring_contracts_count'] ?? 0 ?></div>
            </div>
            <div class="metric-card">
                <h3>🔔 คำขอเบิกค้างอนุมัติ</h3>
                <div class="value"><?= $metrics['pending_disbursements_count'] ?? 0 ?></div>
            </div>
            <?php 
                $quality = $metrics['data_quality_score'][0] ?? ['quality_score' => 0, 'quality_rating' => 'poor'];
                $qualityClass = 'quality-' . $quality['quality_rating'];
            ?>
            <a href="/data-cleansing" style="text-decoration: none;">
                <div class="metric-card <?= $qualityClass ?>">
                    <h3>🧹 คะแนนคุณภาพข้อมูล</h3>
                    <div class="value"><?= number_format($quality['quality_score'], 1) ?>%</div>
                </div>
            </a>
            
            <a href="/admin/jhcis/dashboard" style="text-decoration: none;">
                <div class="metric-card" style="border-left: 4px solid #10b981;">
                    <h3>🏥 รพ.สต. ที่เชื่อมต่อ</h3>
                    <div class="value"><?= $metrics['jhcis_stats']['active_hospitals'] ?? 0 ?> <span style="font-size: 14px; color: #666;">แห่ง</span></div>
                </div>
            </a>

            <?php 
                $failures = $metrics['jhcis_stats']['recent_failures'] ?? 0;
                $syncColor = $failures > 0 ? '#ef4444' : '#10b981';
                $syncText = $failures > 0 ? $failures . ' ข้อผิดพลาด' : 'ปกติ';
            ?>
            <a href="/realtime-sync" style="text-decoration: none;">
                <div class="metric-card" style="border-left: 4px solid <?= $syncColor ?>;">
                    <h3>⚡ สถานะ Sync (24ชม.)</h3>
                    <div class="value" style="color: <?= $syncColor ?>; font-size: 28px;">
                        <?= $syncText ?>
                    </div>
                    <?php if (!empty($metrics['jhcis_stats']['last_sync'])): ?>
                        <div style="font-size: 12px; color: #999; margin-top: 5px;">
                            ล่าสุด: <?= date('d/m H:i', strtotime($metrics['jhcis_stats']['last_sync'])) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </a>
        </div>

        <?php if (!empty($alerts)): ?>
        <div class="alerts">
            <h2>🔔 การแจ้งเตือน</h2>
            <?php foreach (array_slice($alerts, 0, 10) as $alert): ?>
            <div class="alert-item <?= $alert['type'] ?>">
                <span class="alert-icon"><?= $alert['icon'] === 'box' ? '📦' : ($alert['icon'] === 'clock' ? '⏰' : '📄') ?></span>
                <div>
                    <?= htmlspecialchars($alert['message']) ?>
                    <?php if (isset($alert['link'])): ?>
                    <a href="<?= $alert['link'] ?>" style="margin-left: 10px; color: #667eea;">ดูรายละเอียด →</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="recent-activity">
            <div class="activity-card">
                <h2>📋 ใบสั่งซื้อล่าสุด</h2>
                <?php if (!empty($recent_orders)): ?>
                    <?php foreach ($recent_orders as $order): ?>
                    <div class="activity-item">
                        <div class="date"><?= date('d/m/Y', strtotime($order['order_date'])) ?></div>
                        <div class="title">
                            <?= htmlspecialchars($order['order_no']) ?> - 
                            <?= htmlspecialchars($order['supplier_name'] ?? 'N/A') ?>
                            (<?= number_format($order['total_amount'], 2) ?> บาท)
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #999;">ไม่มีข้อมูล</p>
                <?php endif; ?>
            </div>

            <div class="activity-card">
                <h2>📦 การรับยาล่าสุด</h2>
                <?php if (!empty($recent_receives)): ?>
                    <?php foreach ($recent_receives as $receive): ?>
                    <div class="activity-item">
                        <div class="date"><?= date('d/m/Y H:i', strtotime($receive['transaction_date'])) ?></div>
                        <div class="title">
                            <?= htmlspecialchars($receive['drug_name']) ?> 
                            (<?= $receive['quantity'] ?> หน่วย)
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #999;">ไม่มีข้อมูล</p>
                <?php endif; ?>
            </div>
        </div>
        </div>
    </div> <!-- End dashboard-container -->
    
    </main> <!-- End main container from header_responsive.php -->
    
</body>
</html>
