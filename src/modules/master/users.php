<?php 
session_start();
include '../../config/database.php'; 

// Cek Role Owner
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Owner') {
    header("Location: ../dashboard/index.php");
    exit;
}

// --- LOGIKA 1: TAMBAH USER BARU ---
if (isset($_POST['tambah_user'])) {
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $user = mysqli_real_escape_string($koneksi, $_POST['username']);
    $pass = md5($_POST['password']);
    $role = $_POST['role'];

    // Cek dulu apakah username sudah ada?
    $cekUser = mysqli_query($koneksi, "SELECT * FROM karyawan WHERE Username='$user'");
    if (mysqli_num_rows($cekUser) > 0) {
        echo "<script>alert('Gagal: Username sudah digunakan!');</script>";
    } else {
        $query = "INSERT INTO karyawan (NamaKaryawan, Username, Password, Role) VALUES ('$nama', '$user', '$pass', '$role')";
        if(mysqli_query($koneksi, $query)){
            echo "<script>alert('User berhasil ditambahkan'); window.location='users.php';</script>";
        } else {
            echo "<script>alert('Gagal menyimpan data.');</script>";
        }
    }
}

// --- LOGIKA 2: EDIT USER (UPDATE) ---
if (isset($_POST['update_user'])) {
    $id   = $_POST['id_user'];
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $user = mysqli_real_escape_string($koneksi, $_POST['username_baru']); // Ambil username baru
    $role = $_POST['role'];
    
    // Cek Password (diisi atau tidak)
    if(!empty($_POST['password_baru'])) {
        $passBaru = md5($_POST['password_baru']);
        $queryUpdate = "UPDATE karyawan SET NamaKaryawan='$nama', Username='$user', Role='$role', Password='$passBaru' WHERE KaryawanID='$id'";
    } else {
        // Kalau password kosong, update nama, username & role saja
        $queryUpdate = "UPDATE karyawan SET NamaKaryawan='$nama', Username='$user', Role='$role' WHERE KaryawanID='$id'";
    }

    if(mysqli_query($koneksi, $queryUpdate)){
        echo "<script>alert('Data pengguna berhasil diperbarui!'); window.location='users.php';</script>";
    } else {
        echo "<script>alert('Gagal: Username mungkin sudah dipakai orang lain.');</script>";
    }
}

// --- LOGIKA 3: HAPUS USER ---
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM karyawan WHERE KaryawanID='$id'");
    echo "<script>window.location='users.php';</script>";
}

include '../../layouts/header.php'; 
include '../../layouts/sidebar.php'; 
?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Kelola Pengguna</h2>
            <p class="text-muted mb-0">Manajemen akun & hak akses sistem.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card-custom mb-4 sticky-top" style="top: 20px; z-index: 1;">
                <h6 class="fw-bold mb-3">Tambah Akun Baru</h6>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label small">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Role / Jabatan</label>
                        <select name="role" class="form-select">
                            <option value="Kasir">Kasir</option>
                            <option value="Owner">Owner</option>
                        </select>
                    </div>
                    <button type="submit" name="tambah_user" class="btn btn-primary w-100 rounded-pill">Simpan</button>
                </form>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card-custom">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th>Nama</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $qUser = mysqli_query($koneksi, "SELECT * FROM karyawan ORDER BY Role DESC");
                            while($u = mysqli_fetch_assoc($qUser)):
                            ?>
                            <tr>
                                <td class="fw-bold"><?php echo $u['NamaKaryawan']; ?></td>
                                <td><?php echo $u['Username']; ?></td>
                                <td>
                                    <?php if($u['Role'] == 'Owner'): ?>
                                        <span class="badge bg-primary-subtle text-primary">Owner</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary">Kasir</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-light text-primary rounded-circle" 
                                                data-bs-toggle="modal" data-bs-target="#modalEdit<?php echo $u['KaryawanID']; ?>" title="Edit User">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <?php if($u['Username'] != 'admin'): // Cegah hapus admin utama ?>
                                            <a href="?hapus=<?php echo $u['KaryawanID']; ?>" class="btn btn-sm btn-light text-danger rounded-circle" onclick="return confirm('Hapus user ini?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>

                            <div class="modal fade" id="modalEdit<?php echo $u['KaryawanID']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header border-0">
                                            <h5 class="modal-title fw-bold">Edit Akun</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="id_user" value="<?php echo $u['KaryawanID']; ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold">Nama Lengkap</label>
                                                    <input type="text" name="nama" class="form-control" value="<?php echo $u['NamaKaryawan']; ?>" required>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold">Username</label>
                                                    <input type="text" name="username_baru" class="form-control" value="<?php echo $u['Username']; ?>" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold">Role</label>
                                                    <select name="role" class="form-select">
                                                        <option value="Kasir" <?php if($u['Role']=='Kasir') echo 'selected'; ?>>Kasir</option>
                                                        <option value="Owner" <?php if($u['Role']=='Owner') echo 'selected'; ?>>Owner</option>
                                                    </select>
                                                </div>

                                                <hr>
                                                <p class="small text-danger fw-bold mb-2">Ganti Password (Opsional)</p>
                                                <div class="mb-2">
                                                    <input type="text" name="password_baru" class="form-control border-danger" placeholder="Isi password baru disini...">
                                                    <div class="form-text">Biarkan kosong jika password tidak ingin diubah.</div>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0">
                                                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" name="update_user" class="btn btn-primary rounded-pill px-4">Simpan Perubahan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../layouts/footer.php'; ?>