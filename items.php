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
include 'views/header.php';

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    db_query("DELETE FROM items WHERE id = $id");
    db_query("INSERT INTO activity_logs (user_id, action) VALUES (" . $_SESSION['user_id'] . ", 'Menghapus barang ID: $id')");
    $_SESSION['success'] = "Data barang berhasil dihapus.";
    echo "<script>window.location.href='items.php';</script>";
    exit;
}

$search = isset($_GET['search']) ? $_GET['search'] : '';
$whereClause = "";
if (!empty($search)) {
    global $conn;
    $safe_search = $conn->real_escape_string($search);
    $whereClause = "WHERE i.code LIKE '%$safe_search%' OR i.name LIKE '%$safe_search%'";
}

$query = "
    SELECT i.*, s.name as supplier_name, s.lead_time_days 
    FROM items i 
    LEFT JOIN suppliers s ON i.supplier_id = s.id
    $whereClause
    ORDER BY i.id DESC
";
$result = db_query($query);
?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Data Barang</h5>
        <div class="d-flex gap-2">
            <form method="GET" action="" class="d-flex gap-2">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari kode atau nama barang..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="submit" class="btn btn-secondary btn-sm">Cari</button>
            </form>
            <a href="item_form.php" class="btn btn-primary btn-sm text-nowrap"><i class="fas fa-plus"></i> Tambah Barang</a>
        </div>
    </div>
    <div class="card-body">
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
                        <th width="150">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): 

                            $fuzz = FuzzyMamdani::fuzzifikasi($row);
                            $demandLing = FuzzyMamdani::getKategoriDominan($fuzz['Demand']);
                            $expiredLing = FuzzyMamdani::getKategoriDominan($fuzz['Expired']);
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['code']); ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo number_format($row['stock']); ?></td>
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
                                <a href="item_form.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm text-white" title="Ubah"><i class="fas fa-edit"></i></a>
                                <a href="items.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus permanen data ini?');" title="Hapus"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">Belum ada data barang.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include 'views/footer.php'; ?>
