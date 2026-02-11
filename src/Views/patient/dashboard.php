<?php include dirname(__DIR__) . '/layouts/header.php'; ?>

<div class="patient-intelligence-container">
    <div class="dashboard-header glass-effect">
        <div class="patient-info-primary">
            <div class="patient-avatar">
                <span class="avatar-text"><?php echo mb_substr($patient['fname'], 0, 1); ?></span>
            </div>
            <div class="patient-id-info">
                <h1><?php echo $patient['pname'] . $patient['fname'] . ' ' . $patient['lname']; ?></h1>
                <div class="patient-meta">
                    <span class="meta-item"><i class="fas fa-id-card"></i> HN: <strong><?php echo $hn; ?></strong></span>
                    <span class="meta-item"><i class="fas fa-fingerprint"></i> CID: <strong><?php echo $patient['cid']; ?></strong></span>
                    <span class="meta-item"><i class="fas fa-venus-mars"></i> <strong><?php echo $patient['sex_label']; ?></strong></span>
                    <span class="meta-item"><i class="fas fa-birthday-cake"></i> <strong><?php echo $patient['age']; ?></strong> ปี</span>
                </div>
            </div>
        </div>
        <div class="header-actions">
            <button id="sync-patient" class="btn btn-primary" data-hn="<?php echo $hn; ?>">
                <i class="fas fa-sync-alt"></i> ซิงค์ข้อมูลล่าสุด
            </button>
            <button id="print-profile" class="btn btn-outline">
                <i class="fas fa-print"></i> พิมพ์ประวัติ
            </button>
        </div>
    </div>

    <div class="dashboard-grid">
        <!-- Left Column -->
        <div class="grid-column">
            <!-- Allergies Block -->
            <div class="dashboard-card allergy-card <?php echo !empty($patient['allergies']) ? 'has-allergies' : ''; ?>">
                <div class="card-header">
                    <h2><i class="fas fa-exclamation-triangle"></i> ข้อมูลการแพ้ยา</h2>
                    <span class="badge <?php echo !empty($patient['allergies']) ? 'badge-danger' : 'badge-success'; ?>">
                        <?php echo count($patient['allergies']); ?> รายการ
                    </span>
                </div>
                <div class="card-body">
                    <?php if (empty($patient['allergies'])): ?>
                        <div class="empty-state">
                            <i class="fas fa-check-circle"></i>
                            <p>ไม่พบประวัติการแพ้ยา</p>
                        </div>
                    <?php else: ?>
                        <ul class="allergy-list">
                            <?php foreach ($patient['allergies'] as $allergy): ?>
                                <li class="allergy-item severity-<?php echo strtolower($allergy['severity'] ?? 'unknown'); ?>">
                                    <div class="allergy-header">
                                        <span class="drug-name"><?php echo $allergy['drug_name']; ?></span>
                                        <span class="severity-label"><?php echo $allergy['severity_label'] ?? ($allergy['severity'] ?? 'ไม่ระบุ'); ?></span>
                                    </div>
                                    <div class="allergy-details">
                                        <span class="symptom"><?php echo $allergy['symptom'] ?? 'ไม่ระบุอาการ'; ?></span>
                                        <span class="date"><?php echo $allergy['datedetect'] ?? ''; ?></span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Chronic Diseases Block -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2><i class="fas fa-hospital-user"></i> โรคประจำตัว</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($patient['chronic_diseases'])): ?>
                        <div class="empty-state">
                            <p>ไม่พบประวัติโรคประจำตัว</p>
                        </div>
                    <?php else: ?>
                        <div class="chronic-grid">
                            <?php foreach ($patient['chronic_diseases'] as $chronic): ?>
                                <div class="chronic-item">
                                    <span class="chronic-code"><?php echo $chronic['icd10']; ?></span>
                                    <span class="chronic-name"><?php echo $chronic['chronicname']; ?></span>
                                    <span class="chronic-date"><?php echo $chronic['datediag']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Lab Results Block (Phase 4) -->
            <div class="dashboard-card lab-card">
                <div class="card-header">
                    <h2><i class="fas fa-flask"></i> ผลการตรวจทางห้องปฏิบัติการล่าสุด</h2>
                </div>
                <div class="card-body">
                    <div id="patient-lab-summary">
                        <div class="text-center p-3 text-muted">กำลังโหลดข้อมูล Lab...</div>
                    </div>
                </div>
            </div>

            <!-- Vital Signs Trends -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2><i class="fas fa-chart-line"></i> สัญญาณชีพ & BMI</h2>
                </div>
                <div class="card-body">
                    <canvas id="vitalsChart" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="grid-column">
            <!-- AI Clinical Insight (NEW) -->
            <div class="dashboard-card ai-insight-card" id="ai-insight-card">
                <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px 12px 0 0;">
                    <h2 style="color: white;"><i class="fas fa-brain"></i> AI Clinical Insight (Beta)</h2>
                    <span class="badge bg-light text-dark">CORE-AI</span>
                </div>
                <div class="card-body">
                    <div id="ai-insight-content" class="insight-loading">
                        <div class="text-center py-4">
                            <i class="fas fa-spinner fa-spin fa-2x text-primary mb-2"></i>
                            <p>Analyzing patient history & medications...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Current Medications -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2><i class="fas fa-pills"></i> รายการยาที่กินต่อเนื่อง (3 เดือนล่าสุด)</h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ชื่อยา</th>
                                    <th>จำนวน</th>
                                    <th>วิธีใช้</th>
                                    <th>วันที่จ่ายล่าสุด</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($patient['current_medications'] as $med): ?>
                                    <tr>
                                        <td><strong><?php echo $med['drugname']; ?></strong></td>
                                        <td><?php echo $med['qty']; ?> <?php echo $med['unit']; ?></td>
                                        <td><small><?php echo $med['usage']; ?></small></td>
                                        <td><?php echo $med['vstdate']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Patient Engagement & Adherence -->
            <div class="dashboard-card engagement-card">
                <div class="card-header">
                    <h2><i class="fas fa-hand-holding-medical"></i> Patient Engagement & Adherence</h2>
                    <div class="header-actions">
                        <button class="btn btn-sm btn-success" id="btn-send-remote-link" data-hn="<?php echo $hn; ?>">
                            <i class="fas fa-mobile-alt"></i> ส่งลิงก์ให้ผู้ป่วย
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="engagement-tools">
                        <div class="tool-section">
                            <label><strong>คำแนะนำการใช้ยา (ฉบับเข้าใจง่าย):</strong></label>
                            <div id="instruction-generator-area">
                                <select id="engagement-drug-select" class="form-control mb-2">
                                    <option value="">-- เลือกยาเพื่อสร้างคำแนะนำ --</option>
                                    <?php foreach ($patient['current_medications'] as $med): ?>
                                        <option value="<?php echo htmlspecialchars($med['drugname']); ?>" 
                                                data-usage="<?php echo htmlspecialchars($med['usage']); ?>">
                                            <?php echo htmlspecialchars($med['drugname']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <textarea id="easy-instruction-input" class="form-control mb-2" rows="3" placeholder="ข้อความที่จะส่งให้ผู้ป่วย..."></textarea>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-primary" id="btn-generate-instruction">
                                        <i class="fas fa-magic"></i> แปลงคำศัพท์อัตโนมัติ
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary" id="btn-save-instruction">
                                        <i class="fas fa-save"></i> บันทึกลงโปรไฟล์
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="tool-section mt-4">
                            <label><strong>สถิติการทานยา (Adherence):</strong></label>
                            <div id="adherence-chart-container" style="height: 150px;">
                                <div class="empty-state">
                                    <p class="small text-muted">ยังไม่มีข้อมูลการบันทึกจากผู้ป่วย</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Visits -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2><i class="fas fa-history"></i> ประวัติการเข้ารับบริการล่าสุด</h2>
                </div>
                <div class="card-body">
                    <div class="visit-timeline">
                        <?php foreach ($patient['recent_visits'] as $visit): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <div class="visit-header">
                                        <span class="visit-date"><?php echo $visit['vstdate']; ?></span>
                                        <span class="visit-time"><?php echo $visit['vsttime']; ?></span>
                                    </div>
                                    <div class="visit-vitals">
                                        BP: <strong><?php echo $visit['bp_systolic']; ?>/<?php echo $visit['bp_diastolic']; ?></strong> | 
                                        W: <strong><?php echo $visit['weight']; ?></strong> kg | 
                                        T: <strong><?php echo $visit['temp']; ?></strong> °C
                                    </div>
                                    <div class="visit-diagnosis">
                                        Dx: <strong><?php echo $visit['diagnosis_code']; ?></strong>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Vaccination History -->
            <div class="dashboard-card vaccine-card">
                <div class="card-header">
                    <h2><i class="fas fa-syringe"></i> ประวัติการได้รับวัคซีน</h2>
                </div>
                <div class="card-body">
                    <div id="patient-vaccine-list">
                        <div class="text-center p-3 text-muted">กำลังโหลดข้อมูลวัคซีน...</div>
                    </div>
                </div>
            </div>

            <!-- Screening History -->
            <div class="dashboard-card screening-card">
                <div class="card-header">
                    <h2><i class="fas fa-user-check"></i> ประวัติการคัดกรอง & สุขภาพ</h2>
                </div>
                <div class="card-body">
                    <div id="patient-screening-list">
                        <div class="text-center p-3 text-muted">กำลังโหลดข้อมูลการคัดกรอง...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Dashboard Styles */
.patient-intelligence-container {
    padding: 20px 0;
    font-family: 'Inter', sans-serif;
}

.glass-effect {
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.05);
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 30px;
    margin-bottom: 25px;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
}

