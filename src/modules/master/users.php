<?php 
session_start();
include '../../config/database.php'; 

// Cek Role Owner
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Owner') {
    header("Location: ../dashboard/index.php");
    exit;
}

// --- FUNGSI BANTUAN: VALIDASI PASSWORD ---
function isPasswordValid($pwd) {
    if (strlen($pwd) < 8) return "Password minimal 8 karakter!";
    if (!preg_match('/[A-Z]/', $pwd)) return "Password wajib mengandung huruf besar (A-Z)!";
    if (!preg_match('/[a-z]/', $pwd)) return "Password wajib mengandung huruf kecil (a-z)!";
    if (!preg_match('/[^a-zA-Z0-9]/', $pwd)) return "Password wajib mengandung minimal 1 simbol unik!";
    return "OK";
}

// --- LOGIKA 1: TAMBAH USER BARU ---
if (isset($_POST['tambah_user'])) {
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $user = mysqli_real_escape_string($koneksi, $_POST['username']);
    $raw_pass = $_POST['password']; // Ambil password mentah untuk divalidasi
    $role = $_POST['role'];

    // 1. Validasi Kekuatan Password
    $cekPass = isPasswordValid($raw_pass);
    if ($cekPass !== "OK") {
        $_SESSION['toast_error'] = "Pendaftaran Gagal: " . $cekPass;
        header("Location: users.php");
        exit;
    }

    // 2. Cek dulu apakah username sudah ada?
    $cekUser = mysqli_query($koneksi, "SELECT * FROM karyawan WHERE Username='$user'");
    if (mysqli_num_rows($cekUser) > 0) {
        $_SESSION['toast_error'] = "Pendaftaran Gagal: Username '$user' sudah digunakan oleh orang lain!";
    } else {
        $pass = md5($raw_pass); // Enkripsi jika lolos validasi
        $query = "INSERT INTO karyawan (NamaKaryawan, Username, Password, Role) VALUES ('$nama', '$user', '$pass', '$role')";
        if(mysqli_query($koneksi, $query)){
            $_SESSION['toast_success'] = "Pengguna baru berhasil didaftarkan ke sistem!";
        } else {
            $_SESSION['toast_error'] = "Terjadi kesalahan sistem saat menyimpan data pengguna.";
        }
    }
    header("Location: users.php");
    exit;
}

// --- LOGIKA 2: EDIT USER (UPDATE) ---
if (isset($_POST['update_user'])) {
    $id   = $_POST['id_user'];
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $user = mysqli_real_escape_string($koneksi, $_POST['username_baru']);
    $role = $_POST['role'];
    
    // Cek Password (diisi atau tidak)
    if(!empty($_POST['password_baru'])) {
        $raw_passBaru = $_POST['password_baru'];
        
        // Validasi Kekuatan Password Baru
        $cekPass = isPasswordValid($raw_passBaru);
        if ($cekPass !== "OK") {
            $_SESSION['toast_error'] = "Pembaruan Gagal: " . $cekPass;
            header("Location: users.php");
            exit;
        }

        $passBaru = md5($raw_passBaru); // Enkripsi jika lolos
        $queryUpdate = "UPDATE karyawan SET NamaKaryawan='$nama', Username='$user', Role='$role', Password='$passBaru' WHERE KaryawanID='$id'";
    } else {
        // Kalau password kosong, update nama, username & role saja
        $queryUpdate = "UPDATE karyawan SET NamaKaryawan='$nama', Username='$user', Role='$role' WHERE KaryawanID='$id'";
    }

    if(mysqli_query($koneksi, $queryUpdate)){
        $_SESSION['toast_success'] = "Profil kredensial pengguna berhasil diperbarui!";
    } else {
        $_SESSION['toast_error'] = "Pembaruan Gagal: Username '$user' mungkin sudah terpakai.";
    }
    header("Location: users.php");
    exit;
}

