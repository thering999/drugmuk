<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JHCIS Reports - Drugmuk</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .reports-container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 20px;
        }
        .page-header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .report-selector {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .report-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .tab {
            flex: 1;
            padding: 15px;
            background: #f3f4f6;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.2s;
        }
        .tab.active {
            background: #667eea;
            color: white;
        }
        .tab:hover {
            background: #e5e7eb;
        }
        .tab.active:hover {
            background: #5568d3;
        }
        .report-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .metric-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .metric-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
        }
        .metric-value {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .metric-label {
            font-size: 14px;
            opacity: 0.9;
        }
        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #f3f4f6;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.2s;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-success {
            background: #10b981;
            color: white;
        }
        .loading {
            text-align: center;
            padding: 40px;
        }
        .spinner {
            border: 4px solid #f3f4f6;
            border-top: 4px solid #10b981;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="reports-container">
        <div class="page-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>📈 JHCIS Reports & Analytics</h1>
                    <p>รายงานและวิเคราะห์ข้อมูลการเชื่อมต่อ JHCIS</p>
                </div>
                <a href="/admin/jhcis/dashboard" style="background: white; color: #10b981; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold;">
                    ← กลับ Dashboard
                </a>
            </div>
        </div>

        <div class="report-selector">
            <div class="report-tabs">
                <button class="tab active" onclick="switchTab('performance')">
                    📊 Sync Performance
                </button>
                <button class="tab" onclick="switchTab('quality')">
                    ✅ Data Quality
                </button>
                <button class="tab" onclick="switchTab('summary')">
                    📋 Executive Summary
                </button>
            </div>

            <div style="display: flex; gap: 15px; align-items: center;">
                <div style="flex: 1;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">From Date</label>
                    <input type="date" id="fromDate" class="form-control" 
                           value="<?= date('Y-m-d', strtotime('-30 days')) ?>"
                           style="width: 100%; padding: 10px; border: 2px solid #e5e7eb; border-radius: 5px;">
                </div>
                <div style="flex: 1;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">To Date</label>
                    <input type="date" id="toDate" class="form-control" 
                           value="<?= date('Y-m-d') ?>"
                           style="width: 100%; padding: 10px; border: 2px solid #e5e7eb; border-radius: 5px;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px;">&nbsp;</label>
                    <button class="btn btn-primary" onclick="generateReport()">
                        📊 Generate Report
                    </button>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px;">&nbsp;</label>
                    <button class="btn btn-success" onclick="exportReport()">
                        📤 Export
                    </button>
                </div>
            </div>
        </div>

        <div id="loading" class="loading" style="display: none;">
            <div class="spinner"></div>
            <p>กำลังสร้างรายงาน...</p>
        </div>

        <div id="reportContent" class="report-content" style="display: none;">
            <!-- Performance Report -->
            <div id="performanceReport" style="display: none;">
                <h2>📊 Sync Performance Report</h2>
                
                <div class="metric-grid" id="performanceMetrics">
                </div>

                <div class="chart-container">
                    <h3>Daily Sync Statistics</h3>
                    <table id="dailyTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Syncs</th>
                                <th>Records</th>
                                <th>Success</th>
                                <th>Failed</th>
                                <th>Avg Duration (sec)</th>
                            </tr>
                        </thead>
                        <tbody id="dailyBody">
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Quality Report -->
            <div id="qualityReport" style="display: none;">
                <h2>✅ Data Quality Report</h2>
                
                <div class="metric-grid" id="qualityMetrics">
                </div>

                <div class="chart-container">
                    <h3>Mapping Confidence Distribution</h3>
                    <table id="confidenceTable">
                        <thead>
                            <tr>
                                <th>Confidence Level</th>
                                <th>Count</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody id="confidenceBody">
                        </tbody>
                    </table>
                </div>

                <div class="chart-container">
                    <h3>Recent Errors (Last 7 Days)</h3>
                    <table id="errorsTable">
                        <thead>
                            <tr>
                                <th>Error Type</th>
                                <th>Count</th>
                            </tr>
                        </thead>
                        <tbody id="errorsBody">
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Summary Report -->
            <div id="summaryReport" style="display: none;">
                <h2>📋 Executive Summary</h2>
                
                <div class="metric-grid" id="summaryMetrics">
                </div>

                <div class="chart-container">
                    <h3>Recent Alerts</h3>
                    <div id="alertsList"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const hospitalId = <?= $_GET['hospital_id'] ?? 0 ?>;
        let currentTab = 'performance';
        let currentReport = null;

        function switchTab(tab) {
            currentTab = tab;
            
            // Update tab buttons
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');
            
            // Show/hide reports
            document.getElementById('performanceReport').style.display = 'none';
            document.getElementById('qualityReport').style.display = 'none';
            document.getElementById('summaryReport').style.display = 'none';
            
            if (currentReport) {
                displayReport(currentReport);
            }
        }

        async function generateReport() {
            const type = currentTab;
            const fromDate = document.getElementById('fromDate').value;
            const toDate = document.getElementById('toDate').value;

            document.getElementById('loading').style.display = 'block';
            document.getElementById('reportContent').style.display = 'none';

            try {
                const formData = new FormData();
                formData.append('hospital_id', hospitalId);
                formData.append('type', type);
                formData.append('from_date', fromDate);
                formData.append('to_date', toDate);

                const response = await fetch('/api/jhcis/reports/generate', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    currentReport = data.data;
                    displayReport(data.data);
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                alert('Error: ' + error.message);
            } finally {
                document.getElementById('loading').style.display = 'none';
            }
        }

        function displayReport(report) {
            document.getElementById('reportContent').style.display = 'block';

            if (currentTab === 'performance') {
                displayPerformanceReport(report);
            } else if (currentTab === 'quality') {
                displayQualityReport(report);
            } else if (currentTab === 'summary') {
                displaySummaryReport(report);
            }
        }

        function displayPerformanceReport(report) {
            document.getElementById('performanceReport').style.display = 'block';

            // Metrics
            const metrics = `
                <div class="metric-card">
                    <div class="metric-value">${report.overall.total_syncs || 0}</div>
                    <div class="metric-label">Total Syncs</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">${(report.overall.success_rate || 0).toFixed(1)}%</div>
                    <div class="metric-label">Success Rate</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">${(report.overall.total_records || 0).toLocaleString()}</div>
                    <div class="metric-label">Total Records</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">${(report.overall.avg_duration || 0).toFixed(1)}s</div>
                    <div class="metric-label">Avg Duration</div>
                </div>
            `;
            document.getElementById('performanceMetrics').innerHTML = metrics;

            // Daily table
            const tbody = document.getElementById('dailyBody');
            tbody.innerHTML = '';
            (report.daily || []).forEach(day => {
                tbody.innerHTML += `
                    <tr>
                        <td>${day.date}</td>
                        <td>${day.syncs}</td>
                        <td>${(day.records || 0).toLocaleString()}</td>
                        <td style="color: #10b981;">${day.success || 0}</td>
                        <td style="color: #ef4444;">${day.failed || 0}</td>
                        <td>${(day.avg_duration || 0).toFixed(1)}</td>
                    </tr>
                `;
            });
        }

        function displayQualityReport(report) {
            document.getElementById('qualityReport').style.display = 'block';

            // Metrics
            const metrics = `
                <div class="metric-card">
                    <div class="metric-value">${report.mapping_coverage.total_mapped || 0}</div>
                    <div class="metric-label">Mapped Drugs</div>
                </div>
            `;
            document.getElementById('qualityMetrics').innerHTML = metrics;

            // Confidence distribution
            const tbody = document.getElementById('confidenceBody');
            tbody.innerHTML = '';
            const total = report.mapping_coverage.total_mapped || 1;
            (report.mapping_coverage.by_confidence || []).forEach(conf => {
                const percent = ((conf.count / total) * 100).toFixed(1);
                tbody.innerHTML += `
                    <tr>
                        <td>${conf.confidence_level}</td>
                        <td>${conf.count}</td>
                        <td>${percent}%</td>
                    </tr>
                `;
            });

            // Errors
            const errorsBody = document.getElementById('errorsBody');
            errorsBody.innerHTML = '';
            (report.error_analysis || []).forEach(error => {
                errorsBody.innerHTML += `
                    <tr>
                        <td>${error.error_type}</td>
                        <td>${error.count}</td>
                    </tr>
                `;
            });
        }

        function displaySummaryReport(report) {
            document.getElementById('summaryReport').style.display = 'block';

            // Metrics
            const metrics = `
                <div class="metric-card">
                    <div class="metric-value">${report.sync_performance.success_rate || '0%'}</div>
                    <div class="metric-label">Success Rate (7 days)</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">${report.data_quality.mapped_drugs || 0}</div>
                    <div class="metric-label">Mapped Drugs</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">${report.alerts.active_count || 0}</div>
                    <div class="metric-label">Active Alerts</div>
                </div>
            `;
            document.getElementById('summaryMetrics').innerHTML = metrics;

            // Alerts
            const alertsList = document.getElementById('alertsList');
            alertsList.innerHTML = '';
            (report.alerts.recent || []).forEach(alert => {
                alertsList.innerHTML += `
                    <div style="padding: 15px; margin: 10px 0; background: #f9fafb; border-left: 4px solid #f59e0b; border-radius: 5px;">
                        <strong>${alert.type}</strong>
                        <p>${alert.message}</p>
                        <small style="color: #666;">${alert.created_at}</small>
                    </div>
                `;
            });
        }

        function exportReport() {
            const type = currentTab === 'performance' ? 'sync_logs' : 'mappings';
            const fromDate = document.getElementById('fromDate').value;
            const toDate = document.getElementById('toDate').value;
            
            window.location.href = `/admin/jhcis/export?type=${type}&hospital_id=${hospitalId}&from_date=${fromDate}&to_date=${toDate}`;
        }
    </script>
</body>
</html>
