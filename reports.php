<?php
require_once 'config/database.php';
include 'views/header.php';


$query = "
    SELECT r.*, i.name, i.code, i.price, s.name as supplier_name
    FROM fuzzy_results r
    JOIN items i ON r.item_id = i.id
    LEFT JOIN suppliers s ON i.supplier_id = s.id
    WHERE r.recommendation_z > 0
    ORDER BY r.priority_status DESC, r.recommendation_z DESC
";
$results = db_query($query);
$suppliers = db_query("SELECT * FROM suppliers");
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Laporan Pengadaan Stok</h5>
    </div>
    <div class="card-body">


        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Barang</th>
                        <th>Pemasok</th>
                        <th>Rekomendasi Beli (Pcs)</th>
                        <th>Harga Satuan</th>
                        <th>Total Harga</th>
                        <th>Waktu Analisis</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($results && $results->num_rows > 0): ?>
                        <?php while($row = $results->fetch_assoc()): 
                            $badgeClass = '';
                            if ($row['priority_status'] == 'Prioritas Utama') $badgeClass = 'badge-prioritas-utama';
                            else if ($row['priority_status'] == 'Prioritas Cadangan') $badgeClass = 'badge-prioritas-cadangan';
                            else $badgeClass = 'badge-aman';
                            

                            $rekomendasiBulat = round($row['recommendation_z']);
                            

                            $totalHarga = $row['price'] * $rekomendasiBulat;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['code']); ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                            <td class="fw-bold fs-6 text-center"><?php echo number_format($rekomendasiBulat, 0, ',', '.'); ?></td>
                            <td>Rp <?php echo number_format($row['price'], 0, ',', '.'); ?></td>
                            <td class="fw-bold text-success">Rp <?php echo number_format($totalHarga, 0, ',', '.'); ?></td>
                            <td class="small text-muted"><?php echo $row['calculation_date']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center fw-bold text-success py-4">Semua stok dalam kondisi aman, tidak ada pengadaan yang diperlukan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>




<?php include 'views/footer.php'; ?>