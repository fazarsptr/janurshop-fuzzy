<?php
require_once 'config/database.php';
include 'views/header.php';


$totalItems = db_query("SELECT COUNT(*) as total FROM items")->fetch_assoc()['total'];
$totalSuppliers = db_query("SELECT COUNT(*) as total FROM suppliers")->fetch_assoc()['total'];


$kritisResult = db_query("SELECT name, stock, min_stock_limit FROM items WHERE stock <= min_stock_limit ORDER BY stock ASC LIMIT 10");
$labelKritis = [];
$dataStokKritis = [];
$dataBatasKritis = [];
while($r = $kritisResult->fetch_assoc()) {
    $labelKritis[] = $r['name'];
    $dataStokKritis[] = $r['stock'];
    $dataBatasKritis[] = $r['min_stock_limit'];
}


$topResult = db_query("
    SELECT name, stock 
    FROM items 
    ORDER BY stock ASC 
    LIMIT 10
");
$labelTop = [];
$dataTop = [];
while($r = $topResult->fetch_assoc()) {
    $labelTop[] = $r['name'];
    $dataTop[] = $r['stock'];
}


$statsPrioritas = db_query("SELECT priority_status, COUNT(*) as total FROM fuzzy_results GROUP BY priority_status");
$countUtama = 0;
$countCadangan = 0;
$countAman = 0;
while($r = $statsPrioritas->fetch_assoc()) {
    if ($r['priority_status'] == 'Prioritas Utama') $countUtama = $r['total'];
    if ($r['priority_status'] == 'Prioritas Cadangan') $countCadangan = $r['total'];
    if ($r['priority_status'] == 'Aman') $countAman = $r['total'];
}
?>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-box fa-3x opacity-50 me-3"></i>
                <div>
                    <h6 class="card-title mb-0">Total Data Barang</h6>
                    <h2 class="mb-0 fw-bold"><?php echo $totalItems; ?></h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white h-100">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-exclamation-triangle fa-3x opacity-50 me-3"></i>
                <div>
                    <h6 class="card-title mb-0">Prioritas Utama</h6>
                    <h2 class="mb-0 fw-bold"><?php echo $countUtama; ?></h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-dark h-100">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-exclamation-circle fa-3x opacity-50 me-3"></i>
                <div>
                    <h6 class="card-title mb-0">Prioritas Cadangan</h6>
                    <h2 class="mb-0 fw-bold"><?php echo $countCadangan; ?></h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-check-circle fa-3x opacity-50 me-3"></i>
                <div>
                    <h6 class="card-title mb-0">Stok Aman</h6>
                    <h2 class="mb-0 fw-bold"><?php echo $countAman; ?></h2>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">

    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-header text-primary-theme"><i class="fas fa-chart-pie"></i> Status Pengadaan Keseluruhan</div>
            <div class="card-body d-flex justify-content-center align-items-center">
                <div style="width: 100%; max-width: 400px;">
                    <canvas id="chartStatus"></canvas>
                </div>
            </div>
        </div>
    </div>
    

    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header"><i class="fas fa-tachometer-alt text-primary-theme"></i> Akurasi Sistem (MAPE)</div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center">
                <div style="width: 80%; position: relative;">
                    <canvas id="chartGauge"></canvas>
                    <div style="position: absolute; top: 60%; left: 50%; transform: translate(-50%, -50%); text-align: center;">
                        <h3 class="mb-0 text-success fw-bold">32.62%</h3>
                        <small class="text-muted">Tingkat Kesalahan</small>
                    </div>
                </div>
                <p class="text-center text-muted small mt-3">Validasi Hasil sistem menunjukan tingkat kesalahan (MAPE) sebesar 32.96%</p>
            </div>
        </div>
    </div>
</div>

<div class="row">

    <div class="col-12">
        <div class="card">
            <div class="card-header"><i class="fas fa-chart-bar text-primary-theme"></i> 10 Barang Kritis</div>
            <div class="card-body">
                <canvas id="chartTopPriority" height="80"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {

    const ctxTop = document.getElementById('chartTopPriority').getContext('2d');
    new Chart(ctxTop, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($labelTop); ?>,
            datasets: [{
                label: 'Stok Fisik (Pcs)',
                data: <?php echo json_encode($dataTop); ?>,
                backgroundColor: 'rgba(211, 47, 47, 0.8)',
                borderColor: '#d32f2f',
                borderWidth: 1,
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true } }
        }
    });


    const ctxStatus = document.getElementById('chartStatus').getContext('2d');
    new Chart(ctxStatus, {
        type: 'pie',
        data: {
            labels: ['Prioritas Utama', 'Prioritas Cadangan', 'Aman'],
            datasets: [{
                data: [<?php echo $countUtama; ?>, <?php echo $countCadangan; ?>, <?php echo $countAman; ?>],
                backgroundColor: ['#dc3545', '#ffc107', '#198754'], 
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });


    const ctxGauge = document.getElementById('chartGauge').getContext('2d');
    new Chart(ctxGauge, {
        type: 'doughnut',
        data: {
            labels: ['Tingkat Kesalahan (MAPE)', 'Akurasi'],
            datasets: [{
                data: [33, 67],
                backgroundColor: ['#e53935', '#4CAF50'],
                borderWidth: 0,
                cutout: '80%',
                rotation: -90,
                circumference: 180,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: { enabled: true }
            }
        }
    });
});
</script>

<?php include 'views/footer.php'; ?>
