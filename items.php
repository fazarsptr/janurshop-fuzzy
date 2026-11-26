<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['role']) && $_SESSION['role'] === 'pemilik') {
    header("Location: index.php");
    exit;
}
require_once 'config/database.php';
require_once 'helpers/FuzzyMamdani.php';
require_once 'helpers/StockManager.php';
include 'views/header.php';

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    // Audit Trail Integrity: Prevent product deletion when historical stock movements exist
    if (StockManager::hasMovements($id)) {
        $_SESSION['error'] = "Barang tidak dapat dihapus karena memiliki riwayat pergerakan stok. Untuk menjaga integritas audit, barang dengan riwayat mutasi tidak boleh dihapus.";
        echo "<script>window.location.href='items.php';</script>";
        exit;
    }

    db_query("DELETE FROM items WHERE id = $id");
    db_query("INSERT INTO activity_logs (user_id, action) VALUES (" . $_SESSION['user_id'] . ", 'Menghapus barang ID: $id')");
    $_SESSION['success'] = "Data barang berhasil dihapus.";
    echo "<script>window.location.href='items.php';</script>";
    exit;
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$stock_status = isset($_GET['stock_status']) ? trim($_GET['stock_status']) : 'all';
if (!in_array($stock_status, ['all', 'low', 'empty'], true)) {
    $stock_status = 'all';
}

$stockStatusLabels = [
    'all' => 'Semua Stok',
    'low' => 'Stok Rendah',
    'empty' => 'Stok Habis'
];

$isFiltered = ($search !== '' || $stock_status !== 'all');

global $conn;

$whereConditions = [];
$params = [];
$types = "";

if ($search !== '') {
    $whereConditions[] = "(i.code LIKE ? OR i.name LIKE ?)";
    $searchPattern = '%' . $search . '%';
    $params[] = $searchPattern;
    $params[] = $searchPattern;
    $types .= "ss";
}

