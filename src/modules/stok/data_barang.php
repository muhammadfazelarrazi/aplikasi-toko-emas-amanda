<?php 
session_start();
include '../../config/database.php'; 

// Cek Login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../modules/auth/login.php");
    exit;
}

// --- LOGIKA 1: UPDATE STOK (EDIT) ---
if (isset($_POST['edit_stok'])) {
    $id = $_POST['id_barang'];
    $produk_id = $_POST['produk_id']; // Baru: Bisa ganti produk
    $berat = $_POST['berat'];
    $status = $_POST['status'];
    
    // Update data stok termasuk ProdukKatalogID
    $queryUpdate = "UPDATE barang_stok SET ProdukKatalogID='$produk_id', BeratGram='$berat', Status='$status' WHERE BarangID='$id'";
    
    if(mysqli_query($koneksi, $queryUpdate)){
        echo "<script>alert('Data stok berhasil diperbarui!'); window.location='data_barang.php';</script>";
    } else {
        echo "<script>alert('Gagal update data.');</script>";
    }
}

// --- LOGIKA 2: HAPUS STOK ---
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $cekTrx = mysqli_query($koneksi, "SELECT * FROM detail_transaksi_barang WHERE BarangID='$id'");
    
    if(mysqli_num_rows($cekTrx) > 0) {
        echo "<script>alert('GAGAL: Barang ini sudah pernah bertransaksi. Tidak boleh dihapus.'); window.location='data_barang.php';</script>";
    } else {
        mysqli_query($koneksi, "DELETE FROM barang_stok WHERE BarangID='$id'");
        echo "<script>alert('Data stok berhasil dihapus.'); window.location='data_barang.php';</script>";
    }
}

include '../../layouts/header.php'; 
include '../../layouts/sidebar.php'; 

// --- PERSIAPAN DATA ---

// 1. Ambil Data Katalog (Untuk Dropdown di Modal Edit)
// Kita ambil sekali di atas biar gak berat query berulang-ulang di dalam loop
$dataKatalog = [];
$qKat = mysqli_query($koneksi, "SELECT * FROM produk_katalog ORDER BY NamaProduk ASC");
while($k = mysqli_fetch_assoc($qKat)){
    $dataKatalog[] = $k;
}

// 2. Logika Pencarian & Filter
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

// 3. Eksekusi Query Utama
$query = "SELECT bs.*, pk.NamaProduk, pk.Kadar, pk.Tipe, sup.NamaSupplier 
          FROM barang_stok bs
          JOIN produk_katalog pk ON bs.ProdukKatalogID = pk.ProdukKatalogID
          LEFT JOIN supplier sup ON bs.SupplierID = sup.SupplierID
          $whereClause
          $orderClause";

$result = mysqli_query($koneksi, $query);

// 4. Ambil Daftar Kadar untuk Filter
$qKadar = mysqli_query($koneksi, "SELECT DISTINCT Kadar FROM produk_katalog ORDER BY Kadar ASC");
?>

