<?php 
session_start();
include '../../config/database.php'; 

// Cek Login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../modules/auth/login.php");
    exit;
}

// --- LOGIKA 1: TAMBAH PELANGGAN ---
if (isset($_POST['tambah'])) {
    $nama   = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $hp     = mysqli_real_escape_string($koneksi, $_POST['hp']);
    $email  = mysqli_real_escape_string($koneksi, $_POST['email']);
    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);

    // Cek Duplikat HP/Email
    $cek = mysqli_query($koneksi, "SELECT * FROM pelanggan WHERE NoHP='$hp' OR Email='$email'");
    if(mysqli_num_rows($cek) > 0){
        $_SESSION['toast_error'] = "Gagal! No HP atau Email sudah terdaftar sebelumnya.";
    } else {
        // PERBAIKAN: Generate Kode Pelanggan via PHP agar tidak bergantung pada Trigger SQL
        // Mencari angka terbesar dari KodePelanggan yang sudah ada
        $qKode = mysqli_query($koneksi, "SELECT MAX(CAST(SUBSTRING(KodePelanggan, 5) AS UNSIGNED)) as max_kode FROM pelanggan WHERE KodePelanggan LIKE 'PLG-%'");
        $rowKode = mysqli_fetch_assoc($qKode);
        $angkaTerakhir = $rowKode['max_kode'] ? (int)$rowKode['max_kode'] : 0;
        
        // Membuat kode baru dengan format PLG-00001
        $kodeBaru = "PLG-" . str_pad($angkaTerakhir + 1, 5, "0", STR_PAD_LEFT);

        // Memasukkan KodePelanggan baru ke dalam query INSERT
        $query = "INSERT INTO pelanggan (KodePelanggan, NamaPelanggan, NoHP, Email, Alamat) VALUES ('$kodeBaru', '$nama', '$hp', '$email', '$alamat')";
        
        if(mysqli_query($koneksi, $query)) {
            $_SESSION['toast_success'] = "Data pelanggan berhasil ditambahkan!";
        } else {
            $_SESSION['toast_error'] = "Terjadi kesalahan sistem saat menyimpan data.";
        }
    }
    header("Location: pelanggan.php");
    exit;
}

// --- LOGIKA 2: EDIT PELANGGAN ---
if (isset($_POST['edit'])) {
    $id     = $_POST['id_pelanggan'];
    $nama   = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $hp     = mysqli_real_escape_string($koneksi, $_POST['hp']);
    $email  = mysqli_real_escape_string($koneksi, $_POST['email']);
    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);

    $query = "UPDATE pelanggan SET NamaPelanggan='$nama', NoHP='$hp', Email='$email', Alamat='$alamat' WHERE PelangganID='$id'";
    
    if(mysqli_query($koneksi, $query)){
        $_SESSION['toast_success'] = "Data pelanggan berhasil diperbarui!";
    } else {
        $_SESSION['toast_error'] = "Gagal memperbarui data pelanggan.";
    }
    header("Location: pelanggan.php");
    exit;
}

// --- LOGIKA 3: HAPUS PELANGGAN ---
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    // Cek dulu apakah pelanggan ini pernah transaksi? Kalau ya, jangan dihapus (Database Integrity)
    $cekTrx = mysqli_query($koneksi, "SELECT * FROM transaksi WHERE PelangganID='$id'");
    if(mysqli_num_rows($cekTrx) > 0){
        $_SESSION['toast_error'] = "Akses Ditolak: Pelanggan ini memiliki riwayat transaksi di sistem.";
    } else {
        if(mysqli_query($koneksi, "DELETE FROM pelanggan WHERE PelangganID='$id'")){
            $_SESSION['toast_success'] = "Data pelanggan berhasil dihapus.";
        } else {
            $_SESSION['toast_error'] = "Gagal menghapus data pelanggan.";
        }
    }
    header("Location: pelanggan.php");
    exit;
}

// --- LOGIKA 4: PENCARIAN ---
$keyword = "";
$where = "";
if (isset($_GET['q'])) {
    $keyword = mysqli_real_escape_string($koneksi, $_GET['q']);
    $where = "WHERE NamaPelanggan LIKE '%$keyword%' OR KodePelanggan LIKE '%$keyword%' OR NoHP LIKE '%$keyword%'";
}

