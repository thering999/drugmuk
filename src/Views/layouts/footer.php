    </main>
    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> Drugmuk - Pharmaceutical Inventory Management System</p>
    </footer>

    <?php 
    $aiChatUrl = \App\Core\Config::get('AI_CHAT_URL');
    if ($aiChatUrl): 
    ?>
    <!-- Smart AI Assistant Widget -->
    <div id="ai-widget-container" style="position: fixed; bottom: 30px; right: 30px; z-index: 9999; display: flex; flex-direction: column; align-items: flex-end; gap: 15px;">
        
        <!-- Chat Window (Clean Glassmorphism) -->
        <div id="ai-chat-window" style="
            display: none; 
            width: 400px; 
            height: 600px; 
            background: rgba(255, 255, 255, 0.95); 
            backdrop-filter: blur(10px); 
            border-radius: 20px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.15); 
            border: 1px solid rgba(255,255,255,0.2); 
            overflow: hidden; 
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); 
            transform-origin: bottom right; 
            opacity: 0; 
            transform: scale(0.9) translateY(20px);
        ">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; color: white;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 35px; height: 35px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div>
                        <h4 style="margin: 0; font-size: 16px; font-weight: 600;">Drugmuk AI</h4>
                        <span style="font-size: 11px; opacity: 0.8;">ผู้ช่วยอัจฉริยะ 24 ชม.</span>
                    </div>
                </div>
                <button onclick="toggleAiChat()" style="background: none; border: none; color: white; cursor: pointer; opacity: 0.8; font-size: 18px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <iframe src="<?= htmlspecialchars($aiChatUrl) ?>" style="width: 100%; height: calc(100% - 65px); border: none;"></iframe>
        </div>

        <!-- Floating Action Button -->
        <button id="ai-fab-btn" onclick="toggleAiChat()" style="
            width: 60px; 
            height: 60px; 
            border-radius: 30px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            border: none; 
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4); 
            cursor: pointer; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 24px; 
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
        ">
            <i class="fas fa-comment-medical"></i>
            <span class="pulse-ring" style="position: absolute; width: 100%; height: 100%; border-radius: 50%; border: 2px solid #667eea; animation: pulse 2s infinite;"></span>
        </button>
    </div>

    <style>
        @keyframes pulse { 0% { transform: scale(1); opacity: 0.7; } 100% { transform: scale(1.6); opacity: 0; } }
        #ai-fab-btn:hover { transform: scale(1.1); box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5); }
    </style>

    <script>
        function toggleAiChat() {
            const chatWindow = document.getElementById('ai-chat-window');
            const fabBtn = document.getElementById('ai-fab-btn');
            
            if (chatWindow.style.display === 'none') {
                chatWindow.style.display = 'block';
                // Trigger reflow
                chatWindow.offsetHeight; 
                chatWindow.style.opacity = '1';
                chatWindow.style.transform = 'scale(1) translateY(0)';
                fabBtn.innerHTML = '<i class="fas fa-chevron-down"></i>';
                fabBtn.querySelector('.pulse-ring').style.display = 'none';
            } else {
                chatWindow.style.opacity = '0';
                chatWindow.style.transform = 'scale(0.9) translateY(20px)';
                setTimeout(() => {
                    chatWindow.style.display = 'none';
                }, 300);
                fabBtn.innerHTML = '<i class="fas fa-comment-medical"></i>';
            }
        }
    </script>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
    <script src="/js/performance.js"></script>
    <script src="/js/enhancements.js"></script>
    <!-- Font Awesome for Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</body>
</html>
