<?php include dirname(__DIR__) . '/layouts/header.php'; ?>

<div class="intelligence-container">
    <div class="intel-header glass-effect">
        <div class="header-left">
            <h1><i class="fas fa-brain"></i> Intelligence Dashboard</h1>
            <p>Predictive analytics, drug demand forecasting & patient safety scoring</p>
        </div>
        <div class="header-right">
            <button id="recalculate-risk" class="btn btn-primary">
                <i class="fas fa-microchip"></i> ประมวลผล Risk Score ใหม่
            </button>
            <button id="auto-adjust-inventory" class="btn btn-success">
                <i class="fas fa-sync-alt"></i> ปรับจุดสั่งซื้ออัตโนมัติ
            </button>
            <span class="last-sync text-muted">อัพเดทล่าสุด: <span id="last-update">-</span></span>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="intel-stats-row">
        <div class="stat-card glass-effect purple" id="stat-critical">
            <div class="stat-icon"><i class="fas fa-radiation"></i></div>
            <div class="stat-data">
                <span class="value">0</span>
                <span class="label">ผู้ป่วยกลุ่มเสี่ยงสูงสุด (Critical)</span>
            </div>
        </div>
        <div class="stat-card glass-effect red" id="stat-high">
            <div class="stat-icon"><i class="fas fa-exclamation-circle"></i></div>
            <div class="stat-data">
                <span class="value">0</span>
                <span class="label">ผู้ป่วยกลุ่มเสี่ยงสูง (High Risk)</span>
            </div>
        </div>
        <div class="stat-card glass-effect orange" id="stat-clinical-alerts">
            <div class="stat-icon"><i class="fas fa-microscope"></i></div>
            <div class="stat-data">
                <span class="value">0</span>
                <span class="label">Clinical Safety Alerts (Global)</span>
            </div>
        </div>
        <div class="stat-card glass-effect orange" id="stat-shortages">
            <div class="stat-icon"><i class="fas fa-box-open"></i></div>
            <div class="stat-data">
                <span class="value">0</span>
                <span class="label">Predictive Shortages (7D)</span>
            </div>
        </div>
    </div>

    <div class="intel-grid">
        <!-- Bottom: Predictive Shortages -->
        <div class="grid-item col-span-2">
            <div class="dashboard-card" style="margin-bottom: 25px;">
                <div class="card-header" style="background: #fffbef;">
                    <h2 style="color: #b7791f;"><i class="fas fa-hourglass-half"></i> AI Predictive Alert: Out-of-Stock Risk (Next 7 Days)</h2>
                    <span class="badge bg-warning text-dark">PROACTIVE ALERT</span>
                </div>
                <div class="card-body p-0">
                    <div id="shortages-list" class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Drug</th>
                                    <th>Current Stock</th>
                                    <th>Avg. Usage (Daily)</th>
                                    <th>Est. Days Remaining</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="shortages-tbody">
                                <!-- Data from API -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Left: Forecast Trends -->
        <div class="grid-item">
            <div class="dashboard-card h-100">
                <div class="card-header">
                    <h2><i class="fas fa-chart-area"></i> Drug Demand Forecasting Trend (EMA Model)</h2>
                    <div class="header-actions">
                        <select id="drug-selector" class="form-control-sm">
                            <option value="1">Amoxicillin 500mg</option>
                            <option value="2">Paracetamol 500mg</option>
                            <option value="3">Metformin 500mg</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="forecastChart" height="280"></canvas>
                </div>
            </div>
        </div>

        <!-- Right: Risk Patient List -->
        <div class="grid-item">
            <div class="dashboard-card h-100">
                <div class="card-header">
                    <h2><i class="fas fa-user-shield"></i> Patients at High Risk (High Score)</h2>
                    <button class="btn btn-sm btn-outline">ดูทั้งหมด</button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>HN</th>
                                    <th>ผู้ป่วย</th>
                                    <th>ความเสี่ยง</th>
                                    <th>สาเหตุหลัก</th>
                                </tr>
                            </thead>
                            <tbody id="risk-patients-list">
                                <!-- Data from API -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- High-Cost Medications Analysis -->
        <div class="grid-item">
            <div class="dashboard-card h-100">
                <div class="card-header">
                    <h2><i class="fas fa-money-bill-wave"></i> High-Cost Cost Drivers (Top 10)</h2>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>เวชภัณฑ์</th>
                                    <th>มูลค่ารวม</th>
                                    <th>% ของคลัง</th>
                                </tr>
                            </thead>
                            <tbody id="high-cost-list">
                                <!-- Data from API -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Antibiotic Stewardship (RDU) -->
        <div class="grid-item">
            <div class="dashboard-card h-100">
                <div class="card-header">
                    <h2><i class="fas fa-pills"></i> Antibiotic Stewardship (RDU Indicators)</h2>
                </div>
                <div class="card-body">
                    <div id="rdu-chart-container">
                        <canvas id="rduChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom: Recent Clinical Alerts -->
        <div class="grid-item">
            <div class="dashboard-card h-100">
                <div class="card-header" style="background: #fff5f5;">
                    <h2 style="color: #c53030;"><i class="fas fa-biohazard"></i> Recent Clinical Safety Alerts</h2>
                </div>
                <div class="card-body p-0">
                    <div id="clinical-alerts-list" class="table-responsive">
                        <!-- Lab Alerts & DDI Feed -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom: Seasonal Analysis -->
        <div class="grid-item">
            <div class="dashboard-card">
                <div class="card-header">
                    <h2><i class="fas fa-snowflake"></i> Seasonal Usage Analysis & Pattern Detection</h2>
                </div>
                <div class="card-body">
                    <div class="seasonal-viz">
                        <!-- Simplified Heatmap/Visual -->
                        <div class="seasonal-month active">ม.ค.</div>
                        <div class="seasonal-month warning">ก.พ.</div>
                        <div class="seasonal-month">มี.ค.</div>
                        <div class="seasonal-month danger">เม.ย.</div>
                        <div class="seasonal-month">พ.ค.</div>
                        <div class="seasonal-month">มิ.ย.</div>
                        <div class="seasonal-month active">ก.ค.</div>
                        <div class="seasonal-month">ส.ค.</div>
                        <div class="seasonal-month warning">ก.ย.</div>
                        <div class="seasonal-month danger">ต.ค.</div>
                        <div class="seasonal-month active">พ.ย.</div>
                        <div class="seasonal-month active">ธ.ค.</div>
                    </div>
                    <p class="mt-3 text-muted small"><i class="fas fa-info-circle"></i> สีเข้มหมายถึงแนวโน้มการสั่งใช้ยาสูงกว่าปกติ (Seasonal Spike Detected)</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.intelligence-container {
    padding: 20px 0;
    font-family: 'Inter', sans-serif;
    color: #2d3748;
}

