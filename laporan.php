<?php
require_once 'config/database.php';
include 'views/header.php';

$suppliers = db_query("SELECT * FROM suppliers");
?>
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white border-bottom pt-3 pb-2">
        <h5 class="mb-0 text-primary-theme fw-bold"><i class="fas fa-print me-2"></i> Pusat Laporan</h5>
    </div>
    <div class="card-body">
        <form id="exportForm" method="GET" target="_blank">
            <div class="mb-3">
                <label class="form-label fw-bold">Pilih Jenis Laporan</label>
                <select name="report_type" class="form-select" required>
                    <option value="">-- Pilih Jenis Laporan --</option>
                    <option value="data_barang">Data Barang</option>
                    <option value="data_pemasok">Data Pemasok</option>
                    <option value="analisis_fuzzy">Analisis Fuzzy</option>
                    <option value="pengadaan_barang">Pengadaan Barang</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-bold">Filter Pemasok</label>
                <select name="supplier_id" class="form-select" required>
                    <option value="0">Semua Pemasok</option>
                    <?php 
                    if ($suppliers) {
                        $suppliers->data_seek(0);
                        while($sup = $suppliers->fetch_assoc()): ?>
                            <option value="<?php echo $sup['id']; ?>">
                                <?php echo htmlspecialchars($sup['name']); ?>
                            </option>
                        <?php endwhile;
                    }
                    ?>
                </select>
            </div>
            
            <div class="d-flex gap-2 mt-4">
                <button type="button" class="btn btn-danger" onclick="setExportAction('pdf')">
                    <i class="fas fa-file-pdf"></i> Cetak PDF
                </button>
                <button type="button" class="btn btn-success" onclick="setExportAction('excel')">
                    <i class="fas fa-file-excel"></i> Ekspor Excel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function setExportAction(type) {
    var form = document.getElementById('exportForm');
    var reportType = form.elements['report_type'].value;
    
    if (reportType === '') {
        alert('Silakan pilih jenis laporan terlebih dahulu!');
        return;
    }
    
    if (type === 'pdf') {
        form.action = 'print_pdf.php';
        form.target = '_blank';
        form.submit();
    } else if (type === 'excel') {
        form.action = 'export_excel.php';
        form.target = '';
        form.submit();
    }
}
</script>

<?php include 'views/footer.php'; ?>
