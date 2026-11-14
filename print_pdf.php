<?php
require_once 'config/database.php';

$supplier_id = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 0;
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : '';

$supplierNameLabel = "Semua Pemasok";
if ($supplier_id > 0) {
    $supQuery = db_query("SELECT name FROM suppliers WHERE id = $supplier_id");
    if ($sup = $supQuery->fetch_assoc()) {
        $supplierNameLabel = $sup['name'];
    }
}

$title = "Laporan";
$results = null;

switch ($report_type) {
    case 'data_barang':
        $title = "Laporan Data Barang";
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
        $title = "Laporan Analisis Fuzzy Mentah";
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
        $title = "Laporan Rekomendasi Pengadaan";
        $query = "SELECT r.*, i.name, i.price, s.name as supplier_name 
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
        $title = "Laporan Data Pemasok dan Barang";
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
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?php echo $title; ?> - Janurshop</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .header h2 { margin: 0 0 5px 0; }
        .header p { margin: 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row { font-weight: bold; background-color: #f9f9f9; }
        @media print { @page { margin: 1cm; } }
    </style>
</head>
<body onload="window.print()">

    <table style="width: 100%; border-collapse: collapse; border-bottom: 3px solid black; margin-bottom: 20px;">
        <tr>
            <td style="border: none; width: 15%; text-align: center; padding: 10px;">
                <img src="assets/Logo Janur.png" width="100" alt="Logo">
            </td>
            <td style="border: none; width: 85%; text-align: center; padding: 10px;">
                <h1 style="margin: 0; font-size: 26px; font-weight: bold;">JANURSHOP</h1>
                <p style="margin: 5px 0;">Jalan Kalibaru Timur No.6, Bekasi 17132</p>
                <p style="margin: 0;">Telp: +62 851-7317-9259 | E-mail: janurshopdigital@gmail.com</p>
            </td>
        </tr>
    </table>

    <div style="text-align: center; margin-bottom: 20px;">
        <h3 style="margin: 0 0 5px 0;"><?php echo $title; ?></h3>
        <p style="margin: 0; color: #666;">Tanggal Cetak: <?php echo date('d-m-Y H:i'); ?> | Filter: <?php echo htmlspecialchars($supplierNameLabel); ?></p>
    </div>

    <table>
        <?php if ($report_type == 'data_barang'): ?>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode</th>
                    <th>Nama Barang</th>
                    <th>Pemasok</th>
                    <th class="text-center">Stok Fisik (Pcs)</th>
                    <th class="text-right">Harga (Rp)</th>
                </tr>
            </thead>
            <tbody>
                <?php if($results && $results->num_rows > 0): $no=1; while($row = $results->fetch_assoc()): ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td><?php echo htmlspecialchars($row['code']); ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                    <td class="text-center"><?php echo number_format($row['stock'], 0, ',', '.'); ?></td>
                    <td class="text-right"><?php echo number_format($row['price'], 0, ',', '.'); ?></td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="6" class="text-center">Tidak ada data.</td></tr>
                <?php endif; ?>
            </tbody>
            
        <?php elseif ($report_type == 'analisis_fuzzy'): ?>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Barang</th>
                    <th>Pemasok</th>
                    <th class="text-center">Defuzzifikasi Z</th>
                    <th class="text-center">Status Prioritas</th>
                    <th class="text-center">Tanggal Analisis</th>
                </tr>
            </thead>
            <tbody>
                <?php if($results && $results->num_rows > 0): $no=1; while($row = $results->fetch_assoc()): ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                    <td class="text-center"><?php echo number_format($row['recommendation_z'], 2, ',', '.'); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($row['priority_status']); ?></td>
                    <td class="text-center"><?php echo date('d-m-Y', strtotime($row['calculation_date'])); ?></td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="6" class="text-center">Tidak ada data.</td></tr>
                <?php endif; ?>
            </tbody>

        <?php elseif ($report_type == 'pengadaan_barang'): ?>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Barang</th>
                    <th>Pemasok</th>
                    <th class="text-center">Jumlah Rekomendasi (Pcs)</th>
                    <th class="text-right">Harga Satuan (Rp)</th>
                    <th class="text-right">Total Harga (Rp)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if($results && $results->num_rows > 0): 
                    $no = 1; $grandTotal = 0;
                    while($row = $results->fetch_assoc()): 
                        $rekomendasi = round($row['recommendation_z']);
                        $hargaSatuan = $row['price'];
                        $totalHarga = $rekomendasi * $hargaSatuan;
                        $grandTotal += $totalHarga;
                ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                    <td class="text-center"><?php echo number_format($rekomendasi, 0, ',', '.'); ?></td>
                    <td class="text-right"><?php echo number_format($hargaSatuan, 0, ',', '.'); ?></td>
                    <td class="text-right"><?php echo number_format($totalHarga, 0, ',', '.'); ?></td>
                </tr>
                <?php endwhile; ?>
                <tr class="total-row">
                    <td colspan="5" class="text-right">ESTIMASI GRAND TOTAL PENGADAAN :</td>
                    <td class="text-right">Rp <?php echo number_format($grandTotal, 0, ',', '.'); ?></td>
                </tr>
                <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center">Semua stok dalam kondisi aman, tidak ada pengadaan yang diperlukan.</td>
                </tr>
                <?php endif; ?>
            </tbody>

        <?php elseif ($report_type == 'data_pemasok'): ?>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Pemasok</th>
                    <th>Waktu Tunggu (Hari)</th>
                    <th>Kode Barang</th>
                    <th>Nama Barang Disuplai</th>
                    <th class="text-center">Stok Fisik (Pcs)</th>
                </tr>
            </thead>
            <tbody>
                <?php if($results && $results->num_rows > 0): $no=1; while($row = $results->fetch_assoc()): ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($row['lead_time_days']); ?></td>
                    <td><?php echo $row['code'] ? htmlspecialchars($row['code']) : '-'; ?></td>
                    <td><?php echo $row['item_name'] ? htmlspecialchars($row['item_name']) : '-'; ?></td>
                    <td class="text-center"><?php echo $row['stock'] !== null ? number_format($row['stock'], 0, ',', '.') : '-'; ?></td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="6" class="text-center">Tidak ada data.</td></tr>
                <?php endif; ?>
            </tbody>
        <?php endif; ?>
    </table>

    <table style="width: 100%; border: none; margin-top: 40px;">
        <tr>
            <td style="border: none; width: 65%;"></td>
            <td style="border: none; width: 35%; text-align: center;">
                <p style="margin: 0;">Bekasi, <?php 
                    $hari_array = array(
                        'Sunday' => 'Minggu',
                        'Monday' => 'Senin',
                        'Tuesday' => 'Selasa',
                        'Wednesday' => 'Rabu',
                        'Thursday' => 'Kamis',
                        'Friday' => 'Jumat',
                        'Saturday' => 'Sabtu'
                    );
                    $hari = $hari_array[date('l')];
                    $bulan = array(1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
                    echo $hari . ', ' . date('d') . ' ' . $bulan[(int)date('m')] . ' ' . date('Y');
                ?></p>
                <p style="margin: 5px 0 70px 0;">Pemilik Usaha Janurshop</p>
                <p style="margin: 0; font-weight: bold; text-decoration: underline;">Wisnu Aria Budiandanu</p>
            </td>
        </tr>
    </table>

</body>
</html>