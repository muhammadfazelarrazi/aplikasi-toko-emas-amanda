<?php
// 1. CEK SESSION (Agar tidak bentrok/double start)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include '../../config/database.php';

// 2. LOGIKA PENENTUAN ID TRANSAKSI (Universal)
// Cek apakah variabel $idTransaksi sudah ada? (Berarti file ini dipanggil oleh surat_email.php)
if (isset($idTransaksi)) {
    $id = $idTransaksi;
} 
// Jika tidak, cek apakah ada parameter ID di URL? (Berarti file ini dibuka langsung oleh Admin)
elseif (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['id']);
} 
// Jika keduanya tidak ada, stop.
else {
    die("Error: ID Transaksi tidak ditemukan.");
}

// 3. AMBIL DATA HEADER
$queryHeader = "SELECT t.*, p.NamaPelanggan, p.Alamat, k.NamaKaryawan 
                FROM transaksi t
                JOIN pelanggan p ON t.PelangganID = p.PelangganID
                JOIN karyawan k ON t.KaryawanID = k.KaryawanID
                WHERE t.TransaksiID = '$id'";
$resHeader = mysqli_query($koneksi, $queryHeader);
$header = mysqli_fetch_assoc($resHeader);

// Validasi jika data tidak ketemu
if (!$header) {
    die("Data transaksi #$id tidak ditemukan di database.");
}

// 4. AMBIL DATA BARANG
$queryDetail = "SELECT dt.*, bs.KodeBarang, bs.BeratGram, pk.NamaProduk, pk.Kadar 
                FROM detail_transaksi_barang dt
                JOIN barang_stok bs ON dt.BarangID = bs.BarangID
                JOIN produk_katalog pk ON bs.ProdukKatalogID = pk.ProdukKatalogID
                WHERE dt.TransaksiID = '$id'";
