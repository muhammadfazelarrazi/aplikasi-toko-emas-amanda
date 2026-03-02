<?php 
session_start();
include '../../config/database.php'; 

// --- PROSES SIMPAN DATA (SAAT TOMBOL DISUBMIT) ---
$pesan = "";
if (isset($_POST['simpan'])) {
    $produkID = $_POST['produk_id'];
    $supplierID = $_POST['supplier_id'];
    $berat = $_POST['berat'];
    $hargaModal = $_POST['harga_modal'];
    $asal = 'Supplier'; // Default dari Supplier
    $tgl = date('Y-m-d');

    // 1. PROSES GENERATE KODE BARANG (BRG-0000X) DARI PHP
    $queryKode = mysqli_query($koneksi, "SELECT MAX(KodeBarang) as max_kode FROM barang_stok");
    $dataKode = mysqli_fetch_array($queryKode);
    $kodeTerbesar = $dataKode['max_kode'];

    if ($kodeTerbesar) {
        // Jika sudah ada data, ambil 5 digit terakhir lalu tambah 1
        $urutan = (int) substr($kodeTerbesar, 4, 5);
        $urutan++;
    } else {
        // Jika tabel masih kosong sama sekali
        $urutan = 1;
    }
    
    // Gabungkan huruf "BRG-" dengan angka yang sudah diformat 5 digit
    $kodeBarangBaru = "BRG-" . sprintf("%05s", $urutan);

    // 2. QUERY INSERT DENGAN KODE BARANG BARU
    $queryInsert = "INSERT INTO barang_stok 
                    (KodeBarang, ProdukKatalogID, SupplierID, BeratGram, HargaBeliModal, TanggalMasuk, Status, AsalBarang) 
                    VALUES ('$kodeBarangBaru', '$produkID', '$supplierID', '$berat', '$hargaModal', '$tgl', 'Tersedia', '$asal')";
    
    if (mysqli_query($koneksi, $queryInsert)) {
        // Jika sukses, redirect balik ke tabel data barang
        echo "<script>alert('Stok berhasil ditambahkan!'); window.location='data_barang.php';</script>";
    } else {
        $pesan = "<div class='alert alert-danger'>Gagal menyimpan: " . mysqli_error($koneksi) . "</div>";
    }
}

include '../../layouts/header.php'; 
include '../../layouts/sidebar.php'; 
?>

<div class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Input Barang Masuk</h2>
            <p class="text-muted mb-0">Formulir penerimaan stok emas baru dari Supplier.</p>
        </div>
        <a href="data_barang.php" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="bi bi-arrow-left me-2"></i> Kembali
        </a>
    </div>

    <?php echo $pesan; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card-custom">
                
                <form method="POST" action="">
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold text-muted small text-uppercase">Jenis Produk</label>
                        <select name="produk_id" class="form-select py-3 bg-light border-0" required>
                            <option value="">-- Pilih Produk Katalog --</option>
                            <?php 
                            // Ambil data dari tabel PRODUK_KATALOG
                            $qProduk = mysqli_query($koneksi, "SELECT * FROM produk_katalog ORDER BY NamaProduk ASC");
                            while($p = mysqli_fetch_assoc($qProduk)) {
                                echo "<option value='".$p['ProdukKatalogID']."'>".$p['NamaProduk']." (Kadar: ".$p['Kadar'].")</option>";
                            }
                            ?>
                        </select>
                        <div class="form-text">Jenis barang tidak ada? Tambahkan dulu di Master Katalog.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold text-muted small text-uppercase">Supplier / Pemasok</label>
                        <select name="supplier_id" class="form-select py-3 bg-light border-0" required>
                            <option value="">-- Pilih Supplier --</option>
                            <?php 
                            // Ambil data dari tabel SUPPLIER
                            $qSup = mysqli_query($koneksi, "SELECT * FROM supplier ORDER BY NamaSupplier ASC");
                            while($s = mysqli_fetch_assoc($qSup)) {
                                echo "<option value='".$s['SupplierID']."'>".$s['NamaSupplier']."</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-bold text-muted small text-uppercase">Berat (Gram)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="berat" class="form-control py-3 bg-light border-0" placeholder="0.00" required>
                                <span class="input-group-text bg-light border-0">gr</span>
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-bold text-muted small text-uppercase">Harga Beli (Modal)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0">Rp</span>
                                <input type="number" name="harga_modal" class="form-control py-3 bg-light border-0" placeholder="0" required>
                            </div>
                            <div class="form-text">Harga total kulakan untuk item ini.</div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="data_barang.php" class="btn btn-light px-4 rounded-pill">Batal</a>
                        <button type="submit" name="simpan" class="btn btn-primary px-5 rounded-pill fw-bold">
                            <i class="bi bi-save me-2"></i> Simpan Stok
                        </button>
                    </div>

                </form>

            </div>
        </div>

        <div class="col-md-4">
            <div class="alert alert-info border-0 shadow-sm">
                <h6 class="fw-bold"><i class="bi bi-info-circle me-2"></i> Info Sistem</h6>
                <p class="small mb-0">
                    Kode Barang (Nomor Seri) akan dibuat secara <b>Otomatis</b> oleh sistem setelah data disimpan.
                    <br><br>
                    Format Kode: <code>BRG-0000X</code>
                </p>
            </div>
        </div>
    </div>

</div>

<?php include '../../layouts/footer.php'; ?>