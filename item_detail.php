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

<?php include 'views/footer.php'; ?>
