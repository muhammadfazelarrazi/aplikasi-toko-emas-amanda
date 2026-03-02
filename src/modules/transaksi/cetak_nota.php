<?php
session_start();
include '../../config/database.php';

// Cek ID Transaksi di URL
if (!isset($_GET['id'])) {
    die("Error: ID Transaksi tidak ditemukan.");
}

$id = $_GET['id'];

// 1. AMBIL DATA HEADER TRANSAKSI
$queryHeader = "SELECT t.*, p.NamaPelanggan, k.NamaKaryawan 
                FROM transaksi t
                LEFT JOIN pelanggan p ON t.PelangganID = p.PelangganID
                JOIN karyawan k ON t.KaryawanID = k.KaryawanID
                WHERE t.TransaksiID = '$id'";
$resultHeader = mysqli_query($koneksi, $queryHeader);
$header = mysqli_fetch_assoc($resultHeader);

if (!$header) {
    die("Data transaksi tidak ditemukan.");
}

// 2. AMBIL DATA ITEM BARANG
$queryDetail = "SELECT dt.*, bs.KodeBarang, bs.BeratGram, pk.NamaProduk, pk.Kadar 
                FROM detail_transaksi_barang dt
                JOIN barang_stok bs ON dt.BarangID = bs.BarangID
                JOIN produk_katalog pk ON bs.ProdukKatalogID = pk.ProdukKatalogID
                WHERE dt.TransaksiID = '$id'";
$resultDetail = mysqli_query($koneksi, $queryDetail);

// PENGECEKAN AMAN: Jika kolom Ongkos/Diskon belum ada di DB (transaksi lama), fallback ke 0
$dbOngkos = isset($header['TotalOngkos']) ? $header['TotalOngkos'] : 0;
$dbDiskon = isset($header['TotalDiskon']) ? $header['TotalDiskon'] : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota #<?php echo $id; ?> - Toko Emas Amanda</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace; 
            font-size: 12px;
            margin: 0;
            padding: 10px;
            background-color: #f0f0f0; 
        }
        
        .nota-container {
            max-width: 300px; 
            margin: 0 auto;
            background: #fff;
            padding: 15px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .garis { border-bottom: 1px dashed #000; margin: 10px 0; }
        
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; padding: 2px 0; }

        .btn-kembali {
            display: block;
            width: 250px; 
            margin: 20px auto 0 auto; 
            padding: 10px;
            background: #0d6efd;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
            font-family: sans-serif;
            font-weight: bold;
        }

        @media print {
            body { background: none; }
            .nota-container { box-shadow: none; padding: 0; margin: 0; width: 100%; }
            .btn-kembali { display: none; } 
        }
    </style>
</head>
<body onload="window.print()"> 
    <div class="nota-container">
        
        <div class="text-center">
            <h2 style="margin: 0;">TOKO MAS AMANDA</h2>
            <p style="margin: 5px 0;">Jl. Ps. Pancasila, Lengkongsari<br>Tasikmalaya 46111</p>
            <p style="margin: 0;">Telp: 0812-3456-7890</p>
        </div>

        <div class="garis"></div>

        <table>
            <tr>
                <td>No. Nota</td>
                <td class="text-end fw-bold">#TRX-<?php echo $header['TransaksiID']; ?></td>
            </tr>
            <tr>
                <td>Tanggal</td>
                <td class="text-end"><?php echo date('d/m/Y H:i', strtotime($header['TanggalWaktu'])); ?></td>
            </tr>
            <tr>
                <td>Kasir</td>
                <td class="text-end"><?php echo $header['NamaKaryawan']; ?></td>
            </tr>
            <tr>
                <td>Pelanggan</td>
                <td class="text-end"><?php echo $header['NamaPelanggan']; ?></td>
            </tr>
        </table>

        <div class="garis"></div>

        <table>
            <?php 
            $subtotalEmas = 0;
            while($item = mysqli_fetch_assoc($resultDetail)): 
                $subtotalEmas += $item['HargaSatuanSaatItu'];
            ?>
            <tr>
                <td colspan="2" class="fw-bold"><?php echo $item['NamaProduk']; ?> (<?php echo $item['Kadar']; ?>)</td>
            </tr>
            <tr>
                <td>SN: <?php echo $item['KodeBarang']; ?> - <?php echo $item['BeratGram']; ?>gr</td>
                <td class="text-end">Rp <?php echo number_format($item['HargaSatuanSaatItu'], 0, ',', '.'); ?></td>
            </tr>
            <?php endwhile; ?>
        </table>

        <div class="garis"></div>

        <table>
            <tr>
                <td>Subtotal Emas</td>
                <td class="text-end">Rp <?php echo number_format($subtotalEmas, 0, ',', '.'); ?></td>
            </tr>
            
            <?php if($dbOngkos > 0): ?>
            <tr>
                <td>Ongkos Bikin</td>
                <td class="text-end">Rp <?php echo number_format($dbOngkos, 0, ',', '.'); ?></td>
            </tr>
            <?php endif; ?>

            <?php if($dbDiskon > 0): ?>
            <tr>
                <td>Potongan Harga</td>
                <td class="text-end" style="color: red;">(Rp <?php echo number_format($dbDiskon, 0, ',', '.'); ?>)</td>
            </tr>
            <?php endif; ?>

            <tr>
                <td class="fw-bold" style="font-size: 14px; padding-top: 5px;">GRAND TOTAL</td>
                <td class="text-end fw-bold" style="font-size: 14px; padding-top: 5px;">Rp <?php echo number_format($header['TotalTransaksi'], 0, ',', '.'); ?></td>
            </tr>
        </table>

        <div class="garis"></div>

        <div class="text-center">
            <p style="margin: 5px 0;">Terima Kasih atas Kunjungan Anda</p>
            <p style="font-size: 10px;">Barang yang sudah dibeli dapat dijual kembali dengan potongan harga sesuai ketentuan toko.</p>
            <p style="font-size: 10px; margin-top: 5px; color: #0d6efd;">** Surat Emas Digital Telah Dikirim ke Email **</p>
        </div>

    </div>
    
    <a href="input_jual.php" class="btn-kembali btn-print">Kembali ke Menu Kasir</a>

</body>
</html>