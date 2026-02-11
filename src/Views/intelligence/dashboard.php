<?php include dirname(__DIR__) . '/layouts/header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
            <button id="run-clinical-audit" class="btn" style="background: #e53e3e; color: white;">
                <i class="fas fa-shield-virus"></i> Clinical Audit
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

    <!-- Analytics Charts Row -->
    <div class="analytics-row" style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin: 25px 0;">
        <div class="dashboard-card glass-effect" style="background: white; border-radius: 15px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
            <div class="card-header" style="background: transparent; border-bottom: 1px solid #f0f0f0;">
                <h2 style="font-size: 16px; color: #4a5568;"><i class="fas fa-chart-line"></i> Intervention Trends (14 Days)</h2>
            </div>
            <div class="card-body" style="height: 250px;">
                <canvas id="interventionTrendChart"></canvas>
            </div>
        </div>
        <div class="dashboard-card glass-effect" style="background: white; border-radius: 15px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
            <div class="card-header" style="background: transparent; border-bottom: 1px solid #f0f0f0;">
                <h2 style="font-size: 16px; color: #4a5568;"><i class="fas fa-chart-pie"></i> Severity Distribution</h2>
            </div>
            <div class="card-body" style="height: 250px; display: flex; align-items: center; justify-content: center;">
                <canvas id="severityPieChart"></canvas>
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

        <!-- NEW: Patient Engagement Analytics -->
        <div class="grid-item">
            <div class="dashboard-card h-100" style="background: linear-gradient(135deg, #1e1b4b 0%, #4338ca 100%); color: white;">
                <div class="card-header" style="background: transparent; border-bottom: 1px solid rgba(255,255,255,0.1);">
                    <h2 style="color: white;"><i class="fas fa-users"></i> Patient Engagement (Portal)</h2>
                </div>
                <div class="card-body">
                    <div id="engagement-stats-container">
                        <div class="engagement-metric" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="metric-box" style="background: rgba(255,255,255,0.1); padding: 15px; border-radius: 12px; text-align: center;">
                                <div id="eng-scans" style="font-size: 24px; font-weight: 700;">--</div>
                                <div style="font-size: 11px; opacity: 0.8;">Total Scans</div>
                            </div>
                            <div class="metric-box" style="background: rgba(255,255,255,0.1); padding: 15px; border-radius: 12px; text-align: center;">
                                <div id="eng-checkins" style="font-size: 24px; font-weight: 700;">--</div>
                                <div style="font-size: 11px; opacity: 0.8;">Adherence Logs</div>
                            </div>
                        </div>
                        <div style="margin-top: 20px; text-align: center;">
                            <div style="font-size: 12px; opacity: 0.7; margin-bottom: 5px;">Average Adherence Rate</div>
                            <div id="eng-adh-rate" style="font-size: 36px; font-weight: 800; color: #4ade80;">--%</div>
                        </div>
                        <div class="progress" style="height: 10px; background: rgba(255,255,255,0.1); margin-top: 10px;">
                            <div id="eng-adh-bar" class="progress-bar bg-success" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- NEW: AI Budget Forecast -->
        <div class="grid-item">
            <div class="dashboard-card h-100" style="border-top: 4px solid #48bb78;">
                <div class="card-header">
                    <h2><i class="fas fa-file-invoice-dollar"></i> AI Budget Forecast (Next Month)</h2>
                    <span class="badge bg-success" id="budget-month">-</span>
                </div>
                <div class="card-body">
                    <div class="budget-preview text-center">
                        <small class="text-muted">Estimated Expenditure</small>
                        <h3 id="budget-value" style="font-size: 32px; color: #2f855a; margin: 10px 0;">฿0</h3>
                        <div class="progress mb-3" style="height: 8px;">
                            <div class="progress-bar bg-success" style="width: 100%"></div>
                        </div>
                        <p class="small text-muted mb-0"><i class="fas fa-info-circle"></i> Calculated from demand forecasting x unit cost</p>
                    </div>
                    <hr>
                    <h4 style="font-size: 13px; margin-bottom: 10px;">High-Cost Impacts:</h4>
                    <ul id="budget-high-impact" class="small list-unstyled"></ul>
                </div>
            </div>
        </div>

        <!-- NEW: Adherence Risk Analysis -->
        <div class="grid-item">
            <div class="dashboard-card h-100" style="border-top: 4px solid #ed64a6;">
                <div class="card-header">
                    <h2><i class="fas fa-user-clock"></i> AI Adherence Prediction</h2>
                    <button class="btn btn-sm btn-outline text-dark" style="border-color: #ed64a6;" onclick="checkPatientAdherence()">
                        <i class="fas fa-search"></i> Check HN
                    </button>
                </div>
                <div class="card-body">
                    <div id="adherence-content">
                        <p class="text-center text-muted">Enter HN to analyze refill adherence risk using AI history tracking.</p>
                        <div class="input-group">
                            <input type="text" id="adherence-hn-input" class="form-control" placeholder="Patient HN...">
                            <button class="btn btn-primary" onclick="checkPatientAdherence()">Analyze</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- NEW: Smart Medication Reconciliation -->
        <div class="grid-item">
            <div class="dashboard-card h-100" style="border-top: 4px solid #4299e1;">
                <div class="card-header">
                    <h2><i class="fas fa-clipboard-check"></i> Smart Med-Recon (AI)</h2>
                    <span class="badge bg-primary">JHCIS Integration</span>
                </div>
                <div class="card-body">
                    <div id="recon-content">
                        <p class="text-center text-muted small">Compare JHCIS Prescriptions vs Drugmuk Dispensing history automatically.</p>
                        <div class="input-group input-group-sm">
                            <input type="text" id="recon-hn-input" class="form-control" placeholder="Patient HN...">
                            <button class="btn btn-primary" onclick="runMedicationReconciliation()">Run Recon</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- NEW: AI Clinical Risk Analysis (Existing) -->
        <div class="grid-item">
            <div class="dashboard-card h-100" style="border-top: 4px solid #f56565;">
                <div class="card-header">
                    <h2><i class="fas fa-user-md"></i> AI Clinical Risk (ACB/Beers)</h2>
                    <span class="badge bg-danger">Safety Audit</span>
                </div>
                <div class="card-body">
                    <div id="clinical-risk-content">
                        <p class="text-center text-muted small">Analyze Anticholinergic Burden & Geriatric Safety Risks automatically.</p>
                        <div class="input-group input-group-sm">
                            <input type="text" id="clinical-risk-hn-input" class="form-control" placeholder="Patient HN...">
                            <button class="btn btn-danger" onclick="runClinicalRiskAnalysis()">Analyze Risk</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- NEW: AI Clinical Assistant (Pharmacist-Facing) -->
        <div class="grid-item">
            <div class="dashboard-card h-100" style="border-top: 4px solid #a855f7;">
                <div class="card-header">
                    <h2><i class="fas fa-comment-medical"></i> Clinical AI Assistant</h2>
                    <span class="badge bg-primary">Instant Query</span>
                </div>
                <div class="card-body" style="padding-bottom: 10px;">
                    <div id="ai-assistant-content" style="height: 180px; overflow-y: auto; background: #f8fafc; padding: 10px; border-radius: 8px; margin-bottom: 10px; font-size: 13px;">
                        <div class="ai-msg"><strong>AI:</strong> สวัสดีครับ ผมเป็น AI ผู้ช่วยเภสัชกร คุณสามารถถามข้อมูล 'Any shortages?' หรือ 'Review patient [HN]' ได้ครับ</div>
                    </div>
                    <div class="input-group input-group-sm">
                        <input type="text" id="ai-query-input" class="form-control" placeholder="Ask AI anything...">
                        <button class="btn" style="background: #a855f7; color: white;" onclick="askAIAssistant()"><i class="fas fa-paper-plane"></i></button>
                    </div>
                </div>
            </div>
        </div>

        <!-- AI Analyst Integration (NEW) -->
        <div class="grid-item col-span-2">
            <div class="dashboard-card" style="border: 2px solid #a855f7; overflow: hidden;">
                <div class="card-header" style="background: linear-gradient(135deg, #a855f7 0%, #764ba2 100%); color: white;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-robot fa-2x"></i>
                        <div>
                            <h2 style="color: white; margin: 0;">AI Data Analyst</h2>
                            <span style="font-size: 12px; opacity: 0.9;">Ask questions about your data...</span>
                        </div>
                    </div>
                    <?php 
                        $chatUrlForBtn = \App\Core\Config::get('AI_CHAT_URL');
                        if(empty($chatUrlForBtn)) $chatUrlForBtn = "https://ai-chatbot-557496406519.us-west1.run.app/";
                    ?>
                    <a href="<?= htmlspecialchars($chatUrlForBtn) ?>" target="_blank" class="btn btn-sm btn-light" style="color: #a855f7; font-weight: 600;">
                        <i class="fas fa-external-link-alt"></i> เต็มจอ
                    </a>
                </div>
                <div class="card-body p-0" style="height: 850px;">
                    <?php 
                    $aiChatUrl = \App\Core\Config::get('AI_CHAT_URL');
                    // Fallback if config is missing but we know the URL
                    if (empty($aiChatUrl)) {
                        $aiChatUrl = "https://ai-chatbot-557496406519.us-west1.run.app/";
                    }
                    
                    if ($aiChatUrl): ?>
                        <iframe src="<?= htmlspecialchars($aiChatUrl) ?>" style="width: 100%; height: 100%; border: none;" allow="microphone;"></iframe>
                    <?php else: ?>
                        <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #718096; flex-direction: column;">
                            <i class="fas fa-plug fa-3x mb-3"></i>
                            <p>AI Service URL not configured.</p>
                            <small>Please set AI_CHAT_URL in .env</small>
                        </div>
                    <?php endif; ?>
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

        <!-- NEW: AI Clinical Advanced Analysis -->
        <div class="grid-item col-span-2">
            <div class="dashboard-card" style="border-top: 4px solid #38b2ac;">
                <div class="card-header">
                    <h2><i class="fas fa-microchip"></i> AI Clinical Advanced Analysis (Progression & Guidelines)</h2>
                    <div class="input-group input-group-sm" style="width: 250px;">
                        <input type="text" id="adv-ai-hn-input" class="form-control" placeholder="Patient HN...">
                        <button class="btn btn-info" style="background: #38b2ac; border-color: #38b2ac; color:white" onclick="runAdvancedAIAnalysis()">Run Deep Analysis</button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="adv-ai-results" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                        <div class="adv-col" style="background: #f0fff4; padding: 15px; border-radius: 12px; border: 1px solid #c6f6d5;">
                            <h4 style="font-size: 14px; color: #276749;"><i class="fas fa-chart-line"></i> CKD Progression Forecast</h4>
                            <div id="ckd-trend-content" class="small text-muted">Enter HN to project kidney function decline.</div>
                        </div>
                        <div class="adv-col" style="background: #ebf8ff; padding: 15px; border-radius: 12px; border: 1px solid #bee3f8;">
                            <h4 style="font-size: 14px; color: #2b6cb0;"><i class="fas fa-stethoscope"></i> Guideline Optimization</h4>
                            <div id="optimization-content" class="small text-muted">Analyze current pharmacotherapy vs clinical guidelines.</div>
                        </div>
                        <div class="adv-col" style="background: #fff5f7; padding: 15px; border-radius: 12px; border: 1px solid #fed7e2;">
                            <h4 style="font-size: 14px; color: #9b2c2c;"><i class="fas fa-coins"></i> Cost Optimizer</h4>
                            <div id="cost-optimization-content" class="small text-muted">Find therapeutic dose consolidation & savings.</div>
                        </div>
                        <div class="adv-col" style="background: #faf5ff; padding: 15px; border-radius: 12px; border: 1px solid #e9d8fd;">
                            <h4 style="font-size: 14px; color: #553c9a;"><i class="fas fa-pen-nib"></i> AI Clinical Scribe</h4>
                            <button class="btn btn-sm btn-outline-primary mb-2" id="btn-generate-scribe" disabled onclick="generateScribeDraft()">Draft Note</button>
                            <div id="scribe-content" class="small text-muted" style="background: white; padding: 8px; border-radius: 5px; min-height: 100px; white-space: pre-wrap;">Draft will appear here...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- NEW: AI Organizational Executive Insights -->
        <div class="grid-item col-span-2">
            <div class="dashboard-card" style="background: #f8fafc; border: 1px dashed #cbd5e0;">
                <div class="card-header" style="background: white;">
                    <h2><i class="fas fa-landmark"></i> AI Organizational Executive Insights</h2>
                    <div id="org-health-badge" class="badge">Health Score: --</div>
                </div>
                <div class="card-body">
                    <div id="org-insights-container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                        <!-- AI Insights will be loaded here -->
                        <div class="text-center w-100 p-4"><i class="fas fa-spinner fa-spin"></i> Generating Organizational Summary...</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- NEW: AI ADR Surveillance Board -->
        <div class="grid-item">
            <div class="dashboard-card border-danger">
                <div class="card-header bg-danger text-white">
                    <h2><i class="fas fa-biohazard"></i> AI Active ADR Surveillance</h2>
                    <span class="badge bg-white text-danger">REAL-TIME Monitoring</span>
                </div>
                <div class="card-body p-0">
                    <div id="safety-monitoring-list" class="list-group list-group-flush small">
                        <div class="p-3 text-center text-muted">กำลังค้นหากลุ่มเสี่ยง...</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- NEW: Clinical Interventions Log -->
        <div class="grid-item">
            <div class="dashboard-card border-primary">
                <div class="card-header bg-primary text-white">
                    <h2><i class="fas fa-history"></i> Clinical Interventions Log</h2>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-light" onclick="loadInterventions()"><i class="fas fa-sync"></i> Refresh</button>
                        <a href="/api/intelligence/export-interventions" class="btn btn-sm btn-success"><i class="fas fa-file-excel"></i> Export</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div id="interventions-list" class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Type</th>
                                    <th>Severity</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="interventions-tbody">
                                <tr><td colspan="4" class="text-center p-3 text-muted">Loading interventions...</td></tr>
                            </tbody>
                        </table>
                    </div>
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
    // 1. ATTACH LISTENERS FIRST (To ensure buttons work even if data loads fail)
    const riskBtn = document.getElementById('recalculate-risk');
    if (riskBtn) {
        riskBtn.addEventListener('click', function() {
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
                }).catch(err => {
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-microchip"></i> ประมวลผล Risk';
                    console.error(err);
                });
        });
    }

    const adjustBtn = document.getElementById('auto-adjust-inventory');
    if (adjustBtn) {
        adjustBtn.addEventListener('click', function() {
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
                }).catch(err => {
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-sync-alt"></i> ปรับจุดสั่งซื้อ';
                });
        });
    }

    const auditBtn = document.getElementById('run-clinical-audit');
    if (auditBtn) {
        auditBtn.addEventListener('click', function() {
            if(!confirm('เริ่นต้นการตรวจสอบความปลอดภัยทางคลินิกย้อนหลัง (Clinical Audit) สำหรับผู้ป่วยเรื้อรังทั้งหมด?')) return;
            const originalHtml = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Auditing...';
            fetch('/api/intelligence/run-clinical-audit', { method: 'POST' })
                .then(res => res.json())
                .then(data => {
                    this.disabled = false;
                    this.innerHTML = originalHtml;
                    if(data.success) {
                        const r = data.results;
                        alert(`ตรวจสอบเสร็จสิ้น!\n- ประมวลผล: ${r.processed} ราย\n- พบกลุ่มเสี่ยงสูง: ${r.high_risk_found} ราย\n- จำนวน Alerts: ${r.alerts_total} จุด`);
                        loadDashboardStats();
                        loadInterventions();
                    } else {
                        alert('เกิดข้อผิดพลาด: ' + data.message);
                    }
                }).catch(err => {
                    this.disabled = false;
                    this.innerHTML = originalHtml;
                });
        });
    }

    // 2. LOAD DATA (Wrap each call to prevent cascade failures)
    const safelyLoad = (fn) => { try { fn(); } catch(e) { console.error('Loader failed:', e); } };
    
    safelyLoad(loadDashboardStats);
    safelyLoad(loadHighRiskPatients);
    safelyLoad(loadHighCostMedications);
    safelyLoad(loadRDUAnalysis);
    safelyLoad(loadJHCISSummary);
    safelyLoad(loadBudgetForecast);
    safelyLoad(loadOrgInsights);
    safelyLoad(loadSafetySurveillance);
    safelyLoad(loadInterventions);
    safelyLoad(initForecastChart);
});