$queryData = "SELECT * FROM pelanggan $where ORDER BY PelangganID DESC";
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
    
    /* Custom Scrollbar untuk Tabel & Modal */
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
        <p class="text-[12px] font-medium text-slate-500 mb-6 leading-relaxed">Yakin ingin menghapus data pelanggan ini dari sistem secara permanen?</p>
        <div class="flex w-full gap-3">
            <button type="button" onclick="closeConfirmModal()" class="flex-1 py-2.5 rounded-xl font-bold text-[13px] text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors cursor-pointer">Batal</button>
            <a href="#" id="btnConfirmProceed" class="flex-1 py-2.5 rounded-xl font-bold text-[13px] text-white bg-red-500 hover:bg-red-600 shadow-md shadow-red-500/30 transition-all active:scale-95 cursor-pointer flex items-center justify-center">Ya, Hapus</a>
        </div>
    </div>
</div>

<div id="modalTambah" class="fixed inset-0 z-[1000] hidden items-center justify-center">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onclick="closeModal('modalTambah')"></div>
    
    <div class="relative bg-white rounded-[24px] shadow-2xl w-11/12 max-w-md p-6 lg:p-8 z-10 transform scale-95 transition-all duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)]">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h3 class="text-[18px] font-extrabold text-slate-800 leading-tight">Tambah Pelanggan Baru</h3>
                <p class="text-[12px] font-semibold text-slate-500 mb-0">Lengkapi data profil pelanggan di bawah ini.</p>
            </div>
            <button type="button" onclick="closeModal('modalTambah')" class="w-8 h-8 bg-slate-50 hover:bg-red-50 text-slate-500 hover:text-red-500 rounded-full flex items-center justify-center transition-colors">
                <i class="bi bi-x-lg font-bold pointer-events-none"></i>
            </button>
        </div>

        <div class="bg-blue-50 text-blue-600 px-4 py-2.5 rounded-xl text-[11px] font-bold flex items-center gap-2 mb-4" style="border: 1px solid #bfdbfe !important;">
            <i class="bi bi-info-circle-fill text-sm"></i> ID Pelanggan (PLG-XXXXX) akan di-generate otomatis.
        </div>

        <form method="POST" action="">
            <div class="mb-4">
                <label class="block text-[11px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">Nama Lengkap <span class="text-red-500">*</span></label>
                <input type="text" name="nama" class="w-full px-4 py-2.5 bg-slate-50 rounded-xl text-[14px] font-bold text-slate-800 focus:bg-white focus:ring-2 focus:ring-[#3b82f6]/30 transition-all" style="border: 1px solid #cbd5e1 !important;" placeholder="Contoh: Budi Santoso" required>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="col-span-1">
                    <label class="block text-[11px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">No. HP / WA</label>
                    <input type="text" name="hp" class="w-full px-4 py-2.5 bg-slate-50 rounded-xl text-[13px] font-bold text-slate-800 focus:bg-white focus:ring-2 focus:ring-[#3b82f6]/30 transition-all" style="border: 1px solid #cbd5e1 !important;" placeholder="0812...">
                </div>
                <div class="col-span-1">
                    <label class="block text-[11px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">Email Nota <span class="text-red-500">*</span></label>
                    <input type="email" name="email" class="w-full px-4 py-2.5 bg-slate-50 rounded-xl text-[13px] font-bold text-slate-800 focus:bg-white focus:ring-2 focus:ring-[#3b82f6]/30 transition-all" style="border: 1px solid #cbd5e1 !important;" placeholder="email@contoh.com" required>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-[11px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">Alamat Domisili</label>
                <textarea name="alamat" class="w-full px-4 py-3 bg-slate-50 rounded-xl text-[13px] font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-[#3b82f6]/30 transition-all custom-scroll" style="border: 1px solid #cbd5e1 !important;" rows="2" placeholder="Detail alamat..."></textarea>
            </div>

            <div class="flex gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('modalTambah')" class="flex-1 py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl font-bold text-[13px] transition-colors cursor-pointer">Batal</button>
                <button type="submit" name="tambah" class="flex-1 py-3 bg-[#3b82f6] hover:bg-[#2563eb] text-white rounded-xl font-bold text-[13px] transition-colors shadow-md flex items-center justify-center gap-2 cursor-pointer">
                    <i class="bi bi-save2-fill"></i> Simpan Data
                </button>
            </div>
        </form>

    </div>
</div>

<div class="main-content" style="padding: 32px 40px 16px 40px;">
    
    <div class="mb-4">
        <h2 class="text-[1.75rem] font-bold text-slate-800 mb-1 flex items-center gap-2" style="letter-spacing: -0.5px;">
            <i class="bi bi-people-fill text-[#3b82f6]"></i> Data Pelanggan
        </h2>
        <p class="text-[0.95rem] font-medium text-slate-500 mb-0">Manajemen profil pelanggan dan kontak member toko.</p>
    </div>

    <div class="w-full flex flex-wrap items-center justify-between gap-4 mb-6">
        
        <form action="" method="GET" class="flex flex-wrap items-center gap-3 m-0 w-full lg:w-auto">
            <div class="flex items-center bg-white rounded-xl shadow-sm overflow-hidden" style="border: 1px solid #cbd5e1 !important;">
                <input type="text" name="q" class="pl-4 pr-2 py-2.5 w-full sm:w-[260px] lg:w-[300px] text-[13px] font-semibold text-slate-700 bg-transparent focus:ring-2 focus:ring-[#3b82f6]/30 transition-all" placeholder="Cari Nama / ID / No. HP..." value="<?php echo htmlspecialchars($keyword); ?>" autocomplete="off">
                <button type="submit" class="px-4 py-2.5 bg-slate-50 border-l border-slate-200 text-slate-500 hover:text-[#3b82f6] hover:bg-blue-50 transition-colors cursor-pointer" title="Cari Data">
                    <i class="bi bi-search font-bold"></i>
                </button>
            </div>

            <?php if($keyword != ''): ?>
                <a href="pelanggan.php" class="flex items-center justify-center w-[42px] h-[42px] bg-red-50 text-red-500 rounded-xl shadow-sm hover:bg-red-500 hover:text-white transition-all cursor-pointer" title="Reset Filter" style="border: 1px solid #fecaca !important;">
                    <i class="bi bi-arrow-counterclockwise text-lg"></i>
                </a>
            <?php endif; ?>
        </form>

        <button type="button" onclick="openModal('modalTambah')" class="flex items-center justify-center gap-2 bg-[#3b82f6] hover:bg-[#2563eb] text-white px-6 py-2.5 rounded-xl font-bold text-[13px] transition-colors shadow-md w-full lg:w-auto cursor-pointer">
            <i class="bi bi-person-plus-fill"></i> Tambah Pelanggan
        </button>
    </div>

    <div class="bg-white rounded-[20px] shadow-sm border border-solid border-slate-100 overflow-hidden" style="box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);">
        <div class="overflow-x-auto table-scroll">
            <table class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="py-4 px-5 text-[12px] font-extrabold text-slate-500 uppercase tracking-wider">ID Pelanggan</th>
                        <th class="py-4 px-4 text-[12px] font-extrabold text-slate-500 uppercase tracking-wider">Nama Lengkap</th>
                        <th class="py-4 px-4 text-[12px] font-extrabold text-slate-500 uppercase tracking-wider">Kontak & Email</th>
                        <th class="py-4 px-4 text-[12px] font-extrabold text-slate-500 uppercase tracking-wider">Alamat Domisili</th>
                        <th class="py-4 px-5 text-[12px] font-extrabold text-slate-500 uppercase tracking-wider text-right">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if(mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr class="hover:bg-slate-50/50 transition-colors group">
                            <td class="py-4 px-5">
                                <span class="inline-block px-3 py-1.5 bg-slate-50 text-[#3b82f6] rounded-lg text-[12px] font-black border border-slate-200 shadow-sm">
                                    <?php echo $row['KodePelanggan'] ?? 'PLG-???'; ?>
                                </span>
                            </td>
                            <td class="py-4 px-4">
                                <span class="text-[14px] font-extrabold text-slate-800"><?php echo $row['NamaPelanggan']; ?></span>
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex flex-col gap-1">
                                    <span class="text-[12px] font-bold text-slate-700 flex items-center gap-1.5"><i class="bi bi-whatsapp text-emerald-500"></i> <?php echo $row['NoHP'] ? $row['NoHP'] : '-'; ?></span>
                                    <span class="text-[11px] font-semibold text-slate-400 flex items-center gap-1.5"><i class="bi bi-envelope"></i> <?php echo $row['Email']; ?></span>
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                <div class="text-[13px] font-medium text-slate-500 truncate max-w-[250px] lg:max-w-[300px]" title="<?php echo $row['Alamat']; ?>">
                                    <?php echo $row['Alamat'] ? $row['Alamat'] : '-'; ?>
                                </div>
                            </td>
                            <td class="py-4 px-5 text-right">
                                <div class="flex gap-2 justify-end opacity-70 group-hover:opacity-100 transition-opacity">
                                    
                                    <button type="button" onclick="openModal('modalEdit<?php echo $row['PelangganID']; ?>')" class="bg-white hover:bg-blue-50 text-slate-500 hover:text-[#3b82f6] w-8 h-8 rounded-lg text-[13px] font-bold transition-colors shadow-sm flex items-center justify-center border border-slate-200 hover:border-blue-300 cursor-pointer" title="Ubah Profil Pelanggan">
                                        <i class="bi bi-pencil-fill pointer-events-none"></i>
                                    </button>

                                    <button type="button" onclick="openConfirmModal('?hapus=<?php echo $row['PelangganID']; ?>')" class="bg-white hover:bg-red-50 text-slate-500 hover:text-red-500 w-8 h-8 rounded-lg text-[13px] font-bold transition-colors shadow-sm flex items-center justify-center border border-slate-200 hover:border-red-300 cursor-pointer" title="Hapus Pelanggan">
                                        <i class="bi bi-trash3-fill pointer-events-none"></i>
                                    </button>

                                </div>
                            </td>
                        </tr>

                        <div id="modalEdit<?php echo $row['PelangganID']; ?>" class="fixed inset-0 z-[1000] hidden items-center justify-center">
                            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onclick="closeModal('modalEdit<?php echo $row['PelangganID']; ?>')"></div>
                            
                            <div class="relative bg-white rounded-[24px] shadow-2xl w-11/12 max-w-md p-6 lg:p-8 z-10 transform scale-95 transition-all duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)] text-left">
                                <div class="flex justify-between items-center mb-6">
                                    <div>
                                        <h3 class="text-[18px] font-extrabold text-slate-800 leading-tight">Ubah Data Pelanggan</h3>
                                        <p class="text-[12px] font-semibold text-slate-500 mb-0">ID: <span class="text-[#3b82f6]"><?php echo $row['KodePelanggan'] ?? 'Baru'; ?></span></p>
                                    </div>
                                    <button type="button" onclick="closeModal('modalEdit<?php echo $row['PelangganID']; ?>')" class="w-8 h-8 bg-slate-50 hover:bg-red-50 text-slate-500 hover:text-red-500 rounded-full flex items-center justify-center transition-colors">
                                        <i class="bi bi-x-lg font-bold pointer-events-none"></i>
                                    </button>
                                </div>

                                <form method="POST" action="">
                                    <input type="hidden" name="id_pelanggan" value="<?php echo $row['PelangganID']; ?>">
                                    
                                    <div class="mb-4">
                                        <label class="block text-[11px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">Nama Lengkap <span class="text-red-500">*</span></label>
                                        <input type="text" name="nama" value="<?php echo $row['NamaPelanggan']; ?>" class="w-full px-4 py-2.5 bg-slate-50 rounded-xl text-[14px] font-bold text-slate-800 focus:bg-white focus:ring-2 focus:ring-[#3b82f6]/30 transition-all" style="border: 1px solid #cbd5e1 !important;" required>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4 mb-4">
                                        <div class="col-span-1">
                                            <label class="block text-[11px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">No. HP / WA</label>
                                            <input type="text" name="hp" value="<?php echo $row['NoHP']; ?>" class="w-full px-4 py-2.5 bg-slate-50 rounded-xl text-[13px] font-bold text-slate-800 focus:bg-white focus:ring-2 focus:ring-[#3b82f6]/30 transition-all" style="border: 1px solid #cbd5e1 !important;">
                                        </div>
                                        <div class="col-span-1">
                                            <label class="block text-[11px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">Email Nota <span class="text-red-500">*</span></label>
                                            <input type="email" name="email" value="<?php echo $row['Email']; ?>" class="w-full px-4 py-2.5 bg-slate-50 rounded-xl text-[13px] font-bold text-slate-800 focus:bg-white focus:ring-2 focus:ring-[#3b82f6]/30 transition-all" style="border: 1px solid #cbd5e1 !important;" required>
                                        </div>
                                    </div>

                                    <div class="mb-6">
                                        <label class="block text-[11px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">Alamat Domisili</label>
                                        <textarea name="alamat" class="w-full px-4 py-3 bg-slate-50 rounded-xl text-[13px] font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-[#3b82f6]/30 transition-all custom-scroll" style="border: 1px solid #cbd5e1 !important;" rows="2"><?php echo $row['Alamat']; ?></textarea>
                                    </div>

                                    <div class="flex gap-3 pt-4 border-t border-slate-100">
                                        <button type="button" onclick="closeModal('modalEdit<?php echo $row['PelangganID']; ?>')" class="flex-1 py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl font-bold text-[13px] transition-colors cursor-pointer">Batal</button>
                                        <button type="submit" name="edit" class="flex-1 py-3 bg-[#3b82f6] hover:bg-[#2563eb] text-white rounded-xl font-bold text-[13px] transition-colors shadow-md flex items-center justify-center gap-2 cursor-pointer">
                                            <i class="bi bi-check-circle"></i> Simpan Perubahan
                                        </button>
                                    </div>
                                </form>

                            </div>
                        </div>

                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="py-16 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <i class="bi bi-people text-5xl mb-4 opacity-20"></i>
                                    <h6 class="text-[16px] font-bold text-slate-500 mb-1">Data pelanggan kosong.</h6>
                                    <p class="text-[13px] font-medium mb-0">Silakan tambahkan data pelanggan baru ke dalam sistem.</p>
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