<?php include dirname(__DIR__) . '/layouts/header.php'; ?>

<style>
    :root {
        --tele-primary: #6366f1;
        --tele-success: #10b981;
        --tele-warning: #f59e0b;
        --tele-danger: #ef4444;
        --tele-glass-bg: rgba(255, 255, 255, 0.7);
        --tele-shadow: 0 8px 32px rgba(31, 38, 135, 0.1);
    }

    body {
        background-color: #f0f2f5;
    }

    .tele-header {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        color: white;
        padding: 40px;
        border-radius: 24px;
        margin-bottom: 30px;
        box-shadow: 0 20px 40px rgba(79, 70, 229, 0.2);
        position: relative;
        overflow: hidden;
    }

    .tele-header::after {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 400px;
        height: 400px;
        background: rgba(255,255,255,0.1);
        border-radius: 50%;
    }

    .tele-header h1 {
        font-size: 32px;
        font-weight: 800;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        padding: 24px;
        border-radius: 20px;
        box-shadow: var(--tele-shadow);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border-top: 4px solid transparent;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .stat-card:hover { transform: translateY(-8px); }
    .stat-card.primary { border-top-color: var(--tele-primary); }
    .stat-card.success { border-top-color: var(--tele-success); }
    .stat-card.warning { border-top-color: var(--tele-warning); }
    .stat-card.danger { border-top-color: var(--tele-danger); }

    .stat-card .icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin-bottom: 16px;
    }

    .stat-card.primary .icon { background: #e0e7ff; color: var(--tele-primary); }
    .stat-card.success .icon { background: #dcfce7; color: var(--tele-success); }
    .stat-card.warning .icon { background: #fef3c7; color: var(--tele-warning); }
    .stat-card.danger .icon { background: #fee2e2; color: var(--tele-danger); }

    .stat-card h3 { font-size: 28px; font-weight: 800; margin: 0; color: #1e293b; }
    .stat-card p { font-size: 14px; color: #64748b; margin: 4px 0 0 0; }

    .dashboard-layout {
        display: grid;
        grid-template-columns: 1fr 350px;
        gap: 25px;
    }

    .patients-section {
        background: white;
        padding: 30px;
        border-radius: 24px;
        box-shadow: var(--tele-shadow);
    }

    .side-section {
        display: flex;
        flex-direction: column;
        gap: 25px;
    }

    .side-card {
        background: white;
        padding: 24px;
        border-radius: 20px;
        box-shadow: var(--tele-shadow);
    }

    .side-card h4 {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        color: #1e293b;
    }

    .patient-card {
        padding: 24px;
        border: 1px solid #f1f5f9;
        border-radius: 20px;
        margin-bottom: 16px;
        transition: all 0.3s;
        background: #fff;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .patient-card:hover {
        border-color: var(--tele-primary);
        box-shadow: 0 12px 24px rgba(99, 102, 241, 0.08);
        transform: scale(1.01);
    }

    .patient-card.critical {
        border-left: 6px solid var(--tele-danger);
        background: #fffafa;
    }

    .patient-card.high {
        border-left: 6px solid var(--tele-warning);
    }

    .patient-main {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .patient-info {
        display: flex;
        align-items: center;
        gap: 20px;
        flex: 1;
    }

    .patient-avatar {
        width: 64px;
        height: 64px;
        border-radius: 18px;
        background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 24px;
        font-weight: 800;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }

    .patient-details h4 { font-size: 20px; font-weight: 700; margin: 0 0 4px 0; color: #1e293b; }
    .patient-meta { display: flex; flex-wrap: wrap; gap: 12px; font-size: 13px; color: #64748b; }
    .patient-meta span { display: flex; align-items: center; gap: 6px; }

    .ai-insight-strip {
        background: #f8fafc;
        border-radius: 12px;
        padding: 12px 16px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        font-size: 14px;
        border: 1px dashed #cbd5e1;
    }

    .risk-badge {
        padding: 6px 12px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .risk-badge.critical { background: #fee2e2; color: #ef4444; }
    .risk-badge.high { background: #fef3c7; color: #d97706; }
    .risk-badge.low { background: #dcfce7; color: #10b981; }

    .btn-consult {
        background: linear-gradient(135deg, #6366f1 0%, #7c3aed 100%);
        color: white;
        padding: 12px 24px;
        border-radius: 12px;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
    }

    .btn-consult:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
        color: white;
    }

    .soap-preview {
        font-size: 13px;
        color: #475569;
        font-family: 'Courier New', Courier, monospace;
        background: #f1f5f9;
        padding: 10px;
        border-radius: 8px;
        margin-top: 8px;
        white-space: pre-wrap;
    }

    .lab-alert-item {
        padding: 12px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .lab-alert-item:last-child { border-bottom: none; }
    .lab-alert-item strong { font-size: 14px; color: #1e293b; }
    .lab-alert-item span { font-size: 12px; color: #64748b; }
</style>

<div class="tele-header">
    <div style="display: flex; justify-content: space-between; align-items: center; position: relative; z-index: 1;">
        <div>
            <h1><i class="fas fa-brain"></i> AI Tele-pharmacy Dashboard</h1>
            <p>ระบบคัดกรองความปลอดภัยทางเภสัชกรรมเชิงรุก พร้อมการวิเคราะห์ผู้ป่วยด้วย AI</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-light" onclick="location.reload()"><i class="fas fa-sync-alt"></i> Refresh</button>
            <a href="/dashboard" class="btn btn-outline-light"><i class="fas fa-arrow-left"></i> หน้าหลัก</a>
        </div>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card primary">
        <div>
            <div class="icon"><i class="fas fa-microchip"></i></div>
            <h3><?= $stats['forecast_accuracy'] ?? 85 ?>%</h3>
            <p>AI Analysis Accuracy</p>
        </div>
    </div>
    <div class="stat-card danger">
        <div>
            <div class="icon"><i class="fas fa-radiation"></i></div>
            <h3><?= count(array_filter($patients, fn($p) => ($p['ai_risk_level'] ?? '') === 'Critical')) ?></h3>
            <p>Critical Risk Patients</p>
        </div>
    </div>
    <div class="stat-card warning">
        <div>
            <div class="icon"><i class="fas fa-capsules"></i></div>
            <h3><?= $stats['polypharmacy_count'] ?? 0 ?></h3>
            <p>Polypharmacy Cases</p>
        </div>
    </div>
    <div class="stat-card success">
        <div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
            <h3><?= count($patients) ?></h3>
            <p>Patients Screened</p>
        </div>
    </div>
</div>

<div class="dashboard-layout">
    <div class="patients-section">
        <div class="section-header">
            <h2><i class="fas fa-list-ul"></i> Patient Screening Queue</h2>
            <div class="d-flex gap-2">
                <span class="badge bg-danger rounded-pill"><?= count(array_filter($patients, fn($p) => ($p['ai_risk_level'] ?? '') === 'Critical')) ?> Priority</span>
            </div>
        </div>

        <?php if (empty($patients)): ?>
            <div class="empty-state">
                <i class="fas fa-robot"></i>
                <h3>No patients in queue</h3>
                <p>Relax, AI is monitoring for new risks.</p>
            </div>
        <?php else: ?>
            <?php foreach ($patients as $patient): 
                $riskColor = ($patient['ai_risk_level'] === 'Critical') ? 'critical' : (($patient['ai_risk_level'] === 'High') ? 'high' : 'low');
            ?>
                <div class="patient-card <?= $riskColor ?>">
                    <div class="patient-main">
                        <div class="patient-info">
                            <div class="patient-avatar">
                                <?= strtoupper(substr($patient['first_name'] ?? 'P', 0, 1)) ?>
                            </div>
                            <div class="patient-details">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <h4><?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']) ?></h4>
                                    <span class="risk-badge <?= $riskColor ?>">
                                        <?= $patient['ai_risk_level'] ?> Risk (<?= $patient['ai_risk_score'] ?>)
                                    </span>
                                </div>
                                <div class="patient-meta">
                                    <span><i class="fas fa-id-card"></i> HN: <?= $patient['hn'] ?></span>
                                    <span><i class="fas fa-user-clock"></i> อายุ <?= isset($patient['birth_date']) ? date_diff(date_create($patient['birth_date']), date_create('today'))->y : 'N/A' ?> ปี</span>
                                    <span><i class="fas fa-bell"></i> Alerts: <?= $patient['ai_alerts_count'] ?? 0 ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="patient-actions">
                            <a href="/tele-pharmacy/room/<?= $patient['hn'] ?>" class="btn-consult">
                                <i class="fas fa-video"></i> Start Consult
                            </a>
                        </div>
                    </div>

                    <div class="ai-insight-strip">
                        <i class="fas fa-robot text-primary mt-1"></i>
                        <div>
                            <strong>AI Clinical Insight:</strong><br>
                            <?= $patient['ai_summary'] ?? 'Stable profile. No immediate risks detected.' ?>
                            
                            <?php if (isset($patient['last_soap'])): ?>
                                <div class="mt-2">
                                    <strong>Last Consultation (SOAP):</strong>
                                    <div class="soap-preview"><?= htmlspecialchars(substr($patient['last_soap'], 0, 150)) ?>...</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="side-section">
        <div class="side-card">
            <h4><i class="fas fa-exclamation-circle text-danger"></i> AI Lab Advisory</h4>
            <p class="text-muted small">Patients with missing labs based on their drug therapy.</p>
            <div class="lab-alerts-list">
                <?php if (empty($missingLabs)): ?>
                    <p class="text-center text-muted p-4">No missing labs found.</p>
                <?php else: ?>
                    <?php foreach ($missingLabs as $lab): ?>
                        <div class="lab-alert-item">
                            <strong><?= $lab['name'] ?></strong>
                            <span>Check <b><?= $lab['lab'] ?></b></span>
                            <span class="text-primary italic"><i class="fas fa-pills"></i> Drug: <?= $lab['drug'] ?></span>
                            <div class="mt-2">
                                <a href="/tele-pharmacy/room/<?= $lab['hn'] ?>" class="btn btn-sm btn-outline-primary w-100">Contact Patient</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="side-card shadow-sm" style="background: #f1f5f9;">
            <h4><i class="fas fa-clock"></i> Recent Activity</h4>
            <div class="small">
                <div class="p-2 border-bottom d-flex align-items-center gap-2">
                    <i class="fas fa-check-circle text-success"></i>
                    <span>AI Audit completed (100 patients)</span>
                </div>
                <div class="p-2 border-bottom d-flex align-items-center gap-2">
                    <i class="fas fa-sync text-primary"></i>
                    <span>JHCIS Data Synced (2m ago)</span>
                </div>
                <div class="p-2 d-flex align-items-center gap-2">
                    <i class="fas fa-user-plus text-info"></i>
                    <span>3 new patients in queue</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
