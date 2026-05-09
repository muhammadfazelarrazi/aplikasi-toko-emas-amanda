<?php 
session_start();
include '../../config/database.php'; 

$pesanErrorForm = ""; 

// --- PROSES SIMPAN BUYBACK ---
if (isset($_POST['simpan_buyback'])) {
    $pelangganID = $_POST['pelanggan_id'] ?? ''; 
    $kasirID     = $_SESSION['user_id'] ?? 1; 
    $kodeBarang  = $_POST['kode_barang'] ?? ''; 
    $produkID    = $_POST['produk_id'] ?? '';
    $tgl         = date('Y-m-d H:i:s');

    $beratRaw    = str_replace(',', '.', $_POST['berat_sekarang'] ?? '0');
    $berat       = (float) $beratRaw;
    
    // PERBAIKAN: Bersihkan titik pemisah ribuan sebelum diconvert ke integer
    $hargaDealRaw = str_replace('.', '', $_POST['harga_deal'] ?? '0');
    $hargaDeal   = (int) $hargaDealRaw; 

    mysqli_begin_transaction($koneksi);

    try {
        // Validasi Anti-Tembus
        if(empty($pelangganID) || empty($produkID) || empty($kodeBarang)) {
            throw new Exception("Data Pelanggan atau Barang tidak boleh kosong! Pastikan Anda memilih dari daftar saran yang muncul.");
        }
        if($berat <= 0 || $hargaDeal <= 0) {
            throw new Exception("Berat dan Harga Deal harus lebih dari 0!");
        }

        // 1. Simpan Transaksi
        $qHead = "INSERT INTO transaksi (PelangganID, KaryawanID, TanggalWaktu, TipeTransaksi, TotalTransaksi) 
                  VALUES ('$pelangganID', '$kasirID', '$tgl', 'Buyback', '$hargaDeal')";
        if(!mysqli_query($koneksi, $qHead)) throw new Exception("Tabel Transaksi: " . mysqli_error($koneksi));
        $trxID = mysqli_insert_id($koneksi);

        // 2. Cek KodeBarang
        $cekStok = mysqli_query($koneksi, "SELECT BarangID FROM barang_stok WHERE KodeBarang = '$kodeBarang'");
        if (mysqli_num_rows($cekStok) > 0) {
            $rowStok = mysqli_fetch_assoc($cekStok);
            $barangID = $rowStok['BarangID'];
            
            $qStok = "UPDATE barang_stok 
                      SET BeratGram = '$berat', HargaBeliModal = '$hargaDeal', TanggalMasuk = CURDATE(), 
                          Status = 'Tersedia', AsalBarang = 'Buyback' 
                      WHERE BarangID = '$barangID'";
            if(!mysqli_query($koneksi, $qStok)) throw new Exception("Gagal Update Stok: " . mysqli_error($koneksi));
        } else {
            $qStok = "INSERT INTO barang_stok (KodeBarang, ProdukKatalogID, BeratGram, HargaBeliModal, TanggalMasuk, Status, AsalBarang)
                      VALUES ('$kodeBarang', '$produkID', '$berat', '$hargaDeal', CURDATE(), 'Tersedia', 'Buyback')";
            if(!mysqli_query($koneksi, $qStok)) throw new Exception("Gagal Insert Stok Baru: " . mysqli_error($koneksi));
            $barangID = mysqli_insert_id($koneksi);
        }

        // 3. Simpan Detail
        $qDetail = "INSERT INTO detail_transaksi_barang (TransaksiID, BarangID, HargaSatuanSaatItu) 
                    VALUES ('$trxID', '$barangID', '$hargaDeal')";
        if(!mysqli_query($koneksi, $qDetail)) throw new Exception("Tabel Detail: " . mysqli_error($koneksi));

        // 4. Simpan Pembayaran
        $qBayar = "INSERT INTO pembayaran (TransaksiID, MetodeID, JumlahBayar) 
                   VALUES ('$trxID', 1, '$hargaDeal')";
        if(!mysqli_query($koneksi, $qBayar)) throw new Exception("Tabel Pembayaran: " . mysqli_error($koneksi));

        mysqli_commit($koneksi);
        header("Location: riwayat.php");
        exit;

    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        $pesanErrorForm = $e->getMessage();
    }
}