.patient-info-primary {
    display: flex;
    align-items: center;
    gap: 20px;
}

.patient-avatar {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 32px;
    color: white;
    font-weight: bold;
    box-shadow: 0 10px 20px rgba(102, 126, 234, 0.2);
}

.patient-id-info h1 {
    margin: 0 0 10px 0;
    font-size: 28px;
    color: #2d3748;
}

.patient-meta {
    display: flex;
    gap: 20px;
    color: #718096;
    font-size: 14px;
}

.meta-item i {
    margin-right: 5px;
    color: #4a5568;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
}

.dashboard-card {
    background: white;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 25px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    border: 1px solid #edf2f7;
    transition: transform 0.3s ease;
}

.dashboard-card:hover {
    transform: translateY(-5px);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    border-bottom: 1px solid #f7fafc;
    padding-bottom: 10px;
}

.card-header h2 {
    font-size: 18px;
    margin: 0;
    color: #2d3748;
    display: flex;
    align-items: center;
    gap: 10px;
}

.card-header i {
    color: #4299e1;
}

/* Allergy Styles */
.allergy-card.has-allergies {
    border-left: 5px solid #f56565;
}

.allergy-list {
    list-style: none;
    padding: 0;
}

.allergy-item {
    background: #fff5f5;
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 10px;
}

