<?php 
session_start();
include '../../config/database.php'; 

// Cek Role Owner
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Owner') {
    header("Location: ../dashboard/index.php");
    exit;
}

$tgl_today = date('Y-m-d');
$waktu_sekarang = date('Y-m-d H:i:s'); // Mendapatkan waktu spesifik hingga jam, menit, detik

// --- LOGIKA 1: TAMBAH KADAR BARU ---
if (isset($_POST['tambah_kadar_baru'])) {
    $kadar_baru = mysqli_real_escape_string($koneksi, $_POST['kadar_baru']);
    $jual_baru  = $_POST['jual_baru'];
    $beli_baru  = $_POST['beli_baru'];

    // Gunakan DATE(Tanggal) agar kompatibel walau format DB menyimpan Jam
    $cek = mysqli_query($koneksi, "SELECT * FROM riwayat_harga WHERE DATE(Tanggal)='$tgl_today' AND Kadar='$kadar_baru'");
    
    if(mysqli_num_rows($cek) > 0) {
        $q = "UPDATE riwayat_harga SET HargaJualPerGram='$jual_baru', HargaBeliPerGram='$beli_baru', Tanggal='$waktu_sekarang' 
              WHERE DATE(Tanggal)='$tgl_today' AND Kadar='$kadar_baru'";
    } else {
        $q = "INSERT INTO riwayat_harga (Tanggal, Kadar, HargaJualPerGram, HargaBeliPerGram) 
              VALUES ('$waktu_sekarang', '$kadar_baru', '$jual_baru', '$beli_baru')";
    }
    
    if(mysqli_query($koneksi, $q)) {
        $_SESSION['toast_success'] = "Jenis kadar emas baru berhasil didaftarkan!";
    } else {
        $_SESSION['toast_error'] = "Gagal menambahkan jenis kadar.";
    }
    header("Location: harga.php");
    exit;
}

// --- LOGIKA 2: UPDATE HARGA MASSAL ---
if (isset($_POST['update_harga'])) {
    $kadarList = $_POST['kadar']; 
    $jualList  = $_POST['harga_jual'];
    $beliList  = $_POST['harga_beli'];

    $success_count = 0;
    for ($i = 0; $i < count($kadarList); $i++) {
        $k = $kadarList[$i];
        $h_jual = $jualList[$i];
        $h_beli = $beliList[$i];
        
        // Hapus data hari ini, dan masukkan data baru dengan timestamp jam terbaru
        mysqli_query($koneksi, "DELETE FROM riwayat_harga WHERE DATE(Tanggal)='$tgl_today' AND Kadar='$k'");

        if($h_jual > 0) {
            $query = "INSERT INTO riwayat_harga (Tanggal, Kadar, HargaJualPerGram, HargaBeliPerGram) 
                      VALUES ('$waktu_sekarang', '$k', '$h_jual', '$h_beli')";
            if(mysqli_query($koneksi, $query)) $success_count++;
        }
    }
    $_SESSION['toast_success'] = "Berhasil memperbarui $success_count standar harga emas hari ini!";
    header("Location: harga.php");
    exit;
}

include '../../layouts/header.php'; 
include '../../layouts/sidebar.php'; 
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
    
    .custom-toast { transform: translate(-50%, -20px); opacity: 0; transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); }
    .custom-toast.show { transform: translate(-50%, 0); opacity: 1; }
    
    .custom-scroll::-webkit-scrollbar { width: 6px; }
    .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    .custom-scroll::-webkit-scrollbar-track { background: transparent; }
</style>

<div id="toastSuccess" class="fixed top-6 left-1/2 z-[10001] flex items-center gap-3 px-4 py-3 bg-white/95 backdrop-blur-md shadow-lg border border-emerald-200 rounded-2xl custom-toast pointer-events-none">
    <div class="w-8 h-8 rounded-full bg-emerald-50 border border-emerald-100 flex items-center justify-center flex-shrink-0"><i class="bi bi-check-lg text-emerald-500 text-lg"></i></div>
    <span id="toastSuccessMsg" class="text-[14px] font-bold text-slate-700 tracking-wide pr-2">Berhasil!</span>