if ($stock_status === 'low') {
    $whereConditions[] = "i.stock <= i.min_stock_limit";
} elseif ($stock_status === 'empty') {
    $whereConditions[] = "i.stock <= 0";
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

$query = "
    SELECT i.*, s.name as supplier_name, s.lead_time_days
    FROM items i
    LEFT JOIN suppliers s ON i.supplier_id = s.id
    $whereClause
    ORDER BY i.id DESC
";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = db_query($query);
}
?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0">Data Barang</h5>
        <div class="d-flex gap-2 flex-wrap">
            <form method="GET" action="items.php" class="d-flex gap-2 flex-wrap align-items-center">
                <select name="stock_status" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                    <option value="all" <?php echo ($stock_status === 'all') ? 'selected' : ''; ?>>Semua Stok</option>
                    <option value="low" <?php echo ($stock_status === 'low') ? 'selected' : ''; ?>>Stok Rendah</option>
                    <option value="empty" <?php echo ($stock_status === 'empty') ? 'selected' : ''; ?>>Stok Habis</option>
                </select>
                <div class="input-group input-group-sm" style="min-width: 220px;">
                    <input type="text" name="search" class="form-control" placeholder="Cari kode atau nama barang..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-secondary" title="Cari"><i class="fas fa-search"></i></button>
                    <?php if ($isFiltered): ?>
                        <a href="items.php" class="btn btn-outline-secondary" title="Reset Filter"><i class="fas fa-times"></i></a>
                    <?php endif; ?>
                </div>
            </form>
            <a href="item_form.php" class="btn btn-primary btn-sm text-nowrap"><i class="fas fa-plus"></i> Tambah Barang</a>
        </div>
    </div>
    <div class="card-body">
        <?php if ($isFiltered): ?>
            <div class="alert alert-info py-2 px-3 mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span>
                    <i class="fas fa-filter me-1"></i>
                    Menampilkan data barang
                    <?php if ($search !== '' && $stock_status !== 'all'): ?>
                        untuk kata kunci "<strong><?php echo htmlspecialchars($search); ?></strong>" dengan status <strong><?php echo $stockStatusLabels[$stock_status]; ?></strong>
                    <?php elseif ($search !== ''): ?>
                        untuk kata kunci "<strong><?php echo htmlspecialchars($search); ?></strong>"
                    <?php elseif ($stock_status !== 'all'): ?>
                        dengan status <strong><?php echo $stockStatusLabels[$stock_status]; ?></strong>
                    <?php endif; ?>
                    (<strong><?php echo $result ? $result->num_rows : 0; ?></strong> barang ditemukan)
                </span>
                <a href="items.php" class="btn btn-sm btn-outline-secondary py-0 px-2 text-decoration-none">Reset Filter</a>
            </div>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Barang</th>
                        <th>Stok (Pcs)</th>
                        <th>Permintaan</th>
                        <th>Pemasok</th>
                        <th>Harga</th>
                        <th>Kedaluwarsa</th>
                        <th width="190">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()):

                            $fuzz = FuzzyMamdani::fuzzifikasi($row);
                            $demandLing = FuzzyMamdani::getKategoriDominan($fuzz['Demand']);
                            $expiredLing = FuzzyMamdani::getKategoriDominan($fuzz['Expired']);
                            $isOutOfStock = ((float)$row['stock'] <= 0);
                            $isLowStock = ((float)$row['stock'] <= (float)$row['min_stock_limit']);
                        ?>
                        <tr class="<?php echo $isOutOfStock ? 'table-danger' : ($isLowStock ? 'table-warning' : ''); ?>">
                            <td><?php echo htmlspecialchars($row['code']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($row['name']); ?>
                                <?php if ($isOutOfStock): ?>
                                    <span class="badge bg-danger ms-1" title="Stok Habis: Sisa stok 0 Pcs">
                                        <i class="fas fa-times-circle me-1"></i>Stok Habis
                                    </span>
                                <?php elseif ($isLowStock): ?>
                                    <span class="badge bg-warning text-dark ms-1" title="Stok Rendah: Di bawah batas minimum">
                                        <i class="fas fa-exclamation-triangle me-1"></i>Stok Rendah
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="<?php echo ($isOutOfStock || $isLowStock) ? 'fw-bold text-danger' : ''; ?>">
                                    <?php echo number_format($row['stock']); ?>
                                </span>
                                <?php if ($isOutOfStock): ?>
                                    <small class="text-danger d-block fw-semibold">(Stok Habis)</small>
                                <?php elseif ($isLowStock): ?>
                                    <small class="text-muted d-block">(Min: <?php echo number_format($row['min_stock_limit']); ?>)</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?php echo $demandLing; ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                            <td>Rp <?php echo number_format($row['price'], 0, ',', '.'); ?></td>
                            <td>
                                <span class="badge <?php echo ($expiredLing == 'Dekat') ? 'bg-danger' : 'bg-success'; ?>">
                                    <?php echo $expiredLing; ?>
                                </span>
                            </td>
                            <td>
                                <a href="item_detail.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm text-white" title="Detail"><i class="fas fa-eye"></i></a>
                                <a href="stock_movement.php?item_id=<?php echo $row['id']; ?>" class="btn btn-secondary btn-sm" title="Mutasi Stok"><i class="fas fa-exchange-alt"></i></a>
                                <a href="item_form.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm text-white" title="Ubah"><i class="fas fa-edit"></i></a>
                                <a href="items.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus permanen data ini?');" title="Hapus"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <?php if ($isFiltered): ?>
                                    <i class="fas fa-search fa-2x mb-2 d-block opacity-25"></i>
                                    <?php if ($search !== '' && $stock_status !== 'all'): ?>
                                        Barang dengan kata kunci "<strong><?php echo htmlspecialchars($search); ?></strong>" dan status <strong><?php echo $stockStatusLabels[$stock_status]; ?></strong> tidak ditemukan.
                                    <?php elseif ($search !== ''): ?>
                                        Barang dengan kata kunci "<strong><?php echo htmlspecialchars($search); ?></strong>" tidak ditemukan.
                                    <?php else: ?>
                                        Tidak ada barang dengan status <strong><?php echo $stockStatusLabels[$stock_status]; ?></strong>.
                                    <?php endif; ?>
                                    <div class="mt-2">
                                        <a href="items.php" class="btn btn-outline-secondary btn-sm">
                                            <i class="fas fa-redo me-1"></i> Tampilkan Semua Barang
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <i class="fas fa-box-open fa-2x mb-2 d-block opacity-25"></i>
                                    Belum ada data barang.
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include 'views/footer.php'; ?>