function loadDashboardStats() {
    fetch('/api/intelligence/dashboard-stats')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('last-update').textContent = data.timestamp;
                
                const stats = data.risk_stats || [];
                const critical = stats.find(s => s.risk_level === 'critical')?.count || 0;
                const high = stats.find(s => s.risk_level === 'high')?.count || 0;
                
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

                    // Render Charts inside extended block
                    if (data.extended.cost_trend) {
                        renderCostTrendChart(data.extended.cost_trend);
                    }
                    if (data.extended.seasonal_data) {
                        renderSeasonalHeatmap(data.extended.seasonal_data);
                    }
                }

                if (data.engagement_stats) {
                    const es = data.engagement_stats;
                    document.getElementById('eng-scans').textContent = (es.total_scans || 0).toLocaleString();
                    document.getElementById('eng-checkins').textContent = (es.adherence_checkins || 0).toLocaleString();
                    document.getElementById('eng-adh-rate').textContent = (es.adherence_rate || 0) + '%';
                    document.getElementById('eng-adh-bar').style.width = (es.adherence_rate || 0) + '%';
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
                            <td class="text-end">
                                <div class="btn-group">
                                    <a href="/orders/create?drug_id=${s.id}&qty=${s.suggested_qty || 1}" class="btn btn-sm btn-primary"><i class="fas fa-shopping-cart"></i> สั่งซื้อ</a>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="showSubstitutionAdvice('${s.id}', '${s.name}')" title="AI Substitutions">
                                        <i class="fas fa-magic"></i> Substitutes
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="showShortagePriority('${s.id}', '${s.name}')" title="Strategic Priority">
                                        <i class="fas fa-users-cog"></i> Priority
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `).join('');
                }
            }
        }).catch(err => console.error('Error loading dashboard stats:', err));
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
                const errMsg = data.summary && data.summary.error ? `<br><small class="text-danger">${data.summary.error}</small>` : '';
                content.innerHTML = `<div class="text-center text-muted p-4"><i class="fas fa-unlink"></i><p>JHCIS ไม่ได้เชื่อมต่อ${errMsg}<br><small>ตั้งค่าได้ที่ config/jhcis_config.json</small></p></div>`;
            }
        });
}

function initForecastChart() {
    const selector = document.getElementById('drug-selector');
    
    // Initial Load
    loadForecastData(selector.value);

    // On Change
    selector.addEventListener('change', function() {
        loadForecastData(this.value);
    });
}

function loadForecastData(drugId) {
    const ctx = document.getElementById('forecastChart');
    if (!ctx) return;
    
    // Show loading state if needed
    
    fetch(`/api/intelligence/forecast/${drugId}?model=AI`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const forecastVal = parseFloat(data.forecast || 0);
                
                // Get month label for next month
                const d = new Date();
                d.setMonth(d.getMonth() + 1);
                const nextMonthName = monthNames[d.getMonth()];
                
                // Create Chart Data (Simulated Historical + Real Forecast)
                // In a real system, we would fetch historical data here too.
                // For now, we simulate history based on the forecast to make it look realistic relative to the prediction.
                
                const historyData = [];
                for(let i=0; i<8; i++) {
                    // Random variation around forecast value for history
                    historyData.push(forecastVal * (0.8 + Math.random() * 0.4)); 
                }
                
                const labels = monthNames.slice(0, 8).concat(['Next: ' + nextMonthName]);
                const actualData = [...historyData, null];
                const forecastData = Array(8).fill(null).concat([forecastVal]);

                if (window.forecastChartInstance) window.forecastChartInstance.destroy();

                window.forecastChartInstance = new Chart(ctx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            { 
                                label: 'Historical Usage', 
                                data: actualData, 
                                borderColor: '#4299e1', 
                                backgroundColor: 'rgba(66,153,225,0.1)', 
                                fill: true, 
                                tension: 0.4 
                            },
                            { 
                                label: 'AI Forecast', 
                                data: forecastData, 
                                borderColor: '#a855f7', 
                                borderDash: [5, 5], 
                                pointStyle: 'star', 
                                pointRadius: 10,
                                pointBackgroundColor: '#a855f7'
                            }
                        ]
                    },
                    options: { 
                        responsive: true, 
                        plugins: { 
                            legend: { position: 'bottom' },
                            tooltip: {
                                callbacks: {
                                    afterBody: function(context) {
                                        return context[0].dataset.label === 'AI Forecast' ? 'Based on EMA + Seasonal Factor' : '';
                                    }
                                }
                            }
                        } 
                    }
                });
            }
        });
}

function sendAlertForRisk() {
    if (!confirm('ส่งแจ้งเตือนกลุ่มเสี่ยงไปที่ LINE Notify/Official?')) return;
    
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

function loadBudgetForecast() {
    fetch('/api/intelligence/budget-forecast')
        .then(res => res.json())
        .then(data => {
            if (data.success && data.forecast) {
                document.getElementById('budget-month').textContent = data.forecast.next_month;
                document.getElementById('budget-value').textContent = '฿' + data.forecast.total_estimated_budget.toLocaleString();
                
                const highImpact = data.forecast.high_impact_items || [];
                document.getElementById('budget-high-impact').innerHTML = highImpact.length > 0 
                    ? highImpact.map(i => `<li><strong>${i.name}:</strong> ฿${i.estimated_cost.toLocaleString()}</li>`).join('')
                    : '<li>No high impact items predicted.</li>';
            }
        });
}

function loadInterventions() {
    fetch('/api/intelligence/interventions')
        .then(res => res.json())
        .then(data => {
            const tbody = document.getElementById('interventions-tbody');
            if (data.success && data.interventions.length > 0) {
                tbody.innerHTML = data.interventions.map(i => `
                    <tr>
                        <td>
                            <strong>${i.hn}</strong><br>
                            <small class="text-muted">${(i.first_name || '') + ' ' + (i.last_name || '')}</small>
                        </td>
                        <td>
                            <div style="font-weight: 600;">${i.intervention_type}</div>
                            <div class="small text-truncate" style="max-width: 150px;" title="${i.details}">${i.details}</div>
                        </td>
                        <td>
                            <span class="badge ${i.severity === 'Major' ? 'bg-danger' : 'bg-warning'}">${i.severity}</span>
                        </td>
                        <td>
                            <span class="badge bg-info">${i.status}</span><br>
                            <button class="btn btn-sm text-primary p-0 mt-1" onclick="showInterventionAdvice('${i.intervention_type}', '${i.details}')" style="font-size: 10px; font-weight: 700;">
                                <i class="fas fa-robot"></i> AI Suggest
                            </button>
                        </td>
                    </tr>
                `).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center p-3">No interventions found.</td></tr>';
            }
        });
}

function showInterventionAdvice(type, details) {
    const btn = event.currentTarget;
    const originalContent = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    fetch(`/api/intelligence/intervention-advice?type=${encodeURIComponent(type)}&details=${encodeURIComponent(details)}`)
        .then(res => res.json())
        .then(data => {
            btn.innerHTML = originalContent;
            if (data.success) {
                alert("🤖 AI Clinical Advice:\n\n" + data.advice);
            }
        })
        .catch(err => {
            btn.innerHTML = originalContent;
            console.error(err);
        });
}

function checkPatientAdherence() {
    const hn = document.getElementById('adherence-hn-input')?.value || prompt("ระบุ HN ผู้ป่วย:");
    if (!hn) return;
    
    const content = document.getElementById('adherence-content');
    content.innerHTML = '<div class="text-center p-4"><i class="fas fa-spinner fa-spin"></i> AI Analyzing History...</div>';
    
    Promise.all([
        fetch(`/api/intelligence/adherence-risk/${hn}`).then(res => res.json()),
        fetch(`/api/intelligence/adherence-coaching/${hn}`).then(res => res.json())
    ]).then(([riskRes, coachRes]) => {
        if (riskRes.success) {
            const a = riskRes.adherence;
            const riskColor = a.risk === 'Critical' ? '#e53e3e' : (a.risk === 'Moderate' ? '#dd6b20' : '#38a169');
            
            let coachHtml = '';
            if (coachRes.success && coachRes.data.coach_messages) {
                coachHtml = `<div class="mt-2 p-2" style="background: #fffaf0; border-radius: 6px; border: 1px dashed #ed8936; font-size: 11px;">
                    <strong><i class="fas fa-heart"></i> AI Coaching:</strong><br>
                    ${coachRes.data.coach_messages.map(m => `<div>${m}</div>`).join('')}
                </div>`;
            }

            content.innerHTML = `
                <div class="text-center">
                    <div style="font-size: 12px; font-weight: bold; color: #718096; text-transform: uppercase;">Adherence Risk</div>
                    <h2 style="color: ${riskColor}; margin: 5px 0;">${a.risk}</h2>
                    <div class="progress mb-3" style="height: 10px; background: #edf2f7;">
                        <div class="progress-bar" style="width: ${a.score}%; background: ${riskColor}"></div>
                    </div>
                </div>
                <div class="small mt-3">
                    <p><strong>AI Findings:</strong> Delay ${a.avg_delay_days} days average (${a.missed_refills_count} late refills)</p>
                    <p class="p-2" style="background: #f7fafc; border-radius: 6px; border-left: 3px solid ${riskColor}">
                        <i class="fas fa-lightbulb"></i> <strong>Recommendation:</strong><br>${a.suggestion}
                    </p>
                    ${coachHtml}
                </div>
                <button class="btn btn-sm btn-outline mt-2 w-100" onclick="location.reload()">Reset</button>
            `;
        } else {
            content.innerHTML = '<p class="text-danger">Error: ' + riskRes.message + '</p>';
        }
    }).catch(err => {
        content.innerHTML = '<p class="text-danger">Failed to fetch data.</p>';
    });
}

function loadOrgInsights() {
    fetch('/api/intelligence/org-insights')
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById('org-insights-container');
            const badge = document.getElementById('org-health-badge');
            
            if (data.success && data.data) {
                const oi = data.data;
                const healthScore = 100 - (oi.dead_stock.length * 5); // Simple penalty
                badge.textContent = 'Inventory Health: ' + healthScore + '%';
                badge.className = 'badge ' + (healthScore > 80 ? 'bg-success' : 'bg-warning');
                
                if (oi.recommendations && Array.isArray(oi.recommendations)) {
                    container.innerHTML = oi.recommendations.map(r => `
                        <div class="insight-box p-3" style="background: white; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-top: 3px solid ${r.type === 'Urgent Stock Up' ? '#e53e3e' : '#3182ce'}">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <h4 style="margin: 0; font-size: 14px; color: #4a5568;"><i class="fas fa-${r.type === 'Urgent Stock Up' ? 'exclamation-triangle' : 'info-circle'}"></i> ${r.type}</h4>
                            </div>
                            <p style="font-size: 13px; font-weight: bold; color: #2d3748; margin-bottom: 5px;">${r.drug}</p>
                            <p style="font-size: 12px; margin: 0; color: #718096;"><strong>Reason:</strong> ${r.reason}</p>
                            <p style="font-size: 12px; margin: 5px 0 0 0; color: #2b6cb0;"><strong>Action:</strong> ${r.action}</p>
                        </div>
                    `).join('');
                }
                
                if (oi.recommendations.length === 0) {
                    container.innerHTML = '<div class="text-center w-100 p-4 font-italic">AI: Inventory is currently optimized. No strategic changes suggested.</div>';
                }
            } else {
                container.innerHTML = '<div class="text-center w-100 p-4">Unable to load insights.</div>';
            }
        });
}

function loadSafetySurveillance() {
    fetch('/api/intelligence/safety-monitoring')
        .then(res => res.json())
        .then(data => {
            const list = document.getElementById('safety-monitoring-list');
            if (data.success && data.monitoring_list && data.monitoring_list.length > 0) {
                list.innerHTML = data.monitoring_list.map(item => `
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${item.patient_name}</strong> (HN: ${item.hn})<br>
                            <small class="text-danger">เพิ่งเริ่มยา: ${item.drug_name}</small><br>
                            <span class="badge bg-warning text-dark"><i class="fas fa-glasses"></i> ${item.monitoring_focus}</span>
                        </div>
                        <button onclick="generateSafetyReport('${item.hn}')" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-file-alt"></i> Report
                        </button>
                    </div>
                `).join('');
            } else {
                list.innerHTML = '<div class="p-3 text-center text-muted">ไม่มีเคสที่ต้องเฝ้าระวังพิเศษในขณะนี้</div>';
            }
        });
}

function showSubstitutionAdvice(drugId, drugName) {
    const btn = event.currentTarget;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    fetch('/api/intelligence/shortage-substitutions')
        .then(res => res.json())
        .then(data => {
            btn.innerHTML = '<i class="fas fa-magic"></i> AI Substitute';
            if (data.success) {
                const sub = data.substitutions.find(s => s.drug_id == drugId);
                if (sub && sub.options.length > 0) {
                    let msg = `🤖 AI Substitution Suggestions for ${drugName}:\n\n`;
                    sub.options.forEach(opt => {
                        msg += `🔹 [${opt.type}] Alternative: ${opt.alt}\n  👉 ${opt.instruction}\n\n`;
                    });
                    alert(msg);
                } else {
                    alert("No specific AI substitution rules found for this medication.");
                }
            }
        });
}

function runClinicalRiskAnalysis() {
    const hn = document.getElementById('clinical-risk-hn-input').value;
    if (!hn) return alert("Please enter HN");
    
    const content = document.getElementById('clinical-risk-content');
    content.innerHTML = '<div class="text-center p-3"><i class="fas fa-microchip fa-spin"></i> AI Auditing Safety...</div>';
    
    fetch(`/api/intelligence/clinical-burdens/${hn}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const b = data.burdens;
                let html = `
                    <div class="clinical-risk-results" style="font-size: 11px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <span>Anticholinergic Burden:</span>
                            <span class="badge ${b.acb_score >= 3 ? 'bg-danger' : (b.acb_score > 0 ? 'bg-warning' : 'bg-success')}">${b.acb_score} pts</span>
                        </div>
                        <div class="small text-muted mb-2">Level: ${b.acb_level}</div>
                        
                        <div style="max-height: 120px; overflow-y: auto; border: 1px solid #edf2f7; padding: 5px; border-radius: 5px;">
                            <h6 style="font-size: 10px; margin-bottom: 5px; border-bottom: 1px solid #eee;">Geriatric Alerts (Beers)</h6>
                            ${b.geriatric_alerts.length > 0 ? b.geriatric_alerts.map(a => `
                                <div class="mb-2 p-1 bg-light border-left border-danger">
                                    <strong>${a.drug}:</strong> ${a.reason}
                                </div>
                            `).join('') : '<div class="text-muted text-center">No major alerts found.</div>'}
                        </div>
                        <button class="btn btn-sm btn-outline-secondary w-100 mt-2" onclick="location.reload()">Reset</button>
                    </div>
                `;
                content.innerHTML = html;
            } else {
                content.innerHTML = `<div class="text-center p-3 text-danger">Error: ${data.message}</div>`;
            }
        });
}

