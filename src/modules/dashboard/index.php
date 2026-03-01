<?php 
session_start();

// 1. Panggil Koneksi & Helper
include '../../config/database.php'; 

// 2. Panggil Layout Utama
include '../../layouts/header.php'; 
include '../../layouts/sidebar.php'; 

// --- BAGIAN LOGIKA PHP ---
$tgl_hari_ini = date('Y-m-d');

// A. Data Omzet Hari Ini
$queryOmzet = mysqli_query($koneksi, "SELECT SUM(TotalTransaksi) as total FROM transaksi WHERE TanggalWaktu LIKE '$tgl_hari_ini%' AND TipeTransaksi='Penjualan'");
$dataOmzet = mysqli_fetch_assoc($queryOmzet);
$omzet = $dataOmzet['total'] ?? 0;

// B. Data Buyback Hari Ini
$queryBuyback = mysqli_query($koneksi, "SELECT SUM(TotalTransaksi) as total FROM transaksi WHERE TanggalWaktu LIKE '$tgl_hari_ini%' AND TipeTransaksi='Buyback'");
$dataBuyback = mysqli_fetch_assoc($queryBuyback);
$buyback = $dataBuyback['total'] ?? 0;

// C. Stok Tersedia
$queryStok = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM barang_stok WHERE Status='Tersedia'");
$dataStok = mysqli_fetch_assoc($queryStok);
$totalStok = $dataStok['total'] ?? 0;

// D. JUMLAH NOTA HARI INI
$qCount = mysqli_query($koneksi, "SELECT COUNT(*) as jlh FROM transaksi WHERE TanggalWaktu LIKE '$tgl_hari_ini%'");
$jmlNota = mysqli_fetch_assoc($qCount)['jlh'];

// E. LOGIKA HARGA ACUAN
$listKadarObj = mysqli_query($koneksi, "SELECT DISTINCT Kadar FROM produk_katalog UNION SELECT DISTINCT Kadar FROM riwayat_harga");
$hargaAcuan = [];
$outdatedKadarList = []; 

while($k = mysqli_fetch_assoc($listKadarObj)){
    $kdr = $k['Kadar'];
    $qH = mysqli_query($koneksi, "SELECT * FROM riwayat_harga WHERE Kadar='$kdr' ORDER BY Tanggal DESC LIMIT 1");
    if($dH = mysqli_fetch_assoc($qH)){
        $hargaAcuan[] = $dH;
        
        // Cek apakah tanggalnya BUKAN hari ini
        if($dH['Tanggal'] != $tgl_hari_ini) {
            $outdatedKadarList[] = $kdr;
        }
    }
}

// LOGIKA POP-UP PENGINGAT (Hanya 1x Per Login)
$showReminder = false;
if(count($outdatedKadarList) > 0 && !isset($_SESSION['reminder_harga_shown']) && (isset($_SESSION['role']) && $_SESSION['role'] == 'Owner')) {
    $showReminder = true;
    $_SESSION['reminder_harga_shown'] = true; 
}

// F. LOGIKA GRAFIK
$listKadar = [];
$qKadar = mysqli_query($koneksi, "SELECT DISTINCT Kadar FROM riwayat_harga ORDER BY Kadar ASC");
while($k = mysqli_fetch_assoc($qKadar)){
    $listKadar[] = $k['Kadar'];
}

