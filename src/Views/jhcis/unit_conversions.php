<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unit Conversions - Drugmuk</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            color: #2563eb;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
            font-size: 14px;
        }

        .actions {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(59, 130, 246, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .table-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .table-header h2 {
            color: #333;
            font-size: 20px;
        }

        .search-box {
            padding: 10px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            width: 300px;
        }

        .search-box:focus {
            outline: none;
            border-color: #3b82f6;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }

        tbody tr:hover {
            background: #dbeafe;
        }

        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-base {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-secondary {
            background: #dbeafe;
            color: #1e40af;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            max-width: 600px;
            width: 90%;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 600;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
        }

        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📐 Unit Conversions</h1>
            <p>จัดการกฎการแปลงหน่วยสำหรับแต่ละยา</p>
        </div>

        <div class="actions">
            <button class="btn btn-success" onclick="showAddModal()">
                ➕ Add Conversion
            </button>
            <a href="/admin/jhcis/dashboard" class="btn btn-primary">
                ← Back to Dashboard
            </a>
        </div>

        <div id="alert-container"></div>

        <div class="table-container">
            <div class="table-header">
                <h2>Conversion Rules</h2>
                <input type="text" class="search-box" id="search" placeholder="🔍 Search..." onkeyup="filterTable()">
            </div>

            <table id="conversions-table">
                <thead>
                    <tr>
                        <th>Drug</th>
                        <th>From Unit</th>
                        <th>To Unit</th>
                        <th>Factor</th>
                        <th>Type</th>
                        <th>Conversion Text</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="conversions-tbody">
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px;">
                            Loading...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal" id="conversion-modal">
        <div class="modal-content">
            <h3 id="modal-title">Add Unit Conversion</h3>
            <form id="conversion-form" onsubmit="submitConversion(event)">
                <input type="hidden" id="conversion-id">
                <div class="form-group">
                    <label for="drug-id">Drug *</label>
                    <select class="form-control" id="drug-id" required>
                        <option value="">-- Select Drug --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="from-unit">From Unit *</label>
                    <input type="text" class="form-control" id="from-unit" required placeholder="เช่น กล่อง, แผง">
                </div>
                <div class="form-group">
                    <label for="to-unit">To Unit (Base Unit) *</label>
                    <input type="text" class="form-control" id="to-unit" required placeholder="เช่น เม็ด, ml">
                </div>
                <div class="form-group">
                    <label for="conversion-factor">Conversion Factor *</label>
                    <input type="number" step="0.0001" class="form-control" id="conversion-factor" required placeholder="เช่น 10, 100">
                    <small>1 From Unit = ? To Unit</small>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="is-base-unit"> Is Base Unit
                    </label>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-success">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            loadConversions();
            loadDrugs();
        });

        async function loadConversions() {
            try {
                const response = await fetch('/api/unit-conversions');
                const data = await response.json();
                
                const tbody = document.getElementById('conversions-tbody');
                tbody.innerHTML = '';
                
                if (data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 40px;">No conversions found</td></tr>';
                    return;
                }
                
                data.forEach(conv => {
                    const row = `
                        <tr>
                            <td><strong>${conv.drug_name}</strong><br><small>${conv.code}</small></td>
                            <td>${conv.from_unit}</td>
                            <td>${conv.to_unit}</td>
                            <td>${conv.conversion_factor}</td>
                            <td><span class="badge badge-${conv.is_base_unit ? 'base' : 'secondary'}">${conv.is_base_unit ? 'Base' : 'Secondary'}</span></td>
                            <td>${conv.conversion_text}</td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="editConversion(${conv.id})">Edit</button>
                                <button class="btn btn-sm" onclick="deleteConversion(${conv.id})">Delete</button>
                            </td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
            } catch (error) {
                console.error('Error:', error);
                showAlert('Error loading conversions', 'error');
            }
        }

        async function loadDrugs() {
            try {
                const response = await fetch('/api/drugs');
                const data = await response.json();
                const select = document.getElementById('drug-id');
                
                data.data.forEach(drug => {
                    const option = document.createElement('option');
                    option.value = drug.id;
                    option.textContent = `${drug.name} (${drug.code})`;
                    select.appendChild(option);
                });
            } catch (error) {
                console.error('Error loading drugs:', error);
            }
        }

        function showAddModal() {
            document.getElementById('modal-title').textContent = 'Add Unit Conversion';
            document.getElementById('conversion-form').reset();
            document.getElementById('conversion-id').value = '';
            document.getElementById('conversion-modal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('conversion-modal').classList.remove('active');
        }

        async function submitConversion(event) {
            event.preventDefault();
            
            const formData = {
                drug_id: document.getElementById('drug-id').value,
                from_unit: document.getElementById('from-unit').value,
                to_unit: document.getElementById('to-unit').value,
                conversion_factor: document.getElementById('conversion-factor').value,
                is_base_unit: document.getElementById('is-base-unit').checked
            };
            
            const id = document.getElementById('conversion-id').value;
            const url = id ? `/api/unit-conversions/${id}` : '/api/unit-conversions';
            const method = id ? 'PUT' : 'POST';
            
            try {
                const response = await fetch(url, {
                    method: method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('บันทึกสำเร็จ!', 'success');
                    closeModal();
                    loadConversions();
                } else {
                    showAlert('Error: ' + result.message, 'error');
                }
            } catch (error) {
                showAlert('Error saving conversion', 'error');
            }
        }

        async function deleteConversion(id) {
            if (!confirm('ต้องการลบหรือไม่?')) return;
            
            try {
                const response = await fetch(`/api/unit-conversions/${id}`, {
                    method: 'DELETE'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('ลบสำเร็จ!', 'success');
                    loadConversions();
                } else {
                    showAlert('Error: ' + result.message, 'error');
                }
            } catch (error) {
                showAlert('Error deleting conversion', 'error');
            }
        }

        function filterTable() {
            const input = document.getElementById('search');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('conversions-table');
            const tr = table.getElementsByTagName('tr');
            
            for (let i = 1; i < tr.length; i++) {
                const td = tr[i].getElementsByTagName('td');
                let found = false;
                
                for (let j = 0; j < td.length; j++) {
                    if (td[j]) {
                        const txtValue = td[j].textContent || td[j].innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                
                tr[i].style.display = found ? '' : 'none';
            }
        }

        function showAlert(message, type) {
            const container = document.getElementById('alert-container');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.textContent = message;
            container.innerHTML = '';
            container.appendChild(alert);
            
            setTimeout(() => alert.remove(), 5000);
        }
    </script>
</body>
</html>
