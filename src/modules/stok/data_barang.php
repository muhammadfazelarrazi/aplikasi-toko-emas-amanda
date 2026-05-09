<?php 
session_start();
include '../../config/database.php'; 

// Cek Login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../modules/auth/login.php");
    exit;
}

// --- LOGIKA 1: TAMBAH STOK BARU (MERGE DARI INPUT_MASUK) ---
if (isset($_POST['tambah_stok'])) {
    $produkID = $_POST['produk_id'];
    $supplierID = $_POST['supplier_id'];
    $berat = $_POST['berat'];
    
    // PERBAIKAN: Bersihkan titik pemisah ribuan sebelum diconvert ke integer
    $hargaModalRaw = str_replace('.', '', $_POST['harga_modal'] ?? '0');
    $hargaModal = (int) $hargaModalRaw;
    
    $asal = 'Supplier'; // Default dari Supplier
    $tgl = date('Y-m-d');

    // Generate Kode Barang Otomatis (BRG-0000X)
    $queryKode = mysqli_query($koneksi, "SELECT MAX(KodeBarang) as max_kode FROM barang_stok");
    $dataKode = mysqli_fetch_array($queryKode);
    $kodeTerbesar = $dataKode['max_kode'];

    if ($kodeTerbesar) {
        $urutan = (int) substr($kodeTerbesar, 4, 5);
        $urutan++;
    } else {
        $urutan = 1;
    }
    
    $kodeBarangBaru = "BRG-" . sprintf("%05s", $urutan);

    $queryInsert = "INSERT INTO barang_stok 
                    (KodeBarang, ProdukKatalogID, SupplierID, BeratGram, HargaBeliModal, TanggalMasuk, Status, AsalBarang) 
                    VALUES ('$kodeBarangBaru', '$produkID', '$supplierID', '$berat', '$hargaModal', '$tgl', 'Tersedia', '$asal')";
    
    if (mysqli_query($koneksi, $queryInsert)) {
        $_SESSION['toast_success'] = "Stok baru berhasil ditambahkan!";
    } else {
        $_SESSION['toast_error'] = "Gagal menyimpan data stok.";
    }
    header("Location: data_barang.php");
    exit;
}

// --- LOGIKA 2: UPDATE STOK (EDIT) ---
if (isset($_POST['edit_stok'])) {
    $id = $_POST['id_barang'];
    $produk_id = $_POST['produk_id']; 
    $berat = $_POST['berat'];
    $status = $_POST['status'];
    
    $queryUpdate = "UPDATE barang_stok SET ProdukKatalogID='$produk_id', BeratGram='$berat', Status='$status' WHERE BarangID='$id'";
    
    if(mysqli_query($koneksi, $queryUpdate)){
        $_SESSION['toast_success'] = "Data stok berhasil diperbarui!";
    } else {
        $_SESSION['toast_error'] = "Gagal memperbarui data stok.";
    }
    header("Location: data_barang.php");
    exit;
}

// --- LOGIKA 3: HAPUS STOK ---
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $cekTrx = mysqli_query($koneksi, "SELECT * FROM detail_transaksi_barang WHERE BarangID='$id'");
    
    if(mysqli_num_rows($cekTrx) > 0) {
        $_SESSION['toast_error'] = "Akses Ditolak: Barang sudah memiliki riwayat transaksi.";
    } else {
        if(mysqli_query($koneksi, "DELETE FROM barang_stok WHERE BarangID='$id'")){
            $_SESSION['toast_success'] = "Data stok berhasil dihapus.";
        } else {
            $_SESSION['toast_error'] = "Gagal menghapus data stok.";
        }
    }
    header("Location: data_barang.php");
    exit;
}

include '../../layouts/header.php'; 
include '../../layouts/sidebar.php'; 

// --- PERSIAPAN DATA ---
$dataKatalog = [];
$qKat = mysqli_query($koneksi, "SELECT * FROM produk_katalog ORDER BY NamaProduk ASC");
while($k = mysqli_fetch_assoc($qKat)){
    $dataKatalog[] = $k;
}