// --- LOGIKA 3: HAPUS USER (DENGAN PENGECEKAN RELASI TRANSAKSI) ---
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    
    // CEK RELASI: Apakah user ini punya riwayat di tabel transaksi?
    $cekTransaksi = mysqli_query($koneksi, "SELECT * FROM transaksi WHERE KaryawanID='$id'");
    
    if(mysqli_num_rows($cekTransaksi) > 0){
        // Tolak penghapusan untuk melindungi integritas data keuangan
        $_SESSION['toast_error'] = "Akses Ditolak: Pengguna ini tidak bisa dihapus karena memiliki riwayat transaksi aktif.";
    } else {
        // Hapus jika aman (belum pernah transaksi)
        if(mysqli_query($koneksi, "DELETE FROM karyawan WHERE KaryawanID='$id'")){
            $_SESSION['toast_success'] = "Akses pengguna berhasil dicabut dan dihapus permanen.";
        } else {
            $_SESSION['toast_error'] = "Terjadi kesalahan sistem saat menghapus data pengguna.";
        }
    }
    header("Location: users.php");
    exit;
}

// --- LOGIKA 4: PENCARIAN ---
$keyword = "";
$where = "";
if (isset($_GET['q'])) {
    $keyword = mysqli_real_escape_string($koneksi, $_GET['q']);
    $where = "WHERE NamaKaryawan LIKE '%$keyword%' OR Username LIKE '%$keyword%' OR Role LIKE '%$keyword%'";
}

$queryData = "SELECT * FROM karyawan $where ORDER BY Role DESC, NamaKaryawan ASC";
$qUser = mysqli_query($koneksi, $queryData); 

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

    /* CSS UNTUK ANIMASI SELURUH MODAL (Mutlak Diperlukan) */
    .modal-backdrop-show { opacity: 1 !important; }
    .modal-card-show { opacity: 1 !important; transform: scale(1) translateY(0) !important; }

    /* --- ANIMASI TOMBOL GETAR (SHAKE) --- */
    @keyframes shakeBtn {
        0%, 100% { transform: translateX(0); }
        20%, 60% { transform: translateX(-6px); }
        40%, 80% { transform: translateX(6px); }
    }
    .btn-shake { animation: shakeBtn 0.4s ease-in-out; }
</style>

<div id="toastSuccess" class="fixed top-6 left-1/2 z-[10001] flex items-center gap-3 px-4 py-3 bg-white/95 backdrop-blur-md shadow-lg border border-emerald-200 rounded-2xl custom-toast pointer-events-none">
    <div class="w-8 h-8 rounded-full bg-emerald-50 border border-emerald-100 flex items-center justify-center flex-shrink-0"><i class="bi bi-check-lg text-emerald-500 text-lg"></i></div>
    <span id="toastSuccessMsg" class="text-[14px] font-bold text-slate-700 tracking-wide pr-2">Berhasil!</span>
</div>

<div id="toastError" class="fixed top-6 left-1/2 z-[10001] flex items-center gap-3 px-4 py-3 bg-white/95 backdrop-blur-md shadow-lg border border-red-200 rounded-2xl custom-toast pointer-events-none">
    <div class="w-8 h-8 rounded-full bg-red-50 border border-red-100 flex items-center justify-center flex-shrink-0"><i class="bi bi-exclamation-triangle-fill text-red-500 text-lg"></i></div>
    <span id="toastErrorMsg" class="text-[14px] font-bold text-slate-700 tracking-wide pr-2">Gagal!</span>
</div>

