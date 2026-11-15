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

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id == 0) {
    echo "<script>window.location.href='items.php';</script>";
    exit;
}

$query = "SELECT i.*, s.name as supplier_name, s.lead_time_days
          FROM items i
          LEFT JOIN suppliers s ON i.supplier_id = s.id
          WHERE i.id = $id";
$result = db_query($query);

if (!$result || $result->num_rows == 0) {
    echo "<script>window.location.href='items.php';</script>";
    exit;
}

$item = $result->fetch_assoc();


$fuzzifikasi = FuzzyMamdani::fuzzifikasi($item);
?>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Detail Barang: <?php echo htmlspecialchars($item['name']); ?> (<?php echo htmlspecialchars($item['code']); ?>)</h5>
        <a href="items.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-4">
                <h6 class="text-primary-theme fw-bold border-bottom pb-2">Informasi Dasar (Data Mentah)</h6>
                <table class="table table-sm table-borderless">
                    <tr><td width="150">Stok Saat Ini</td><td>: <strong><?php echo number_format($item['stock']); ?></strong> Pcs</td></tr>
                    <tr><td>Permintaan</td><td>: <strong><?php echo number_format($item['demand']); ?></strong></td></tr>
                    <tr><td>Pemasok</td><td>: <strong><?php echo htmlspecialchars($item['supplier_name']); ?></strong> (Waktu Tunggu: <?php echo $item['lead_time_days']; ?> hari)</td></tr>
                    <tr><td>Harga</td><td>: <strong>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></strong></td></tr>
                    <tr><td>Kedaluwarsa</td><td>: <strong><?php echo number_format($item['expired_days']); ?></strong> Hari</td></tr>
                    <tr><td>Batas Kritis (Min)</td><td>: <strong><?php echo number_format($item['min_stock_limit']); ?></strong></td></tr>
                    <tr><td>Stok Maksimal</td><td>: <strong><?php echo number_format($item['stok_max']); ?></strong> (Utk Defuzzifikasi)</td></tr>
                </table>
            </div>

            <div class="col-md-6 mb-4">
                <h6 class="text-primary-theme fw-bold border-bottom pb-2">Hasil Fuzzifikasi (Derajat Keanggotaan)</h6>


                <div class="mb-2">
                    <strong>Stok:</strong>
                    <span class="badge bg-secondary">Sedikit (<?php echo round($fuzzifikasi['Stok']['Sedikit'], 2); ?>)</span>
                    <span class="badge bg-secondary">Sedang (<?php echo round($fuzzifikasi['Stok']['Sedang'], 2); ?>)</span>
                    <span class="badge bg-secondary">Banyak (<?php echo round($fuzzifikasi['Stok']['Banyak'], 2); ?>)</span>
                    <br><small class="text-muted">Kategori Dominan: <strong><?php echo FuzzyMamdani::getKategoriDominan($fuzzifikasi['Stok']); ?></strong></small>
                </div>


                <div class="mb-2">
                    <strong>Permintaan:</strong>
                    <span class="badge bg-secondary">Rendah (<?php echo round($fuzzifikasi['Demand']['Rendah'], 2); ?>)</span>
                    <span class="badge bg-secondary">Normal (<?php echo round($fuzzifikasi['Demand']['Normal'], 2); ?>)</span>
                    <span class="badge bg-secondary">Tinggi (<?php echo round($fuzzifikasi['Demand']['Tinggi'], 2); ?>)</span>
                    <br><small class="text-muted">Kategori Dominan: <strong><?php echo FuzzyMamdani::getKategoriDominan($fuzzifikasi['Demand']); ?></strong></small>
                </div>


                <div class="mb-2">
                    <strong>Waktu Tunggu:</strong>
                    <span class="badge bg-secondary">Cepat (<?php echo round($fuzzifikasi['LeadTime']['Cepat'], 2); ?>)</span>
                    <span class="badge bg-secondary">Sedang (<?php echo round($fuzzifikasi['LeadTime']['Sedang'], 2); ?>)</span>
                    <span class="badge bg-secondary">Lama (<?php echo round($fuzzifikasi['LeadTime']['Lama'], 2); ?>)</span>
                    <br><small class="text-muted">Kategori Dominan: <strong><?php echo FuzzyMamdani::getKategoriDominan($fuzzifikasi['LeadTime']); ?></strong></small>
                </div>


                <div class="mb-2">
                    <strong>Harga:</strong>
                    <span class="badge bg-secondary">Murah (<?php echo round($fuzzifikasi['Harga']['Murah'], 2); ?>)</span>
                    <span class="badge bg-secondary">Mahal (<?php echo round($fuzzifikasi['Harga']['Mahal'], 2); ?>)</span>
                    <br><small class="text-muted">Kategori Dominan: <strong><?php echo FuzzyMamdani::getKategoriDominan($fuzzifikasi['Harga']); ?></strong></small>
                </div>


                <div class="mb-2">
                    <strong>Kedaluwarsa:</strong>
                    <span class="badge bg-secondary">Dekat (<?php echo round($fuzzifikasi['Expired']['Dekat'], 2); ?>)</span>
                    <span class="badge bg-secondary">Lama (<?php echo round($fuzzifikasi['Expired']['Lama'], 2); ?>)</span>
                    <br><small class="text-muted">Kategori Dominan: <strong><?php echo FuzzyMamdani::getKategoriDominan($fuzzifikasi['Expired']); ?></strong></small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$recentMovements = StockManager::getItemMovements($id, 10);
