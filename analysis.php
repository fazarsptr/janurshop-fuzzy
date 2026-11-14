<?php
require_once 'config/database.php';
require_once 'helpers/FuzzyMamdani.php';
include 'views/header.php';


if (isset($_POST['run_analysis'])) {
    

    $itemsResult = db_query("
        SELECT i.*, s.lead_time_days 
        FROM items i 
        LEFT JOIN suppliers s ON i.supplier_id = s.id
    ");
    

    db_query("TRUNCATE TABLE fuzzy_results");

    $count = 0;
    while ($item = $itemsResult->fetch_assoc()) {
        

        $hasil = FuzzyMamdani::hitungPengadaan($item);
        $z = $hasil['z'];
        
        $stokMax = isset($item['stok_max']) ? (float)$item['stok_max'] : (float)$item['fuzzy_stok_atas'];
        $priority = FuzzyMamdani::getStatusPrioritas($z, $stokMax);
        
        $itemId = $item['id'];
        db_query("INSERT INTO fuzzy_results (item_id, recommendation_z, priority_status) VALUES ($itemId, $z, '$priority')");
        $count++;
    }

    db_query("INSERT INTO activity_logs (user_id, action) VALUES (" . $_SESSION['user_id'] . ", 'Menjalankan Analisis Fuzzy untuk $count barang')");
    $_SESSION['success'] = "Analisis Fuzzy berhasil dijalankan untuk $count barang.";
    echo "<script>window.location.href='analysis.php';</script>";
    exit;
}


$supplier_filter = isset($_GET['supplier']) ? (int)$_GET['supplier'] : 0;
$where = $supplier_filter > 0 ? "WHERE i.supplier_id = $supplier_filter" : "";

$query = "
    SELECT r.*, i.name, i.code, i.stock, s.name as supplier_name, s.lead_time_days
    FROM fuzzy_results r
    JOIN items i ON r.item_id = i.id
    LEFT JOIN suppliers s ON i.supplier_id = s.id
    $where
    ORDER BY r.recommendation_z DESC
";
$results = db_query($query);

$suppliers = db_query("SELECT * FROM suppliers");
?>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Analisis Persediaan (Fuzzy Mamdani)</h5>
        <form method="POST" action="">
            <button type="submit" name="run_analysis" class="btn btn-success" onclick="return confirm('Proses ini akan menghitung ulang seluruh data barang. Lanjutkan?');">
                <i class="fas fa-play"></i> Jalankan Analisis (Semua Barang)
            </button>
        </form>
    </div>
    <div class="card-body">
        
        <form method="GET" action="" class="row mb-4 align-items-end">
            <div class="col-md-4">
                <label>Filter Pemasok</label>
                <select name="supplier" class="form-select">
                    <option value="0">Semua Pemasok</option>
                    <?php while($sup = $suppliers->fetch_assoc()): ?>
                        <option value="<?php echo $sup['id']; ?>" <?php echo $supplier_filter == $sup['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($sup['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter"></i> Filter</button>
            </div>

        </form>

        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Barang</th>
                        <th>Pemasok</th>
                        <th>Sisa Stok</th>
                        <th>Rekomendasi (Z) Pcs</th>
                        <th>Status Prioritas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($results && $results->num_rows > 0): ?>
                        <?php while($row = $results->fetch_assoc()): 
                            $badgeClass = '';
                            if ($row['priority_status'] == 'Prioritas Utama') $badgeClass = 'badge-prioritas-utama';
                            else if ($row['priority_status'] == 'Prioritas Cadangan') $badgeClass = 'badge-prioritas-cadangan';
                            else $badgeClass = 'badge-aman';
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['code']); ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                            <td><?php echo number_format($row['stock']); ?></td>
                            <td class="fw-bold fs-5 text-center text-primary-theme"><?php echo number_format($row['recommendation_z'], 2); ?></td>
                            <td class="text-center">
                                <span class="badge <?php echo $badgeClass; ?> p-2">
                                    <?php echo $row['priority_status']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">Data hasil analisis kosong. Silakan jalankan analisis terlebih dahulu.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>

@media print {
    body * { visibility: hidden; }
    .main-content { margin-left: 0; padding: 0; }
    .sidebar, .navbar, form, .btn { display: none !important; }
    .table-responsive { visibility: visible; position: absolute; left: 0; top: 0; width: 100%; }
    .table-responsive * { visibility: visible; }
}
</style>

<?php include 'views/footer.php'; ?>