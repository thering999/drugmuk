<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auto Drug Mapping - ระบบแนะนำการจับคู่ยา</title>
    <?= \App\Core\CSRF::metaTag() ?>
    <script>
        // CSRF Interceptor - Must be early in the head
        (function() {
            const originalFetch = window.fetch;
            window.fetch = function(url, options = {}) {
                const method = (options.method || 'GET').toUpperCase();
                if (method !== 'GET') {
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    if (token) {
                        options.headers = options.headers || {};
                        options.headers['X-CSRF-Token'] = token;
                    }
                }
                return originalFetch(url, options);
            };
        })();
    </script>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .mapping-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        .control-row {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 15px;
        }
        .form-group {
            flex: 1;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 2px solid #e5e7eb;
            border-radius: 5px;
            font-size: 14px;
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
        .btn-primary:hover {
            background: #5568d3;
        }
        .btn-success {
            background: #10b981;
            color: white;
        }
        .btn-success:hover {
            background: #059669;
        }
        .suggestions-table {
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
            color: #333;
        }
        td {
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
        }
        tr:hover {
            background: #f9fafb;
        }
        .confidence-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .confidence-high {
            background: #d1fae5;
            color: #065f46;
        }
        .confidence-medium {
            background: #fef3c7;
            color: #92400e;
        }
        .confidence-low {
            background: #fee2e2;
            color: #991b1b;
        }
        .match-type {
            font-size: 11px;
            color: #666;
            font-style: italic;
        }
        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .spinner {
            border: 4px solid #f3f4f6;
            border-top: 4px solid #667eea;
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
        .stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .stat-card {
            flex: 1;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="mapping-container">
        <div class="page-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>🤖 ระบบจับคู่ยาอัตโนมัติ (AI Support)</h1>
                    <p>ระบบแนะนำการ map รหัสยาอัตโนมัติด้วย AI</p>
                </div>
                <a href="/admin/jhcis/dashboard" class="btn btn-primary" style="text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                    ← กลับหน้าหลัก
                </a>
            </div>
        </div>

        <div class="control-panel">
            <div class="control-row">
                <div class="form-group">
                    <label>ค่าความเชื่อมั่นขั้นต่ำ (%)</label>
                    <input type="number" id="minConfidence" class="form-control" value="80" min="0" max="100">
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button class="btn btn-primary" onclick="getSuggestions()">
                        🔍 ค้นหาข้อเสนอแนะ
                    </button>
                </div>
            </div>
        </div>

        <div id="stats" class="stats" style="display: none;">
            <div class="stat-card">
                <div class="stat-value" id="totalSuggestions">0</div>
                <div class="stat-label">ข้อเสนอแนะทั้งหมด</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="highConfidence">0</div>
                <div class="stat-label">ความเชื่อมั่นสูง (≥90%)</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="selectedCount">0</div>
                <div class="stat-label">เลือกแล้ว</div>
            </div>
        </div>

        <div id="loading" class="loading" style="display: none;">
            <div class="spinner"></div>
            <p>กำลังวิเคราะห์และแนะนำการ mapping...</p>
        </div>

        <div id="results" style="display: none;">
            <div style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                    <label for="selectAll" style="margin-left: 5px;">เลือกทั้งหมด</label>
                </div>
                <button class="btn btn-success" onclick="applyMappings(event)">
                    ✅ บันทึกการจับคู่ที่เลือก
                </button>
            </div>

            <div class="suggestions-table">
                <table>
                    <thead>
                        <tr>
                            <th width="50">
                                <input type="checkbox" id="selectAllHeader" onchange="toggleSelectAll()">
                            </th>
                            <th>รหัสยา JHCIS</th>
                            <th>ชื่อยา JHCIS</th>
                            <th>→</th>
                            <th>ชื่อยาในระบบ Drugmuk</th>
                            <th>ความเชื่อมั่น</th>
                            <th>วิธีตรวจสอบ</th>
                        </tr>
                    </thead>
                    <tbody id="suggestionsBody">
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const hospitalId = <?= $_GET['hospital_id'] ?? 0 ?>;
        let suggestions = [];

        async function getSuggestions() {
            const minConfidence = document.getElementById('minConfidence').value / 100;
            
            document.getElementById('loading').style.display = 'block';
            document.getElementById('results').style.display = 'none';
            document.getElementById('stats').style.display = 'none';

            try {
                const formData = new FormData();
                formData.append('hospital_id', hospitalId);
                formData.append('min_confidence', minConfidence);

                const response = await fetch('/api/jhcis/auto-mapping/suggest', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    suggestions = data.data.suggestions;
                    displaySuggestions(suggestions);
                    updateStats(suggestions);
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                alert('Error: ' + error.message);
            } finally {
                document.getElementById('loading').style.display = 'none';
            }
        }

        function displaySuggestions(suggestions) {
            const tbody = document.getElementById('suggestionsBody');
            tbody.innerHTML = '';

            suggestions.forEach((suggestion, index) => {
                const confidence = Number(suggestion.confidence * 100).toFixed(1);
                const confidenceClass = confidence >= 90 ? 'confidence-high' : 
                                       confidence >= 70 ? 'confidence-medium' : 'confidence-low';

                let matchTypeLabel = suggestion.match_type;
                if (suggestion.match_type === 'exact_code') matchTypeLabel = 'รหัสตรงกันทุกประการ';
                if (suggestion.match_type === 'exact_name') matchTypeLabel = 'ชื่อตรงกันทุกประการ';
                if (suggestion.match_type === 'partial_name') matchTypeLabel = 'ชื่อใกล้เคียง';

                const row = `
                    <tr>
                        <td><input type="checkbox" class="suggestion-checkbox" data-index="${index}"></td>
                        <td><strong>${suggestion.jhcis_code}</strong></td>
                        <td>${suggestion.jhcis_name}</td>
                        <td style="text-align: center;">→</td>
                        <td><strong>${suggestion.drugmuk_name}</strong></td>
                        <td>
                            <span class="confidence-badge ${confidenceClass}">
                                ${confidence}%
                            </span>
                        </td>
                        <td><span class="match-type">${matchTypeLabel}</span></td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });

            document.getElementById('results').style.display = 'block';
            document.getElementById('stats').style.display = 'flex';
        }

        function updateStats(suggestions) {
            document.getElementById('totalSuggestions').textContent = suggestions.length;
            const highConf = suggestions.filter(s => s.confidence >= 0.9).length;
            document.getElementById('highConfidence').textContent = highConf;
            updateSelectedCount();
        }

        function toggleSelectAll() {
            const checkboxes = document.querySelectorAll('.suggestion-checkbox');
            const selectAll = document.getElementById('selectAll').checked;
            checkboxes.forEach(cb => cb.checked = selectAll);
            document.getElementById('selectAllHeader').checked = selectAll;
            updateSelectedCount();
        }

        function updateSelectedCount() {
            const selected = document.querySelectorAll('.suggestion-checkbox:checked').length;
            document.getElementById('selectedCount').textContent = selected;
        }

        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('suggestion-checkbox')) {
                updateSelectedCount();
            }
        });

        async function applyMappings(event) {
            const checkboxes = document.querySelectorAll('.suggestion-checkbox:checked');
            const selectedMappings = Array.from(checkboxes).map(cb => {
                const index = cb.dataset.index;
                return suggestions[index];
            });

            if (selectedMappings.length === 0) {
                alert('กรุณาเลือกรายการที่ต้องการจับคู่อย่างน้อย 1 รายการ');
                return;
            }

            if (!confirm(`ยืนยันการบันทึกการจับคู่ ${selectedMappings.length} รายการหรือไม่?`)) {
                return;
            }

            const btn = event.currentTarget;
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = '⏳ กำลังบันทึก...';

            try {
                const formData = new FormData();
                formData.append('hospital_id', hospitalId);
                formData.append('mappings', JSON.stringify(selectedMappings));

                const response = await fetch('/api/jhcis/auto-mapping/apply', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    alert(`สำเร็จ! บันทึกแล้ว: ${data.data.applied}, ล้มเหลว: ${data.data.failed}`);
                    getSuggestions(); // Refresh
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                alert('Error: ' + error.message);
            } finally {
                btn.disabled = false;
                btn.textContent = originalText;
            }
        }
    </script>
</body>
</html>
