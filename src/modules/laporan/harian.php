<?php 
session_start();
include '../../config/database.php'; 

// Filter Tanggal (Default: Hari ini)
$tgl_mulai = isset($_GET['mulai']) ? $_GET['mulai'] : date('Y-m-d');
$tgl_selesai = isset($_GET['selesai']) ? $_GET['selesai'] : date('Y-m-d');

// Query Laporan
$query = "SELECT t.*, p.NamaPelanggan, k.NamaKaryawan 
          FROM transaksi t
          LEFT JOIN pelanggan p ON t.PelangganID = p.PelangganID
          JOIN karyawan k ON t.KaryawanID = k.KaryawanID
          WHERE DATE(t.TanggalWaktu) BETWEEN '$tgl_mulai' AND '$tgl_selesai'
          ORDER BY t.TanggalWaktu DESC";
$result = mysqli_query($koneksi, $query);

include '../../layouts/header.php'; 
include '../../layouts/sidebar.php'; 
?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Laporan Transaksi</h2>
            <p class="text-muted mb-0">Rekapitulasi penjualan dan buyback per periode.</p>
        </div>
        <a href="cetak_laporan_pdf.php?mulai=<?php echo $tgl_mulai; ?>&selesai=<?php echo $tgl_selesai; ?>" target="_blank" class="btn btn-outline-secondary rounded-pill px-4">
    <i class="bi bi-file-earmark-pdf-fill text-danger me-2"></i> Cetak PDF
</a>
    </div>

    <div class="card-custom mb-4 no-print"> <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="small fw-bold text-muted">Dari Tanggal</label>
                <input type="date" name="mulai" class="form-control" value="<?php echo $tgl_mulai; ?>">
            </div>
            <div class="col-md-4">
                <label class="small fw-bold text-muted">Sampai Tanggal</label>
                <input type="date" name="selesai" class="form-control" value="<?php echo $tgl_selesai; ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>

    <div class="card-custom">
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="bg-light">
                    <tr>
                        <th>No. Nota</th>
                        <th>Waktu</th>
                        <th>Pelanggan</th>
                        <th>Tipe</th>
                        <th>Kasir</th>
                        <th class="text-end">Total (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $grandTotal = 0;
                    if(mysqli_num_rows($result) > 0):
                        while($row = mysqli_fetch_assoc($result)): 
                            // Hitung Grand Total (Penjualan nambah, Buyback kurang)
                            if($row['TipeTransaksi'] == 'Penjualan') {
                                $grandTotal += $row['TotalTransaksi'];
                                $badge = '<span class="badge bg-success-subtle text-success">Jual</span>';
                            } else {
                                $grandTotal -= $row['TotalTransaksi'];
                                $badge = '<span class="badge bg-danger-subtle text-danger">Buyback</span>';
                            }
                    ?>
                    <tr>
                        <td class="fw-bold text-primary">#<?php echo $row['TransaksiID']; ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($row['TanggalWaktu'])); ?></td>
                        <td><?php echo $row['NamaPelanggan']; ?></td>
                        <td><?php echo $badge; ?></td>
                        <td><?php echo $row['NamaKaryawan']; ?></td>
                        <td class="text-end fw-bold"><?php echo number_format($row['TotalTransaksi'], 0, ',', '.'); ?></td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">Tidak ada transaksi pada periode ini.</td></tr>
                    <?php endif; ?>
                </tbody>
                <tfoot class="bg-light">
                    <tr>
                        <td colspan="5" class="text-end fw-bold">TOTAL OMZET BERSIH</td>
                        <td class="text-end fw-bold fs-5 text-primary">Rp <?php echo number_format($grandTotal, 0, ',', '.'); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<style>
@media print {
    .no-print, .sidebar { display: none !important; }
    .main-content { margin-left: 0 !important; padding: 0 !important; }
    .card-custom { box-shadow: none !important; border: 1px solid #ddd !important; }
}
</style>

<?php include '../../layouts/footer.php'; ?>