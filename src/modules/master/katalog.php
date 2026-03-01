<?php 
session_start();
include '../../config/database.php'; 

// --- LOGIKA 1: TAMBAH KATALOG ---
if (isset($_POST['tambah'])) {
    $nama   = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $tipe   = mysqli_real_escape_string($koneksi, $_POST['tipe']);
    $kadar  = mysqli_real_escape_string($koneksi, $_POST['kadar']);
    $satuan = 'Gram'; // Default

    $query = "INSERT INTO produk_katalog (NamaProduk, Tipe, Kadar, Satuan) VALUES ('$nama', '$tipe', '$kadar', '$satuan')";
    
    if(mysqli_query($koneksi, $query)){
        echo "<script>alert('Produk baru berhasil ditambahkan!'); window.location='katalog.php';</script>";
    } else {
        echo "<script>alert('Gagal menyimpan data.');</script>";
    }
}

// --- LOGIKA 2: EDIT KATALOG ---
if (isset($_POST['edit'])) {
    $id     = $_POST['id_katalog'];
    $nama   = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $tipe   = mysqli_real_escape_string($koneksi, $_POST['tipe']);
    $kadar  = mysqli_real_escape_string($koneksi, $_POST['kadar']);

    $query = "UPDATE produk_katalog SET NamaProduk='$nama', Tipe='$tipe', Kadar='$kadar' WHERE ProdukKatalogID='$id'";
    
    if(mysqli_query($koneksi, $query)){
        echo "<script>alert('Data produk berhasil diupdate!'); window.location='katalog.php';</script>";
    } else {
        echo "<script>alert('Gagal update data.');</script>";
    }
}

// --- LOGIKA 3: HAPUS KATALOG ---
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    
    // Cek Relasi: Apakah produk ini sudah ada di Stok?
    $cekStok = mysqli_query($koneksi, "SELECT * FROM barang_stok WHERE ProdukKatalogID='$id'");
    
    if(mysqli_num_rows($cekStok) > 0){
        echo "<script>alert('GAGAL: Produk ini tidak bisa dihapus karena sudah ada stok yang menggunakan data ini.'); window.location='katalog.php';</script>";
    } else {
        mysqli_query($koneksi, "DELETE FROM produk_katalog WHERE ProdukKatalogID='$id'");
        echo "<script>alert('Produk berhasil dihapus.'); window.location='katalog.php';</script>";
    }
}

// --- LOGIKA 4: PENCARIAN ---
$keyword = "";
$where = "";
if (isset($_GET['q'])) {
    $keyword = mysqli_real_escape_string($koneksi, $_GET['q']);
    $where = "WHERE NamaProduk LIKE '%$keyword%' OR Tipe LIKE '%$keyword%' OR Kadar LIKE '%$keyword%'";
}

$queryData = "SELECT * FROM produk_katalog $where ORDER BY Tipe ASC, NamaProduk ASC";
$result = mysqli_query($koneksi, $queryData);

include '../../layouts/header.php'; 
include '../../layouts/sidebar.php'; 
?>

<div class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Master Katalog Produk</h2>
            <p class="text-muted mb-0">Daftar referensi jenis barang, tipe, dan kadar emas.</p>
        </div>
        
        <div class="d-flex gap-2">
            <form method="GET" class="d-flex">
                <div class="input-group">
                    <input type="text" name="q" class="form-control rounded-start-pill ps-3" placeholder="Cari Cincin / 17K..." value="<?php echo $keyword; ?>">
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
                        <th class="ps-4 rounded-start">Nama Produk</th>
                        <th>Tipe / Kategori</th>
                        <th>Kadar</th>
                        <th>Satuan</th>
                        <th class="text-end rounded-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-primary"><?php echo $row['NamaProduk']; ?></td>
                            <td><span class="badge bg-light text-dark border"><?php echo $row['Tipe']; ?></span></td>
                            <td>
                                <?php if($row['Kadar'] == '24K'): ?>
                                    <span class="badge bg-warning text-dark">24K (99%)</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">17K (70%)</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $row['Satuan']; ?></td>
                            <td class="text-end pe-4">
                                <button type="button" class="btn btn-sm btn-light text-primary rounded-circle" 
                                        data-bs-toggle="modal" data-bs-target="#modalEdit<?php echo $row['ProdukKatalogID']; ?>">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <a href="?hapus=<?php echo $row['ProdukKatalogID']; ?>" class="btn btn-sm btn-light text-danger rounded-circle" 
                                   onclick="return confirm('Hapus item katalog ini?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>

                        <div class="modal fade" id="modalEdit<?php echo $row['ProdukKatalogID']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header border-0">
                                        <h5 class="modal-title fw-bold">Edit Katalog</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="id_katalog" value="<?php echo $row['ProdukKatalogID']; ?>">
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold">Nama Produk</label>
                                                <input type="text" name="nama" class="form-control" value="<?php echo $row['NamaProduk']; ?>" required>
                                            </div>
                                            <div class="row">
                                                <div class="col-6 mb-3">
                                                    <label class="form-label small fw-bold">Tipe</label>
                                                    <select name="tipe" class="form-select">
                                                        <option value="Cincin" <?php if($row['Tipe']=='Cincin') echo 'selected'; ?>>Cincin</option>
                                                        <option value="Kalung" <?php if($row['Tipe']=='Kalung') echo 'selected'; ?>>Kalung</option>
                                                        <option value="Gelang" <?php if($row['Tipe']=='Gelang') echo 'selected'; ?>>Gelang</option>
                                                        <option value="Anting" <?php if($row['Tipe']=='Anting') echo 'selected'; ?>>Anting</option>
                                                        <option value="Logam Mulia" <?php if($row['Tipe']=='Logam Mulia') echo 'selected'; ?>>Logam Mulia</option>
                                                    </select>
                                                </div>
                                                <div class="col-6 mb-3">
                                                    <label class="form-label small fw-bold">Kadar</label>
                                                    <select name="kadar" class="form-select">
                                                        <option value="17K" <?php if($row['Kadar']=='17K') echo 'selected'; ?>>17K (Tua)</option>
                                                        <option value="24K" <?php if($row['Kadar']=='24K') echo 'selected'; ?>>24K (Murni)</option>
                                                        <option value="9K" <?php if($row['Kadar']=='9K') echo 'selected'; ?>>9K (Muda)</option>
                                                    </select>
                                                </div>
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
                        <tr><td colspan="5" class="text-center py-5 text-muted">Belum ada data katalog.</td></tr>
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
                <h5 class="modal-title fw-bold">Tambah Katalog Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Produk</label>
                        <input type="text" name="nama" class="form-control" placeholder="Cth: Cincin Polos 2 Gram" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold">Tipe</label>
                            <select name="tipe" class="form-select">
                                <option value="Cincin">Cincin</option>
                                <option value="Kalung">Kalung</option>
                                <option value="Gelang">Gelang</option>
                                <option value="Anting">Anting</option>
                                <option value="Logam Mulia">Logam Mulia</option>
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold">Kadar</label>
                            <select name="kadar" class="form-select">
                                <option value="17K">17K (Tua)</option>
                                <option value="24K">24K (Murni)</option>
                                <option value="9K">9K (Muda)</option>
                            </select>
                        </div>
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