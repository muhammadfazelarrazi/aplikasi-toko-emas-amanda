<?php 
session_start();
include '../../config/database.php'; 

// Inisialisasi Keranjang
if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

// --- AMBIL DATA PELANGGAN UNTUK DROPDOWN ---
$dataPelanggan = [];
$qPel = mysqli_query($koneksi, "SELECT * FROM pelanggan ORDER BY NamaPelanggan ASC");
while($p = mysqli_fetch_assoc($qPel)){
    $dataPelanggan[] = $p;
}

// --- LOGIKA 1: CARI BARANG & TAMPILAN DEFAULT ---
$hasilPencarian = null; 
$pesanError = "";
$isSearching = false; 

if (isset($_POST['cari_barang'])) {
    $isSearching = true;
    $kode = mysqli_real_escape_string($koneksi, $_POST['kode_input']);
    
    $queryCari = "SELECT bs.*, pk.NamaProduk, pk.Kadar, pk.Tipe, 
                  (SELECT HargaJualPerGram FROM riwayat_harga WHERE Kadar = pk.Kadar ORDER BY Tanggal DESC LIMIT 1) as HargaJualPerGram,
                  (SELECT Tanggal FROM riwayat_harga WHERE Kadar = pk.Kadar ORDER BY Tanggal DESC LIMIT 1) as TanggalHarga
                  FROM barang_stok bs
                  JOIN produk_katalog pk ON bs.ProdukKatalogID = pk.ProdukKatalogID
                  WHERE (bs.KodeBarang LIKE '%$kode%' OR pk.NamaProduk LIKE '%$kode%') 
                  AND bs.Status = 'Tersedia'
                  ORDER BY pk.NamaProduk ASC";
                  
    $resultCari = mysqli_query($koneksi, $queryCari);
    
    if (mysqli_num_rows($resultCari) > 0) {
        $hasilPencarian = $resultCari; 
    } else {
        $pesanError = "Barang dengan kata kunci tersebut tidak ditemukan atau stok kosong.";
    }
} else {
    $queryDefault = "SELECT bs.*, pk.NamaProduk, pk.Kadar, pk.Tipe, 
                     (SELECT HargaJualPerGram FROM riwayat_harga WHERE Kadar = pk.Kadar ORDER BY Tanggal DESC LIMIT 1) as HargaJualPerGram,
                     (SELECT Tanggal FROM riwayat_harga WHERE Kadar = pk.Kadar ORDER BY Tanggal DESC LIMIT 1) as TanggalHarga
                     FROM barang_stok bs
                     JOIN produk_katalog pk ON bs.ProdukKatalogID = pk.ProdukKatalogID
                     WHERE bs.Status = 'Tersedia'
                     ORDER BY bs.TanggalMasuk DESC LIMIT 50";
                     
    $hasilPencarian = mysqli_query($koneksi, $queryDefault);
}

// --- LOGIKA 2: TAMBAH KE KERANJANG ---
if (isset($_POST['tambah_keranjang'])) {
    $id = $_POST['id_barang'];
    $sudahAda = false;
    foreach ($_SESSION['keranjang'] as $item) {
        if ($item['BarangID'] == $id) { $sudahAda = true; break; }
    }
    
    if (!$sudahAda) {
        $itemBaru = [
            'BarangID' => $_POST['id_barang'],
            'Kode' => $_POST['kode_barang'],
            'Nama' => $_POST['nama_barang'],
            'Kadar' => $_POST['kadar'],
            'Berat' => $_POST['berat'],
            'HargaPerGram' => $_POST['harga_per_gram'],
            'HargaTotal' => $_POST['harga_total_item']
        ];
        $_SESSION['keranjang'][] = $itemBaru;
    }
}

// --- LOGIKA 3 & 4: HAPUS/RESET ---
if (isset($_GET['hapus'])) {
    $index = $_GET['hapus'];
    unset($_SESSION['keranjang'][$index]);
    $_SESSION['keranjang'] = array_values($_SESSION['keranjang']); 
    exit; 
}

if (isset($_GET['reset'])) {
    unset($_SESSION['keranjang']);
    exit;
}

include '../../layouts/header.php'; 
include '../../layouts/sidebar.php'; 
?>

<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = { 
      corePlugins: { 
          preflight: false,
          visibility: false
      } 
  }
</script>