// --- AMBIL DATA PELANGGAN & KATALOG UNTUK JAVASCRIPT ---
$dataPelanggan = [];
$qPel = mysqli_query($koneksi, "SELECT * FROM pelanggan ORDER BY NamaPelanggan ASC");
while($p = mysqli_fetch_assoc($qPel)){ $dataPelanggan[] = $p; }

$dataKatalog = [];
$qKat = mysqli_query($koneksi, "SELECT * FROM produk_katalog ORDER BY NamaProduk ASC");
while($k = mysqli_fetch_assoc($qKat)){ $dataKatalog[] = $k; }


// --- LOGIKA AUTO-FILL DARI URL (?id=...) ---
$def_pelanggan = '';
$def_pelanggan_nama = '';
$def_produk    = '';
$def_produk_nama = '';
$def_kode      = '';
$def_berat     = '';
$def_kadar     = '';
$def_harga_beli_awal = 0;
$def_harga_buyback_gram = 0;

$is_autofill   = false;
$trx_id_url    = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($trx_id_url > 0) {
    $qAuto = mysqli_query($koneksi, "
        SELECT t.PelangganID, p.NamaPelanggan, b.ProdukKatalogID, pk.NamaProduk, pk.Kadar, b.KodeBarang, b.BeratGram, dt.HargaSatuanSaatItu,
               (SELECT HargaBeliPerGram FROM riwayat_harga WHERE Kadar = pk.Kadar ORDER BY Tanggal DESC LIMIT 1) as HargaBuybackSekarang
        FROM transaksi t
        JOIN pelanggan p ON t.PelangganID = p.PelangganID
        JOIN detail_transaksi_barang dt ON t.TransaksiID = dt.TransaksiID
        JOIN barang_stok b ON dt.BarangID = b.BarangID
        JOIN produk_katalog pk ON b.ProdukKatalogID = pk.ProdukKatalogID
        WHERE t.TransaksiID = '$trx_id_url' AND b.Status = 'Terjual'
        LIMIT 1
    ");

    if ($rowAuto = mysqli_fetch_assoc($qAuto)) {
        $def_pelanggan = $rowAuto['PelangganID'];
        $def_pelanggan_nama = $rowAuto['NamaPelanggan'];
        $def_produk    = $rowAuto['ProdukKatalogID'];
        $def_produk_nama = $rowAuto['NamaProduk'] . " (Kadar " . $rowAuto['Kadar'] . ")";
        $def_kode      = $rowAuto['KodeBarang'];
        $def_berat     = $rowAuto['BeratGram'];
        $def_kadar     = $rowAuto['Kadar'];
        $def_harga_beli_awal = $rowAuto['HargaSatuanSaatItu'];
        $def_harga_buyback_gram = $rowAuto['HargaBuybackSekarang'] ?? 0;
        $is_autofill   = true;
    }
}

include '../../layouts/header.php'; 
include '../../layouts/sidebar.php'; 
?>

<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = { corePlugins: { preflight: false, visibility: false } }
</script>

<style>
    /* Fix Sidebar Dropdown Bootstrap */
    .collapse { visibility: visible !important; }
    .collapse:not(.show) { display: none !important; }
    .collapsing { visibility: visible !important; }

    body { 
        font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", "Helvetica Neue", Helvetica, Arial, sans-serif;
        background-color: #f8fafc; 
        color: #0f172a;
        -webkit-font-smoothing: antialiased;
        letter-spacing: -0.2px;
    }

    input, button, select, textarea {
        border: none !important; outline: none !important; box-shadow: none !important; background: transparent;
        -webkit-appearance: none; -moz-appearance: none; appearance: none;
    }
    input:focus, button:focus, select:focus { outline: none !important; box-shadow: none !important; }
    input[type=number]::-webkit-inner-spin-button, input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }

    .custom-scroll::-webkit-scrollbar { width: 5px; height: 5px; }
    .custom-scroll::-webkit-scrollbar-track { background: transparent; }
    .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

    .warning-toast { transform: translate(-50%, -20px); opacity: 0; transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); }
    .warning-toast.show { transform: translate(-50%, 0); opacity: 1; }

    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        20%, 60% { transform: translateX(-5px); }
        40%, 80% { transform: translateX(5px); }
    }
    .animate-shake { animation: shake 0.4s ease-in-out; }
