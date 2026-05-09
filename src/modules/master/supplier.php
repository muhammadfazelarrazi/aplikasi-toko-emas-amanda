<?php 
session_start();
include '../../config/database.php'; 

// Cek Login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../modules/auth/login.php");
    exit;
}

// --- LOGIKA 1: TAMBAH SUPPLIER ---
if (isset($_POST['tambah'])) {
    $nama   = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $kontak = mysqli_real_escape_string($koneksi, $_POST['kontak']);
    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);

    $query = "INSERT INTO supplier (NamaSupplier, Kontak, Alamat) VALUES ('$nama', '$kontak', '$alamat')";
    
    if(mysqli_query($koneksi, $query)){
        $_SESSION['toast_success'] = "Data supplier baru berhasil ditambahkan ke sistem!";
    } else {
        $_SESSION['toast_error'] = "Gagal menyimpan data referensi supplier.";
    }
    header("Location: supplier.php");
    exit;
}

// --- LOGIKA 2: EDIT SUPPLIER ---
if (isset($_POST['edit'])) {
    $id     = $_POST['id_supplier'];
    $nama   = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $kontak = mysqli_real_escape_string($koneksi, $_POST['kontak']);
    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);

    $query = "UPDATE supplier SET NamaSupplier='$nama', Kontak='$kontak', Alamat='$alamat' WHERE SupplierID='$id'";
    
    if(mysqli_query($koneksi, $query)){
        $_SESSION['toast_success'] = "Profil perusahaan/supplier berhasil diperbarui!";
    } else {
        $_SESSION['toast_error'] = "Gagal memperbarui profil supplier.";
    }
    header("Location: supplier.php");
    exit;
}

// --- LOGIKA 3: HAPUS SUPPLIER ---
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    
    // Cek Relasi: Apakah supplier ini pernah kirim barang?
    $cekStok = mysqli_query($koneksi, "SELECT * FROM barang_stok WHERE SupplierID='$id'");
    
    if(mysqli_num_rows($cekStok) > 0){
        $_SESSION['toast_error'] = "Akses Ditolak: Supplier ini tidak bisa dihapus karena terkait dengan histori persediaan stok.";
    } else {
        if(mysqli_query($koneksi, "DELETE FROM supplier WHERE SupplierID='$id'")){
            $_SESSION['toast_success'] = "Data supplier berhasil dihapus secara permanen.";
        } else {
            $_SESSION['toast_error'] = "Terjadi kesalahan saat menghapus data supplier.";
        }
    }
    header("Location: supplier.php");
    exit;
}

// --- LOGIKA 4: PENCARIAN ---
$keyword = "";
$where = "";
if (isset($_GET['q'])) {
    $keyword = mysqli_real_escape_string($koneksi, $_GET['q']);
    $where = "WHERE NamaSupplier LIKE '%$keyword%' OR Kontak LIKE '%$keyword%'";
}

$queryData = "SELECT * FROM supplier $where ORDER BY NamaSupplier ASC";
$result = mysqli_query($koneksi, $queryData);

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
    a { text-decoration: none !important; }

    body { 
        font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", "Helvetica Neue", Helvetica, Arial, sans-serif;
        background-color: #f8fafc; color: #0f172a; -webkit-font-smoothing: antialiased; letter-spacing: -0.2px;
    }
    input, button, select, textarea {
        border: none !important; outline: none !important; box-shadow: none !important; background: transparent;
        -webkit-appearance: none; -moz-appearance: none; appearance: none;
    }
    input:focus, button:focus, select:focus, textarea:focus { outline: none !important; box-shadow: none !important; }
    
    /* Custom Scrollbar */
    .table-scroll::-webkit-scrollbar, .custom-scroll::-webkit-scrollbar { height: 6px; width: 6px; }
    .table-scroll::-webkit-scrollbar-track, .custom-scroll::-webkit-scrollbar-track { background: transparent; }
    .table-scroll::-webkit-scrollbar-thumb, .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

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
    <span id="toastSuccessMsg" class="text-[14px] font-bold text-slate-700 tracking-wide pr-2">Berhasil!</span>
</div>

<div id="toastError" class="fixed top-6 left-1/2 z-[10001] flex items-center gap-3 px-4 py-3 bg-white/95 backdrop-blur-md shadow-lg border border-red-200 rounded-2xl custom-toast pointer-events-none">
    <div class="w-8 h-8 rounded-full bg-red-50 border border-red-100 flex items-center justify-center flex-shrink-0">
        <i class="bi bi-exclamation-triangle-fill text-red-500 text-lg"></i>
    </div>
    <span id="toastErrorMsg" class="text-[14px] font-bold text-slate-700 tracking-wide pr-2">Gagal!</span>
