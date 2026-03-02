<?php 
session_start();
include '../../config/database.php'; 

// Cek Login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../modules/auth/login.php");
    exit;
}

// --- LOGIKA PENCARIAN ---
$keyword = "";
if (isset($_GET['keyword'])) {
    $keyword = mysqli_real_escape_string($koneksi, $_GET['keyword']);
    $where = "WHERE t.TransaksiID LIKE '%$keyword%' OR p.NamaPelanggan LIKE '%$keyword%' OR bs.KodeBarang LIKE '%$keyword%' OR DATE(t.TanggalWaktu) LIKE '%$keyword%'";
} else {
    $where = "";
}

// Query Data (Limit 20 biar ringan)
$query = "SELECT DISTINCT t.*, p.NamaPelanggan, k.NamaKaryawan 
          FROM transaksi t
          LEFT JOIN pelanggan p ON t.PelangganID = p.PelangganID
          JOIN karyawan k ON t.KaryawanID = k.KaryawanID
          LEFT JOIN detail_transaksi_barang dt ON t.TransaksiID = dt.TransaksiID
          LEFT JOIN barang_stok bs ON dt.BarangID = bs.BarangID
          $where
          ORDER BY t.TanggalWaktu DESC LIMIT 20";

$result = mysqli_query($koneksi, $query);

include '../../layouts/header.php'; 
include '../../layouts/sidebar.php'; 
?>

<div class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Riwayat Transaksi</h2>
            <p class="text-muted mb-0">Daftar transaksi penjualan dan buyback.</p>
        </div>
        <a href="input_jual.php" class="btn btn-primary rounded-pill px-4 shadow-sm">
            <i class="bi bi-plus-lg me-2"></i> Transaksi Baru
        </a>
    </div>

    <div class="card-custom mb-4">
        <form method="GET" action="">
            <label class="form-label small fw-bold text-muted">Cari Data</label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 ps-3"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="keyword" class="form-control border-start-0 py-2" placeholder="Masukan No Nota / Nama Pelanggan / Kode Barang / Tanggal..." value="<?php echo $keyword; ?>">
                <button type="submit" class="btn btn-primary px-4">Cari</button>
                <?php if($keyword): ?>
                    <a href="riwayat.php" class="btn btn-outline-secondary">Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="card-custom">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="bg-light text-muted small">
                    <tr>
                        <th class="ps-4 rounded-start">No. Nota</th>
                        <th>Waktu</th>
                        <th>Pelanggan</th>
                        <th>Tipe</th>
                        <th>Kasir</th>
                        <th>Total</th>
                        <th class="text-end rounded-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-primary">
                                    #TRX-<?php echo $row['TransaksiID']; ?>
                                </td>
                                <td>
                                    <span class="d-block text-dark small fw-bold"><?php echo date('d/m/Y', strtotime($row['TanggalWaktu'])); ?></span>
                                    <small class="text-muted"><?php echo date('H:i', strtotime($row['TanggalWaktu'])); ?> WIB</small>
                                </td>
                                <td>
                                    <?php echo $row['NamaPelanggan'] ? $row['NamaPelanggan'] : '<span class="text-muted fst-italic">Umum</span>'; ?>
                                </td>
                                <td>
                                    <?php if($row['TipeTransaksi'] == 'Penjualan'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success px-2">Penjualan</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger px-2">Buyback</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted"><?php echo $row['NamaKaryawan']; ?></td>
                                <td class="fw-bold">Rp <?php echo number_format($row['TotalTransaksi'], 0, ',', '.'); ?></td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <?php if($row['TipeTransaksi'] == 'Penjualan'): ?>
                                        <a href="surat_emas.php?id=<?php echo $row['TransaksiID']; ?>" target="_blank" class="btn btn-sm btn-success rounded-circle" title="Lihat Surat Emas Digital">
                                            <i class="bi bi-file-earmark-richtext"></i>
                                        </a>
                                        <?php endif; ?>

                                        <a href="cetak_nota.php?id=<?php echo $row['TransaksiID']; ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-circle" title="Cetak Struk">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-search fs-1 d-block mb-2"></i>
                                Data transaksi tidak ditemukan.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php include '../../layouts/footer.php'; ?>