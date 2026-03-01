<?php 
session_start();
include '../../config/database.php'; 

// --- LOGIKA 1: TAMBAH PELANGGAN ---
if (isset($_POST['tambah'])) {
    $nama   = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $hp     = mysqli_real_escape_string($koneksi, $_POST['hp']);
    $email  = mysqli_real_escape_string($koneksi, $_POST['email']);
    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);

    // Cek Duplikat HP/Email
    $cek = mysqli_query($koneksi, "SELECT * FROM pelanggan WHERE NoHP='$hp' OR Email='$email'");
    if(mysqli_num_rows($cek) > 0){
        echo "<script>alert('Gagal! No HP atau Email sudah terdaftar.');</script>";
    } else {
        // Trigger di SQL akan otomatis isi KodePelanggan
        $query = "INSERT INTO pelanggan (NamaPelanggan, NoHP, Email, Alamat) VALUES ('$nama', '$hp', '$email', '$alamat')";
        mysqli_query($koneksi, $query);
        echo "<script>alert('Pelanggan berhasil ditambahkan!'); window.location='pelanggan.php';</script>";
    }
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
        echo "<script>alert('Data pelanggan berhasil diupdate!'); window.location='pelanggan.php';</script>";
    } else {
        echo "<script>alert('Gagal update data.');</script>";
    }
}

// --- LOGIKA 3: HAPUS PELANGGAN ---
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    // Cek dulu apakah pelanggan ini pernah transaksi? Kalau ya, jangan dihapus (Database Integrity)
    $cekTrx = mysqli_query($koneksi, "SELECT * FROM transaksi WHERE PelangganID='$id'");
    if(mysqli_num_rows($cekTrx) > 0){
        echo "<script>alert('Gagal! Pelanggan ini memiliki riwayat transaksi. Data tidak boleh dihapus.'); window.location='pelanggan.php';</script>";
    } else {
        mysqli_query($koneksi, "DELETE FROM pelanggan WHERE PelangganID='$id'");
        echo "<script>alert('Pelanggan berhasil dihapus.'); window.location='pelanggan.php';</script>";
    }
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

<div class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Data Pelanggan</h2>
            <p class="text-muted mb-0">Manajemen data member toko.</p>
        </div>
        
        <div class="d-flex gap-2">
            <form method="GET" class="d-flex">
                <div class="input-group">
                    <input type="text" name="q" class="form-control rounded-start-pill ps-3" placeholder="Cari Nama / Kode / HP..." value="<?php echo $keyword; ?>">
                    <button type="submit" class="btn btn-outline-primary rounded-end-pill px-3"><i class="bi bi-search"></i></button>
                </div>
            </form>
            
            <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
                <i class="bi bi-person-plus-fill me-2"></i> Tambah
            </button>
        </div>
    </div>

    <div class="card-custom">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="bg-light text-muted small">
                    <tr>
                        <th class="ps-4 rounded-start">ID Pelanggan</th>
                        <th>Nama Lengkap</th>
                        <th>Kontak</th>
                        <th>Alamat</th>
                        <th class="text-end rounded-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td class="ps-4">
                                <span class="badge bg-light text-primary border border-primary-subtle">
                                    <?php echo $row['KodePelanggan'] ?? 'PLG-???'; ?>
                                </span>
                            </td>
                            <td class="fw-bold"><?php echo $row['NamaPelanggan']; ?></td>
                            <td>
                                <div class="small"><i class="bi bi-whatsapp text-success me-1"></i> <?php echo $row['NoHP']; ?></div>
                                <div class="small text-muted"><i class="bi bi-envelope me-1"></i> <?php echo $row['Email']; ?></div>
                            </td>
                            <td class="small text-muted text-truncate" style="max-width: 200px;">
                                <?php echo $row['Alamat'] ? $row['Alamat'] : '-'; ?>
                            </td>
                            <td class="text-end pe-4">
                                <button type="button" class="btn btn-sm btn-light text-primary rounded-circle" 
                                        data-bs-toggle="modal" data-bs-target="#modalEdit<?php echo $row['PelangganID']; ?>" title="Edit">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                
                                <a href="?hapus=<?php echo $row['PelangganID']; ?>" class="btn btn-sm btn-light text-danger rounded-circle" 
                                   onclick="return confirm('Yakin ingin menghapus pelanggan ini?')" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>

                        <div class="modal fade" id="modalEdit<?php echo $row['PelangganID']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header border-0">
                                        <h5 class="modal-title fw-bold">Edit Pelanggan</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <form method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="id_pelanggan" value="<?php echo $row['PelangganID']; ?>">
                                            
                                            <div class="mb-3">
                                                <label class="form-label small text-muted">ID System (Tidak bisa diubah)</label>
                                                <input type="text" class="form-control bg-light" value="<?php echo $row['KodePelanggan']; ?>" readonly>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold">Nama Lengkap</label>
                                                <input type="text" name="nama" class="form-control" value="<?php echo $row['NamaPelanggan']; ?>" required>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label small fw-bold">No HP / WA</label>
                                                    <input type="text" name="hp" class="form-control" value="<?php echo $row['NoHP']; ?>">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label small fw-bold">Email</label>
                                                    <input type="email" name="email" class="form-control" value="<?php echo $row['Email']; ?>" required>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold">Alamat</label>
                                                <textarea name="alamat" class="form-control" rows="2"><?php echo $row['Alamat']; ?></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-0">
                                            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" name="edit" class="btn btn-primary rounded-pill px-4">Simpan Perubahan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-people fs-1 d-block mb-2"></i>
                                Data pelanggan tidak ditemukan.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Tambah Pelanggan Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="bi bi-info-circle me-1"></i> ID Pelanggan (PLG-XXXXX) akan dibuat otomatis.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" placeholder="Cth: Budi Santoso" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">No HP / WA</label>
                            <input type="text" name="hp" class="form-control" placeholder="0812...">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="email@contoh.com" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Alamat</label>
                        <textarea name="alamat" class="form-control" rows="2" placeholder="Alamat domisili..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah" class="btn btn-primary rounded-pill px-4">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../layouts/footer.php'; ?>