?>
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white border-bottom pt-3 pb-2 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 text-primary-theme fw-bold">
            <i class="fas fa-history me-1"></i> Riwayat Pergerakan Stok Terkini
        </h6>
        <a href="stock_movement.php?item_id=<?php echo $id; ?>" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-exchange-alt me-1"></i> Catat / Kelola Mutasi
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Waktu</th>
                        <th>Jenis</th>
                        <th class="text-center">Jumlah</th>
                        <th class="text-center">Sebelum &rarr; Sesudah</th>
                        <th>No. Ref</th>
                        <th>Keterangan</th>
                        <th>Petugas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recentMovements)): ?>
                        <?php foreach ($recentMovements as $rm): ?>
                        <?php
                            $badge = '';
                            if ($rm['type'] === 'in') {
                                $badge = '<span class="badge bg-success"><i class="fas fa-arrow-down me-1"></i>Masuk</span>';
                            } elseif ($rm['type'] === 'out') {
                                $badge = '<span class="badge bg-danger"><i class="fas fa-arrow-up me-1"></i>Keluar</span>';
                            } elseif ($rm['type'] === 'adjustment') {
                                $badge = '<span class="badge bg-warning text-dark"><i class="fas fa-sliders-h me-1"></i>Opname</span>';
                            } elseif ($rm['type'] === 'opening_balance') {
                                $badge = '<span class="badge bg-info text-dark"><i class="fas fa-flag me-1"></i>Saldo Awal</span>';
                            }
                        ?>
                        <tr>
                            <td class="text-nowrap text-muted"><?php echo date('d/m/Y H:i', strtotime($rm['created_at'])); ?></td>
                            <td><?php echo $badge; ?></td>
                            <td class="text-center fw-bold">
                                <?php if ($rm['type'] === 'in'): ?>
                                    <span class="text-success">+<?php echo number_format($rm['quantity']); ?></span>
                                <?php elseif ($rm['type'] === 'out'): ?>
                                    <span class="text-danger">-<?php echo number_format($rm['quantity']); ?></span>
                                <?php else: ?>
                                    <span><?php echo number_format($rm['quantity']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center text-nowrap">
                                <span><?php echo number_format($rm['stock_before']); ?></span>
                                <i class="fas fa-arrow-right mx-1 text-muted"></i>
                                <strong><?php echo number_format($rm['stock_after']); ?></strong>
                            </td>
                            <td class="text-muted"><?php echo !empty($rm['reference_no']) ? htmlspecialchars($rm['reference_no']) : '-'; ?></td>
                            <td><?php echo !empty($rm['notes']) ? htmlspecialchars($rm['notes']) : '<span class="text-muted">-</span>'; ?></td>
                            <td class="text-nowrap text-muted"><?php echo htmlspecialchars($rm['user_name'] ?? 'Admin'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-3 text-muted">
                                Belum ada riwayat pergerakan stok untuk barang ini.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?>