</div>

<div id="customConfirmModal" class="fixed inset-0 z-[10000] hidden items-center justify-center">
    <div id="confirmBackdrop" class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm opacity-0 transition-opacity duration-300" onclick="closeConfirmModal()"></div>
    <div id="confirmCard" class="relative bg-white rounded-[24px] shadow-2xl w-11/12 max-w-xs p-6 flex flex-col items-center text-center transform scale-95 translate-y-4 opacity-0 transition-all duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)]">
        <div class="w-16 h-16 bg-red-50 text-red-500 rounded-full flex items-center justify-center mb-4 border-[6px] border-red-50 shadow-sm relative">
            <i class="bi bi-exclamation-triangle-fill text-2xl z-10"></i>
            <div class="absolute inset-0 rounded-full bg-red-500/20 animate-ping"></div>
        </div>
        <h3 class="text-[17px] font-extrabold text-slate-800 mb-1">Konfirmasi Tindakan</h3>
        <p class="text-[13px] font-medium text-slate-500 mb-6 leading-relaxed">Yakin ingin menghapus data supplier ini dari sistem manajemen pasokan?</p>
        <div class="flex w-full gap-3">
            <button type="button" onclick="closeConfirmModal()" class="flex-1 py-3 rounded-xl font-bold text-[14px] text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors cursor-pointer">Batal</button>
            <a href="#" id="btnConfirmProceed" class="flex-1 py-3 rounded-xl font-bold text-[14px] text-white bg-red-500 hover:bg-red-600 shadow-md shadow-red-500/30 transition-all active:scale-95 cursor-pointer flex items-center justify-center">Ya, Hapus</a>
        </div>
    </div>
</div>

<div id="modalTambah" class="fixed inset-0 z-[1000] hidden items-center justify-center">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onclick="closeModal('modalTambah')"></div>
    
    <div class="relative bg-white rounded-[24px] shadow-2xl w-11/12 max-w-md p-6 lg:p-8 z-10 transform scale-95 transition-all duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)]">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h3 class="text-[20px] font-extrabold text-slate-800 leading-tight">Tambah Supplier Baru</h3>
                <p class="text-[13px] font-semibold text-slate-500 mb-0">Input data referensi perusahaan pemasok barang.</p>
            </div>
            <button type="button" onclick="closeModal('modalTambah')" class="w-8 h-8 bg-slate-50 hover:bg-red-50 text-slate-500 hover:text-red-500 rounded-full flex items-center justify-center transition-colors">
                <i class="bi bi-x-lg font-bold pointer-events-none"></i>
            </button>
        </div>

        <form method="POST" action="">
            <div class="mb-4">
                <label class="block text-[12px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">Nama Perusahaan / Entitas <span class="text-red-500">*</span></label>
                <input type="text" name="nama" class="w-full px-4 py-3 bg-slate-50 rounded-xl text-[15px] font-bold text-slate-800 focus:bg-white focus:ring-2 focus:ring-[#3b82f6]/30 transition-all" style="border: 1px solid #cbd5e1 !important;" placeholder="Contoh: PT. Sinar Emas Mulia" required>
            </div>

            <div class="mb-4">
                <label class="block text-[12px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">Nomor Kontak / Telepon</label>
                <input type="text" name="kontak" class="w-full px-4 py-3 bg-slate-50 rounded-xl text-[14px] font-bold text-slate-800 focus:bg-white focus:ring-2 focus:ring-[#3b82f6]/30 transition-all" style="border: 1px solid #cbd5e1 !important;" placeholder="Contoh: 021-xxxxxx atau 0812...">
            </div>

            <div class="mb-6">
                <label class="block text-[12px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">Alamat Lengkap (Operasional)</label>
                <textarea name="alamat" class="w-full px-4 py-3 bg-slate-50 rounded-xl text-[14px] font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-[#3b82f6]/30 transition-all custom-scroll" style="border: 1px solid #cbd5e1 !important;" rows="3" placeholder="Jalan, Gedung, Kota..."></textarea>
            </div>

            <div class="flex gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('modalTambah')" class="flex-1 py-3.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl font-bold text-[14px] transition-colors cursor-pointer">Batal</button>
                <button type="submit" name="tambah" class="flex-1 py-3.5 bg-[#3b82f6] hover:bg-[#2563eb] text-white rounded-xl font-bold text-[14px] transition-colors shadow-md flex items-center justify-center gap-2 cursor-pointer">
                    <i class="bi bi-save2-fill"></i> Simpan Data
                </button>
            </div>
        </form>

    </div>
</div>

