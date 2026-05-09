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
        
        // Ambil format Y-m-d saja dari DB untuk mencocokkan dengan $tgl_hari_ini
        $tgl_db_only = date('Y-m-d', strtotime($dH['Tanggal']));
        
        // Cek apakah tanggalnya BUKAN hari ini
        if($tgl_db_only != $tgl_hari_ini) {
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

// G. LOGIKA FILTER TRANSAKSI TERAKHIR (FITUR BARU)
$filter_trx = isset($_GET['filter_trx']) ? $_GET['filter_trx'] : 'hari_ini';
$where_trx = "WHERE DATE(TanggalWaktu) = '$tgl_hari_ini'"; // Default: Hari Ini

if ($filter_trx == 'minggu_ini') {
    $where_trx = "WHERE DATE(TanggalWaktu) >= DATE_SUB('$tgl_hari_ini', INTERVAL 7 DAY)";
} elseif ($filter_trx == 'bulan_ini') {
    $where_trx = "WHERE DATE(TanggalWaktu) >= DATE_SUB('$tgl_hari_ini', INTERVAL 30 DAY)";
} elseif ($filter_trx == 'semua') {
    $where_trx = ""; // Tanpa batas waktu
}
?>

<style>
    /* =========================================
       GLOBAL UI: FINPAY PREMIUM DESIGN SYSTEM
       ========================================= */
    :root {
        /* Apple SF Pro Font */
        --font-sf: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", "Helvetica Neue", Helvetica, Arial, sans-serif;
        
        /* FinPay Color Palette */
        --fin-bg: #f8fafc;          
        --fin-surface: #ffffff;
        --fin-primary: #3b82f6;     
        --fin-primary-dark: #2563eb;
        --fin-primary-soft: #eff6ff;
        
        --fin-text-main: #0f172a;   
        --fin-text-muted: #64748b;  
        --fin-border: #f1f5f9;      
        
        /* Status Colors */
        --fin-success: #10b981;
        --fin-success-bg: #ecfdf5;
        --fin-danger: #ef4444;
        --fin-danger-bg: #fef2f2;
        --fin-warning: #f59e0b;
        --fin-warning-bg: #fffbeb;
        
        /* Layout Variables */
        --radius-xl: 24px;
        --radius-lg: 16px;
        --radius-md: 12px;
        --shadow-soft: 0 4px 20px rgba(0, 0, 0, 0.02);
        --shadow-hover: 0 10px 25px rgba(37, 99, 235, 0.08);
    }

    body {
        font-family: var(--font-sf) !important;
        background-color: var(--fin-bg) !important;
        color: var(--fin-text-main);
        -webkit-font-smoothing: antialiased;
        letter-spacing: -0.2px;
    }

    .main-content {
        padding: 32px 40px;
        background-color: var(--fin-bg);
        min-height: 100vh;
    }

    /* --- TYPOGRAPHY --- */
    .text-title { font-weight: 700; color: var(--fin-text-main); letter-spacing: -0.5px; }
    .text-subtitle { font-weight: 500; color: var(--fin-text-muted); }

    /* --- LOADING BAR --- */
    #page-loader-bar {
        position: fixed; top: 0; left: 0; height: 3px; 
        background: var(--fin-primary);
        z-index: 9999; width: 0%; 
        transition: width 0.5s ease-out, opacity 0.4s ease;
    }

    /* --- EFEK GETAR (SHAKE) --- */
    @keyframes shakeError {
        0%, 100% { transform: translateX(0); }
        20%, 60% { transform: translateX(-4px); }
        40%, 80% { transform: translateX(4px); }
    }
    .shake-error { animation: shakeError 0.4s ease-in-out; }

    /* --- CARDS (FinPay Style) --- */
    .fin-card {
        background: var(--fin-surface);
        border-radius: var(--radius-xl);
        padding: 24px;
        box-shadow: var(--shadow-soft);
        border: 1px solid rgba(0,0,0,0.015);
        transition: all 0.3s ease;
        position: relative;
    }
    .fin-card:hover {
        box-shadow: var(--shadow-hover);
        transform: translateY(-2px);
    }

    /* Special Blue Card (Mimic FinPay Credit Card Widget) */
    .fin-card-blue {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: white;
        border: none;
        box-shadow: 0 12px 24px rgba(37, 99, 235, 0.25);
        position: relative;
        overflow: hidden;
    }
    .fin-card-blue::before {
        content: ''; position: absolute; top: -20px; right: -20px;
        width: 100px; height: 100px; border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
    }
    .fin-card-blue::after {
        content: ''; position: absolute; bottom: -30px; right: 40px;
        width: 60px; height: 60px; border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
    }
    .fin-card-blue .text-subtitle { color: rgba(255,255,255,0.8); }
    .fin-card-blue .text-title { color: white; }
    .fin-card-blue .icon-wrapper { background: rgba(255,255,255,0.2); color: white; }

    /* --- WIDGET ICONS --- */
    .icon-wrapper {
        width: 48px; height: 48px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.25rem;
        margin-bottom: 20px;
    }
    .icon-soft-blue { background: var(--fin-primary-soft); color: var(--fin-primary); }
    .icon-soft-red { background: var(--fin-danger-bg); color: var(--fin-danger); }
    .icon-soft-green { background: var(--fin-success-bg); color: var(--fin-success); }
    .icon-soft-orange { background: #fff7ed; color: #f97316; }

    /* --- BUTTONS --- */
    .btn-fin-primary {
        background: var(--fin-primary) !important; color: #ffffff !important; 
        border: none; border-radius: 8px; font-weight: 600;
        padding: 10px 20px; transition: all 0.2s ease;
        font-size: 0.9rem; display: flex; align-items: center;
    }
    .btn-fin-primary:hover {
        background: var(--fin-primary-dark) !important; color: #ffffff !important;
        transform: scale(1.02); box-shadow: 0 6px 15px rgba(37, 99, 235, 0.2);
    }
    .btn-fin-light {
        background: var(--fin-surface) !important; color: var(--fin-text-main) !important;
        border: 1px solid #e2e8f0; border-radius: 8px;
        font-weight: 600; padding: 10px 20px; transition: all 0.2s ease;
        font-size: 0.9rem; box-shadow: var(--shadow-soft); display: flex; align-items: center;
    }
    .btn-fin-light:hover {
        background: #f8fafc !important; transform: scale(1.02);
    }

    /* --- SELECT DROPDOWN --- */
    .fin-select {
        background-color: var(--fin-bg);
        border: none; border-radius: 8px;
        color: var(--fin-text-main); font-weight: 600; font-size: 0.85rem;
        padding: 8px 32px 8px 16px; cursor: pointer;
        box-shadow: none;
    }
    .fin-select:focus { outline: none; box-shadow: 0 0 0 2px var(--fin-primary-soft); }

    /* --- TABLE (FinPay Style) --- */
    .fin-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .fin-table th {
        background: transparent; color: var(--fin-text-muted);
        font-weight: 600; font-size: 0.75rem; text-transform: uppercase;
        letter-spacing: 0.5px; border-bottom: 1px solid var(--fin-border);
        padding: 16px 20px; white-space: nowrap;
    }
    .fin-table td {
        padding: 16px 20px; vertical-align: middle;
        border-bottom: 1px solid var(--fin-border);
        color: var(--fin-text-main); font-weight: 500; font-size: 0.9rem;
    }
    .fin-table tbody tr { transition: background-color 0.2s ease; }
    .fin-table tbody tr:hover { background-color: #f8fafc; }
    .fin-table tbody tr:last-child td { border-bottom: none; }

    /* BADGES */
    .fin-badge { 
        padding: 6px 12px; border-radius: 6px; 
        font-size: 0.75rem; font-weight: 600; letter-spacing: 0.3px;
    }
    .badge-success { background: var(--fin-success-bg); color: var(--fin-success); }
    .badge-danger { background: var(--fin-danger-bg); color: var(--fin-danger); }
    .badge-pending { background: #fff7ed; color: #d97706; }

    /* --- HORIZONTAL SCROLL FOR PRICES (CARD STYLE) --- */
    .price-scroll-container {
        display: flex;
        overflow-x: auto;
        gap: 16px; /* Jarak antar kartu mini */
        padding-bottom: 8px; /* Ruang untuk shadow */
        padding-top: 4px;
        
        /* Hide scrollbar for a cleaner look */
        scrollbar-width: none; 
        -ms-overflow-style: none; 
    }
    .price-scroll-container::-webkit-scrollbar { display: none; }
    
    /* Card Shape untuk masing-masing kadar emas */
    .price-item {
        min-width: max-content;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.015);
        flex-shrink: 0;
    }

    /* --- TOAST / NOTIFICATION --- */
    .toast-fin {
        background-color: #e0e7ff !important; 
        border: 1px solid #c7d2fe !important;
        border-radius: var(--radius-lg) !important;
        box-shadow: 0 10px 25px rgba(67, 56, 202, 0.1) !important;
        color: #3730a3 !important; 
    }
    .toast-fin .btn-close { filter: opacity(0.6) drop-shadow(0 0 0 #3730a3); }
    
    @keyframes slideInRight {
        0% { transform: translateX(100%); opacity: 0; }
        100% { transform: translateX(0); opacity: 1; }
    }
    .toast.show { animation: slideInRight 0.5s cubic-bezier(0.25, 0.8, 0.25, 1) forwards; }

    /* Scrollbar Vertical untuk Ringkasan Stok */
    .fin-scroll::-webkit-scrollbar { width: 5px; }
    .fin-scroll::-webkit-scrollbar-track { background: transparent; }
    .fin-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    
</style>

<div id="page-loader-bar"></div>

<div class="main-content">
    
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="text-title mb-1 fs-3">Halo <?php echo $_SESSION['username'] ?? 'Owner'; ?>, Selamat datang kembali! <span style="font-size: 1.1rem;">👋</span></h2>
            <p class="text-subtitle mb-0" style="font-size: 0.95rem;">Berikut adalah ringkasan aktivitas toko hari ini.</p>
        </div>
        
        <div class="d-flex gap-3">
            <a href="../stok/data_barang.php#tambah_stok" class="btn btn-fin-light hover-shake-btn">
                <i class="bi bi-box-arrow-in-down me-2 text-muted"></i> Stok Masuk
            </a>
            <a href="../transaksi/input_jual.php" class="btn btn-fin-primary">
                <i class="bi bi-plus-lg me-2"></i> Transaksi Baru
            </a>
        </div>
    </div>

    <div class="fin-card p-3 px-4 mb-4" style="border-left: 4px solid var(--fin-primary);">
        <div class="d-flex align-items-center">
            
            <div class="d-flex flex-column justify-content-center me-4 pe-4 border-end" style="min-width: max-content;">
                <span class="text-subtitle d-block mb-1" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px;">Informasi Harga Acuan</span>
                <div class="d-flex align-items-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; background: var(--fin-primary-soft); color: var(--fin-primary);">
                        <i class="bi bi-tag-fill"></i>
                    </div>
                    <span class="fw-bold text-dark fs-6"><?php echo date('d M Y', strtotime($tgl_hari_ini)); ?></span>
                </div>
            </div>

            <div class="flex-grow-1 price-scroll-container align-items-center pt-1">
                <?php 
                if(count($hargaAcuan) > 0) {
                    foreach($hargaAcuan as $h) {
                        $tgl_db_only = date('Y-m-d', strtotime($h['Tanggal']));
                        $isToday = ($tgl_db_only == $tgl_hari_ini);
                        
                        $kadarColor = $isToday ? 'var(--fin-primary)' : 'var(--fin-danger)';
                        $priceColor = $isToday ? 'var(--fin-text-main)' : 'var(--fin-danger)';
                        
                        echo '<div class="price-item d-flex flex-column">';
                        echo '  <div class="d-flex align-items-baseline gap-2">';
                        echo '      <span class="fw-bolder fs-5" style="color: '.$kadarColor.';">'.$h['Kadar'].'</span>';
                        echo '      <span class="fw-bold fs-6" style="color: '.$priceColor.'; letter-spacing: -0.5px;">Rp '.number_format($h['HargaJualPerGram'], 0, ',', '.').'</span>';
                        echo '  </div>';
                        
                        if(!$isToday) {
                            echo '  <small class="fw-semibold mt-1" style="color: var(--fin-danger); font-size: 0.7rem;"><i class="bi bi-exclamation-triangle-fill"></i> Terakhir diupdate: '.date('d M Y', strtotime($h['Tanggal'])).'</small>';
                        } else {
                            echo '  <small class="text-subtitle mt-1" style="font-size: 0.7rem;"><i class="bi bi-check-circle-fill text-success"></i> Sudah Terupdate</small>';
                        }
                        
                        echo '</div>';
                    }
                } else {
                    echo '<span class="small text-danger fw-bold"><i class="bi bi-exclamation-circle"></i> Data harga belum tersedia.</span>';
                }
                ?>
            </div>

            <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'Owner'): ?>
            <div class="ms-4 ps-2 d-none d-lg-block">
                <a href="../master/harga.php" class="btn btn-fin-light text-primary hover-shake-btn" style="border-radius: 8px; padding: 8px 20px;">Update Harga</a>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="fin-card fin-card-blue h-100">
                <div class="icon-wrapper">
                    <i class="bi bi-credit-card-2-front-fill"></i>
                </div>
                <p class="text-subtitle mb-1 fs-6">Total Pemasukan</p>
                <h3 class="text-title mb-2">Rp <?php echo number_format($omzet, 0, ',', '.'); ?></h3>
                <div class="d-flex align-items-center fw-semibold mt-4" style="font-size: 0.8rem; opacity: 0.9;">
                    <i class="bi bi-graph-up-arrow me-2"></i> Penjualan Hari Ini
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="fin-card h-100">
                <div class="icon-wrapper icon-soft-red">
                    <i class="bi bi-arrow-down-left-square-fill"></i>
                </div>
                <p class="text-subtitle mb-1 fs-6">Total Pengeluaran</p>
                <h3 class="text-title mb-2">Rp <?php echo number_format($buyback, 0, ',', '.'); ?></h3>
                <div class="d-flex align-items-center fw-semibold mt-4" style="font-size: 0.8rem; color: var(--fin-danger);">
                    <i class="bi bi-graph-down-arrow me-2"></i> Buyback Hari Ini
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="fin-card h-100">
                <div class="icon-wrapper icon-soft-blue">
                    <i class="bi bi-box-seam-fill"></i>
                </div>
                <p class="text-subtitle mb-1 fs-6">Stok Tersedia</p>
                <h3 class="text-title mb-2"><?php echo $totalStok; ?> <span class="fs-6 text-muted fw-normal">Item</span></h3>
                <div class="d-flex align-items-center fw-semibold mt-4" style="font-size: 0.8rem; color: var(--fin-primary);">
                    <i class="bi bi-check-circle-fill me-2"></i> Tersedia di Etalase
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="fin-card h-100">
                <div class="icon-wrapper icon-soft-green">
                    <i class="bi bi-receipt"></i>
                </div>
                <p class="text-subtitle mb-1 fs-6">Total Transaksi</p>
                <h3 class="text-title mb-2"><?php echo $jmlNota; ?> <span class="fs-6 text-muted fw-normal">Nota</span></h3>
                <div class="d-flex align-items-center fw-semibold mt-4" style="font-size: 0.8rem; color: var(--fin-success);">
                    <i class="bi bi-calendar-check-fill me-2"></i> Dibuat Hari Ini
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="fin-card h-100 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="text-title mb-1 fs-5">Statistik Keuangan</h5>
                        <p class="text-subtitle small mb-0">Grafik pergerakan harga emas per gram (7 hari terakhir).</p>
                    </div>
                    <select id="filterKadar" class="fin-select border rounded-3 shadow-sm" style="background: white;">
                        <?php foreach($listKadar as $kdr): ?>
                            <option value="<?php echo $kdr; ?>">Kadar <?php echo $kdr; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="height: 250px; width: 100%;">
                    <canvas id="goldPriceChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="fin-card h-100 p-0 d-flex flex-column overflow-hidden">
                <div class="d-flex justify-content-between align-items-center p-4 border-bottom" style="border-color: var(--fin-border) !important;">
                    <h5 class="text-title mb-0 fs-5">Ringkasan Stok</h5>
                    <a href="../stok/data_barang.php" class="text-decoration-none fw-semibold" style="font-size: 0.85rem; color: var(--fin-primary);">Lihat Semua</a>
                </div>
                
                <div class="flex-grow-1 p-3 fin-scroll" style="overflow-y: auto; max-height: 250px;">
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
                            $percent = ($jumlah > 0) ? min(100, $jumlah * 5) : 0; 
                    ?>
                        <div class="mb-3 px-3 py-2 rounded-3" style="border: 1px solid var(--fin-border); background: #ffffff;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-gem me-2" style="color: #94a3b8; font-size: 0.85rem;"></i>
                                    <span class="text-title text-truncate" style="max-width: 140px; font-size: 0.85rem;" title="<?php echo $row['NamaProduk']; ?>">
                                        <?php echo $row['NamaProduk']; ?>
                                    </span>
                                </div>
                                <i class="bi bi-three-dots-vertical text-muted" style="cursor: pointer; font-size: 0.8rem;"></i>
                            </div>
                            
                            <div style="height: 5px; background: #f1f5f9; border-radius: 10px; overflow: hidden; display: flex;">
                                <div style="width: <?php echo $percent; ?>%; background: var(--fin-primary); border-radius: 10px;"></div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <small class="text-title" style="font-size: 0.75rem;"><?php echo $jumlah; ?> Unit</small>
                                <small class="text-subtitle" style="font-size: 0.7rem;">Kadar <?php echo $row['Kadar']; ?></small>
                            </div>
                        </div>
                    <?php 
                        }
                    } else {
                        echo "<div class='text-center py-4'>
                                <p class='text-subtitle small mb-0'>Data katalog kosong.</p>
                              </div>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-12">
        <div class="fin-card p-0 overflow-hidden">
            <div class="d-flex justify-content-between align-items-center p-4 border-bottom" style="border-color: var(--fin-border) !important;">
                <h5 class="text-title mb-0 fs-5">Transaksi Terakhir</h5>
                
                <form method="GET" action="" class="m-0">
                    <select name="filter_trx" class="fin-select border rounded-3 shadow-sm" style="padding: 6px 30px 6px 12px; background: white;" onchange="this.form.submit()">
                        <option value="hari_ini" <?php if($filter_trx == 'hari_ini') echo 'selected'; ?>>Hari Ini</option>
                        <option value="minggu_ini" <?php if($filter_trx == 'minggu_ini') echo 'selected'; ?>>7 Hari Terakhir</option>
                        <option value="bulan_ini" <?php if($filter_trx == 'bulan_ini') echo 'selected'; ?>>30 Hari Terakhir</option>
                        <option value="semua" <?php if($filter_trx == 'semua') echo 'selected'; ?>>Semua Waktu</option>
                    </select>
                </form>
            </div>
            
            <div class="table-responsive">
                <table class="fin-table">
                    <thead>
                        <tr>
                            <th class="ps-4">Nama Transaksi</th>
                            <th>Tanggal & Waktu</th>
                            <th>Jumlah</th>
                            <th>Status</th>
                            <th class="pe-4 text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Eksekusi query dengan filter waktu yang dinamis (LIMIT 10 agar lebih informatif)
                        $queryLastTrx = mysqli_query($koneksi, "SELECT * FROM transaksi $where_trx ORDER BY TransaksiID DESC LIMIT 10");
                        
                        if(mysqli_num_rows($queryLastTrx) > 0) {
                            while($trx = mysqli_fetch_assoc($queryLastTrx)) {
                                $isPenjualan = ($trx['TipeTransaksi'] == 'Penjualan');
                                $badgeColor = $isPenjualan ? 'badge-success' : 'badge-danger';
                                $desc = $isPenjualan ? 'Pembelian Pelanggan' : 'Toko Beli Kembali';
                        ?>
                        <tr>
                            <td class="ps-4">
                                <span class="d-block text-title fs-6">#TRX-<?php echo $trx['TransaksiID']; ?></span>
                                <span class="text-subtitle" style="font-size: 0.75rem;"><?php echo $desc; ?></span>
                            </td>
                            <td>
                                <span class="d-block text-title" style="font-size: 0.85rem;"><?php echo date('d M Y', strtotime($trx['TanggalWaktu'])); ?></span>
                                <span class="text-subtitle" style="font-size: 0.75rem;"><?php echo date('H:i:s', strtotime($trx['TanggalWaktu'])); ?></span>
                            </td>
                            <td class="text-title fs-6">Rp <?php echo number_format($trx['TotalTransaksi'], 0, ',', '.'); ?></td>
                            <td><span class="fin-badge <?php echo $badgeColor; ?>"><?php echo $trx['TipeTransaksi']; ?></span></td>
                            <td class="pe-4 text-end">
                                <a href="../transaksi/cetak_nota.php?id=<?php echo $trx['TransaksiID']; ?>" target="_blank" class="btn btn-sm btn-light text-primary rounded-circle shadow-sm" style="width: 32px; height: 32px; line-height: 20px; border: 1px solid var(--fin-border);" title="Cetak Nota"><i class="bi bi-printer"></i></a>
                            </td>
                        </tr>
                        <?php 
                            }
                        } else {
                        ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted fw-semibold">
                                <div class="d-flex flex-column align-items-center justify-content-center opacity-75">
                                    <i class="bi bi-inbox fs-1 mb-2 text-slate-300"></i>
                                    <span>Belum ada transaksi untuk rentang waktu ini.</span>
                                </div>
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
<div class="toast-container position-fixed top-0 end-0 p-4" style="z-index: 1055; margin-top: 20px;">
    <div id="priceReminderToast" class="toast toast-fin" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="false">
        <div class="toast-body p-4 d-flex align-items-start">
            <div class="bg-white rounded-circle d-flex justify-content-center align-items-center shadow-sm me-3 flex-shrink-0" style="width: 38px; height: 38px; color: #4338ca;">
                <i class="bi bi-info-circle-fill fs-5"></i>
            </div>
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <strong class="fs-6" style="color: #312e81;">Peringatan Sistem</strong>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <p class="mb-3" style="font-size: 0.85rem; line-height: 1.5; color: #3730a3;">
                    Harga emas berikut <b>belum diperbarui hari ini</b>:
                    <span class="fw-bold d-block mt-1"><?php echo implode(', ', $outdatedKadarList); ?></span>
                </p>
                <div class="d-flex gap-2">
                    <a href="../master/harga.php" class="btn btn-sm bg-white border border-light rounded-pill fw-bold shadow-sm" style="color: #4338ca; padding: 6px 16px;">Update Sekarang</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var toastEl = document.getElementById('priceReminderToast');
        if(toastEl) {
            var toast = new bootstrap.Toast(toastEl);
            setTimeout(function() { toast.show(); }, 800);
        }
    });
</script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // 1. Script Loading Bar
    document.addEventListener("DOMContentLoaded", function() {
        const loader = document.getElementById("page-loader-bar");
        loader.style.width = "40%";
        setTimeout(() => {
            loader.style.width = "100%";
            setTimeout(() => { loader.style.opacity = "0"; }, 400); 
        }, 200);
    });

    // 2. Fungsi Global Efek Shake
    function triggerShake(element) {
        element.classList.remove('shake-error');
        void element.offsetWidth; // Trigger reflow
        element.classList.add('shake-error');
    }

    document.querySelectorAll('.hover-shake-btn').forEach(btn => {
        btn.addEventListener('click', function(e) { triggerShake(this); });
    });

    // 3. Script Chart.js bergaya Bar
    const ctx = document.getElementById('goldPriceChart').getContext('2d');
    const allData = <?php echo json_encode($chartData); ?>;
    const select = document.getElementById('filterKadar');
    
    Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "SF Pro Display", "Helvetica Neue", sans-serif';
    Chart.defaults.color = '#64748b';

    let myChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Harga Jual (Rp)',
                data: [],
                backgroundColor: '#3b82f6', 
                borderRadius: 4,
                barThickness: 24,
                hoverBackgroundColor: '#2563eb'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a',
                    titleFont: { size: 13, weight: 'bold' },
                    bodyFont: { size: 14 },
                    padding: 12, cornerRadius: 10, displayColors: false,
                    callbacks: {
                        label: function(context) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(context.parsed.y); }
                    }
                }
            },
            scales: {
                y: { 
                    grid: { color: '#f1f5f9', drawBorder: false },
                    ticks: { callback: function(value) { return 'Rp ' + value / 1000 + 'k'; } }
                },
                x: { grid: { display: false, drawBorder: false } }
            }
        }
    });

    function updateChart(kadar) {
        const newData = allData[kadar];
        if (newData) {
            myChart.data.labels = newData.labels;
            myChart.data.datasets[0].data = newData.data;
            if (kadar === '24K') {
                myChart.data.datasets[0].backgroundColor = '#f59e0b';
                myChart.data.datasets[0].hoverBackgroundColor = '#d97706';
            } else {
                myChart.data.datasets[0].backgroundColor = '#3b82f6';
                myChart.data.datasets[0].hoverBackgroundColor = '#2563eb';
            }
            myChart.update();
        }
    }

    if(select.value) updateChart(select.value);
    select.addEventListener('change', function() { updateChart(this.value); });
</script>

<?php include '../../layouts/footer.php'; ?>