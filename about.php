<?php
require_once 'config/database.php';
include 'views/header.php';
?>

<div class="row">

    <div class="col-md-5 mb-4">

        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-white text-primary-theme fw-bold border-bottom-0 pt-4 pb-0">
                <h5 class="mb-0"><i class="fas fa-id-card me-2"></i> Informasi Pembuat</h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <div class="d-inline-flex justify-content-center align-items-center bg-light rounded-circle" style="width: 80px; height: 80px;">
                        <i class="fas fa-user-graduate fa-3x text-success opacity-50"></i>
                    </div>
                </div>
                <table class="table table-sm table-borderless">
                    <tr><td width="100" class="text-muted">Nama</td><td></td><td>: <strong>Muhammad Fazar Saputra</strong></td></tr>
                    <tr><td class="text-muted">NPM</td><td></td><td>: <strong>202243500889</strong></td></tr>
                    <tr><td class="text-muted">Program Studi</td><td></td><td>: <strong>Teknik Informatika</strong></td></tr>
                    <tr><td class="text-muted">Universitas</td><td></td><td>: <strong>Universitas Indraprasta PGRI</strong></td></tr>
                    <tr><td class="text-muted">Tahun</td><td></td><td>: <strong>2026</strong></td></tr>
                </table>
                <p class="text-muted small mt-3 text-center border-top pt-3">
                    Aplikasi ini dibangun sebagai bagian dari Tugas Akhir / Skripsi yang mengimplementasikan Algoritma <strong>Logika Fuzzy Metode Mamdani</strong> untuk optimasi persediaan barang pada Janurshop.
                </p>
            </div>
        </div>


        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white text-primary-theme fw-bold">
                <i class="fas fa-laptop-code me-2"></i> Spesifikasi Sistem
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span class="text-muted"><i class="fab fa-php me-2"></i>Bahasa Inti</span>
                        <span class="badge bg-secondary">PHP Native 8+</span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span class="text-muted"><i class="fas fa-database me-2"></i>Database</span>
                        <span class="badge bg-secondary">MySQL / MariaDB</span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span class="text-muted"><i class="fab fa-bootstrap me-2"></i>Antarmuka (UI)</span>
                        <span class="badge bg-secondary">Bootstrap 5 + CSS Custom</span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span class="text-muted"><i class="fas fa-chart-pie me-2"></i>Visualisasi Data</span>
                        <span class="badge bg-secondary">Chart.js</span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span class="text-muted"><i class="fas fa-brain me-2"></i>Algoritma Analisis</span>
                        <span class="badge bg-success">Fuzzy Mamdani (108 Rules)</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>


    <div class="col-md-7 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white text-primary-theme fw-bold pt-4 pb-2 border-bottom">
                <h5 class="mb-0"><i class="fas fa-book-open me-2"></i> Panduan Penggunaan Aplikasi</h5>
            </div>
            <div class="card-body">
                <div class="accordion accordion-flush" id="accordionPanduan">
                    

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                <strong>1. Mengelola Data Master Barang</strong>
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#accordionPanduan">
                            <div class="accordion-body text-muted small">
                                <p>Navigasi ke menu <strong>Master Barang</strong>. Di sini Anda dapat menambahkan, mengedit, atau menghapus daftar produk.</p>
                                <ul>
                                    <li>Pastikan menginput nilai Stok, Demand, Lead Time (berdasarkan Supplier), Harga, dan angka Expired saat ini dengan benar.</li>
                                    <li><strong>Sangat Penting:</strong> Saat menginput barang, Anda bisa menyesuaikan kurva keanggotaan Fuzzy (Titik Bawah, Tengah, Atas) secara dinamis agar sesuai dengan karakteristik laku/tidaknya barang tersebut.</li>
                                    <li>Tentukan juga nilai <strong>Stok Maksimal</strong>. Nilai ini krusial karena digunakan sebagai titik tertinggi perhitungan Defuzzifikasi (pembobotan akhir) agar aplikasi tahu seberapa banyak kapasitas tampung barang ini.</li>
                                </ul>
                            </div>
                        </div>
                    </div>


                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                <strong>2. Menjalankan Analisis Logika Fuzzy</strong>
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionPanduan">
                            <div class="accordion-body text-muted small">
                                <p>Buka menu <strong>Analisis Fuzzy</strong>, lalu klik tombol hijau <span class="badge bg-success">Jalankan Analisis (Semua Barang)</span>.</p>
                                <p>Sistem akan secara otomatis membaca nilai mentah (raw data) seluruh barang Anda, melakukan proses <em>Fuzzifikasi</em>, mencocokkannya dengan <strong>108 Basis Aturan (Rules)</strong> yang telah ditanam di program, lalu melakukan <em>Defuzzifikasi</em> untuk menemukan nilai bobot mutlak ($Z$).</p>
                            </div>
                        </div>
                    </div>


                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                <strong>3. Membaca Status Prioritas Keputusan</strong>
                            </button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordionPanduan">
                            <div class="accordion-body text-muted small">
                                Setelah analisis selesai, sistem akan mengeluarkan rekomendasi angka riil barang yang harus disuplai ulang beserta 3 kategori prioritas:
                                <ul class="mt-2">
                                    <li><span class="badge badge-prioritas-utama">Prioritas Utama</span> : Barang hampir habis dan demand sedang tinggi. Wajib segera pesan ulang dengan kuantitas (Banyak / Sangat Banyak).</li>
                                    <li><span class="badge badge-prioritas-cadangan">Prioritas Cadangan</span> : Stok masih ada tapi mulai menipis. Lakukan pengadaan penyangga (Sedikit / Sedang).</li>
                                    <li><span class="badge badge-aman">Aman</span> : Stok masih melimpah atau demand sangat rendah. Rekomendasi (Tidak Beli).</li>
                                </ul>
                            </div>
                        </div>
                    </div>


                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingFour">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                <strong>4. Pemantauan & Pelaporan (Cetak PDF/Excel)</strong>
                            </button>
                        </h2>
                        <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#accordionPanduan">
                            <div class="accordion-body text-muted small">
                                <ul>
                                    <li>Gunakan <strong>Dashboard</strong> untuk memantau grafik barang kritis secara visual dan melihat peringkat 10 besar barang yang paling darurat untuk dibeli.</li>
                                    <li>Gunakan menu <strong>Laporan Pengadaan</strong> jika Anda ingin menyaring (filter) data rekomendasi tersebut ke pabrik/suplier tertentu. Anda bisa langsung mencetak nota PDF atau mendownloadnya dalam bentuk Excel (.xls) untuk diarsip.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?>
