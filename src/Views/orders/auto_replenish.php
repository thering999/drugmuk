<?php include dirname(__DIR__) . '/layouts/header.php'; ?>
<?= \App\Core\CSRF::metaTag() ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    :root {
        --glass-bg: rgba(255, 255, 255, 0.7);
        --glass-border: rgba(255, 255, 255, 0.3);
    }
    .replenish-container {
        background: #f0f2f5;
        min-height: 100vh;
        padding-bottom: 50px;
    }
    .premium-card {
        background: var(--glass-bg);
        backdrop-filter: blur(10px);
        border: 1px solid var(--glass-border);
        border-radius: 20px;
        box-shadow: 0 8px 32px rgba(31, 38, 135, 0.07);
        overflow: hidden;
        transition: transform 0.3s ease;
    }
    .premium-card:hover { transform: translateY(-5px); }
    
    .urgency-v-high { border-left: 5px solid #ef4444; }
    .urgency-high { border-left: 5px solid #f59e0b; }
    .urgency-normal { border-left: 5px solid #10b981; }

    .score-badge {
        width: 40px; height: 40px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 14px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    .score-red { background: #fee2e2; color: #b91c1c; }
    .score-yellow { background: #fef3c7; color: #92400e; }
    .score-green { background: #dcfce7; color: #15803d; }

    .abc-pill {
        padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700;
        text-transform: uppercase; letter-spacing: 1px;
    }
    .abc-a { background: #fecaca; color: #991b1b; }
    .abc-b { background: #fef3c7; color: #92400e; }
    .abc-c { background: #dcfce7; color: #15803d; }

    .table thead th {
        font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;
        color: #64748b; background: #f8fafc; border-bottom: 2px solid #e2e8f0;
    }
</style>

<div class="replenish-container">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center py-4">
            <div>
                <h1 class="mb-0 fw-800" style="color: #1e293b; font-size: 2.2rem;">
                    <i class="fas fa-magic text-primary"></i> Smart Auto-Replenish
                </h1>
                <p class="text-muted">AI-driven predictive stock management & replenishment optimization.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-white shadow-sm border-0 px-4" onclick="location.reload()">
                    <i class="fas fa-sync-alt"></i> Refresh AI
                </button>
                <a href="/orders" class="btn btn-dark shadow-sm px-4">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <!-- Dashboard Widgets -->
        <div class="row mb-4">
            <div class="col-lg-8">
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="premium-card p-4 h-100">
                            <div class="text-muted small fw-600 mb-1">RECOMMENDED ITEMS</div>
                            <div class="h2 fw-800 mb-0"><?= count($suggestions) ?></div>
                            <div class="mt-2">
                                <span class="badge bg-danger rounded-pill">Need Immediate Action</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="premium-card p-4 h-100">
                            <div class="text-muted small fw-600 mb-1">ESTIMATED BUDGET</div>
                            <div class="h2 fw-800 mb-0">฿<?= number_format(array_sum(array_column($suggestions, 'estimated_cost')), 0) ?></div>
                            <div class="mt-2 text-muted small">Based on last unit cost</div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="premium-card p-4 h-100">
                            <div class="text-muted small fw-600 mb-1">AI CONFIDENCE</div>
                            <div class="h2 fw-800 mb-0"><?= count($suggestions) > 0 ? round(array_sum(array_column($suggestions, 'confidence')) / count($suggestions)) : 0 ?>%</div>
                            <div class="progress mt-2" style="height: 6px;">
                                <div class="progress-bar bg-primary" style="width: 85%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Suggestions Table -->
                <div class="premium-card">
                    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-700"><i class="fas fa-robot text-primary"></i> AI Procurement Plan</h5>
                        <button type="submit" form="auto-po-form" class="btn btn-primary btn-sm px-3 rounded-pill fw-700 shadow-sm">
                            <i class="fas fa-file-invoice"></i> Generate Draft POs
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <form id="auto-po-form" action="/orders/auto-replenish/store" method="POST">
                            <?= \App\Core\CSRF::field() ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle">
                                    <thead>
                                        <tr>
                                            <th class="text-center" width="50"><input type="checkbox" id="select-all" class="form-check-input" checked></th>
                                            <th>Drug Identity</th>
                                            <th class="text-center">Priority</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">Order Qty</th>
                                            <th class="text-end">Cost</th>
                                            <th class="text-center">Urgency</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(empty($suggestions)): ?>
                                            <tr><td colspan="7" class="text-center py-5">No suggestions at this time.</td></tr>
                                        <?php else: foreach ($suggestions as $index => $item): 
                                            $urgencyClass = $item['urgency_score'] > 80 ? 'urgency-v-high' : ($item['urgency_score'] > 50 ? 'urgency-high' : 'urgency-normal');
                                            $scoreColor = $item['urgency_score'] > 80 ? 'score-red' : ($item['urgency_score'] > 50 ? 'score-yellow' : 'score-green');
                                        ?>
                                            <tr class="<?= $urgencyClass ?>">
                                                <td class="text-center">
                                                    <input type="checkbox" name="selected_items[<?= $index ?>][checked]" value="1" class="form-check-input item-checkbox" checked>
                                                    <input type="hidden" name="selected_items[<?= $index ?>][drug_id]" value="<?= $item['drug_id'] ?>">
                                                    <input type="hidden" name="selected_items[<?= $index ?>][supplier_id]" value="<?= $item['supplier_id'] ?>">
                                                    <input type="hidden" name="selected_items[<?= $index ?>][unit_price]" value="<?= $item['unit_price'] ?>">
                                                </td>
                                                <td>
                                                    <div class="fw-700 text-dark"><?= htmlspecialchars($item['drug_name']) ?></div>
                                                    <div class="small text-muted d-flex align-items-center gap-2">
                                                        <span><?= $item['drug_code'] ?></span>
                                                        <span class="abc-pill abc-<?= strtolower($item['abc_class']) ?>"><?= $item['abc_class'] ?></span>
                                                        <span class="badge bg-light text-secondary border"><?= $item['ven_class'] ?></span>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <div class="small fw-600 text-uppercase"><?= $item['supplier_name'] ?></div>
                                                    <div class="text-muted small" style="font-size: 10px;">AI Conf: <?= $item['confidence'] ?>%</div>
                                                </td>
                                                <td class="text-center">
                                                    <div class="small text-muted">Stock: <span class="fw-700 text-danger"><?= number_format($item['current_stock']) ?></span></div>
                                                    <div class="small text-muted">ROP: <?= number_format($item['reorder_point']) ?></div>
                                                </td>
                                                <td class="text-center">
                                                    <input type="number" name="selected_items[<?= $index ?>][quantity]" 
                                                           class="form-control form-control-sm text-center fw-800" 
                                                           style="width: 80px; margin: auto;"
                                                           value="<?= $item['suggested_qty'] ?>">
                                                    <div style="font-size: 10px;" class="text-muted mt-1">~<?= $item['monthly_demand'] ?>/mo</div>
                                                </td>
                                                <td class="text-end fw-700">
                                                    ฿<?= number_format($item['estimated_cost'], 2) ?>
                                                </td>
                                                <td class="text-center">
                                                    <div class="score-badge <?= $scoreColor ?> mx-auto">
                                                        <?= $item['urgency_score'] ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="premium-card p-4 mb-4">
                    <h5 class="fw-700 mb-3">Budget Analysis</h5>
                    <div style="height: 250px;">
                        <canvas id="budgetChart"></canvas>
                    </div>
                </div>

                <div class="premium-card p-4 border-primary border-top-4">
                    <h5 class="fw-700 mb-3"><i class="fas fa-lightbulb text-warning"></i> AI Insights</h5>
                    <div class="small">
                        <div class="mb-3 d-flex gap-3">
                            <div class="h1 text-primary mb-0"><i class="fas fa-shipping-fast"></i></div>
                            <div>
                                <div class="fw-700">Optimal Lead Time</div>
                                <div class="text-muted">Orders placed today will likely arrive in <b>4.2 days</b>.</div>
                            </div>
                        </div>
                        <div class="mb-3 d-flex gap-3">
                            <div class="h1 text-success mb-0"><i class="fas fa-leaf"></i></div>
                            <div>
                                <div class="fw-700">Financial Optimization</div>
                                <div class="text-muted">Consolidating orders by Supplier could save up to <b>฿4,200</b>.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Select All
    document.getElementById('select-all').addEventListener('change', function() {
        document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = this.checked);
    });

    // Form submission handler
    document.getElementById('auto-po-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Check if any items are selected
        const checkedItems = document.querySelectorAll('.item-checkbox:checked');
        if (checkedItems.length === 0) {
            alert('กรุณาเลือกรายการยาที่ต้องการสั่งซื้ออย่างน้อย 1 รายการ');
            return false;
        }

        // Confirm before submitting
        if (!confirm(`ต้องการสร้าง Draft Purchase Orders จำนวน ${checkedItems.length} รายการ?`)) {
            return false;
        }

        // Show loading state
        const submitBtn = document.querySelector('button[type="submit"][form="auto-po-form"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

        // Submit form
        fetch(this.action, {
            method: 'POST',
            body: new FormData(this)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success === false) {
                alert('❌ Error: ' + (data.message || data.error));
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            } else {
                // Success - redirect to orders page
                window.location.href = '/orders';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // If not JSON response, assume it's a redirect (success)
            window.location.href = '/orders';
        });
    });

    // Budget Chart
    <?php
        $abcGroups = ['A' => 0, 'B' => 0, 'C' => 0];
        foreach($suggestions as $s) {
            $abcGroups[$s['abc_class']] += $s['estimated_cost'];
        }
    ?>
    const ctx = document.getElementById('budgetChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Class A', 'Class B', 'Class C'],
            datasets: [{
                data: [<?= $abcGroups['A'] ?>, <?= $abcGroups['B'] ?>, <?= $abcGroups['C'] ?>],
                backgroundColor: ['#ef4444', '#f59e0b', '#10b981'],
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