<div id="customConfirmModal" class="fixed inset-0 z-[9999] hidden items-center justify-center">
    <div id="confirmBackdrop" class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm opacity-0 transition-opacity duration-300" onclick="closeConfirmModal()"></div>
    <div id="confirmCard" class="relative bg-white rounded-[24px] shadow-2xl w-11/12 max-w-xs p-6 flex flex-col items-center text-center transform scale-95 translate-y-4 opacity-0 transition-all duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)]">
        <div class="w-16 h-16 bg-rose-50 text-rose-500 rounded-full flex items-center justify-center mb-4 border-[6px] border-rose-50 shadow-sm relative">
            <i class="bi bi-person-x-fill text-3xl z-10"></i>
            <div class="absolute inset-0 rounded-full bg-rose-500/20 animate-ping"></div>
        </div>
        <h3 class="text-[17px] font-extrabold text-slate-800 mb-1">Cabut Akses Pengguna</h3>
        <p class="text-[13px] font-medium text-slate-500 mb-6 leading-relaxed">Tindakan ini akan menghapus permanen kredensial akses pengguna dari sistem. Lanjutkan?</p>
        <div class="flex w-full gap-3">
            <button type="button" onclick="closeConfirmModal()" class="flex-1 py-3 rounded-xl font-bold text-[14px] text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors cursor-pointer">Batal</button>
            <a href="#" id="btnConfirmProceed" class="flex-1 py-3 rounded-xl font-bold text-[14px] text-white bg-rose-500 hover:bg-rose-600 shadow-md shadow-rose-500/30 transition-all active:scale-95 cursor-pointer flex items-center justify-center">Ya, Hapus</a>
        </div>
    </div>
</div>

