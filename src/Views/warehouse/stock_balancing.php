<?php include dirname(__DIR__) . '/layouts/header.php'; ?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <div>
            <h1 class="mb-0 text-primary">
                <i class="fas fa-balance-scale"></i> Smart Stock Balancing
            </h1>
            <p class="text-muted small">AI แนะนำการโอนย้ายสินค้าระหว่างคลังเพื่อปรับสมดุล (Cross-Warehouse Operations)</p>
        </div>
        <a href="/warehouse" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Warehouse
        </a>
    </div>

    <div class="card mb-4 shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-info">
                    <i class="fas fa-exchange-alt"></i> รายการแนะนำโอนย้าย (Suggested Transfers)
                </h6>
                <button type="submit" form="balancing-form" class="btn btn-info text-white">
                    <i class="fas fa-check-circle"></i> ยืนยันการโอนย้าย
                </button>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($suggestions)): ?>
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="fas fa-check-double fa-4x text-success opacity-50"></i>
                    </div>
                    <h4 class="text-success">Stock Balanced!</h4>
                    <p class="text-muted">คลังย่อยมีสินค้าเพียงพอ หรือคลังหลักไม่มีสินค้าส่วนเกิน</p>
                </div>
            <?php else: ?>
                <form id="balancing-form" action="/warehouse/balancing/process" method="POST">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;" class="text-center">
                                        <input type="checkbox" id="select-all" class="form-check-input" checked>
                                    </th>
                                    <th>To Warehouse</th>
                                    <th>Drug Info</th>
                                    <th class="text-center">Current Sub Stock</th>
                                    <th class="text-center">Main Warehouse Excess</th>
                                    <th class="text-center">Suggested Transfer</th>
                                    <th>Reason (AI Logic)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($suggestions as $index => $item): ?>
                                    <tr>
                                        <td class="text-center">
                                            <input type="checkbox" name="selected_transfers[<?php echo $index; ?>][checked]" value="1" class="form-check-input item-checkbox" checked>
                                            <input type="hidden" name="selected_transfers[<?php echo $index; ?>][subwarehouse_id]" value="<?php echo $item['subwarehouse_id']; ?>">
                                            <input type="hidden" name="selected_transfers[<?php echo $index; ?>][drug_id]" value="<?php echo $item['drug_id']; ?>">
                                            <input type="hidden" name="selected_transfers[<?php echo $index; ?>][reason]" value="<?php echo $item['reason']; ?>">
                                        </td>
                                        <td>
                                            <span class="badge bg-warning text-dark">
                                                <i class="fas fa-warehouse"></i> <?php echo htmlspecialchars($item['subwarehouse_name']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($item['drug_name']); ?></strong> 
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-danger"><?php echo number_format($item['current_sw_stock']); ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success"><?php echo number_format($item['main_warehouse_stock']); ?></span>
                                        </td>
                                        <td class="text-center">
                                            <input type="number" name="selected_transfers[<?php echo $index; ?>][quantity]" 
                                                   class="form-control form-control-sm text-center font-weight-bold text-info" 
                                                   style="width: 100px; display: inline-block;"
                                                   value="<?php echo $item['suggested_transfer']; ?>">
                                        </td>
                                        <td>
                                            <small class="text-muted"><i class="fas fa-robot"></i> <?php echo $item['reason']; ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    document.getElementById('select-all').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.item-checkbox');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
