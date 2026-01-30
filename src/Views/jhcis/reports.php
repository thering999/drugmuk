<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JHCIS Reports - Drugmuk</title>
    <?= \App\Core\CSRF::metaTag() ?>
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
                <button class="tab active" id="tab-multi-hospital" onclick="switchTab('multi_hospital')">
                    🏢 เปรียบเทียบ รพ.สต.
                </button>
                <button class="tab" id="tab-performance" onclick="switchTab('performance')">
                    📊 Sync Performance
                </button>
                <button class="tab" id="tab-quality" onclick="switchTab('quality')">
                    ✅ Data Quality
                </button>
                <button class="tab" id="tab-summary" onclick="switchTab('summary')">
                    📋 Executive Summary
                </button>
                <button class="tab" id="tab-consumption" onclick="switchTab('consumption')">
                    💊 วิเคราะห์ยอดใช้ยา
                </button>
            </div>




            <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                <?php if (isset($debugError)): ?>
                    <div class="alert-error-debug" style="width: 100%; padding: 15px; background: #fef2f2; border: 2px solid #ef4444; border-radius: 8px; color: #991b1b;">
                        <strong><?= $debugError ?></strong>
                        <br><small>Database connection seems OK, but jhcis_hospitals table is empty or query failed.</small>
                    </div>
                <?php endif; ?>
                
                <div style="flex: 1; min-width: 200px;" id="hospitalSelector">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">
                        🏥 เลือก รพ.สต. <span style="color: #ef4444;" id="requiredIndicator">*</span>
                    </label>
                    
                    <!-- DEBUG INFO -->
                    <?php 
                    echo "<!-- DEBUG: Hospitals count = " . count($hospitals ?? []) . " -->\n";
                    if (!empty($hospitals)) {
                        foreach ($hospitals as $idx => $h) {
                            echo "<!-- DEBUG Hospital[$idx]: " . json_encode($h) . " -->\n";
                        }
                    }
                    ?>
                    
                    <select id="hospitalId" class="form-control" 
                            style="width: 100%; padding: 10px; border: 2px solid #e5e7eb; border-radius: 5px;">
                        <option value="">-- เลือก รพ.สต. --</option>
                        <?php if (empty($hospitals)): ?>
                            <option value="" disabled>ยังไม่มี รพ.สต. ในระบบ - กรุณาเพิ่มที่หน้า Hospital Management</option>
                        <?php else: ?>
                            <?php foreach ($hospitals as $h): ?>
                                <?php 
                                    // Use PCU CODE if available and not empty/dash, otherwise use regular code
                                    $pcucode = trim($h['pcucode'] ?? '');
                                    $code = trim($h['code'] ?? '');
                                    
                                    // If pcucode is empty, null, or just a dash, use regular code
                                    if (empty($pcucode) || $pcucode === '-') {
                                        $displayCode = $code;
                                    } else {
                                        $displayCode = $pcucode;
                                    }
                                ?>
                                <option value="<?= $h['id'] ?>" 
                                        <?= ($hospitalId == $h['id']) ? 'selected' : '' ?>
                                        style="<?= empty($h['is_active']) ? 'color: #9ca3af;' : '' ?>">
                                    <?= htmlspecialchars($h['name']) ?><?= !empty($displayCode) ? ' (' . htmlspecialchars($displayCode) . ')' : '' ?>
                                    <?= empty($h['is_active']) ? ' [ปิดใช้งาน]' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <?php if (empty($hospitals)): ?>
                        <small style="color: #ef4444; display: block; margin-top: 5px;">
                            ⚠️ <a href="/admin/jhcis/hospitals" style="color: #3b82f6;">คลิกที่นี่</a> เพื่อเพิ่ม รพ.สต. ก่อน
                        </small>
                    <?php endif; ?>
                </div>
                <div style="flex: 1; min-width: 150px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">From Date</label>
                    <input type="date" id="fromDate" class="form-control" 
                           value="<?= date('Y-m-d', strtotime('-30 days')) ?>"
                           style="width: 100%; padding: 10px; border: 2px solid #e5e7eb; border-radius: 5px;">
                </div>
                <div style="flex: 1; min-width: 150px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">To Date</label>
                    <input type="date" id="toDate" class="form-control" 
                           value="<?= date('Y-m-d') ?>"
                           style="width: 100%; padding: 10px; border: 2px solid #e5e7eb; border-radius: 5px;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px;">&nbsp;</label>
                    <button class="btn btn-primary" onclick="generateReport()">
                        📊 สร้างรายงาน
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

            <!-- Executive Summary Report -->
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

            <!-- Multi-Hospital Report -->
            <div id="multiHospitalReport" style="display: none;">
                <h2>🏢 รายงานเปรียบเทียบการเชื่อมต่อ รพ.สต.</h2>
                
                <div class="metric-grid" id="multiMetrics">
                </div>

                <div class="chart-container">
                    <h3>สถานะการเชื่อมต่อแยกตาม รพ.สต.</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>รหัส</th>
                                <th>ชื่อ รพ.สต.</th>
                                <th>PCU Code</th>
                                <th>จำนวนยาที่ Map แล้ว</th>
                                <th>ซิงค์สำเร็จล่าสุด</th>
                                <th>จำนวนเรคคอร์ด (30 วัน)</th>
                                <th>ข้อผิดพลาด (7 วัน)</th>
                            </tr>
                        </thead>
                        <tbody id="multiBody">
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Consumption Report -->
            <div id="consumptionReport" style="display: none;">
                <h2>💊 รายงานวิเคราะห์ยอดใช้ยารวมทุก รพ.สต.</h2>
                <p>สรุปยอดจ่ายยา (Dispensing) เปรียบเทียบตามแต่ละแห่ง</p>
                
                <div class="chart-container" style="overflow-x: auto;">
                    <table id="consumptionTable">
                        <thead id="consumptionHead">
                        </thead>
                        <tbody id="consumptionBody">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        const selectedHospitalId = <?= json_encode($_GET['hospital_id'] ?? null) ?>;
        let currentTab = 'multi_hospital';
        let currentReport = null;

        function switchTab(tab) {
            currentTab = tab;
            
            // Update tab buttons
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.getElementById('tab-' + tab.replace('_', '-')).classList.add('active');
            
            // Show/hide hospital selector based on report type
            const hospitalSelector = document.getElementById('hospitalSelector');
            const requiredIndicator = document.getElementById('requiredIndicator');
            
            if (['multi_hospital', 'consumption'].includes(tab)) {
                // These reports don't need hospital selection
                hospitalSelector.style.display = 'none';
            } else {
                // Single-hospital reports require selection
                hospitalSelector.style.display = 'block';
            }
            
            // Show/hide reports
            document.getElementById('performanceReport').style.display = 'none';
            document.getElementById('qualityReport').style.display = 'none';
            document.getElementById('summaryReport').style.display = 'none';
            document.getElementById('multiHospitalReport').style.display = 'none';
            document.getElementById('consumptionReport').style.display = 'none';
            
            if (currentReport) {
                displayReport(currentReport);
            } else {
                generateReport();
            }
        }

        // Auto-include CSRF token in all AJAX requests (MUST BE FIRST!)
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        if (csrfToken) {
            // Fetch API
            const originalFetch = window.fetch;
            window.fetch = function(url, options = {}) {
                const method = (options.method || 'GET').toUpperCase();
                if (method !== 'GET') {
                    options.headers = options.headers || {};
                    options.headers['X-CSRF-Token'] = csrfToken;
                }
                return originalFetch(url, options);
            };
        }

        // Initialize: hide hospital selector for multi_hospital tab
        document.addEventListener('DOMContentLoaded', () => {
            const hospitalSelector = document.getElementById('hospitalSelector');
            if (hospitalSelector && currentTab === 'multi_hospital') {
                hospitalSelector.style.display = 'none';
            }
        });

        // Don't auto-generate on page load - wait for user to click
        document.addEventListener('DOMContentLoaded', async () => {
            // Check if hospitals need to be loaded
            const selector = document.getElementById('hospitalId');
            
            // Check if:
            // 1. Only default option exists (length <= 1)
            // 2. OR The "No hospitals" placeholder exists (length == 2 and second option is disabled)
            const needsLoading = selector && (
                selector.options.length <= 1 || 
                (selector.options.length === 2 && selector.options[1].disabled && selector.options[1].text.includes('ยังไม่มี'))
            );

            if (needsLoading) {
                try {
                    console.log('Fetching hospitals via API...');
                    const response = await fetch('/admin/jhcis/api/hospitals');
                    const result = await response.json();
                    
                    if (result.success && result.data && result.data.length > 0) {
                        // Clear existing options except the first one
                        selector.innerHTML = '<option value="">-- เลือก รพ.สต. --</option>';
                        
                        result.data.forEach(h => {
                            const displayCode = (h.pcucode && h.pcucode !== '-' && h.pcucode.trim() !== '') ? h.pcucode : h.code;
                            const option = document.createElement('option');
                            option.value = h.id;
                            option.textContent = `${h.name} (${displayCode})`;
                            if (!h.is_active) {
                                option.style.color = '#9ca3af';
                                option.textContent += ' [ปิดใช้งาน]';
                            }
                            selector.appendChild(option);
                        });
                        
                        // Hide error box if present
                        const errorBox = document.querySelector('.alert-error-debug');
                        if (errorBox) errorBox.style.display = 'none';
                        
                        // Also hide the "click here to add" warning under dropdown
                        const warningSmall = selector.nextElementSibling;
                        if (warningSmall && warningSmall.tagName === 'SMALL') {
                            warningSmall.style.display = 'none';
                        }
                    }
                } catch (e) {
                    console.error('Failed to load hospitals via API:', e);
                }
            }
        });

        async function generateReport() {
            const type = currentTab;
            const fromDate = document.getElementById('fromDate').value;
            const toDate = document.getElementById('toDate').value;
            const hospitalId = document.getElementById('hospitalId').value; // Read from dropdown

            // Hide previous reports
            document.querySelectorAll('.report-section').forEach(section => {
                section.style.display = 'none';
            });

            document.getElementById('loading').style.display = 'block';
            document.getElementById('reportContent').style.display = 'none';

            try {
                const formData = new FormData();
                
                // Only add hospital_id for single-hospital reports
                if (!['multi_hospital', 'consumption'].includes(type)) {
                    if (!hospitalId) {
                        throw new Error('กรุณาเลือก รพ.สต. ก่อนสร้างรายงาน');
                    }
                    formData.append('hospital_id', hospitalId);
                }
                
                formData.append('type', type);
                formData.append('from_date', fromDate);
                formData.append('to_date', toDate);

                const response = await fetch('/api/jhcis/reports/generate', {
                    method: 'POST',
                    body: formData
                });

                // Check if response is JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Non-JSON response:', text);
                    throw new Error('Server returned invalid response (not JSON). Check console for details.');
                }

                const data = await response.json();

                if (data.success) {
                    currentReport = data.data;
                    displayReport(data.data);
                } else {
                    let errorMsg = 'Error: ' + data.message;
                    if (data.error_type) {
                        errorMsg += '\nType: ' + data.error_type;
                    }
                    if (data.trace && confirm('Show detailed error trace?')) {
                        console.error('Error trace:', data.trace);
                    }
                    alert(errorMsg);
                }
            } catch (error) {
                console.error('Generate report error:', error);
                alert('เกิดข้อผิดพลาด: ' + error.message);
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
            } else if (currentTab === 'multi_hospital') {
                displayMultiHospitalReport(report);
            } else if (currentTab === 'consumption') {
                displayConsumptionReport(report);
            }
        }

        function displayConsumptionReport(report) {
            document.getElementById('consumptionReport').style.display = 'block';
            
            // Generate header
            let head = '<tr><th>ชื่อยา</th>';
            report.hospitals.forEach(h => {
                head += `<th style="text-align:center">${h.name}<br><small>${h.code}</small></th>`;
            });
            head += '<th style="text-align:center; background:#f3f4f6">รวมทั้งหมด</th></tr>';
            document.getElementById('consumptionHead').innerHTML = head;

            // Generate body
            let body = '';
            (report.data || []).forEach(item => {
                body += `<tr><td><strong>${item.name}</strong></td>`;
                report.hospitals.forEach(h => {
                    const qty = item.breakdown[h.code] || 0;
                    body += `<td style="text-align:center; ${qty > 0 ? 'background:#ecfdf5' : ''}">${qty.toLocaleString()}</td>`;
                });
                body += `<td style="text-align:center; font-weight:bold; background:#f3f4f6">${item.total.toLocaleString()}</td></tr>`;
            });
            document.getElementById('consumptionBody').innerHTML = body;
        }

        function displayMultiHospitalReport(report) {
            document.getElementById('multiHospitalReport').style.display = 'block';
            
            const metrics = `
                <div class="metric-card">
                    <div class="metric-value">${report.summary.total_hospitals}</div>
                    <div class="metric-label">รพ.สต. ทั้งหมด</div>
                </div>
                <div class="metric-card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    <div class="metric-value">${report.summary.total_mappings.toLocaleString()}</div>
                    <div class="metric-label">จำนวนการจับคู่ยารวม</div>
                </div>
                <div class="metric-card" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                    <div class="metric-value">${report.summary.avg_mappings}</div>
                    <div class="metric-label">ค่าเฉลี่ยการ Map ต่อแห่ง</div>
                </div>
            `;
            document.getElementById('multiMetrics').innerHTML = metrics;

            const tbody = document.getElementById('multiBody');
            tbody.innerHTML = '';
            (report.hospitals || []).forEach(h => {
                tbody.innerHTML += `
                    <tr>
                        <td>${h.code}</td>
                        <td><strong>${h.name}</strong></td>
                        <td><code>${(h.pcucode && h.pcucode !== '-' && String(h.pcucode).trim() !== '') ? h.pcucode : h.code}</code></td>
                        <td class="text-center">${h.mapped_count}</td>
                        <td>${h.last_success_sync || 'ยังไม่เคยซิงค์'}</td>
                        <td>${(h.records_30d || 0).toLocaleString()}</td>
                        <td style="color: ${h.failures_7d > 0 ? '#ef4444' : '#10b981'};">
                            ${h.failures_7d}
                        </td>
                    </tr>
                `;
            });
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
                    <div class="metric-value">${Number(report.overall.success_rate || 0).toFixed(1)}%</div>
                    <div class="metric-label">Success Rate</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">${Number(report.overall.total_records || 0).toLocaleString()}</div>
                    <div class="metric-label">Total Records</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">${Number(report.overall.avg_duration || 0).toFixed(1)}s</div>
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
                        <td>${Number(day.avg_duration || 0).toFixed(1)}</td>
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
                const percent = (Number(conf.count || 0) / total * 100).toFixed(1);
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
            // Get value from dropdown explicitly
            const hId = document.getElementById('hospitalId').value;
            
            window.location.href = `/admin/jhcis/export?type=${type}&hospital_id=${hId}&from_date=${fromDate}&to_date=${toDate}`;
        }
    </script>
</body>
</html>
