<?php 
session_start();
include '../../config/database.php'; 

$pesanErrorForm = ""; // Variabel penampung pesan error database

// --- PROSES SIMPAN BUYBACK ---
if (isset($_POST['simpan_buyback'])) {
    // Tangkap data dengan pengaman (fallback) jika kosong
    $pelangganID = $_POST['pelanggan_id'] ?? ''; 
    $kasirID     = $_SESSION['user_id'] ?? 1; // Fallback ke KaryawanID 1 jika session terlepas
    $kodeBarang  = $_POST['kode_barang'] ?? ''; 
    $produkID    = $_POST['produk_id'] ?? '';
    $tgl         = date('Y-m-d H:i:s');

    // Ubah koma menjadi titik agar kalkulasi angka aman
    $beratRaw    = str_replace(',', '.', $_POST['berat_sekarang'] ?? '0');
    $berat       = (float) $beratRaw;
    $hargaDeal   = (int) ($_POST['harga_deal'] ?? 0); 

    mysqli_begin_transaction($koneksi);

    try {
        // Validasi Anti-Tembus
        if(empty($pelangganID) || empty($produkID) || empty($kodeBarang)) {
            throw new Exception("Data Pelanggan atau Barang tidak boleh kosong!");
        }
        if($berat <= 0 || $hargaDeal <= 0) {
            throw new Exception("Berat dan Harga Deal harus lebih dari 0!");
        }

        // 1. Simpan Transaksi Utama
        $qHead = "INSERT INTO transaksi (PelangganID, KaryawanID, TanggalWaktu, TipeTransaksi, TotalTransaksi) 
                  VALUES ('$pelangganID', '$kasirID', '$tgl', 'Buyback', '$hargaDeal')";
        if(!mysqli_query($koneksi, $qHead)) throw new Exception("Tabel Transaksi: " . mysqli_error($koneksi));
        $trxID = mysqli_insert_id($koneksi);

        // 2. PERBAIKAN LOGIKA: Cek apakah KodeBarang sudah ada di database
        $cekStok = mysqli_query($koneksi, "SELECT BarangID FROM barang_stok WHERE KodeBarang = '$kodeBarang'");
        
        if (mysqli_num_rows($cekStok) > 0) {
            // JIKA BARANG SUDAH ADA (Emas Toko Sendiri Pulang Kembali) -> Lakukan UPDATE
            $rowStok = mysqli_fetch_assoc($cekStok);
            $barangID = $rowStok['BarangID'];
            
            $qStok = "UPDATE barang_stok 
                      SET BeratGram = '$berat', 
                          HargaBeliModal = '$hargaDeal', 
                          TanggalMasuk = CURDATE(), 
                          Status = 'Tersedia', 
                          AsalBarang = 'Buyback' 
                      WHERE BarangID = '$barangID'";
            if(!mysqli_query($koneksi, $qStok)) throw new Exception("Gagal Update Stok: " . mysqli_error($koneksi));
            
        } else {
            // JIKA BARANG BELUM ADA (Misal: Terima emas dari toko lain) -> Lakukan INSERT
            $qStok = "INSERT INTO barang_stok (KodeBarang, ProdukKatalogID, BeratGram, HargaBeliModal, TanggalMasuk, Status, AsalBarang)
                      VALUES ('$kodeBarang', '$produkID', '$berat', '$hargaDeal', CURDATE(), 'Tersedia', 'Buyback')";
            if(!mysqli_query($koneksi, $qStok)) throw new Exception("Gagal Insert Stok Baru: " . mysqli_error($koneksi));
            $barangID = mysqli_insert_id($koneksi);
        }

        // 3. Simpan Detail Transaksi
        $qDetail = "INSERT INTO detail_transaksi_barang (TransaksiID, BarangID, HargaSatuanSaatItu) 
                    VALUES ('$trxID', '$barangID', '$hargaDeal')";
        if(!mysqli_query($koneksi, $qDetail)) throw new Exception("Tabel Detail: " . mysqli_error($koneksi));

        // 4. Simpan Pembayaran
        $qBayar = "INSERT INTO pembayaran (TransaksiID, MetodeID, JumlahBayar) 
                   VALUES ('$trxID', 1, '$hargaDeal')";
        if(!mysqli_query($koneksi, $qBayar)) throw new Exception("Tabel Pembayaran: " . mysqli_error($koneksi));

        // Jika semua sukses, kunci database dan pindah halaman
        mysqli_commit($koneksi);
        header("Location: riwayat.php");
        exit;

    } catch (Exception $e) {
        // Jika ada error, batalkan dan tampilkan di banner merah
        mysqli_rollback($koneksi);
        $pesanErrorForm = $e->getMessage();
    }
}

