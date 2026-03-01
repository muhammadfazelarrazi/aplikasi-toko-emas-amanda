<?php 
session_start();
include '../../config/database.php'; 

// --- LOGIKA 1: TAMBAH SUPPLIER ---
if (isset($_POST['tambah'])) {
    $nama   = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $kontak = mysqli_real_escape_string($koneksi, $_POST['kontak']);
    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);

    $query = "INSERT INTO supplier (NamaSupplier, Kontak, Alamat) VALUES ('$nama', '$kontak', '$alamat')";
    
    if(mysqli_query($koneksi, $query)){
        echo "<script>alert('Supplier berhasil ditambahkan!'); window.location='supplier.php';</script>";
    } else {
        echo "<script>alert('Gagal menyimpan data.');</script>";
    }
}

// --- LOGIKA 2: EDIT SUPPLIER ---
if (isset($_POST['edit'])) {
    $id     = $_POST['id_supplier'];
    $nama   = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $kontak = mysqli_real_escape_string($koneksi, $_POST['kontak']);
    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);

    $query = "UPDATE supplier SET NamaSupplier='$nama', Kontak='$kontak', Alamat='$alamat' WHERE SupplierID='$id'";
    
    if(mysqli_query($koneksi, $query)){
        echo "<script>alert('Data supplier berhasil diupdate!'); window.location='supplier.php';</script>";
    } else {
        echo "<script>alert('Gagal update data.');</script>";
    }
}

// --- LOGIKA 3: HAPUS SUPPLIER ---
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    
    // Cek Relasi: Apakah supplier ini pernah kirim barang?
    $cekStok = mysqli_query($koneksi, "SELECT * FROM barang_stok WHERE SupplierID='$id'");
    
    if(mysqli_num_rows($cekStok) > 0){
        echo "<script>alert('GAGAL: Supplier ini tidak bisa dihapus karena terikat dengan data stok barang.'); window.location='supplier.php';</script>";
    } else {
        mysqli_query($koneksi, "DELETE FROM supplier WHERE SupplierID='$id'");
        echo "<script>alert('Supplier berhasil dihapus.'); window.location='supplier.php';</script>";
    }
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

<div class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Data Supplier</h2>
            <p class="text-muted mb-0">Daftar pemasok/distributor barang toko.</p>
        </div>
        
        <div class="d-flex gap-2">
            <form method="GET" class="d-flex">
                <div class="input-group">
                    <input type="text" name="q" class="form-control rounded-start-pill ps-3" placeholder="Cari Nama / Kontak..." value="<?php echo $keyword; ?>">
                    <button type="submit" class="btn btn-outline-primary rounded-end-pill px-3"><i class="bi bi-search"></i></button>
                </div>
            </form>
            <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
                <i class="bi bi-plus-lg me-2"></i> Tambah
            </button>
        </div>
    </div>

    <div class="card-custom">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="bg-light text-muted small">
                    <tr>
                        <th class="ps-4 rounded-start">Nama Supplier</th>
                        <th>Kontak</th>
                        <th>Alamat</th>
                        <th class="text-end rounded-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-dark">
                                <i class="bi bi-building me-2 text-primary"></i>
                                <?php echo $row['NamaSupplier']; ?>
                            </td>
                            <td><?php echo $row['Kontak']; ?></td>
                            <td class="text-muted small"><?php echo $row['Alamat']; ?></td>
                            <td class="text-end pe-4">
                                <button type="button" class="btn btn-sm btn-light text-primary rounded-circle" 
                                        data-bs-toggle="modal" data-bs-target="#modalEdit<?php echo $row['SupplierID']; ?>">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <a href="?hapus=<?php echo $row['SupplierID']; ?>" class="btn btn-sm btn-light text-danger rounded-circle" 
                                   onclick="return confirm('Hapus supplier ini?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>

                        <div class="modal fade" id="modalEdit<?php echo $row['SupplierID']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header border-0">
                                        <h5 class="modal-title fw-bold">Edit Supplier</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="id_supplier" value="<?php echo $row['SupplierID']; ?>">
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold">Nama Supplier</label>
                                                <input type="text" name="nama" class="form-control" value="<?php echo $row['NamaSupplier']; ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold">Kontak (HP/Telp)</label>
                                                <input type="text" name="kontak" class="form-control" value="<?php echo $row['Kontak']; ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold">Alamat</label>
                                                <textarea name="alamat" class="form-control" rows="2"><?php echo $row['Alamat']; ?></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-0">
                                            <button type="submit" name="edit" class="btn btn-primary rounded-pill px-4">Update</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center py-5 text-muted">Belum ada data supplier.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Tambah Supplier Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Supplier / PT</label>
                        <input type="text" name="nama" class="form-control" placeholder="Cth: PT. Emas Murni" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Kontak (HP/Telp)</label>
                        <input type="text" name="kontak" class="form-control" placeholder="021-xxxx">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Alamat</label>
                        <textarea name="alamat" class="form-control" rows="2" placeholder="Alamat kantor..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" name="tambah" class="btn btn-primary rounded-pill px-4">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../layouts/footer.php'; ?>