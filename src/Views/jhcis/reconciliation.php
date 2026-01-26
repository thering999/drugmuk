<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Reconciliation - JHCIS</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .reconciliation-container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 20px;
        }
        .page-header {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .control-panel {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .severity-critical {
            background: #fee2e2;
            border-left: 4px solid #dc2626;
        }
        .severity-high {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
        }
        .severity-medium {
            background: #dbeafe;
            border-left: 4px solid #3b82f6;
        }
        .discrepancy-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #f3f4f6;
            padding: 15px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
        }
        .qty-jhcis {
            color: #3b82f6;
            font-weight: bold;
        }
        .qty-drugmuk {
            color: #10b981;
            font-weight: bold;
        }
        .qty-diff {
            font-weight: bold;
        }
        .qty-diff.positive {
            color: #10b981;
        }
        .qty-diff.negative {
            color: #dc2626;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: all 0.2s;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-warning {
            background: #f59e0b;
            color: white;
        }
        .btn-success {
            background: #10b981;
            color: white;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-value {
            font-size: 32px;
            font-weight: bold;
        }
        .stat-label {
            color: #666;
            margin-top: 5px;
            font-size: 14px;
        }
        .loading {
            text-align: center;
            padding: 40px;
        }
        .spinner {
            border: 4px solid #f3f4f6;
            border-top: 4px solid #f59e0b;
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
    <div class="reconciliation-container">
        <div class="page-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>📊 Inventory Reconciliation</h1>
                    <p>เปรียบเทียบและปรับปรุงสต็อกระหว่าง JHCIS และ Drugmuk</p>
                </div>
                <a href="/admin/jhcis/dashboard" style="background: white; color: #f59e0b; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold;">
                    ← กลับ Dashboard
                </a>
            </div>
        </div>

        <div class="control-panel">
            <div style="display: flex; gap: 15px; align-items: center;">
                <div style="flex: 1;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">
                        Tolerance (%)
                    </label>
                    <input type="number" id="tolerance" value="5" min="0" max="100" 
                           style="width: 100%; padding: 10px; border: 2px solid #e5e7eb; border-radius: 5px;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px;">&nbsp;</label>
                    <button class="btn btn-primary" onclick="runReconciliation()">
                        🔍 Run Reconciliation
                    </button>
                </div>
            </div>
        </div>

        <div id="loading" class="loading" style="display: none;">
            <div class="spinner"></div>
            <p>กำลังเปรียบเทียบสต็อก...</p>
        </div>

        <div id="stats" class="stats-grid" style="display: none;">
            <div class="stat-card">
                <div class="stat-value" id="totalDiscrepancies" style="color: #f59e0b;">0</div>
                <div class="stat-label">Total Discrepancies</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="criticalCount" style="color: #dc2626;">0</div>
                <div class="stat-label">Critical</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="highCount" style="color: #f59e0b;">0</div>
                <div class="stat-label">High</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="mediumCount" style="color: #3b82f6;">0</div>
                <div class="stat-label">Medium</div>
            </div>
        </div>

        <div id="results" style="display: none;">
            <div style="margin-bottom: 15px; display: flex; justify-content: space-between;">
                <div>
                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                    <label for="selectAll">Select All</label>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button class="btn btn-warning" onclick="generateAdjustments()">
                        📋 Generate Adjustments
                    </button>
                    <button class="btn btn-success" onclick="applyAdjustments()" id="applyBtn" style="display: none;">
                        ✅ Apply Adjustments
                    </button>
                </div>
            </div>

            <div class="discrepancy-table">
                <table>
                    <thead>
                        <tr>
                            <th width="50">
                                <input type="checkbox" id="selectAllHeader" onchange="toggleSelectAll()">
                            </th>
                            <th>Drug Name</th>
                            <th>JHCIS Code</th>
                            <th>JHCIS Qty</th>
                            <th>Drugmuk Qty</th>
                            <th>Difference</th>
                            <th>% Diff</th>
                            <th>Severity</th>
                        </tr>
                    </thead>
                    <tbody id="discrepanciesBody">
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const hospitalId = <?= $_GET['hospital_id'] ?? 0 ?>;
        let discrepancies = [];
        let adjustments = [];

        async function runReconciliation() {
            const tolerance = document.getElementById('tolerance').value;
            
            document.getElementById('loading').style.display = 'block';
            document.getElementById('results').style.display = 'none';
            document.getElementById('stats').style.display = 'none';

            try {
                const formData = new FormData();
                formData.append('hospital_id', hospitalId);
                formData.append('tolerance', tolerance);

                const response = await fetch('/api/jhcis/reconciliation/run', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    discrepancies = data.data.discrepancies;
                    displayDiscrepancies(discrepancies);
                    updateStats(discrepancies);
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                alert('Error: ' + error.message);
            } finally {
                document.getElementById('loading').style.display = 'none';
            }
        }

        function displayDiscrepancies(discrepancies) {
            const tbody = document.getElementById('discrepanciesBody');
            tbody.innerHTML = '';

            discrepancies.forEach((disc, index) => {
                const diffClass = disc.difference > 0 ? 'positive' : 'negative';
                const severityClass = `severity-${disc.severity.toLowerCase()}`;

                const row = `
                    <tr class="${severityClass}">
                        <td><input type="checkbox" class="disc-checkbox" data-index="${index}"></td>
                        <td><strong>${disc.drug_name}</strong></td>
                        <td>${disc.jhcis_code}</td>
                        <td class="qty-jhcis">${disc.jhcis_qty.toLocaleString()}</td>
                        <td class="qty-drugmuk">${disc.drugmuk_qty.toLocaleString()}</td>
                        <td class="qty-diff ${diffClass}">
                            ${disc.difference > 0 ? '+' : ''}${disc.difference.toLocaleString()}
                        </td>
                        <td>${disc.percent_diff}%</td>
                        <td><strong>${disc.severity}</strong></td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });

            document.getElementById('results').style.display = 'block';
            document.getElementById('stats').style.display = 'grid';
        }

        function updateStats(discrepancies) {
            document.getElementById('totalDiscrepancies').textContent = discrepancies.length;
            document.getElementById('criticalCount').textContent = 
                discrepancies.filter(d => d.severity === 'CRITICAL').length;
            document.getElementById('highCount').textContent = 
                discrepancies.filter(d => d.severity === 'HIGH').length;
            document.getElementById('mediumCount').textContent = 
                discrepancies.filter(d => d.severity === 'MEDIUM').length;
        }

        function toggleSelectAll() {
            const checkboxes = document.querySelectorAll('.disc-checkbox');
            const selectAll = document.getElementById('selectAll').checked;
            checkboxes.forEach(cb => cb.checked = selectAll);
            document.getElementById('selectAllHeader').checked = selectAll;
        }

        function generateAdjustments() {
            const checkboxes = document.querySelectorAll('.disc-checkbox:checked');
            const selected = Array.from(checkboxes).map(cb => {
                const index = cb.dataset.index;
                return discrepancies[index];
            });

            if (selected.length === 0) {
                alert('Please select at least one discrepancy');
                return;
            }

            adjustments = selected.map(disc => ({
                drug_id: disc.drug_id,
                drug_name: disc.drug_name,
                current_qty: disc.drugmuk_qty,
                target_qty: disc.jhcis_qty,
                adjustment: disc.difference,
                action: disc.difference > 0 ? 'INCREASE' : 'DECREASE',
                reason: 'JHCIS Reconciliation',
                severity: disc.severity
            }));

            document.getElementById('applyBtn').style.display = 'inline-block';
            alert(`Generated ${adjustments.length} adjustments. Click "Apply Adjustments" to proceed.`);
        }

        async function applyAdjustments() {
            if (adjustments.length === 0) {
                alert('No adjustments to apply');
                return;
            }

            const requireApproval = confirm('Require approval for adjustments?\n\nYes = Pending approval\nNo = Apply immediately');

            try {
                const formData = new FormData();
                formData.append('adjustments', JSON.stringify(adjustments));
                formData.append('require_approval', requireApproval);

                const response = await fetch('/api/jhcis/reconciliation/adjust', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    const msg = requireApproval 
                        ? `${data.data.pending} adjustments pending approval`
                        : `${data.data.applied} adjustments applied successfully`;
                    alert(msg);
                    runReconciliation(); // Refresh
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }
    </script>
</body>
</html>