</style>

<div id="warningToast" class="fixed top-6 left-1/2 z-[10001] flex items-center gap-3 px-4 py-3 bg-white/95 backdrop-blur-md shadow-lg border border-red-200 rounded-2xl warning-toast pointer-events-none">
    <div class="w-8 h-8 rounded-full bg-red-50 border border-red-100 flex items-center justify-center flex-shrink-0">
        <i class="bi bi-exclamation-triangle-fill text-red-500 text-lg"></i>
    </div>
    <span id="warningToastMessage" class="text-sm font-bold text-slate-700 tracking-wide pr-2">Mohon lengkapi data!</span>
</div>

<div id="miniLoader" class="fixed top-6 left-1/2 transform -translate-x-1/2 -translate-y-4 opacity-0 z-[10000] flex items-center gap-3 px-4 py-2 bg-white/90 backdrop-blur-md shadow-md border border-slate-200 rounded-full transition-all duration-300 pointer-events-none hidden">
    <div class="animate-spin rounded-full h-4 w-4 border-2 border-slate-200 border-t-red-500"></div>
    <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Memproses...</span>
</div>

<div class="main-content" style="padding: 32px 40px 16px 40px;">
    
    <div class="mb-6 flex-shrink-0 flex items-center justify-between">
        <div>
            <h2 class="text-[1.75rem] font-bold text-slate-800 mb-1 flex items-center gap-2" style="letter-spacing: -0.5px;">
                <i class="bi bi-arrow-down-left-square-fill text-red-500"></i> Transaksi Buyback
            </h2>
            <p class="text-[0.95rem] font-medium text-slate-500 mb-0">Proses pembelian kembali emas dari pelanggan ke toko.</p>
        </div>
        <div class="hidden sm:flex">
            <span class="text-sm font-semibold text-slate-500 bg-white px-4 py-2 rounded-xl border border-solid border-slate-200 shadow-sm flex items-center gap-2">
                <i class="bi bi-calendar-event text-red-500"></i> <?php echo date('d M Y'); ?>
            </span>
        </div>
    </div>

    <?php if(!empty($pesanErrorForm)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-2xl mb-6 shadow-sm flex items-start gap-3">
            <i class="bi bi-exclamation-triangle-fill text-xl mt-0.5"></i>
            <div>
                <h4 class="font-bold text-[14px] mb-1">Gagal Menyimpan Transaksi!</h4>
                <p class="text-[12px] font-medium mb-0 opacity-90"><?php echo $pesanErrorForm; ?>. Silakan periksa kembali data.</p>
            </div>
        </div>
    <?php endif; ?>

    <form id="formBuyback" method="POST" action="" class="m-0" novalidate>
        <div class="flex flex-col lg:flex-row gap-6 items-start">
            
            <div class="w-full lg:flex-1 bg-white rounded-[24px] shadow-sm border border-solid border-slate-100 p-6 lg:p-8" style="box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);">
                
                <h3 class="text-[14px] font-extrabold text-slate-800 mb-6 flex items-center gap-2 border-b border-slate-100 pb-3">
                    <i class="bi bi-card-checklist text-red-500 text-lg"></i> Rincian Emas & Pelanggan
                </h3>

                <div class="flex flex-col gap-5">
                    
                    <div class="relative">
                        <label class="block text-[11px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">
                            Pelanggan <?php if($is_autofill) echo '<i class="bi bi-lock-fill text-slate-400 ms-1" title="Terkunci"></i>'; ?>
                        </label>
                        <input type="hidden" name="pelanggan_id" id="valPelangganID" value="<?php echo $def_pelanggan; ?>">
                        
                        <div class="relative">
                            <i class="bi bi-search absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400"></i>
                            <input type="text" id="inputPelanggan" class="w-full pl-10 pr-4 py-2.5 rounded-xl text-[13px] font-bold transition-all shadow-sm <?php echo $is_autofill ? 'bg-slate-50 text-slate-400' : 'bg-white text-slate-700 focus:ring-2 focus:ring-red-500/30'; ?>" style="border: 1px solid #cbd5e1 !important;" placeholder="Cari data pelanggan..." value="<?php echo htmlspecialchars($def_pelanggan_nama); ?>" <?php echo $is_autofill ? 'readonly tabindex="-1"' : 'required autocomplete="off"'; ?>>
                        </div>
                        
                        <div id="dropdownPelanggan" class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-48 overflow-y-auto hidden flex-col custom-scroll"></div>
                    </div>

                    <div class="relative">
                        <label class="block text-[11px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">
                            Jenis Katalog <?php if($is_autofill) echo '<i class="bi bi-lock-fill text-slate-400 ms-1" title="Terkunci"></i>'; ?>
                        </label>
                        <input type="hidden" name="produk_id" id="valKatalogID" value="<?php echo $def_produk; ?>">
                        
                        <div class="relative">
                            <i class="bi bi-search absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400"></i>
                            <input type="text" id="inputKatalog" class="w-full pl-10 pr-4 py-2.5 rounded-xl text-[13px] font-bold transition-all shadow-sm <?php echo $is_autofill ? 'bg-slate-50 text-slate-400' : 'bg-white text-slate-700 focus:ring-2 focus:ring-red-500/30'; ?>" style="border: 1px solid #cbd5e1 !important;" placeholder="Cari katalog emas..." value="<?php echo htmlspecialchars($def_produk_nama); ?>" <?php echo $is_autofill ? 'readonly tabindex="-1"' : 'required autocomplete="off"'; ?>>
                        </div>
                        
                        <div id="dropdownKatalog" class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-48 overflow-y-auto hidden flex-col custom-scroll"></div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-1">
                        
                        <div class="col-span-1 <?php echo !$is_autofill ? 'md:col-span-2' : ''; ?>">
                            <label class="block text-[11px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">
                                Kode Fisik / Barcode <?php if($is_autofill) echo '<i class="bi bi-lock-fill text-slate-400 ms-1" title="Terkunci"></i>'; ?>
                            </label>
                            <div class="relative">
                                <i class="bi bi-upc-scan absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400 text-lg"></i>
                                <input type="text" name="kode_barang" id="inputKode" class="w-full pl-10 pr-4 py-2.5 rounded-xl text-[14px] font-bold transition-all shadow-sm <?php echo $is_autofill ? 'bg-slate-50 text-slate-400' : 'bg-white text-red-600 focus:ring-2 focus:ring-red-500/30'; ?>" style="border: 1px solid #cbd5e1 !important;" placeholder="Cth: BRG-0005-OLD" value="<?php echo htmlspecialchars($def_kode); ?>" <?php echo $is_autofill ? 'readonly tabindex="-1"' : 'required autocomplete="off"'; ?>>
                            </div>
                        </div>

                        <?php if($is_autofill): ?>
                            <div class="col-span-1">
                                <label class="block text-[11px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">
                                    Berat Awal <i class="bi bi-lock-fill text-slate-400 ms-1"></i>
                                </label>
                                <input type="text" class="w-full px-3 py-2.5 bg-slate-50 rounded-xl text-[13px] font-bold text-slate-400 shadow-sm" style="border: 1px solid #cbd5e1 !important;" value="<?php echo htmlspecialchars($def_berat); ?> g" readonly tabindex="-1">
                            </div>
                        <?php endif; ?>

                        <div class="col-span-1 <?php echo !$is_autofill ? 'md:col-span-2' : 'md:col-span-2'; ?>">
                            <label class="block text-[11px] font-extrabold text-red-500 uppercase tracking-wider mb-2">
                                Berat Terima (Gram)
                            </label>
                            <input type="text" inputmode="decimal" id="inputBeratSekarang" name="berat_sekarang" class="w-full px-3 py-2.5 bg-white rounded-xl text-[14px] font-black text-slate-800 focus:ring-2 focus:ring-red-500/30 transition-all shadow-sm" style="border: 1px solid #fca5a5 !important;" value="<?php echo htmlspecialchars($def_berat); ?>" placeholder="0.00" autocomplete="off" required>
                            <?php if($is_autofill): ?>
                                <p class="text-[10px] font-bold text-red-400 mt-1.5 mb-0"><i class="bi bi-info-circle-fill"></i> Ubah nominal gram jika saat ditimbang emas menyusut.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>

            <div class="w-full lg:w-[35%] flex flex-col gap-4">
                
                <?php if($is_autofill): ?>
                    <div class="bg-gradient-to-br from-[#3b82f6] to-[#2563eb] rounded-[20px] p-5 shadow-lg relative overflow-hidden">
                        <i class="bi bi-receipt absolute -right-4 -bottom-4 text-[6rem] text-white opacity-10 rotate-12 pointer-events-none"></i>
                        
                        <div class="flex items-center gap-2 mb-4">
                            <span class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center text-white text-[10px] font-black"><i class="bi bi-check-lg"></i></span>
                            <span class="text-[11px] font-extrabold text-blue-100 uppercase tracking-widest">Surat Ditemukan</span>
                        </div>

                        <div class="flex justify-between items-center mb-2">
                            <span class="text-[11px] font-semibold text-blue-200">Harga Jual Dulu</span>
                            <span class="text-[12px] font-bold text-white">Rp <?php echo number_format($def_harga_beli_awal, 0, ',', '.'); ?></span>
                        </div>
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-[11px] font-semibold text-blue-200">Acuan /gram</span>
                            <span class="text-[12px] font-bold <?php echo ($def_harga_buyback_gram > 0) ? 'text-green-300' : 'text-red-300'; ?>">
                                <?php echo ($def_harga_buyback_gram > 0) ? 'Rp ' . number_format($def_harga_buyback_gram, 0, ',', '.') : 'Belum Diatur'; ?>
                            </span>
                        </div>

                        <div class="pt-3 mt-1 flex justify-between items-end" style="border-top: 1px dashed rgba(255,255,255,0.3);">
                            <span class="text-[11px] font-black text-blue-100 uppercase tracking-wider">ESTIMASI SISTEM</span>
                            <span class="text-[20px] font-black text-white leading-none tracking-tight" id="displayEstimasi">Rp 0</span>
                        </div>
                        
                        <button type="button" class="w-full mt-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl font-bold text-[12px] transition-colors border border-white/20 flex items-center justify-center gap-2 cursor-pointer" onclick="salinEstimasi()">
                            <i class="bi bi-arrow-down"></i> Salin ke Harga Deal
                        </button>
                    </div>
                <?php else: ?>
                    <div class="bg-blue-50 border border-blue-200 text-blue-700 px-5 py-4 rounded-2xl shadow-sm flex items-start gap-3">
                        <i class="bi bi-info-circle-fill text-xl mt-0.5"></i>
                        <div>
                            <h4 class="font-bold text-[14px] mb-1">Buyback Non-Surat</h4>
                            <p class="text-[12px] font-medium mb-0 opacity-90">Sistem tidak memiliki acuan riwayat harga beli. Silakan kalkulasi manual dan masukkan Harga Deal di bawah.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="bg-white p-5 rounded-[20px] shadow-sm border border-red-100 relative overflow-hidden">
                    <label class="block text-[12px] font-extrabold text-red-500 uppercase tracking-wider mb-2">Harga Deal Akhir</label>
                    <div class="flex items-center bg-red-50 rounded-xl px-4 py-1 shadow-inner border border-red-200 mb-2 transition-colors duration-300" id="boxHargaDeal">
                        <span class="text-[14px] font-bold text-red-400 mr-2">Rp</span>
                        <input type="text" inputmode="numeric" id="inputHargaDeal" name="harga_deal" class="w-full py-2 text-[20px] font-black text-red-600 bg-transparent outline-none focus:outline-none" placeholder="0" required autocomplete="off">
                    </div>
                    <p class="text-[10px] font-semibold text-slate-400 mb-0 leading-tight">Uang tunai riil yang akan dibayarkan toko ke pelanggan.</p>
                </div>

                <button type="submit" name="simpan_buyback" class="w-full py-3.5 bg-red-500 hover:bg-red-600 text-white rounded-[16px] font-black text-[14px] tracking-widest uppercase transition-all shadow-md hover:shadow-lg flex items-center justify-center gap-2.5 cursor-pointer mt-1">
                    <i class="bi bi-wallet2 text-lg"></i> SIMPAN TRANSAKSI
                </button>

            </div>
        </div>
    </form>
</div>

<script>
    // === LOGIKA AUTOCOMPLETE ===
    const dbPelanggan = <?php echo json_encode($dataPelanggan); ?>;
    const dbKatalog = <?php echo json_encode($dataKatalog); ?>;
    
    const iPel = document.getElementById('inputPelanggan');
    const vPelID = document.getElementById('valPelangganID');
    const dropPel = document.getElementById('dropdownPelanggan');
    
    if (iPel && dropPel && !iPel.readOnly) {
        iPel.addEventListener('input', function() {
            const val = this.value.toLowerCase();
            dropPel.innerHTML = '';
            vPelID.value = ''; 
            
            if (val.length >= 2) {
                const filtered = dbPelanggan.filter(p => p.NamaPelanggan.toLowerCase().includes(val) || p.NoHP.includes(val));
                
                if (filtered.length > 0) {
                    filtered.forEach(p => {
                        const item = document.createElement('div');
                        item.className = 'px-4 py-2.5 cursor-pointer hover:bg-slate-50 border-b border-slate-100 last:border-0 transition-colors';
                        item.innerHTML = `<div class="text-[13px] font-bold text-slate-800">${p.NamaPelanggan}</div><div class="text-[11px] font-semibold text-slate-500"><i class="bi bi-whatsapp"></i> ${p.NoHP}</div>`;
                        item.addEventListener('click', () => {
                            iPel.value = p.NamaPelanggan;
                            vPelID.value = p.PelangganID;
                            dropPel.classList.add('hidden');
                            dropPel.classList.remove('flex');
                            iPel.style.setProperty('border-color', '#cbd5e1', 'important');
                            iPel.classList.remove('bg-red-50');
                        });
                        dropPel.appendChild(item);
                    });
                } else {
                    const item = document.createElement('div');
                    item.className = 'px-4 py-4 text-center bg-slate-50';
                    item.innerHTML = `<span class="text-[12px] font-semibold text-slate-500 block mb-2">Pelanggan tidak ditemukan</span>
                                      <a href="../master/pelanggan.php" class="inline-block px-4 py-1.5 bg-white border border-slate-200 shadow-sm rounded-lg text-[11px] font-bold text-[#3b82f6] hover:bg-slate-100 transition-colors"><i class="bi bi-plus-lg"></i> Tambah Pelanggan Baru</a>`;
                    dropPel.appendChild(item);
                }
                dropPel.classList.remove('hidden');
                dropPel.classList.add('flex');
            } else {
                dropPel.classList.add('hidden');
                dropPel.classList.remove('flex');
            }
        });
    }

    const iKat = document.getElementById('inputKatalog');
    const vKatID = document.getElementById('valKatalogID');
    const dropKat = document.getElementById('dropdownKatalog');
    
    if (iKat && dropKat && !iKat.readOnly) {
        iKat.addEventListener('input', function() {
            const val = this.value.toLowerCase();
            dropKat.innerHTML = '';
            vKatID.value = ''; 
            
            if (val.length >= 2) {
                const filtered = dbKatalog.filter(k => k.NamaProduk.toLowerCase().includes(val) || k.Kadar.toLowerCase().includes(val));
                
                if (filtered.length > 0) {
                    filtered.forEach(k => {
                        const item = document.createElement('div');
                        item.className = 'px-4 py-2.5 cursor-pointer hover:bg-slate-50 border-b border-slate-100 last:border-0 transition-colors';
                        item.innerHTML = `<div class="text-[13px] font-bold text-slate-800">${k.NamaProduk}</div><div class="text-[11px] font-semibold text-amber-600"><i class="bi bi-gem"></i> Kadar: ${k.Kadar}</div>`;
                        item.addEventListener('click', () => {
                            iKat.value = k.NamaProduk;
                            vKatID.value = k.ProdukKatalogID;
                            dropKat.classList.add('hidden');
                            dropKat.classList.remove('flex');
                            iKat.style.setProperty('border-color', '#cbd5e1', 'important');
                            iKat.classList.remove('bg-red-50');
                        });
                        dropKat.appendChild(item);
                    });
                } else {
                    const item = document.createElement('div');
                    item.className = 'px-4 py-4 text-center bg-slate-50';
                    item.innerHTML = `<span class="text-[12px] font-semibold text-slate-500 block">Katalog emas tidak ditemukan</span>`;
                    dropKat.appendChild(item);
                }
                dropKat.classList.remove('hidden');
                dropKat.classList.add('flex');
            } else {
                dropKat.classList.add('hidden');
                dropKat.classList.remove('flex');
            }
        });
    }

    document.addEventListener('click', function(e) {
        if (iPel && dropPel && !iPel.contains(e.target) && !dropPel.contains(e.target)) {
            dropPel.classList.add('hidden');
            dropPel.classList.remove('flex');
        }
        if (iKat && dropKat && !iKat.contains(e.target) && !dropKat.contains(e.target)) {
            dropKat.classList.add('hidden');
            dropKat.classList.remove('flex');
        }
    });

    // === LOGIKA ESTIMASI & FORMAT RIBUAN ===
    const hargaBuybackPerGram = <?php echo $def_harga_buyback_gram; ?>;
    const inputBeratSekarang = document.getElementById('inputBeratSekarang');
    const displayEstimasi = document.getElementById('displayEstimasi');
    const inputHargaDeal = document.getElementById('inputHargaDeal');
    const boxHargaDeal = document.getElementById('boxHargaDeal');
    const iKod = document.getElementById('inputKode');
    
    let estimasiSaatIni = 0;

    function hitungEstimasi() {
        if(hargaBuybackPerGram > 0 && displayEstimasi) {
            let rawBerat = inputBeratSekarang.value.replace(',', '.');
            let berat = parseFloat(rawBerat) || 0;
            
            estimasiSaatIni = berat * hargaBuybackPerGram;
            displayEstimasi.innerHTML = 'Rp ' + new Intl.NumberFormat('id-ID').format(estimasiSaatIni);
        }
    }

    function salinEstimasi() {
        if(estimasiSaatIni > 0) {
            // PERBAIKAN: Format angka saat disalin
            inputHargaDeal.value = new Intl.NumberFormat('id-ID').format(estimasiSaatIni);
            
            boxHargaDeal.classList.remove('bg-red-50', 'border-red-200');
            boxHargaDeal.classList.add('bg-emerald-50', 'border-emerald-300');
            setTimeout(() => { 
                boxHargaDeal.classList.remove('bg-emerald-50', 'border-emerald-300');
                boxHargaDeal.classList.add('bg-red-50', 'border-red-200');
            }, 600);
        } else {
            alert('Harga acuan buyback belum diatur untuk kadar emas ini!');
        }
    }

    // PERBAIKAN: Format Ribuan Live saat mengetik di input Harga Deal
    if(inputHargaDeal) {
        inputHargaDeal.addEventListener('input', function(e) {
            // Hilangkan semua karakter kecuali angka
            let value = this.value.replace(/[^0-9]/g, '');
            if (value !== '') {
                // Konversi kembali menjadi format ribuan dengan titik
                this.value = new Intl.NumberFormat('id-ID').format(value);
            } else {
                this.value = '';
            }
        });
    }

    hitungEstimasi();
    if(inputBeratSekarang) {
        inputBeratSekarang.addEventListener('input', hitungEstimasi);
    }

    // === CUSTOM VALIDATION FORM ===
    const formBuyback = document.getElementById('formBuyback');

    formBuyback.addEventListener('submit', function(e) {
        let isValid = true;
        
        const checkFields = [
            { input: iPel, hidden: vPelID, errorMsg: "Pilih Pelanggan dari saran!" },
            { input: iKat, hidden: vKatID, errorMsg: "Pilih Katalog dari saran!" },
            { input: iKod, errorMsg: "Isi Kode Barcode" },
            { input: inputBeratSekarang, errorMsg: "Isi Berat Sekarang" },
            { input: inputHargaDeal, errorMsg: "Isi Harga Deal > 0" }
        ];

        checkFields.forEach(field => {
            let el = field.input;
            let hiddenEl = field.hidden;
            
            if(el && !el.disabled && !el.readOnly) {
                let valToCheck = hiddenEl ? hiddenEl.value : el.value;
                
                // Pengecekan khusus Harga Deal (Abaikan titik agar bisa divalidasi)
                let isZeroHarga = false;
                if(el.id === 'inputHargaDeal') {
                    let numValue = parseInt(valToCheck.replace(/\./g, '')) || 0;
                    if(numValue <= 0) isZeroHarga = true;
                }
                
                if(!valToCheck || valToCheck.toString().trim() === '' || isZeroHarga) {
                    isValid = false;
                    // Jika elemen adalah inputHargaDeal, kita warnai box-nya
                    if(el.id === 'inputHargaDeal') {
                        boxHargaDeal.style.setProperty('border-color', '#ef4444', 'important');
                        boxHargaDeal.classList.add('bg-red-100');
                    } else {
                        el.style.setProperty('border-color', '#ef4444', 'important'); 
                        el.classList.add('bg-red-50');
                    }
                }
            }
        });

        if(!isValid) {
            e.preventDefault(); 
            const btn = this.querySelector('button[type="submit"]');
            btn.classList.remove('animate-shake');
            void btn.offsetWidth;
            btn.classList.add('animate-shake');

            const wToast = document.getElementById('warningToast');
            wToast.classList.add('show');
            setTimeout(() => { wToast.classList.remove('show'); }, 3000);
        } else {
            document.getElementById('miniLoader').classList.remove('hidden');
            document.getElementById('miniLoader').classList.add('flex');
            setTimeout(() => { document.getElementById('miniLoader').style.opacity = '1'; }, 10);
        }
    });

</script>

<?php include '../../layouts/footer.php'; ?>