.intel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 30px;
    margin-bottom: 30px;
    background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
    color: white;
}

.intel-header h1 { margin: 0; font-size: 28px; }
.intel-header p { margin: 5px 0 0 0; opacity: 0.8; }

.intel-stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    padding: 25px;
    display: flex;
    align-items: center;
    gap: 20px;
}

.stat-icon {
    font-size: 32px;
    opacity: 0.8;
}

.stat-data .value {
    display: block;
    font-size: 28px;
    font-weight: 800;
}

.stat-data .label {
    font-size: 13px;
    opacity: 0.7;
}

/* Colors */
.purple { background: #fdf2ff; border-left: 5px solid #a855f7; color: #7e22ce; }
.red { background: #fff5f5; border-left: 5px solid #f56565; color: #c53030; }
.orange { background: #fffaf0; border-left: 5px solid #ed8936; color: #c05621; }
.green { background: #f0fff4; border-left: 5px solid #48bb78; color: #276749; }

.intel-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
}

.col-span-2 { grid-column: span 2; }

.dashboard-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.05);
    border: 1px solid #edf2f7;
    overflow: hidden;
}

.card-header {
    padding: 20px 25px;
    border-bottom: 1px solid #f7fafc;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header h2 { font-size: 16px; margin: 0; font-weight: 700; color: #4a5568; }

.card-body { padding: 25px; }

/* Seasonal Analysis Viz */
.seasonal-viz {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 10px;
    margin-top: 15px;
}

.seasonal-month {
    padding: 15px 5px;
    text-align: center;
    border-radius: 8px;
    background: #f8fafc;
    font-size: 12px;
    font-weight: bold;
    color: #a0aec0;
}

.seasonal-month.active { background: #c6f6d5; color: #22543d; }
.seasonal-month.warning { background: #feebc8; color: #744210; }
.seasonal-month.danger { background: #fed7d7; color: #822727; border: 1px solid #f56565; }

/* Responsive */
@media (max-width: 992px) {
    .intel-grid { grid-template-columns: 1fr; }
    .col-span-2 { grid-column: span 1; }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardStats();
    loadHighRiskPatients();
    loadHighCostMedications();
    loadRDUAnalysis();
    initForecastChart();

    document.getElementById('recalculate-risk').addEventListener('click', function() {
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        
        fetch('/api/intelligence/recalculate-risk', { method: 'POST' })
            .then(res => res.json())
            .then(data => {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-microchip"></i> ประมวลผล Risk Score ใหม่';
                if (data.success) {
                    alert('ประมวลผลสำเร็จ ข้อมูล ' + data.updated_count + ' รายการถูกอัพเดท');
                    loadDashboardStats();
                    loadHighRiskPatients();
                }
            });
    });

    document.getElementById('auto-adjust-inventory').addEventListener('click', function() {
        if (!confirm('ยืนยันระบบจะปรับจุดสั่งซื้อ (Min Stock) อัตโนมัติในฐานข้อมูลคลังยา?')) return;
        
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        
        fetch('/api/intelligence/auto-adjust-inventory', { method: 'POST' })
            .then(res => res.json())
            .then(data => {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-sync-alt"></i> ปรับจุดสั่งซื้ออัตโนมัติ';
                if (data.success) {
                    alert('ปรับปรุงข้อมูลคลังยา ' + data.updated_count + ' รายการเรียบร้อยแล้ว');
                } else {
                    alert('Error: ' + data.message);
                }
            });
    });
});

function loadDashboardStats() {
    fetch('/api/intelligence/dashboard-stats')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('last-update').textContent = data.timestamp;
                
                // Update stats
                const critical = data.risk_stats.find(s => s.risk_level === 'critical')?.count || 0;
                const high = data.risk_stats.find(s => s.risk_level === 'high')?.count || 0;
                
                document.querySelector('#stat-critical .value').textContent = critical;
                document.querySelector('#stat-high .value').textContent = high;
                document.querySelector('#stat-clinical-alerts .value').textContent = data.critical_labs_count;
                document.querySelector('#stat-shortages .value').textContent = data.predictive_shortages ? data.predictive_shortages.length : 0;

                // Load Predictive Shortages List
                const shortagesTbody = document.getElementById('shortages-tbody');
                if (!data.predictive_shortages || data.predictive_shortages.length === 0) {
                    shortagesTbody.innerHTML = '<tr><td colspan="5" class="text-center p-4">✅ No predicted shortages detected for the next 7 days</td></tr>';
                } else {
                    shortagesTbody.innerHTML = data.predictive_shortages.map(s => `
                        <tr>
                            <td>
                                <strong>${s.name}</strong><br>
                                <small class="text-muted">${s.code}</small>
                            </td>
                            <td>${parseFloat(s.current_stock).toLocaleString()}</td>
                            <td>${parseFloat(s.avg_daily_usage).toFixed(1)}</td>
                            <td>
                                <span class="badge ${s.days_remaining <= 2 ? 'bg-danger' : 'bg-warning text-dark'}">
                                    ${parseFloat(s.days_remaining).toFixed(1)} วัน
                                </span>
                            </td>
                            <td>
                                <a href="/orders/create?drug_id=${s.id}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-shopping-cart"></i> สั่งซื้อด่วน
                                </a>
                            </td>
                         </tr>
                    `).join('');
                }

                // Load Clinical Alerts List
                const alertList = document.getElementById('clinical-alerts-list');
                if (data.recent_interactions.length === 0) {
                    alertList.innerHTML = '<div class="p-4 text-center text-muted">ไม่พบความเสี่ยงภายรุนแรงในช่วงนี้</div>';
                } else {
                    alertList.innerHTML = `
                        <table class="table mb-0">
                            <tbody>
                                ${data.recent_interactions.map(i => `
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="bg-light-danger rounded p-2 me-3">
                                                    <i class="fas fa-exclamation-triangle text-danger"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-danger">${i.patient_name}</div>
                                                    <small class="text-muted">Drug: ${i.drug_name}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge bg-danger">Critical Risk</span>
                                            <div class="text-muted small">${new Date(i.dispense_date).toLocaleDateString()}</div>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    `;
                }
            }
        });
}

function loadHighRiskPatients() {
    fetch('/api/intelligence/high-risk-patients')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const tbody = document.getElementById('risk-patients-list');
                if (data.patients.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center p-4">ไม่พบข้อมูลกลุ่มเสี่ยงสูง</td></tr>';
                    return;
                }
                
                tbody.innerHTML = data.patients.slice(0, 5).map(p => `
                    <tr>
                        <td><strong>${p.hn}</strong></td>
                        <td>${p.full_name}</td>
                        <td><span class="badge ${p.risk_level === 'critical' ? 'bg-danger' : 'bg-warning'}">${p.risk_level.toUpperCase()}</span></td>
                        <td><small>${p.polypharmacy_detected ? 'Polypharmacy (5+ ยา)' : (p.chronic_conditions_count > 2 ? 'Multiple Chronic' : 'Frequent Visits')}</small></td>
                    </tr>
                `).join('');
            }
        });
}

function loadHighCostMedications() {
    fetch('/api/intelligence/high-cost-analysis')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const tbody = document.getElementById('high-cost-list');
                tbody.innerHTML = data.analysis.slice(0, 5).map(item => `
                    <tr>
                        <td><small>${item.drug_name}</small></td>
                        <td><strong>${parseFloat(item.total_cost).toLocaleString()}</strong></td>
                        <td>
                            <div class="progress" style="height: 5px;">
                                <div class="progress-bar bg-info" role="progressbar" style="width: ${item.cost_percentage}%"></div>
                            </div>
                            <small>${parseFloat(item.cost_percentage).toFixed(1)}%</small>
                        </td>
                    </tr>
                `).join('');
            }
        });
}

function loadRDUAnalysis() {
    fetch('/api/intelligence/rdu-analysis')
        .then(res => res.json())
        .then(data => {
            if (data.success && data.analysis.length > 0) {
                const drugs = data.analysis.map(a => a.drug_name);
                const percentages = data.analysis.map(a => a.usage_percentage);
                
                const ctx = document.getElementById('rduChart').getContext('2d');
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: drugs,
                        datasets: [{
                            data: percentages,
                            backgroundColor: ['#4299e1', '#48bb78', '#f6ad55', '#f56565', '#9f7aea']
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { position: 'bottom', labels: { boxWidth: 12, padding: 10 } }
                        }
                    }
                });
            }
        });
}

function initForecastChart() {
    const ctx = document.getElementById('forecastChart').getContext('2d');
    
    // Simulation data for now
    const labels = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค. (Forecast)'];
    const historical = [520, 480, 610, 850, 590, 620, 680, null];
    const forecast = [null, null, null, null, null, null, 680, 750];

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Actual Usage',
                    data: historical,
                    borderColor: '#4299e1',
                    backgroundColor: 'rgba(66, 153, 225, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Intelligence Forecast (EMA)',
                    data: forecast,
                    borderColor: '#a855f7',
                    borderDash: [5, 5],
                    borderWidth: 3,
                    tension: 0.4,
                    pointStyle: 'star',
                    pointRadius: 8
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { borderDash: [2, 2] }
                }
            }
        }
    });
}
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