// --- LOGIKA AUTO-FILL DARI URL (?id=...) ---
$def_pelanggan = '';
$def_produk    = '';
$def_kode      = '';
$def_berat     = '';
$def_kadar     = '';
$def_harga_beli_awal = 0;
$def_harga_buyback_gram = 0;

$is_autofill   = false;
$trx_id_url    = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($trx_id_url > 0) {
    $qAuto = mysqli_query($koneksi, "
        SELECT t.PelangganID, b.ProdukKatalogID, b.KodeBarang, b.BeratGram, dt.HargaSatuanSaatItu, pk.Kadar,
               (SELECT HargaBeliPerGram FROM riwayat_harga WHERE Kadar = pk.Kadar ORDER BY Tanggal DESC LIMIT 1) as HargaBuybackSekarang
        FROM transaksi t
        JOIN detail_transaksi_barang dt ON t.TransaksiID = dt.TransaksiID
        JOIN barang_stok b ON dt.BarangID = b.BarangID
        JOIN produk_katalog pk ON b.ProdukKatalogID = pk.ProdukKatalogID
        WHERE t.TransaksiID = '$trx_id_url' AND b.Status = 'Terjual'
        LIMIT 1
    ");

    if ($rowAuto = mysqli_fetch_assoc($qAuto)) {
        $def_pelanggan = $rowAuto['PelangganID'];
        $def_produk    = $rowAuto['ProdukKatalogID'];
        $def_kode      = $rowAuto['KodeBarang'];
        $def_berat     = $rowAuto['BeratGram'];
        $def_kadar     = $rowAuto['Kadar'];
        $def_harga_beli_awal = $rowAuto['HargaSatuanSaatItu'];
        $def_harga_buyback_gram = $rowAuto['HargaBuybackSekarang'] ?? 0;
        $is_autofill   = true;
    }
}

include '../../layouts/header.php'; 
include '../../layouts/sidebar.php'; 
?>

<style>
    /* Merapikan panah naik turun di input angka */
    input[type=number]::-webkit-inner-spin-button, 
    input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
    input[type=number] { -moz-appearance: textfield; }
</style>

<div class="main-content">
    <h2 class="fw-bold mb-4">Input Transaksi Buyback (Beli Kembali)</h2>
    
    <div class="card-custom">
        <form method="POST" action="" novalidate onsubmit="return pastikanIsi()">
            
            <?php if(!empty($pesanErrorForm)): ?>
                <div class="alert alert-danger shadow-sm border-0 d-flex align-items-center mb-4 rounded-4">
                    <i class="bi bi-exclamation-triangle-fill fs-3 me-3 text-danger"></i>
                    <div>
                        <h6 class="fw-bold mb-1 text-danger">Gagal Menyimpan Transaksi!</h6>
                        <small class="text-danger-emphasis">Penyebab: <b><?php echo $pesanErrorForm; ?></b>. Silakan periksa kembali data atau hubungi admin.</small>
                    </div>
                </div>
            <?php endif; ?>

            <?php if($is_autofill): ?>
                <div class="alert alert-primary shadow-sm border-0 d-flex align-items-center mb-4 rounded-4" style="background-color: #e7f1ff; color: #0d6efd;">
                    <i class="bi bi-magic fs-3 me-3"></i>
                    <div>
                        <h6 class="fw-bold mb-1">Data Terisi Otomatis!</h6>
                        <small>Data ditarik dari Surat Emas <b>#TRX-<?php echo sprintf("%04d", $trx_id_url); ?></b>. Silakan periksa penyusutan berat dan tentukan <b>Harga Deal</b>.</small>
                    </div>
                </div>

                <div class="bg-primary-subtle p-3 rounded-4 mb-4 border border-primary-subtle">
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-primary-emphasis"><i class="bi bi-clock-history me-1"></i> Harga Saat Beli Dulu:</span>
                        <span class="fw-bold text-primary-emphasis">Rp <?php echo number_format($def_harga_beli_awal, 0, ',', '.'); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-primary-emphasis"><i class="bi bi-graph-up-arrow me-1"></i> Acuan Harga Buyback (Kadar <?php echo $def_kadar; ?>):</span>
                        <span class="fw-bold text-primary-emphasis">
                            <?php echo ($def_harga_buyback_gram > 0) ? 'Rp ' . number_format($def_harga_buyback_gram, 0, ',', '.') . ' / gram' : '<span class="text-danger">Belum diatur</span>'; ?>
                        </span>
                    </div>
                    
                    <hr class="border-primary opacity-25 my-2">
                    
                    <div class="d-flex justify-content-between align-items-center mt-1">
                        <span class="text-primary-emphasis fw-bold text-uppercase" style="letter-spacing: 1px;">Estimasi Sistem:</span>
                        <div class="text-end d-flex align-items-center gap-3">
                            <span class="fw-bold fs-4 text-primary" id="displayEstimasi">Rp 0</span>
                            <button type="button" class="btn btn-sm btn-primary rounded-pill shadow-sm" onclick="salinEstimasi()" title="Salin ke Harga Deal">
                                <i class="bi bi-box-arrow-in-down"></i> Gunakan
                            </button>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning small border-0 shadow-sm mb-4 rounded-4">
                    <i class="bi bi-info-circle me-1"></i> Fitur ini digunakan saat Toko membeli kembali emas dari Pelanggan secara manual.
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label small fw-bold">
                        Pilih Pelanggan 
                        <?php if($is_autofill) echo '<i class="bi bi-lock-fill text-muted ms-1" title="Terkunci"></i>'; ?>
                    </label>
                    <select name="<?php echo $is_autofill ? 'pelanggan_dummy' : 'pelanggan_id'; ?>" class="form-select <?php echo $is_autofill ? 'bg-light text-muted' : ''; ?>" <?php echo $is_autofill ? 'disabled' : 'required'; ?>>
                        <option value="">-- Cari Pelanggan --</option>
                        <?php 
                        $qPel = mysqli_query($koneksi, "SELECT * FROM pelanggan");
                        while($p = mysqli_fetch_assoc($qPel)){
                            $selected = ($p['PelangganID'] == $def_pelanggan) ? 'selected' : '';
                            echo "<option value='".$p['PelangganID']."' $selected>".$p['NamaPelanggan']." - ".$p['NoHP']."</option>";
                        }
                        ?>
                    </select>
                    <?php if($is_autofill): ?>
                        <input type="hidden" name="pelanggan_id" value="<?php echo $def_pelanggan; ?>">
                    <?php else: ?>
                        <div class="form-text">Pelanggan belum ada? <a href="../master/pelanggan.php">Tambah disini</a></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label small fw-bold">
                        Jenis Barang (Katalog)
                        <?php if($is_autofill) echo '<i class="bi bi-lock-fill text-muted ms-1" title="Terkunci"></i>'; ?>
                    </label>
                    <select name="<?php echo $is_autofill ? 'produk_dummy' : 'produk_id'; ?>" class="form-select <?php echo $is_autofill ? 'bg-light text-muted' : ''; ?>" <?php echo $is_autofill ? 'disabled' : 'required'; ?>>
                        <option value="">-- Pilih Jenis Barang --</option>
                        <?php 
                        $qKat = mysqli_query($koneksi, "SELECT * FROM produk_katalog");
                        while($k = mysqli_fetch_assoc($qKat)){
                            $selected = ($k['ProdukKatalogID'] == $def_produk) ? 'selected' : '';
                            echo "<option value='".$k['ProdukKatalogID']."' $selected>".$k['NamaProduk']." (".$k['Kadar'].")</option>";
                        }
                        ?>
                    </select>
                    <?php if($is_autofill): ?>
                        <input type="hidden" name="produk_id" value="<?php echo $def_produk; ?>">
                    <?php endif; ?>
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label small fw-bold">
                        Kode Barang (Fisik)
                        <?php if($is_autofill) echo '<i class="bi bi-lock-fill text-muted ms-1" title="Terkunci"></i>'; ?>
                    </label>
                    <input type="text" name="kode_barang" class="form-control fw-bold <?php echo $is_autofill ? 'bg-light text-muted' : 'text-primary'; ?>" placeholder="Cth: BRG-0005-OLD" value="<?php echo htmlspecialchars($def_kode); ?>" <?php echo $is_autofill ? 'readonly tabindex="-1"' : 'required'; ?>>
                </div>

                <?php if($is_autofill): ?>
                    <div class="col-md-4 mb-3">
                        <label class="form-label small fw-bold text-muted">
                            Berat Awal (Gram) <i class="bi bi-lock-fill text-muted ms-1"></i>
                        </label>
                        <input type="text" class="form-control bg-light text-muted" value="<?php echo htmlspecialchars($def_berat); ?>" readonly tabindex="-1">
                    </div>
                <?php endif; ?>

                <div class="col-md-4 mb-3">
                    <label class="form-label small fw-bold <?php echo $is_autofill ? 'text-primary' : ''; ?>">
                        <?php echo $is_autofill ? 'Berat Sekarang (Gram)' : 'Berat (Gram)'; ?>
                    </label>
                    <input type="text" inputmode="decimal" id="inputBeratSekarang" name="berat_sekarang" class="form-control fw-bold border-primary" value="<?php echo htmlspecialchars($def_berat); ?>" autocomplete="off" required>
                    <?php if($is_autofill): ?>
                        <div class="form-text text-primary" style="font-size: 0.75rem;">Ubah jika emas menyusut.</div>
                    <?php endif; ?>
                </div>

                <div class="col-md-4 mb-4">
                    <label class="form-label small fw-bold text-primary">Harga Deal Akhir (Rupiah)</label>
                    <input type="number" min="1" id="inputHargaDeal" name="harga_deal" class="form-control border-primary bg-white fw-bold text-primary" placeholder="0" autofocus required style="font-size: 1.1rem; transition: background-color 0.3s ease;">
                    <div class="form-text text-primary fw-bold" style="font-size: 0.75rem;"><i class="bi bi-info-circle-fill me-1"></i> Nominal pasti yang dibayarkan ke pelanggan.</div>
                </div>
            </div>

            <button type="submit" name="simpan_buyback" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-sm fs-6">
                <i class="bi bi-check-circle-fill me-2"></i> PROSES BUYBACK SEKARANG
            </button>
        </form>
    </div>
</div>

<script>
    // Logika Validasi Manual Javascript
    function pastikanIsi() {
        if(document.getElementById('inputBeratSekarang').value.trim() === '') {
            alert('Berat gram tidak boleh kosong!'); return false;
        }
        if(document.getElementById('inputHargaDeal').value.trim() === '' || document.getElementById('inputHargaDeal').value <= 0) {
            alert('Harga Deal harus diisi angka yang benar!'); return false;
        }
        return true; 
    }

    // Logika Kalkulator Estimasi
    const hargaBuybackPerGram = <?php echo $def_harga_buyback_gram; ?>;
    const inputBeratSekarang = document.getElementById('inputBeratSekarang');
    const displayEstimasi = document.getElementById('displayEstimasi');
    const inputHargaDeal = document.getElementById('inputHargaDeal');
    
    let estimasiSaatIni = 0;

    function hitungEstimasi() {
        if(hargaBuybackPerGram > 0) {
            let rawBerat = inputBeratSekarang.value.replace(',', '.');
            let berat = parseFloat(rawBerat) || 0;
            
            estimasiSaatIni = berat * hargaBuybackPerGram;
            displayEstimasi.innerHTML = 'Rp ' + new Intl.NumberFormat('id-ID').format(estimasiSaatIni);
        }
    }

    function salinEstimasi() {
        if(estimasiSaatIni > 0) {
            inputHargaDeal.value = estimasiSaatIni;
            inputHargaDeal.style.backgroundColor = '#e7f1ff';
            setTimeout(() => { inputHargaDeal.style.backgroundColor = '#fff'; }, 500);
        } else {
            alert('Harga acuan buyback belum diatur untuk kadar ini!');
        }
    }

    hitungEstimasi();
    if(inputBeratSekarang) {
        inputBeratSekarang.addEventListener('input', hitungEstimasi);
    }
</script>

<?php include '../../layouts/footer.php'; ?>