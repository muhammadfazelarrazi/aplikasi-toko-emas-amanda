<?php 
session_start();
include '../../config/database.php'; 

// 1. TANGKAP DATA DARI URL (PENCARIAN, TANGGAL, & SORTING)
$keyword = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : '';
$filter_tgl = isset($_GET['tgl']) ? mysqli_real_escape_string($koneksi, $_GET['tgl']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'baru';

include '../../layouts/header.php'; 
include '../../layouts/sidebar.php'; 
?>

<div class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Surat Emas Aktif</h2>
            <p class="text-muted mb-0">Daftar perhiasan di pelanggan yang belum di-buyback.</p>
        </div>
        
        <div>
            <form action="" method="GET" class="d-flex gap-2 align-items-center">
                
                <input type="date" name="tgl" value="<?php echo $filter_tgl; ?>" 
                       class="form-control border-0 shadow-sm text-muted" 
                       style="border-radius: 50px; width: 145px; font-size: 0.85rem; padding: 10px 15px;"
                       title="Filter berdasarkan tanggal">

                <select name="sort" class="form-select border-0 shadow-sm text-muted" 
                        style="border-radius: 50px; width: 140px; font-size: 0.85rem; padding: 10px 15px; cursor: pointer;">
                    <option value="baru" <?php if($sort == 'baru') echo 'selected'; ?>>Paling Baru</option>
                    <option value="lama" <?php if($sort == 'lama') echo 'selected'; ?>>Paling Lama</option>
                    <option value="berat_max" <?php if($sort == 'berat_max') echo 'selected'; ?>>Paling Berat</option>
                    <option value="berat_min" <?php if($sort == 'berat_min') echo 'selected'; ?>>Paling Ringan</option>
                </select>

                <div class="input-group shadow-sm" style="border-radius: 50px; overflow: hidden; width: 250px;">
                    <input type="text" name="cari" class="form-control border-0 bg-white" 
                           placeholder="Cari ID / Pelanggan..." value="<?php echo htmlspecialchars($keyword); ?>"
                           style="font-size: 0.85rem; padding: 10px 15px;">
                    <button class="btn btn-primary px-3" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>

                <?php if($keyword != '' || $filter_tgl != '' || $sort != 'baru'): ?>
                    <a href="surat_aktif.php" class="btn btn-light shadow-sm rounded-circle d-flex align-items-center justify-content-center text-danger" 
                       style="width: 40px; height: 40px;" title="Reset Filter">
                        <i class="bi bi-arrow-counterclockwise fs-5"></i>
                    </a>
                <?php endif; ?>

            </form>
        </div>
    </div>

    <div class="card-custom bg-white border-0 shadow-sm" style="border-radius: 16px; padding: 25px;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small text-uppercase" style="border-bottom: 2px solid #f3f4f6;">
                    <tr>
                        <th class="py-3 px-3" style="border-radius: 10px 0 0 10px;">No. Surat / Nota</th>
                        <th class="py-3">Tanggal Beli</th>
                        <th class="py-3">Pelanggan</th>
                        <th class="py-3">Detail Barang</th>
                        <th class="py-3">Berat</th>
                        <th class="py-3">Status</th>
                        <th class="py-3 px-3 text-end" style="border-radius: 0 10px 10px 0;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // 2. QUERY DASAR
                    $query = "SELECT 
                                t.TransaksiID, 
                                t.TanggalWaktu, 
                                p.NamaPelanggan, 
                                pk.NamaProduk, 
                                pk.Kadar,
                                b.KodeBarang, 
                                b.BeratGram 
                              FROM transaksi t 
                              JOIN pelanggan p ON t.PelangganID = p.PelangganID 
                              JOIN detail_transaksi_barang dt ON t.TransaksiID = dt.TransaksiID 
                              JOIN barang_stok b ON dt.BarangID = b.BarangID 
                              JOIN produk_katalog pk ON b.ProdukKatalogID = pk.ProdukKatalogID
                              WHERE b.Status = 'Terjual' AND t.TipeTransaksi = 'Penjualan'"; 
                    
                    // 3. LOGIKA FILTER PENCARIAN (KEYWORD)
                    if($keyword != '') {
                        $query .= " AND (t.TransaksiID LIKE '%$keyword%' OR p.NamaPelanggan LIKE '%$keyword%' OR b.KodeBarang LIKE '%$keyword%')";
                    }

                    // 4. LOGIKA FILTER TANGGAL
                    if($filter_tgl != '') {
                        // Menggunakan fungsi DATE() untuk mengambil tanggal saja tanpa jam
                        $query .= " AND DATE(t.TanggalWaktu) = '$filter_tgl'";
                    }
                    
                    // 5. LOGIKA SORTING (PENGURUTAN)
                    if($sort == 'lama') {
                        $query .= " ORDER BY t.TanggalWaktu ASC";
                    } elseif($sort == 'berat_max') {
                        $query .= " ORDER BY b.BeratGram DESC";
                    } elseif($sort == 'berat_min') {
                        $query .= " ORDER BY b.BeratGram ASC";
                    } else {
                        // Default: Paling Baru
                        $query .= " ORDER BY t.TanggalWaktu DESC";
                    }
                    
                    $result = mysqli_query($koneksi, $query);

                    // TAMPILKAN HASILNYA
                    if(mysqli_num_rows($result) > 0) {
                        while($row = mysqli_fetch_assoc($result)) {
                            $tglBeli = date('d M Y', strtotime($row['TanggalWaktu']));
                            $noSuratFormat = "TRX-" . sprintf("%04d", $row['TransaksiID']);
                    ?>
                    <tr>
                        <td class="px-3 fw-bold text-primary">#<?php echo $noSuratFormat; ?></td>
                        <td><?php echo $tglBeli; ?></td>
                        <td><span class="fw-semibold"><?php echo $row['NamaPelanggan']; ?></span></td>
                        <td>
                            <div class="d-flex flex-column">
                                <span class="fw-bold text-dark"><?php echo $row['NamaProduk']; ?> Kadar <?php echo $row['Kadar']; ?></span>
                                <small class="text-muted">Kode: <?php echo $row['KodeBarang']; ?></small>
                            </div>
                        </td>
                        <td><?php echo $row['BeratGram']; ?> gr</td>
                        <td>
                            <span class="badge" style="background-color: #dcfce3; color: #166534; padding: 6px 12px; border-radius: 20px;">
                                <i class="bi bi-check-circle me-1"></i> Beredar
                            </span>
                        </td>
                        <td class="px-3 text-end">
                            <div class="d-flex gap-2 justify-content-end">
                                <a href="<?php echo $base_url; ?>modules/transaksi/surat_emas.php?id=<?php echo $row['TransaksiID']; ?>" 
                                   class="btn btn-sm btn-light rounded-pill px-3 fw-semibold border shadow-sm text-dark">
                                    <i class="bi bi-file-earmark-text me-1"></i> Detail
                                </a>
                                
                                <a href="<?php echo $base_url; ?>modules/transaksi/input_buyback.php?id=<?php echo $row['TransaksiID']; ?>" 
                                   class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold shadow-sm">
                                    Proses Buyback <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php 
                        } 
                    } else { 
                    ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <div class="mb-3">
                                <i class="bi bi-search fs-1 text-light"></i>
                            </div>
                            <h6 class="fw-bold text-secondary">Tidak ada data ditemukan</h6>
                            <p class="small mb-0">Coba gunakan tanggal atau kata kunci yang berbeda.</p>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php include '../../layouts/footer.php'; ?>