<?php 
session_start();
include '../../config/database.php'; 

// Ambil ID Transaksi dari URL
$transaksi_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($transaksi_id == 0) {
    echo "<script>alert('Data transaksi tidak ditemukan!'); window.location='surat_aktif.php';</script>";
    exit;
}

// 1. AMBIL DATA HEADER TRANSAKSI
$qHeader = mysqli_query($koneksi, "
    SELECT t.TransaksiID, t.TanggalWaktu, t.TotalTransaksi, p.NamaPelanggan 
    FROM transaksi t
    LEFT JOIN pelanggan p ON t.PelangganID = p.PelangganID
    WHERE t.TransaksiID = $transaksi_id
");

$header = mysqli_fetch_assoc($qHeader);

if(!$header) {
    echo "<script>alert('Surat Emas tidak ditemukan!'); window.location='surat_aktif.php';</script>";
    exit;
}

// Format Nomor Surat & Tanggal
$noSurat = "TRX-" . sprintf("%04d", $header['TransaksiID']);
$tglTransaksi = date('d F Y - H:i', strtotime($header['TanggalWaktu']));

include '../../layouts/header.php'; 
include '../../layouts/sidebar.php'; 
?>

<div class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Detail Transaksi <span class="text-primary">#<?php echo $noSurat; ?></span></h2>
            <p class="text-muted mb-0">Rincian data penjualan dan daftar barang.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="surat_aktif.php" class="btn btn-light border rounded-pill px-4 shadow-sm">
                <i class="bi bi-arrow-left me-2"></i> Kembali
            </a>
            <a href="surat_emas.php?id=<?php echo $transaksi_id; ?>" target="_blank" class="btn btn-primary rounded-pill px-4 shadow-sm fw-semibold">
                <i class="bi bi-file-earmark-richtext me-2"></i> Lihat Surat Digital
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card-custom bg-white border-0 shadow-sm h-100" style="border-radius: 16px; padding: 25px;">
                <h6 class="fw-bold text-muted text-uppercase mb-4"><i class="bi bi-person-badge me-2"></i> Info Transaksi</h6>
                
                <div class="mb-3">
                    <small class="text-muted d-block mb-1">Nama Pelanggan</small>
                    <h5 class="fw-bold text-dark mb-0"><?php echo $header['NamaPelanggan'] ? $header['NamaPelanggan'] : 'Pelanggan Umum'; ?></h5>
                </div>
                
                <div class="mb-3">
                    <small class="text-muted d-block mb-1">Tanggal Transaksi</small>
                    <div class="fw-semibold text-dark"><?php echo $tglTransaksi; ?></div>
                </div>

                <hr class="my-4 dashed">

                <div>
                    <small class="text-muted d-block mb-1">Total Belanja</small>
                    <h3 class="fw-bold text-primary mb-0">Rp <?php echo number_format($header['TotalTransaksi'], 0, ',', '.'); ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-8 mb-4">
            <div class="card-custom bg-white border-0 shadow-sm h-100" style="border-radius: 16px; padding: 25px;">
                <h6 class="fw-bold text-muted text-uppercase mb-4"><i class="bi bi-box-seam me-2"></i> Daftar Barang Emas</h6>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="py-3 px-3 rounded-start">Deskripsi Barang</th>
                                <th class="py-3 text-center">Berat</th>
                                <th class="py-3 text-end">Harga/gr</th>
                                <th class="py-3 text-end">Ongkos</th>
                                <th class="py-3 px-3 text-end rounded-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // 2. AMBIL DATA DETAIL BARANG
                            $qDetail = mysqli_query($koneksi, "
                                SELECT 
                                    dt.HargaSatuanSaatItu, 
                                    dt.Ongkos, 
                                    b.KodeBarang, 
                                    b.BeratGram, 
                                    pk.NamaProduk, 
                                    pk.Kadar
                                FROM detail_transaksi_barang dt
                                JOIN barang_stok b ON dt.BarangID = b.BarangID
                                JOIN produk_katalog pk ON b.ProdukKatalogID = pk.ProdukKatalogID
                                WHERE dt.TransaksiID = $transaksi_id
                            ");

                            while($row = mysqli_fetch_assoc($qDetail)) {
                                // Hitung subtotal. Pastikan rumus ini sesuai dengan logika tokomu
                                $subtotal = ($row['HargaSatuanSaatItu'] * $row['BeratGram']) + $row['Ongkos'];
                            ?>
                            <tr>
                                <td class="px-3 py-3">
                                    <div class="fw-bold text-dark"><?php echo $row['NamaProduk']; ?> (Kadar <?php echo $row['Kadar']; ?>)</div>
                                    <div class="text-muted small mt-1">Kode: <?php echo $row['KodeBarang']; ?></div>
                                </td>
                                <td class="text-center fw-semibold text-dark"><?php echo number_format($row['BeratGram'], 2); ?> gr</td>
                                <td class="text-end text-muted">Rp <?php echo number_format($row['HargaSatuanSaatItu'], 0, ',', '.'); ?></td>
                                <td class="text-end text-muted">Rp <?php echo number_format($row['Ongkos'], 0, ',', '.'); ?></td>
                                <td class="px-3 text-end fw-bold text-dark">Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<?php include '../../layouts/footer.php'; ?>