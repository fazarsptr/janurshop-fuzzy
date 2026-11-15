<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Ensure database connection is available
require_once __DIR__ . '/../config/database.php';

// Fetch low-stock products where stock <= min_stock_limit
$lowStockQuery = "SELECT id, code, name, stock, min_stock_limit FROM items WHERE stock <= min_stock_limit ORDER BY stock ASC";
$lowStockResult = db_query($lowStockQuery);
$lowStockItems = [];
if ($lowStockResult && $lowStockResult->num_rows > 0) {
    while ($lowStockRow = $lowStockResult->fetch_assoc()) {
        $lowStockItems[] = $lowStockRow;
    }
}
$lowStockCount = count($lowStockItems);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Janurshop - Fuzzy Inventory</title>

    <link rel="stylesheet" href="assets/vendor/googlefonts/inter.css">

    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">

    <link rel="stylesheet" href="assets/css/style.css">

    <script src="assets/vendor/chartjs/chart.min.js"></script>
    <style>

        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }


        input[type=number] {
            -moz-appearance: textfield;
        }
    </style>
</head>
<body>
    

    <?php include 'views/sidebar.php'; ?>


    <div class="main-content">

        <nav class="navbar navbar-expand-lg navbar-light bg-transparent mb-4">
            <div class="container-fluid">
                <span class="navbar-brand mb-0 h1" id="page-title">Beranda</span>
                <div class="d-flex align-items-center">
                    <!-- Low Stock Notification Dropdown -->
                    <div class="dropdown me-3">
                        <button class="btn btn-outline-secondary position-relative rounded-circle d-flex align-items-center justify-content-center" 
                                type="button" 
                                id="lowStockDropdown" 
                                data-bs-toggle="dropdown" 
                                aria-expanded="false" 
                                title="Low Stock Notifications"
                                style="width: 40px; height: 40px; border-color: rgba(0,0,0,0.15);">
                            <i class="fas fa-bell text-secondary"></i>
                            <?php if ($lowStockCount > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.7rem;">
                                    <?php echo $lowStockCount; ?>
                                    <span class="visually-hidden">unread notifications</span>
                                </span>
                            <?php endif; ?>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end shadow border-0 py-0" 
                             aria-labelledby="lowStockDropdown" 
                             style="width: 340px; max-width: 90vw; max-height: 420px; overflow-y: auto; border-radius: 10px;">
                            <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white sticky-top" style="border-top-left-radius: 10px; border-top-right-radius: 10px;">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-bell text-primary-theme me-2"></i>
                                    <span class="fw-bold text-dark">Low Stock Notifications</span>
                                </div>
                                <span class="badge <?php echo $lowStockCount > 0 ? 'bg-danger' : 'bg-success'; ?> rounded-pill">
                                    <?php echo $lowStockCount; ?>
                                </span>
                            </div>

                            <div class="list-group list-group-flush">
                                <?php if ($lowStockCount > 0): ?>
                                    <?php foreach ($lowStockItems as $lItem): ?>
                                        <a href="item_detail.php?id=<?php echo (int)$lItem['id']; ?>" class="list-group-item list-group-item-action py-2 px-3 border-bottom">
                                            <div class="d-flex w-100 justify-content-between align-items-start">
                                                <div class="me-2 text-truncate" style="max-width: 210px;">
                                                    <span class="fw-semibold text-dark d-block text-truncate"><?php echo htmlspecialchars($lItem['name']); ?></span>
                                                    <small class="text-muted">Code: <?php echo htmlspecialchars($lItem['code']); ?></small>
                                                </div>
                                                <span class="badge bg-danger rounded-pill flex-shrink-0">
                                                    <?php echo number_format($lItem['stock']); ?> Pcs
                                                </span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mt-1">
                                                <small class="text-danger fw-semibold">
                                                    <i class="fas fa-exclamation-circle me-1"></i>Min Limit: <?php echo number_format($lItem['min_stock_limit']); ?> Pcs
                                                </small>
                                                <small class="text-primary-theme">Details &rarr;</small>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="p-4 text-center text-muted">
                                        <i class="fas fa-check-circle text-success fa-2x mb-2 d-block"></i>
                                        <span class="d-block fw-semibold text-dark">Stock Levels are Healthy</span>
                                        <small class="text-muted">No products are currently at or below minimum stock limit.</small>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if ($lowStockCount > 0 && isset($_SESSION['role']) && $_SESSION['role'] !== 'pemilik'): ?>
                                <div class="p-2 border-top text-center bg-light sticky-bottom" style="border-bottom-left-radius: 10px; border-bottom-right-radius: 10px;">
                                    <a href="items.php" class="text-decoration-none small fw-bold text-primary-theme">
                                        View Inventory List <i class="fas fa-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <span class="me-3">Halo, <strong>Janur</strong></span>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm"><i class="fas fa-sign-out-alt"></i> Keluar</a>
                </div>
            </div>
        </nav>
        

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
