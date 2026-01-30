<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JHCIS API Debug</title>
    <?= \App\Core\CSRF::metaTag() ?>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: #4ec9b0;
            margin-bottom: 20px;
        }
        .section {
            background: #252526;
            border: 1px solid #3e3e42;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .section h2 {
            color: #569cd6;
            margin-bottom: 15px;
            font-size: 18px;
        }
        button {
            background: #0e639c;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-right: 10px;
        }
        button:hover {
            background: #1177bb;
        }
        pre {
            background: #1e1e1e;
            border: 1px solid #3e3e42;
            border-radius: 4px;
            padding: 15px;
            overflow-x: auto;
            margin-top: 10px;
            color: #ce9178;
        }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        .info { color: #569cd6; }
        .label {
            color: #9cdcfe;
            font-weight: bold;
            margin-top: 10px;
            display: block;
        }
        input, select {
            background: #3c3c3c;
            border: 1px solid #3e3e42;
            color: #d4d4d4;
            padding: 8px 12px;
            border-radius: 4px;
            width: 100%;
            margin-top: 5px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 JHCIS API Debug Tool</h1>
        
        <div class="section">
            <h2>📊 Test Report Generation API</h2>
            
            <label class="label">Report Type:</label>
            <select id="reportType">
                <option value="multi_hospital">Multi-Hospital Comparison</option>
                <option value="consumption">Consolidated Consumption</option>
                <option value="performance">Sync Performance (requires hospital_id)</option>
                <option value="quality">Data Quality (requires hospital_id)</option>
                <option value="summary">Executive Summary (requires hospital_id)</option>
            </select>
            
            <label class="label">Hospital ID (optional for multi/consumption):</label>
            <input type="number" id="hospitalId" placeholder="Leave empty for multi-hospital reports">
            
            <label class="label">From Date:</label>
            <input type="date" id="fromDate" value="<?= date('Y-m-d', strtotime('-30 days')) ?>">
            
            <label class="label">To Date:</label>
            <input type="date" id="toDate" value="<?= date('Y-m-d') ?>">
            
            <div style="margin-top: 15px;">
                <button onclick="testAPI()">🚀 Test API</button>
                <button onclick="clearResults()">🗑️ Clear</button>
            </div>
            
            <div id="results"></div>
        </div>
        
        <div class="section">
            <h2>📋 Recent Test Results</h2>
            <div id="history"></div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        let testHistory = [];
        
        async function testAPI() {
            const resultsDiv = document.getElementById('results');
            const reportType = document.getElementById('reportType').value;
            const hospitalId = document.getElementById('hospitalId').value;
            const fromDate = document.getElementById('fromDate').value;
            const toDate = document.getElementById('toDate').value;
            
            resultsDiv.innerHTML = '<pre class="info">⏳ Testing API endpoint...</pre>';
            
            const formData = new FormData();
            formData.append('type', reportType);
            if (hospitalId) formData.append('hospital_id', hospitalId);
            formData.append('from_date', fromDate);
            formData.append('to_date', toDate);
            
            const startTime = Date.now();
            
            try {
                const response = await fetch('/api/jhcis/reports/generate', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-Token': csrfToken
                    },
                    body: formData
                });
                
                const duration = Date.now() - startTime;
                const contentType = response.headers.get('content-type');
                const rawText = await response.text();
                
                let result = {
                    timestamp: new Date().toISOString(),
                    reportType,
                    hospitalId: hospitalId || 'N/A',
                    status: response.status,
                    contentType,
                    duration: duration + 'ms',
                    success: false,
                    data: null,
                    error: null
                };
                
                let output = `<pre class="info">📡 Response Status: ${response.status} ${response.statusText}</pre>`;
                output += `<pre class="info">⏱️ Duration: ${duration}ms</pre>`;
                output += `<pre class="info">📄 Content-Type: ${contentType}</pre>`;
                
                if (contentType && contentType.includes('application/json')) {
                    try {
                        const data = JSON.parse(rawText);
                        result.success = data.success;
                        result.data = data;
                        
                        if (data.success) {
                            output += `<pre class="success">✅ SUCCESS!</pre>`;
                            output += `<pre class="success">${JSON.stringify(data, null, 2)}</pre>`;
                        } else {
                            output += `<pre class="error">❌ API Error: ${data.message}</pre>`;
                            if (data.error_type) {
                                output += `<pre class="error">Error Type: ${data.error_type}</pre>`;
                            }
                            if (data.trace) {
                                output += `<pre class="error">Stack Trace:\n${data.trace}</pre>`;
                            }
                            result.error = data.message;
                        }
                    } catch (e) {
                        output += `<pre class="error">❌ JSON Parse Error: ${e.message}</pre>`;
                        output += `<pre class="error">Raw Response:\n${rawText}</pre>`;
                        result.error = 'JSON parse error: ' + e.message;
                    }
                } else {
                    output += `<pre class="error">❌ Invalid Content-Type (expected JSON)</pre>`;
                    output += `<pre class="error">Raw Response:\n${rawText.substring(0, 1000)}</pre>`;
                    result.error = 'Invalid content type: ' + contentType;
                }
                
                resultsDiv.innerHTML = output;
                testHistory.unshift(result);
                updateHistory();
                
            } catch (error) {
                const duration = Date.now() - startTime;
                resultsDiv.innerHTML = `
                    <pre class="error">❌ Network Error: ${error.message}</pre>
                    <pre class="error">Duration: ${duration}ms</pre>
                `;
                
                testHistory.unshift({
                    timestamp: new Date().toISOString(),
                    reportType,
                    hospitalId: hospitalId || 'N/A',
                    status: 'Network Error',
                    duration: duration + 'ms',
                    success: false,
                    error: error.message
                });
                updateHistory();
            }
        }
        
        function updateHistory() {
            const historyDiv = document.getElementById('history');
            if (testHistory.length === 0) {
                historyDiv.innerHTML = '<pre class="info">No tests run yet</pre>';
                return;
            }
            
            let html = '';
            testHistory.slice(0, 5).forEach((test, index) => {
                const statusClass = test.success ? 'success' : 'error';
                const statusIcon = test.success ? '✅' : '❌';
                html += `<pre class="${statusClass}">${statusIcon} [${test.timestamp}] ${test.reportType} - ${test.status} (${test.duration})</pre>`;
            });
            historyDiv.innerHTML = html;
        }
        
        function clearResults() {
            document.getElementById('results').innerHTML = '';
        }
    </script>
</body>
</html>
