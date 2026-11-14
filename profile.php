<?php
require_once 'config/database.php';
include 'views/header.php';

$userId = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = $userId";
$user = db_query($query)->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];


    $is_password_valid = false;
    

    if ($password_lama === $user['password']) {
        $is_password_valid = true; 
    } 

    elseif (password_verify($password_lama, $user['password'])) {
        $is_password_valid = true; 
    }

    if (!$is_password_valid) {
        $_SESSION['error'] = "Verifikasi Gagal: Password Lama yang Anda masukkan salah!";
        echo "<script>window.location.href='profile.php';</script>";
        exit;
    }


    if (!empty($password_baru)) {

        $hashed = password_hash($password_baru, PASSWORD_DEFAULT);
        $q = "UPDATE users SET password='$hashed' WHERE id=$userId";
        
        if (db_query($q)) {
            $_SESSION['success'] = "Password berhasil diperbarui dan kini telah dienkripsi dengan aman di database.";
            db_query("INSERT INTO activity_logs (user_id, action) VALUES ($userId, 'Mengganti password akun')");
        } else {
            $_SESSION['error'] = "Terjadi kesalahan sistem. Gagal memperbarui password.";
        }
    } else {
        $_SESSION['error'] = "Anda tidak memasukkan password baru.";
    }
    
    echo "<script>window.location.href='profile.php';</script>";
    exit;
}
?>

<div class="card shadow-sm" style="max-width: 600px; margin: 0 auto;">
    <div class="card-header bg-white border-bottom pt-3 pb-2">
        <h5 class="mb-0 text-primary-theme"><i class="fas fa-lock me-2"></i> Ubah Kata Sandi</h5>
    </div>
    <div class="card-body p-4">
        
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

        <form method="POST" action="">

            <div class="mb-4">
                <label class="form-label fw-bold text-secondary">Nama Pengguna</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="fas fa-user text-muted"></i></span>
                    <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($user['username']); ?>" readonly disabled>
                </div>
                <small class="text-muted"><i class="fas fa-info-circle"></i> Nama Pengguna bersifat permanen dan tidak dapat diubah.</small>
            </div>

            <hr class="my-4 text-muted">

            <div class="mb-3">
                <label class="form-label fw-bold text-danger">Kata Sandi Lama <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="fas fa-unlock text-muted"></i></span>
                    <input type="password" name="password_lama" class="form-control" placeholder="Masukkan kata sandi Anda saat ini" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold text-primary">Kata Sandi Baru <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="fas fa-key text-muted"></i></span>
                    <input type="password" name="password_baru" class="form-control" placeholder="Masukkan kata sandi baru" required>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary w-100 py-2 mt-2">
                <i class="fas fa-save me-2"></i> Simpan Kata Sandi Baru
            </button>
        </form>
    </div>
</div>

<?php include 'views/footer.php'; ?>