$resDetail = mysqli_query($koneksi, $queryDetail);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Emas - #<?php echo $id; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

    <style>
        @page { size: A5 landscape; margin: 0; }

        body { 
            background: #555; 
            font-family: 'Poppins', sans-serif; 
            color: #000;
            display: flex;
            justify-content: center;
            padding: 20px;
        }
        
        .page-surat {
            background: #fff;
            width: 210mm; 
            height: 148mm; 
            padding: 10mm 15mm;
            position: relative;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            box-sizing: border-box; 
            overflow: hidden;
        }

        /* HEADER */
        .header-section {
            border-bottom: 3px double #0d6efd; 
            padding-bottom: 10px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start; 
        }
        .toko-info h2 {
            color: #0d6efd; font-weight: 800; margin: 0; font-size: 1.6rem; letter-spacing: 1px; text-transform: uppercase;
        }
        .toko-info p { margin: 0; font-size: 0.7rem; color: #555; }

        .surat-info { text-align: right; }
        .no-surat { font-size: 1.2rem; font-weight: 800; color: #0d6efd; margin-bottom: 2px; }
        .tgl-surat { font-size: 0.8rem; color: #666; margin-bottom: 5px; }
        .kepada-lbl { font-size: 0.7rem; font-weight: bold; margin-bottom: 0; }
        .nama-plg { font-size: 0.9rem; font-weight: 600; text-transform: uppercase; }

        .judul-surat {
            text-align: center; font-weight: 700; font-size: 1.1rem;
            text-transform: uppercase; text-decoration: underline; margin-bottom: 10px;
        }

        /* TABEL */
        .table-custom { width: 100%; font-size: 0.8rem; border-collapse: collapse; }
        .table-custom th { 
            border-top: 1px solid #000; border-bottom: 1px solid #000; 
            padding: 8px 5px; text-align: center; background-color: #f8f9fa;
        }
        .table-custom td { 
            padding: 8px 5px; 
            border-bottom: 1px solid #ddd; 
            vertical-align: middle; 
        }
        .text-end { text-align: right; }
        .text-center { text-align: center; }

        .syarat-area { font-size: 0.6rem; color: #666; margin-top: 15px; font-style: italic; }

        .ttd-wrapper { display: flex; justify-content: space-between; margin-top: 15px; font-size: 0.8rem; }
        .ttd-box { text-align: center; width: 200px; }
        .ttd-space { height: 40px; } 
        .ttd-name { border-top: 1px solid #000; font-weight: bold; padding-top: 2px; text-transform: uppercase;}

        .watermark {
            position: absolute; top: 55%; left: 50%;
            transform: translate(-50%, -50%) rotate(-10deg);
            font-size: 6rem; color: rgba(13, 110, 253, 0.03);
            z-index: 0; pointer-events: none; font-weight: 900;
        }

        .btn-print-floating {
            position: fixed; bottom: 30px; right: 30px; z-index: 999;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }

        @media print {
            body { background: none; padding: 0; margin: 0; }
            .page-surat { box-shadow: none; width: 100%; height: 100%; margin: 0; border: none; }
            .btn-print-floating { display: none; }
            -webkit-print-color-adjust: exact; 
        }
    </style>
</head>
<body>

    <div class="page-surat">
        <div class="watermark">AMANDA</div>

        <div class="header-section">
            <div class="toko-info">
                <h2>TOKO EMAS AMANDA</h2>
                <p>Jl. Ps. Pancasila, Lengkongsari, Kec. Tawang, Kab. Tasikmalaya, Jawa Barat 46111</p>
                <p>WA: 0812-3456-7890 | Email: cs@tokoamanda.com</p>
            </div>
            
            <div class="surat-info">
                <div class="no-surat">#TRX-<?php echo $header['TransaksiID']; ?></div>
                <div class="tgl-surat"><?php echo date('d F Y', strtotime($header['TanggalWaktu'])); ?></div>
                <p class="kepada-lbl">Kepada Yth:</p>
                <div class="nama-plg"><?php echo $header['NamaPelanggan']; ?></div>
            </div>
        </div>

        <div class="judul-surat">BUKTI KEPEMILIKAN EMAS</div>

        <table class="table-custom">
            <thead>
                <tr>
                    <th width="5%" class="text-center">No</th>
                    <th width="40%">Nama Barang / Barcode</th>
                    <th width="15%" class="text-center">Kadar</th>
                    <th width="15%" class="text-center">Berat</th>
                    <th width="25%" class="text-end">Harga</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                $subtotalEmas = 0; // Tambahan untuk menghitung harga emas murni
                while($row = mysqli_fetch_assoc($resDetail)): 
                    $subtotalEmas += $row['HargaSatuanSaatItu'];
                ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    
                    <td class="text-center">
                        <div style="display: flex; flex-direction: column; align-items: center;">
                            <span style="font-weight: bold; font-size: 0.95rem; margin-bottom: 5px; color: #000;">
                                <?php echo $row['NamaProduk']; ?>
                            </span>
                            <svg class="barcode"
                                 jsbarcode-format="CODE128"
                                 jsbarcode-value="<?php echo $row['KodeBarang']; ?>"
                                 jsbarcode-margin="0"
                                 jsbarcode-textmargin="2"
                                 jsbarcode-fontoptions="bold"
                                 jsbarcode-height="25"
                                 jsbarcode-width="1.5"
                                 jsbarcode-fontSize="10"
                                 jsbarcode-displayValue="true">
                            </svg>
                        </div>
                    </td>
                    
                    <td class="text-center"><?php echo $row['Kadar']; ?></td>
                    <td class="text-center fw-bold"><?php echo $row['BeratGram']; ?> gr</td>
                    <td class="text-end fw-bold">Rp <?php echo number_format($row['HargaSatuanSaatItu'], 0, ',', '.'); ?></td>
                </tr>
                <?php endwhile; ?>
                
                <?php 
                // Logika Rincian Harga Pintar
                $dbOngkos = isset($header['TotalOngkos']) ? $header['TotalOngkos'] : 0;
                $dbDiskon = isset($header['TotalDiskon']) ? $header['TotalDiskon'] : 0;
                $showRincian = ($dbOngkos > 0 || $dbDiskon > 0);
                ?>

                <?php if($showRincian): ?>
                    <tr>
                        <td colspan="4" class="text-end" style="padding-top: 15px; padding-right: 10px; border-bottom: none; font-size: 0.8rem;">Subtotal Emas</td>
                        <td class="text-end" style="padding-top: 15px; padding-right: 10px; border-bottom: none; font-size: 0.8rem;">Rp <?php echo number_format($subtotalEmas, 0, ',', '.'); ?></td>
                    </tr>
                    <?php if($dbOngkos > 0): ?>
                    <tr>
                        <td colspan="4" class="text-end" style="padding-right: 10px; border-bottom: none; font-size: 0.8rem;">Ongkos Bikin</td>
                        <td class="text-end" style="padding-right: 10px; border-bottom: none; font-size: 0.8rem;">Rp <?php echo number_format($dbOngkos, 0, ',', '.'); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if($dbDiskon > 0): ?>
                    <tr>
                        <td colspan="4" class="text-end" style="padding-right: 10px; border-bottom: none; font-size: 0.8rem;">Potongan Harga</td>
                        <td class="text-end" style="padding-right: 10px; border-bottom: none; font-size: 0.8rem; color: red;">(Rp <?php echo number_format($dbDiskon, 0, ',', '.'); ?>)</td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td colspan="4" class="text-end" style="padding-right: 10px; border-top: 1px dashed #000;"><b>TOTAL BAYAR</b></td>
                        <td class="text-end" style="padding-right: 10px; border-top: 1px dashed #000;"><b>Rp <?php echo number_format($header['TotalTransaksi'], 0, ',', '.'); ?></b></td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-end" style="padding-top: 15px; padding-right: 10px;"><b>TOTAL BAYAR</b></td>
                        <td class="text-end" style="padding-top: 15px; padding-right: 10px;"><b>Rp <?php echo number_format($header['TotalTransaksi'], 0, ',', '.'); ?></b></td>
                    </tr>
                <?php endif; ?>

            </tbody>
        </table>

        <div class="d-flex justify-content-between" style="align-items: flex-end;">
            <div style="width: 50%;">
                <div class="syarat-area">
                    <strong>Catatan Penting:</strong>
                    <ul class="ps-3 mb-0">
                        <li>Surat ini adalah bukti sah kepemilikan dan jaminan buyback.</li>
                        <li>Harap dibawa saat menjual kembali. Tanpa surat, dikenakan potongan biaya.</li>
                        <li>Barang rusak/cacat akan mempengaruhi harga jual kembali.</li>
                    </ul>
                </div>
            </div>
            <div style="width: 45%;">
                <div class="ttd-wrapper">
                    <div class="ttd-box">
                        <span>Hormat Kami,</span>
                        <div class="ttd-space"></div>
                        <div class="ttd-name"><?php echo $header['NamaKaryawan']; ?></div>
                    </div>
                    <div class="ttd-box">
                        <span>Penerima,</span>
                        <div class="ttd-space"></div>
                        <div class="ttd-name"><?php echo $header['NamaPelanggan']; ?></div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="btn-print-floating btn-group">
        <button onclick="window.print()" class="btn btn-primary rounded-pill fw-bold px-4 py-2 shadow">
            <i class="bi bi-printer-fill me-2"></i> Cetak / Simpan PDF
        </button>
    </div>

    <script>
        JsBarcode(".barcode").init();
    </script>

</body>
</html>