<div class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Data Stok Emas</h2>
            <p class="text-muted mb-0">Kelola persediaan barang fisik di toko.</p>
        </div>
        <a href="input_masuk.php" class="btn btn-primary rounded-pill px-4 shadow-sm">
            <i class="bi bi-plus-lg me-2"></i> Tambah Stok Baru
        </a>
    </div>

    <div class="card-custom mb-4 p-3">
        <form method="GET" action="">
            <div class="row g-2">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 ps-3"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Cari Kode / Nama Produk..." value="<?php echo $search; ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="kadar" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua Kadar</option>
                        <?php while($k = mysqli_fetch_assoc($qKadar)): ?>
                            <option value="<?php echo $k['Kadar']; ?>" <?php if($filter_kadar == $k['Kadar']) echo 'selected'; ?>>
                                <?php echo $k['Kadar']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="sort" class="form-select" onchange="this.form.submit()">
                        <option value="terbaru" <?php if($sort_by == 'terbaru') echo 'selected'; ?>>📅 Terbaru</option>
                        <option value="terlama" <?php if($sort_by == 'terlama') echo 'selected'; ?>>📅 Terlama</option>
                        <option value="berat_tinggi" <?php if($sort_by == 'berat_tinggi') echo 'selected'; ?>>⚖️ Berat (Berat->Ringan)</option>
                        <option value="berat_rendah" <?php if($sort_by == 'berat_rendah') echo 'selected'; ?>>⚖️ Berat (Ringan->Berat)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Terapkan</button>
                </div>
            </div>
        </form>
    </div>

    <div class="card-custom">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="bg-light text-muted small">
                    <tr>
                        <th class="ps-4 rounded-start">Kode Barang</th>
                        <th>Produk</th>
                        <th>Kadar</th>
                        <th>Berat</th>
                        <th>Asal</th>
                        <th>Tgl Masuk</th>
                        <th>Status</th>
                        <th class="text-end rounded-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-primary">
                                    <i class="bi bi-upc-scan me-1"></i> <?php echo $row['KodeBarang']; ?>
                                </td>
                                <td>
                                    <span class="d-block fw-bold text-dark"><?php echo $row['NamaProduk']; ?></span>
                                    <small class="text-muted"><?php echo $row['Tipe']; ?></small>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?php echo $row['Kadar']; ?></span></td>
                                <td class="fw-bold"><?php echo $row['BeratGram']; ?> gr</td>
                                <td>
                                    <small class="d-block text-muted"><?php echo $row['AsalBarang']; ?></small>
                                    <?php if($row['AsalBarang'] == 'Supplier'): ?>
                                        <small class="text-primary" style="font-size: 0.7rem;"><?php echo $row['NamaSupplier']; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted small"><?php echo date('d M Y', strtotime($row['TanggalMasuk'])); ?></td>
                                <td>
                                    <?php 
                                    if($row['Status'] == 'Tersedia') {
                                        echo '<span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill">Tersedia</span>';
                                    } elseif($row['Status'] == 'Terjual') {
                                        echo '<span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill">Terjual</span>';
                                    } else {
                                        echo '<span class="badge bg-warning-subtle text-warning px-3 py-2 rounded-pill">Buyback</span>';
                                    }
                                    ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-light text-primary rounded-circle" 
                                                data-bs-toggle="modal" data-bs-target="#modalEdit<?php echo $row['BarangID']; ?>" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        
                                        <a href="?hapus=<?php echo $row['BarangID']; ?>" class="btn btn-sm btn-light text-danger rounded-circle" 
                                           onclick="return confirm('Yakin ingin menghapus data stok ini?')" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            <div class="modal fade" id="modalEdit<?php echo $row['BarangID']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header border-0">
                                            <h5 class="modal-title fw-bold">Edit Stok: <?php echo $row['KodeBarang']; ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="id_barang" value="<?php echo $row['BarangID']; ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold">Produk / Katalog</label>
                                                    <select name="produk_id" class="form-select">
                                                        <?php foreach($dataKatalog as $kat): ?>
                                                            <option value="<?php echo $kat['ProdukKatalogID']; ?>" 
                                                                <?php if($kat['ProdukKatalogID'] == $row['ProdukKatalogID']) echo 'selected'; ?>>
                                                                <?php echo $kat['NamaProduk']; ?> (<?php echo $kat['Kadar']; ?>)
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div class="row">
                                                    <div class="col-6 mb-3">
                                                        <label class="form-label small fw-bold">Berat (Gram)</label>
                                                        <input type="number" step="0.01" name="berat" class="form-control" value="<?php echo $row['BeratGram']; ?>">
                                                    </div>
                                                    <div class="col-6 mb-3">
                                                        <label class="form-label small fw-bold">Status</label>
                                                        <select name="status" class="form-select">
                                                            <option value="Tersedia" <?php if($row['Status']=='Tersedia') echo 'selected'; ?>>Tersedia</option>
                                                            <option value="Terjual" <?php if($row['Status']=='Terjual') echo 'selected'; ?>>Terjual</option>
                                                            <option value="Buyback" <?php if($row['Status']=='Buyback') echo 'selected'; ?>>Buyback</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0">
                                                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" name="edit_stok" class="btn btn-primary rounded-pill px-4">Simpan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-search fs-1 d-block mb-2"></i>
                                Data tidak ditemukan.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php include '../../layouts/footer.php'; ?>