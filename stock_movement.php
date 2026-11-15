<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'config/database.php';
require_once 'helpers/StockManager.php';

$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
$selectedItemId = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;

// Handle Transaction POST submission (Admin Only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'record_movement') {
    if (!$isAdmin) {
        $_SESSION['error'] = "Akses ditolak: Hanya administrator yang berhak mencatat transaksi mutasi stok.";
        header("Location: stock_movement.php" . ($selectedItemId > 0 ? "?item_id=$selectedItemId" : ""));
        exit;
    }

    // CSRF Verification
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!StockManager::validateCsrfToken($csrfToken)) {
        $_SESSION['error'] = "Validasi token keamanan (CSRF) gagal. Silakan coba kembali.";
        header("Location: stock_movement.php" . ($selectedItemId > 0 ? "?item_id=$selectedItemId" : ""));
        exit;
    }

    $itemId = (int)($_POST['item_id'] ?? 0);
    $type = trim($_POST['type'] ?? '');
    $qty = (float)($_POST['quantity'] ?? 0);
    $newStockTarget = isset($_POST['new_stock_target']) && $_POST['new_stock_target'] !== '' ? (float)$_POST['new_stock_target'] : null;
    $notes = trim($_POST['notes'] ?? '');
    $refNo = trim($_POST['reference_no'] ?? '');

    if ($itemId <= 0) {
        $_SESSION['error'] = "Silakan pilih barang yang akan dimutasi.";
        header("Location: stock_movement.php");
        exit;
    }

    $result = StockManager::recordMovement(
        $itemId,
        (int)$_SESSION['user_id'],
        $type,
        $qty,
        $newStockTarget,
        $notes,
        $refNo
    );

    if ($result['success']) {
        $_SESSION['success'] = "Transaksi mutasi stok berhasil dicatat. Sisa stok saat ini: " . number_format($result['stock_after']) . " Pcs.";
    } else {
        $_SESSION['error'] = "Gagal mencatat mutasi stok: " . htmlspecialchars($result['error']);
    }

    header("Location: stock_movement.php?item_id=" . $itemId);
    exit;
}

// Fetch all items for dropdown picker
$itemsResult = db_query("SELECT id, code, name, stock, min_stock_limit FROM items ORDER BY name ASC");
$itemsList = [];
if ($itemsResult) {
    while ($it = $itemsResult->fetch_assoc()) {
        $itemsList[] = $it;
    }
}

// Prepare filters for Movement History Ledger
$filterItem = isset($_GET['filter_item']) ? (int)$_GET['filter_item'] : ($selectedItemId > 0 ? $selectedItemId : 0);
$filterType = isset($_GET['filter_type']) ? trim($_GET['filter_type']) : '';
$filterDateStart = isset($_GET['filter_date_start']) ? trim($_GET['filter_date_start']) : '';
$filterDateEnd = isset($_GET['filter_date_end']) ? trim($_GET['filter_date_end']) : '';
$searchKeyword = isset($_GET['search']) ? trim($_GET['search']) : '';

$filterParams = [
    'item_id' => $filterItem,
    'type' => $filterType,
    'date_start' => $filterDateStart,
    'date_end' => $filterDateEnd,
    'search' => $searchKeyword
];

$movements = StockManager::getMovements($filterParams, 150, 0);

include 'views/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 text-primary-theme fw-bold"><i class="fas fa-exchange-alt me-2"></i> Riwayat & Mutasi Stok</h4>
        <small class="text-muted">Pencatatan mutasi masuk, keluar, penyesuaian (opname), dan buku besar riwayat pergerakan stok.</small>
    </div>
    <div>
        <a href="items.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-boxes me-1"></i> Data Barang</a>
    </div>
</div>

