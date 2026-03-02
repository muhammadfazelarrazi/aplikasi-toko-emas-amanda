<?php 
session_start();
include '../../config/database.php'; 

// Inisialisasi Keranjang
if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

// --- AMBIL DATA PELANGGAN UNTUK DROPDOWN ---
$dataPelanggan = [];
$qPel = mysqli_query($koneksi, "SELECT * FROM pelanggan ORDER BY NamaPelanggan ASC");
while($p = mysqli_fetch_assoc($qPel)){
    $dataPelanggan[] = $p;
}

// --- LOGIKA 1: CARI BARANG & TAMPILAN DEFAULT ---
$hasilPencarian = null; 
$pesanError = "";
$isSearching = false; 

if (isset($_POST['cari_barang'])) {
    $isSearching = true;
    $kode = mysqli_real_escape_string($koneksi, $_POST['kode_input']);
    
    $queryCari = "SELECT bs.*, pk.NamaProduk, pk.Kadar, pk.Tipe, 
                  (SELECT HargaJualPerGram FROM riwayat_harga WHERE Kadar = pk.Kadar ORDER BY Tanggal DESC LIMIT 1) as HargaJualPerGram,
                  (SELECT Tanggal FROM riwayat_harga WHERE Kadar = pk.Kadar ORDER BY Tanggal DESC LIMIT 1) as TanggalHarga
                  FROM barang_stok bs
                  JOIN produk_katalog pk ON bs.ProdukKatalogID = pk.ProdukKatalogID
                  WHERE (bs.KodeBarang LIKE '%$kode%' OR pk.NamaProduk LIKE '%$kode%') 
                  AND bs.Status = 'Tersedia'
                  ORDER BY pk.NamaProduk ASC";
                  
    $resultCari = mysqli_query($koneksi, $queryCari);
    
    if (mysqli_num_rows($resultCari) > 0) {
        $hasilPencarian = $resultCari; 
    } else {
        $pesanError = "Barang dengan kata kunci tersebut tidak ditemukan atau stok kosong.";
    }
} else {
    $queryDefault = "SELECT bs.*, pk.NamaProduk, pk.Kadar, pk.Tipe, 
                     (SELECT HargaJualPerGram FROM riwayat_harga WHERE Kadar = pk.Kadar ORDER BY Tanggal DESC LIMIT 1) as HargaJualPerGram,
                     (SELECT Tanggal FROM riwayat_harga WHERE Kadar = pk.Kadar ORDER BY Tanggal DESC LIMIT 1) as TanggalHarga
                     FROM barang_stok bs
                     JOIN produk_katalog pk ON bs.ProdukKatalogID = pk.ProdukKatalogID
                     WHERE bs.Status = 'Tersedia'
                     ORDER BY bs.TanggalMasuk DESC LIMIT 50";
                     
    $hasilPencarian = mysqli_query($koneksi, $queryDefault);
}

// --- LOGIKA 2: TAMBAH KE KERANJANG ---
if (isset($_POST['tambah_keranjang'])) {
    $id = $_POST['id_barang'];
    $sudahAda = false;
    foreach ($_SESSION['keranjang'] as $item) {
        if ($item['BarangID'] == $id) { $sudahAda = true; break; }
    }
    
    if (!$sudahAda) {
        $itemBaru = [
            'BarangID' => $_POST['id_barang'],
            'Kode' => $_POST['kode_barang'],
            'Nama' => $_POST['nama_barang'],
            'Kadar' => $_POST['kadar'],
            'Berat' => $_POST['berat'],
            'HargaPerGram' => $_POST['harga_per_gram'],
            'HargaTotal' => $_POST['harga_total_item']
        ];
        $_SESSION['keranjang'][] = $itemBaru;
    }
}

// --- LOGIKA 3 & 4: HAPUS/RESET ---
if (isset($_GET['hapus'])) {
    $index = $_GET['hapus'];
    unset($_SESSION['keranjang'][$index]);
    $_SESSION['keranjang'] = array_values($_SESSION['keranjang']); 
    echo "<script>window.location='input_jual.php';</script>";
    exit;
}

if (isset($_GET['reset'])) {
    unset($_SESSION['keranjang']);
    echo "<script>window.location='input_jual.php';</script>";
    exit;
}

include '../../layouts/header.php'; 
include '../../layouts/sidebar.php'; 
?>

