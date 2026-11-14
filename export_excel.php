<?php
require_once 'config/database.php';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Laporan_Janurshop.xls");
header("Pragma: no-cache");
header("Expires: 0");

$supplier_id = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 0;
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : '';

$results = null;

switch ($report_type) {
    case 'data_barang':
        $query = "SELECT i.code, i.name, i.stock, i.price, s.name as supplier_name 
                  FROM items i 
                  LEFT JOIN suppliers s ON i.supplier_id = s.id";
        if ($supplier_id > 0) {
            $query .= " WHERE i.supplier_id = $supplier_id";
        }
        $query .= " ORDER BY i.name ASC";
        $results = db_query($query);
        break;
        
    case 'analisis_fuzzy':
        $query = "SELECT r.*, i.name, i.code, s.name as supplier_name 
                  FROM fuzzy_results r 
                  JOIN items i ON r.item_id = i.id 
                  LEFT JOIN suppliers s ON i.supplier_id = s.id";
        if ($supplier_id > 0) {
            $query .= " WHERE i.supplier_id = $supplier_id";
        }
        $query .= " ORDER BY r.calculation_date DESC";
        $results = db_query($query);
        break;
        
    case 'pengadaan_barang':
        $query = "SELECT r.*, i.name, i.code, i.price, s.name as supplier_name 
                  FROM fuzzy_results r 
                  JOIN items i ON r.item_id = i.id 
                  LEFT JOIN suppliers s ON i.supplier_id = s.id 
                  WHERE r.recommendation_z > 0";
        if ($supplier_id > 0) {
            $query .= " AND i.supplier_id = $supplier_id";
        }
        $query .= " ORDER BY r.priority_status DESC, r.recommendation_z DESC";
        $results = db_query($query);
        break;
        
    case 'data_pemasok':
        $query = "SELECT s.name as supplier_name, s.lead_time_days, i.code, i.name as item_name, i.stock 
                  FROM suppliers s 
                  LEFT JOIN items i ON s.id = i.supplier_id";
        if ($supplier_id > 0) {
            $query .= " WHERE s.id = $supplier_id";
        }
        $query .= " ORDER BY s.name ASC, i.name ASC";
        $results = db_query($query);
        break;
        
    default:
        die("Jenis laporan tidak valid!");
}
?>
<table border="1">
    <?php if ($report_type == 'data_barang'): ?>
        <tr>
            <th>Kode</th>
            <th>Nama Barang</th>
            <th>Pemasok</th>
            <th>Stok Fisik (Pcs)</th>
            <th>Harga (Rp)</th>
        </tr>
        <?php if($results && $results->num_rows > 0): while($row = $results->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['code']); ?></td>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
            <td><?php echo $row['stock']; ?></td>
            <td><?php echo $row['price']; ?></td>
        </tr>
        <?php endwhile; else: ?>
        <tr><td colspan="5">Tidak ada data.</td></tr>
        <?php endif; ?>
        
    <?php elseif ($report_type == 'analisis_fuzzy'): ?>
        <tr>
            <th>Nama Barang</th>
            <th>Pemasok</th>
            <th>Defuzzifikasi Z</th>
            <th>Status Prioritas</th>
            <th>Tanggal Analisis</th>
        </tr>
        <?php if($results && $results->num_rows > 0): while($row = $results->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
            <td><?php echo $row['recommendation_z']; ?></td>
            <td><?php echo htmlspecialchars($row['priority_status']); ?></td>
            <td><?php echo $row['calculation_date']; ?></td>
        </tr>
        <?php endwhile; else: ?>
        <tr><td colspan="5">Tidak ada data.</td></tr>
        <?php endif; ?>
        
    <?php elseif ($report_type == 'pengadaan_barang'): ?>
        <tr>
            <th>Kode Barang</th>
            <th>Nama Barang</th>
            <th>Pemasok</th>
            <th>Rekomendasi Beli (Pcs)</th>
            <th>Harga Satuan (Rp)</th>
            <th>Total Harga (Rp)</th>
            <th>Waktu Kalkulasi</th>
        </tr>
        <?php 
        if($results && $results->num_rows > 0): 
            while($row = $results->fetch_assoc()): 
                $rekomendasi = round($row['recommendation_z']);
                $totalHarga = $rekomendasi * $row['price'];
        ?>
        <tr>
            <td><?php echo htmlspecialchars($row['code']); ?></td>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
            <td><?php echo $rekomendasi; ?></td>
            <td><?php echo $row['price']; ?></td>
            <td><?php echo $totalHarga; ?></td>
            <td><?php echo $row['calculation_date']; ?></td>
        </tr>
        <?php endwhile; else: ?>
        <tr><td colspan="7">Semua stok dalam kondisi aman, tidak ada pengadaan yang diperlukan.</td></tr>
        <?php endif; ?>

    <?php elseif ($report_type == 'data_pemasok'): ?>
        <tr>
            <th>Nama Pemasok</th>
            <th>Waktu Tunggu (Hari)</th>
            <th>Kode Barang</th>
            <th>Nama Barang Disuplai</th>
            <th>Stok Fisik (Pcs)</th>
        </tr>
        <?php if($results && $results->num_rows > 0): while($row = $results->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
            <td><?php echo htmlspecialchars($row['lead_time_days']); ?></td>
            <td><?php echo $row['code'] ? htmlspecialchars($row['code']) : '-'; ?></td>
            <td><?php echo $row['item_name'] ? htmlspecialchars($row['item_name']) : '-'; ?></td>
            <td><?php echo $row['stock'] !== null ? $row['stock'] : '-'; ?></td>
        </tr>
        <?php endwhile; else: ?>
        <tr><td colspan="5">Tidak ada data.</td></tr>
        <?php endif; ?>
    <?php endif; ?>
</table>