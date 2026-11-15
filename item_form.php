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
$item = null;
if ($id > 0) {
    $result = db_query("SELECT i.*, s.name as supplier_name FROM items i LEFT JOIN suppliers s ON i.supplier_id = s.id WHERE i.id = $id");
    if ($result && $result->num_rows > 0) {
        $item = $result->fetch_assoc();
    } else {
        echo "<script>window.location.href='items.php';</script>";
        exit;
    }
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $code = db_escape($_POST['code']);
    $name = db_escape($_POST['name']);
    $stock = (float)$_POST['stock'];
    $demand = (float)$_POST['demand'];
    $supplier_id = (int)$_POST['supplier_id'];
    $price = (float)$_POST['price'];
    $expired_days = (float)$_POST['expired_days'];
    $min_stock_limit = (float)$_POST['min_stock_limit'];
    $stok_max = (float)$_POST['stok_max'];

    $fs_b = (float)$_POST['fuzzy_stok_bawah'];
    $fs_t = (float)$_POST['fuzzy_stok_tengah'];
    $fs_a = (float)$_POST['fuzzy_stok_atas'];

    $fd_b = (float)$_POST['fuzzy_demand_bawah'];
    $fd_t = (float)$_POST['fuzzy_demand_tengah'];
    $fd_a = (float)$_POST['fuzzy_demand_atas'];
    $fl_b = (float)$_POST['fuzzy_leadtime_bawah'];
    $fl_t = (float)$_POST['fuzzy_leadtime_tengah'];
    $fl_a = (float)$_POST['fuzzy_leadtime_atas'];

    $fh_b = (float)$_POST['fuzzy_harga_bawah'];
    $fh_a = (float)$_POST['fuzzy_harga_atas'];

    $fe_b = (float)$_POST['fuzzy_expired_bawah'];
    $fe_a = (float)$_POST['fuzzy_expired_atas'];
    if ($id > 0) {
        // When editing an existing item, physical stock is NOT modified directly here.
        // All stock mutations must be recorded through StockManager / stock_movement.php.
        $q = "UPDATE items SET
              code='$code', name='$name', demand=$demand, supplier_id=$supplier_id,
              price=$price, expired_days=$expired_days, min_stock_limit=$min_stock_limit, stok_max=$stok_max,
              fuzzy_stok_bawah=$fs_b, fuzzy_stok_tengah=$fs_t, fuzzy_stok_atas=$fs_a,
              fuzzy_demand_bawah=$fd_b, fuzzy_demand_tengah=$fd_t, fuzzy_demand_atas=$fd_a,
              fuzzy_leadtime_bawah=$fl_b, fuzzy_leadtime_tengah=$fl_t, fuzzy_leadtime_atas=$fl_a,
              fuzzy_harga_bawah=$fh_b, fuzzy_harga_atas=$fh_a,
              fuzzy_expired_bawah=$fe_b, fuzzy_expired_atas=$fe_a
              WHERE id=$id";
        db_query($q);
        db_query("INSERT INTO activity_logs (user_id, action) VALUES (" . $_SESSION['user_id'] . ", 'Update barang ID: $id')");
        $_SESSION['success'] = "Data barang berhasil diperbarui.";
        echo "<script>window.location.href='items.php';</script>";
        exit;
    } else {
        // When adding a new item, create item and its initial opening balance atomically
        $createResult = StockManager::createItemWithInitialStock($_POST, (int)$_SESSION['user_id']);
        if ($createResult['success']) {
            $_SESSION['success'] = "Data barang baru berhasil ditambahkan beserta saldo awal stok.";
            echo "<script>window.location.href='items.php';</script>";
            exit;
        } else {
            $_SESSION['error'] = "Gagal menambahkan barang: " . htmlspecialchars($createResult['error']);
        }
    }
}

