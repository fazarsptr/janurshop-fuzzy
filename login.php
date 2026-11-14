<?php
session_start();
require_once 'config/database.php';


if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    global $conn; 
    $username = trim($_POST['username']);
    $password = $_POST['password'];


    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        

        $is_valid = false;
        
        if ($password === $user['password']) {
            $is_valid = true; 
        } elseif (password_verify($password, $user['password'])) {
            $is_valid = true; 
        }
        
        if ($is_valid) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            

            $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $update_stmt->bind_param("i", $user['id']);
            $update_stmt->execute();
            

            $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, 'Login berhasil')");
            $log_stmt->bind_param("i", $user['id']);
            $log_stmt->execute();

            header("Location: index.php");
            exit;
        } else {
            $error = "Kata Sandi salah!";
        }
    } else {
        $error = "Nama Pengguna tidak ditemukan!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - Janurshop Fuzzy</title>
    <link rel="stylesheet" href="assets/vendor/googlefonts/inter.css">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body.login-page {

            background-image: url('assets/bg log2.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            background-color: #2e7d32; 
        }
        .login-card {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4) !important;
            padding: 40px;
            border-radius: 15px;
            width: 100%;
            max-width: 500px;
            margin: 100px auto;
        }
    </style>
</head>
<body class="login-page">

    <div class="login-card bg-white">
        <div class="login-logo text-center">

            <img src="assets/Logo Janur.png" alt="Logo JanurShop" style="max-height: 120px; width: auto; margin-bottom: 25px;" onerror="this.style.display='none'">
        </div>
        
        <?php if($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="username" class="form-label fw-bold text-secondary">Nama Pengguna</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="fas fa-user text-muted"></i></span>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan Nama Pengguna" required>
                </div>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label fw-bold text-secondary">Kata Sandi</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan Kata Sandi" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2">
                <i class="fas fa-sign-in-alt me-2"></i> Masuk
            </button>
        </form>
    </div>

</body>
</html>