$dataSupplier = [];
$qSup = mysqli_query($koneksi, "SELECT * FROM supplier ORDER BY NamaSupplier ASC");
while($s = mysqli_fetch_assoc($qSup)){
    $dataSupplier[] = $s;
}

$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : "";
$filter_kadar = isset($_GET['kadar']) ? $_GET['kadar'] : "";
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : "terbaru";

$whereClause = "WHERE 1=1"; 
if (!empty($search)) {
    $whereClause .= " AND (bs.KodeBarang LIKE '%$search%' OR pk.NamaProduk LIKE '%$search%')";
}
if (!empty($filter_kadar)) {
    $whereClause .= " AND pk.Kadar = '$filter_kadar'";
}

$orderClause = "";
switch ($sort_by) {
    case 'terlama': $orderClause = "ORDER BY bs.TanggalMasuk ASC, bs.BarangID ASC"; break;
    case 'berat_tinggi': $orderClause = "ORDER BY bs.BeratGram DESC"; break;
    case 'berat_rendah': $orderClause = "ORDER BY bs.BeratGram ASC"; break;
    case 'terbaru': default: $orderClause = "ORDER BY bs.TanggalMasuk DESC, bs.BarangID DESC"; break;
}

$query = "SELECT bs.*, pk.NamaProduk, pk.Kadar, pk.Tipe, sup.NamaSupplier 
          FROM barang_stok bs
          JOIN produk_katalog pk ON bs.ProdukKatalogID = pk.ProdukKatalogID
          LEFT JOIN supplier sup ON bs.SupplierID = sup.SupplierID
          $whereClause
          $orderClause";

$result = mysqli_query($koneksi, $query);

$qKadar = mysqli_query($koneksi, "SELECT DISTINCT Kadar FROM produk_katalog ORDER BY Kadar ASC");
?>

<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = { corePlugins: { preflight: false, visibility: false } }
</script>

<style>
    .collapse { visibility: visible !important; }
    .collapse:not(.show) { display: none !important; }
    .collapsing { visibility: visible !important; }
    a { text-decoration: none !important; }

    body { 
        font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", "Helvetica Neue", Helvetica, Arial, sans-serif;
        background-color: #f8fafc; color: #0f172a; -webkit-font-smoothing: antialiased; letter-spacing: -0.2px;
    }
    input, button, select {
        border: none !important; outline: none !important; box-shadow: none !important; background: transparent;
        -webkit-appearance: none; -moz-appearance: none; appearance: none;
    }
    input:focus, button:focus, select:focus { outline: none !important; box-shadow: none !important; }
    
    .table-scroll::-webkit-scrollbar { height: 6px; width: 6px; }
    .table-scroll::-webkit-scrollbar-track { background: transparent; }
    .table-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

    /* Modal Animation Classes */
    .modal-backdrop-show { opacity: 1 !important; }
    .modal-card-show { opacity: 1 !important; transform: scale(1) translateY(0) !important; }

    /* Custom Toast Animations */
    .custom-toast { transform: translate(-50%, -20px); opacity: 0; transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); }
    .custom-toast.show { transform: translate(-50%, 0); opacity: 1; }
</style>

<div id="toastSuccess" class="fixed top-6 left-1/2 z-[10001] flex items-center gap-3 px-4 py-3 bg-white/95 backdrop-blur-md shadow-lg border border-emerald-200 rounded-2xl custom-toast pointer-events-none">
    <div class="w-8 h-8 rounded-full bg-emerald-50 border border-emerald-100 flex items-center justify-center flex-shrink-0">
        <i class="bi bi-check-lg text-emerald-500 text-lg"></i>
    </div>
    <span id="toastSuccessMsg" class="text-[13px] font-bold text-slate-700 tracking-wide pr-2">Berhasil!</span>
</div>