<div id="modalTambah" class="fixed inset-0 z-[9999] hidden items-center justify-center">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm opacity-0 transition-opacity duration-300" onclick="closeModal('modalTambah')"></div>
    <div class="relative bg-white rounded-[24px] shadow-2xl w-11/12 max-w-md p-6 lg:p-8 z-10 transform scale-95 translate-y-4 opacity-0 transition-all duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)]">
        <div class="flex justify-between items-center mb-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-[#3b82f6] border border-blue-100">
                    <i class="bi bi-person-plus-fill text-xl"></i>
                </div>
                <div>
                    <h4 class="text-[18px] font-extrabold text-slate-800 mb-0 leading-tight">Registrasi Baru</h4>
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Akses Karyawan</span>
                </div>
            </div>
            <button type="button" onclick="closeModal('modalTambah')" class="w-8 h-8 bg-slate-50 hover:bg-red-50 text-slate-500 hover:text-red-500 rounded-full flex items-center justify-center transition-colors cursor-pointer"><i class="bi bi-x-lg font-bold pointer-events-none"></i></button>
        </div>

        <form method="POST" action="" onsubmit="return validateFormSubmit(event, this, false)">
            <div class="mb-4">
                <label class="block text-[12px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">Nama Lengkap Personal</label>
                <input type="text" name="nama" class="w-full px-4 py-3 bg-slate-50 rounded-xl text-[15px] font-bold text-slate-800 focus:bg-white focus:ring-2 focus:ring-[#3b82f6]/30 transition-all" style="border: 1px solid #cbd5e1 !important;" placeholder="Contoh: Fazel Refano" required autocomplete="off">
            </div>
            
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="col-span-1">
                    <label class="block text-[12px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">Username</label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 font-black text-slate-400">@</span>
                        <input type="text" name="username" class="w-full pl-8 pr-3 py-3 bg-slate-50 rounded-xl text-[14px] font-bold text-[#3b82f6] focus:bg-white focus:ring-2 focus:ring-[#3b82f6]/30 transition-all" style="border: 1px solid #cbd5e1 !important;" placeholder="username" required autocomplete="new-password">
                    </div>
                </div>
                <div class="col-span-1">
                    <label class="block text-[12px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">Hak Akses</label>
                    <div class="relative">
                        <select name="role" class="w-full pl-3 pr-8 py-3 bg-slate-50 rounded-xl text-[14px] font-bold text-slate-700 focus:bg-white focus:ring-2 focus:ring-[#3b82f6]/30 transition-all appearance-none cursor-pointer" style="border: 1px solid #cbd5e1 !important;" required>
                            <option value="Kasir">Staff Kasir</option>
                            <option value="Owner">Administrator</option>
                        </select>
                        <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none font-bold"></i>
                    </div>
                </div>
            </div>
            
            <div class="mb-6">
                <label class="block text-[12px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">Password Pengaman</label>
                <input type="password" id="passAdd" name="password" class="pwd-input w-full px-4 py-3 bg-slate-50 rounded-xl text-[15px] font-bold text-slate-800 focus:bg-white focus:ring-2 focus:ring-[#3b82f6]/30 transition-all" style="border: 1px solid #cbd5e1 !important;" placeholder="••••••••" required autocomplete="new-password">
                
                <div id="tracker_passAdd" class="mt-3 bg-slate-50 p-3 rounded-xl border border-slate-200">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-extrabold text-slate-500 uppercase tracking-widest">Kekuatan Password</span>
                    </div>
                    <div class="h-1.5 w-full bg-slate-200 rounded-full mb-3 overflow-hidden">
                        <div class="strength-bar h-full w-0 transition-all duration-300 rounded-full"></div>
                    </div>
                    <ul class="text-[11px] font-semibold text-slate-400 space-y-1.5 m-0 p-0 list-none">
                        <li class="req-len flex items-center gap-1.5"><i class="bi bi-x-circle text-slate-300"></i> Min. 8 karakter</li>
                        <li class="req-up flex items-center gap-1.5"><i class="bi bi-x-circle text-slate-300"></i> Huruf Besar (A-Z)</li>
                        <li class="req-low flex items-center gap-1.5"><i class="bi bi-x-circle text-slate-300"></i> Huruf Kecil (a-z)</li>
                        <li class="req-sym flex items-center gap-1.5"><i class="bi bi-x-circle text-slate-300"></i> Simbol Unik (@, #, !, dll)</li>
                    </ul>
                </div>
            </div>

            <button type="submit" name="tambah_user" class="btn-submit w-full py-3.5 bg-[#3b82f6] hover:bg-[#2563eb] text-white rounded-xl font-extrabold text-[15px] tracking-wide transition-all shadow-lg hover:shadow-[#3b82f6]/30 flex items-center justify-center gap-2 cursor-pointer active:scale-95">
                <i class="bi bi-save2-fill"></i> Daftarkan Pengguna
            </button>
        </form>
    </div>
</div>

<div class="main-content" style="padding: 32px 40px 16px 40px;">
    
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6 pb-2 gap-4 border-b border-slate-200">
        <div>
            <h2 class="text-[1.75rem] font-bold text-slate-800 mb-1 flex items-center gap-2" style="letter-spacing: -0.5px;">
                <i class="bi bi-shield-lock-fill text-[#3b82f6]"></i> Manajemen Pengguna
            </h2>
            <p class="text-[0.95rem] font-medium text-slate-500 mb-0">Kelola profil karyawan, otorisasi jabatan, dan kredensial sistem.</p>
        </div>
        
        <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
            <form method="GET" action="" class="flex items-center bg-white rounded-xl shadow-sm overflow-hidden flex-1 lg:flex-none m-0" style="border: 1px solid #cbd5e1 !important;">
                <input type="text" name="q" class="pl-4 pr-2 py-2 w-full lg:w-[220px] text-[14px] font-bold text-slate-700 bg-transparent focus:ring-2 focus:ring-[#3b82f6]/30 transition-all" placeholder="Cari profil..." value="<?php echo htmlspecialchars($keyword); ?>" autocomplete="off">
                <button type="submit" class="px-4 py-2 bg-slate-50 border-l border-slate-200 text-slate-500 hover:text-[#3b82f6] hover:bg-blue-50 transition-colors cursor-pointer" title="Cari Data">
                    <i class="bi bi-search font-bold"></i>
                </button>
            </form>

            <?php if($keyword != ''): ?>
                <a href="users.php" class="flex items-center justify-center w-[38px] h-[38px] bg-red-50 text-red-500 rounded-xl shadow-sm hover:bg-red-500 hover:text-white transition-all cursor-pointer" title="Reset Filter" style="border: 1px solid #fecaca !important;">
                    <i class="bi bi-arrow-counterclockwise text-md"></i>
                </a>
            <?php endif; ?>

            <button type="button" onclick="openModal('modalTambah')" class="flex items-center justify-center gap-2 bg-[#3b82f6] hover:bg-[#2563eb] text-white px-5 py-2 rounded-xl font-bold text-[13px] transition-colors shadow-md cursor-pointer w-full lg:w-auto">
                <i class="bi bi-plus-lg"></i> Tambah Karyawan
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <?php 
        if(mysqli_num_rows($qUser) > 0):
            while($u = mysqli_fetch_assoc($qUser)):
                $isOwner = ($u['Role'] == 'Owner');
                
                // Tema Warna
                $avatarBg = $isOwner ? 'bg-indigo-100 text-indigo-600' : 'bg-emerald-100 text-emerald-600';
                $badgeClass = $isOwner ? 'bg-indigo-50 text-indigo-600 border-indigo-200' : 'bg-emerald-50 text-emerald-600 border-emerald-200';
                
                // Inisial Teks Sederhana
                $inisial = strtoupper(substr($u['NamaKaryawan'], 0, 1));
        ?>
        
        <div class="bg-white rounded-2xl p-4 border border-slate-200 hover:shadow-lg transition-shadow flex flex-col h-full">
            
            <div class="flex justify-between items-start mb-3.5 gap-2">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-full <?php echo $avatarBg; ?> flex items-center justify-center text-lg font-bold flex-shrink-0">
                        <?php echo $inisial; ?>
                    </div>
                    <div class="flex flex-col justify-center">
                        <h4 class="text-[15px] font-bold text-slate-800 leading-tight break-words m-0"><?php echo $u['NamaKaryawan']; ?></h4>
                        <p class="text-[11px] font-medium text-slate-500 mt-0.5 m-0">Sistem Akses</p>
                    </div>
                </div>
                <span class="text-[9px] font-bold px-2 py-0.5 rounded border uppercase tracking-widest flex-shrink-0 <?php echo $badgeClass; ?>">
                    <?php echo $u['Role']; ?>
                </span>
            </div>

            <div class="bg-slate-50 rounded-xl px-3.5 py-2.5 mb-4 border border-slate-100">
                <div class="grid grid-cols-[65px_1fr] gap-2 mb-1.5 items-center">
                    <span class="text-[11px] font-semibold text-slate-400">Username</span>
                    <span class="text-[13px] font-bold text-slate-700 break-all leading-tight">@<?php echo $u['Username']; ?></span>
                </div>
                <div class="grid grid-cols-[65px_1fr] gap-2 items-center">
                    <span class="text-[11px] font-semibold text-slate-400">Jabatan</span>
                    <span class="text-[13px] font-bold text-slate-700 leading-tight"><?php echo $u['Role']; ?></span>
                </div>
            </div>

            <div class="flex gap-2 mt-auto">
                <button type="button" onclick="openModal('modalEdit<?php echo $u['KaryawanID']; ?>')" class="flex-1 py-2 bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white rounded-xl text-[12px] font-bold transition-colors cursor-pointer flex justify-center items-center gap-1.5">
                    <i class="bi bi-pencil-square"></i> Edit
                </button>

                <?php if($u['Username'] != 'admin'): ?>
                    <button type="button" onclick="openConfirmModal('?hapus=<?php echo $u['KaryawanID']; ?>')" class="flex-1 py-2 bg-rose-500 text-white hover:bg-rose-600 rounded-xl text-[12px] font-bold transition-colors cursor-pointer flex justify-center items-center gap-1.5 shadow-sm">
                        <i class="bi bi-trash3-fill"></i> Hapus
                    </button>
                <?php else: ?>
                    <button type="button" class="flex-1 py-2 bg-slate-100 text-slate-400 rounded-xl text-[12px] font-bold cursor-not-allowed flex justify-center items-center gap-1.5" title="Admin Utama Tidak Dapat Dihapus">
                        <i class="bi bi-shield-lock-fill"></i> Root
                    </button>
                <?php endif; ?>
            </div>
            
        </div>
        <?php 
            endwhile;
        else:
        ?>
            <div class="col-span-full py-16 text-center bg-white rounded-2xl border border-slate-200">
                <i class="bi bi-people text-5xl text-slate-300 mb-4 block"></i>
                <h6 class="text-[16px] font-bold text-slate-600 mb-1">Tidak ada karyawan ditemukan.</h6>
                <p class="text-[13px] font-medium text-slate-400 mb-0">Coba ubah kata kunci pencarian Anda.</p>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php 
if(mysqli_num_rows($qUser) > 0) {
    mysqli_data_seek($qUser, 0);
    while($u = mysqli_fetch_assoc($qUser)):
        $isOwner = ($u['Role'] == 'Owner');
        $avatarBg = $isOwner ? 'bg-indigo-100 text-indigo-600' : 'bg-emerald-100 text-emerald-600';
        $inisial = strtoupper(substr($u['NamaKaryawan'], 0, 1));
?>
<div id="modalEdit<?php echo $u['KaryawanID']; ?>" class="fixed inset-0 z-[9999] hidden items-center justify-center">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm opacity-0 transition-opacity duration-300" onclick="closeModal('modalEdit<?php echo $u['KaryawanID']; ?>')"></div>
    <div class="relative bg-white rounded-[24px] shadow-2xl w-11/12 max-w-md p-6 lg:p-8 z-10 transform scale-95 translate-y-4 opacity-0 transition-all duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)]">
        <div class="flex justify-between items-center mb-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full <?php echo $avatarBg; ?> flex items-center justify-center text-xl font-bold flex-shrink-0">
                    <?php echo $inisial; ?>
                </div>
                <div>
                    <h3 class="text-[18px] font-extrabold text-slate-800 leading-tight">Ubah Kredensial</h3>
                    <p class="text-[12px] font-semibold text-slate-500 mb-0 leading-none">Atur profil: @<?php echo $u['Username']; ?></p>
                </div>
            </div>
            <button type="button" onclick="closeModal('modalEdit<?php echo $u['KaryawanID']; ?>')" class="w-8 h-8 bg-slate-50 hover:bg-red-50 text-slate-500 hover:text-red-500 rounded-full flex items-center justify-center transition-colors cursor-pointer"><i class="bi bi-x-lg font-bold pointer-events-none"></i></button>
        </div>

        <form method="POST" action="" onsubmit="return validateFormSubmit(event, this, true)">
            <input type="hidden" name="id_user" value="<?php echo $u['KaryawanID']; ?>">
            
            <div class="mb-4">
                <label class="block text-[12px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">Nama Lengkap Personal</label>
                <input type="text" name="nama" value="<?php echo $u['NamaKaryawan']; ?>" class="w-full px-4 py-3 bg-slate-50 rounded-xl text-[15px] font-bold text-slate-800 focus:bg-white focus:ring-2 focus:ring-[#3b82f6]/30 transition-all" style="border: 1px solid #cbd5e1 !important;" required autocomplete="off">
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="col-span-1">
                    <label class="block text-[12px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">Username</label>
                    <input type="text" name="username_baru" value="<?php echo $u['Username']; ?>" class="w-full px-4 py-3 bg-slate-50 rounded-xl text-[14px] font-bold text-[#3b82f6] focus:bg-white focus:ring-2 focus:ring-[#3b82f6]/30 transition-all" style="border: 1px solid #cbd5e1 !important;" required autocomplete="off">
                </div>
                <div class="col-span-1">
                    <label class="block text-[12px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">Jabatan</label>
                    <div class="relative">
                        <select name="role" class="w-full pl-4 pr-8 py-3 bg-slate-50 rounded-xl text-[14px] font-bold text-slate-700 focus:bg-white focus:ring-2 focus:ring-[#3b82f6]/30 transition-all appearance-none cursor-pointer" style="border: 1px solid #cbd5e1 !important;" required>
                            <option value="Kasir" <?php if($u['Role']=='Kasir') echo 'selected'; ?>>Staff Kasir</option>
                            <option value="Owner" <?php if($u['Role']=='Owner') echo 'selected'; ?>>Owner / Admin</option>
                        </select>
                        <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 font-bold pointer-events-none"></i>
                    </div>
                </div>
            </div>

            <div class="mb-6 p-4 rounded-xl border border-rose-100 bg-rose-50/30">
                <label class="block text-[11px] font-extrabold text-rose-500 uppercase tracking-wider mb-2"><i class="bi bi-shield-exclamation"></i> Reset Password (Opsional)</label>
                <input type="password" id="passEdit_<?php echo $u['KaryawanID']; ?>" name="password_baru" class="pwd-input w-full px-4 py-2.5 bg-white rounded-lg text-[14px] font-bold text-slate-800 focus:ring-2 focus:ring-rose-500/30 transition-all" style="border: 1px solid #fecaca !important;" placeholder="Ketik password baru..." autocomplete="new-password">
                <p class="text-[11px] font-semibold text-slate-400 mt-2 mb-0 leading-tight">Biarkan kosong jika password tidak diubah.</p>
                
                <div id="tracker_passEdit_<?php echo $u['KaryawanID']; ?>" class="mt-3 bg-white p-3 rounded-xl border border-rose-100 hidden">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-extrabold text-slate-500 uppercase tracking-widest">Kekuatan Password Baru</span>
                    </div>
                    <div class="h-1.5 w-full bg-slate-100 rounded-full mb-3 overflow-hidden">
                        <div class="strength-bar h-full w-0 transition-all duration-300 rounded-full"></div>
                    </div>
                    <ul class="text-[11px] font-semibold text-slate-400 space-y-1.5 m-0 p-0 list-none">
                        <li class="req-len flex items-center gap-1.5"><i class="bi bi-x-circle text-slate-300"></i> Min. 8 karakter</li>
                        <li class="req-up flex items-center gap-1.5"><i class="bi bi-x-circle text-slate-300"></i> Huruf Besar (A-Z)</li>
                        <li class="req-low flex items-center gap-1.5"><i class="bi bi-x-circle text-slate-300"></i> Huruf Kecil (a-z)</li>
                        <li class="req-sym flex items-center gap-1.5"><i class="bi bi-x-circle text-slate-300"></i> Simbol Unik (@, #, !, dll)</li>
                    </ul>
                </div>
            </div>

            <div class="flex gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('modalEdit<?php echo $u['KaryawanID']; ?>')" class="flex-1 py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl font-bold text-[14px] transition-colors cursor-pointer">Batal</button>
                <button type="submit" name="update_user" class="btn-submit flex-1 py-3 bg-[#3b82f6] hover:bg-[#2563eb] text-white rounded-xl font-bold text-[14px] transition-colors shadow-md flex items-center justify-center gap-2 cursor-pointer">
                    <i class="bi bi-check-circle"></i> Terapkan Update
                </button>
            </div>
        </form>
    </div>
</div>
<?php 
    endwhile;
}
?>

<script>
    // --- JS UNTUK TRACKER PASSWORD ---
    document.addEventListener("DOMContentLoaded", function() {
        const passInputs = document.querySelectorAll('.pwd-input');
        
        passInputs.forEach(input => {
            const trackerId = 'tracker_' + input.id;
            const tracker = document.getElementById(trackerId);
            
            if(!tracker) return;

            const bar = tracker.querySelector('.strength-bar');
            const reqLen = tracker.querySelector('.req-len');
            const reqUp = tracker.querySelector('.req-up');
            const reqLow = tracker.querySelector('.req-low');
            const reqSym = tracker.querySelector('.req-sym');

            // Cek apakah ini form Edit (optional) atau Tambah (wajib)
            const isOptional = input.id.includes('Edit');

            input.addEventListener('input', function() {
                const val = this.value;

                // Jika di form Edit dan dikosongkan, sembunyikan tracker
                if (isOptional) {
                    if (val.length === 0) {
                        tracker.classList.add('hidden');
                        input.dataset.isValid = 'true'; // Anggap valid karena opsional
                        return;
                    } else {
                        tracker.classList.remove('hidden');
                    }
                }

                // Logika Pengecekan
                const hasLen = val.length >= 8;
                const hasUp = /[A-Z]/.test(val);
                const hasLow = /[a-z]/.test(val);
                const hasSym = /[^a-zA-Z0-9]/.test(val); // Hanya simbol non-alfanumerik

                let score = 0;
                updateChecklist(reqLen, hasLen); if(hasLen) score++;
                updateChecklist(reqUp, hasUp); if(hasUp) score++;
                updateChecklist(reqLow, hasLow); if(hasLow) score++;
                updateChecklist(reqSym, hasSym); if(hasSym) score++;

                // Update Visual Bar
                bar.className = 'strength-bar h-full transition-all duration-300 rounded-full';
                if (score === 0) { bar.classList.add('w-0'); }
                else if (score === 1) { bar.classList.add('w-1/4', 'bg-rose-500'); }
                else if (score === 2) { bar.classList.add('w-2/4', 'bg-amber-400'); }
                else if (score === 3) { bar.classList.add('w-3/4', 'bg-blue-400'); }
                else if (score === 4) { bar.classList.add('w-full', 'bg-emerald-500'); }

                // Simpan status validasi di elemen
                input.dataset.isValid = (score === 4) ? 'true' : 'false';
            });

            // Inisialisasi awal khusus untuk modal Tambah
            if(!isOptional) { input.dataset.isValid = 'false'; }
        });
    });

    function updateChecklist(el, isValid) {
        const icon = el.querySelector('i');
        if (isValid) {
            icon.className = 'bi bi-check-circle-fill text-emerald-500';
            el.classList.add('text-slate-800');
            el.classList.remove('text-slate-400');
        } else {
            icon.className = 'bi bi-x-circle text-slate-300';
            el.classList.remove('text-slate-800');
            el.classList.add('text-slate-400');
        }
    }

    // --- JS UNTUK VALIDASI SUBMIT FORM & EFEK GETAR ---
    function validateFormSubmit(event, form, isEdit) {
        const passInput = form.querySelector('.pwd-input');
        if (!passInput) return true;

        // Boleh lewat kalau di Form Edit dan inputnya kosong (karena opsional)
        if (isEdit && passInput.value === '') {
            return true;
        }

        // Kalau belum valid semua syaratnya
        if (passInput.dataset.isValid !== 'true') {
            event.preventDefault(); // Hentikan proses submit ke PHP
            
            // Cari tombol submit lalu getarkan
            const btn = form.querySelector('.btn-submit');
            btn.classList.remove('btn-shake');
            void btn.offsetWidth; // Trigger reflow CSS
            btn.classList.add('btn-shake');

            // Munculkan Toast Error
            const tError = document.getElementById('toastError');
            document.getElementById('toastErrorMsg').innerText = "Password belum memenuhi syarat keamanan!";
            tError.classList.add('show');
            setTimeout(() => { tError.classList.remove('show'); }, 3500);

            return false;
        }
        return true;
    }


    // --- JS UNTUK MODAL MUNCUL/TUTUP ---
    function openModal(id) {
        const modal = document.getElementById(id);
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => {
            modal.children[0].classList.add('modal-backdrop-show');
            modal.children[1].classList.add('modal-card-show');
        }, 10);
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        
        // Reset form & tracker jika modal ditutup (Mencegah bug display)
        const form = modal.querySelector('form');
        if(form) { 
            form.reset(); 
            const tracker = modal.querySelector('[id^="tracker_"]');
            if(tracker) {
                const isEdit = tracker.id.includes('Edit');
                if(isEdit) tracker.classList.add('hidden');
                
                // Reset warna bar & tulisan
                tracker.querySelector('.strength-bar').className = 'strength-bar h-full w-0 transition-all duration-300 rounded-full';
                tracker.querySelectorAll('li').forEach(el => {
                    el.classList.remove('text-slate-800');
                    el.classList.add('text-slate-400');
                    el.querySelector('i').className = 'bi bi-x-circle text-slate-300';
                });
            }
        }

        modal.children[0].classList.remove('modal-backdrop-show');
        modal.children[1].classList.remove('modal-card-show');
        setTimeout(() => { 
            modal.classList.add('hidden'); 
            modal.classList.remove('flex'); 
        }, 300);
    }

    // --- JS UNTUK MODAL HAPUS KONFIRMASI ---
    function openConfirmModal(deleteUrl) {
        const modal = document.getElementById('customConfirmModal');
        const btnProceed = document.getElementById('btnConfirmProceed');
        
        btnProceed.href = deleteUrl; // Masukkan URL eksekusi PHP ke tombol
        
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

    // --- JS TOAST PHP ---
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