</div>

<div id="toastError" class="fixed top-6 left-1/2 z-[10001] flex items-center gap-3 px-4 py-3 bg-white/95 backdrop-blur-md shadow-lg border border-red-200 rounded-2xl custom-toast pointer-events-none">
    <div class="w-8 h-8 rounded-full bg-red-50 border border-red-100 flex items-center justify-center flex-shrink-0"><i class="bi bi-exclamation-triangle-fill text-red-500 text-lg"></i></div>
    <span id="toastErrorMsg" class="text-[14px] font-bold text-slate-700 tracking-wide pr-2">Gagal!</span>
</div>

<div id="modalKadar" class="fixed inset-0 z-[1000] hidden items-center justify-center">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onclick="closeModal('modalKadar')"></div>
    <div class="relative bg-white rounded-[24px] shadow-2xl w-11/12 max-w-sm p-6 lg:p-8 z-10 transform scale-95 transition-all duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)]">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-[20px] font-extrabold text-slate-800 leading-tight">Daftarkan Kadar</h3>
            <button type="button" onclick="closeModal('modalKadar')" class="w-8 h-8 bg-slate-50 hover:bg-red-50 text-slate-500 rounded-full flex items-center justify-center transition-colors"><i class="bi bi-x-lg font-bold"></i></button>
        </div>
        <form method="POST" action="">
            <div class="mb-4">
                <label class="block text-[13px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">Nama Label Kadar</label>
                <input type="text" name="kadar_baru" class="w-full px-4 py-3 bg-slate-50 rounded-xl text-[16px] font-bold text-slate-800 focus:bg-white focus:ring-2 focus:ring-[#3b82f6]/30 transition-all" style="border: 1px solid #cbd5e1 !important;" placeholder="Cth: 22K atau 18K" required>
            </div>
            <div class="mb-4">
                <label class="block text-[13px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">Harga Jual /gram</label>
                <input type="number" name="jual_baru" class="w-full px-4 py-3 bg-slate-50 rounded-xl text-[16px] font-bold text-slate-800 focus:bg-white focus:ring-2 focus:ring-[#3b82f6]/30 transition-all" style="border: 1px solid #cbd5e1 !important;" placeholder="0" required>
            </div>
            <div class="mb-6">
                <label class="block text-[13px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">Harga Buyback /gram</label>
                <input type="number" name="beli_baru" class="w-full px-4 py-3 bg-slate-50 rounded-xl text-[16px] font-bold text-slate-800 focus:bg-white focus:ring-2 focus:ring-[#3b82f6]/30 transition-all" style="border: 1px solid #cbd5e1 !important;" placeholder="0" required>
            </div>
            <button type="submit" name="tambah_kadar_baru" class="w-full py-3.5 bg-[#3b82f6] hover:bg-[#2563eb] text-white rounded-xl font-bold text-[15px] transition-all shadow-md flex items-center justify-center gap-2 cursor-pointer active:scale-95">
                <i class="bi bi-plus-circle-fill"></i> Tambahkan Jenis Kadar
            </button>
        </form>
    </div>
</div>