$suppliers = db_query("SELECT * FROM suppliers");
?>
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><?php echo $id > 0 ? 'Ubah Data Barang' : 'Tambah Data Barang Baru'; ?></h5>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <div class="row">

                <div class="col-md-6 border-end">
                    <h6 class="text-primary-theme fw-bold mb-3"><i class="fas fa-box"></i> Data Utama</h6>
                    <div class="mb-3">
                        <label>Kode Barang</label>
                        <input type="text" name="code" class="form-control" value="<?php echo $item ? $item['code'] : ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label>Nama Barang</label>
                        <input type="text" name="name" class="form-control" value="<?php echo $item ? $item['name'] : ''; ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Stok Saat Ini (Pcs)</label>
                            <?php if ($id > 0): ?>
                                <div class="input-group">
                                    <input type="number" name="stock" class="form-control bg-light" value="<?php echo $item ? $item['stock'] : ''; ?>" readonly>
                                    <a href="stock_movement.php?item_id=<?php echo $item['id']; ?>" class="btn btn-outline-secondary" title="Kelola Mutasi Stok">
                                        <i class="fas fa-exchange-alt"></i> Mutasi
                                    </a>
                                </div>
                                <small class="text-muted"><i class="fas fa-lock text-warning"></i> Stok dikunci. Untuk mengubah jumlah stok, silakan gunakan menu <strong>Mutasi Stok</strong>.</small>
                            <?php else: ?>
                                <input type="number" step="1" min="0" name="stock" class="form-control" value="0" required>
                                <small class="text-muted">Stok awal akan dicatat otomatis sebagai saldo awal.</small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Permintaan</label>
                            <input type="number" step="0.01" name="demand" class="form-control" value="<?php echo $item ? $item['demand'] : ''; ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Pemasok</label>
                        <select name="supplier_id" class="form-select" required>
                            <option value="">-- Pilih Pemasok --</option>
                            <?php while($sup = $suppliers->fetch_assoc()): ?>
                                <option value="<?php echo $sup['id']; ?>" <?php echo ($item && $item['supplier_id'] == $sup['id']) ? 'selected' : ''; ?>>
                                    <?php echo $sup['name']; ?> (Waktu Tunggu: <?php echo $sup['lead_time_days']; ?> hari)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Harga (Rp)</label>
                            <input type="number" step="0.01" name="price" class="form-control" value="<?php echo $item ? $item['price'] : ''; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Kedaluwarsa (Sisa Hari)</label>
                            <input type="number" step="0.01" name="expired_days" class="form-control" value="<?php echo $item ? $item['expired_days'] : ''; ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Batas Stok Kritis (Min)</label>
                            <input type="number" step="0.01" name="min_stock_limit" class="form-control" value="<?php echo $item ? $item['min_stock_limit'] : ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3 bg-light p-2 rounded">
                            <label class="fw-bold text-danger">Stok Maksimal (Defuzzifikasi)</label>
                            <input type="number" step="0.01" name="stok_max" class="form-control" value="<?php echo $item ? $item['stok_max'] : ''; ?>" required>
                            <small class="text-muted">Kapasitas maksimal barang ini untuk perhitungan rekomendasi.</small>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <h6 class="text-primary-theme fw-bold mb-3"><i class="fas fa-sliders-h"></i> Parameter Batas Fuzzy Khusus Barang Ini</h6>
                    <p class="small text-muted">Sesuaikan titik batas himpunan linguistik spesifik untuk barang ini.</p>

                    <div class="row mb-2">
                        <div class="col-12"><label class="fw-bold">Stok (Bawah, Tengah, Atas)</label></div>
                        <div class="col-4"><input type="number" step="0.01" name="fuzzy_stok_bawah" class="form-control form-control-sm" value="<?php echo $item ? $item['fuzzy_stok_bawah'] : ''; ?>"></div>
                        <div class="col-4"><input type="number" step="0.01" name="fuzzy_stok_tengah" class="form-control form-control-sm" value="<?php echo $item ? $item['fuzzy_stok_tengah'] : ''; ?>"></div>
                        <div class="col-4"><input type="number" step="0.01" name="fuzzy_stok_atas" class="form-control form-control-sm" value="<?php echo $item ? $item['fuzzy_stok_atas'] : ''; ?>"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-12"><label class="fw-bold">Permintaan (Bawah, Tengah, Atas)</label></div>
                        <div class="col-4"><input type="number" step="0.01" name="fuzzy_demand_bawah" class="form-control form-control-sm" value="<?php echo $item ? $item['fuzzy_demand_bawah'] : ''; ?>"></div>
                        <div class="col-4"><input type="number" step="0.01" name="fuzzy_demand_tengah" class="form-control form-control-sm" value="<?php echo $item ? $item['fuzzy_demand_tengah'] : ''; ?>"></div>
                        <div class="col-4"><input type="number" step="0.01" name="fuzzy_demand_atas" class="form-control form-control-sm" value="<?php echo $item ? $item['fuzzy_demand_atas'] : ''; ?>"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-12"><label class="fw-bold">Waktu Tunggu (Bawah, Tengah, Atas)</label></div>
                        <div class="col-4"><input type="number" step="0.01" name="fuzzy_leadtime_bawah" class="form-control form-control-sm" value="<?php echo $item ? $item['fuzzy_leadtime_bawah'] : '1'; ?>"></div>
                        <div class="col-4"><input type="number" step="0.01" name="fuzzy_leadtime_tengah" class="form-control form-control-sm" value="<?php echo $item ? $item['fuzzy_leadtime_tengah'] : '4'; ?>"></div>
                        <div class="col-4"><input type="number" step="0.01" name="fuzzy_leadtime_atas" class="form-control form-control-sm" value="<?php echo $item ? $item['fuzzy_leadtime_atas'] : '7'; ?>"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-12"><label class="fw-bold">Harga (Murah Bawah, Mahal Atas)</label></div>
                        <div class="col-6"><input type="number" step="0.01" name="fuzzy_harga_bawah" class="form-control form-control-sm" value="<?php echo $item ? $item['fuzzy_harga_bawah'] : '20000'; ?>"></div>
                        <div class="col-6"><input type="number" step="0.01" name="fuzzy_harga_atas" class="form-control form-control-sm" value="<?php echo $item ? $item['fuzzy_harga_atas'] : '745000'; ?>"></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12"><label class="fw-bold">Kedaluwarsa (Dekat Bawah, Lama Atas)</label></div>
                        <div class="col-6"><input type="number" step="0.01" name="fuzzy_expired_bawah" class="form-control form-control-sm" value="<?php echo $item ? $item['fuzzy_expired_bawah'] : '30'; ?>"></div>
                        <div class="col-6"><input type="number" step="0.01" name="fuzzy_expired_atas" class="form-control form-control-sm" value="<?php echo $item ? $item['fuzzy_expired_atas'] : '365'; ?>"></div>
                    </div>
                </div>
            </div>
            <hr>
            <div class="text-end">
                <a href="items.php" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Data</button>
            </div>
        </form>
    </div>
</div>
<?php include 'views/footer.php'; ?>