<style>
    .collapse { visibility: visible !important; }
    .collapse:not(.show) { display: none !important; }
    .collapsing { visibility: visible !important; }

    body { 
        font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", "Helvetica Neue", Helvetica, Arial, sans-serif;
        background-color: #f8fafc; 
        color: #0f172a;
        -webkit-font-smoothing: antialiased;
        letter-spacing: -0.2px;
        overflow: hidden; 
    }

    .custom-scroll::-webkit-scrollbar { width: 5px; height: 5px; }
    .custom-scroll::-webkit-scrollbar-track { background: transparent; }
    .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    
    input, button, select, textarea {
        border: none !important;
        outline: none !important;
        box-shadow: none !important;
        background: transparent;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
    }
    input:focus, button:focus, select:focus { outline: none !important; box-shadow: none !important; }
    input[type=number]::-webkit-inner-spin-button, 
    input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }

    .pos-container {
        height: 100vh; display: flex; flex-direction: column;
        padding: 32px 40px 16px 40px; 
    }
    @media (max-width: 768px) { .pos-container { padding: 16px; } }

    .modal-backdrop-show { opacity: 1 !important; }
    .modal-card-show { opacity: 1 !important; transform: scale(1) translateY(0) !important; }

    /* Floating Toast Animation */
    .success-toast { transform: translate(-50%, -20px); opacity: 0; transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); }
    .success-toast.show { transform: translate(-50%, 0); opacity: 1; }
    .animated-check path { stroke-dasharray: 50; stroke-dashoffset: 50; }
    .success-toast.show .animated-check path,
    .show-check-anim .animated-check path { animation: drawCheck 0.5s cubic-bezier(0.65, 0, 0.45, 1) 0.1s forwards; }
    @keyframes drawCheck { to { stroke-dashoffset: 0; } }

    /* ANIMASI SHAKE (GETAR) UNTUK TOMBOL ERROR */
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        20%, 60% { transform: translateX(-5px); }
        40%, 80% { transform: translateX(5px); }
    }
    .animate-shake { animation: shake 0.4s ease-in-out; }

    .text-title { font-weight: 700; color: #0f172a; letter-spacing: -0.5px; }
    .text-subtitle { font-weight: 500; color: #64748b; }

    @media print {
        body * { visibility: hidden; }
        #printArea, #printArea * { visibility: visible; }
        #printArea {
            position: absolute; left: 0; top: 0; width: 58mm; 
            margin: 0; padding: 0; box-shadow: none;
        }
        @page { size: 58mm auto; margin: 0mm; }
    }
</style>

<div id="successToast" class="fixed top-6 left-1/2 z-[10001] flex items-center gap-3 px-4 py-3 bg-white/95 backdrop-blur-md shadow-lg border border-slate-200 rounded-2xl success-toast pointer-events-none">
    <div class="w-8 h-8 rounded-full bg-emerald-50 border border-emerald-100 flex items-center justify-center flex-shrink-0">
        <svg class="w-5 h-5 text-emerald-500 animated-check" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
        </svg>
    </div>
    <span id="successToastMessage" class="text-sm font-bold text-slate-700 tracking-wide pr-2">Berhasil!</span>
</div>

<div id="warningToast" class="fixed top-6 left-1/2 z-[10001] flex items-center gap-3 px-4 py-3 bg-white/95 backdrop-blur-md shadow-lg border border-red-200 rounded-2xl success-toast pointer-events-none">
    <div class="w-8 h-8 rounded-full bg-red-50 border border-red-100 flex items-center justify-center flex-shrink-0">
        <i class="bi bi-exclamation-triangle-fill text-red-500 text-lg"></i>
    </div>
    <span id="warningToastMessage" class="text-sm font-bold text-slate-700 tracking-wide pr-2">Mohon lengkapi data!</span>
</div>

<div id="miniLoader" class="fixed top-6 left-1/2 transform -translate-x-1/2 -translate-y-4 opacity-0 z-[10000] flex items-center gap-3 px-4 py-2 bg-white/90 backdrop-blur-md shadow-md border border-slate-200 rounded-full transition-all duration-300 pointer-events-none hidden">
    <div class="animate-spin rounded-full h-4 w-4 border-2 border-slate-200 border-t-[#3b82f6]"></div>
    <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Memproses</span>
</div>

<div id="customConfirmModal" class="fixed inset-0 z-[10000] hidden items-center justify-center">
    <div id="confirmBackdrop" class="absolute inset-0 bg-slate-900/30 backdrop-blur-sm opacity-0 transition-opacity duration-300"></div>
    <div id="confirmCard" class="relative bg-white rounded-3xl shadow-2xl w-11/12 max-w-xs p-6 flex flex-col items-center text-center transform scale-95 translate-y-4 opacity-0 transition-all duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)]">
        <div class="w-16 h-16 bg-red-50 text-red-500 rounded-full flex items-center justify-center mb-4 border-[6px] border-red-50 shadow-sm relative">
            <i class="bi bi-trash3-fill text-2xl z-10"></i>
            <div class="absolute inset-0 rounded-full bg-red-500/20 animate-ping"></div>
        </div>
        <h3 class="text-lg font-extrabold text-slate-800 mb-1">Konfirmasi</h3>
        <p id="confirmMessage" class="text-[13px] font-medium text-slate-500 mb-6 leading-relaxed">Apakah Anda yakin ingin menghapus item ini?</p>
        <div class="flex w-full gap-3">
            <button id="btnConfirmCancel" class="flex-1 py-2.5 rounded-xl font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors cursor-pointer" style="border: 1px solid #e2e8f0 !important;">Batal</button>
            <button id="btnConfirmProceed" class="flex-1 py-2.5 rounded-xl font-bold text-white bg-red-500 hover:bg-red-600 shadow-md shadow-red-500/30 transition-all active:scale-95 cursor-pointer">Ya, Hapus</button>
        </div>
    </div>
</div>

<div id="checkoutProcessModal" class="fixed inset-0 z-[10002] hidden items-center justify-center">
    <div id="checkoutBackdrop" class="absolute inset-0 bg-slate-900/50 backdrop-blur-md opacity-0 transition-opacity duration-300"></div>
    <div id="checkoutProcessCard" class="relative bg-white rounded-3xl shadow-2xl w-11/12 max-w-sm p-8 flex flex-col items-center text-center transform scale-95 translate-y-4 opacity-0 transition-all duration-300 ease-out">
        
        <div id="checkoutStateLoading" class="flex flex-col items-center w-full">
            <div class="relative w-16 h-16 mb-5">
                <div class="absolute inset-0 border-4 border-slate-100 rounded-full"></div>
                <div class="absolute inset-0 border-4 border-[#3b82f6] rounded-full border-t-transparent animate-spin"></div>
                <i class="bi bi-cart-check-fill absolute inset-0 flex items-center justify-center text-xl text-[#3b82f6] animate-pulse"></i>
            </div>
            <h3 class="text-lg font-extrabold text-slate-800 mb-1">Memproses Transaksi</h3>
            <p class="text-[13px] font-medium text-slate-500 mb-5 leading-relaxed">Mencatat data dan membuat nota otomatis. Mohon tunggu...</p>
            <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden shadow-inner">
                <div id="checkoutProgressBar" class="bg-gradient-to-r from-[#3b82f6] to-[#60a5fa] h-full rounded-full w-0 transition-all ease-out"></div>
            </div>
        </div>

        <div id="checkoutStateSuccess" class="flex flex-col items-center w-full hidden">
            <div class="w-20 h-20 bg-emerald-50 text-emerald-500 rounded-full flex items-center justify-center mb-5 border-[6px] border-emerald-50 shadow-sm relative">
                <svg class="w-10 h-10 text-emerald-500 animated-check" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                <div class="absolute inset-0 rounded-full bg-emerald-500/20 animate-ping" style="animation-iteration-count: 1;"></div>
            </div>
            <h3 class="text-xl font-extrabold text-slate-800 mb-1">Transaksi Berhasil!</h3>
            <p class="text-[13px] font-medium text-slate-500">Membuka Nota Digital...</p>
        </div>
    </div>
</div>

<div id="receiptModal" class="fixed inset-0 z-[10005] hidden items-center justify-center">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-11/12 max-w-sm flex flex-col overflow-hidden animate-slide-up transform transition-all">
        <div class="bg-slate-50 p-4 border-b border-slate-200 flex justify-between items-center">
            <h3 class="font-bold text-slate-800 text-[15px] flex items-center gap-2"><i class="bi bi-receipt text-[#3b82f6]"></i> Nota Transaksi</h3>
            <button onclick="window.location.reload()" class="text-slate-400 hover:text-red-500 cursor-pointer transition-colors"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="p-4 overflow-y-auto max-h-[60vh] flex justify-center bg-slate-200 custom-scroll">
            <div id="printArea" class="bg-white shadow-sm p-4 w-[300px]">
                </div>
        </div>
        <div class="p-4 border-t border-slate-200 flex gap-3 bg-white">
            <button onclick="window.print()" class="flex-1 bg-[#3b82f6] hover:bg-blue-600 text-white py-2.5 rounded-xl font-bold flex justify-center items-center gap-2 transition-colors cursor-pointer shadow-md">
                <i class="bi bi-printer-fill"></i> Cetak Thermal
            </button>
            <button onclick="window.location.reload()" class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 py-2.5 rounded-xl font-bold transition-colors cursor-pointer" style="border: 1px solid #e2e8f0 !important;">
                Transaksi Baru
            </button>
        </div>
    </div>
</div>

<div class="main-content pos-container">
    
    <div class="mb-4 flex-shrink-0 flex items-center justify-between">
        <div>
            <h2 class="text-[1.75rem] text-title mb-1 flex items-center gap-2">
                <i class="bi bi-cart-check-fill text-[#3b82f6]"></i> Transaksi Kasir
            </h2>
            <p class="text-[0.95rem] text-subtitle mb-0">Pilih barang dan selesaikan penjualan ke pelanggan.</p>
        </div>
        <div class="hidden sm:flex">
            <span class="text-sm font-semibold text-slate-500 bg-white px-4 py-2 rounded-xl border border-solid border-slate-200 shadow-sm flex items-center gap-2">
                <i class="bi bi-calendar-event text-[#3b82f6]"></i> <?php echo date('d M Y'); ?>
            </span>
        </div>
    </div>

    <div id="spa-container" class="flex flex-col lg:flex-row gap-4 flex-1 min-h-0"> 
        
        <div id="etalase-container" class="w-full lg:w-[55%] flex flex-col bg-white rounded-[24px] shadow-sm border border-solid border-slate-100 overflow-hidden" style="box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);">
            
            <div class="p-4 border-b border-solid border-slate-100 bg-slate-50/30 flex-shrink-0">
                <form id="formCari" class="flex items-stretch gap-3">
                    <input type="hidden" name="cari_barang" value="1">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="bi bi-upc-scan text-slate-400 text-lg"></i>
                        </div>
                        <input type="text" name="kode_input" class="w-full pl-12 pr-4 py-2.5 bg-white text-slate-700 rounded-xl text-sm font-semibold focus:ring-2 focus:ring-[#3b82f6]/30 transition-all" style="border: 1px solid #e2e8f0 !important;" placeholder="Scan Barcode / Cari Nama..." autocomplete="off" value="<?php echo isset($_POST['kode_input']) ? htmlspecialchars($_POST['kode_input']) : ''; ?>">
                    </div>
                    <button type="submit" class="px-6 py-2.5 bg-[#3b82f6] hover:bg-[#2563eb] text-white text-sm font-bold rounded-xl transition-all shadow-sm hover:shadow-md cursor-pointer flex items-center gap-2">
                        <i class="bi bi-search"></i> Cari
                    </button>
                </form>

                <?php if($pesanError): ?>
                    <div class="mt-3 bg-red-50 text-red-600 px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-2" style="border: 1px solid #fecaca !important;">
                        <i class="bi bi-x-circle-fill text-sm"></i> <?php echo $pesanError; ?>
                    </div>
                <?php endif; ?>

                <div class="flex justify-between items-center mt-3 px-2">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        <?php echo $isSearching ? '<span class="text-[#3b82f6]"><i class="bi bi-funnel-fill"></i> Hasil Pencarian</span>' : '<i class="bi bi-box-seam-fill text-emerald-500"></i> Etalase Barang'; ?>
                    </span>
                    <?php if($isSearching): ?>
                        <button id="btnResetSearch" class="text-[11px] text-red-500 hover:text-red-700 font-bold flex items-center gap-1 cursor-pointer transition-colors"><i class="bi bi-arrow-counterclockwise"></i> Tampilkan Semua</button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto custom-scroll p-4 bg-slate-50/50">
                <?php 
                $inCartIds = !empty($_SESSION['keranjang']) ? array_column($_SESSION['keranjang'], 'BarangID') : [];
                $renderedCount = 0;
                
                if ($hasilPencarian && mysqli_num_rows($hasilPencarian) > 0): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php while($row = mysqli_fetch_assoc($hasilPencarian)): 
                            if (in_array($row['BarangID'], $inCartIds)) { continue; }
                            
                            $renderedCount++;
                            $hargaPerGram = $row['HargaJualPerGram'];
                            $tglHarga = $row['TanggalHarga'];
                            
                            // PERBAIKAN BUG DATETIME: Hapus jam dari database, komparasi Y-m-d saja
                            $tgl_db_only = $tglHarga ? date('Y-m-d', strtotime($tglHarga)) : null;
                            $tgl_hari_ini = date('Y-m-d');
                            $isHariIni = ($tgl_db_only === $tgl_hari_ini);
                            
                            $hargaDasar = $row['BeratGram'] * $hargaPerGram;
                            $isReady = ($hargaPerGram != NULL);
                        ?>
                            <div class="bg-white rounded-2xl p-4 flex flex-col justify-between transition-all group relative shadow-sm hover:shadow-md" style="border: 1px solid #e2e8f0 !important;">
                                
                                <div class="flex justify-between items-start mb-3 gap-2">
                                    <span class="text-[15px] font-extrabold text-slate-800 line-clamp-2 leading-tight" title="<?php echo $row['NamaProduk']; ?>"><?php echo $row['NamaProduk']; ?></span>
                                    <span class="text-[10px] font-mono font-bold px-2 py-1 bg-slate-50 text-slate-500 rounded-md flex-shrink-0" style="border: 1px solid #cbd5e1 !important;"><?php echo $row['KodeBarang']; ?></span>
                                </div>

                                <div class="flex items-center gap-1.5 text-[11px] font-semibold text-slate-500 mb-4">
                                    <span class="bg-amber-50 text-amber-700 px-2 py-0.5 rounded" style="border: 1px solid #fef3c7 !important;"><?php echo $row['Kadar']; ?></span>
                                    <span class="text-slate-300">•</span>
                                    <span class="text-slate-700"><?php echo $row['BeratGram']; ?>g</span>
                                    <span class="text-slate-300">•</span>
                                    <span class="text-slate-400 opacity-80">@<?php echo number_format((float)$hargaPerGram,0,',','.'); ?>/g</span>
                                </div>

                                <div class="mt-auto pt-3 flex items-center justify-between" style="border-top: 1px solid #f1f5f9 !important;">
                                    <div class="flex flex-col">
                                        <?php if ($isReady): ?>
                                            <span class="text-[15px] font-black text-[#3b82f6] leading-none tracking-tight">Rp <?php echo number_format($hargaDasar,0,',','.'); ?></span>
                                            
                                            <?php if (!$isHariIni): ?>
                                                <span class="text-[10px] font-bold text-amber-500 mt-1.5 flex items-center gap-1" title="Terakhir Update: <?php echo date('d M Y', strtotime($tglHarga)); ?>">
                                                    <i class="bi bi-exclamation-triangle-fill"></i> Harga Lama
                                                </span>
                                            <?php else: ?>
                                                <span class="text-[10px] font-bold text-emerald-500 mt-1.5 flex items-center gap-1" title="Sudah Terupdate Hari Ini">
                                                    <i class="bi bi-check-circle-fill"></i> Harga Terupdate
                                                </span>
                                            <?php endif; ?>
                                            
                                        <?php else: ?>
                                            <span class="text-[10px] font-bold text-red-500 bg-red-50 px-2 py-1 rounded-md" style="border: 1px solid #fee2e2 !important;">Set Harga Dulu</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <form class="form-tambah m-0">
                                        <input type="hidden" name="tambah_keranjang" value="1">
                                        <input type="hidden" name="id_barang" value="<?php echo $row['BarangID']; ?>">
                                        <input type="hidden" name="kode_barang" value="<?php echo $row['KodeBarang']; ?>">
                                        <input type="hidden" name="nama_barang" value="<?php echo $row['NamaProduk']; ?>">
                                        <input type="hidden" name="kadar" value="<?php echo $row['Kadar']; ?>">
                                        <input type="hidden" name="berat" value="<?php echo $row['BeratGram']; ?>">
                                        <input type="hidden" name="harga_per_gram" value="<?php echo $hargaPerGram; ?>">
                                        <input type="hidden" name="harga_total_item" value="<?php echo $hargaDasar; ?>">
                                        
                                        <button type="submit" class="w-10 h-10 rounded-xl flex items-center justify-center transition-all cursor-pointer <?php echo $isReady ? 'bg-blue-50 text-[#3b82f6] hover:bg-[#3b82f6] hover:text-white shadow-sm' : 'bg-slate-100 text-slate-300 cursor-not-allowed'; ?>" <?php echo !$isReady ? 'disabled' : ''; ?> title="Tambah" style="<?php echo $isReady ? 'border: 1px solid #bfdbfe !important;' : ''; ?>">
                                            <i class="bi bi-plus-lg font-bold text-xl"></i>
                                        </button>
                                    </form>
                                </div>
                                
                            </div>
                        <?php endwhile; ?>
                    </div>
                    
                    <?php if($renderedCount == 0): ?>
                        <div class="h-full flex flex-col items-center justify-center text-slate-400 py-10">
                            <i class="bi bi-cart-check-fill text-6xl mb-3 opacity-60 text-emerald-500"></i>
                            <span class="text-[15px] font-bold text-slate-500">Semua barang sudah di keranjang.</span>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="h-full flex flex-col items-center justify-center text-slate-400">
                        <i class="bi bi-inbox text-6xl mb-3 opacity-40"></i>
                        <span class="text-[15px] font-bold">Tidak ada barang ditemukan.</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div id="keranjang-wrapper" class="w-full lg:w-[45%] flex flex-col bg-white rounded-[24px] shadow-sm border border-solid border-slate-100 overflow-hidden flex-1" style="box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);">
            
            <div class="p-4 border-b border-solid border-slate-100 flex items-center justify-between bg-slate-50/30 flex-shrink-0">
                <span class="text-[15px] font-bold text-slate-800 flex items-center gap-2">
                    <i class="bi bi-cart3 text-[#3b82f6] text-xl"></i> Keranjang Belanja
                    <?php $jmlKeranjang = count($_SESSION['keranjang']); if($jmlKeranjang > 0): ?>
                        <span class="ml-1.5 text-[10px] bg-slate-200 text-slate-600 px-2 py-0.5 rounded-full font-bold"><?php echo $jmlKeranjang; ?> Item</span>
                    <?php endif; ?>
                </span>
                <?php if(!empty($_SESSION['keranjang'])): ?>
                    <button class="text-[11px] font-bold px-3 py-1.5 bg-red-50 text-red-600 hover:bg-red-500 hover:text-white rounded-lg transition-colors btn-ajax-delete cursor-pointer" data-href="?reset=true" data-message="Mengosongkan semua isi keranjang?" style="border: 1px solid #fecaca !important;">Reset</button>
                <?php endif; ?>
            </div>

            <div id="list-keranjang" class="flex-1 overflow-y-auto custom-scroll p-4">
                <?php 
                $grandTotal = 0;
                if (empty($_SESSION['keranjang'])): ?>
                    <div class="h-full flex flex-col items-center justify-center text-slate-300">
                        <i class="bi bi-cart-x text-6xl mb-3 opacity-40"></i>
                        <span class="text-sm font-bold uppercase tracking-wider">Keranjang Kosong</span>
                    </div>
                <?php else: ?>
                    <div class="flex flex-col gap-2.5">
                        <?php 
                        $noItem = 1;
                        foreach ($_SESSION['keranjang'] as $key => $item): 
                            $grandTotal += $item['HargaTotal']; 
                        ?>
                            <div class="bg-white rounded-xl p-3 flex items-center justify-between shadow-sm transition-all hover:shadow-md gap-3" style="border: 1px solid #e2e8f0 !important;">
                                <div class="w-6 h-6 rounded-full bg-slate-50 flex items-center justify-center flex-shrink-0 text-[11px] font-bold text-slate-400" style="border: 1px solid #cbd5e1 !important;">
                                    <?php echo $noItem++; ?>
                                </div>
                                <div class="flex flex-col flex-1">
                                    <span class="text-[13px] font-extrabold text-slate-800 mb-1"><?php echo $item['Nama']; ?></span>
                                    <span class="text-[11px] font-semibold text-slate-500 flex items-center gap-2">
                                        <span class="font-mono text-slate-400 bg-slate-50 px-1 rounded border border-solid border-slate-100"><?php echo $item['Kode']; ?></span>
                                        <span class="text-slate-300">•</span>
                                        <span class="text-amber-600"><?php echo $item['Kadar']; ?></span>
                                        <span class="text-slate-300">•</span>
                                        <span class="text-slate-700"><?php echo $item['Berat']; ?>g</span>
                                    </span>
                                </div>
                                <div class="flex items-center gap-4">
                                    <span class="text-[14px] font-black text-[#3b82f6] tracking-tight">Rp <?php echo number_format($item['HargaTotal'], 0, ',', '.'); ?></span>
                                    <button class="w-8 h-8 rounded-lg bg-red-50 text-red-500 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors btn-ajax-delete cursor-pointer" data-href="?hapus=<?php echo $key; ?>" data-message="Hapus '<?php echo $item['Nama']; ?>' dari keranjang?" title="Hapus">
                                        <i class="bi bi-trash3 text-[15px] pointer-events-none"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div id="checkout-panel" class="p-3 border-t border-solid border-slate-100 bg-slate-50 flex-shrink-0">
                <form id="formCheckout" action="proses_jual.php" method="POST" class="m-0" novalidate>
                    
                    <div class="grid grid-cols-2 gap-2.5 mb-3">
                        <div class="col-span-2 flex items-center gap-2 mb-[-6px]">
                            <i class="bi bi-person-vcard text-[#3b82f6] text-[14px]"></i>
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Identitas Pelanggan</span>
                        </div>
                        
                        <div class="col-span-1 relative">
                            <input type="text" name="nama_pelanggan" id="inputNama" class="w-full px-3 py-1.5 bg-white rounded-lg text-[12px] font-bold text-slate-700 focus:ring-2 focus:ring-[#3b82f6]/30 transition-all shadow-sm" style="border: 1px solid #cbd5e1 !important;" placeholder="Nama (Wajib)*" required autocomplete="off">
                            
                            <div id="autocompleteDropdown" class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-48 overflow-y-auto hidden flex-col custom-scroll">
                            </div>
                        </div>

                        <div class="col-span-1">
                            <input type="text" name="no_hp" id="inputHP" class="w-full px-3 py-1.5 bg-white rounded-lg text-[12px] font-bold text-slate-700 focus:ring-2 focus:ring-[#3b82f6]/30 transition-all shadow-sm" style="border: 1px solid #cbd5e1 !important;" placeholder="No WA/Telp (Wajib)*" required autocomplete="off">
                        </div>
                        <div class="col-span-1">
                            <input type="email" name="email_pelanggan" id="inputEmail" class="w-full px-3 py-1.5 bg-white rounded-lg text-[12px] font-bold text-slate-700 focus:ring-2 focus:ring-[#3b82f6]/30 transition-all shadow-sm" style="border: 1px solid #cbd5e1 !important;" placeholder="Email Nota (Wajib)*" required autocomplete="off">
                        </div>
                        <div class="col-span-1 relative">
                            <select name="metode_bayar" class="w-full pl-3 pr-8 py-1.5 bg-white rounded-lg text-[12px] font-bold text-slate-700 appearance-none focus:ring-2 focus:ring-[#3b82f6]/30 transition-all shadow-sm cursor-pointer" style="border: 1px solid #cbd5e1 !important;" required>
                                <?php 
                                $qMetode = mysqli_query($koneksi, "SELECT * FROM metode_pembayaran");
                                while($m = mysqli_fetch_assoc($qMetode)) {
                                    echo "<option value='".$m['MetodeID']."'>".$m['NamaMetode']."</option>";
                                }
                                ?>
                            </select>
                            <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px] pointer-events-none"></i>
                        </div>
                    </div>

                    <div class="bg-white p-3 rounded-xl mb-3 shadow-sm relative overflow-hidden" style="border: 1px solid #bfdbfe !important;">
                        <i class="bi bi-calculator absolute -right-3 -bottom-3 text-[5rem] text-[#3b82f6] opacity-5 rotate-12 pointer-events-none"></i>

                        <div class="flex justify-between items-center mb-2 relative z-10">
                            <span class="text-[11px] font-extrabold text-slate-500">Subtotal Emas</span>
                            <span class="text-[12px] font-black text-slate-800">Rp <?php echo number_format($grandTotal, 0, ',', '.'); ?></span>
                            
                            <!-- Input hidden yang selalu update dari PHP saat direfresh AJAX -->
                            <input type="hidden" id="valSubtotalBase" value="<?php echo $grandTotal; ?>">
                        </div>
                        
                        <div class="flex justify-between items-center mb-2 relative z-10">
                            <span class="text-[11px] font-extrabold text-slate-500">+ Biaya Pembuatan</span>
                            <div class="flex items-center bg-slate-50 rounded-lg px-2 shadow-inner" style="border: 1px solid #e2e8f0 !important;">
                                <span class="text-[11px] font-bold text-slate-400">Rp</span>
                                <!-- PERBAIKAN: Ubah type="number" jadi type="text" -->
                                <input type="text" inputmode="numeric" id="inputOngkos" name="total_ongkos" class="w-[120px] py-1.5 text-[13px] text-right font-bold text-[#3b82f6] focus:outline-none" placeholder="0" autocomplete="off">
                            </div>
                        </div>

                        <div class="flex justify-between items-center mb-2 relative z-10">
                            <span class="text-[11px] font-extrabold text-red-500">- Potongan Harga</span>
                            <div class="flex items-center bg-red-50 rounded-lg px-2 shadow-inner" style="border: 1px solid #fecaca !important;">
                                <span class="text-[11px] font-bold text-red-300">Rp</span>
                                <!-- PERBAIKAN: Ubah type="number" jadi type="text" -->
                                <input type="text" inputmode="numeric" id="inputDiskon" name="diskon" class="w-[120px] py-1.5 text-[13px] text-right font-bold text-red-500 focus:outline-none" placeholder="0" autocomplete="off">
                            </div>
                        </div>
                        
                        <div class="pt-2 mt-1 flex justify-between items-end relative z-10" style="border-top: 1px solid #e2e8f0 !important;">
                            <span class="text-[13px] font-black text-[#3b82f6] uppercase tracking-wider">TOTAL BAYAR</span>
                            <span class="text-[22px] font-black text-[#3b82f6] leading-none tracking-tight" id="displayTotal">Rp <?php echo number_format($grandTotal, 0, ',', '.'); ?></span>
                        </div>
                    </div>

                    <button type="submit" class="w-full py-3 bg-[#3b82f6] hover:bg-[#2563eb] text-white rounded-xl font-black text-[13px] tracking-widest uppercase transition-all shadow-md hover:shadow-lg flex items-center justify-center gap-2.5 disabled:opacity-50 cursor-pointer disabled:cursor-not-allowed" <?php echo empty($_SESSION['keranjang']) ? 'disabled' : ''; ?>>
                        <i class="bi bi-printer-fill text-lg"></i> PROSES TRANSAKSI
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
    function initializeEvents() {
        
        const inOngkos = document.getElementById('inputOngkos');
        const inDiskon = document.getElementById('inputDiskon');
        const dTotal = document.getElementById('displayTotal');

        function hitungTotal() {
            const subInput = document.getElementById('valSubtotalBase');
            let currentSubtotal = subInput ? parseFloat(subInput.value) : 0;

            // PERBAIKAN: Hapus karakter titik (ribuan) sebelum dijumlahkan
            let ongkos = parseFloat(inOngkos.value.replace(/\./g, '')) || 0;
            let diskon = parseFloat(inDiskon.value.replace(/\./g, '')) || 0;
            let total = currentSubtotal + ongkos - diskon;

            if(total < 0) total = 0;
            if(dTotal) dTotal.innerHTML = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
        }
        
        // PERBAIKAN: Fungsi format ribuan secara live
        function formatRibuanLive(e) {
            let val = e.target.value.replace(/[^0-9]/g, ''); // Hapus semua kecuali angka
            if (val !== '') {
                e.target.value = new Intl.NumberFormat('id-ID').format(val);
            } else {
                e.target.value = '';
            }
            hitungTotal(); // Update total setiap kali ngetik
        }

        if(inOngkos) inOngkos.addEventListener('input', formatRibuanLive);
        if(inDiskon) inDiskon.addEventListener('input', formatRibuanLive);

        const dbPelanggan = <?php echo json_encode($dataPelanggan); ?>;
        const iNama = document.getElementById('inputNama');
        const iHP = document.getElementById('inputHP');
        const iEmail = document.getElementById('inputEmail');
        const dropdown = document.getElementById('autocompleteDropdown');

        if(iNama && dropdown) {
            iNama.addEventListener('input', function() {
                const val = this.value.toLowerCase();
                dropdown.innerHTML = '';

                if (val.length >= 2) {
                    const filtered = dbPelanggan.filter(p => p.NamaPelanggan.toLowerCase().includes(val) || p.NoHP.includes(val));

                    if (filtered.length > 0) {
                        filtered.forEach(p => {
                            const item = document.createElement('div');
                            item.className = 'px-3 py-2 cursor-pointer hover:bg-slate-50 border-b border-slate-100 last:border-0 transition-colors';
                            item.innerHTML = `
                                <div class="text-[12px] font-bold text-slate-800">${p.NamaPelanggan}</div>
                                <div class="text-[10px] font-semibold text-slate-500">${p.NoHP}</div>
                            `;
                            item.addEventListener('click', () => {
                                iNama.value = p.NamaPelanggan;
                                if(iHP) iHP.value = p.NoHP;
                                if(iEmail) iEmail.value = p.Email;
                                dropdown.classList.add('hidden');
                                dropdown.classList.remove('flex');
                                iNama.style.setProperty('border-color', '#cbd5e1', 'important');
                                iNama.classList.remove('bg-red-50');
                            });
                            dropdown.appendChild(item);
                        });
                        dropdown.classList.remove('hidden');
                        dropdown.classList.add('flex');
                    } else {
                        dropdown.classList.add('hidden');
                        dropdown.classList.remove('flex');
                    }
                } else {
                    dropdown.classList.add('hidden');
                    dropdown.classList.remove('flex');
                }
            });

            document.addEventListener('click', function(e) {
                if (!iNama.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.classList.add('hidden');
                    dropdown.classList.remove('flex');
                }
            });
        }

        document.querySelectorAll('.btn-ajax-delete').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault(); 
                actionUrlToExecute = this.getAttribute('data-href');
                document.getElementById('confirmMessage').textContent = this.getAttribute('data-message');
                
                const cModal = document.getElementById('customConfirmModal');
                cModal.classList.remove('hidden');
                cModal.classList.add('flex');
                void cModal.offsetWidth; 
                document.getElementById('confirmBackdrop').classList.add('modal-backdrop-show');
                document.getElementById('confirmCard').classList.add('modal-card-show');
            });
        });

        document.querySelectorAll('.form-tambah').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                executeAjax(window.location.pathname, { method: 'POST', body: formData }, 'Berhasil Ditambahkan');
            });
        });

        const formCari = document.getElementById('formCari');
        if(formCari) {
            formCari.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                executeAjax(window.location.pathname, { method: 'POST', body: formData }, null);
            });
        }
        
        const btnResetSearch = document.getElementById('btnResetSearch');
        if(btnResetSearch){
            btnResetSearch.addEventListener('click', function(e){
                e.preventDefault();
                executeAjax(window.location.pathname, null, null);
            });
        }
        
        const formCheckout = document.getElementById('formCheckout');
        if(formCheckout) {
            formCheckout.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const checkFields = [iNama, iHP, iEmail];
                let isFormValid = true;
                
                checkFields.forEach(input => {
                    if(!input.value.trim()) {
                        isFormValid = false;
                        input.style.setProperty('border-color', '#ef4444', 'important'); 
                        input.classList.add('bg-red-50');
                    } else {
                        input.style.setProperty('border-color', '#cbd5e1', 'important'); 
                        input.classList.remove('bg-red-50');
                    }
                });

                checkFields.forEach(input => {
                    input.addEventListener('input', function() {
                         this.style.setProperty('border-color', '#cbd5e1', 'important');
                         this.classList.remove('bg-red-50');
                    }, {once: true});
                });

                if(!isFormValid) {
                    const btn = this.querySelector('button[type="submit"]');
                    btn.classList.remove('animate-shake');
                    void btn.offsetWidth;
                    btn.classList.add('animate-shake');

                    const wToast = document.getElementById('warningToast');
                    document.getElementById('warningToastMessage').innerText = 'Data Pelanggan (Nama, WA, Email) Wajib Diisi!';
                    wToast.classList.add('show');
                    setTimeout(() => { wToast.classList.remove('show'); }, 3000);
                    
                    return; 
                }

                const modal = document.getElementById('checkoutProcessModal');
                const card = document.getElementById('checkoutProcessCard');
                const stateLoading = document.getElementById('checkoutStateLoading');
                const stateSuccess = document.getElementById('checkoutStateSuccess');
                const progressBar = document.getElementById('checkoutProgressBar');
                
                stateLoading.classList.remove('hidden');
                stateSuccess.classList.add('hidden');
                stateSuccess.classList.remove('show-check-anim');
                progressBar.style.width = '0%';
                progressBar.style.transition = 'none';
                
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                void modal.offsetWidth; 
                card.classList.add('modal-card-show');
                document.getElementById('checkoutBackdrop').classList.add('modal-backdrop-show');
                
                setTimeout(() => {
                    progressBar.style.transition = 'width 1.5s cubic-bezier(0.1, 0.5, 0.2, 1)';
                    progressBar.style.width = '85%';
                }, 50);

                try {
                    const formData = new FormData(this);
                    
                    // PERBAIKAN: Bersihkan titik sebelum dikirim ke backend (proses_jual.php)
                    if(inOngkos) formData.set('total_ongkos', inOngkos.value.replace(/\./g, ''));
                    if(inDiskon) formData.set('diskon', inDiskon.value.replace(/\./g, ''));
                    
                    formData.append('ajax', '1');
                    
                    const response = await fetch('proses_jual.php', { method: 'POST', body: formData });
                    const data = await response.json();
                    
                    if(data.status === 'success') {
                        progressBar.style.transition = 'width 0.2s ease-out';
                        progressBar.style.width = '100%';
                        
                        setTimeout(() => {
                            stateLoading.classList.add('hidden');
                            stateSuccess.classList.remove('hidden');
                            stateSuccess.classList.add('show-check-anim');
                            
                            setTimeout(() => {
                                modal.classList.add('hidden');
                                modal.classList.remove('flex');
                                document.getElementById('printArea').innerHTML = data.receipt_html;
                                
                                const receiptModal = document.getElementById('receiptModal');
                                receiptModal.classList.remove('hidden');
                                receiptModal.classList.add('flex');
                            }, 2000);
                        }, 300);
                    } else {
                        alert(data.message);
                        modal.classList.add('hidden');
                        modal.classList.remove('flex');
                    }
                } catch(err) {
                    alert('Gagal memproses transaksi. Cek koneksi internet Anda.');
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }
            });
        }
    }

    let actionUrlToExecute = '';
    const miniLoader = document.getElementById('miniLoader');
    const toast = document.getElementById('successToast');

    async function executeAjax(url, options, successMsg) {
        miniLoader.classList.remove('hidden');
        miniLoader.classList.add('flex');
        void miniLoader.offsetWidth;
        miniLoader.style.opacity = '1';

        try {
            let htmlText = '';
            if (options && options.method === 'POST') {
                const res = await fetch(url, options);
                htmlText = await res.text();
            } else {
                await fetch(url);
                const res2 = await fetch(window.location.pathname);
                htmlText = await res2.text();
            }

            const parser = new DOMParser();
            const newDoc = parser.parseFromString(htmlText, 'text/html');

            document.getElementById('etalase-container').innerHTML = newDoc.getElementById('etalase-container').innerHTML;
            document.getElementById('keranjang-wrapper').innerHTML = newDoc.getElementById('keranjang-wrapper').innerHTML;

            initializeEvents();

            miniLoader.style.opacity = '0';
            setTimeout(() => {
                miniLoader.classList.add('hidden');
                miniLoader.classList.remove('flex');
            }, 300);

            if(successMsg) {
                document.getElementById('successToastMessage').innerText = successMsg;
                toast.classList.add('show');
                
                const svg = toast.querySelector('svg');
                svg.style.animation = 'none';
                void svg.offsetWidth;
                svg.style.animation = null;

                setTimeout(() => { toast.classList.remove('show'); }, 2000);
            }

        } catch (err) {
            console.error(err);
            alert("Koneksi gagal.");
            miniLoader.style.opacity = '0';
        }
    }

    function closeModal() {
        document.getElementById('confirmBackdrop').classList.remove('modal-backdrop-show');
        document.getElementById('confirmCard').classList.remove('modal-card-show');
        setTimeout(() => {
            const m = document.getElementById('customConfirmModal');
            m.classList.add('hidden');
            m.classList.remove('flex');
        }, 300);
    }
    document.getElementById('btnConfirmCancel').addEventListener('click', closeModal);
    document.getElementById('btnConfirmProceed').addEventListener('click', function() {
        closeModal();
        executeAjax(actionUrlToExecute, null, 'Berhasil Dihapus');
    });

    initializeEvents();

</script>

<?php include '../../layouts/footer.php'; ?>