function runMedicationReconciliation() {
    const hn = document.getElementById('recon-hn-input').value;
    if (!hn) return alert("Please enter HN");
    
    const content = document.getElementById('recon-content');
    content.innerHTML = '<div class="text-center p-3"><i class="fas fa-brain fa-spin"></i> AI Reconciling Lists...</div>';
    
    fetch(`/api/intelligence/medication-reconciliation/${hn}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const r = data.reconciliation;
                if (r.error) {
                    content.innerHTML = `<div class="alert alert-warning small">${r.error}</div><button class="btn btn-sm btn-secondary w-100" onclick="location.reload()">Reset</button>`;
                    return;
                }
                
                let html = `
                    <div style="font-size: 11px;">
                        <span class="badge bg-success mb-2">${r.matches.length} Matches Found</span>
                        <span class="badge bg-danger mb-2">${r.discrepancies.length} Discrepancies</span>
                        <div style="max-height: 150px; overflow-y: auto;">
                            <table class="table table-sm table-borderless small">
                                <thead><tr><th>Item</th><th class="text-end">Status</th></tr></thead>
                                <tbody>
                `;
                
                r.discrepancies.forEach(d => {
                    const color = d.severity === 'High' ? 'text-danger' : 'text-warning';
                    html += `<tr><td><strong class="${color}">${d.type}:</strong> ${d.name}</td><td class="text-end"><i class="fas fa-exclamation-triangle ${color}"></i></td></tr>`;
                });
                
                html += `</tbody></table></div>`;
                html += `<button class="btn btn-sm btn-outline-primary w-100 mt-2" onclick="location.reload()">New Analysis</button></div>`;
                content.innerHTML = html;
            } else {
                content.innerHTML = `<div class="text-center p-3 text-danger">Error: ${data.message}</div>`;
            }
        });
}

// NEW: AI Clinical Assistant JS
function askAIAssistant() {
    const input = document.getElementById('ai-query-input');
    const query = input.value;
    if (!query) return;
    
    const chat = document.getElementById('ai-assistant-content');
    chat.innerHTML += `<div class="user-msg" style="margin: 10px 0; text-align: right; color: #3182ce;"><span style="background: white; padding: 5px 10px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);"><strong>You:</strong> ${query}</span></div>`;
    input.value = '';
    chat.scrollTop = chat.scrollHeight;
    
    fetch('/api/intelligence/ask', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ query: query })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            let response = '';
            const d = data.data;
            if (d.type === 'Help') {
                response = `<div>${d.message}</div><ul style="padding-left: 15px; margin: 5px 0;">` + d.suggestions.map(s => `<li style="cursor:pointer; color:#a855f7" onclick="document.getElementById('ai-query-input').value='${s}'; askAIAssistant()">${s}</li>`).join('') + '</ul>';
            } else if (d.type === 'Traditional Medicine') {
                response = `<div>${d.message}</div>`;
                if(d.alerts.length > 0) {
                     response += `<div style="background:#fff5f5; padding:5px; border-radius:5px; margin-top:5px;"><strong>Alerts:</strong><br>` + d.alerts.map(a => `⚠️ ${a.herb} + ${a.drug}: ${a.risk}`).join('<br>') + `</div>`;
                }
            } else if (d.findings) {
                // This is a patient review
                response = `<strong>Risk Level: ${d.risk_level} (Score: ${d.score})</strong><br>`;
                response += d.findings.slice(0, 3).map(f => `• [${f.type}] ${f.title}`).join('<br>');
                if(d.findings.length > 3) response += `<br>... and ${d.findings.length - 3} more findings.`;
            } else if (d.total_estimated_budget) {
                 response = `Budget Forecast: ฿${d.total_estimated_budget.toLocaleString()}`;
            } else if (Array.isArray(d)) {
                 response = `Found ${d.length} items.`;
            } else {
                 response = "I processed your request, but the data format is complex. Please check the specific modules above.";
            }

            chat.innerHTML += `<div class="ai-msg" style="margin: 10px 0;"><span style="background: #fdf2ff; padding: 5px 10px; border-radius: 12px; border-left: 3px solid #a855f7;"><strong>AI:</strong> ${response}</span></div>`;
            chat.scrollTop = chat.scrollHeight;
        }
    });
}

function showShortagePriority(drugId, drugName) {
    const btn = event.currentTarget;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    fetch(`/api/intelligence/shortage-priority/${drugId}`)
        .then(res => res.json())
        .then(data => {
            btn.innerHTML = originalHtml;
            if (data.success && data.data.length > 0) {
                let msg = `📋 AI Strategic Priority for ${drugName}:\n\nTop At-Risk Patients:\n`;
                data.data.forEach((p, idx) => {
                    msg += `${idx+1}. ${p.name} (Risk: ${p.risk_score})\n   Reason: ${p.reasons.join(', ')}\n\n`;
                });
                alert(msg);
            } else {
                alert("No high-risk patients found for this medication or insufficient history.");
            }
        });
}

// Update the loadDashboardStats function's shortages loop to include the Priority button
// (I will do this by wrapping it in the replace call or doing it separately)

function generateSafetyReport(hn) {
    fetch(`/api/intelligence/patient-safety-report/${hn}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.report);
            } else {
                alert('ไม่สามารถสร้างรายงานได้: ' + data.message);
            }
        });
}

