<?php 
session_start();
include '../../config/database.php'; 

// Cek Login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../modules/auth/login.php");
    exit;
}

// Filter Periode (Default: Bulan Ini)
$tgl_awal = isset($_GET['awal']) ? $_GET['awal'] : date('Y-m-01');
$tgl_akhir = isset($_GET['akhir']) ? $_GET['akhir'] : date('Y-m-d');

// --- QUERY 1: BARANG MASUK (Stok Baru) ---
// Mengambil data dari tabel barang_stok berdasarkan TanggalMasuk
$queryMasuk = "SELECT bs.*, pk.NamaProduk, pk.Kadar, s.NamaSupplier 
               FROM barang_stok bs
               JOIN produk_katalog pk ON bs.ProdukKatalogID = pk.ProdukKatalogID
               LEFT JOIN supplier s ON bs.SupplierID = s.SupplierID
               WHERE bs.TanggalMasuk BETWEEN '$tgl_awal' AND '$tgl_akhir'
               ORDER BY bs.TanggalMasuk DESC";
$resMasuk = mysqli_query($koneksi, $queryMasuk);

// --- QUERY 2: BARANG KELUAR (Penjualan) ---
// Mengambil data dari tabel transaksi & detail berdasarkan Tanggal Transaksi
$queryKeluar = "SELECT t.TanggalWaktu, t.TransaksiID, bs.KodeBarang, bs.BeratGram, pk.NamaProduk, pk.Kadar
                FROM detail_transaksi_barang dt
                JOIN transaksi t ON dt.TransaksiID = t.TransaksiID
                JOIN barang_stok bs ON dt.BarangID = bs.BarangID
                JOIN produk_katalog pk ON bs.ProdukKatalogID = pk.ProdukKatalogID
                WHERE t.TipeTransaksi = 'Penjualan' 
                AND DATE(t.TanggalWaktu) BETWEEN '$tgl_awal' AND '$tgl_akhir'
                ORDER BY t.TanggalWaktu DESC";
$resKeluar = mysqli_query($koneksi, $queryKeluar);

include '../../layouts/header.php'; 
include '../../layouts/sidebar.php'; 
?>

<div class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Mutasi Stok</h2>
            <p class="text-muted mb-0">Laporan pergerakan keluar-masuk barang per periode.</p>
        </div>
        <button onclick="window.print()" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-printer me-2"></i> Cetak
        </button>
    </div>

    <div class="card-custom mb-4 no-print">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="small fw-bold text-muted">Dari Tanggal</label>
                <input type="date" name="awal" class="form-control" value="<?php echo $tgl_awal; ?>">
            </div>
            <div class="col-md-4">
                <label class="small fw-bold text-muted">Sampai Tanggal</label>
                <input type="date" name="akhir" class="form-control" value="<?php echo $tgl_akhir; ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Tampilkan</button>
            </div>
        </form>
    </div>

    <div class="row">
        
        <div class="col-md-6">
            <div class="card-custom h-100">
                <div class="d-flex align-items-center mb-3 text-success">
                    <i class="bi bi-arrow-down-circle-fill fs-4 me-2"></i>
                    <h5 class="fw-bold mb-0">Riwayat Barang Masuk</h5>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-sm table-hover small">
                        <thead class="bg-light">
                            <tr>
                                <th>Tgl Masuk</th>
                                <th>Barang</th>
                                <th>Asal</th>
                                <th class="text-end">Berat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($resMasuk) > 0): ?>
                                <?php while($in = mysqli_fetch_assoc($resMasuk)): ?>
                                <tr>
                                    <td><?php echo date('d/m/y', strtotime($in['TanggalMasuk'])); ?></td>
                                    <td>
                                        <span class="d-block fw-bold"><?php echo $in['KodeBarang']; ?></span>
                                        <span class="text-muted"><?php echo $in['NamaProduk']; ?></span>
                                    </td>
                                    <td>
                                        <?php if($in['AsalBarang'] == 'Supplier'): ?>
                                            <span class="badge bg-info-subtle text-info border border-info px-1">Supplier</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning px-1">Buyback</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end fw-bold"><?php echo $in['BeratGram']; ?>g</td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center py-3 text-muted">Tidak ada barang masuk.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card-custom h-100">
                <div class="d-flex align-items-center mb-3 text-danger">
                    <i class="bi bi-arrow-up-circle-fill fs-4 me-2"></i>
                    <h5 class="fw-bold mb-0">Riwayat Barang Keluar (Terjual)</h5>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-sm table-hover small">
                        <thead class="bg-light">
                            <tr>
                                <th>Tgl Jual</th>
                                <th>Barang</th>
                                <th>Nota</th>
                                <th class="text-end">Berat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($resKeluar) > 0): ?>
                                <?php while($out = mysqli_fetch_assoc($resKeluar)): ?>
                                <tr>
                                    <td><?php echo date('d/m/y', strtotime($out['TanggalWaktu'])); ?></td>
                                    <td>
                                        <span class="d-block fw-bold"><?php echo $out['KodeBarang']; ?></span>
                                        <span class="text-muted"><?php echo $out['NamaProduk']; ?></span>
                                    </td>
                                    <td>
                                        <a href="../transaksi/cetak_nota.php?id=<?php echo $out['TransaksiID']; ?>" target="_blank" class="text-decoration-none">
                                            #<?php echo $out['TransaksiID']; ?>
                                        </a>
                                    </td>
                                    <td class="text-end fw-bold"><?php echo $out['BeratGram']; ?>g</td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center py-3 text-muted">Tidak ada penjualan.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
@media print {
    .no-print, .sidebar { display: none !important; }
    .main-content { margin-left: 0 !important; }
    .card-custom { border: 1px solid #ccc; box-shadow: none; }
}
</style>

<?php include '../../layouts/footer.php'; ?>