$chartData = [];
foreach($listKadar as $kdr) {
    $qHistory = mysqli_query($koneksi, "
        SELECT Tanggal, HargaJualPerGram 
        FROM riwayat_harga 
        WHERE Kadar = '$kdr' AND Tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ORDER BY Tanggal ASC
    ");
    $dates = []; $prices = [];
    while($h = mysqli_fetch_assoc($qHistory)) {
        $dates[] = date('d M', strtotime($h['Tanggal']));
        $prices[] = $h['HargaJualPerGram'];
    }
    $chartData[$kdr] = ['labels' => $dates, 'data' => $prices];
}
?>

<style>
    /* Scrollbar Tipis */
    .widget-scroll::-webkit-scrollbar { width: 5px; }
    .widget-scroll::-webkit-scrollbar-track { background: transparent; }
    .widget-scroll::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }
    .widget-scroll::-webkit-scrollbar-thumb:hover { background: #9ca3af; }

    /* Animasi Pop-up Smooth yang Benar */
    @keyframes slideInSmooth {
        0% { transform: translateY(100%); opacity: 0; }
        100% { transform: translateY(0); opacity: 1; }
    }
    /* Menerapkan animasi HANYA saat Bootstrap memunculkan (.show) toast-nya */
    .toast.show {
        animation: slideInSmooth 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    /* Perbaikan Tombol Pop-up agar sejajar */
    .btn-popup {
        font-size: 0.85rem !important; 
        white-space: nowrap !important; /* Mencegah teks turun ke bawah */
        padding-left: 15px !important;
        padding-right: 15px !important;
    }
</style>

<div class="main-content">
    
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <h2 class="fw-bold mb-1">Dashboard</h2>
            <p class="text-muted mb-0">Ringkasan aktivitas Toko Emas Amanda hari ini.</p>
        </div>
        
        <div class="d-flex gap-2">
            <a href="../transaksi/input_jual.php" class="btn btn-primary rounded-pill px-4 shadow-sm">
                <i class="bi bi-plus-lg me-2"></i> Transaksi Baru
            </a>
            <a href="../stok/input_masuk.php" class="btn btn-outline-secondary rounded-pill px-4">
                <i class="bi bi-box-arrow-in-down me-2"></i> Stok Masuk
            </a>
        </div>
    </div>

    <div class="alert alert-primary border-0 shadow-sm d-flex align-items-center mb-4 p-3" role="alert" style="background-color: #e7f1ff; color: #0d6efd;">
        <div class="bg-white p-2 rounded-circle me-3 shadow-sm d-none d-md-block">
            <i class="bi bi-tag-fill fs-5"></i>
        </div>
        <div class="d-flex flex-wrap align-items-center w-100 justify-content-between">
            <div class="flex-grow-1">
                <span class="fw-bold d-block text-uppercase small ls-1 text-muted mb-2" style="font-size: 0.7rem;">Harga Acuan Terakhir (per Gram)</span>
                
                <div class="d-flex gap-4 flex-wrap">
                    <?php 
                    if(count($hargaAcuan) > 0) {
                        foreach($hargaAcuan as $h) {
                            $isToday = ($h['Tanggal'] == $tgl_hari_ini);
                            $warningStyle = $isToday ? 'text-dark' : 'text-danger fw-bold';
                            
                            echo '<div class="d-flex flex-column">';
                            echo '<span class="h5 fw-bold mb-0">'.$h['Kadar'].': <span class="'.$warningStyle.'">Rp '.number_format($h['HargaJualPerGram'], 0, ',', '.').'</span></span>';
                            if(!$isToday) {
                                echo '<small class="text-danger" style="font-size: 0.65rem;"><i class="bi bi-exclamation-triangle-fill"></i> Terakhir: '.date('d M', strtotime($h['Tanggal'])).'</small>';
                            }
                            echo '</div>';
                        }
                    } else {
                        echo '<span class="small text-danger fw-bold"><i class="bi bi-exclamation-circle"></i> Belum ada data harga sama sekali!</span>';
                    }
                    ?>
                </div>
            </div>

            <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'Owner'): ?>
                <a href="../master/harga.php" class="btn btn-sm btn-primary fw-bold rounded-pill px-3 shadow-sm mt-2 mt-md-0">Update Harga</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card-custom">
                <div class="d-flex justify-content-between mb-2">
                    <div class="icon-box bg-light-green"><i class="bi bi-cash-stack"></i></div>
                </div>
                <p class="text-muted mb-1" style="font-size: 0.85rem;">Omzet Hari Ini</p>
                <h4 class="fw-bold mb-0">Rp <?php echo number_format($omzet, 0, ',', '.'); ?></h4>
                <small class="text-success fw-medium"><i class="bi bi-arrow-up-short"></i> Masuk</small>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card-custom">
                <div class="d-flex justify-content-between mb-2">
                    <div class="icon-box bg-light-red"><i class="bi bi-arrow-left-right"></i></div>
                </div>
                <p class="text-muted mb-1" style="font-size: 0.85rem;">Buyback Keluar</p>
                <h4 class="fw-bold mb-0">Rp <?php echo number_format($buyback, 0, ',', '.'); ?></h4>
                <small class="text-danger fw-medium"><i class="bi bi-arrow-down-short"></i> Keluar</small>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card-custom">
                <div class="d-flex justify-content-between mb-2">
                    <div class="icon-box bg-light-blue"><i class="bi bi-gem"></i></div>
                </div>
                <p class="text-muted mb-1" style="font-size: 0.85rem;">Stok Tersedia</p>
                <h4 class="fw-bold mb-0"><?php echo $totalStok; ?> Item</h4>
                <small class="text-primary fw-medium">Siap Jual</small>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card-custom">
                <div class="d-flex justify-content-between mb-2">
                    <div class="icon-box bg-light-orange"><i class="bi bi-receipt"></i></div>
                </div>
                <p class="text-muted mb-1" style="font-size: 0.85rem;">Transaksi</p>
                <h4 class="fw-bold mb-0"><?php echo $jmlNota; ?> Nota</h4>
                <small class="text-muted">Hari Ini</small>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div class="card-custom h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="fw-bold mb-1">Grafik Harga Emas</h5>
                        <p class="text-muted small mb-0">Pantau pergerakan harga jual per gram.</p>
                    </div>
                    <select id="filterKadar" class="form-select form-select-sm w-auto border-0 bg-light fw-bold text-primary">
                        <?php foreach($listKadar as $kdr): ?>
                            <option value="<?php echo $kdr; ?>">Emas <?php echo $kdr; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="height: 300px; width: 100%;">
                    <canvas id="goldPriceChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card-custom h-100 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">Ringkasan Stok</h5>
                    <a href="../stok/data_barang.php" class="small text-decoration-none">Lihat Semua</a>
                </div>
                
                <div class="flex-grow-1 pe-2 widget-scroll" style="overflow-y: auto; max-height: 270px;">
                    <?php 
                    $queryAllStock = mysqli_query($koneksi, "
                        SELECT pk.NamaProduk, pk.Kadar, 
                               SUM(CASE WHEN bs.Status = 'Tersedia' THEN 1 ELSE 0 END) as jumlah 
                        FROM produk_katalog pk 
                        LEFT JOIN barang_stok bs ON pk.ProdukKatalogID = bs.ProdukKatalogID 
                        GROUP BY pk.ProdukKatalogID 
                        ORDER BY pk.NamaProduk ASC
                    ");
                    
                    if(mysqli_num_rows($queryAllStock) > 0) {
                        while($row = mysqli_fetch_assoc($queryAllStock)) {
                            $jumlah = $row['jumlah'];
                            $badgeStyle = ($jumlah == 0) ? 'bg-light text-muted border border-secondary-subtle' : 'bg-primary-subtle text-primary fw-bold';
                    ?>
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom border-light">
                            <div class="d-flex flex-column">
                                <span class="fw-semibold text-dark text-truncate" style="max-width: 170px;" title="<?php echo $row['NamaProduk']; ?>">
                                    <?php echo $row['NamaProduk']; ?>
                                </span>
                                <small class="text-muted" style="font-size: 0.75rem;">Kadar: <?php echo $row['Kadar']; ?></small>
                            </div>
                            <span class="badge <?php echo $badgeStyle; ?> rounded-pill px-3 py-2">
                                <?php echo $jumlah; ?> Unit
                            </span>
                        </div>
                    <?php 
                        }
                    } else {
                        echo "<div class='text-center py-4'>
                                <i class='bi bi-box-seam text-muted fs-1 mb-2 d-block opacity-50'></i>
                                <p class='text-muted small mb-0'>Katalog produk masih kosong.</p>
                              </div>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-12">
        <div class="card-custom">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0">Transaksi Terakhir</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="text-muted small bg-light">
                        <tr>
                            <th class="ps-3 border-0 rounded-start">ID</th>
                            <th class="border-0">Tipe</th>
                            <th class="border-0">Tanggal</th>
                            <th class="border-0">Total</th>
                            <th class="pe-3 border-0 rounded-end text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $queryLastTrx = mysqli_query($koneksi, "SELECT * FROM transaksi ORDER BY TransaksiID DESC LIMIT 5");
                        while($trx = mysqli_fetch_assoc($queryLastTrx)) {
                            $badgeColor = ($trx['TipeTransaksi'] == 'Penjualan') ? 'bg-light-green text-success' : 'bg-light-red text-danger';
                        ?>
                        <tr>
                            <td class="ps-3 fw-bold">#TRX-<?php echo $trx['TransaksiID']; ?></td>
                            <td><span class="badge <?php echo $badgeColor; ?> px-3 py-2 rounded-pill"><?php echo $trx['TipeTransaksi']; ?></span></td>
                            <td class="text-muted small"><?php echo date('d M H:i', strtotime($trx['TanggalWaktu'])); ?></td>
                            <td class="fw-bold">Rp <?php echo number_format($trx['TotalTransaksi'], 0, ',', '.'); ?></td>
                            <td class="pe-3 text-end">
                                <a href="../transaksi/cetak_nota.php?id=<?php echo $trx['TransaksiID']; ?>" target="_blank" class="btn btn-sm btn-light rounded-circle shadow-sm" title="Cetak Nota"><i class="bi bi-printer"></i></a>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if($showReminder): ?>
<div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index: 1055;">
    <div id="priceReminderToast" class="toast shadow-lg border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="false" style="border-radius: 16px;">
        <div class="toast-header bg-warning-subtle border-0 py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
            <i class="bi bi-exclamation-circle-fill text-warning me-2 fs-5"></i>
            <strong class="me-auto fs-6 text-warning-emphasis">Pengingat Update Harga!</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body p-4 bg-white" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
            <p class="mb-3 text-muted" style="line-height: 1.6;">
                Sistem mendeteksi ada beberapa harga emas yang <b>belum diperbarui hari ini</b>. Tolong cek kembali kadar berikut:
                <br>
                <span class="fw-bold text-danger fs-5 d-block mt-2"><?php echo implode(', ', $outdatedKadarList); ?></span>
            </p>
            <div class="d-flex gap-2 mt-4">
                <a href="../master/harga.php" class="btn btn-primary rounded-pill fw-semibold shadow-sm w-100 btn-popup text-center">Update Sekarang</a>
                <button type="button" class="btn btn-light rounded-pill fw-semibold border shadow-sm w-100 btn-popup" data-bs-dismiss="toast">Abaikan</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var toastEl = document.getElementById('priceReminderToast');
        if(toastEl) {
            var toast = new bootstrap.Toast(toastEl);
            // Delay 0.3 detik agar animasinya terlihat memuaskan setelah loading
            setTimeout(function() {
                toast.show();
            }, 300);
        }
    });
</script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('goldPriceChart').getContext('2d');
    const allData = <?php echo json_encode($chartData); ?>;
    const select = document.getElementById('filterKadar');
    
    let myChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Harga Jual',
                data: [],
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { grid: { borderDash: [5, 5] } },
                x: { grid: { display: false } }
            }
        }
    });

    function updateChart(kadar) {
        const newData = allData[kadar];
        if (newData) {
            myChart.data.labels = newData.labels;
            myChart.data.datasets[0].data = newData.data;
            if (kadar === '24K') {
                myChart.data.datasets[0].borderColor = '#ffc107';
                myChart.data.datasets[0].backgroundColor = 'rgba(255, 193, 7, 0.1)';
            } else {
                myChart.data.datasets[0].borderColor = '#0d6efd';
                myChart.data.datasets[0].backgroundColor = 'rgba(13, 110, 253, 0.1)';
            }
            myChart.update();
        }
    }

    if(select.value) updateChart(select.value);
    select.addEventListener('change', function() {
        updateChart(this.value);
    });
</script>

<?php include '../../layouts/footer.php'; ?>