<div class="main-content" style="padding: 32px 40px 16px 40px;">
    
    <div class="mb-4">
        <h2 class="text-[1.75rem] font-bold text-slate-800 mb-1 flex items-center gap-2" style="letter-spacing: -0.5px;">
            <i class="bi bi-truck text-[#3b82f6]"></i> Data Supplier & Pemasok
        </h2>
        <p class="text-[0.95rem] font-medium text-slate-500 mb-0">Manajemen profil distributor dan rujukan pengadaan stok barang toko.</p>
    </div>

    <div class="w-full flex flex-wrap items-center justify-between gap-4 mb-6">
        
        <form action="" method="GET" class="flex flex-wrap items-center gap-3 m-0 w-full lg:w-auto">
            <div class="flex items-center bg-white rounded-xl shadow-sm overflow-hidden" style="border: 1px solid #cbd5e1 !important;">
                <input type="text" name="q" class="pl-4 pr-2 py-3 w-full sm:w-[260px] lg:w-[320px] text-[14px] font-semibold text-slate-700 bg-transparent focus:ring-2 focus:ring-[#3b82f6]/30 transition-all" placeholder="Cari Nama Perusahaan / Nomor Kontak..." value="<?php echo htmlspecialchars($keyword); ?>" autocomplete="off">
                <button type="submit" class="px-4 py-3 bg-slate-50 border-l border-slate-200 text-slate-500 hover:text-[#3b82f6] hover:bg-blue-50 transition-colors cursor-pointer" title="Cari Data">
                    <i class="bi bi-search font-bold"></i>
                </button>
            </div>

            <?php if($keyword != ''): ?>
                <a href="supplier.php" class="flex items-center justify-center w-[46px] h-[46px] bg-red-50 text-red-500 rounded-xl shadow-sm hover:bg-red-500 hover:text-white transition-all cursor-pointer" title="Reset Filter" style="border: 1px solid #fecaca !important;">
                    <i class="bi bi-arrow-counterclockwise text-xl"></i>
                </a>
            <?php endif; ?>
        </form>

        <button type="button" onclick="openModal('modalTambah')" class="flex items-center justify-center gap-2 bg-[#3b82f6] hover:bg-[#2563eb] text-white px-6 py-3 rounded-xl font-bold text-[14px] transition-colors shadow-md w-full lg:w-auto cursor-pointer">
            <i class="bi bi-plus-lg text-lg"></i> Tambah Supplier
        </button>
    </div>

    <div class="bg-white rounded-[20px] shadow-sm border border-solid border-slate-100 overflow-hidden" style="box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);">
        <div class="overflow-x-auto table-scroll">
            <table class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="py-4 px-5 text-[13px] font-extrabold text-slate-500 uppercase tracking-wider">Nama Perusahaan / Supplier</th>
                        <th class="py-4 px-4 text-[13px] font-extrabold text-slate-500 uppercase tracking-wider">Nomor Kontak / Telepon</th>
                        <th class="py-4 px-4 text-[13px] font-extrabold text-slate-500 uppercase tracking-wider">Alamat Lengkap (Operasional)</th>
                        <th class="py-4 px-5 text-[13px] font-extrabold text-slate-500 uppercase tracking-wider text-right">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if(mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr class="hover:bg-slate-50/50 transition-colors group">
                            <td class="py-4 px-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-[#3b82f6] flex-shrink-0 border border-blue-100">
                                        <i class="bi bi-building-fill text-xl"></i>
                                    </div>
                                    <span class="text-[15px] font-extrabold text-slate-800"><?php echo $row['NamaSupplier']; ?></span>
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                <span class="text-[14px] font-bold text-slate-600 flex items-center gap-2"><i class="bi bi-telephone-fill text-slate-400"></i> <?php echo $row['Kontak'] ? $row['Kontak'] : '-'; ?></span>
                            </td>
                            <td class="py-4 px-4">
                                <div class="text-[14px] font-medium text-slate-500 truncate max-w-[300px] lg:max-w-[400px]" title="<?php echo $row['Alamat']; ?>">
                                    <?php echo $row['Alamat'] ? $row['Alamat'] : '-'; ?>
                                </div>
                            </td>
                            <td class="py-4 px-5 text-right">
                                <div class="flex gap-2 justify-end opacity-70 group-hover:opacity-100 transition-opacity">
                                    
                                    <button type="button" onclick="openModal('modalEdit<?php echo $row['SupplierID']; ?>')" class="bg-white hover:bg-blue-50 text-slate-500 hover:text-[#3b82f6] w-9 h-9 rounded-lg text-[14px] font-bold transition-colors shadow-sm flex items-center justify-center border border-slate-200 hover:border-blue-300 cursor-pointer" title="Ubah Data Supplier">
                                        <i class="bi bi-pencil-fill pointer-events-none"></i>
                                    </button>

                                    <button type="button" onclick="openConfirmModal('?hapus=<?php echo $row['SupplierID']; ?>')" class="bg-white hover:bg-red-50 text-slate-500 hover:text-red-500 w-9 h-9 rounded-lg text-[14px] font-bold transition-colors shadow-sm flex items-center justify-center border border-slate-200 hover:border-red-300 cursor-pointer" title="Hapus Data Supplier">
                                        <i class="bi bi-trash3-fill pointer-events-none"></i>
                                    </button>

                                </div>
                            </td>
                        </tr>

                        <div id="modalEdit<?php echo $row['SupplierID']; ?>" class="fixed inset-0 z-[1000] hidden items-center justify-center">
                            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onclick="closeModal('modalEdit<?php echo $row['SupplierID']; ?>')"></div>
                            
                            <div class="relative bg-white rounded-[24px] shadow-2xl w-11/12 max-w-md p-6 lg:p-8 z-10 transform scale-95 transition-all duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)] text-left">
                                <div class="flex justify-between items-center mb-6">
                                    <div>
                                        <h3 class="text-[20px] font-extrabold text-slate-800 leading-tight">Ubah Data Supplier</h3>
                                        <p class="text-[13px] font-semibold text-slate-500 mb-0">ID Ref: <span class="text-[#3b82f6]">SUP-<?php echo $row['SupplierID']; ?></span></p>
                                    </div>
                                    <button type="button" onclick="closeModal('modalEdit<?php echo $row['SupplierID']; ?>')" class="w-8 h-8 bg-slate-50 hover:bg-red-50 text-slate-500 hover:text-red-500 rounded-full flex items-center justify-center transition-colors">
                                        <i class="bi bi-x-lg font-bold pointer-events-none"></i>
                                    </button>
                                </div>

                                <form method="POST" action="">
                                    <input type="hidden" name="id_supplier" value="<?php echo $row['SupplierID']; ?>">
                                    
                                    <div class="mb-4">
                                        <label class="block text-[12px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">Nama Perusahaan / Entitas <span class="text-red-500">*</span></label>
                                        <input type="text" name="nama" value="<?php echo $row['NamaSupplier']; ?>" class="w-full px-4 py-3 bg-slate-50 rounded-xl text-[15px] font-bold text-slate-800 focus:bg-white focus:ring-2 focus:ring-[#3b82f6]/30 transition-all" style="border: 1px solid #cbd5e1 !important;" required>
                                    </div>

                                    <div class="mb-4">
                                        <label class="block text-[12px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">Nomor Kontak / Telepon</label>
                                        <input type="text" name="kontak" value="<?php echo $row['Kontak']; ?>" class="w-full px-4 py-3 bg-slate-50 rounded-xl text-[14px] font-bold text-slate-800 focus:bg-white focus:ring-2 focus:ring-[#3b82f6]/30 transition-all" style="border: 1px solid #cbd5e1 !important;">
                                    </div>

                                    <div class="mb-6">
                                        <label class="block text-[12px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">Alamat Lengkap (Operasional)</label>
                                        <textarea name="alamat" class="w-full px-4 py-3 bg-slate-50 rounded-xl text-[14px] font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-[#3b82f6]/30 transition-all custom-scroll" style="border: 1px solid #cbd5e1 !important;" rows="3"><?php echo $row['Alamat']; ?></textarea>
                                    </div>

                                    <div class="flex gap-3 pt-4 border-t border-slate-100">
                                        <button type="button" onclick="closeModal('modalEdit<?php echo $row['SupplierID']; ?>')" class="flex-1 py-3.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl font-bold text-[14px] transition-colors cursor-pointer">Batal</button>
                                        <button type="submit" name="edit" class="flex-1 py-3.5 bg-[#3b82f6] hover:bg-[#2563eb] text-white rounded-xl font-bold text-[14px] transition-colors shadow-md flex items-center justify-center gap-2 cursor-pointer">
                                            <i class="bi bi-check-circle"></i> Simpan Perubahan
                                        </button>
                                    </div>
                                </form>

                            </div>
                        </div>

                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="py-16 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <i class="bi bi-truck text-5xl mb-4 opacity-20"></i>
                                    <h6 class="text-[17px] font-bold text-slate-500 mb-1">Data supplier kosong.</h6>
                                    <p class="text-[14px] font-medium mb-0">Silakan tambahkan data referensi perusahaan pemasok ke dalam sistem.</p>
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
    // --- LOGIKA MODAL UMUM ---
    function openModal(modalID) {
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

    function closeModal(modalID) {
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