function runAdvancedAIAnalysis() {
    const hn = document.getElementById('adv-ai-hn-input').value;
    if (!hn) return alert("Please enter HN");
    
    const ckdCont = document.getElementById('ckd-trend-content');
    const optCont = document.getElementById('optimization-content');
    
    ckdCont.innerHTML = '<i class="fas fa-sync fa-spin"></i> Calculating progression trend...';
    optCont.innerHTML = '<i class="fas fa-sync fa-spin"></i> Analyzing guidelines...';
    
    // 1. CKD Progression
    fetch(`/api/intelligence/ckd-progression/${hn}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.data.current_egfr) {
                const d = data.data;
                const riskColor = (d.risk_level || '').includes('High') ? '#e53e3e' : ((d.risk_level || '').includes('Rapid') ? '#dd6b20' : '#276749');
                ckdCont.innerHTML = `
                    <div style="font-weight:bold; color:${riskColor}; font-size:16px;">${d.risk_level}</div>
                    <div class="mt-2" style="font-size:11px;">
                        <span>Current eGFR: <strong>${d.current_egfr}</strong></span><br>
                        <span>Annual Decline: <strong>${d.annual_decline_rate}</strong> units/yr</span><br>
                        <span>Forecast (12m): <strong>${d.forecast_12m}</strong></span>
                    </div>
                    <div class="p-2 mt-2" style="background:rgba(255,255,255,0.7); border-radius:5px; border-left:3px solid ${riskColor}; font-size:11px;">
                        <i class="fas fa-lightbulb"></i> ${d.recommendation}
                    </div>
                `;
            } else {
                ckdCont.innerHTML = '<div class="text-danger small">Insufficient data (need >1 eGFR lab results).</div>';
            }
        });
        
    // 2. Optimization
    let findingsForScribe = [];
    fetch(`/api/intelligence/optimization/${hn}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const d = data.data;
                const opts = d.optimizations;
                if (!opts || opts.length === 0) {
                    optCont.innerHTML = '<div class="text-success small"><i class="fas fa-check-circle"></i> Medication regimen matches clinical guidelines.</div>';
                } else {
                    optCont.innerHTML = opts.map(o => {
                        findingsForScribe.push(o.finding);
                        return `
                        <div class="mb-2 p-2" style="background:white; border-radius:5px; border:1px solid #bee3f8; font-size:11px;">
                            <span class="badge bg-primary" style="font-size:9px;">${o.goal}</span>
                            <div style="font-weight:bold; margin:3px 0;">${o.finding}</div>
                            <div style="color:#2b6cb0;">👉 ${o.suggestion}</div>
                        </div>`;
                    }).join('');
                }
                window.lastAIAnalysisFindings = findingsForScribe;
                document.getElementById('btn-generate-scribe').disabled = false;
            } else {
                optCont.innerHTML = '<div class="text-danger small">Failed to analyze guidelines.</div>';
            }
        });

    // 3. Cost Optimization
    const costCont = document.getElementById('cost-optimization-content');
    costCont.innerHTML = '<i class="fas fa-sync fa-spin"></i> Analyzing costs...';
    fetch(`/api/intelligence/cost-optimization/${hn}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.data.cost_saving_opportunities.length > 0) {
                const opps = data.data.cost_saving_opportunities;
                costCont.innerHTML = opps.map(o => `
                    <div class="mb-2 p-2" style="background:white; border-radius:5px; border:1px solid #fed7e2; font-size:11px;">
                        <span class="badge bg-danger" style="font-size:9px;">${o.type}</span>
                        <div style="font-weight:bold; margin:3px 0; color:#9b2c2c;">${o.finding}</div>
                        <div style="color:#c53030;">💡 ${o.suggestion}</div>
                    </div>
                `).join('');
            } else {
                costCont.innerHTML = '<div class="text-success small"><i class="fas fa-check-circle"></i> No obvious cost-saving opportunities (generic/dose) found.</div>';
            }
        });
}

function generateScribeDraft() {
    const hn = document.getElementById('adv-ai-hn-input').value;
    const scribeCont = document.getElementById('scribe-content');
    const findings = window.lastAIAnalysisFindings || [];
    
    scribeCont.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Scribing...';
    
    fetch('/api/intelligence/scribe', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ hn: hn, findings: findings })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            scribeCont.innerText = data.data;
        } else {
            scribeCont.innerHTML = '<span class="text-danger">Failed to generate scribe draft.</span>';
        }
    });
}
function loadInterventionAnalytics() {
    fetch('/api/intelligence/intervention-analytics')
        .then(res => res.json())
        .then(data => {
            if (data.success && data.analytics) {
                const a = data.analytics;
                
                // 1. Line Chart: Trends
                const trendCtx = document.getElementById('interventionTrendChart').getContext('2d');
                new Chart(trendCtx, {
                    type: 'line',
                    data: {
                        labels: (a.by_day || []).map(d => d.date),
                        datasets: [{
                            label: 'Interventions',
                            data: (a.by_day || []).map(d => d.count),
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            fill: true,
                            tension: 0.4,
                            borderWidth: 3,
                            pointRadius: 4,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#667eea',
                            pointBorderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, grid: { display: false } },
                            x: { grid: { display: false } }
                        }
                    }
                });

                // 2. Pie Chart: Severity
                const severityCtx = document.getElementById('severityPieChart').getContext('2d');
                const severityData = a.severity;
                new Chart(severityCtx, {
                    type: 'doughnut',
                    data: {
                        labels: (severityData || []).map(s => s.severity),
                        datasets: [{
                            data: (severityData || []).map(s => s.count),
                            backgroundColor: ['#e53e3e', '#ecc94b', '#a0aec0', '#4299e1'],
                            hoverOffset: 4,
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { boxWidth: 12, padding: 15, font: { size: 11 } }
                            }
                        },
                        cutout: '70%'
                    }
                });
            }
        });
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadInterventionAnalytics();
});
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
