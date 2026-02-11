<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Drugmuk - ระบบบริหารคลังเวชภัณฑ์</title>
    <link rel="manifest" href="/manifest.json">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?= \App\Core\CSRF::metaTag() ?>
    
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --accent-color: #10b981;
            --text-color: #333;
            --bg-color: #f3f4f6;
            --sidebar-width: 280px;
            --header-height: 60px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Sarabun', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            padding-top: var(--header-height);
        }

        /* Mobile Header */
        .mobile-header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: var(--header-height);
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 15px;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .menu-toggle {
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 10px;
        }

        .brand-logo {
            color: white;
            font-size: 20px;
            font-weight: 700;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .user-profile-icon {
            width: 36px;
            height: 36px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        /* Sidebar Navigation */
        .sidebar {
            position: fixed;
            top: 0;
            left: -100%; /* Hidden by default */
            width: var(--sidebar-width);
            height: 100%;
            background: white;
            z-index: 1001;
            transition: left 0.3s ease-in-out;
            box-shadow: 4px 0 15px rgba(0,0,0,0.1);
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .sidebar.active {
            left: 0;
        }

        .sidebar-header {
            height: var(--header-height); /* Match header height */
            /* background: var(--primary-color); */
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            color: white;
        }
        
        .sidebar-header h3 {
            margin: 0;
            font-size: 1.2rem;
        }

        .close-sidebar {
            font-size: 24px;
            cursor: pointer;
            color: white;
        }

        .sidebar-menu {
            padding: 20px 0;
            flex-grow: 1;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 12px 25px;
            color: #555;
            text-decoration: none;
            font-size: 16px;
            border-left: 4px solid transparent;
            transition: all 0.2s;
        }

        .menu-item:hover, .menu-item.active {
            background: #f0f7ff;
            color: var(--primary-color);
            border-left-color: var(--primary-color);
        }

        .menu-item i {
            width: 30px;
            font-size: 18px;
            text-align: center;
            margin-right: 10px;
        }
        
        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid #eee;
            background: #f9fafb;
        }
        
        .btn-logout {
            display: block;
            width: 100%;
            padding: 10px;
            background: #ffebee;
            color: #d32f2f;
            text-align: center;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
        }

        /* Overlay */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000; /* Behind sidebar, above content */
            display: none;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .overlay.active {
            display: block;
            opacity: 1;
        }

        /* Responsive Adjustments */
        @media (min-width: 992px) {
            /* Desktop Styles Override Could go here if needed */
            /* But we want this header specifically for mobile/responsive design */
        }
    </style>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // PWA Service Worker Registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('SW Registered!', reg.scope))
                    .catch(err => console.log('SW Registration Failed', err));
            });
        }
    </script>
