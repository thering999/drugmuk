<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สแกนบาร์โค้ด - Drugmuk</title>
    <?= \App\Core\CSRF::metaTag() ?>
    <!-- Use specific version of html5-qrcode -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Sarabun', sans-serif; background: #f0f2f5; min-height: 100vh; padding-bottom: 50px; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        
        .header {
            text-align: center; margin-bottom: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 25px; border-radius: 20px; color: white;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.2);
        }
        .header h1 { font-size: 26px; margin-bottom: 5px; font-weight: 700; color: #fff; }
        .header p { opacity: 0.9; font-size: 14px; color: #eee; }
        
        /* Mode Switcher */
        .mode-switcher {
            display: flex; background: white; padding: 5px; border-radius: 50px;
            margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .mode-btn {
            flex: 1; padding: 10px; border: none; background: none; border-radius: 50px;
            font-family: 'Sarabun', sans-serif; font-weight: 600; color: #666; cursor: pointer; transition: all 0.3s;
        }
        .mode-btn.active { background: #667eea; color: white; box-shadow: 0 2px 5px rgba(102, 126, 234, 0.3); }

        .main-layout { display: grid; gap: 20px; }
        
        /* Scanner Section */
        .scanner-card {
            background: white; border-radius: 20px; overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            padding: 20px;
        }
        
        #reader { 
            width: 100%; 
            min-height: 250px; 
            background: #000; 
            border-radius: 12px;
            overflow: hidden;
            position: relative;
        }
        
        /* Controls */
        .controls {
            display: flex; gap: 10px; margin-top: 20px;
            justify-content: center;
        }
        .btn {
            border: none; padding: 12px 24px; border-radius: 50px;
            font-weight: 600; cursor: pointer; transition: all 0.2s;
            display: flex; align-items: center; gap: 8px;
            font-size: 16px; font-family: 'Sarabun', sans-serif;
        }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3); }
        .btn-secondary { background: #fff; color: #333; border: 2px solid #eee; }
        .btn-danger { background: #fee2e2; color: #dc2626; }
        .btn-success { background: #d1fae5; color: #065f46; }
        .btn:active { transform: scale(0.95); }
        .btn i { font-size: 18px; }
        
        /* Manual Entry */
        .manual-entry {
            margin-top: 20px; border-top: 1px solid #eee; padding-top: 20px;
            display: flex; gap: 10px;
        }
        .manual-input {
            flex: 1; padding: 12px 20px; border: 2px solid #eee; border-radius: 50px;
            font-family: 'Sarabun', sans-serif; font-size: 16px; outline: none; transition: all 0.3s;
        }
        .manual-input:focus { border-color: #667eea; }

        /* Bluetooth Indicator */
        .bt-indicator {
            text-align: center; margin-top: 10px; font-size: 12px; color: #666;
            display: flex; align-items: center; justify-content: center; gap: 5px;
        }
        .bt-indicator i { color: #667eea; }
        
        /* Result Card (Single Mode) */
        .result-card {
            background: white; border-radius: 20px; padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            display: none; animation: slideUp 0.3s ease;
            border-left: 5px solid #10b981;
        }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        
        .result-header { border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 15px; }
        .result-title { font-size: 14px; color: #666; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }
        .drug-name { font-size: 24px; color: #1f2937; font-weight: 700; margin-top: 5px; }
        .drug-code { font-family: 'Courier New', monospace; background: #f3f4f6; padding: 2px 8px; border-radius: 4px; color: #4b5563; font-size: 14px; }
        
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; }
        .stat-box { background: #f9fafb; padding: 15px; border-radius: 12px; text-align: center; }
        .stat-label { font-size: 13px; color: #6b7280; margin-bottom: 5px; }
        .stat-value { font-size: 20px; font-weight: 700; color: #111; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; margin-top: 5px; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        
        .actions { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .action-btn { 
            padding: 15px; border: none; border-radius: 12px; 
            font-weight: 600; font-size: 16px; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            transition: all 0.2s; font-family: 'Sarabun', sans-serif;
        }
        .btn-receive { background: #ecfdf5; color: #059669; }
        .btn-receive:hover { background: #d1fae5; }
        .btn-dispense { background: #eff6ff; color: #2563eb; }
        .btn-dispense:hover { background: #dbeafe; }

        /* Batch List */
        .batch-container {
            display: none;
            background: white; border-radius: 20px; padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .batch-header {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;
            font-weight: 700; color: #374151;
        }
        .batch-list { list-style: none; max-height: 400px; overflow-y: auto; }
        .batch-item {
            display: flex; justify-content: space-between; align-items: center;
            padding: 12px; border-bottom: 1px solid #eee; transition: all 0.3s;
        }
        /* Highlight newly added item */
        .batch-item.highlight { background-color: #ecfdf5; animation: highlightFade 2s forwards; }
        @keyframes highlightFade { 0% { background-color: #ecfdf5; } 100% { background-color: transparent; } }

        .batch-item-info h4 { font-size: 16px; margin-bottom: 2px; }
        .batch-item-info small { color: #6b7280; }
        
        .qty-control { flex-shrink: 0; display: flex; align-items: center; gap: 5px; }
        .qty-btn { width: 30px; height: 30px; border-radius: 50%; border: 1px solid #ddd; background: #fff; cursor: pointer; }
        .qty-val { width: 40px; text-align: center; border: none; font-weight: 600; }
        .del-btn { color: #ef4444; background: none; border: none; cursor: pointer; padding: 5px; margin-left: 10px; }

        .batch-actions {
            margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;
            display: grid; grid-template-columns: 1fr 1fr; gap: 15px;
        }
        
        /* Recent Scans */
        .recent-list { margin-top: 20px; }
        .recent-header { font-size: 16px; font-weight: 700; color: #4b5563; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
        .recent-item { 
            background: white; padding: 15px; border-radius: 12px; margin-bottom: 10px;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02); transition: all 0.2s; cursor: pointer;
        }
        
        /* Error */
        .error-overlay {
            position: fixed; top: 20px; left: 50%; transform: translateX(-50%);
            background: #fee2e2; color: #991b1b; padding: 15px 30px;
            border-radius: 50px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            z-index: 1000; display: none; font-weight: 600;
            display: flex; align-items: center; gap: 10px;
            white-space: nowrap;
        }
        
    </style>
</head>
<body>
    <div class="container">
        
        <div id="errorAlert" class="error-overlay">
            <i class="fas fa-exclamation-circle"></i> <span id="errorMsg">Error message</span>
        </div>

        <div class="header">
            <h1>📷 ระบบสแกนบาร์โค้ด</h1>
            <p>Drugmuk Smart Scanning System</p>
        </div>

        <div class="mode-switcher">
            <button class="mode-btn active" onclick="setMode('single')">Single Scan</button>
            <button class="mode-btn" onclick="setMode('batch')">Batch Mode (ต่อเนื่อง)</button>
        </div>

        <div class="main-layout">
            
            <!-- Scanner Card -->
            <div class="scanner-card">
                <div id="reader"></div>
                
                <div class="controls">
                    <button class="btn btn-primary" id="startBtn" onclick="startScanning()">
                        <i class="fas fa-camera"></i> เปิดกล้อง
                    </button>
                    <button class="btn btn-danger" id="stopBtn" onclick="stopScanning()" style="display:none;">
                        <i class="fas fa-stop"></i> หยุด
                    </button>
                </div>

                <div class="manual-entry">
                    <input type="text" id="manualInput" class="manual-input" placeholder="เลขบาร์โค้ด / รหัสยา..." onkeypress="handleEnter(event)">
                    <button class="btn btn-secondary" onclick="manualSearch()">
                        <i class="fas fa-search"></i>
                    </button>
                </div>

                <div class="bt-indicator">
                    <i class="fas fa-barcode"></i> รองรับ Bluetooth Scanner (ยิงได้เลยไม่ต้องคลิก)
                </div>
            </div>

            <!-- Single Result Card -->
            <div id="resultCard" class="result-card">
                <div class="result-header">
                    <div class="result-title">ผลการค้นหา</div>
                    <div class="drug-name" id="resName">-</div>
                    <span class="drug-code" id="resCode">-</span>
                </div>

                <div class="stats-grid">
                    <div class="stat-box">
                        <div class="stat-label">คลังใหญ่</div>
                        <div class="stat-value" id="resMainDetails">-</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-label">คลังย่อย</div>
                        <div class="stat-value" id="resSubDetails">-</div>
                    </div>
                </div>

                <div class="actions">
                    <button class="action-btn btn-receive" onclick="goToReceive()">
                        <i class="fas fa-box-open"></i> รับเข้าสต็อก
                    </button>
                    <button class="action-btn btn-dispense" onclick="goToDispense()">
                        <i class="fas fa-hand-holding-medical"></i> จ่ายยา
                    </button>
                </div>
            </div>

            <!-- Batch Container -->
            <div id="batchContainer" class="batch-container">
                <div class="batch-header">
                    <span>🛒 รายการที่สแกน (<span id="batchCount">0</span>)</span>
                    <button class="btn btn-secondary" style="padding: 5px 10px; font-size: 12px;" onclick="clearBatch()">Clear All</button>
                </div>
                <div style="text-align: center; padding: 20px; color: #999; font-style: italic;" id="emptyBatchMsg">
                    ยังไม่มีรายการ สแกนเพื่อเพิ่มสินค้า
                </div>
                <ul class="batch-list" id="batchList"></ul>
                
                <div class="batch-actions" id="batchActions" style="display: none;">
                    <button class="action-btn btn-receive" onclick="commitBatch('receive')">
                        <i class="fas fa-file-import"></i> ยืนยันรับเข้า
                    </button>
                    <button class="action-btn btn-dispense" onclick="commitBatch('dispense')">
                        <i class="fas fa-file-export"></i> ยืนยันจ่ายออก
                    </button>
                </div>
            </div>
            
            <!-- Recent Scans (Single Mode Only) -->
            <div class="recent-list" id="recentList" style="display:none;">
                <div class="recent-header">
                    <span>🕒 ประวัติการสแกนล่าสุด</span>
                    <button class="btn btn-secondary" style="padding: 5px 10px; font-size: 12px;" onclick="clearHistory()">L้างประวัติ</button>
                </div>
                <div id="historyContainer"></div>
            </div>
            
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="/dashboard" style="color: #6b7280; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                <i class="fas fa-arrow-left"></i> กลับไปหน้า Dashboard
            </a>
        </div>
    </div>

    <!-- Audio for beep -->
    <audio id="beepSound" src="https://cdn.freesound.org/previews/242/242501_4414128-lq.mp3"></audio>
    <audio id="successSound" src="https://cdn.freesound.org/previews/274/274181_5371556-lq.mp3"></audio>

    <script>
        function getCSRFToken() {
            return document.querySelector('meta[name="csrf-token"]').content;
        }
        let html5QrcodeScanner = null;
        let isScanning = false;
        let currentDrug = null;
        let mode = 'single'; // 'single' or 'batch'
        let batchItems = [];
        const beepAudio = document.getElementById("beepSound");
        const successAudio = document.getElementById("successSound");

        // Bluetooth Scanner Buffer
        let scanBuffer = "";
        let scanTimeout = null;

        document.addEventListener('DOMContentLoaded', () => {
            loadHistory();
            renderBatch();
            
            // Listen for keyboard input (Bluetooth Scanner acts as keyboard)
            document.addEventListener('keydown', handleGlobalKeydown);
        });

        // --- BLUETOOTH SCANNER SUPPORT ---
        function handleGlobalKeydown(e) {
            // Ignore if focus is on manual input (let it handle normally)
            if (document.activeElement.id === 'manualInput') return;
            
            // Scanner usually sends 'Enter' at end
            if (e.key === 'Enter') {
                if (scanBuffer.length > 2) { // Minimum length check
                    handleLookup(scanBuffer);
                    scanBuffer = "";
                }
                return;
            }
            
            // Only alphanumeric
            if (e.key.length === 1 && /[a-zA-Z0-9-]/.test(e.key)) {
                scanBuffer += e.key;

                // Clear buffer if typing stops (prevent random keypresses from accumulating)
                clearTimeout(scanTimeout);
                scanTimeout = setTimeout(() => { scanBuffer = ""; }, 200); // 200ms rapid fire
            }
        }

        function setMode(newMode) {
            mode = newMode;
            document.querySelectorAll('.mode-btn').forEach(b => b.classList.remove('active'));
            document.querySelector(`.mode-btn[onclick="setMode('${newMode}')"]`).classList.add('active');

            if (mode === 'single') {
                document.getElementById('resultCard').style.display = 'none'; // hide until updated
                document.getElementById('batchContainer').style.display = 'none';
                document.getElementById('recentList').style.display = 'block';
            } else {
                document.getElementById('resultCard').style.display = 'none';
                document.getElementById('batchContainer').style.display = 'block';
                document.getElementById('recentList').style.display = 'none';
            }
        }

        // --- SCANNING ---

        async function startScanning() {
            if (isScanning) return;
            try {
                html5QrcodeScanner = new Html5Qrcode("reader");
                const config = { fps: 10, qrbox: { width: 250, height: 250 }, aspectRatio: 1.0 };
                await html5QrcodeScanner.start({ facingMode: "environment" }, config, onScanSuccess, onScanFailure);
                isScanning = true;
                updateUIState('scanning');
                hideError();
            } catch (err) {
                showError("Camera Error: " + err);
            }
        }

        async function stopScanning() {
            if (!html5QrcodeScanner || !isScanning) return;
            try {
                await html5QrcodeScanner.stop();
                await html5QrcodeScanner.clear();
                html5QrcodeScanner = null;
                isScanning = false;
                updateUIState('idle');
            } catch (err) {}
        }

        function onScanSuccess(decodedText, decodedResult) {
            beepAudio.currentTime = 0;
            beepAudio.play().catch(e => {});

            if(mode === 'single') stopScanning(); // Single mode stops after scan
            
            handleLookup(decodedText);
        }
        
        function onScanFailure(error) {}

        // --- MANUAL ENTRY ---
        function handleEnter(e) { if (e.key === 'Enter') manualSearch(); }
        
        function manualSearch() {
            const code = document.getElementById('manualInput').value.trim();
            if (code) handleLookup(code);
        }

        // --- DATA LOGIC ---

        async function handleLookup(code) {
            try {
                const response = await fetch('/api/scan/lookup', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCSRFToken()
                    },
                    body: JSON.stringify({ code: code, csrf_token: getCSRFToken() })
                });
                const data = await response.json();
                
                if (data.success && data.found) {
                    if (mode === 'single') {
                        currentDrug = data.drug;
                        displayResult(data.drug);
                        addToHistory(data.drug);
                        
                        // Flash message better than alert
                        // alert(`Found: ${data.drug.name}`);
                    } else {
                        addToBatch(data.drug);
                        document.getElementById('manualInput').value = ''; 
                    }
                } else {
                    showError(`ไม่พบข้อมูลยา: ${code}`);
                }
            } catch (err) {
                showError("Network error");
            }
        }

        // --- BATCH LOGIC ---

        function addToBatch(drug) {
            const existing = batchItems.find(i => i.id === drug.id);
            if (existing) {
                existing.quantity++;
            } else {
                batchItems.push({ ...drug, quantity: 1, _isNew: true });
            }
            renderBatch();
        }

        function renderBatch() {
            const list = document.getElementById('batchList');
            const count = document.getElementById('batchCount');
            const emptyMsg = document.getElementById('emptyBatchMsg');
            const actions = document.getElementById('batchActions');
            
            list.innerHTML = '';
            count.textContent = batchItems.length;
            
            if (batchItems.length === 0) {
                emptyMsg.style.display = 'block';
                actions.style.display = 'none';
                return;
            }
            
            emptyMsg.style.display = 'none';
            actions.style.display = 'grid';

            batchItems.forEach((item, index) => {
                const li = document.createElement('li');
                li.className = `batch-item ${item._isNew ? 'highlight' : ''}`;
                
                // Remove highlight flag after render
                if(item._isNew) delete item._isNew;

                li.innerHTML = `
                    <div class="batch-item-info">
                        <h4>${item.name}</h4>
                        <small>Stock: ${item.stock_qty || 0}</small>
                    </div>
                    <div class="qty-control">
                        <button class="qty-btn" onclick="updateBatchQty(${index}, -1)">-</button>
                        <input type="text" readonly class="qty-val" value="${item.quantity}">
                        <button class="qty-btn" onclick="updateBatchQty(${index}, 1)">+</button>
                        <button class="del-btn" onclick="removeBatchItem(${index})"><i class="fas fa-trash"></i></button>
                    </div>
                `;
                list.appendChild(li);
            });
            
            // Scroll to bottom
            list.scrollTop = list.scrollHeight;
        }

        function updateBatchQty(index, change) {
            batchItems[index].quantity += change;
            if (batchItems[index].quantity < 1) batchItems[index].quantity = 1;
            renderBatch();
        }

        function removeBatchItem(index) {
            batchItems.splice(index, 1);
            renderBatch();
        }

        function clearBatch() {
            if(confirm('Clear all items?')) {
                batchItems = [];
                renderBatch();
            }
        }

        async function commitBatch(type) {
            if (!confirm(`ยืนยันการทำรายการ "${type === 'receive' ? 'รับเข้า' : 'จ่ายออก'}" ทั้งหมด ${batchItems.length} รายการ?`)) return;

            try {
                const response = await fetch('/api/scan/batch', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCSRFToken()
                    },
                    body: JSON.stringify({ type: type, items: batchItems, csrf_token: getCSRFToken() })
                });
                const res = await response.json();
                
                if (res.success) {
                    successAudio.play();
                    alert(res.message);
                    batchItems = [];
                    renderBatch();
                } else {
                    alert('Error: ' + res.message);
                }
            } catch (e) {
                alert('Connection Error');
            }
        }

        // --- SINGLE MODE UI ---

        function displayResult(drug) {
            document.getElementById('resName').textContent = drug.name;
            document.getElementById('resCode').textContent = drug.code;
            
            const mainStock = document.getElementById('resMainDetails');
            const qty = parseInt(drug.stock_qty || 0);
            mainStock.innerHTML = `
                ${qty.toLocaleString()} <span style="font-size:12px;color:#666;">${drug.unit || 'หน่วย'}</span>
                <br>${qty > 0 ? '<span class="badge badge-success">มีของ</span>' : '<span class="badge badge-danger">หมด</span>'}
            `;
            
            const subStock = document.getElementById('resSubDetails');
            const subQty = parseInt(drug.sub_stock_qty || 0);
            subStock.innerHTML = `${subQty.toLocaleString()} <span style="font-size:12px;color:#666;">${drug.unit || 'หน่วย'}</span>`;

            document.getElementById('resultCard').style.display = 'block';
            window.currentDrugId = drug.id;
        }

        function updateUIState(state) {
            const startBtn = document.getElementById('startBtn');
            const stopBtn = document.getElementById('stopBtn');
            
            if (state === 'scanning') {
                startBtn.style.display = 'none';
                stopBtn.style.display = 'flex';
                document.getElementById('reader').style.border = '2px solid #667eea';
            } else {
                startBtn.style.display = 'flex';
                stopBtn.style.display = 'none';
                document.getElementById('reader').style.border = 'none';
            }
        }

        function showError(msg) {
            const el = document.getElementById('errorAlert');
            const txt = document.getElementById('errorMsg');
            txt.textContent = msg;
            el.style.display = 'flex';
            setTimeout(() => { el.style.display = 'none'; }, 4000);
        }
        
        function hideError() { document.getElementById('errorAlert').style.display = 'none'; }
        
        function goToReceive() { if (!currentDrug) return; window.location.href = `/warehouse/receive?drug_id=${currentDrug.id}`; }
        function goToDispense() { if (!currentDrug) return; window.location.href = `/dispensing/create?drug_id=${currentDrug.id}`; }

        function addToHistory(drug) {
            let history = JSON.parse(localStorage.getItem('scan_history') || '[]');
            history = history.filter(h => h.id !== drug.id);
            history.unshift({ id: drug.id, name: drug.name, code: drug.code, timestamp: new Date().getTime() });
            if (history.length > 5) history.pop();
            localStorage.setItem('scan_history', JSON.stringify(history));
            loadHistory();
        }

        function loadHistory() {
            const history = JSON.parse(localStorage.getItem('scan_history') || '[]');
            const list = document.getElementById('recentList');
            const container = document.getElementById('historyContainer');
            
            if (history.length === 0) list.style.display = 'none';
            else list.style.display = 'block';
            
            container.innerHTML = '';
            history.forEach(item => {
                const elapsed = Math.floor((new Date().getTime() - item.timestamp) / 60000);
                const timeText = elapsed < 1 ? 'Just now' : `${elapsed}m ago`;
                const div = document.createElement('div');
                div.className = 'recent-item';
                div.onclick = () => { if(mode==='single') handleLookup(item.code); else addToBatch(item); };
                div.innerHTML = `
                    <div style="font-weight:600;color:#374151;">${item.name}</div>
                    <div style="font-size:12px;color:#999;">${timeText}</div>
                `;
                container.appendChild(div);
            });
        }
        function clearHistory() { localStorage.removeItem('scan_history'); loadHistory(); }

    </script>
</body>
</html>
