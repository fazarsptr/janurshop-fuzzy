<?php

$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar">
    <div class="sidebar-brand text-center py-3">
        <img src="assets/Logo Janur.png" alt="Logo JanurShop" style="max-height: 50px; width: auto;" onerror="this.style.display='none'">
    </div>
    
    <ul class="nav flex-column mt-4">
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>" href="index.php">
                <i class="fas fa-tachometer-alt fa-fw"></i> Beranda
            </a>
        </li>
        <?php if ($_SESSION['role'] !== 'pemilik'): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_page == 'items.php' || $current_page == 'item_form.php' || $current_page == 'item_detail.php') ? 'active' : ''; ?>" href="items.php">
                <i class="fas fa-box fa-fw"></i>
                <span class="flex-grow-1">Data Barang</span>
                <?php if (isset($lowStockCount) && $lowStockCount > 0): ?>
                    <span class="badge rounded-pill bg-danger ms-auto" title="<?php echo $lowStockCount; ?> low-stock products"><?php echo $lowStockCount; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_page == 'suppliers.php' || $current_page == 'suppliers.php' || $current_page == 'supplier_detail.php') ? 'active' : ''; ?>" href="suppliers.php">
                <i class="fas fa-truck fa-fw"></i> Data Pemasok
            </a>
        </li>
        <?php endif; ?>
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_page == 'stock_movement.php') ? 'active' : ''; ?>" href="stock_movement.php">
                <i class="fas fa-exchange-alt fa-fw"></i> Riwayat Mutasi
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_page == 'analysis.php') ? 'active' : ''; ?>" href="analysis.php">
                <i class="fas fa-cogs fa-fw"></i> Analisis Fuzzy
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>" href="reports.php">
                <i class="fas fa-file-alt fa-fw"></i> Pengadaan
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_page == 'laporan.php') ? 'active' : ''; ?>" href="laporan.php">
                <i class="fas fa-print fa-fw"></i> Pusat Laporan
            </a>
        </li>
        <li class="nav-item mt-4">
            <a class="nav-link <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>" href="profile.php">
                <i class="fas fa-user-cog fa-fw"></i> Manajemen Pengguna
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_page == 'logs.php') ? 'active' : ''; ?>" href="logs.php">
                <i class="fas fa-history fa-fw"></i> Log Aktivitas
            </a>
        </li>
        <li class="nav-item mt-4">
            <a class="nav-link <?php echo ($current_page == 'about.php') ? 'active' : ''; ?>" href="about.php">
                <i class="fas fa-info-circle fa-fw"></i> Tentang Aplikasi
            </a>
        </li>
    </ul>
</div>
