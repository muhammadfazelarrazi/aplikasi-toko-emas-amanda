<?php 
session_start();
include '../../config/database.php'; 

// Cek Role Owner
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Owner') {
    header("Location: ../dashboard/index.php");
    exit;
}

$tgl_today = date('Y-m-d');

// --- LOGIKA 1: TAMBAH KADAR BARU (MANUAL) ---
if (isset($_POST['tambah_kadar_baru'])) {
    $kadar_baru = mysqli_real_escape_string($koneksi, $_POST['kadar_baru']);
    $jual_baru  = $_POST['jual_baru'];
    $beli_baru  = $_POST['beli_baru'];

    // Cek apakah hari ini sudah ada untuk kadar tsb?
    $cek = mysqli_query($koneksi, "SELECT * FROM riwayat_harga WHERE Tanggal='$tgl_today' AND Kadar='$kadar_baru'");
    
    if(mysqli_num_rows($cek) > 0) {
        // Update
        $q = "UPDATE riwayat_harga SET HargaJualPerGram='$jual_baru', HargaBeliPerGram='$beli_baru' 
              WHERE Tanggal='$tgl_today' AND Kadar='$kadar_baru'";
    } else {
        // Insert
        $q = "INSERT INTO riwayat_harga (Tanggal, Kadar, HargaJualPerGram, HargaBeliPerGram) 
              VALUES ('$tgl_today', '$kadar_baru', '$jual_baru', '$beli_baru')";
    }
    
    mysqli_query($koneksi, $q);
    echo "<script>alert('Kadar baru berhasil ditambahkan!'); window.location='harga.php';</script>";
}

// --- LOGIKA 2: UPDATE HARGA MASSAL ---
if (isset($_POST['update_harga'])) {
    $kadarList = $_POST['kadar']; 
    $jualList  = $_POST['harga_jual'];
    $beliList  = $_POST['harga_beli'];

    // Loop semua inputan
    for ($i = 0; $i < count($kadarList); $i++) {
        $k = $kadarList[$i];
        $h_jual = $jualList[$i];
        $h_beli = $beliList[$i];
        
        // Hapus data lama hari ini untuk kadar ini (biar bersih jika diupdate berkali-kali di hari yang sama)
        mysqli_query($koneksi, "DELETE FROM riwayat_harga WHERE Tanggal='$tgl_today' AND Kadar='$k'");

        // Insert baru (Hanya jika diisi harga lebih dari 0)
        if($h_jual > 0) {
            $query = "INSERT INTO riwayat_harga (Tanggal, Kadar, HargaJualPerGram, HargaBeliPerGram) 
                      VALUES ('$tgl_today', '$k', '$h_jual', '$h_beli')";
            mysqli_query($koneksi, $query);
        }
    }
    echo "<script>alert('Semua harga berhasil diupdate dan disimpan untuk hari ini!'); window.location='harga.php';</script>";
}

