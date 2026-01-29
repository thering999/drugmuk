<?php include dirname(__DIR__) . '/layouts/header.php'; ?>

<div class="intelligence-container">
    <div class="intel-header glass-effect">
        <div class="header-left">
            <h1><i class="fas fa-brain"></i> Intelligence Dashboard</h1>
            <p>Predictive analytics, drug demand forecasting & JHCIS integration</p>
        </div>
        <div class="header-right">
            <button id="recalculate-risk" class="btn btn-primary">
                <i class="fas fa-microchip"></i> ประมวลผล Risk
            </button>
            <button id="auto-adjust-inventory" class="btn btn-success">
                <i class="fas fa-sync-alt"></i> ปรับจุดสั่งซื้อ
            </button>
            <a href="/admin/intelligence/export-pdf" class="btn btn-outline" target="_blank">
                <i class="fas fa-file-pdf"></i> Export
            </a>
            <span class="last-sync text-muted">อัพเดท: <span id="last-update">-</span></span>
        </div>
    </div>

    <!-- Extended Stats Row -->
    <div class="intel-stats-row">
        <div class="stat-card glass-effect purple" id="stat-critical">
            <div class="stat-icon"><i class="fas fa-radiation"></i></div>
            <div class="stat-data">
                <span class="value">0</span>
                <span class="label">Critical Risk</span>
            </div>
        </div>
        <div class="stat-card glass-effect red" id="stat-high">
            <div class="stat-icon"><i class="fas fa-exclamation-circle"></i></div>
            <div class="stat-data">
                <span class="value">0</span>
                <span class="label">High Risk</span>
            </div>
        </div>
        <div class="stat-card glass-effect blue" id="stat-polypharmacy">
            <div class="stat-icon"><i class="fas fa-pills"></i></div>
            <div class="stat-data">
                <span class="value">0</span>
                <span class="label">Polypharmacy (5+ ยา)</span>
            </div>
        </div>
        <div class="stat-card glass-effect orange" id="stat-shortages">
            <div class="stat-icon"><i class="fas fa-box-open"></i></div>
            <div class="stat-data">
                <span class="value">0</span>
                <span class="label">Predicted Shortages</span>
            </div>
        </div>
        <div class="stat-card glass-effect green" id="stat-inventory">
            <div class="stat-icon"><i class="fas fa-coins"></i></div>
            <div class="stat-data">
                <span class="value">฿0</span>
                <span class="label">Inventory Value</span>
            </div>
        </div>
        <div class="stat-card glass-effect cyan" id="stat-jhcis">
            <div class="stat-icon"><i class="fas fa-database"></i></div>
            <div class="stat-data">
                <span class="value">0</span>
                <span class="label">JHCIS Synced</span>
            </div>
        </div>
        <div class="stat-card glass-effect gold" id="stat-accuracy">
            <div class="stat-icon"><i class="fas fa-bullseye"></i></div>
            <div class="stat-data">
                <span class="value">85%</span>
                <span class="label">Forecast Accuracy</span>
            </div>
        </div>
        <div class="stat-card glass-effect pink" id="stat-allergy">
            <div class="stat-icon"><i class="fas fa-allergies"></i></div>
            <div class="stat-data">
                <span class="value">0</span>
                <span class="label">Allergy Alerts Today</span>
            </div>
        </div>
    </div>

    <div class="intel-grid">
        <!-- Predictive Shortages -->
        <div class="grid-item col-span-2">
            <div class="dashboard-card" style="margin-bottom: 25px;">
                <div class="card-header" style="background: #fffbef;">
                    <h2 style="color: #b7791f;"><i class="fas fa-hourglass-half"></i> AI Predictive Alert: Out-of-Stock Risk (7 Days)</h2>
                    <span class="badge bg-warning text-dark">PROACTIVE</span>
                </div>
                <div class="card-body p-0">
                    <div id="shortages-list" class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Drug</th>
                                    <th>Stock</th>
                                    <th>Avg. Usage/Day</th>
                                    <th>Days Left</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="shortages-tbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cost Trend Chart -->
        <div class="grid-item">
            <div class="dashboard-card h-100">
                <div class="card-header">
                    <h2><i class="fas fa-chart-line"></i> Cost & Revenue Trend (6 Months)</h2>
                </div>
                <div class="card-body">
                    <canvas id="costTrendChart" height="280"></canvas>
                </div>
            </div>
        </div>

        <!-- Forecast Accuracy -->
        <div class="grid-item">
            <div class="dashboard-card h-100">
                <div class="card-header">
                    <h2><i class="fas fa-chart-area"></i> Forecast Accuracy & Demand</h2>
                    <select id="drug-selector" class="form-control-sm">
                        <option value="1">Amoxicillin 500mg</option>
                        <option value="2">Paracetamol 500mg</option>
                        <option value="3">Metformin 500mg</option>
                    </select>
                </div>
                <div class="card-body">
                    <canvas id="forecastChart" height="280"></canvas>
                </div>
            </div>
        </div>

        <!-- High Risk Patients -->
        <div class="grid-item">
            <div class="dashboard-card h-100">
                <div class="card-header">
                    <h2><i class="fas fa-user-shield"></i> High Risk Patients</h2>
                    <button class="btn btn-sm btn-outline" onclick="sendAlertForRisk()">
                        <i class="fas fa-bell"></i> Alert
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>HN</th>
                                    <th>ผู้ป่วย</th>
                                    <th>Risk</th>
                                    <th>สาเหตุ</th>
                                </tr>
                            </thead>
                            <tbody id="risk-patients-list"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- JHCIS Summary (NEW) -->
        <div class="grid-item">
            <div class="dashboard-card h-100">
                <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <h2 style="color: white;"><i class="fas fa-hospital"></i> JHCIS Real-Time Summary</h2>
                    <span class="badge bg-light text-dark" id="jhcis-status">Checking...</span>
                </div>
                <div class="card-body">
                    <div id="jhcis-content">
                        <div class="loading-spinner">
                            <i class="fas fa-spinner fa-spin"></i> Loading JHCIS data...
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- High-Cost Medications -->
        <div class="grid-item">
            <div class="dashboard-card h-100">
                <div class="card-header">
                    <h2><i class="fas fa-money-bill-wave"></i> High-Cost Drivers (Top 10)</h2>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>เวชภัณฑ์</th>
                                    <th>มูลค่ารวม</th>
                                    <th>%</th>
                                </tr>
                            </thead>
                            <tbody id="high-cost-list"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- RDU Analysis -->
        <div class="grid-item">
            <div class="dashboard-card h-100">
                <div class="card-header">
                    <h2><i class="fas fa-pills"></i> Antibiotic Stewardship (RDU)</h2>
                </div>
                <div class="card-body">
                    <canvas id="rduChart" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Dynamic Seasonal Analysis -->
        <div class="grid-item col-span-2">
            <div class="dashboard-card">
                <div class="card-header">
                    <h2><i class="fas fa-snowflake"></i> Seasonal Usage Analysis (Real Data)</h2>
                </div>
                <div class="card-body">
                    <div class="seasonal-viz" id="seasonal-heatmap"></div>
                    <p class="mt-3 text-muted small"><i class="fas fa-info-circle"></i> สีเข้มหมายถึงยอดการใช้ยาสูงกว่าปกติ (Seasonal Spike)</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.intelligence-container { padding: 20px 0; font-family: 'Inter', sans-serif; color: #2d3748; }

.intel-header {
    display: flex; justify-content: space-between; align-items: center;
    padding: 25px 30px; margin-bottom: 25px;
    background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
    color: white; border-radius: 16px;
}
.intel-header h1 { margin: 0; font-size: 26px; }
.intel-header p { margin: 5px 0 0 0; opacity: 0.8; }
.header-right { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
.header-right .btn { padding: 8px 16px; border-radius: 8px; font-size: 13px; }
.btn-outline { background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3); }

.intel-stats-row {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 15px; margin-bottom: 25px;
}

.stat-card {
    padding: 20px; display: flex; align-items: center; gap: 15px;
    border-radius: 12px; transition: transform 0.2s;
}
.stat-card:hover { transform: translateY(-3px); }
.stat-icon { font-size: 28px; opacity: 0.8; }
.stat-data .value { display: block; font-size: 24px; font-weight: 800; }
.stat-data .label { font-size: 12px; opacity: 0.7; }

.purple { background: #fdf2ff; border-left: 4px solid #a855f7; color: #7e22ce; }
.red { background: #fff5f5; border-left: 4px solid #f56565; color: #c53030; }
.orange { background: #fffaf0; border-left: 4px solid #ed8936; color: #c05621; }
.green { background: #f0fff4; border-left: 4px solid #48bb78; color: #276749; }
.blue { background: #ebf8ff; border-left: 4px solid #4299e1; color: #2b6cb0; }
.cyan { background: #e6fffa; border-left: 4px solid #38b2ac; color: #234e52; }
.gold { background: #fffbeb; border-left: 4px solid #ecc94b; color: #744210; }
.pink { background: #fff5f7; border-left: 4px solid #ed64a6; color: #97266d; }

.intel-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.col-span-2 { grid-column: span 2; }

.dashboard-card {
    background: white; border-radius: 16px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #edf2f7; overflow: hidden;
}
.card-header {
    padding: 18px 22px; border-bottom: 1px solid #f7fafc;
    display: flex; justify-content: space-between; align-items: center;
}
.card-header h2 { font-size: 15px; margin: 0; font-weight: 700; color: #4a5568; }
.card-body { padding: 20px; }

.seasonal-viz { display: grid; grid-template-columns: repeat(12, 1fr); gap: 8px; }
.seasonal-month {
    padding: 15px 5px; text-align: center; border-radius: 8px;
    background: #f8fafc; font-size: 12px; font-weight: bold; color: #a0aec0;
}
.seasonal-month.active { background: #c6f6d5; color: #22543d; }
.seasonal-month.warning { background: #feebc8; color: #744210; }
.seasonal-month.danger { background: #fed7d7; color: #822727; border: 1px solid #f56565; }

.loading-spinner { text-align: center; padding: 40px; color: #718096; }

@media (max-width: 1200px) { .intel-grid { grid-template-columns: 1fr; } .col-span-2 { grid-column: span 1; } }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const monthNames = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

document.addEventListener('DOMContentLoaded', function() {
    loadDashboardStats();
    loadHighRiskPatients();
    loadHighCostMedications();
    loadRDUAnalysis();
    loadJHCISSummary();
    initForecastChart();

    document.getElementById('recalculate-risk').addEventListener('click', function() {
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        
        fetch('/api/intelligence/recalculate-risk', { method: 'POST' })
            .then(res => res.json())
            .then(data => {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-microchip"></i> ประมวลผล Risk';
                if (data.success) {
                    alert('ประมวลผลสำเร็จ: ' + data.updated_count + ' รายการ');
                    loadDashboardStats();
                    loadHighRiskPatients();
                }
            });
    });

    document.getElementById('auto-adjust-inventory').addEventListener('click', function() {
        if (!confirm('ยืนยันปรับจุดสั่งซื้อ (Min Stock) อัตโนมัติ?')) return;
        
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        
        fetch('/api/intelligence/auto-adjust-inventory', { method: 'POST' })
            .then(res => res.json())
            .then(data => {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-sync-alt"></i> ปรับจุดสั่งซื้อ';
                if (data.success) {
                    alert('ปรับปรุง ' + data.updated_count + ' รายการเรียบร้อย');
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
                
                const critical = data.risk_stats.find(s => s.risk_level === 'critical')?.count || 0;
                const high = data.risk_stats.find(s => s.risk_level === 'high')?.count || 0;
                
                document.querySelector('#stat-critical .value').textContent = critical;
                document.querySelector('#stat-high .value').textContent = high;
                document.querySelector('#stat-shortages .value').textContent = data.predictive_shortages?.length || 0;

                // Extended Stats
                if (data.extended) {
                    document.querySelector('#stat-polypharmacy .value').textContent = data.extended.polypharmacy_count || 0;
                    document.querySelector('#stat-inventory .value').textContent = '฿' + Number(data.extended.total_inventory_value || 0).toLocaleString();
                    document.querySelector('#stat-jhcis .value').textContent = data.extended.jhcis_patients_synced || 0;
                    document.querySelector('#stat-accuracy .value').textContent = (data.extended.forecast_accuracy || 85) + '%';
                    document.querySelector('#stat-allergy .value').textContent = data.extended.allergy_alerts_today || 0;

                    // Render Cost Trend Chart
                    if (data.extended.cost_trend) {
                        renderCostTrendChart(data.extended.cost_trend);
                    }

                    // Render Seasonal Heatmap
                    if (data.extended.seasonal_data) {
                        renderSeasonalHeatmap(data.extended.seasonal_data);
                    }
                }

                // Shortages table
                const shortagesTbody = document.getElementById('shortages-tbody');
                if (!data.predictive_shortages || data.predictive_shortages.length === 0) {
                    shortagesTbody.innerHTML = '<tr><td colspan="5" class="text-center p-4">✅ No predicted shortages</td></tr>';
                } else {
                    shortagesTbody.innerHTML = data.predictive_shortages.map(s => `
                        <tr>
                            <td><strong>${s.name}</strong><br><small class="text-muted">${s.code}</small></td>
                            <td>${parseFloat(s.current_stock).toLocaleString()}</td>
                            <td>${parseFloat(s.avg_daily_usage).toFixed(1)}</td>
                            <td><span class="badge ${s.days_remaining <= 2 ? 'bg-danger' : 'bg-warning text-dark'}">${parseFloat(s.days_remaining).toFixed(1)} วัน</span></td>
                            <td><a href="/orders/create?drug_id=${s.id}" class="btn btn-sm btn-primary"><i class="fas fa-shopping-cart"></i> สั่งซื้อ</a></td>
                        </tr>
                    `).join('');
                }
            }
        });
}

function renderCostTrendChart(trend) {
    const ctx = document.getElementById('costTrendChart');
    if (!ctx) return;
    
    if (window.costTrendChartInstance) window.costTrendChartInstance.destroy();
    
    window.costTrendChartInstance = new Chart(ctx.getContext('2d'), {
        type: 'line',
        data: {
            labels: trend.map(t => t.month),
            datasets: [
                { label: 'Cost', data: trend.map(t => t.total_cost), borderColor: '#f56565', backgroundColor: 'rgba(245,101,101,0.1)', fill: true, tension: 0.4 },
                { label: 'Revenue', data: trend.map(t => t.total_revenue), borderColor: '#48bb78', backgroundColor: 'rgba(72,187,120,0.1)', fill: true, tension: 0.4 }
            ]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } }
    });
}

function renderSeasonalHeatmap(seasonal) {
    const container = document.getElementById('seasonal-heatmap');
    container.innerHTML = Object.entries(seasonal).map(([m, d]) => `
        <div class="seasonal-month ${d.intensity}">
            ${monthNames[m-1]}<br>
            <small>${d.tx_count} rx</small>
        </div>
    `).join('');
}

function loadHighRiskPatients() {
    fetch('/api/intelligence/high-risk-patients')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const tbody = document.getElementById('risk-patients-list');
                if (data.patients.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center p-4">ไม่พบกลุ่มเสี่ยง</td></tr>';
                    return;
                }
                tbody.innerHTML = data.patients.slice(0, 5).map(p => `
                    <tr>
                        <td><strong>${p.hn}</strong></td>
                        <td>${p.full_name || '-'}</td>
                        <td><span class="badge ${p.risk_level === 'critical' ? 'bg-danger' : 'bg-warning'}">${(p.risk_level || '').toUpperCase()}</span></td>
                        <td><small>${p.polypharmacy_detected ? 'Polypharmacy' : 'Multiple Chronic'}</small></td>
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
                tbody.innerHTML = (data.analysis || []).slice(0, 5).map(item => `
                    <tr>
                        <td><small>${item.drug_name}</small></td>
                        <td><strong>${parseFloat(item.total_cost || 0).toLocaleString()}</strong></td>
                        <td>
                            <div class="progress" style="height: 5px;">
                                <div class="progress-bar bg-info" style="width: ${item.cost_percentage || 0}%"></div>
                            </div>
                            <small>${parseFloat(item.cost_percentage || 0).toFixed(1)}%</small>
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
                const ctx = document.getElementById('rduChart').getContext('2d');
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: data.analysis.map(a => a.drug_name),
                        datasets: [{ data: data.analysis.map(a => a.usage_percentage), backgroundColor: ['#4299e1', '#48bb78', '#f6ad55', '#f56565', '#9f7aea'] }]
                    },
                    options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } } }
                });
            }
        });
}

function loadJHCISSummary() {
    fetch('/api/intelligence/jhcis-summary')
        .then(res => res.json())
        .then(data => {
            const content = document.getElementById('jhcis-content');
            const status = document.getElementById('jhcis-status');
            
            if (data.success && data.summary.connected) {
                status.textContent = 'Connected';
                status.classList.remove('bg-light');
                status.classList.add('bg-success');
                
                const s = data.summary;
                content.innerHTML = `
                    <div class="jhcis-stats">
                        <div class="jhcis-stat"><span class="value">${s.patients_today || 0}</span><span class="label">Patients Today</span></div>
                        <div class="jhcis-stat"><span class="value">${s.dispensing_today || 0}</span><span class="label">Dispensing Today</span></div>
                    </div>
                    <hr>
                    <h4><i class="fas fa-stethoscope"></i> Top Diagnoses Today</h4>
                    <ul>${(s.top_diagnoses || []).map(d => `<li>${d.icd10} (${d.cnt})</li>`).join('') || '<li>No data</li>'}</ul>
                    <h4><i class="fas fa-capsules"></i> Top Drugs Today</h4>
                    <ul>${(s.top_drugs || []).map(d => `<li>${d.drugname} (${d.total_qty})</li>`).join('') || '<li>No data</li>'}</ul>
                `;
            } else {
                status.textContent = 'Not Connected';
                status.classList.add('bg-secondary');
                content.innerHTML = '<div class="text-center text-muted p-4"><i class="fas fa-unlink"></i><p>JHCIS ไม่ได้เชื่อมต่อ<br><small>ตั้งค่าได้ที่ config/jhcis_config.json</small></p></div>';
            }
        });
}

function initForecastChart() {
    const ctx = document.getElementById('forecastChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: monthNames.slice(0, 8).concat(['(Forecast)']),
            datasets: [
                { label: 'Actual', data: [520, 480, 610, 850, 590, 620, 680, null], borderColor: '#4299e1', backgroundColor: 'rgba(66,153,225,0.1)', fill: true, tension: 0.4 },
                { label: 'Forecast', data: [null, null, null, null, null, null, null, 750], borderColor: '#a855f7', borderDash: [5, 5], pointStyle: 'star', pointRadius: 10 }
            ]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });
}

function sendAlertForRisk() {
    if (!confirm('ส่งแจ้งเตือนกลุ่มเสี่ยงไป Discord/Telegram?')) return;
    
    fetch('/api/intelligence/send-alert', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'type=critical_risk&data=' + encodeURIComponent(JSON.stringify({hn: 'Multiple', score: 'High'}))
    })
    .then(res => res.json())
    .then(data => {
        alert(data.success ? 'Alert sent!' : 'ยังไม่ได้ตั้งค่า: ' + data.message);
    });
}
</script>

<style>
.jhcis-stats { display: flex; gap: 20px; margin-bottom: 15px; }
.jhcis-stat { text-align: center; flex: 1; padding: 15px; background: #f7fafc; border-radius: 10px; }
.jhcis-stat .value { display: block; font-size: 28px; font-weight: bold; color: #667eea; }
.jhcis-stat .label { font-size: 12px; color: #718096; }
#jhcis-content h4 { font-size: 14px; margin: 15px 0 10px; color: #4a5568; }
#jhcis-content ul { padding-left: 20px; margin: 0; font-size: 13px; }
</style>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
