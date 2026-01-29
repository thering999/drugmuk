<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Real-time Sync Command Center - Drugmuk</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
    <style>
        :root {
            --primary: #6366f1;
            --secondary: #a855f7;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --dark: #1f2937;
            --light: #f3f4f6;
            --glass-bg: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.1);
            --card-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
            --modal-bg: #1e293b;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; outline: none; }

        body {
            font-family: 'Sarabun', sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
        }

        /* Animated Background */
        .bg-animation {
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            z-index: -1;
            background: radial-gradient(circle at 50% 50%, #1e1b4b 0%, #0f172a 100%);
        }

        .bg-grid {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: 
                linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: -1;
            opacity: 0.5;
        }

        /* Layout */
        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 1.5rem;
            width: 100%;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1rem 2rem;
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            box-shadow: var(--card-shadow);
        }

        .nav-brand {
            font-family: 'Outfit', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }
        
        .nav-brand span {
            font-size: 0.9rem; 
            padding: 4px 10px; 
            background: rgba(255,255,255,0.1); 
            border-radius: 20px; 
            font-weight: 400;
        }

        .nav-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .btn-icon {
            width: 40px; height: 40px;
            border-radius: 10px;
            border: 1px solid var(--glass-border);
            background: rgba(255,255,255,0.05);
            color: #cbd5e1;
            cursor: pointer;
            transition: all 0.2s;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none;
        }
        
        .btn-icon:hover { background: rgba(255,255,255,0.1); color: white; transform: translateY(-2px); }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 1.5rem;
            flex: 1;
            min-height: 0; /* Important for scroll */
        }

        /* Panels */
        .glass-panel {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            box-shadow: var(--card-shadow);
        }

        /* Connection Card (Left Top) */
        .status-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 2rem;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 28px;
        }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute; cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #475569;
            transition: .4s; border-radius: 34px;
        }
        .slider:before {
            position: absolute; content: "";
            height: 20px; width: 20px;
            left: 4px; bottom: 4px;
            background-color: white;
            transition: .4s; border-radius: 50%;
        }
        input:checked + .slider { background-color: var(--success); }
        input:checked + .slider:before { transform: translateX(22px); }

        .connection-status {
            text-align: center;
            padding: 2rem 0;
            position: relative;
        }
        
        .pulse-ring {
            width: 140px; height: 140px;
            background: rgba(16, 185, 129, 0.05);
            border: 2px solid rgba(16, 185, 129, 0.2);
            border-radius: 50%;
            margin: 0 auto;
            position: relative;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.5s;
        }
        
        .pulse-ring.offline {
            background: rgba(239, 68, 68, 0.05);
            border-color: rgba(239, 68, 68, 0.2);
        }

        .pulse-ring i { font-size: 3rem; color: var(--success); transition: 0.3s; }
        .pulse-ring.offline i { color: var(--danger); }

        .pulse-ring::before {
            content: ''; position: absolute;
            width: 100%; height: 100%;
            border-radius: 50%;
            border: 2px solid var(--success);
            opacity: 0;
            animation: pulse-border 2s infinite;
        }
        .pulse-ring.offline::before { animation: none; border-color: var(--danger); }

        @keyframes pulse-border {
            0% { transform: scale(1); opacity: 0.8; }
            100% { transform: scale(1.5); opacity: 0; }
        }

        /* Stats Grid (Left Middle) */
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.8rem;
            margin-top: 1.5rem;
        }

        .stat-card {
            background: rgba(255,255,255,0.03);
            border-radius: 12px;
            padding: 1rem;
            transition: 0.3s;
        }
        
        .stat-card:hover { background: rgba(255,255,255,0.06); }

        .stat-label { font-size: 0.8rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-val { font-size: 1.5rem; font-weight: 700; color: white; margin-top: 5px; font-family: 'Outfit'; }

        /* Feed Section (Right) */
        .feed-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 1rem;
        }

        .filter-group {
            display: flex;
            gap: 8px;
            background: rgba(0,0,0,0.2);
            padding: 4px;
            border-radius: 8px;
        }

        .filter-btn {
            background: transparent;
            border: none;
            color: #94a3b8;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: 0.2s;
        }

        .filter-btn.active {
            background: rgba(255,255,255,0.1);
            color: white;
            font-weight: 600;
        }

        .log-list {
            flex: 1;
            overflow-y: auto;
            padding-right: 8px;
        }
        
        .log-list::-webkit-scrollbar { width: 6px; }
        .log-list::-webkit-scrollbar-track { background: transparent; }
        .log-list::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
        .log-list::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.2); }

        .log-row {
            display: grid;
            grid-template-columns: 60px 1fr 120px 40px;
            align-items: center;
            padding: 1rem;
            margin-bottom: 0.8rem;
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.03);
            border-radius: 12px;
            transition: all 0.2s;
            cursor: pointer;
        }

        .log-row:hover {
            background: rgba(255,255,255,0.05);
            transform: translateX(4px);
            border-color: rgba(255,255,255,0.1);
        }

        .log-icon-box {
            width: 40px; height: 40px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
        }

        .log-main { padding: 0 1rem; overflow: hidden; }
        .log-title { font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #f1f5f9; }
        .log-sub { font-size: 0.85rem; color: #94a3b8; display: flex; gap: 8px; margin-top: 4px; }
        
        .log-time { text-align: right; font-size: 0.85rem; color: #64748b; font-family: monospace; }
        
        .badge { font-size: 0.7rem; padding: 2px 6px; border-radius: 4px; background: #334155; color: #cbd5e1; }

        /* Modal */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(5px);
            z-index: 100;
            display: none;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: 0.3s;
        }
        
        .modal-overlay.open { display: flex; opacity: 1; }

        .modal {
            background: #1e293b;
            width: 90%; max-width: 600px;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            transform: scale(0.9);
            transition: 0.3s;
            display: flex; flex-direction: column;
            max-height: 85vh;
        }
        
        .modal-overlay.open .modal { transform: scale(1); }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            display: flex; justify-content: space-between; align-items: center;
        }

        .modal-body { padding: 1.5rem; overflow-y: auto; }
        
        .json-viewer {
            background: #0f172a;
            padding: 1rem;
            border-radius: 8px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.9rem;
            color: #a5b4fc;
            white-space: pre-wrap;
            overflow-x: auto;
        }

        .key { color: #818cf8; }
        .string { color: #34d399; }
        .number { color: #fbbf24; }
        .boolean { color: #f472b6; }

    </style>
</head>
<body>
    <div class="bg-animation"></div>
    <div class="bg-grid"></div>

    <div class="container">
        <!-- TOP NAV -->
        <nav class="navbar">
            <a href="#" class="nav-brand">
                <i class="fa-solid fa-bolt" style="color: #fbbf24; font-size: 1.8rem;"></i>
                <div>
                    <div>Drugmuk Sync</div>
                    <span id="txt-version">v2.4.0 Live Agent</span>
                </div>
            </a>
            <div class="nav-controls">
                <div style="text-align: right; margin-right: 15px;">
                    <div style="font-size: 0.8rem; color: #94a3b8;">Server Time</div>
                    <div id="clock" style="font-family: monospace; font-size: 1.1rem; font-weight: 600;">--:--:--</div>
                </div>
                <a href="/dashboard" class="btn-icon" title="Main Dashboard">
                    <i class="fa-solid fa-house"></i>
                </a>
                <button class="btn-icon" onclick="toggleFullScreen()" title="Fullscreen">
                    <i class="fa-solid fa-expand"></i>
                </button>
            </div>
        </nav>

        <div class="dashboard-grid">
            <!-- LEFT PANEL: STATUS & CONTROLS -->
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                <!-- Master Control -->
                <div class="glass-panel">
                    <div class="status-header">
                        <h3 style="margin: 0;">Connection Status</h3>
                        <label class="switch" title="Toggle Auto-Sync">
                            <input type="checkbox" id="syncSw" checked onchange="toggleSyncSystem(this)">
                            <span class="slider"></span>
                        </label>
                    </div>
                    
                    <div class="connection-status">
                        <div class="pulse-ring" id="pulseRing">
                            <i class="fa-solid fa-link" id="statusIcon"></i>
                        </div>
                        <h2 style="margin-top: 1.5rem; font-family: 'Outfit';" id="statusText">ONLINE</h2>
                        <div style="color: #94a3b8; font-size: 0.9rem; margin-top: 5px;" id="statusSub">
                            Connected via secure SSE stream
                        </div>
                    </div>
                </div>

                <!-- Stats Summary -->
                <div class="glass-panel" style="flex: 1;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <h3>Statistics</h3>
                        <button onclick="resetStats()" style="background: none; border: none; color: #64748b; cursor: pointer; font-size: 0.8rem;">
                            <i class="fa-solid fa-rotate-right"></i> Reset
                        </button>
                    </div>

                    <div class="chart-container" style="height: 150px; margin-bottom: 1.5rem;">
                        <canvas id="miniChart"></canvas>
                    </div>

                    <div class="stats-grid">
                        <div class="stat-card">
                            <i class="fa-solid fa-arrow-up" style="color: #a78bfa;"></i>
                            <div class="stat-val" id="countToJHCIS">0</div>
                            <div class="stat-label">Sent to JHCIS</div>
                        </div>
                        <div class="stat-card">
                            <i class="fa-solid fa-arrow-down" style="color: #34d399;"></i>
                            <div class="stat-val" id="countFromJHCIS">0</div>
                            <div class="stat-label">Received</div>
                        </div>
                        <div class="stat-card">
                            <i class="fa-solid fa-triangle-exclamation" style="color: #f87171;"></i>
                            <div class="stat-val" id="countErrors">0</div>
                            <div class="stat-label">Errors</div>
                        </div>
                        <div class="stat-card">
                            <i class="fa-solid fa-stopwatch" style="color: #60a5fa;"></i>
                            <div class="stat-val" id="avgLatency">24ms</div>
                            <div class="stat-label">Avg Latency</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT PANEL: FEED -->
            <div class="glass-panel">
                <div class="feed-toolbar">
                    <div class="filter-group">
                        <button class="filter-btn active" onclick="setFilter('all')">All Activity</button>
                        <button class="filter-btn" onclick="setFilter('to_jhcis')">To JHCIS</button>
                        <button class="filter-btn" onclick="setFilter('from_jhcis')">From JHCIS</button>
                        <button class="filter-btn" onclick="setFilter('error')">Errors</button>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <button class="btn-icon" onclick="exportLogs()" title="Download Logs">
                            <i class="fa-solid fa-download"></i>
                        </button>
                        <button class="btn-icon" onclick="testSync()" title="Test Sync (Trigger Fake Data)" style="background: var(--primary); color: white; border: none; width: auto; padding: 0 15px; font-weight: 600;">
                            <i class="fa-solid fa-vial"></i> &nbsp; Test Sync
                        </button>
                    </div>
                </div>

                <div class="log-list" id="logList">
                    <!-- Logs injected here -->
                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; opacity: 0.7;">
                        <i class="fa-solid fa-satellite-dish" style="font-size: 4rem; margin-bottom: 1rem;"></i>
                        <p>Waiting for data stream...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- DETAIL MODAL -->
    <div class="modal-overlay" id="detailModal">
        <div class="modal">
            <div class="modal-header">
                <h3 id="modalTitle">Transaction Details</h3>
                <button onclick="closeModal()" class="btn-icon" style="border: none; background: transparent;">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="modal-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                    <div>
                        <span style="color: #64748b; font-size: 0.85rem;">Transaction ID</span>
                        <div style="font-weight: 600;" id="modalId">#---</div>
                    </div>
                    <div>
                        <span style="color: #64748b; font-size: 0.85rem;">Timestamp</span>
                        <div style="font-weight: 600;" id="modalTime">---</div>
                    </div>
                </div>
                <div style="margin-bottom: 0.5rem; color: #94a3b8; font-size: 0.9rem;">Payload Data</div>
                <div class="json-viewer" id="modalContent"></div>
                <br>
                <div style="display: flex; justify-content: flex-end;">
                     <button onclick="closeModal()" style="padding: 10px 20px; background: #334155; color: white; border: none; border-radius: 8px; cursor: pointer;">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // --- DATA & STATE ---
        // Preload existing logs from server
        const initialLogs = <?php echo json_encode($recentChanges ?? [], JSON_UNESCAPED_UNICODE); ?>;
        
        const state = {
            logs: [],
            stats: { to: 0, from: 0, err: 0 },
            filter: 'all',
            chartData: Array(15).fill(0)
        };
        let chartInstance;

        // --- INIT ---
        document.addEventListener('DOMContentLoaded', () => {
            initChart();
            loadInitialLogs();
            connectSSE();
            updateClock();
            setInterval(updateClock, 1000);
            
            // Check Auto-Sync State
            checkSystemStatus();
        });
        
        function loadInitialLogs() {
            if (initialLogs && initialLogs.length > 0) {
                initialLogs.forEach(log => {
                    log.timestamp = log.created_at ? new Date(log.created_at) : new Date();
                    
                    // Count stats
                    if(log.status === 'error') state.stats.err++;
                    else if(log.direction === 'to_jhcis') state.stats.to++;
                    else state.stats.from++;
                    
                    state.logs.push(log);
                });
                
                updateStatDisplay();
                renderLogs();
                
                // Update chart with random values for visual effect
                state.logs.slice(0, 15).forEach(() => {
                    updateChart(Math.random() * 5 + 1);
                });
            }
        }

        // --- CLOCK ---
        function updateClock() {
            const now = new Date();
            document.getElementById('clock').innerText = now.toLocaleTimeString('th-TH', { hour12: false });
        }

        // --- CHART ---
        function initChart() {
            const ctx = document.getElementById('miniChart').getContext('2d');
            chartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: Array(15).fill(''),
                    datasets: [{
                        data: state.chartData,
                        backgroundColor: '#6366f1',
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { x: { display: false }, y: { display: false } },
                    animation: { duration: 300 }
                }
            });
        }

        function updateChart(val) {
            state.chartData.shift();
            state.chartData.push(val);
            chartInstance.update();
        }

        // --- SSE & SYNC LOGIC ---
        let eventSource;
        function connectSSE() {
            if(eventSource) eventSource.close();
            
            eventSource = new EventSource('/realtime-sync/stream');
            
            eventSource.onopen = () => {
                setOnline(true);
            };

            eventSource.onerror = () => {
                setOnline(false);
                setTimeout(connectSSE, 5000); // Retry
            };

            eventSource.addEventListener('change', (e) => {
                const data = JSON.parse(e.data);
                handleNewLog(data);
            });
        }

        function setOnline(isOnline) {
            const ring = document.getElementById('pulseRing');
            const icon = document.getElementById('statusIcon');
            const mainText = document.getElementById('statusText');
            const subText = document.getElementById('statusSub');

            if(isOnline) {
                ring.classList.remove('offline');
                icon.className = 'fa-solid fa-link';
                mainText.innerText = 'ONLINE';
                mainText.style.color = '#fff';
                subText.innerText = 'Secure SSE Stream Active';
            } else {
                ring.classList.add('offline');
                icon.className = 'fa-solid fa-link-slash';
                mainText.innerText = 'DISCONNECTED';
                mainText.style.color = '#ef4444';
                subText.innerText = 'Attempting to reconnect...';
            }
        }

        function handleNewLog(data) {
            // Update Stats
            if(data.status === 'error') state.stats.err++;
            else if(data.direction === 'to_jhcis') state.stats.to++;
            else state.stats.from++;

            updateStatDisplay();
            
            // Add to Log Array
            data.timestamp = new Date();
            state.logs.unshift(data);
            if(state.logs.length > 100) state.logs.pop(); // Keep last 100

            renderLogs();
            updateChart(Math.random() * 5 + 2); // Visual feedback
        }

        function updateStatDisplay() {
            document.getElementById('countToJHCIS').innerText = state.stats.to;
            document.getElementById('countFromJHCIS').innerText = state.stats.from;
            document.getElementById('countErrors').innerText = state.stats.err;
        }

        // --- RENDERING ---
        function renderLogs() {
            const list = document.getElementById('logList');
            const filtered = state.logs.filter(l => {
                if(state.filter === 'all') return true;
                if(state.filter === 'error') return l.status === 'error';
                return l.direction === state.filter;
            });

            if(filtered.length === 0) {
                 if(state.logs.length === 0) return; // Keep waiting msg
                 list.innerHTML = '<div style="text-align:center; padding: 2rem; color: #64748b;">No logs match filter</div>';
                 return;
            }

            list.innerHTML = filtered.map(log => {
                const isTo = log.direction === 'to_jhcis';
                const isErr = log.status === 'error';
                
                let iconColor = isErr ? '#ef4444' : (isTo ? '#a78bfa' : '#34d399');
                let iconClass = isErr ? 'fa-triangle-exclamation' : (isTo ? 'fa-upload' : 'fa-download');
                let bg = isErr ? 'rgba(239, 68, 68, 0.1)' : (isTo ? 'rgba(167, 139, 250, 0.1)' : 'rgba(52, 211, 153, 0.1)');
                
                return `
                <div class="log-row" onclick='openDetail(${JSON.stringify(log)})'>
                    <div class="log-icon-box" style="background: ${bg}; color: ${iconColor};">
                        <i class="fa-solid ${iconClass}"></i>
                    </div>
                    <div class="log-main">
                        <div class="log-title">${log.change_type || 'Sync Event'} #${log.record_id || log.id}</div>
                        <div class="log-sub">
                            <span class="badge" style="background: ${bg}; color: ${iconColor}; border: 1px solid ${iconColor}44;">${log.status || 'Success'}</span>
                            ${log.details ? '<span>• has details</span>' : ''}
                        </div>
                    </div>
                    <div>
                         ${isTo ? '<i class="fa-solid fa-arrow-right" style="opacity:0.3"></i>' : '<i class="fa-solid fa-arrow-left" style="opacity:0.3"></i>'}
                    </div>
                    <div class="log-time">
                        ${new Date(log.timestamp).toLocaleTimeString('th-TH').split(':').slice(0,2).join(':')}
                    </div>
                </div>
                `;
            }).join('');
        }

        // --- ACTIONS ---
        function setFilter(type) {
            state.filter = type;
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            event.target.classList.add('active');
            renderLogs();
        }

        async function testSync() {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            
            Swal.fire({
                title: 'Sending Test Data...',
                didOpen: () => Swal.showLoading(),
                timer: 800,
                background: '#1e293b',
                color: '#fff'
            });

            try {
                const testData = {
                    type: ['dispensing', 'inventory', 'order'][Math.floor(Math.random() * 3)],
                    record_id: Math.floor(Math.random() * 9000) + 1000,
                    direction: Math.random() > 0.5 ? 'to_jhcis' : 'from_jhcis',
                    status: Math.random() > 0.85 ? 'error' : 'synced',
                    details: {
                        patient: ['John Doe', 'สมชาย ใจดี', 'Jane Smith'][Math.floor(Math.random() * 3)],
                        drug: ['Paracetamol 500mg', 'Amoxicillin 250mg', 'Omeprazole 20mg'][Math.floor(Math.random() * 3)],
                        qty: Math.floor(Math.random() * 100) + 10,
                        action: 'sync_test'
                    }
                };

                const response = await fetch('/api/realtime-sync/log', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken || ''
                    },
                    body: JSON.stringify(testData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // แสดงผลทันทีโดยไม่รอ SSE
                    testData.id = result.change_id;
                    testData.timestamp = new Date();
                    handleNewLog(testData);
                    
                    const Toast = Swal.mixin({
                        toast: true, position: 'top-end', showConfirmButton: false, timer: 2000,
                        background: '#334155', color: '#fff'
                    });
                    Toast.fire({
                        icon: 'success',
                        title: 'Test sync sent! Record #' + result.change_id
                    });
                } else {
                    throw new Error(result.message || 'Unknown error');
                }
            } catch(e) {
                console.error(e);
                Swal.fire({
                    icon: 'error',
                    title: 'Test Failed',
                    text: e.message,
                    background: '#1e293b',
                    color: '#fff'
                });
            }
        }

        async function toggleSyncSystem(checkbox) {
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                const res = await fetch('/realtime-sync/toggle', { 
                    method: 'POST',
                    headers: {
                        'X-CSRF-Token': csrfToken || ''
                    }
                });
                const data = await res.json();
                
                if(data.success) {
                    const Toast = Swal.mixin({
                        toast: true, position: 'top-end', showConfirmButton: false, timer: 3000,
                        background: '#334155', color: '#fff'
                    });
                    
                    Toast.fire({
                        icon: data.is_enabled ? 'success' : 'warning',
                        title: data.is_enabled ? 'System Resumed' : 'System Paused'
                    });
                    
                    if(!data.is_enabled) {
                        setOnline(false);
                        document.getElementById('statusText').innerText = "PAUSED";
                        document.getElementById('statusSub').innerText = "Sync System is manually disabled";
                    } else {
                        connectSSE(); // Reconnect
                    }
                }
            } catch(e) {
                checkbox.checked = !checkbox.checked; // Revert
                Swal.fire('Error', 'Failed to toggle system', 'error');
            }
        }

        async function checkSystemStatus() {
            try {
                const response = await fetch('/api/realtime-sync/settings');
                const data = await response.json();
                
                if (data.success) {
                    const toggle = document.getElementById('syncSw');
                    toggle.checked = data.is_enabled;
                    
                    if (!data.is_enabled) {
                        setOnline(false);
                        document.getElementById('statusText').innerText = "PAUSED";
                        document.getElementById('statusSub').innerText = "Sync System is manually disabled";
                        if(eventSource) eventSource.close();
                    }
                }
            } catch (e) {
                console.error('Failed to load settings:', e);
            }
        }

        function exportLogs() {
            if (state.logs.length === 0) {
                Swal.fire({
                    icon: 'info',
                    title: 'No Logs',
                    text: 'There are no logs to export yet.',
                    background: '#1e293b',
                    color: '#fff'
                });
                return;
            }
            const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(state.logs, null, 2));
            const downloadAnchorNode = document.createElement('a');
            downloadAnchorNode.setAttribute("href", dataStr);
            downloadAnchorNode.setAttribute("download", "sync_logs_" + new Date().getTime() + ".json");
            document.body.appendChild(downloadAnchorNode);
            downloadAnchorNode.click();
            downloadAnchorNode.remove();
        }

        function resetStats() {
            state.stats = { to: 0, from: 0, err: 0 };
            updateStatDisplay();
            state.logs = [];
            state.chartData = Array(15).fill(0);
            updateChart(0);
            renderLogs();
        }

        function toggleFullScreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
            } else {
                if (document.exitFullscreen) document.exitFullscreen();
            }
        }

        // --- MODAL ---
        function openDetail(log) {
            document.getElementById('modalTitle').innerText = (log.change_type || 'Event').toUpperCase();
            document.getElementById('modalId').innerText = '#' + (log.record_id || log.id);
            document.getElementById('modalTime').innerText = new Date(log.timestamp).toLocaleString();
            
            // Syntax Highlight JSON
            const jsonStr = JSON.stringify(log, null, 2);
            document.getElementById('modalContent').innerText = jsonStr;
            
            const modal = document.getElementById('detailModal');
            modal.style.display = 'flex';
            setTimeout(() => modal.classList.add('open'), 10);
        }

        function closeModal() {
            const modal = document.getElementById('detailModal');
            modal.classList.remove('open');
            setTimeout(() => modal.style.display = 'none', 300);
        }

        // Close on outside click
        document.getElementById('detailModal').addEventListener('click', (e) => {
            if(e.target === document.getElementById('detailModal')) closeModal();
        });

    </script>
</body>
</html>