<style>
    .custom-scroll::-webkit-scrollbar { width: 5px; }
    .custom-scroll::-webkit-scrollbar-track { background: transparent; }
    .custom-scroll::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }
    .custom-scroll::-webkit-scrollbar-thumb:hover { background: #9ca3af; }
    
    input[type=number]::-webkit-inner-spin-button, 
    input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
    input[type=number] { -moz-appearance: textfield; }
</style>

<div class="main-content">
    
    <div class="row align-items-stretch mb-4"> 
        
        <div class="col-md-6 mb-4 mb-md-0">
            <div class="card-custom h-100 d-flex flex-column">
                <h5 class="fw-bold mb-4"><i class="bi bi-search me-2"></i> Cari & Pilih Barang</h5>
                
                <form method="POST" action="">
                    <div class="input-group mb-3 shadow-sm" style="border-radius: 8px; overflow: hidden;">
                        <input type="text" name="kode_input" class="form-control border-0 bg-light py-2" placeholder="Scan / Ketik Nama Barang..." autofocus value="<?php echo isset($_POST['kode_input']) ? htmlspecialchars($_POST['kode_input']) : ''; ?>">
                        <button type="submit" name="cari_barang" class="btn btn-primary px-4 fw-bold">Cari</button>
                    </div>
                </form>

                <?php if($pesanError): ?>
                    <div class="alert alert-danger small py-2"><i class="bi bi-exclamation-circle me-1"></i> <?php echo $pesanError; ?></div>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                    <span class="small fw-bold text-muted text-uppercase">
                        <?php echo $isSearching ? '<i class="bi bi-funnel-fill text-primary me-1"></i> Hasil Pencarian' : '<i class="bi bi-box-seam-fill text-success me-1"></i> Barang Tersedia (Ready Stock)'; ?>
                    </span>
                    <?php if($isSearching): ?>
                        <a href="input_jual.php" class="small text-decoration-none text-danger fw-bold"><i class="bi bi-x-circle me-1"></i>Reset</a>
                    <?php endif; ?>
                </div>

                <div class="flex-grow-1 position-relative mt-2" style="min-height: 400px;">
                    <div class="table-responsive custom-scroll position-absolute w-100 h-100 pe-2" style="top: 0; left: 0; overflow-y: auto;">
                        <?php if ($hasilPencarian && mysqli_num_rows($hasilPencarian) > 0): ?>
                            <table class="table table-hover align-middle small mb-0">
                                <thead class="bg-white sticky-top" style="z-index: 1;">
                                    <tr>
                                        <th class="py-2 border-bottom border-2">Produk</th>
                                        <th class="py-2 border-bottom border-2">Detail</th>
                                        <th class="py-2 border-bottom border-2 text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = mysqli_fetch_assoc($hasilPencarian)): 
                                        $hargaPerGram = $row['HargaJualPerGram'];
                                        $tglHarga = $row['TanggalHarga'];
                                        $isHariIni = ($tglHarga == date('Y-m-d'));
                                        
                                        $hargaDasar = $row['BeratGram'] * $hargaPerGram;
                                        $btnDisabled = ($hargaPerGram == NULL) ? 'disabled' : '';
                                        $btnText = ($hargaPerGram == NULL) ? 'Harga 0' : 'Tambah';
                                        $btnColor = ($hargaPerGram == NULL) ? 'btn-outline-secondary' : 'btn-success';
                                    ?>
                                    <tr>
                                        <td class="py-3">
                                            <span class="d-block fw-bold text-dark text-truncate" style="max-width: 150px;" title="<?php echo $row['NamaProduk']; ?>"><?php echo $row['NamaProduk']; ?></span>
                                            <span class="badge bg-light text-dark border font-monospace mt-1"><i class="bi bi-upc-scan"></i> <?php echo $row['KodeBarang']; ?></span>
                                        </td>
                                        <td class="py-3">
                                            <div class="text-muted mb-1" style="font-size: 0.75rem;">Kadar: <b class="text-dark"><?php echo $row['Kadar']; ?></b> | Berat: <b class="text-dark"><?php echo $row['BeratGram']; ?>g</b></div>
                                            <div class="fw-bold">
                                                <?php 
                                                if ($hargaPerGram) {
                                                    echo "<span class='text-primary'>Rp " . number_format($hargaDasar,0,',','.') . "</span>";
                                                    if (!$isHariIni) {
                                                        echo " <i class='bi bi-exclamation-circle-fill text-warning ms-1' title='Memakai harga terakhir: " . date('d M Y', strtotime($tglHarga)) . "' style='font-size: 0.8rem; cursor: help;'></i>";
                                                    }
                                                } else {
                                                    echo "<span class='text-danger small'><i class='bi bi-exclamation-triangle'></i> Harga belum diatur</span>";
                                                }
                                                ?>
                                            </div>
                                        </td>
                                        <td class="py-3 text-end">
                                            <form method="POST" action="">
                                                <input type="hidden" name="id_barang" value="<?php echo $row['BarangID']; ?>">
                                                <input type="hidden" name="kode_barang" value="<?php echo $row['KodeBarang']; ?>">
                                                <input type="hidden" name="nama_barang" value="<?php echo $row['NamaProduk']; ?>">
                                                <input type="hidden" name="kadar" value="<?php echo $row['Kadar']; ?>">
                                                <input type="hidden" name="berat" value="<?php echo $row['BeratGram']; ?>">
                                                <input type="hidden" name="harga_per_gram" value="<?php echo $hargaPerGram; ?>">
                                                <input type="hidden" name="harga_total_item" value="<?php echo $hargaDasar; ?>">
                                                
                                                <button type="submit" name="tambah_keranjang" class="btn btn-sm <?php echo $btnColor; ?> rounded-pill px-3 fw-bold shadow-sm" <?php echo $btnDisabled; ?>>
                                                    <i class="bi bi-plus-lg"></i> <?php echo $btnText; ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="text-center text-muted mt-5 pt-4">
                                <i class="bi bi-inbox fs-1 d-block mb-3 opacity-50"></i>
                                <h6 class="fw-bold">Belum Ada Barang Tersedia</h6>
                                <p class="small">Silakan input stok masuk terlebih dahulu.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4 mb-md-0">
            <div class="card-custom h-100 d-flex flex-column"> <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-cart3 text-primary me-2"></i> Keranjang Belanja</h5>
                    <?php if(!empty($_SESSION['keranjang'])): ?>
                        <a href="?reset=true" class="btn btn-sm btn-light text-danger border rounded-pill px-3 shadow-sm fw-bold" onclick="return confirm('Kosongkan keranjang?')">Reset Keranjang</a>
                    <?php endif; ?>
                </div>

                <div class="table-responsive bg-white border border-light-subtle rounded-3 mb-4 custom-scroll" style="max-height: 250px; overflow-y: auto;">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="bg-light sticky-top" style="z-index: 1;">
                            <tr>
                                <th class="py-2 ps-3 text-muted small text-uppercase">Barang</th>
                                <th class="py-2 text-muted small text-uppercase text-center">Berat</th>
                                <th class="py-2 text-end text-muted small text-uppercase">Harga</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $grandTotal = 0;
                            if (empty($_SESSION['keranjang'])) {
                                echo "<tr><td colspan='4' class='text-center py-5 text-muted small'><i class='bi bi-cart-x fs-2 d-block mb-2 opacity-50'></i>Keranjang masih kosong.</td></tr>";
                            } else {
                                foreach ($_SESSION['keranjang'] as $key => $item) {
                                    $grandTotal += $item['HargaTotal'];
                            ?>
                            <tr>
                                <td class="py-2 ps-3">
                                    <span class="fw-bold d-block text-dark"><?php echo $item['Nama']; ?></span>
                                    <small class="text-muted" style="font-size: 0.70rem"><?php echo $item['Kode']; ?> (<?php echo $item['Kadar']; ?>)</small>
                                </td>
                                <td class="py-2 text-center text-dark fw-medium"><?php echo $item['Berat']; ?>g</td>
                                <td class="py-2 text-end text-dark fw-bold">Rp <?php echo number_format($item['HargaTotal'], 0, ',', '.'); ?></td>
                                <td class="py-2 text-end pe-3">
                                    <a href="?hapus=<?php echo $key; ?>" class="btn btn-sm btn-light text-danger rounded-circle shadow-sm" title="Hapus"><i class="bi bi-trash3-fill"></i></a>
                                </td>
                            </tr>
                            <?php 
                                } 
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <form action="proses_jual.php" method="POST">
                    
                    <div class="bg-primary-subtle p-3 rounded-4 mb-4 border border-primary-subtle">
                        
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-primary-emphasis fw-medium">Subtotal Emas:</span>
                            <span class="fw-bold text-primary-emphasis">Rp <?php echo number_format($grandTotal, 0, ',', '.'); ?></span>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-primary-emphasis fw-medium">+ Biaya Pembuatan:</span>
                            <div class="input-group input-group-sm shadow-sm" style="width: 140px;">
                                <span class="input-group-text bg-white border-end-0">Rp</span>
                                <input type="number" id="inputOngkos" name="total_ongkos" class="form-control border-start-0 text-end fw-bold text-primary" placeholder="0" min="0" autocomplete="off">
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-danger fw-medium">- Potongan (Tawar):</span>
                            <div class="input-group input-group-sm shadow-sm" style="width: 140px;">
                                <span class="input-group-text bg-white border-end-0 text-danger">Rp</span>
                                <input type="number" id="inputDiskon" name="diskon" class="form-control border-start-0 text-end fw-bold text-danger" placeholder="0" min="0" autocomplete="off">
                            </div>
                        </div>
                        
                        <hr class="border-primary opacity-25 my-2">
                        
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <span class="text-primary-emphasis fw-bold text-uppercase" style="letter-spacing: 1px;">Total Bayar:</span>
                            <span class="fw-bold fs-4 text-primary" id="displayTotal">Rp <?php echo number_format($grandTotal, 0, ',', '.'); ?></span>
                        </div>

                    </div>

                    <h6 class="fw-bold mt-2 mb-3 text-uppercase small text-muted"><i class="bi bi-person-lines-fill me-2"></i>Data Pelanggan (Wajib)</h6>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark mb-1">Nama Pelanggan</label>
                            <input type="text" list="list_pelanggan" name="nama_pelanggan" id="inputNama" 
                                   class="form-control form-control-sm bg-light border-0" 
                                   placeholder="Ketik / Pilih Nama..." required autocomplete="off">
                            <datalist id="list_pelanggan">
                                <?php foreach($dataPelanggan as $p): ?>
                                    <option value="<?php echo htmlspecialchars($p['NamaPelanggan']); ?>"><?php echo htmlspecialchars($p['NoHP']); ?></option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark mb-1">No HP</label>
                            <input type="text" name="no_hp" id="inputHP" class="form-control form-control-sm bg-light border-0" placeholder="0812...">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label small fw-bold text-primary mb-1">Email <i class="bi bi-envelope-check ms-1"></i></label>
                            <input type="email" name="email_pelanggan" id="inputEmail" class="form-control form-control-sm border-primary" required placeholder="email@contoh.com">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-dark mb-1">Metode Pembayaran</label>
                        <select name="metode_bayar" class="form-select bg-light border-0 shadow-sm" required>
                            <?php 
                            $qMetode = mysqli_query($koneksi, "SELECT * FROM metode_pembayaran");
                            while($m = mysqli_fetch_assoc($qMetode)) {
                                echo "<option value='".$m['MetodeID']."'>".$m['NamaMetode']."</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2" <?php echo empty($_SESSION['keranjang']) ? 'disabled' : ''; ?>>
                        <i class="bi bi-check-circle-fill fs-5"></i> <span style="letter-spacing: 1px;">PROSES TRANSAKSI SEKARANG</span>
                    </button>
                </form>

            </div>
        </div>

    </div>
</div>

<script>
    // Logika Auto-Fill Pelanggan
    const dbPelanggan = <?php echo json_encode($dataPelanggan); ?>;
    const inputNama = document.getElementById('inputNama');
    const inputHP = document.getElementById('inputHP');
    const inputEmail = document.getElementById('inputEmail');

    inputNama.addEventListener('change', function() {
        const val = this.value;
        const found = dbPelanggan.find(p => p.NamaPelanggan === val);
        if(found) {
            inputHP.value = found.NoHP;
            inputEmail.value = found.Email;
        }
    });

    // Logika Kalkulator Real-Time (Total Bayar)
    const subtotalEmas = <?php echo $grandTotal; ?>;
    const inputOngkos = document.getElementById('inputOngkos');
    const inputDiskon = document.getElementById('inputDiskon');
    const displayTotal = document.getElementById('displayTotal');

    function hitungTotal() {
        let ongkos = parseFloat(inputOngkos.value) || 0;
        let diskon = parseFloat(inputDiskon.value) || 0;
        
        let total = subtotalEmas + ongkos - diskon;
        if(total < 0) total = 0;

        displayTotal.innerHTML = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
    }

    inputOngkos.addEventListener('input', hitungTotal);
    inputDiskon.addEventListener('input', hitungTotal);
</script>

<?php include '../../layouts/footer.php'; ?>