<?php
/**
 * Audit Log Viewer
 * 
 * View and search system audit logs
 */

$pageTitle = 'Audit Logs';
$auditService = \App\Services\AuditLogService::getInstance();

// Get filters from request
$filters = [
    'user_id' => $_GET['user_id'] ?? null,
    'action' => $_GET['action'] ?? null,
    'entity_type' => $_GET['entity_type'] ?? null,
    'date_from' => $_GET['date_from'] ?? null,
    'date_to' => $_GET['date_to'] ?? null
];

$page = $_GET['page'] ?? 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Get logs
$logs = $auditService->search($filters, $perPage, $offset);

// Get statistics
$stats = $auditService->getStatistics('today');

// Export functionality
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="audit_logs_' . date('Y-m-d') . '.csv"');
    echo $auditService->exportToCSV($filters);
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Drugmuk</title>
    <link rel="stylesheet" href="/css/main.css">
    <style>
        .audit-container {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #007bff;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .filter-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .form-control {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .log-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .action-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .action-create { background: #d4edda; color: #155724; }
        .action-update { background: #fff3cd; color: #856404; }
        .action-delete { background: #f8d7da; color: #721c24; }
        .action-view { background: #d1ecf1; color: #0c5460; }
        .action-login { background: #d1ecf1; color: #0c5460; }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .pagination {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }
        
        .details-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        
        .modal-content {
            background: white;
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 8px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .json-view {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="audit-container">
        <h1>📋 Audit Logs</h1>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= number_format($stats['total_actions']) ?></div>
                <div class="stat-label">Total Actions Today</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format($stats['active_users']) ?></div>
                <div class="stat-label">Active Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format($stats['entity_types']) ?></div>
                <div class="stat-label">Entity Types</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format($stats['active_days']) ?></div>
                <div class="stat-label">Active Days</div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filter-card">
            <h3>🔍 Search & Filter</h3>
            <form method="GET">
                <div class="filter-grid">
                    <div class="form-group">
                        <label>Action</label>
                        <input type="text" name="action" class="form-control" 
                               value="<?= htmlspecialchars($filters['action'] ?? '') ?>" 
                               placeholder="e.g., drug.create">
                    </div>
                    <div class="form-group">
                        <label>Entity Type</label>
                        <select name="entity_type" class="form-control">
                            <option value="">All</option>
                            <option value="drug" <?= $filters['entity_type'] === 'drug' ? 'selected' : '' ?>>Drug</option>
                            <option value="order" <?= $filters['entity_type'] === 'order' ? 'selected' : '' ?>>Order</option>
                            <option value="dispensing" <?= $filters['entity_type'] === 'dispensing' ? 'selected' : '' ?>>Dispensing</option>
                            <option value="user" <?= $filters['entity_type'] === 'user' ? 'selected' : '' ?>>User</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date From</label>
                        <input type="date" name="date_from" class="form-control" 
                               value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Date To</label>
                        <input type="date" name="date_to" class="form-control" 
                               value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
                    </div>
                </div>
                <div style="margin-top: 15px;">
                    <button type="submit" class="btn btn-primary">🔍 Search</button>
                    <a href="/audit-logs" class="btn btn-secondary">Clear</a>
                    <a href="?export=csv&<?= http_build_query($filters) ?>" class="btn btn-success">📥 Export CSV</a>
                </div>
            </form>
        </div>
        
        <!-- Logs Table -->
        <div class="log-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Entity</th>
                        <th>IP Address</th>
                        <th>Date/Time</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px;">
                                No audit logs found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?= $log['id'] ?></td>
                                <td><?= htmlspecialchars($log['username'] ?? 'System') ?></td>
                                <td>
                                    <?php
                                    $actionClass = 'action-view';
                                    if (strpos($log['action'], 'create') !== false) $actionClass = 'action-create';
                                    if (strpos($log['action'], 'update') !== false) $actionClass = 'action-update';
                                    if (strpos($log['action'], 'delete') !== false) $actionClass = 'action-delete';
                                    if (strpos($log['action'], 'login') !== false) $actionClass = 'action-login';
                                    ?>
                                    <span class="action-badge <?= $actionClass ?>">
                                        <?= htmlspecialchars($log['action']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($log['entity_type']): ?>
                                        <?= htmlspecialchars($log['entity_type']) ?> 
                                        #<?= $log['entity_id'] ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($log['ip_address'] ?? '-') ?></td>
                                <td><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                                <td>
                                    <button class="btn btn-secondary btn-sm" 
                                            onclick="showDetails(<?= htmlspecialchars(json_encode($log)) ?>)">
                                        View
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&<?= http_build_query($filters) ?>" class="btn btn-secondary">← Previous</a>
            <?php endif; ?>
            <span class="btn btn-secondary">Page <?= $page ?></span>
            <?php if (count($logs) === $perPage): ?>
                <a href="?page=<?= $page + 1 ?>&<?= http_build_query($filters) ?>" class="btn btn-secondary">Next →</a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Details Modal -->
    <div id="detailsModal" class="details-modal">
        <div class="modal-content">
            <h2>Log Details</h2>
            <div id="modalBody"></div>
            <button class="btn btn-secondary" onclick="closeModal()">Close</button>
        </div>
    </div>
    
    <script>
        function showDetails(log) {
            const modal = document.getElementById('detailsModal');
            const body = document.getElementById('modalBody');
            
            let html = `
                <p><strong>ID:</strong> ${log.id}</p>
                <p><strong>User:</strong> ${log.username || 'System'}</p>
                <p><strong>Action:</strong> ${log.action}</p>
                <p><strong>IP Address:</strong> ${log.ip_address || '-'}</p>
                <p><strong>User Agent:</strong> ${log.user_agent || '-'}</p>
                <p><strong>Request:</strong> ${log.request_method || '-'} ${log.request_url || '-'}</p>
                <p><strong>Date/Time:</strong> ${log.created_at}</p>
            `;
            
            if (log.old_values) {
                html += `<h3>Old Values</h3><div class="json-view">${JSON.stringify(JSON.parse(log.old_values), null, 2)}</div>`;
            }
            
            if (log.new_values) {
                html += `<h3>New Values</h3><div class="json-view">${JSON.stringify(JSON.parse(log.new_values), null, 2)}</div>`;
            }
            
            body.innerHTML = html;
            modal.style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('detailsModal').style.display = 'none';
        }
        
        // Close modal on outside click
        window.onclick = function(event) {
            const modal = document.getElementById('detailsModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