.severity-severe { background: #fff5f5; border: 1px solid #feb2b2; }
.severity-moderate { background: #fffaf0; border: 1px solid #fbd38d; }
.severity-mild { background: #f7fafc; border: 1px solid #e2e8f0; }

.allergy-header {
    display: flex;
    justify-content: space-between;
    font-weight: 600;
    margin-bottom: 5px;
}

.drug-name { color: #c53030; }
.severity-label { font-size: 12px; padding: 2px 8px; border-radius: 10px; background: rgba(0,0,0,0.05); }

/* Chronic Grid */
.chronic-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.chronic-item {
    background: #ebf8ff;
    padding: 12px;
    border-radius: 12px;
    display: flex;
    flex-direction: column;
}

.chronic-code { font-weight: bold; color: #2b6cb0; font-size: 12px; }
.chronic-name { font-size: 14px; margin: 3px 0; }
.chronic-date { font-size: 11px; color: #718096; }

/* Timeline Styles */
.visit-timeline {
    position: relative;
    padding-left: 30px;
}

.visit-timeline::before {
    content: '';
    position: absolute;
    left: 10px;
    top: 5px;
    bottom: 5px;
    width: 2px;
    background: #e2e8f0;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -24px;
    top: 5px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #4299e1;
    border: 2px solid white;
    box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.2);
}

.timeline-content {
    background: #f8fafc;
    padding: 15px;
    border-radius: 12px;
}

.visit-header {
    display: flex;
    gap: 10px;
    font-weight: 600;
    font-size: 13px;
    margin-bottom: 5px;
}

.visit-vitals { font-size: 12px; color: #4a5568; }
.visit-diagnosis { margin-top: 5px; font-size: 13px; color: #2d3748; }

/* Responsive */
@media (max-width: 992px) {
    .dashboard-grid { grid-template-columns: 1fr; }
    .dashboard-header { flex-direction: column; gap: 20px; align-items: flex-start; }
}

/* AI Insight Card Styles */
.ai-insight-card {
    padding: 0 !important;
    border: 2px solid #667eea !important;
    overflow: hidden;
}
.ai-insight-card .card-body {
    padding: 20px;
}
.insight-summary {
    font-size: 15px;
    line-height: 1.6;
    margin-bottom: 20px;
    color: #4a5568;
    background: #f7fafc;
    padding: 15px;
    border-radius: 12px;
}
.insight-alert-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.insight-alert-item {
    display: flex;
    gap: 15px;
    padding: 15px;
    border-radius: 12px;
    border-left: 4px solid #ccc;
}
.insight-alert-item.danger { background: #fff5f5; border-color: #f56565; }
.insight-alert-item.warning { background: #fffaf0; border-color: #f6ad55; }
.insight-alert-item .alert-icon { font-size: 20px; margin-top: 2px; }
.insight-alert-item h4 { margin: 0 0 5px 0; font-size: 14px; font-weight: bold; }
.insight-alert-item p { margin: 0; font-size: 13px; color: #4a5568; }

.insight-recommendations {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px dashed #e2e8f0;
}
.insight-recommendations h4 { font-size: 14px; margin-bottom: 10px; color: #2d3748; }
.insight-recommendations ul { padding-left: 20px; margin: 0; }
.insight-recommendations li { font-size: 13px; color: #4a5568; margin-bottom: 5px; }

.score-badge {
    float: right;
    font-size: 11px;
    background: rgba(255,255,255,0.2);
    color: white;
    padding: 2px 8px;
    border-radius: 10px;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Lab Results Fetching (Phase 4)
    fetch('/api/safety/labs/<?php echo $hn; ?>')
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById('patient-lab-summary');
            if (data.success && data.labs.length > 0) {
                container.innerHTML = `
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless mb-0">
                            <thead>
                                <tr class="text-muted" style="font-size: 11px;">
                                    <th>หัวข้อ</th>
                                    <th>ผลตรวจ</th>
                                    <th>หน่วย</th>
                                    <th>วันที่</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.labs.map(lab => `
                                    <tr>
                                        <td><strong>${lab.lab_name}</strong></td>
                                        <td class="${lab.lab_name === 'eGFR' && lab.lab_value < 30 ? 'text-danger fw-bold' : ''}">${lab.lab_value}</td>
                                        <td><small>${lab.lab_unit}</small></td>
                                        <td><small>${lab.vstdate}</small></td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
            } else {
                container.innerHTML = '<div class="text-center p-3 text-muted">ไม่พบประวัติผล Lab ในระบบ</div>';
            }
        });

    // Vaccines Fetching
    fetch('/api/patient/<?php echo $hn; ?>/vaccines')
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById('patient-vaccine-list');
            if (data.success && data.vaccines.length > 0) {
                container.innerHTML = `
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>วันที่</th>
                                    <th>วัคซีน</th>
                                    <th>Lot.</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.vaccines.map(v => `
                                    <tr>
                                        <td><small>${v.vstdate}</small></td>
                                        <td><strong>${v.vaccine_name}</strong></td>
                                        <td><small>${v.lot_no || '-'}</small></td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
            } else {
                container.innerHTML = '<div class="text-center p-3 text-muted">ไม่พบประวัติวัคซีน</div>';
            }
        });

    // Screening Fetching
    fetch('/api/patient/<?php echo $hn; ?>/screening')
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById('patient-screening-list');
            if (data.success && data.screening.length > 0) {
                container.innerHTML = `
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>วันที่</th>
                                    <th>BMI</th>
                                    <th>BP</th>
                                    <th>FBS</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.screening.map(s => `
                                    <tr>
                                        <td><small>${s.vstdate}</small></td>
                                        <td>${s.bmi || '-'}</td>
                                        <td>${s.bp_systolic}/${s.bp_diastolic}</td>
                                        <td>${s.fbs || '-'}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
            } else {
                container.innerHTML = '<div class="text-center p-3 text-muted">ไม่พบประวัติการคัดกรอง</div>';
            }
        });

    // Vital Signs Chart
    const ctx = document.getElementById('vitalsChart').getContext('2d');
    
    // Fetch data via AJAX
    fetch('/api/patient/<?php echo $hn; ?>/vitals')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.vitals.length > 0) {
                const vitals = data.vitals.reverse(); // Chronological
                
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: vitals.map(v => v.vstdate),
                        datasets: [
                            {
                                label: 'Systolic BP',
                                data: vitals.map(v => v.bp_systolic),
                                borderColor: '#f56565',
                                tension: 0.3
                            },
                            {
                                label: 'Diastolic BP',
                                data: vitals.map(v => v.bp_diastolic),
                                borderColor: '#4299e1',
                                tension: 0.3
                            },
                            {
                                label: 'Weight (kg)',
                                data: vitals.map(v => v.weight),
                                borderColor: '#48bb78',
                                tension: 0.3,
                                hidden: true
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { position: 'bottom' }
                        },
                        scales: {
                            y: { beginAtZero: false }
                        }
                    }
                });
            }
        });

    // AI Insight Fetching (NEW)
    fetch('/api/patient/<?php echo $hn; ?>/ai-insight')
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById('ai-insight-content');
            if (data.success && data.insight) {
                const insight = data.insight;
                
                let alertsHtml = '';
                if (insight.alerts && insight.alerts.length > 0) {
                    alertsHtml = `
                        <div class="insight-alert-list">
                            ${insight.alerts.map(alert => `
                                <div class="insight-alert-item ${alert.type}">
                                    <div class="alert-icon">
                                        <i class="fas ${alert.type === 'danger' ? 'fa-radiation' : 'fa-exclamation-triangle'}"></i>
                                    </div>
                                    <div class="alert-text">
                                        <h4>${alert.title}</h4>
                                        <p>${alert.message}</p>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    `;
                }

                let recommendationsHtml = '';
                if (insight.recommendations && insight.recommendations.length > 0) {
                    recommendationsHtml = `
                        <div class="insight-recommendations">
                            <h4><i class="fas fa-clipboard-check text-success"></i> Recommendations:</h4>
                            <ul>
                                ${insight.recommendations.map(r => `<li>${r}</li>`).join('')}
                            </ul>
                        </div>
                    `;
                }

                container.innerHTML = `
                    <div class="insight-summary">
                        ${insight.summary.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')}
                    </div>
                    ${alertsHtml}
                    ${recommendationsHtml}
                `;
                
                // Add score badge to header
                const header = document.querySelector('#ai-insight-card .card-header');
                const badge = document.createElement('span');
                badge.className = 'score-badge';
                badge.textContent = 'Risk Score: ' + insight.score;
                header.appendChild(badge);

            } else {
                container.innerHTML = '<div class="text-center p-3 text-muted">Analysis unavailable for this profile.</div>';
            }
        });

    // --- PATIENT ENGAGEMENT TOOLS ---
    const drugSelect = document.getElementById('engagement-drug-select');
    const instructionInput = document.getElementById('easy-instruction-input');
    const btnGenerate = document.getElementById('btn-generate-instruction');
    const btnSave = document.getElementById('btn-save-instruction');
    const btnSendLink = document.getElementById('btn-send-remote-link');

    // Generate Easy Instruction
    btnGenerate.addEventListener('click', function() {
        const drugName = drugSelect.value;
        const usage = drugSelect.options[drugSelect.selectedIndex].dataset.usage;

        if (!drugName || !usage) return alert('กรุณาเลือกยาก่อนครับ');

        fetch('/api/engagement/generate-instruction', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ drug_name: drugName, raw_instruction: usage })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                instructionInput.value = data.instruction;
            }
        });
    });

    // Save Instruction
    btnSave.addEventListener('click', function() {
        const drugName = drugSelect.value;
        const instruction = instructionInput.value;
        if (!drugName || !instruction) return alert('กรุณาระบุข้อมูลให้ครบถ้วน');

        fetch('/api/engagement/save-instruction', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                hn: '<?php echo $hn; ?>',
                drug_id: 0, // Simplified for now
                drug_name: drugName,
                instruction: instruction
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('บันทึกคำแนะนำเรียบร้อยแล้ว');
            }
        });
    });

    // Send Mobile Link (Reminder)
    btnSendLink.addEventListener('click', function() {
        if (!confirm('ส่งลิงก์ Patient Portal ให้ผู้ป่วยทาง LINE?')) return;
        
        fetch('/api/engagement/send-portal-link', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ hn: '<?php echo $hn; ?>' })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('ส่ง Link ให้ผู้ป่วยเรียบร้อยแล้ว!');
            } else {
                alert('Failed: ' + data.message);
            }
        });
    });

    // Sync Action
    document.getElementById('sync-patient').addEventListener('click', function() {
        const hn = this.getAttribute('data-hn');
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังซิงค์...';
        
        fetch('/api/patient/' + hn + '/sync', { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-sync-alt"></i> ซิงค์ข้อมูลล่าสุด';
                }
            });
    });
});
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