include '../../layouts/header.php'; 
include '../../layouts/sidebar.php'; 
?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Pengaturan Harga Emas</h2>
            <p class="text-muted mb-0">Update harga harian atau tambah jenis kadar baru.</p>
        </div>
        
        <button type="button" class="btn btn-outline-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalKadar">
            <i class="bi bi-plus-circle me-2"></i> Tambah Kadar Baru
        </button>
    </div>

    <div class="row">
        <div class="col-md-7">
            <div class="card-custom mb-4">
                <div class="d-flex align-items-center mb-4 border-bottom pb-2">
                    <span class="badge bg-primary fs-6 px-3 py-2 rounded-pill me-auto shadow-sm">
                        <i class="bi bi-calendar-event me-2"></i> Hari Ini: <?php echo date('d F Y'); ?>
                    </span>
                    <small class="text-muted"><i class="bi bi-info-circle me-1"></i> Data di form adalah harga terakhir</small>
                </div>
                
                <form method="POST">
                    
                    <?php 
                    // QUERY PINTAR: Gabungkan Kadar dari Katalog DAN Riwayat Harga
                    $qKadar = mysqli_query($koneksi, "
                        SELECT DISTINCT Kadar FROM (
                            SELECT Kadar FROM produk_katalog
                            UNION
                            SELECT Kadar FROM riwayat_harga
                        ) AS AllKadar 
                        ORDER BY Kadar DESC
                    ");
                    
                    if(mysqli_num_rows($qKadar) > 0) {
                        while($row = mysqli_fetch_assoc($qKadar)) {
                            $kadar = $row['Kadar'];
                            
                            // Visual: Warna Warni Badge
                            $badgeColor = ($kadar == '24K') ? 'text-warning' : (($kadar == '17K') ? 'text-primary' : 'text-secondary');
                            
                            // --- PERBAIKAN LOGIKA HARGA TERAKHIR ---
                            // Ambil harga yang PALING TERBARU (limit 1), tidak peduli tanggal berapa
                            $qCekHarga = mysqli_query($koneksi, "SELECT * FROM riwayat_harga WHERE Kadar='$kadar' ORDER BY Tanggal DESC LIMIT 1");
                            $dataHarga = mysqli_fetch_assoc($qCekHarga);
                            
                            $valJual = $dataHarga['HargaJualPerGram'] ?? '';
                            $valBeli = $dataHarga['HargaBeliPerGram'] ?? '';
                            $tglTerakhir = $dataHarga['Tanggal'] ?? '';

                            // Logika Lencana/Badge Peringatan
                            $infoBadge = "";
                            if ($tglTerakhir == '') {
                                $infoBadge = "<span class='badge bg-danger-subtle text-danger mt-1' style='font-size:0.65rem;'><i class='bi bi-x-circle'></i> Belum ada data</span>";
                            } elseif ($tglTerakhir == $tgl_today) {
                                $infoBadge = "<span class='badge bg-success-subtle text-success mt-1' style='font-size:0.65rem;'><i class='bi bi-check-circle'></i> Updated Hari Ini</span>";
                            } else {
                                // Jika harga berasal dari hari sebelumnya
                                $infoBadge = "<span class='badge bg-warning-subtle text-warning-emphasis mt-1' style='font-size:0.65rem;' title='Harga terakhir disalin dari tanggal ini'><i class='bi bi-clock-history'></i> Terakhir: " . date('d M', strtotime($tglTerakhir)) . "</span>";
                            }
                    ?>
                    
                    <div class="bg-light p-3 rounded-3 mb-3 border">
                        <div class="row align-items-center">
                            <div class="col-md-2 text-center">
                                <h2 class="fw-bold <?php echo $badgeColor; ?> mb-0"><?php echo $kadar; ?></h2>
                                <input type="hidden" name="kadar[]" value="<?php echo $kadar; ?>">
                                <div><?php echo $infoBadge; ?></div>
                            </div>
                            <div class="col-md-5">
                                <label class="small fw-bold text-muted mb-1">Jual</label>
                                <div class="input-group input-group-sm shadow-sm">
                                    <span class="input-group-text bg-white border-end-0">Rp</span>
                                    <input type="number" name="harga_jual[]" class="form-control border-start-0" placeholder="0" value="<?php echo $valJual; ?>">
                                </div>
                            </div>
                            <div class="col-md-5">
                                <label class="small fw-bold text-muted mb-1">Buyback</label>
                                <div class="input-group input-group-sm shadow-sm">
                                    <span class="input-group-text bg-white border-end-0 text-danger">Rp</span>
                                    <input type="number" name="harga_beli[]" class="form-control border-start-0" placeholder="0" value="<?php echo $valBeli; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php 
                        } 
                    } else {
                        echo "<div class='alert alert-info text-center'>Belum ada data kadar. Klik tombol <b>Tambah Kadar Baru</b> di atas.</div>";
                    }
                    ?>

                    <button type="submit" name="update_harga" class="btn btn-primary w-100 py-2 rounded-pill fw-bold shadow-sm mt-3">
                        <i class="bi bi-save me-2"></i> Simpan Harga Untuk Hari Ini
                    </button>
                </form>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card-custom h-100">
                <div class="d-flex align-items-center mb-3">
                    <i class="bi bi-clock-history me-2 fs-5 text-muted"></i>
                    <h6 class="fw-bold mb-0">Arsip Riwayat Terakhir</h6>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover small align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="py-2">Tanggal</th>
                                <th class="py-2">Kadar</th>
                                <th class="text-end py-2">Harga Jual</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $qHist = mysqli_query($koneksi, "SELECT * FROM riwayat_harga ORDER BY Tanggal DESC, Kadar DESC LIMIT 10");
                            while($h = mysqli_fetch_assoc($qHist)):
                            ?>
                            <tr>
                                <td class="text-muted"><?php echo date('d M Y', strtotime($h['Tanggal'])); ?></td>
                                <td class="fw-bold"><?php echo $h['Kadar']; ?></td>
                                <td class="text-end fw-bold text-primary">
                                    Rp <?php echo number_format($h['HargaJualPerGram'], 0, ',', '.'); ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalKadar" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Kadar Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Kadar</label>
                        <input type="text" name="kadar_baru" class="form-control" placeholder="Cth: 22K" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Harga Jual Hari Ini</label>
                        <input type="number" name="jual_baru" class="form-control" placeholder="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Harga Buyback</label>
                        <input type="number" name="beli_baru" class="form-control" placeholder="0" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" name="tambah_kadar_baru" class="btn btn-primary w-100 rounded-pill">Tambah</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../layouts/footer.php'; ?>