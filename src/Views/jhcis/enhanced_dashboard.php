<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JHCIS Dashboard - Drugmuk</title>
    <?= \App\Core\CSRF::metaTag() ?>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .jhcis-dashboard {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .hospital-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .hospital-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .hospital-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .hospital-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        .hospital-name {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-active { background: #10b981; color: white; }
        .status-inactive { background: #ef4444; color: white; }
        .metric-row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            padding: 8px 0;
        }
        .metric-label {
            color: #666;
            font-size: 14px;
        }
        .metric-value {
            font-weight: bold;
            color: #333;
        }
        .success-rate {
            color: #10b981;
        }
        .warning-rate {
            color: #f59e0b;
        }
        .error-rate {
            color: #ef4444;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #f0f0f0;
        }
        .btn {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #5568d3;
        }
        .btn-secondary {
            background: #f3f4f6;
            color: #333;
        }
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        .alert-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .alert-item {
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid;
            border-radius: 5px;
            background: #f9fafb;
        }
        .alert-high { border-color: #ef4444; }
        .alert-medium { border-color: #f59e0b; }
        .alert-low { border-color: #3b82f6; }
    </style>
</head>
<body>
    <div class="jhcis-dashboard">
        <div class="dashboard-header">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h1>🔗 แดชบอร์ดการเชื่อมต่อ JHCIS</h1>
                    <p>จัดการการเชื่อมต่อและซิงค์ข้อมูลกับระบบฐานข้อมูล JHCIS ของ รพ.สต.</p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <a href="/settings/database" class="btn btn-secondary" style="background: white; color: #667eea; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold;">
                        🏥 ตั้งค่าฐานข้อมูล JHCIS
                    </a>
                    <a href="/dashboard" class="btn btn-secondary" style="background: white; color: #667eea; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold;">
                        ← กลับหน้าหลัก
                    </a>
                </div>
            </div>
        </div>

        <div class="hospital-grid">
            <?php foreach ($hospitals as $hospital): ?>
                <?php 
                    $summary = $summaries[$hospital['id']] ?? null;
                    $isActive = $hospital['is_active'];
                ?>
                <div class="hospital-card">
                    <div class="hospital-header">
                        <div class="hospital-name"><?= htmlspecialchars($hospital['name']) ?></div>
                        <span class="status-badge <?= $isActive ? 'status-active' : 'status-inactive' ?>">
                            <?= $isActive ? 'เปิดรวมงาน' : 'ปิดรวมงาน' ?>
                        </span>
                    </div>

                    <?php if ($isActive && $summary && !isset($summary['error'])): ?>
                        <?php if (isset($summary['no_data'])): ?>
                            <div class="alert-item alert-low" style="margin: 15px 0; padding: 15px; border-left: 4px solid #3b82f6; border-radius: 5px; background: #f9fafb;">
                                ℹ️ ยังไม่มีข้อมูล Sync - กรุณาคลิก "🔄 Sync Now" เพื่อเริ่มต้น
                            </div>
                        <?php else: ?>
                            <div class="metric-row">
                                <span class="metric-label">📊 อัตราการ Sync สำเร็จ:</span>
                                <span class="metric-value <?= $summary['sync_performance']['success_rate'] >= 95 ? 'success-rate' : 'warning-rate' ?>">
                                    <?= $summary['sync_performance']['success_rate'] ?>%
                                </span>
                            </div>

                            <div class="metric-row">
                                <span class="metric-label">🔄 จำนวนรอบ Sync:</span>
                                <span class="metric-value"><?= number_format($summary['sync_performance']['total_syncs']) ?></span>
                            </div>

                            <div class="metric-row">
                                <span class="metric-label">📝 รายการที่ Sync แล้ว:</span>
                                <span class="metric-value"><?= number_format($summary['sync_performance']['total_records']) ?></span>
                            </div>

                            <div class="metric-row">
                                <span class="metric-label">💊 ยาที่จับคู่แล้ว:</span>
                                <a href="/admin/jhcis/mapping?hospital_id=<?= $hospital['id'] ?>" class="metric-value" style="text-decoration: none; color: #667eea; border-bottom: 1px dashed #667eea;">
                                    <?= number_format($summary['data_quality']['mapped_drugs']) ?>
                                </a>
                            </div>

                            <?php if ($summary['last_sync']): ?>
                            <div class="metric-row">
                                <span class="metric-label">⏰ Last Sync:</span>
                                <span class="metric-value" style="font-size: 12px;">
                                    <?= date('d/m/Y H:i', strtotime($summary['last_sync'])) ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <div class="action-buttons">
                            <button onclick="testConnection(event, <?= $hospital['id'] ?>)" class="btn btn-primary">
                                🔍 ทดสอบการเชื่อมต่อ
                            </button>
                            <button onclick="syncNow(event, <?= $hospital['id'] ?>)" class="btn btn-secondary">
                                🔄 ซิงค์ตอนนี้
                            </button>
                        </div>
                        <div class="action-buttons">
                            <a href="/admin/jhcis/auto-mapping?hospital_id=<?= $hospital['id'] ?>" class="btn btn-secondary">
                                🤖 จับคู่อัตโนมัติ
                            </a>
                            <a href="/admin/jhcis/mapping?hospital_id=<?= $hospital['id'] ?>" class="btn btn-secondary">
                                🔗 จัดการการจับคู่
                            </a>
                            <a href="/admin/jhcis/reconciliation?hospital_id=<?= $hospital['id'] ?>" class="btn btn-secondary">
                                📊 ตรวจสอบยอดสต็อก
                            </a>
                        </div>
                        <div class="action-buttons">
                            <a href="/settings/database" class="btn btn-secondary">
                                ⚙️ ตั้งค่าการเชื่อมต่อ
                            </a>
                            <a href="/admin/jhcis/reports?hospital_id=<?= $hospital['id'] ?>" class="btn btn-secondary">
                                📈 รายงานการซิงค์
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="alert-item alert-low">
                            ℹ️ Hospital is inactive
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="alert-section">
            <h2>🔔 การแจ้งเตือนล่าสุด</h2>
            <?php
                $alertService = new \App\Services\JHCIS\JHCISAlertService();
                $recentAlerts = $alertService->getActiveAlerts(null, 10);
            ?>
            <?php if (empty($recentAlerts)): ?>
                <p style="color: #10b981; padding: 20px; text-align: center;">
                    ✅ No active alerts
                </p>
            <?php else: ?>
                <?php foreach ($recentAlerts as $alert): ?>
                    <div class="alert-item alert-<?= $alert['severity'] ?>">
                        <strong><?= htmlspecialchars($alert['type']) ?></strong>
                        <p><?= htmlspecialchars($alert['message']) ?></p>
                        <small style="color: #666;">
                            <?= date('Y-m-d H:i:s', strtotime($alert['created_at'])) ?>
                        </small>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Test JHCIS connection
        async function testConnection(event, hospitalId) {
            if (!confirm('ทดสอบการเชื่อมต่อ JHCIS?')) return;
            
            const btn = event.currentTarget;
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = '⏳ กำลังทดสอบ...';
            
            try {
                const response = await fetch(`/api/jhcis/test-connection/${hospitalId}`);
                const data = await response.json();
                
                if (data.success) {
                    let message = `✅ ${data.message}\n`;
                    message += `พบยา ${data.total_drugs} รายการ\n`;
                    message += `ตารางทั้งหมด: ${data.total_tables} ตาราง\n`;
                    if (data.dispensing_tables && data.dispensing_tables.length > 0) {
                        message += `ตารางจ่ายยา: ${data.dispensing_tables.join(', ')}\n`;
                    } else {
                        message += `⚠️ ไม่พบตารางจ่ายยา (visitdrug, opd_dispensing, dispensing)\n`;
                    }
                    if (data.sample_tables && data.sample_tables.length > 0) {
                        message += `\nตัวอย่างตาราง:\n${data.sample_tables.slice(0, 10).join(', ')}`;
                    }
                    alert(message);
                } else {
                    alert(`❌ ${data.message}`);
                }
            } catch (error) {
                alert('❌ เกิดข้อผิดพลาด: ' + error.message);
            } finally {
                btn.disabled = false;
                btn.textContent = originalText;
            }
        }
        
        // Sync data now
        async function syncNow(event, hospitalId) {
            if (!confirm('ต้องการ Sync ข้อมูลจาก JHCIS ตอนนี้?\n(จะดึงข้อมูล 30 วันย้อนหลัง)')) return;
            
            const btn = event.currentTarget;
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = '⏳ กำลัง Sync...';
            
            try {
                const response = await fetch(`/api/jhcis/sync-now/${hospitalId}`, {
                    method: 'POST'
                });
                const data = await response.json();
                
                if (data.success) {
                    alert(`✅ Sync สำเร็จ!\nImport: ${data.imported} รายการ\nFailed: ${data.failed} รายการ`);
                    location.reload();
                } else {
                    alert(`❌ ${data.message}`);
                }
            } catch (error) {
                alert('❌ เกิดข้อผิดพลาด: ' + error.message);
            } finally {
                btn.disabled = false;
                btn.textContent = originalText;
            }
        }
        
        // Auto-refresh every 30 seconds
        setTimeout(() => {
            location.reload();
        }, 30000);
    </script>
    <script>
        // Auto-include CSRF token in all AJAX requests
        document.addEventListener('DOMContentLoaded', function() {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            if (csrfToken) {
                // Fetch API
                const originalFetch = window.fetch;
                window.fetch = function(url, options = {}) {
                    const method = (options.method || 'GET').toUpperCase();
                    if (method !== 'GET') {
                        options.headers = options.headers || {};
                        options.headers['X-CSRF-Token'] = csrfToken;
                        
                        // If body is FormData, we can't easily add to it without affecting the boundary
                        // If body is JSON string, we should probably add it there too, but headers are usually enough
                    }
                    return originalFetch(url, options);
                };
            }
        });
    </script>
</body>
</html>
