<!-- Real-time Notification Widget -->
<style>
.notification-widget {
    position: fixed;
    top: 70px;
    right: 20px;
    width: 380px;
    max-height: 600px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    z-index: 1000;
    display: none;
    overflow: hidden;
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

.notification-widget.active {
    display: block;
}

.notification-header {
    padding: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notification-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
}

.notification-badge {
    background: #ff4757;
    color: white;
    border-radius: 12px;
    padding: 2px 8px;
    font-size: 12px;
    font-weight: 700;
    margin-left: 8px;
}

.notification-close {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
}

.notification-close:hover {
    background: rgba(255,255,255,0.3);
    transform: rotate(90deg);
}

.notification-tabs {
    display: flex;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
}

.notification-tab {
    flex: 1;
    padding: 12px;
    text-align: center;
    background: none;
    border: none;
    cursor: pointer;
    font-weight: 600;
    color: #6c757d;
    transition: all 0.3s;
    position: relative;
}

.notification-tab.active {
    color: #667eea;
    background: white;
}

.notification-tab.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
}

.notification-list {
    max-height: 450px;
    overflow-y: auto;
}

.notification-item {
    padding: 16px 20px;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    transition: all 0.3s;
    position: relative;
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.notification-item:hover {
    background: #f8f9ff;
}

.notification-item.unread {
    background: #f0f4ff;
    border-left: 4px solid #667eea;
}

.notification-item.unread::before {
    content: '';
    position: absolute;
    top: 20px;
    right: 20px;
    width: 8px;
    height: 8px;
    background: #667eea;
    border-radius: 50%;
}

.notification-priority {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    margin-bottom: 4px;
}

.priority-critical {
    background: #fee2e2;
    color: #991b1b;
}

.priority-high {
    background: #fef3c7;
    color: #92400e;
}

.priority-medium {
    background: #dbeafe;
    color: #1e40af;
}

.priority-low {
    background: #f0fdf4;
    color: #166534;
}

.notification-title {
    font-weight: 600;
    color: #1e293b;
    margin: 4px 0;
    font-size: 14px;
}

.notification-message {
    color: #64748b;
    font-size: 13px;
    margin: 4px 0;
    line-height: 1.4;
}

.notification-time {
    color: #94a3b8;
    font-size: 11px;
    margin-top: 4px;
}

.notification-action {
    margin-top: 8px;
}

.notification-action-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.notification-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.notification-empty {
    padding: 60px 20px;
    text-align: center;
    color: #94a3b8;
}

.notification-empty-icon {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.notification-footer {
    padding: 12px 20px;
    background: #f8f9fa;
    text-align: center;
    border-top: 1px solid #e9ecef;
}

.notification-footer button {
    background: none;
    border: none;
    color: #667eea;
    font-weight: 600;
    cursor: pointer;
    font-size: 13px;
    transition: all 0.3s;
}

.notification-footer button:hover {
    color: #764ba2;
    text-decoration: underline;
}

/* Bell Icon Animation */
@keyframes ring {
    0%, 100% { transform: rotate(0deg); }
    10%, 30% { transform: rotate(-10deg); }
    20%, 40% { transform: rotate(10deg); }
}

.notification-bell.has-new {
    animation: ring 1s ease-in-out infinite;
}
</style>

<!-- Notification Bell Button -->
<div class="notification-bell-container" style="position: relative; display: inline-block;">
    <button id="notification-bell" class="btn btn-light position-relative" style="border-radius: 50%; width: 45px; height: 45px; padding: 0;">
        <i class="fas fa-bell" style="font-size: 20px;"></i>
        <span id="notification-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display: none;">
            0
        </span>
    </button>
</div>

<!-- Notification Widget -->
<div id="notification-widget" class="notification-widget">
    <div class="notification-header">
        <div>
            <h3>
                <i class="fas fa-bell"></i> การแจ้งเตือน
                <span id="widget-badge" class="notification-badge" style="display: none;">0</span>
            </h3>
        </div>
        <button class="notification-close" onclick="closeNotificationWidget()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <div class="notification-tabs">
        <button class="notification-tab active" data-tab="all" onclick="switchNotificationTab('all')">
            ทั้งหมด
        </button>
        <button class="notification-tab" data-tab="unread" onclick="switchNotificationTab('unread')">
            ยังไม่อ่าน
        </button>
    </div>
    
    <div id="notification-list" class="notification-list">
        <div class="notification-empty">
            <div class="notification-empty-icon">🔔</div>
            <div>ไม่มีการแจ้งเตือน</div>
        </div>
    </div>
    
    <div class="notification-footer">
        <button onclick="markAllAsRead()">
            <i class="fas fa-check-double"></i> ทำเครื่องหมายทั้งหมดว่าอ่านแล้ว
        </button>
    </div>
</div>

<script>
// Notification Widget JavaScript
let currentTab = 'all';
let notifications = [];
let refreshInterval;

// Initialize notification system
document.addEventListener('DOMContentLoaded', function() {
    loadNotifications();
    
    // Auto-refresh every 30 seconds
    refreshInterval = setInterval(loadNotifications, 30000);
    
    // Bell click handler
    document.getElementById('notification-bell').addEventListener('click', function() {
        toggleNotificationWidget();
    });
    
    // Close widget when clicking outside
    document.addEventListener('click', function(e) {
        const widget = document.getElementById('notification-widget');
        const bell = document.getElementById('notification-bell');
        
        if (!widget.contains(e.target) && !bell.contains(e.target)) {
            closeNotificationWidget();
        }
    });
});

// Load notifications from server
async function loadNotifications() {
    try {
        const response = await fetch('/api/notifications');
        const data = await response.json();
        
        if (data.success) {
            notifications = data.notifications || [];
            updateNotificationUI();
        }
    } catch (error) {
        console.error('Failed to load notifications:', error);
    }
}

// Update UI with notifications
function updateNotificationUI() {
    const unreadCount = notifications.filter(n => !n.is_read).length;
    
    // Update badge
    const badge = document.getElementById('notification-count');
    const widgetBadge = document.getElementById('widget-badge');
    const bell = document.getElementById('notification-bell');
    
    if (unreadCount > 0) {
        badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
        badge.style.display = 'inline-block';
        widgetBadge.textContent = unreadCount;
        widgetBadge.style.display = 'inline-block';
        bell.classList.add('has-new');
    } else {
        badge.style.display = 'none';
        widgetBadge.style.display = 'none';
        bell.classList.remove('has-new');
    }
    
    // Render notification list
    renderNotifications();
}

// Render notification list
function renderNotifications() {
    const listContainer = document.getElementById('notification-list');
    const filteredNotifications = currentTab === 'unread' 
        ? notifications.filter(n => !n.is_read)
        : notifications;
    
    if (filteredNotifications.length === 0) {
        listContainer.innerHTML = `
            <div class="notification-empty">
                <div class="notification-empty-icon">🔔</div>
                <div>${currentTab === 'unread' ? 'ไม่มีการแจ้งเตือนที่ยังไม่อ่าน' : 'ไม่มีการแจ้งเตือน'}</div>
            </div>
        `;
        return;
    }
    
    listContainer.innerHTML = filteredNotifications.map(n => `
        <div class="notification-item ${n.is_read ? '' : 'unread'}" onclick="handleNotificationClick(${n.id}, '${n.action_url || ''}')">
            <div class="notification-priority priority-${n.priority}">
                ${n.priority}
            </div>
            <div class="notification-title">${escapeHtml(n.title)}</div>
            <div class="notification-message">${escapeHtml(n.message)}</div>
            <div class="notification-time">
                <i class="far fa-clock"></i> ${formatTime(n.created_at)}
            </div>
            ${n.action_url ? `
                <div class="notification-action">
                    <button class="notification-action-btn" onclick="event.stopPropagation(); window.location.href='${n.action_url}'">
                        ${n.action_label || 'ดูรายละเอียด'}
                    </button>
                </div>
            ` : ''}
        </div>
    `).join('');
}

// Toggle notification widget
function toggleNotificationWidget() {
    const widget = document.getElementById('notification-widget');
    widget.classList.toggle('active');
}

// Close notification widget
function closeNotificationWidget() {
    const widget = document.getElementById('notification-widget');
    widget.classList.remove('active');
}

// Switch notification tab
function switchNotificationTab(tab) {
    currentTab = tab;
    
    // Update tab UI
    document.querySelectorAll('.notification-tab').forEach(t => {
        t.classList.remove('active');
    });
    document.querySelector(`[data-tab="${tab}"]`).classList.add('active');
    
    // Re-render
    renderNotifications();
}

// Handle notification click
async function handleNotificationClick(notificationId, actionUrl) {
    // Mark as read
    try {
        await fetch(`/api/notifications/${notificationId}/read`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        });
        
        // Update local state
        const notification = notifications.find(n => n.id === notificationId);
        if (notification) {
            notification.is_read = true;
            updateNotificationUI();
        }
        
        // Navigate if has action URL
        if (actionUrl) {
            window.location.href = actionUrl;
        }
    } catch (error) {
        console.error('Failed to mark notification as read:', error);
    }
}

// Mark all as read
async function markAllAsRead() {
    try {
        await fetch('/api/notifications/mark-all-read', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        });
        
        // Update local state
        notifications.forEach(n => n.is_read = true);
        updateNotificationUI();
        
        alert('✅ ทำเครื่องหมายทั้งหมดว่าอ่านแล้ว');
    } catch (error) {
        console.error('Failed to mark all as read:', error);
        alert('❌ เกิดข้อผิดพลาด');
    }
}

// Utility functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatTime(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000); // seconds
    
    if (diff < 60) return 'เมื่อสักครู่';
    if (diff < 3600) return `${Math.floor(diff / 60)} นาทีที่แล้ว`;
    if (diff < 86400) return `${Math.floor(diff / 3600)} ชั่วโมงที่แล้ว`;
    if (diff < 604800) return `${Math.floor(diff / 86400)} วันที่แล้ว`;
    
    return date.toLocaleDateString('th-TH', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    });
}
</script>