<div id="toastError" class="fixed top-6 left-1/2 z-[10001] flex items-center gap-3 px-4 py-3 bg-white/95 backdrop-blur-md shadow-lg border border-red-200 rounded-2xl custom-toast pointer-events-none">
    <div class="w-8 h-8 rounded-full bg-red-50 border border-red-100 flex items-center justify-center flex-shrink-0">
        <i class="bi bi-exclamation-triangle-fill text-red-500 text-lg"></i>
    </div>
    <span id="toastErrorMsg" class="text-[13px] font-bold text-slate-700 tracking-wide pr-2">Gagal!</span>
</div>

<div id="customConfirmModal" class="fixed inset-0 z-[10000] hidden items-center justify-center">
    <div id="confirmBackdrop" class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm opacity-0 transition-opacity duration-300" onclick="closeConfirmModal()"></div>
    <div id="confirmCard" class="relative bg-white rounded-[24px] shadow-2xl w-11/12 max-w-xs p-6 flex flex-col items-center text-center transform scale-95 translate-y-4 opacity-0 transition-all duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)]">
        <div class="w-16 h-16 bg-red-50 text-red-500 rounded-full flex items-center justify-center mb-4 border-[6px] border-red-50 shadow-sm relative">
            <i class="bi bi-exclamation-triangle-fill text-2xl z-10"></i>
            <div class="absolute inset-0 rounded-full bg-red-500/20 animate-ping"></div>
        </div>
        <h3 class="text-[16px] font-extrabold text-slate-800 mb-1">Konfirmasi Tindakan</h3>
        <p class="text-[12px] font-medium text-slate-500 mb-6 leading-relaxed">Menghapus data ini dapat berdampak pada riwayat transaksi jika barang terkait sudah terjual. Yakin ingin melanjutkan?</p>
        <div class="flex w-full gap-3">
            <button type="button" onclick="closeConfirmModal()" class="flex-1 py-2.5 rounded-xl font-bold text-[13px] text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors cursor-pointer">Batal</button>
            <a href="#" id="btnConfirmProceed" class="flex-1 py-2.5 rounded-xl font-bold text-[13px] text-white bg-red-500 hover:bg-red-600 shadow-md shadow-red-500/30 transition-all active:scale-95 cursor-pointer flex items-center justify-center">Ya, Hapus</a>
        </div>
    </div>
</div>