<div class="row">
    <?php if ($isAdmin): ?>
    <!-- Form Pencatatan Mutasi Stok Baru (Admin Only) -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white border-bottom pt-3 pb-2">
                <h6 class="mb-0 text-primary-theme fw-bold">
                    <i class="fas fa-plus-circle me-1"></i> Catat Transaksi Mutasi
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="stock_movement.php" id="stockMovementForm">
                    <input type="hidden" name="action" value="record_movement">
                    <input type="hidden" name="csrf_token" value="<?php echo StockManager::generateCsrfToken(); ?>">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Pilih Barang <span class="text-danger">*</span></label>
                        <select name="item_id" id="itemSelect" class="form-select" required onchange="updateCurrentStockDisplay()">
                            <option value="">-- Pilih Barang --</option>
                            <?php foreach ($itemsList as $it): ?>
                                <option value="<?php echo $it['id']; ?>" 
                                        data-stock="<?php echo (float)$it['stock']; ?>"
                                        data-code="<?php echo htmlspecialchars($it['code']); ?>"
                                        <?php echo ($selectedItemId === (int)$it['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($it['code'] . ' - ' . $it['name']); ?> (Stok: <?php echo number_format($it['stock']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="stockBadgeContainer" class="mt-2" style="display: none;">
                            <span class="badge bg-light text-dark border">
                                Sisa Stok Saat Ini: <strong id="currentStockDisplay" class="text-success">0</strong> Pcs
                            </span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Jenis Transaksi <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2">
                            <div class="form-check flex-fill p-2 border rounded bg-light">
                                <input class="form-check-input" type="radio" name="type" id="typeIn" value="in" checked onchange="toggleMovementFields()">
                                <label class="form-check-label fw-semibold text-success" for="typeIn">
                                    <i class="fas fa-arrow-down"></i> Masuk
                                </label>
                            </div>
                            <div class="form-check flex-fill p-2 border rounded bg-light">
                                <input class="form-check-input" type="radio" name="type" id="typeOut" value="out" onchange="toggleMovementFields()">
                                <label class="form-check-label fw-semibold text-danger" for="typeOut">
                                    <i class="fas fa-arrow-up"></i> Keluar
                                </label>
                            </div>
                            <div class="form-check flex-fill p-2 border rounded bg-light">
                                <input class="form-check-input" type="radio" name="type" id="typeAdj" value="adjustment" onchange="toggleMovementFields()">
                                <label class="form-check-label fw-semibold text-warning text-dark" for="typeAdj">
                                    <i class="fas fa-sliders-h"></i> Opname
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Input Kuantitas untuk In / Out -->
                    <div class="mb-3" id="quantityFieldGroup">
                        <label class="form-label fw-bold" id="quantityLabel">Jumlah Barang (Pcs) <span class="text-danger">*</span></label>
                        <input type="number" step="1" min="1" name="quantity" id="quantityInput" class="form-control" placeholder="Contoh: 10" required>
                        <small class="text-muted" id="quantityHelp">Kuantitas penambahan stok fisik.</small>
                    </div>

                    <!-- Input Target Stok untuk Adjustment / Opname -->
                    <div class="mb-3" id="adjustmentFieldGroup" style="display: none;">
                        <label class="form-label fw-bold">Stok Fisik Sebenarnya (Pcs) <span class="text-danger">*</span></label>
                        <input type="number" step="1" min="0" name="new_stock_target" id="adjustmentInput" class="form-control" placeholder="Hasil hitung opname fisik" oninput="calculateAdjustmentDiff()">
                        <small class="text-muted d-block mt-1" id="adjustmentDiffDisplay">
                            Selisih stok akan dihitung otomatis.
                        </small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">No. Referensi / Bukti</label>
                        <input type="text" name="reference_no" class="form-control" placeholder="Contoh: PO-2026/05, DO-8891, NOTA-12">
                        <small class="text-muted">Nomor surat jalan, nota, atau referensi faktur (opsional).</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Catatan / Alasan Transaksi</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Contoh: Restock dari supplier Rhinotec / Rusak saat cetak"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100" id="submitMovementBtn">
                        <i class="fas fa-save me-1"></i> Simpan Mutasi Stok
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tabel Buku Besar Riwayat Mutasi Stok -->
    <div class="<?php echo $isAdmin ? 'col-lg-8' : 'col-12'; ?> mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white border-bottom pt-3 pb-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-primary-theme fw-bold">
                    <i class="fas fa-history me-1"></i> Buku Riwayat Mutasi Stok
                </h6>
                <span class="badge bg-secondary rounded-pill">Total: <?php echo count($movements); ?> Data</span>
            </div>
            <div class="card-body">
                <!-- Filter Bar -->
                <form method="GET" action="stock_movement.php" class="row g-2 mb-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold mb-1">Filter Barang</label>
                        <select name="filter_item" class="form-select form-select-sm">
                            <option value="0">Semua Barang</option>
                            <?php foreach ($itemsList as $it): ?>
                                <option value="<?php echo $it['id']; ?>" <?php echo ($filterItem === (int)$it['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($it['code'] . ' - ' . $it['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold mb-1">Jenis Mutasi</label>
                        <select name="filter_type" class="form-select form-select-sm">
                            <option value="">Semua Jenis</option>
                            <option value="in" <?php echo ($filterType === 'in') ? 'selected' : ''; ?>>Masuk</option>
                            <option value="out" <?php echo ($filterType === 'out') ? 'selected' : ''; ?>>Keluar</option>
                            <option value="adjustment" <?php echo ($filterType === 'adjustment') ? 'selected' : ''; ?>>Penyesuaian (Opname)</option>
                            <option value="opening_balance" <?php echo ($filterType === 'opening_balance') ? 'selected' : ''; ?>>Saldo Awal</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold mb-1">Dari Tanggal</label>
                        <input type="date" name="filter_date_start" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filterDateStart); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold mb-1">Sampai Tanggal</label>
                        <input type="date" name="filter_date_end" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filterDateEnd); ?>">
                    </div>
                    <div class="col-md-3 d-flex gap-1">
                        <button type="submit" class="btn btn-primary btn-sm flex-fill"><i class="fas fa-filter"></i> Filter</button>
                        <a href="stock_movement.php" class="btn btn-outline-secondary btn-sm" title="Reset"><i class="fas fa-redo"></i></a>
                    </div>
                </form>

                <!-- Ledger Table -->
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle small mb-0">
                        <thead class="text-nowrap">
                            <tr>
                                <th>Tanggal & Waktu</th>
                                <th>Barang</th>
                                <th>Jenis</th>
                                <th class="text-center">Jumlah</th>
                                <th class="text-center">Sebelum &rarr; Sesudah</th>
                                <th>No. Ref</th>
                                <th>Keterangan</th>
                                <th>Petugas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($movements)): ?>
                                <?php foreach ($movements as $m): 
                                    $typeBadge = '';
                                    if ($m['type'] === 'in') {
                                        $typeBadge = '<span class="badge bg-success"><i class="fas fa-arrow-down me-1"></i>Masuk</span>';
                                    } elseif ($m['type'] === 'out') {
                                        $typeBadge = '<span class="badge bg-danger"><i class="fas fa-arrow-up me-1"></i>Keluar</span>';
                                    } elseif ($m['type'] === 'adjustment') {
                                        $typeBadge = '<span class="badge bg-warning text-dark"><i class="fas fa-sliders-h me-1"></i>Opname</span>';
                                    } elseif ($m['type'] === 'opening_balance') {
                                        $typeBadge = '<span class="badge bg-info text-dark"><i class="fas fa-flag me-1"></i>Saldo Awal</span>';
                                    }
                                ?>
                                <tr>
                                    <td class="text-nowrap text-muted"><?php echo date('d/m/Y H:i', strtotime($m['created_at'])); ?></td>
                                    <td>
                                        <a href="item_detail.php?id=<?php echo (int)$m['item_id']; ?>" class="fw-bold text-decoration-none text-dark">
                                            <?php echo htmlspecialchars($m['item_name']); ?>
                                        </a>
                                        <small class="text-muted d-block"><?php echo htmlspecialchars($m['item_code']); ?></small>
                                    </td>
                                    <td><?php echo $typeBadge; ?></td>
                                    <td class="text-center fw-bold fs-6">
                                        <?php if ($m['type'] === 'in'): ?>
                                            <span class="text-success">+<?php echo number_format($m['quantity']); ?></span>
                                        <?php elseif ($m['type'] === 'out'): ?>
                                            <span class="text-danger">-<?php echo number_format($m['quantity']); ?></span>
                                        <?php elseif ($m['type'] === 'adjustment'): ?>
                                            <span class="text-warning text-dark">&plusmn;<?php echo number_format($m['quantity']); ?></span>
                                        <?php else: ?>
                                            <span class="text-info text-dark"><?php echo number_format($m['quantity']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <span class="text-muted"><?php echo number_format($m['stock_before']); ?></span>
                                        <i class="fas fa-arrow-right mx-1 text-secondary opacity-50"></i>
                                        <strong class="text-dark"><?php echo number_format($m['stock_after']); ?></strong>
                                    </td>
                                    <td class="text-muted"><?php echo !empty($m['reference_no']) ? htmlspecialchars($m['reference_no']) : '-'; ?></td>
                                    <td><?php echo !empty($m['notes']) ? htmlspecialchars($m['notes']) : '<span class="text-muted">-</span>'; ?></td>
                                    <td class="text-nowrap text-muted"><?php echo htmlspecialchars($m['user_name'] ?? 'Admin'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        <i class="fas fa-clipboard-list fa-2x mb-2 d-block opacity-25"></i>
                                        Belum ada riwayat mutasi stok yang tercatat sesuai filter ini.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateCurrentStockDisplay() {
    var select = document.getElementById('itemSelect');
    if (!select) return;
    var selectedOption = select.options[select.selectedIndex];
    var badgeContainer = document.getElementById('stockBadgeContainer');
    var stockDisplay = document.getElementById('currentStockDisplay');

    if (selectedOption && selectedOption.value !== "") {
        var stock = selectedOption.getAttribute('data-stock');
        stockDisplay.innerText = Number(stock).toLocaleString('id-ID');
        badgeContainer.style.display = 'block';
    } else {
        badgeContainer.style.display = 'none';
    }
    calculateAdjustmentDiff();
}

function toggleMovementFields() {
    var isAdj = document.getElementById('typeAdj').checked;
    var isOut = document.getElementById('typeOut').checked;
    var isIn = document.getElementById('typeIn').checked;

    var qtyGroup = document.getElementById('quantityFieldGroup');
    var adjGroup = document.getElementById('adjustmentFieldGroup');
    var qtyInput = document.getElementById('quantityInput');
    var adjInput = document.getElementById('adjustmentInput');
    var qtyHelp = document.getElementById('quantityHelp');

    if (isAdj) {
        qtyGroup.style.display = 'none';
        qtyInput.removeAttribute('required');
        adjGroup.style.display = 'block';
        adjInput.setAttribute('required', 'required');
    } else {
        adjGroup.style.display = 'none';
        adjInput.removeAttribute('required');
        qtyGroup.style.display = 'block';
        qtyInput.setAttribute('required', 'required');

        if (isOut) {
            qtyHelp.innerText = 'Kuantitas pengurangan / pengeluaran stok fisik.';
        } else {
            qtyHelp.innerText = 'Kuantitas penambahan stok fisik.';
        }
    }
}

function calculateAdjustmentDiff() {
    var isAdj = document.getElementById('typeAdj').checked;
    if (!isAdj) return;

    var select = document.getElementById('itemSelect');
    var diffDisplay = document.getElementById('adjustmentDiffDisplay');
    if (!select || select.value === "") {
        diffDisplay.innerText = "Pilih barang terlebih dahulu untuk melihat selisih.";
        return;
    }

    var selectedOption = select.options[select.selectedIndex];
    var currentStock = parseFloat(selectedOption.getAttribute('data-stock') || 0);
    var targetStockVal = document.getElementById('adjustmentInput').value;

    if (targetStockVal === "") {
        diffDisplay.innerText = "Masukkan stok fisik baru untuk menghitung selisih.";
        return;
    }

    var targetStock = parseFloat(targetStockVal);
    var diff = targetStock - currentStock;

    if (diff > 0) {
        diffDisplay.innerHTML = '<span class="text-success fw-bold"><i class="fas fa-plus"></i> Bertambah ' + diff.toLocaleString('id-ID') + ' Pcs</span> (dari ' + currentStock + ' ke ' + targetStock + ')';
    } else if (diff < 0) {
        diffDisplay.innerHTML = '<span class="text-danger fw-bold"><i class="fas fa-minus"></i> Berkurang ' + Math.abs(diff).toLocaleString('id-ID') + ' Pcs</span> (dari ' + currentStock + ' ke ' + targetStock + ')';
    } else {
        diffDisplay.innerHTML = '<span class="text-secondary fw-bold">Stok sama (tidak ada perubahan selisih)</span>';
    }
}

document.addEventListener("DOMContentLoaded", function() {
    updateCurrentStockDisplay();
    toggleMovementFields();
});
</script>

<?php include 'views/footer.php'; ?>