<div class="main-content" style="padding: 32px 40px 16px 40px;">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h2 class="text-[1.75rem] font-bold text-slate-800 mb-1 flex items-center gap-2" style="letter-spacing: -0.5px;">
                <i class="bi bi-graph-up-arrow text-[#3b82f6]"></i> Kendali Harga Emas
            </h2>
            <p class="text-[0.95rem] font-medium text-slate-500 mb-0">Atur standar nilai jual dan beli harian toko secara massal.</p>
        </div>
        
        <button type="button" onclick="openModal('modalKadar')" class="bg-white hover:bg-slate-50 text-slate-600 px-6 py-2.5 rounded-xl font-bold text-[14px] transition-colors flex items-center gap-2 shadow-sm border border-slate-200 cursor-pointer">
            <i class="bi bi-plus-circle text-[#3b82f6]"></i> Tambah Jenis Kadar
        </button>
    </div>

    <div class="flex flex-col xl:flex-row gap-6">
        
        <div class="w-full xl:w-7/12">
            <div class="bg-white rounded-[24px] shadow-sm border border-solid border-slate-100 p-6 lg:p-7" style="box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);">
                
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-[#3b82f6] border border-blue-100">
                            <i class="bi bi-calendar-check text-xl"></i>
                        </div>
                        <div>
                            <span class="text-[11px] font-extrabold text-slate-400 uppercase tracking-widest leading-none">Update Harian</span>
                            <h4 class="text-[17px] font-black text-slate-800 mb-0"><?php echo date('d F Y'); ?></h4>
                        </div>
                    </div>
                    <span class="text-[11px] font-bold text-slate-400 italic flex items-center gap-1.5 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-100">
                        <i class="bi bi-info-circle text-blue-400"></i> Menampilkan harga terakhir
                    </span>
                </div>

                <form method="POST" action="">
                    <div class="space-y-2">
                        <?php 
                        $qKadar = mysqli_query($koneksi, "SELECT DISTINCT Kadar FROM (SELECT Kadar FROM produk_katalog UNION SELECT Kadar FROM riwayat_harga) AS AllKadar ORDER BY Kadar DESC");
                        
                        if(mysqli_num_rows($qKadar) > 0) {
                            while($row = mysqli_fetch_assoc($qKadar)) {
                                $kadar = $row['Kadar'];
                                $qCekHarga = mysqli_query($koneksi, "SELECT * FROM riwayat_harga WHERE Kadar='$kadar' ORDER BY Tanggal DESC LIMIT 1");
                                $dataHarga = mysqli_fetch_assoc($qCekHarga);
                                
                                $valJual = $dataHarga['HargaJualPerGram'] ?? '';
                                $valBeli = $dataHarga['HargaBeliPerGram'] ?? '';
                                $tglTerakhir = $dataHarga['Tanggal'] ?? '';
                                
                                $tglOnly = ($tglTerakhir != '') ? date('Y-m-d', strtotime($tglTerakhir)) : '';
                                $isUpdatedToday = ($tglOnly == $tgl_today);
                                
                                $waktuFormat = ($tglTerakhir != '') ? date('d M Y, H:i', strtotime($tglTerakhir)) . ' WIB' : 'Belum ada data';
                                $kadarColor = ($kadar == '24K') ? 'text-amber-500' : 'text-[#1e293b]';
                        ?>
                        
                        <div class="p-3 rounded-[14px] transition-all border border-solid border-slate-100 <?php echo $isUpdatedToday ? 'bg-emerald-50/20 border-emerald-100' : 'bg-slate-50/50 hover:bg-white hover:shadow-md'; ?>">
                            <div class="flex flex-col sm:flex-row items-center gap-3 sm:gap-4">
                                
                                <div class="w-full sm:w-[140px] flex flex-col justify-center text-center sm:text-left border-b sm:border-b-0 sm:border-r border-slate-200 py-1 sm:py-0 pr-0 sm:pr-3">
                                    <h3 class="text-[22px] font-black <?php echo $kadarColor; ?> m-0 p-0 leading-none"><?php echo $kadar; ?></h3>
                                    <input type="hidden" name="kadar[]" value="<?php echo $kadar; ?>">
                                    
                                    <div class="mt-1 mb-1">
                                        <?php if($isUpdatedToday): ?>
                                            <span class="inline-block text-[9px] font-extrabold text-emerald-600 bg-emerald-100 px-1.5 py-0.5 rounded uppercase tracking-tighter leading-none">Diperbarui Hari Ini</span>
                                        <?php else: ?>
                                            <span class="inline-block text-[9px] font-bold text-slate-500 bg-slate-200 px-1.5 py-0.5 rounded uppercase tracking-tighter leading-none">Pembaruan Tertunda</span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="text-[9px] font-semibold text-slate-400 leading-tight m-0 p-0">
                                        Pembaruan Terakhir:<br>
                                        <span class="font-bold text-slate-500"><?php echo $waktuFormat; ?></span>
                                    </div>
                                </div>

                                <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-3 w-full">
                                    <div class="relative">
                                        <label class="block text-[11px] font-extrabold text-slate-500 uppercase tracking-widest mb-1">Nilai Jual</label>
                                        <div class="flex items-center bg-white rounded-xl px-3 py-1.5 border border-solid border-slate-200 shadow-sm focus-within:ring-2 focus-within:ring-blue-500/20 focus-within:border-blue-400 transition-all">
                                            <span class="text-[13px] font-bold text-slate-400 mr-2">Rp</span>
                                            <input type="number" name="harga_jual[]" value="<?php echo $valJual; ?>" class="w-full py-0.5 text-[18px] font-black text-slate-800" placeholder="0">
                                        </div>
                                    </div>
                                    <div class="relative">
                                        <label class="block text-[11px] font-extrabold text-slate-500 uppercase tracking-widest mb-1">Nilai Buyback</label>
                                        <div class="flex items-center bg-white rounded-xl px-3 py-1.5 border border-solid border-slate-200 shadow-sm focus-within:ring-2 focus-within:ring-rose-500/20 focus-within:border-rose-400 transition-all">
                                            <span class="text-[13px] font-bold text-rose-400 mr-2">Rp</span>
                                            <input type="number" name="harga_beli[]" value="<?php echo $valBeli; ?>" class="w-full py-0.5 text-[18px] font-black text-rose-600" placeholder="0">
                                        </div>
                                    </div>
                                </div>
                                
                            </div>
                        </div>

                        <?php } } ?>

                        <button type="submit" name="update_harga" class="w-full mt-4 py-3.5 bg-[#3b82f6] hover:bg-[#2563eb] text-white rounded-[16px] font-black text-[15px] tracking-widest uppercase transition-all shadow-lg hover:shadow-[#3b82f6]/30 flex items-center justify-center gap-3 cursor-pointer active:scale-[0.98]">
                            <i class="bi bi-cloud-upload-fill text-xl"></i> Publikasikan Harga Hari Ini
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="w-full xl:w-5/12">
            <div class="bg-white rounded-[24px] shadow-sm border border-solid border-slate-100 overflow-hidden flex flex-col" style="box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02); height: calc(100vh - 120px);">
                
                <div class="p-6 lg:p-7 border-b border-slate-100 flex items-center justify-between bg-slate-50/30">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-[#3b82f6] border border-blue-100">
                            <i class="bi bi-clock-history text-xl"></i>
                        </div>
                        <h3 class="text-[17px] font-extrabold text-slate-800 mb-0">Log Perubahan Terakhir</h3>
                    </div>
                </div>
                
                <div class="flex-1 overflow-y-auto custom-scroll divide-y divide-slate-100 p-0">
                    <?php 
                    $historyData = [];
                    $qHist = mysqli_query($koneksi, "SELECT * FROM riwayat_harga ORDER BY Tanggal DESC, Kadar DESC LIMIT 40");
                    
                    while($h = mysqli_fetch_assoc($qHist)){
                        $dateStr = date('Y-m-d', strtotime($h['Tanggal']));
                        if(!isset($historyData[$dateStr])) {
                            $historyData[$dateStr] = [];
                        }
                        $historyData[$dateStr][] = $h;
                    }

                    if(count($historyData) > 0):
                        $isFirst = true; // Hanya yang pertama otomatis kebuka
                        foreach($historyData as $date => $items):
                            $groupId = 'log-' . strtotime($date);
                    ?>
                    
                    <div class="group border-b border-slate-100 last:border-0">
                        
                        <button type="button" onclick="toggleAccordion('<?php echo $groupId; ?>')" class="w-full flex justify-between items-center px-6 py-3.5 bg-white hover:bg-slate-50 transition-colors cursor-pointer outline-none text-left">
                            <div class="flex items-center gap-3">
                                <i class="bi bi-calendar-event text-[#3b82f6] text-[16px]"></i>
                                <span class="text-[14px] font-extrabold text-slate-800"><?php echo date('d M Y', strtotime($date)); ?></span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-[11px] font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md border border-slate-200"><?php echo count($items); ?> Kadar</span>
                                <i id="icon-<?php echo $groupId; ?>" class="bi bi-chevron-<?php echo $isFirst ? 'up' : 'down'; ?> text-slate-400 transition-transform duration-200"></i>
                            </div>
                        </button>

                        <div id="content-<?php echo $groupId; ?>" class="<?php echo $isFirst ? 'block' : 'hidden'; ?> bg-slate-50/50 border-t border-slate-100">
                            <table class="w-full text-left border-collapse whitespace-nowrap">
                                <tbody class="divide-y divide-slate-100">
                                    <?php foreach($items as $h): ?>
                                    <tr class="hover:bg-white transition-colors">
                                        <td class="py-2.5 px-6 w-1/4">
                                            <span class="text-[14px] font-black text-slate-700"><?php echo $h['Kadar']; ?></span>
                                            <div class="text-[9px] font-semibold text-slate-400"><?php echo date('H:i', strtotime($h['Tanggal'])); ?> WIB</div>
                                        </td>
                                        <td class="py-2.5 px-4 text-right w-2/4">
                                            <div class="flex flex-col">
                                                <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mb-0.5">Nilai Jual</span>
                                                <span class="text-[14px] font-black text-[#3b82f6]">Rp <?php echo number_format($h['HargaJualPerGram'], 0, ',', '.'); ?></span>
                                            </div>
                                        </td>
                                        <td class="py-2.5 px-6 text-right w-2/4">
                                            <div class="flex flex-col">
                                                <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mb-0.5">Nilai Buyback</span>
                                                <span class="text-[14px] font-black text-rose-500">Rp <?php echo number_format($h['HargaBeliPerGram'], 0, ',', '.'); ?></span>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <?php 
                        $isFirst = false;
                        endforeach;
                    else: 
                    ?>
                        <div class="text-center py-16">
                            <i class="bi bi-clock-history text-5xl text-slate-200 mb-4 block"></i>
                            <p class="text-[14px] font-bold text-slate-500">Belum ada log riwayat harga yang tercatat.</p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>

    </div>
</div>

<script>
    // ACCORDION LOGIKA
    function toggleAccordion(id) {
        const content = document.getElementById('content-' + id);
        const icon = document.getElementById('icon-' + id);
        
        if (content.classList.contains('hidden')) {
            content.classList.remove('hidden');
            content.classList.add('block');
            icon.classList.remove('bi-chevron-down');
            icon.classList.add('bi-chevron-up');
        } else {
            content.classList.remove('block');
            content.classList.add('hidden');
            icon.classList.remove('bi-chevron-up');
            icon.classList.add('bi-chevron-down');
        }
    }

    function openModal(id) {
        const modal = document.getElementById(id);
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => modal.children[1].classList.add('scale-100', 'opacity-100'), 10);
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        modal.children[1].classList.remove('scale-100', 'opacity-100');
        setTimeout(() => { modal.classList.add('hidden'); modal.classList.remove('flex'); }, 200);
    }

    window.addEventListener('DOMContentLoaded', () => {
        <?php if(isset($_SESSION['toast_success'])): ?>
            const tSuccess = document.getElementById('toastSuccess');
            document.getElementById('toastSuccessMsg').innerText = "<?php echo $_SESSION['toast_success']; ?>";
            tSuccess.classList.add('show');
            setTimeout(() => { tSuccess.classList.remove('show'); }, 3500);
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