<div id="modalAddStok" class="fixed inset-0 z-[1000] hidden items-center justify-center">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onclick="closeAddModal()"></div>
    
    <div class="relative bg-white rounded-[24px] shadow-2xl w-11/12 max-w-lg p-6 lg:p-8 z-10 transform scale-95 transition-all duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)]">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h3 class="text-[18px] font-extrabold text-slate-800 leading-tight flex items-center gap-2">
                    <i class="bi bi-box-seam text-[#3b82f6]"></i> Tambah Stok Masuk
                </h3>
                <p class="text-[12px] font-semibold text-slate-500 mb-0 mt-1">Kode Barang akan digenerate otomatis.</p>
            </div>
            <button type="button" onclick="closeAddModal()" class="w-8 h-8 bg-slate-50 hover:bg-red-50 text-slate-500 hover:text-red-500 rounded-full flex items-center justify-center transition-colors">
                <i class="bi bi-x-lg font-bold pointer-events-none"></i>
            </button>
        </div>

        <form method="POST" action="">
            <div class="mb-4">
                <label class="block text-[11px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">Jenis Produk (Katalog)</label>
                <div class="relative">
                    <select name="produk_id" class="w-full pl-3 pr-8 py-2.5 bg-slate-50 rounded-xl text-[13px] font-bold text-slate-700 focus:ring-2 focus:ring-[#3b82f6]/30 transition-all appearance-none cursor-pointer" style="border: 1px solid #cbd5e1 !important;" required>
                        <option value="">-- Pilih Produk --</option>
                        <?php foreach($dataKatalog as $kat): ?>
                            <option value="<?php echo $kat['ProdukKatalogID']; ?>">
                                <?php echo $kat['NamaProduk']; ?> (Kadar <?php echo $kat['Kadar']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-[11px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">Supplier / Pemasok</label>
                <div class="relative">
                    <select name="supplier_id" class="w-full pl-3 pr-8 py-2.5 bg-slate-50 rounded-xl text-[13px] font-bold text-slate-700 focus:ring-2 focus:ring-[#3b82f6]/30 transition-all appearance-none cursor-pointer" style="border: 1px solid #cbd5e1 !important;" required>
                        <option value="">-- Pilih Supplier --</option>
                        <?php foreach($dataSupplier as $sup): ?>
                            <option value="<?php echo $sup['SupplierID']; ?>"><?php echo $sup['NamaSupplier']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-8">
                <div class="col-span-1">
                    <label class="block text-[11px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">Berat (Gram)</label>
                    <div class="flex items-center bg-slate-50 rounded-xl focus-within:ring-2 focus-within:ring-[#3b82f6]/30 transition-all" style="border: 1px solid #cbd5e1 !important;">
                        <input type="number" step="0.01" name="berat" placeholder="0.00" class="w-full pl-3 py-2.5 bg-transparent text-[14px] font-black text-slate-800" required>
                        <span class="pr-3 text-[12px] font-bold text-slate-400">gr</span>
                    </div>
                </div>
                <div class="col-span-1">
                    <label class="block text-[11px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">Harga Beli / Modal</label>
                    <div class="flex items-center bg-slate-50 rounded-xl focus-within:ring-2 focus-within:ring-[#3b82f6]/30 transition-all" style="border: 1px solid #cbd5e1 !important;">
                        <span class="pl-3 text-[12px] font-bold text-slate-400">Rp</span>
                        <input type="text" inputmode="numeric" id="inputHargaModal" name="harga_modal" placeholder="0" class="w-full px-2 py-2.5 bg-transparent text-[14px] font-black text-slate-800" required autocomplete="off">
                    </div>
                </div>
            </div>

            <div class="flex gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeAddModal()" class="flex-1 py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl font-bold text-[13px] transition-colors cursor-pointer">Batal</button>
                <button type="submit" name="tambah_stok" class="flex-1 py-3 bg-[#3b82f6] hover:bg-[#2563eb] text-white rounded-xl font-bold text-[13px] transition-colors shadow-md flex items-center justify-center gap-2 cursor-pointer">
                    <i class="bi bi-save-fill"></i> Simpan Stok
                </button>
            </div>
        </form>
    </div>
</div>


<div class="main-content" style="padding: 32px 40px 16px 40px;">
    
    <div class="mb-4">
        <h2 class="text-[1.75rem] font-bold text-slate-800 mb-1 flex items-center gap-2" style="letter-spacing: -0.5px;">
            <i class="bi bi-box-seam-fill text-[#3b82f6]"></i> Data Stok Emas
        </h2>
        <p class="text-[0.95rem] font-medium text-slate-500 mb-0">Kelola persediaan fisik barang, sumber perolehan, dan status ketersediaan di toko.</p>
    </div>

    <div class="w-full flex flex-wrap items-center justify-between gap-4 mb-6">
        
        <form action="" method="GET" class="flex flex-wrap items-center gap-3 m-0 w-full lg:w-auto">
            
            <div class="flex items-center bg-white rounded-xl shadow-sm overflow-hidden" style="border: 1px solid #cbd5e1 !important;">
                <input type="text" name="search" class="pl-4 pr-2 py-2.5 w-full sm:w-[220px] lg:w-[260px] text-[13px] font-semibold text-slate-700 bg-transparent focus:ring-2 focus:ring-[#3b82f6]/30 transition-all" placeholder="Cari Kode / Nama Produk..." value="<?php echo htmlspecialchars($search); ?>" autocomplete="off">
                <button type="submit" class="px-4 py-2.5 bg-slate-50 border-l border-slate-200 text-slate-500 hover:text-[#3b82f6] hover:bg-blue-50 transition-colors cursor-pointer" title="Cari Data">
                    <i class="bi bi-search font-bold"></i>
                </button>
            </div>

            <div class="relative bg-white rounded-xl shadow-sm" style="border: 1px solid #cbd5e1 !important;">
                <select name="kadar" class="pl-4 pr-8 py-2.5 w-[140px] text-[13px] font-semibold text-slate-600 bg-transparent focus:ring-2 focus:ring-[#3b82f6]/30 rounded-xl transition-all cursor-pointer appearance-none" onchange="this.form.submit()">
                    <option value="">Semua Kadar</option>
                    <?php while($k = mysqli_fetch_assoc($qKadar)): ?>
                        <option value="<?php echo $k['Kadar']; ?>" <?php if($filter_kadar == $k['Kadar']) echo 'selected'; ?>>Kadar <?php echo $k['Kadar']; ?></option>
                    <?php endwhile; ?>
                </select>
                <i class="bi bi-funnel absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400 pointer-events-none"></i>
            </div>

            <div class="relative bg-white rounded-xl shadow-sm" style="border: 1px solid #cbd5e1 !important;">
                <select name="sort" class="pl-4 pr-8 py-2.5 w-[170px] text-[13px] font-semibold text-slate-600 bg-transparent focus:ring-2 focus:ring-[#3b82f6]/30 rounded-xl transition-all cursor-pointer appearance-none" onchange="this.form.submit()">
                    <option value="terbaru" <?php if($sort_by == 'terbaru') echo 'selected'; ?>>Paling Baru</option>
                    <option value="terlama" <?php if($sort_by == 'terlama') echo 'selected'; ?>>Paling Lama</option>
                    <option value="berat_tinggi" <?php if($sort_by == 'berat_tinggi') echo 'selected'; ?>>Paling Berat</option>
                    <option value="berat_rendah" <?php if($sort_by == 'berat_rendah') echo 'selected'; ?>>Paling Ringan</option>
                </select>
                <i class="bi bi-sort-down absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400 pointer-events-none"></i>
            </div>

            <?php if($search != '' || $filter_kadar != '' || $sort_by != 'terbaru'): ?>
                <a href="data_barang.php" class="flex items-center justify-center w-[42px] h-[42px] bg-red-50 text-red-500 rounded-xl shadow-sm hover:bg-red-500 hover:text-white transition-all cursor-pointer" title="Reset Filter" style="border: 1px solid #fecaca !important;">
                    <i class="bi bi-arrow-counterclockwise text-lg"></i>
                </a>
            <?php endif; ?>
        </form>

        <button type="button" onclick="openAddModal()" class="flex items-center justify-center gap-2 bg-[#3b82f6] hover:bg-[#2563eb] text-white px-6 py-2.5 rounded-xl font-bold text-[13px] transition-colors shadow-md w-full lg:w-auto cursor-pointer">
            <i class="bi bi-plus-lg"></i> Tambah Stok Baru
        </button>
    </div>

    <div class="bg-white rounded-[20px] shadow-sm border border-solid border-slate-100 overflow-hidden" style="box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);">
        <div class="overflow-x-auto table-scroll">
            <table class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="py-4 px-5 text-[12px] font-extrabold text-slate-500 uppercase tracking-wider">Kode Barang</th>
                        <th class="py-4 px-4 text-[12px] font-extrabold text-slate-500 uppercase tracking-wider">Spesifikasi Produk</th>
                        <th class="py-4 px-4 text-[12px] font-extrabold text-slate-500 uppercase tracking-wider">Kadar Emas</th>
                        <th class="py-4 px-4 text-[12px] font-extrabold text-slate-500 uppercase tracking-wider">Berat Aktual</th>
                        <th class="py-4 px-4 text-[12px] font-extrabold text-slate-500 uppercase tracking-wider">Sumber Barang</th>
                        <th class="py-4 px-4 text-[12px] font-extrabold text-slate-500 uppercase tracking-wider">Tanggal Masuk</th>
                        <th class="py-4 px-4 text-[12px] font-extrabold text-slate-500 uppercase tracking-wider">Status Stok</th>
                        <th class="py-4 px-5 text-[12px] font-extrabold text-slate-500 uppercase tracking-wider text-right">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if(mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr class="hover:bg-slate-50/50 transition-colors group">
                            <td class="py-4 px-5">
                                <div class="flex items-center gap-2">
                                    <i class="bi bi-upc-scan text-slate-400"></i>
                                    <span class="text-[14px] font-black text-[#3b82f6]"><?php echo $row['KodeBarang']; ?></span>
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex flex-col gap-0.5">
                                    <span class="text-[14px] font-bold text-slate-800"><?php echo $row['NamaProduk']; ?></span>
                                    <span class="text-[12px] font-semibold text-slate-400"><?php echo $row['Tipe']; ?></span>
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                <span class="text-[11px] font-bold bg-amber-50 text-amber-600 px-2.5 py-1 rounded-md border border-amber-200">
                                    <?php echo $row['Kadar']; ?>
                                </span>
                            </td>
                            <td class="py-4 px-4">
                                <span class="text-[15px] font-black text-slate-800"><?php echo $row['BeratGram']; ?>g</span>
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex flex-col">
                                    <span class="text-[13px] font-bold text-slate-700"><?php echo $row['AsalBarang']; ?></span>
                                    <?php if($row['AsalBarang'] == 'Supplier' && !empty($row['NamaSupplier'])): ?>
                                        <span class="text-[11px] font-semibold text-[#3b82f6]"><?php echo $row['NamaSupplier']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                <span class="text-[13px] font-semibold text-slate-500"><?php echo date('d M Y', strtotime($row['TanggalMasuk'])); ?></span>
                            </td>
                            <td class="py-4 px-4">
                                <?php if($row['Status'] == 'Tersedia'): ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-200">
                                        <i class="bi bi-check-circle-fill"></i> Tersedia
                                    </span>
                                <?php elseif($row['Status'] == 'Terjual'): ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-bold bg-rose-50 text-rose-600 border border-rose-200">
                                        <i class="bi bi-cart-x-fill"></i> Terjual
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-bold bg-amber-50 text-amber-600 border border-amber-200">
                                        <i class="bi bi-arrow-return-left"></i> Buyback
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-5 text-right">
                                <div class="flex gap-2 justify-end opacity-70 group-hover:opacity-100 transition-opacity">
                                    
                                    <button type="button" onclick="openEditModal('modalEdit<?php echo $row['BarangID']; ?>')" class="bg-white hover:bg-blue-50 text-slate-500 hover:text-[#3b82f6] w-8 h-8 rounded-lg text-[13px] font-bold transition-colors shadow-sm flex items-center justify-center border border-slate-200 hover:border-blue-300" title="Ubah Data Stok">
                                        <i class="bi bi-pencil-fill pointer-events-none"></i>
                                    </button>

                                    <button type="button" onclick="openConfirmModal('?hapus=<?php echo $row['BarangID']; ?>')" class="bg-white hover:bg-red-50 text-slate-500 hover:text-red-500 w-8 h-8 rounded-lg text-[13px] font-bold transition-colors shadow-sm flex items-center justify-center border border-slate-200 hover:border-red-300" title="Hapus Data Stok">
                                        <i class="bi bi-trash3-fill pointer-events-none"></i>
                                    </button>

                                </div>
                            </td>
                        </tr>

                        <div id="modalEdit<?php echo $row['BarangID']; ?>" class="fixed inset-0 z-[1000] hidden items-center justify-center">
                            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onclick="closeEditModal('modalEdit<?php echo $row['BarangID']; ?>')"></div>
                            
                            <div class="relative bg-white rounded-[24px] shadow-2xl w-11/12 max-w-md p-6 lg:p-8 z-10 transform scale-95 transition-all duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)]">
                                <div class="flex justify-between items-center mb-6">
                                    <div>
                                        <h3 class="text-[18px] font-extrabold text-slate-800 leading-tight">Ubah Data Stok</h3>
                                        <p class="text-[12px] font-semibold text-slate-500 mb-0">Kode Barang: <span class="text-[#3b82f6]"><?php echo $row['KodeBarang']; ?></span></p>
                                    </div>
                                    <button type="button" onclick="closeEditModal('modalEdit<?php echo $row['BarangID']; ?>')" class="w-8 h-8 bg-slate-50 hover:bg-red-50 text-slate-500 hover:text-red-500 rounded-full flex items-center justify-center transition-colors">
                                        <i class="bi bi-x-lg font-bold pointer-events-none"></i>
                                    </button>
                                </div>

                                <form method="POST" action="">
                                    <input type="hidden" name="id_barang" value="<?php echo $row['BarangID']; ?>">
                                    
                                    <div class="mb-4">
                                        <label class="block text-[11px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">Spesifikasi Katalog</label>
                                        <div class="relative">
                                            <select name="produk_id" class="w-full pl-3 pr-8 py-2.5 bg-slate-50 rounded-xl text-[13px] font-bold text-slate-700 focus:ring-2 focus:ring-[#3b82f6]/30 transition-all appearance-none cursor-pointer" style="border: 1px solid #cbd5e1 !important;" required>
                                                <?php foreach($dataKatalog as $kat): ?>
                                                    <option value="<?php echo $kat['ProdukKatalogID']; ?>" <?php if($kat['ProdukKatalogID'] == $row['ProdukKatalogID']) echo 'selected'; ?>>
                                                        <?php echo $kat['NamaProduk']; ?> (Kadar <?php echo $kat['Kadar']; ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4 mb-8">
                                        <div class="col-span-1">
                                            <label class="block text-[11px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">Berat Aktual (Gram)</label>
                                            <input type="number" step="0.01" name="berat" value="<?php echo $row['BeratGram']; ?>" class="w-full px-3 py-2.5 bg-slate-50 rounded-xl text-[14px] font-black text-slate-800 focus:ring-2 focus:ring-[#3b82f6]/30 transition-all" style="border: 1px solid #cbd5e1 !important;" required>
                                        </div>
                                        <div class="col-span-1">
                                            <label class="block text-[11px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">Status Stok</label>
                                            <div class="relative">
                                                <select name="status" class="w-full pl-3 pr-8 py-2.5 bg-slate-50 rounded-xl text-[13px] font-bold text-slate-700 focus:ring-2 focus:ring-[#3b82f6]/30 transition-all appearance-none cursor-pointer" style="border: 1px solid #cbd5e1 !important;" required>
                                                    <option value="Tersedia" <?php if($row['Status']=='Tersedia') echo 'selected'; ?>>Tersedia</option>
                                                    <option value="Terjual" <?php if($row['Status']=='Terjual') echo 'selected'; ?>>Terjual</option>
                                                    <option value="Buyback" <?php if($row['Status']=='Buyback') echo 'selected'; ?>>Buyback</option>
                                                </select>
                                                <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex gap-3 pt-4 border-t border-slate-100">
                                        <button type="button" onclick="closeEditModal('modalEdit<?php echo $row['BarangID']; ?>')" class="flex-1 py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl font-bold text-[13px] transition-colors">Batal</button>
                                        <button type="submit" name="edit_stok" class="flex-1 py-3 bg-[#3b82f6] hover:bg-[#2563eb] text-white rounded-xl font-bold text-[13px] transition-colors shadow-md flex items-center justify-center gap-2">
                                            <i class="bi bi-check-circle"></i> Simpan
                                        </button>
                                    </div>
                                </form>

                            </div>
                        </div>

                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="py-16 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <i class="bi bi-inbox text-5xl mb-4 opacity-20"></i>
                                    <h6 class="text-[16px] font-bold text-slate-500 mb-1">Data stok kosong.</h6>
                                    <p class="text-[13px] font-medium mb-0">Coba ubah filter pencarian atau tambahkan stok emas baru.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
    // --- FORMAT RIBUAN LIVE UNTUK HARGA MODAL ---
    const inputHargaModal = document.getElementById('inputHargaModal');
    if(inputHargaModal) {
        inputHargaModal.addEventListener('input', function(e) {
            let value = this.value.replace(/[^0-9]/g, '');
            if (value !== '') {
                this.value = new Intl.NumberFormat('id-ID').format(value);
            } else {
                this.value = '';
            }
        });
    }

    // --- LOGIKA MODAL TAMBAH STOK BARU ---
    function openAddModal() {
        const modal = document.getElementById('modalAddStok');
        if(modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => {
                modal.children[1].classList.remove('scale-95', 'opacity-0');
                modal.children[1].classList.add('scale-100', 'opacity-100');
                modal.children[0].classList.remove('opacity-0');
            }, 10);
        }
    }

    function closeAddModal() {
        const modal = document.getElementById('modalAddStok');
        if(modal) {
            modal.children[1].classList.remove('scale-100', 'opacity-100');
            modal.children[1].classList.add('scale-95', 'opacity-0');
            modal.children[0].classList.add('opacity-0');
            setTimeout(() => {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
            }, 300); 
        }
    }

    // --- LOGIKA MODAL EDIT ---
    function openEditModal(modalID) {
        const modal = document.getElementById(modalID);
        if(modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => {
                modal.children[1].classList.remove('scale-95', 'opacity-0');
                modal.children[1].classList.add('scale-100', 'opacity-100');
                modal.children[0].classList.remove('opacity-0');
            }, 10);
        }
    }

    function closeEditModal(modalID) {
        const modal = document.getElementById(modalID);
        if(modal) {
            modal.children[1].classList.remove('scale-100', 'opacity-100');
            modal.children[1].classList.add('scale-95', 'opacity-0');
            modal.children[0].classList.add('opacity-0');
            setTimeout(() => {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
            }, 300); 
        }
    }

    // --- LOGIKA MODAL CONFIRM DELETE ---
    function openConfirmModal(deleteUrl) {
        const modal = document.getElementById('customConfirmModal');
        const btnProceed = document.getElementById('btnConfirmProceed');
        
        btnProceed.href = deleteUrl;
        
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => {
            document.getElementById('confirmBackdrop').classList.add('modal-backdrop-show');
            document.getElementById('confirmCard').classList.add('modal-card-show');
        }, 10);
    }

    function closeConfirmModal() {
        document.getElementById('confirmBackdrop').classList.remove('modal-backdrop-show');
        document.getElementById('confirmCard').classList.remove('modal-card-show');
        setTimeout(() => {
            const modal = document.getElementById('customConfirmModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 300);
    }

    // --- LOGIKA FLASH MESSAGE TOAST ---
    window.addEventListener('DOMContentLoaded', () => {
        <?php if(isset($_SESSION['toast_success'])): ?>
            const tSuccess = document.getElementById('toastSuccess');
            document.getElementById('toastSuccessMsg').innerText = "<?php echo $_SESSION['toast_success']; ?>";
            tSuccess.classList.add('show');
            setTimeout(() => { tSuccess.classList.remove('show'); }, 3000);
            <?php unset($_SESSION['toast_success']); ?>
        <?php endif; ?>

        <?php if(isset($_SESSION['toast_error'])): ?>
            const tError = document.getElementById('toastError');
            document.getElementById('toastErrorMsg').innerText = "<?php echo $_SESSION['toast_error']; ?>";
            tError.classList.add('show');
            setTimeout(() => { tError.classList.remove('show'); }, 3500);
            <?php unset($_SESSION['toast_error']); ?>
        <?php endif; ?>
    });
</script>

<?php include '../../layouts/footer.php'; ?>