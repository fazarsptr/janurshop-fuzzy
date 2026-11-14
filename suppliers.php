<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['role']) && $_SESSION['role'] === 'pemilik') {
    header("Location: index.php");
    exit;
}
require_once 'config/database.php';
include 'views/header.php';

global $conn;


if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    

    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM items WHERE supplier_id = ?");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result()->fetch_assoc();
    
    if ($check_result['count'] > 0) {

        $_SESSION['error'] = "<b>GAGAL DIHAPUS:</b> Pemasok ini tidak dapat dihapus karena masih digunakan oleh " . $check_result['count'] . " Data Barang. Silakan ubah atau hapus barang terkait terlebih dahulu.";
    } else {

        $stmt = $conn->prepare("DELETE FROM suppliers WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Data Pemasok berhasil dihapus secara permanen.";
        } else {
            $_SESSION['error'] = "Terjadi kesalahan sistem saat menghapus data.";
        }
    }
    echo "<script>window.location.href='suppliers.php';</script>";
    exit;
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $lead_time_days = (int)$_POST['lead_time_days'];
    
    if (isset($_POST['id']) && !empty($_POST['id'])) {

        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("UPDATE suppliers SET name = ?, lead_time_days = ? WHERE id = ?");
        $stmt->bind_param("sii", $name, $lead_time_days, $id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Data Pemasok berhasil diperbarui.";
        } else {
            $_SESSION['error'] = "Gagal memperbarui data.";
        }
    } else {

        $stmt = $conn->prepare("INSERT INTO suppliers (name, lead_time_days) VALUES (?, ?)");
        $stmt->bind_param("si", $name, $lead_time_days);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Pemasok baru berhasil ditambahkan.";
        } else {
            $_SESSION['error'] = "Gagal menambahkan data pemasok.";
        }
    }
    echo "<script>window.location.href='suppliers.php';</script>";
    exit;
}


$query = "SELECT * FROM suppliers ORDER BY id DESC";
$results = db_query($query);
?>


<div class="card shadow-sm mb-4">
    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center pt-3 pb-2">
        <h5 class="mb-0 text-primary-theme fw-bold"><i class="fas fa-truck me-2"></i> Data Pemasok</h5>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdd">
            <i class="fas fa-plus-circle me-1"></i> Tambah Pemasok
        </button>
    </div>
    <div class="card-body">
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-striped table-hover table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="5%" class="text-center">No</th>
                        <th>Nama Pemasok</th>
                        <th width="20%" class="text-center">Waktu Tunggu</th>
                        <th width="15%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($results && $results->num_rows > 0): ?>
                        <?php $no = 1; while($row = $results->fetch_assoc()): ?>
                        <tr>
                            <td class="text-center"><?php echo $no++; ?></td>
                            <td class="fw-bold"><?php echo htmlspecialchars($row['name']); ?></td>
                            <td class="text-center">
                                <span class="badge bg-info text-dark fs-6 px-3 py-2">
                                    <i class="fas fa-clock me-1"></i> <?php echo $row['lead_time_days']; ?> Hari
                                </span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-warning me-1" title="Ubah" 
                                    onclick="openEditModal(<?php echo $row['id']; ?>, '<?php echo addslashes($row['name']); ?>', <?php echo $row['lead_time_days']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="suppliers.php?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" title="Hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus pemasok ini? Tindakan ini tidak dapat dibatalkan!');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">Belum ada data pemasok yang ditambahkan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<div class="modal fade" id="modalForm" tabindex="-1" aria-labelledby="modalFormLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content border-0 shadow">
      <form method="POST" action="suppliers.php">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title" id="modalFormLabel"><i class="fas fa-truck-loading me-2"></i> Form Pemasok</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">

            <input type="hidden" name="id" id="form_id">
            
            <div class="mb-3">
                <label class="form-label fw-bold">Nama Pemasok <span class="text-danger">*</span></label>
                <input type="text" name="name" id="form_name" class="form-control" required placeholder="Contoh: PT. Sentosa Abadi">
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-bold">Waktu Tunggu (Hari) <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="number" name="lead_time_days" id="form_lead_time" class="form-control" required min="1" placeholder="Contoh: 3">
                    <span class="input-group-text bg-light">Hari</span>
                </div>
                <small class="text-muted"><i class="fas fa-info-circle"></i> Berpengaruh pada perhitungan himpunan Fuzzy Lead Time.</small>
            </div>
          </div>
          <div class="modal-footer bg-light">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan Data</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script>

document.addEventListener('DOMContentLoaded', function() {
    var modalAddBtn = document.querySelector('[data-bs-target="#modalAdd"]');
    if(modalAddBtn) {
        modalAddBtn.addEventListener('click', function() {
            document.getElementById('modalFormLabel').innerHTML = '<i class="fas fa-plus-circle me-2"></i> Tambah Pemasok Baru';
            document.getElementById('form_id').value = '';
            document.getElementById('form_name').value = '';
            document.getElementById('form_lead_time').value = '';
            var modal = new bootstrap.Modal(document.getElementById('modalForm'));
            modal.show();
        });
    }
});


function openEditModal(id, name, leadTime) {
    document.getElementById('modalFormLabel').innerHTML = '<i class="fas fa-edit me-2"></i> Ubah Data Pemasok';
    document.getElementById('form_id').value = id;
    document.getElementById('form_name').value = name;
    document.getElementById('form_lead_time').value = leadTime;
    var modal = new bootstrap.Modal(document.getElementById('modalForm'));
    modal.show();
}
</script>

<?php include 'views/footer.php'; ?>