</head>
<body>
    <!-- Mobile Header -->

    <header class="mobile-header">
        <div class="menu-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </div>
        <a href="/" class="brand-logo">
            <i class="fas fa-pills"></i> Drugmuk
        </a>
        <a href="/profile" class="user-profile-icon">
            <?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?>
        </a>
    </header>

    <!-- Sidebar Navigation -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3>เมนูหลัก</h3>
            <div class="close-sidebar" onclick="toggleSidebar()">
                <i class="fas fa-times"></i>
            </div>
        </div>
        
        <nav class="sidebar-menu">
            <a href="/" class="menu-item <?= ($_SERVER['REQUEST_URI'] == '/' || $_SERVER['REQUEST_URI'] == '/dashboard') ? 'active' : '' ?>">
                <i class="fas fa-home"></i> หน้าหลัก
            </a>
            <a href="/analytics" class="menu-item <?= strpos($_SERVER['REQUEST_URI'], '/analytics') !== false ? 'active' : '' ?>">
                <i class="fas fa-chart-line"></i> Analytics Dashboard
            </a>
            <a href="/orders/auto-replenish" class="menu-item">
                <i class="fas fa-robot"></i> Smart Auto-PO
            </a>
            <a href="/purchasing" class="menu-item">
                <i class="fas fa-clipboard-list"></i> แผนจัดซื้อ
            </a>
            <a href="/orders" class="menu-item">
                <i class="fas fa-shopping-cart"></i> สั่งซื้อเวชภัณฑ์
            </a>
            <a href="/warehouse" class="menu-item">
                <i class="fas fa-warehouse"></i> คลังใหญ่
            </a>
            <a href="/subwarehouse" class="menu-item">
                <i class="fas fa-store"></i> คลังย่อย
            </a>
            <a href="/dispensing" class="menu-item">
                <i class="fas fa-prescription-bottle-alt"></i> จ่ายยา
            </a>
            <a href="/admin/jhcis/dashboard" class="menu-item">
                <i class="fas fa-hospital-alt"></i> JHCIS System
            </a>
            <a href="/admin/intelligence" class="menu-item">
                <i class="fas fa-brain"></i> Intelligence
            </a>
            <a href="/scan" class="menu-item">
                <i class="fas fa-qrcode"></i> สแกนบาร์โค้ด
            </a>
            <a href="/notifications" class="menu-item">
                <i class="fas fa-bell"></i> การแจ้งเตือน
            </a>
             <a href="/reports" class="menu-item">
                <i class="fas fa-file-alt"></i> รายงาน
            </a>
        </nav>
        
        <div class="sidebar-footer">
            <div style="font-size: 0.9em; color: #666; margin-bottom: 10px;">
                เข้าสู่ระบบโดย: <strong><?= $_SESSION['username'] ?? 'Guest' ?></strong>
            </div>
            <a href="/logout" class="btn-logout">
                <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
            </a>
        </div>
    </aside>

    <!-- Overlay for closing sidebar -->
    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
            
            // Prevent body scrolling when menu is open
            if (sidebar.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        }
        
        function toggleChat() {
            const chat = document.getElementById('chat-window');
            if (chat.style.display === 'none' || chat.style.display === '') {
                chat.style.display = 'flex';
                setTimeout(() => document.getElementById('chat-input').focus(), 100);
            } else {
                chat.style.display = 'none';
            }
        }

        let currentFile = null;

        function handleFileSelect(input) {
            if (input.files && input.files[0]) {
                currentFile = input.files[0];
                document.getElementById('file-preview').style.display = 'flex';
                document.getElementById('file-name').textContent = currentFile.name;
                document.getElementById('chat-input').focus();
            }
        }

        function clearFile() {
            document.getElementById('chat-file-input').value = '';
            document.getElementById('file-preview').style.display = 'none';
            currentFile = null;
        }

        function startVoiceProp() {
            if (!('webkitSpeechRecognition' in window)) {
                alert('เบราว์เซอร์ของคุณไม่รองรับการสั่งงานด้วยเสียง');
                return;
            }
            
            const btn = document.getElementById('voice-btn');
            
            // Visual Feedback: Pulse Red
            btn.style.color = '#ef4444';
            btn.classList.add('fa-beat-fade'); // More visible animation
            
            const recognition = new webkitSpeechRecognition();
            recognition.lang = 'th-TH';
            recognition.interimResults = false;
            recognition.maxAlternatives = 1;

            recognition.start();

            recognition.onresult = function(event) {
                const speechResult = event.results[0][0].transcript;
                const input = document.getElementById('chat-input');
                input.value = speechResult; // Replace for clean command
                
                // Auto Submit after short delay for user to realize
                setTimeout(() => {
                    sendMessage();
                }, 800);
            };

            recognition.onend = function() {
                // Reset UI
                btn.style.color = '#6b7280';
                btn.classList.remove('fa-beat-fade');
            };

            recognition.onerror = function(event) {
                console.error('Speech recognition error: ' + event.error);
                btn.style.color = '#6b7280';
                btn.classList.remove('fa-beat-fade');
            };
        }

        async function sendMessage() {
            const input = document.getElementById('chat-input');
            const message = input.value.trim();
            
            if (!message && !currentFile) return;

            // Add User Message
            let displayMsg = message;
            if (currentFile) {
                const icon = currentFile.type.startsWith('image/') ? '🖼️' : '📄';
                displayMsg += `<br><small style="color:#e0e7ff"><i>${icon} แนบไฟล์: ${currentFile.name}</i></small>`;
            }
            addMessage(displayMsg, 'user');
            
            input.value = '';
            const fileToSend = currentFile; // Capture current file
            clearFile(); // Clear UI immediately

            // Add Loading Indicator
            const loadingId = 'loading-' + Date.now();
            addMessage('<i class="fas fa-ellipsis-h"></i> กำลังวิเคราะห์...', 'ai', loadingId);

            try {
                // Get CSRF Token
                const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

                const formData = new FormData();
                formData.append('message', message);
                if (fileToSend) {
                    formData.append('file', fileToSend);
                }

                const response = await fetch('/ai/chat', {
                    method: 'POST',
                    headers: { 
                        'X-CSRF-Token': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                        // Content-Type is auto-set for FormData
                    },
                    body: formData
                });
                
                // Remove loading
                const loadingElement = document.getElementById(loadingId);
                if (loadingElement) loadingElement.parentNode.remove();

                if (!response.ok) {
                    try {
                        const errorData = await response.json();
                        addMessage('❌ ' + (errorData.message || 'Server Error'), 'ai');
                    } catch (e) {
                        addMessage('❌ เกิดข้อผิดพลาดทางเทคนิค (' + response.status + ')', 'ai');
                    }
                    return;
                }

                const data = await response.json();
                // Pass widget config if exists
                addMessage(data.message || data.reply || 'ไม่เข้าใจคำถามครับ', 'ai', null, data.chart_data, data.widget);

            } catch (error) {
                const loadingElement = document.getElementById(loadingId);
                if (loadingElement) loadingElement.parentNode.remove();
                addMessage('❌ การเชื่อมต่อผิดพลาด: ' + error.message, 'ai');
            }
        }

        // Initialize Speech - Auto OFF by default
        let autoSpeakEnabled = false;

        function toggleAutoSpeak() {
            autoSpeakEnabled = !autoSpeakEnabled;
            const icon = document.getElementById('speaker-icon');
            if (autoSpeakEnabled) {
                icon.className = 'fas fa-volume-up';
                icon.style.opacity = '1';
                speakText("เปิดระบบเสียง");
            } else {
                icon.className = 'fas fa-volume-mute';
                icon.style.opacity = '0.7';
                window.speechSynthesis.cancel();
            }
            // Close menu if open
            document.getElementById('ai-options-menu').style.display = 'none';
        }

        function toggleOptionsMenu() {
            const menu = document.getElementById('ai-options-menu');
            menu.style.display = (menu.style.display === 'none' || !menu.style.display) ? 'block' : 'none';
        }

        function speakText(text) {
            if (!autoSpeakEnabled) return;
            if ('speechSynthesis' in window) {
                window.speechSynthesis.cancel(); // Stop current
                // remove HTML tags for speech
                const rawText = text.replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ');
                const utterance = new SpeechSynthesisUtterance(rawText);
                utterance.lang = 'th-TH'; // Thai language
                utterance.rate = 1.0;
                window.speechSynthesis.speak(utterance);
            }
        }

        function addMessage(html, sender, id = null, chartConfig = null, widgetConfig = null) {
            const container = document.getElementById('chat-messages');
            const wrapper = document.createElement('div');
            wrapper.style.marginBottom = '10px';
            wrapper.style.textAlign = sender === 'user' ? 'right' : 'left';
            
            const bubble = document.createElement('div');
            bubble.style.display = 'inline-block';
            bubble.style.padding = '10px 15px';
            bubble.style.borderRadius = sender === 'user' ? '15px 15px 2px 15px' : '15px 15px 15px 2px';
            bubble.style.background = sender === 'user' ? '#667eea' : '#f3f4f6';
            bubble.style.color = sender === 'user' ? 'white' : '#1f2937';
            bubble.style.maxWidth = '85%';
            bubble.style.wordWrap = 'break-word';
            bubble.style.fontSize = '14px';
            bubble.style.textAlign = 'left';
            if (id) bubble.id = id;
            
            bubble.innerHTML = html;
            
            // Check for Chart
            if (chartConfig) {
                const chartContainer = document.createElement('div');
                chartContainer.style.width = '100%';
                chartContainer.style.height = '150px';
                chartContainer.style.marginTop = '10px';
                chartContainer.style.background = 'white';
                chartContainer.style.borderRadius = '8px';
                chartContainer.style.padding = '5px';
                
                const canvas = document.createElement('canvas');
                canvas.id = 'chart-' + Date.now();
                chartContainer.appendChild(canvas);
                bubble.appendChild(chartContainer);
                
                // Render async
                setTimeout(() => renderChart(canvas.id, chartConfig), 100);
            }

            // Check for Interactive Widget
            if (widgetConfig) {
                const widgetContainer = document.createElement('div');
                widgetContainer.className = 'ai-widget-container';
                widgetContainer.style.marginTop = '10px';
                renderWidget(widgetContainer, widgetConfig);
                bubble.appendChild(widgetContainer);
            }

            wrapper.appendChild(bubble);
            container.appendChild(wrapper);
            container.scrollTop = container.scrollHeight;

            if (sender === 'ai' && !id && autoSpeakEnabled) {
                speakText(html);
            }
        }
        
        function renderWidget(container, config) {
            if (config === 'mood_tracker') {
                container.innerHTML = `
                    <div style="display:flex; justify-content:space-between; gap:5px; margin-top:5px;">
                        <button onclick="sendQuick('รู้สึกแย่มาก 😫')" style="border:none; background:none; font-size:24px; cursor:pointer; transition:transform 0.2s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'">😫</button>
                        <button onclick="sendQuick('เครียดนิดหน่อย 😕')" style="border:none; background:none; font-size:24px; cursor:pointer; transition:transform 0.2s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'">😕</button>
                        <button onclick="sendQuick('เฉยๆ 😐')" style="border:none; background:none; font-size:24px; cursor:pointer; transition:transform 0.2s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'">😐</button>
                        <button onclick="sendQuick('อารมณ์ดี 🙂')" style="border:none; background:none; font-size:24px; cursor:pointer; transition:transform 0.2s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'">🙂</button>
                        <button onclick="sendQuick('แฮปปี้สุดๆ 😄')" style="border:none; background:none; font-size:24px; cursor:pointer; transition:transform 0.2s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'">😄</button>
                    </div>
                `;
            } else if (config === 'mukdahan_places') {
                container.innerHTML = `
                    <div style="display:flex; gap:10px; overflow-x:auto; padding-bottom:5px; scrollbar-width:none; -ms-overflow-style:none;">
                        <!-- Ho Kaeo Card -->
                        <div style="min-width:140px; background:white; border-radius:8px; overflow:hidden; border:1px solid #e5e7eb; flex-shrink:0;">
                            <div style="height:80px; background:#e0e7ff; display:flex; align-items:center; justify-content:center; color:#667eea; font-size:30px;">🗼</div>
                            <div style="padding:8px;">
                                <div style="font-weight:bold; font-size:12px; margin-bottom:2px;">หอแก้วมุกดาหาร</div>
                                <div style="color:#f59e0b; font-size:10px;">⭐️ 4.8 (Landmark)</div>
                                <button onclick="sendQuick('ขอรายละเอียดหอแก้วมุกดาหาร')" style="margin-top:5px; width:100%; padding:4px; font-size:11px; background:#667eea; color:white; border:none; border-radius:4px; cursor:pointer;">ดูข้อมูล</button>
                            </div>
                        </div>

                        <!-- Phu Manorom Card -->
                        <div style="min-width:140px; background:white; border-radius:8px; overflow:hidden; border:1px solid #e5e7eb; flex-shrink:0;">
                            <div style="height:80px; background:#d1fae5; display:flex; align-items:center; justify-content:center; color:#10b981; font-size:30px;">🐍</div>
                            <div style="padding:8px;">
                                <div style="font-weight:bold; font-size:12px; margin-bottom:2px;">วัดรอยพระพุทธบาทภูมโนรมย์</div>
                                <div style="color:#f59e0b; font-size:10px;">⭐️ 4.9 (Must Visit)</div>
                                <button onclick="sendQuick('ขอรายละเอียดวัดภูมโนรมย์')" style="margin-top:5px; width:100%; padding:4px; font-size:11px; background:#667eea; color:white; border:none; border-radius:4px; cursor:pointer;">ดูข้อมูล</button>
                            </div>
                        </div>

                        <!-- Indochina Market -->
                        <div style="min-width:140px; background:white; border-radius:8px; overflow:hidden; border:1px solid #e5e7eb; flex-shrink:0;">
                            <div style="height:80px; background:#ffedd5; display:flex; align-items:center; justify-content:center; color:#f97316; font-size:30px;">🛍️</div>
                            <div style="padding:8px;">
                                <div style="font-weight:bold; font-size:12px; margin-bottom:2px;">ตลาดอินโดจีน</div>
                                <div style="color:#f59e0b; font-size:10px;">⭐️ 4.5 (Shopping)</div>
                                <button onclick="sendQuick('ขอรายละเอียดตลาดอินโดจีน')" style="margin-top:5px; width:100%; padding:4px; font-size:11px; background:#667eea; color:white; border:none; border-radius:4px; cursor:pointer;">ดูข้อมูล</button>
                            </div>
                        </div>
                    </div>
                `;
            } else if (config === 'symptom_triage') {
                container.innerHTML = `
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:8px; margin-top:5px;">
                        <button onclick="sendQuick('มีอาการปวดศีรษะ ไมเกรน')" style="background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:10px; display:flex; align-items:center; gap:8px; cursor:pointer; text-align:left;">
                            <span style="font-size:20px;">🤕</span> <span style="font-size:13px;">ปวดศีรษะ</span>
                        </button>
                        <button onclick="sendQuick('มีไข้ ตัวร้อน หนาวสั่น')" style="background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:10px; display:flex; align-items:center; gap:8px; cursor:pointer; text-align:left;">
                            <span style="font-size:20px;">🤒</span> <span style="font-size:13px;">ไข้หวัด</span>
                        </button>
                        <button onclick="sendQuick('ปวดท้อง จุกเสียด แน่นท้อง')" style="background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:10px; display:flex; align-items:center; gap:8px; cursor:pointer; text-align:left;">
                            <span style="font-size:20px;">🤢</span> <span style="font-size:13px;">ปวดท้อง</span>
                        </button>
                        <button onclick="sendQuick('เจ็บคอ ไอ เสมหะ')" style="background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:10px; display:flex; align-items:center; gap:8px; cursor:pointer; text-align:left;">
                            <span style="font-size:20px;">😷</span> <span style="font-size:13px;">เจ็บคอ/ไอ</span>
                        </button>
                        <button onclick="sendQuick('ผื่นคัน ลมพิษ')" style="background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:10px; display:flex; align-items:center; gap:8px; cursor:pointer; text-align:left;">
                            <span style="font-size:20px;">🧴</span> <span style="font-size:13px;">ผื่นแพ้</span>
                        </button>
                        <button onclick="sendQuick('ปวดกล้ามเนื้อ ออฟฟิศซินโดรม')" style="background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:10px; display:flex; align-items:center; gap:8px; cursor:pointer; text-align:left;">
                            <span style="font-size:20px;">🦵</span> <span style="font-size:13px;">ปวดเมื่อย</span>
                        </button>
                    </div>
                `;
            } else if (config.widget === 'stock_alert') {
                const itemsHtml = config.data.map(item => `
                    <div style="background:#fef2f2; border:1px solid #fee2e2; border-radius:8px; padding:12px; margin-bottom:8px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px;">
                            <strong style="color:#b91c1c; font-size:14px;">${item.name}</strong>
                            <span style="background:#fee2e2; color:#b91c1c; font-size:10px; padding:2px 6px; border-radius:10px; font-weight:bold;">CRITICAL</span>
                        </div>
                        <div style="font-size:13px; color:#7f1d1d; margin-bottom:10px;">
                            เหลือเพียง: <b>${item.qty}</b> ${item.unit}
                        </div>
                        <a href="/orders/create?drug_id=${item.id}" target="_blank" style="display:block; text-align:center; background:#ef4444; color:white; padding:8px; border-radius:6px; text-decoration:none; font-size:13px; font-weight:bold;">
                            <i class="fas fa-cart-plus"></i> สั่งซื้อด่วน
                        </a>
                    </div>
                `).join('');
                
                container.innerHTML = `<div style="margin-top:10px;">${itemsHtml}</div>`;
            } else if (config.widget === 'drug_label') {
                const data = config.data;
                const iconsHtml = data.icons.map(icon => {
                    let emoji = '';
                    let color = '';
                    if (icon === 'morning') { emoji = '☀️'; color = '#f59e0b'; }
                    if (icon === 'noon') { emoji = '🍛'; color = '#ef4444'; }
                    if (icon === 'evening') { emoji = '🌆'; color = '#3b82f6'; }
                    if (icon === 'night') { emoji = '🌙'; color = '#6366f1'; }
                    return `<span style="font-size:24px; padding:5px;">${emoji}</span>`;
                }).join('');

                let timingBadge = '';
                if (data.timing === 'before_meal') timingBadge = '<span style="background:#fef3c7; color:#d97706; padding:4px 8px; border-radius:12px; font-size:12px;">ก่อนอาหาร</span>';
                if (data.timing === 'after_meal') timingBadge = '<span style="background:#d1fae5; color:#059669; padding:4px 8px; border-radius:12px; font-size:12px;">หลังอาหาร</span>';
                if (data.timing === 'with_meal') timingBadge = '<span style="background:#fee2e2; color:#b91c1c; padding:4px 8px; border-radius:12px; font-size:12px;">พร้อมอาหาร</span>';

                container.innerHTML = `
                    <div style="background:white; border:1px solid #e5e7eb; border-radius:12px; padding:15px; margin-top:10px; box-shadow:0 4px 6px rgba(0,0,0,0.05);">
                        <div style="text-align:center; padding-bottom:10px; border-bottom:1px solid #f3f4f6;">
                            <h3 style="margin:0; color:#1f2937; font-size:18px;">${data.name}</h3>
                            <div style="color:#6b7280; font-size:12px;">${data.generic_name || ''}</div>
                        </div>
                        <div style="padding:15px 0; text-align:center;">
                            <div style="margin-bottom:10px;">${iconsHtml}</div>
                            <div style="font-size:16px; color:#374151; margin-bottom:10px;">
                                ${data.instruction}
                            </div>
                            <div>${timingBadge}</div>
                        </div>
                        <div style="background:#f9fafb; padding:10px; border-radius:8px; margin-top:10px; text-align:center;">
                            <button onclick="alert('ส่งไปยังเครื่องพิมพ์ฉลาก (Simulation)')" style="background:#6366f1; color:white; border:none; padding:8px 16px; border-radius:6px; cursor:pointer; font-size:13px;">
                                🖨️ พิมพ์ฉลาก
                            </button>
                        </div>
                    </div>
                `;
            } else if (config.widget === 'chart') {
                const canvasId = 'chart-' + Date.now();
                container.innerHTML = `
                    <div style="background:white; border:1px solid #e5e7eb; border-radius:12px; padding:15px; margin-top:10px; box-shadow:0 4px 6px rgba(0,0,0,0.05);">
                        <div style="text-align:center; padding-bottom:10px; border-bottom:1px solid #f3f4f6; margin-bottom:10px;">
                            <h3 style="margin:0; color:#1f2937; font-size:16px;">📊 แนวโน้มยอดขาย</h3>
                        </div>
                        <div style="height: 200px;">
                            <canvas id="${canvasId}"></canvas>
                        </div>
                    </div>
                `;
                // Render chart after DOM update
                setTimeout(() => renderChart(canvasId, config.data), 100);
            } else if (config.widget === 'refill_alert') {
                const itemsHtml = config.data.map(pt => `
                    <div style="background:white; border:1px solid #e5e7eb; border-radius:8px; padding:10px; margin-bottom:8px; box-shadow:0 1px 2px rgba(0,0,0,0.05);">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:5px;">
                            <div>
                                <div style="font-weight:bold; color:#1f2937; font-size:14px;">${pt.patient_name || 'HN ' + pt.hn}</div>
                                <div style="font-size:11px; color:#6b7280;">รับยาล่าสุด: ${new Date(pt.last_visit).toLocaleDateString('th-TH')}</div>
                            </div>
                            <span style="background:#fef3c7; color:#d97706; font-size:10px; padding:2px 6px; border-radius:10px; white-space:nowrap;">ครบกำหนด</span>
                        </div>
                        <div style="font-size:12px; color:#374151; margin-bottom:8px; border-top:1px dashed #f3f4f6; padding-top:5px;">
                            💊 ${pt.drugs || 'ไม่ระบุรายการยา'}
                        </div>
                        <button onclick="alert('โทรหาคุณ ${pt.patient_name || pt.hn}')" style="width:100%; background:#ecfdf5; color:#059669; border:1px solid #d1fae5; padding:6px; border-radius:6px; cursor:pointer; font-size:12px; display:flex; align-items:center; justify-content:center; gap:5px;">
                            <i class="fas fa-phone-alt"></i> ติดตามคนไข้
                        </button>
                    </div>
                `).join('');
                
                container.innerHTML = `<div style="margin-top:10px;">${itemsHtml}</div>`;
            } else if (config.widget === 'stock_transfer') {
                const branchHtml = config.data.branches.map(br => `
                    <div style="background:white; border-left:4px solid ${br.color}; border-radius:4px; padding:10px; margin-bottom:8px; display:flex; justify-content:space-between; align-items:center; box-shadow:0 1px 2px rgba(0,0,0,0.05);">
                        <div>
                            <div style="font-weight:bold; font-size:13px; color:#374151;">${br.name}</div>
                            <div style="font-size:11px; color:#9ca3af;">สต็อคคงเหลือ</div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-weight:bold; font-size:16px; color:#1f2937;">${br.qty}</div>
                            <small>${config.data.unit}</small>
                        </div>
                    </div>
                `).join('');

                container.innerHTML = `
                    <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:12px; padding:15px; margin-top:10px;">
                        <h3 style="margin:0 0 10px 0; font-size:15px; color:#111827;">📦 เช็คสต็อคข้ามสาขา</h3>
                        <div style="margin-bottom:10px; font-weight:bold; color:#4b5563;">ยา: ${config.data.drug_name}</div>
                        ${branchHtml}
                        <button onclick="alert('สร้างใบขอโอนของ (Stock Transfer Request) - Demo')" style="width:100%; background:#3b82f6; color:white; border:none; padding:8px; border-radius:6px; margin-top:5px; cursor:pointer;">
                            <i class="fas fa-exchange-alt"></i> ขอโอนของ
                        </button>
                    </div>
                `;
            } else if (config.widget === 'po_confirm') {
                container.innerHTML = `
                    <div style="background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius:12px; padding:20px; margin-top:10px; color:white;">
                        <h3 style="margin:0 0 15px 0; font-size:16px; display:flex; align-items:center; gap:8px;">
                            <i class="fas fa-shopping-cart"></i> ยืนยันการสั่งซื้อ
                        </h3>
                        <div style="background:rgba(255,255,255,0.15); border-radius:8px; padding:15px; margin-bottom:15px;">
                            <div style="font-size:18px; font-weight:bold; margin-bottom:10px;">${config.data.drug_name}</div>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; font-size:13px;">
                                <div>
                                    <div style="opacity:0.8;">จำนวน</div>
                                    <div style="font-weight:bold; font-size:16px;">${config.data.qty} ${config.data.unit}</div>
                                </div>
                                <div>
                                    <div style="opacity:0.8;">ราคา/หน่วย</div>
                                    <div style="font-weight:bold; font-size:16px;">฿${config.data.unit_price}</div>
                                </div>
                            </div>
                            <div style="border-top:1px dashed rgba(255,255,255,0.3); margin-top:10px; padding-top:10px;">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <span style="font-size:14px;">ยอดรวม</span>
                                    <span style="font-size:20px; font-weight:bold;">฿${config.data.total_price}</span>
                                </div>
                            </div>
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                            <button onclick="alert('ยกเลิกการสั่งซื้อ')" style="background:rgba(255,255,255,0.2); color:white; border:1px solid rgba(255,255,255,0.3); padding:10px; border-radius:6px; cursor:pointer; font-size:13px;">
                                <i class="fas fa-times"></i> ยกเลิก
                            </button>
                            <button onclick="window.location.href='/orders/create?drug_id=${config.data.drug_id}&qty=${config.data.qty.replace(/,/g, '')}'" style="background:white; color:#667eea; border:none; padding:10px; border-radius:6px; cursor:pointer; font-weight:bold; font-size:13px;">
                                <i class="fas fa-check"></i> ยืนยันสั่งซื้อ
                            </button>
                        </div>
                    </div>
                `;
            }
        }

        function renderChart(canvasId, config) {
            const ctx = document.getElementById(canvasId).getContext('2d');
            new Chart(ctx, {
                type: config.type || 'bar',
                data: config.data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { font: { size: 10 } } },
                        x: { ticks: { font: { size: 10 } } }
                    }
                }
            });
        }
        
        function sendQuick(msg) {
            document.getElementById('chat-input').value = msg;
            sendMessage();
        }


        function clearChat() {
            const container = document.getElementById('chat-messages');
            // Reset to initial state
            container.innerHTML = `
                <div style="margin-bottom: 15px; text-align: center; color: #9ca3af; font-size: 12px;">วันนี้ ${new Date().toLocaleDateString('th-TH', { hour: '2-digit', minute: '2-digit' })}</div>
                <div style="margin-bottom: 10px; text-align: left;">
                    <div style="display: inline-block; padding: 12px; border-radius: 15px 15px 15px 2px; background: #f3f4f6; color: #1f2937; max-width: 85%; font-size: 14px;">
                        สวัสดีครับ! 👋 ผมคือ AI Data Analyst 🕵️‍♂️<br>
                        พร้อมวิเคราะห์ยอดขายและ Dead Stock แล้วครับ!
                    </div>
                </div>
                <div style="margin-bottom: 10px; text-align: left;">
                    <div style="display: inline-block; padding: 12px; border-radius: 15px 15px 15px 2px; background: #f3f4f6; color: #1f2937; max-width: 85%; font-size: 14px;">
                        ลองกดปุ่มด้านล่างเพื่อเริ่มวิเคราะห์ข้อมูลได้เลย 👇
                    </div>
                </div>`;
        }

        // Handle Enter key
        document.addEventListener('DOMContentLoaded', function() {
            const input = document.getElementById('chat-input');
            if (input) {
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') sendMessage();
                });
            }
        });
    </script>
    
    <style>
        /* Add to existing styles */
        .chat-btn-action {
            display: inline-block;
            margin-top: 5px;
            padding: 4px 10px;
            background: #f59e0b;
            color: white !important;
            border-radius: 5px;
            text-decoration: none;
            font-size: 12px;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.2s;
        }
        .chat-btn-action:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.15);
            opacity: 0.9;
        }
    </style>
    
    <!-- AI Chatbot Widget -->
    <div id="ai-chatbot" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999; font-family: 'Sarabun', sans-serif;">
        <button onclick="toggleChat()" style="width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.2); cursor: pointer; font-size: 24px; transition: transform 0.2s;">
            <i class="fas fa-robot"></i>
        </button>
        
        <div id="chat-window" style="display: none; position: absolute; bottom: 80px; right: 0; width: 340px; height: 550px; background: white; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); overflow: hidden; flex-direction: column; border: 1px solid #e5e7eb;">
            
            <!-- Header -->
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 15px; color: white; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <button onclick="toggleChat()" style="background: none; border: none; color: white; cursor: pointer; font-size: 20px;">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div style="cursor:pointer;" onclick="toggleAutoSpeak()" title="แตะเพื่อเปิด/ปิดเสียงพูด">
                        <div style="font-weight: bold; font-size: 16px;">
                            Drugmuk AI <i class="fas fa-volume-mute" id="speaker-icon" style="font-size:14px; opacity:0.7;"></i>
                        </div>
                        <div style="font-size: 12px; opacity: 0.9;">Analyst Online</div>
                    </div>
                </div>
                <div style="display: flex; gap: 10px; position:relative;">
                    <span onclick="toggleOptionsMenu()" style="cursor: pointer; padding: 5px; font-size: 16px; opacity: 0.9;"><i class="fas fa-ellipsis-v"></i></span>
                    
                    <!-- Dropdown Options (Expanded Convenience Center) -->
                    <div id="ai-options-menu" style="display:none; position:absolute; top:35px; right:0; background:white; color:#333; border-radius:12px; box-shadow:0 10px 25px rgba(0,0,0,0.15); width:200px; z-index:100; overflow:hidden; border:1px solid #e5e7eb;">
                        
                        <!-- AI Tools -->
                        <div style="padding:8px 15px; background:#f9fafb; font-size:11px; font-weight:bold; color:#9ca3af; text-transform:uppercase; letter-spacing:0.5px;">เครื่องมือ AI</div>
                        <div onclick="toggleAutoSpeak()" style="padding:10px 15px; border-bottom:1px solid #f3f4f6; cursor:pointer; font-size:13px; display:flex; align-items:center; gap:10px; transition:background 0.2s;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='white'">
                            <i class="fas fa-volume-up" style="color:#667eea; width:20px; text-align:center;"></i> เปิด/ปิดเสียง
                        </div>
                        <div onclick="document.getElementById('chat-file-input').click(); toggleOptionsMenu()" style="padding:10px 15px; border-bottom:1px solid #f3f4f6; cursor:pointer; font-size:13px; display:flex; align-items:center; gap:10px; transition:background 0.2s;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='white'">
                            <i class="fas fa-file-upload" style="color:#10b981; width:20px; text-align:center;"></i> แนบไฟล์ภาพ
                        </div>

                        <!-- Quick Nav -->
                        <div style="padding:8px 15px; background:#f9fafb; font-size:11px; font-weight:bold; color:#9ca3af; text-transform:uppercase; letter-spacing:0.5px;">เมนูลัดรวดเร็ว</div>
                        <a href="/dashboard" style="text-decoration:none; color:inherit; display:block;">
                            <div style="padding:10px 15px; border-bottom:1px solid #f3f4f6; cursor:pointer; font-size:13px; display:flex; align-items:center; gap:10px; transition:background 0.2s;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='white'">
                                <i class="fas fa-home" style="color:#3b82f6; width:20px; text-align:center;"></i> หน้าหลัก
                            </div>
                        </a>
                        <a href="/dispensing" style="text-decoration:none; color:inherit; display:block;">
                            <div style="padding:10px 15px; border-bottom:1px solid #f3f4f6; cursor:pointer; font-size:13px; display:flex; align-items:center; gap:10px; transition:background 0.2s;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='white'">
                                <i class="fas fa-pills" style="color:#8b5cf6; width:20px; text-align:center;"></i> ห้องจ่ายยา
                            </div>
                        </a>
                        <a href="/warehouse" style="text-decoration:none; color:inherit; display:block;">
                            <div style="padding:10px 15px; border-bottom:1px solid #f3f4f6; cursor:pointer; font-size:13px; display:flex; align-items:center; gap:10px; transition:background 0.2s;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='white'">
                                <i class="fas fa-boxes" style="color:#f59e0b; width:20px; text-align:center;"></i> เช็คสต็อค
                            </div>
                        </a>

                        <!-- Emergency -->
                        <div style="padding:8px 15px; background:#f9fafb; font-size:11px; font-weight:bold; color:#9ca3af; text-transform:uppercase; letter-spacing:0.5px;">ฉุกเฉิน</div>
                        <a href="tel:1669" style="text-decoration:none; color:inherit; display:block;">
                            <div style="padding:10px 15px; border-bottom:1px solid #f3f4f6; cursor:pointer; font-size:13px; display:flex; align-items:center; gap:10px; color:#ef4444; background:#fff5f5;">
                                <i class="fas fa-ambulance" style="width:20px; text-align:center;"></i> โทร 1669
                            </div>
                        </a>

                        <!-- Actions -->
                        <div onclick="sendQuick('คุณทำอะไรได้บ้าง'); toggleOptionsMenu()" style="padding:10px 15px; border-bottom:1px solid #f3f4f6; cursor:pointer; font-size:13px; display:flex; align-items:center; gap:10px; color:#6b7280;">
                            <i class="fas fa-question-circle" style="width:20px; text-align:center;"></i> วิธีใช้งาน
                        </div>
                        <div onclick="clearChat(); toggleOptionsMenu()" style="padding:10px 15px; cursor:pointer; font-size:13px; display:flex; align-items:center; gap:10px; color:#ef4444;">
                            <i class="fas fa-trash-alt" style="width:20px; text-align:center;"></i> ล้างประวัติ
                        </div>
                    </div>
                </div>
            </div>

            <!-- Messages Area -->
            <div id="chat-messages" style="flex-grow: 1; padding: 15px; overflow-y: auto; background: #ffffff;">
                <!-- Welcome Message -->
                <div style="margin-bottom: 15px; text-align: center; color: #9ca3af; font-size: 12px;">วันนี้ <?= date('d/m/Y H:i') ?></div>
                
                <div style="margin-bottom: 10px; text-align: left;">
                    <div style="display: inline-block; padding: 12px; border-radius: 15px 15px 15px 2px; background: #f3f4f6; color: #1f2937; max-width: 85%; font-size: 14px;">
                        สวัสดีครับ! 👋 ผมคือ AI Data Analyst 🕵️‍♂️<br>
                        ผมสามารถช่วยวิเคราะห์ยอดขายและ Dead Stock ได้ครับ
                    </div>
                </div>
                
                <div style="margin-bottom: 10px; text-align: left;">
                    <div style="display: inline-block; padding: 12px; border-radius: 15px 15px 15px 2px; background: #f3f4f6; color: #1f2937; max-width: 85%; font-size: 14px;">
                        ลองกดปุ่มด้านล่าง 👇 หรือแนบภาพ/เอกสารเพื่อวิเคราะห์
                    </div>
                </div>
            </div>

            <!-- Quick Chips Area -->
            <div style="padding: 10px 15px; border-top: 1px solid #f3f4f6; background: #fff; display: flex; gap: 8px; overflow-x: auto; white-space: nowrap; scrollbar-width: none; -ms-overflow-style: none;">
                <button onclick="sendQuick('ยอดขายเดือนนี้')" style="border: 1px solid #e5e7eb; background: #d1fae5; color: #065f46; padding: 6px 12px; border-radius: 15px; font-size: 12px; cursor: pointer; transition: all 0.2s;">💰 ยอดขาย</button>
                <button onclick="sendQuick('เช็ค Dead Stock')" style="border: 1px solid #e5e7eb; background: #fee2e2; color: #991b1b; padding: 6px 12px; border-radius: 15px; font-size: 12px; cursor: pointer; transition: all 0.2s;">💀 Dead Stock</button>
                <button onclick="sendQuick('สรุปยอดวันนี้')" style="border: 1px solid #e5e7eb; background: #eff6ff; color: #1e40af; padding: 6px 12px; border-radius: 15px; font-size: 12px; cursor: pointer; transition: all 0.2s;">📅 สรุปวันนี้</button>
                <button onclick="sendQuick('ใครใช้ยา Amoxy บ้าง')" style="border: 1px solid #e5e7eb; background: #f9fafb; color: #4b5563; padding: 6px 12px; border-radius: 15px; font-size: 12px; cursor: pointer; transition: all 0.2s;">🕵️‍♂️ ใครใช้ยา</button>
            </div>

            <!-- File Preview Area -->
            <div id="file-preview" style="display: none; padding: 8px 15px; background: #eff6ff; border-top: 1px solid #dbeafe; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: #1e40af; overflow: hidden;">
                    <i class="fas fa-file-alt"></i>
                    <span id="file-name" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px;">filename.jpg</span>
                </div>
                <button onclick="clearFile()" style="background: none; border: none; color: #ef4444; cursor: pointer;"><i class="fas fa-times"></i></button>
            </div>

            <!-- Input Area -->
            <div style="padding: 10px; border-top: 1px solid #f3f4f6; display: flex; gap: 8px; background: white; align-items: center;">
                <input type="file" id="chat-file-input" style="display: none;" onchange="handleFileSelect(this)">
                
                <button onclick="document.getElementById('chat-file-input').click()" title="แนบภาพ/เอกสาร" style="background: none; border: none; color: #6b7280; cursor: pointer; font-size: 18px; padding: 5px;">
                    <i class="fas fa-paperclip"></i>
                </button>
                
                <div style="flex-grow: 1; position: relative; display: flex; align-items: center;">
                    <input type="text" id="chat-input" placeholder="พิมพ์คำถาม หรือใช้เสียง..." style="width: 100%; border: 1px solid #e5e7eb; padding: 10px 35px 10px 15px; border-radius: 25px; outline: none; font-size: 14px; background: #f9fafb;">
                    <button id="voice-btn" onclick="startVoiceProp()" style="position: absolute; right: 5px; background: none; border: none; color: #6b7280; cursor: pointer; width: 30px; height: 30px; border-radius: 50%;">
                        <i class="fas fa-microphone"></i>
                    </button>
                </div>

                <button onclick="sendMessage()" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 5px rgba(102, 126, 234, 0.3); flex-shrink: 0;">
                    <i class="fas fa-paper-plane" style="font-size: 16px; margin-left: -2px;"></i>
                </button>
            </div>
        </div>
    </div>

    <main class="container" style="padding-top: 20px;">
