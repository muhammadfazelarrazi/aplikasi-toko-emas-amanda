<?php
// CEK SESSION
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// PANGGIL AUTOLOAD COMPOSER & KONEKSI
require_once __DIR__ . '/../../vendor/autoload.php';
include __DIR__ . '/../../config/database.php';


// =========================================================================
// BLOK KEAMANAN & PENENTUAN ID TRANSAKSI (ANTI-HACK & SUPPORT TOKEN EMAIL)
// =========================================================================
$id = null;
$is_authorized = false;

// 1. JALUR PELANGGAN: Apakah diakses membawa Token dari Email?
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $dataToken = base64_decode($token);
    $exploded = explode('|', $dataToken);
    
    if (count($exploded) >= 2) {
        $id = mysqli_real_escape_string($koneksi, $exploded[0]);
        $_GET['action'] = 'pdf'; // Otomatis paksa ke mode PDF agar langsung download
        $is_authorized = true;
    } else {
        die("Error: Token keamanan tidak valid atau rusak.");
    }
} 
// 2. JALUR KASIR/OWNER: Apakah diakses dari dalam sistem (Sudah Login)?
elseif (isset($_SESSION['user_id'])) {
    $is_authorized = true;
    if (isset($idTransaksi)) {
        $id = $idTransaksi;
    } elseif (isset($_GET['id'])) {
        $id = mysqli_real_escape_string($koneksi, $_GET['id']);
    }
}

// Eksekusi pemblokiran jika penyusup mencoba menebak ID tanpa login/token
if (!$is_authorized || $id == null) {
    die("Akses Ditolak: Anda tidak memiliki izin untuk melihat dokumen ini.");
}
// =========================================================================


// CEK MODE REQUEST
$isPdfMode = (isset($_GET['action']) && $_GET['action'] == 'pdf') || isset($idTransaksi);

if ($isPdfMode) {
    // =========================================================================
    // BLOK 1: RENDER DOMPDF
    // =========================================================================
    
    $queryHeader = "SELECT t.*, p.NamaPelanggan, p.Alamat, k.NamaKaryawan 
                    FROM transaksi t
                    JOIN pelanggan p ON t.PelangganID = p.PelangganID
                    JOIN karyawan k ON t.KaryawanID = k.KaryawanID
                    WHERE t.TransaksiID = '$id'";
    $resHeader = mysqli_query($koneksi, $queryHeader);
    $header = mysqli_fetch_assoc($resHeader);

    if (!$header) { die("Data transaksi #$id tidak ditemukan di database."); }

    $queryDetail = "SELECT dt.*, bs.KodeBarang, bs.BeratGram, pk.NamaProduk, pk.Kadar 
                    FROM detail_transaksi_barang dt
                    JOIN barang_stok bs ON dt.BarangID = bs.BarangID
                    JOIN produk_katalog pk ON bs.ProdukKatalogID = pk.ProdukKatalogID
                    WHERE dt.TransaksiID = '$id'";
    $resDetail = mysqli_query($koneksi, $queryDetail);

    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Surat Emas - #<?php echo $id; ?></title>
        <style>
            /* Merapatkan margin agar PDF fit 1 halaman tanpa tumpah */
            @page { margin: 20px 30px; }

            body { 
                font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; 
                color: #0f172a;
                font-size: 11px;
                margin: 0;
                padding: 0;
            }

            .watermark {
                position: absolute;
                top: 25%; left: 15%;
                font-size: 100px;
                color: rgba(13, 110, 253, 0.05);
                transform: rotate(-15deg);
                z-index: -1;
                font-weight: bold;
                letter-spacing: 5px;
            }

            .layout-table { width: 100%; border-collapse: collapse; }
            
            .header-line {
                border-bottom: 2px solid #0d6efd;
                padding-bottom: 8px; margin-bottom: 12px;
            }

            .title-surat {
                text-align: center; font-weight: bold;
                font-size: 14px; letter-spacing: 1px;
                text-decoration: underline; margin-bottom: 12px; color: #0f172a;
            }

            /* Mengunci layout tabel agar presisi dan tidak overflow */
            .table-items {
                width: 100%; border-collapse: collapse;
                margin-bottom: 10px; table-layout: fixed;
            }
            .table-items th {
                border-top: 1px solid #1e293b; border-bottom: 1px solid #1e293b;
                padding: 6px 4px; text-align: center;
                background-color: #f8fafc; font-size: 10px;
                text-transform: uppercase; color: #475569;
            }
            .table-items td {
                padding: 6px 4px; border-bottom: 1px solid #e2e8f0;
                vertical-align: middle; font-size: 11px; word-wrap: break-word;
            }

            .text-end { text-align: right; }
            .text-center { text-align: center; }
            .fw-bold { font-weight: bold; }
            .text-primary { color: #0d6efd; }
            .text-danger { color: #ef4444; }

            .ttd-table { width: 100%; margin-top: 15px; font-size: 10px; }
        </style>
    </head>
    <body>

        <div class="watermark">AMANDA</div>

        <div class="header-line">
            <table class="layout-table">
                <tr>
                    <td width="60%" valign="top">
                        <h2 style="color: #0d6efd; margin: 0; font-size: 22px; font-weight: 900; letter-spacing: -0.5px;">TOKO EMAS AMANDA</h2>
                        <p style="margin: 4px 0 0 0; font-size: 10px; color: #475569;">Jl. Ps. Pancasila, Lengkongsari, Kec. Tawang, Tasikmalaya 46111</p>
                        <p style="margin: 2px 0 0 0; font-size: 10px; color: #475569;">WA: 0812-3456-7890 | Email: cs@tokoamanda.com</p>
                    </td>
                    <td width="40%" valign="top" align="right">
                        <h3 style="margin: 0; color: #0d6efd; font-size: 18px; font-weight: bold;">#TRX-<?php echo $header['TransaksiID']; ?></h3>
                        <p style="margin: 2px 0; font-size: 10px; color: #64748b;"><?php echo date('d F Y - H:i', strtotime($header['TanggalWaktu'])); ?></p>
                        <p style="margin: 8px 0 0 0; font-size: 9px; font-weight: bold; color: #94a3b8; text-transform: uppercase;">Kepada Yth:</p>
                        <p style="margin: 0; font-size: 13px; font-weight: bold; text-transform: uppercase; color: #0f172a;"><?php echo $header['NamaPelanggan']; ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="title-surat">BUKTI KEPEMILIKAN EMAS</div>

        <table class="table-items">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="45%" style="text-align: left; padding-left: 10px;">Nama Barang / Barcode</th>
                    <th width="15%">Kadar</th>
                    <th width="15%">Berat</th>
                    <th width="20%" class="text-end" style="padding-right: 10px;">Harga</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                $subtotalEmas = 0; 
                while($row = mysqli_fetch_assoc($resDetail)): 
                    $subtotalEmas += $row['HargaSatuanSaatItu'];
                ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td style="padding-left: 10px;">
                        <div style="font-weight: bold; font-size: 11px; margin-bottom: 4px;"><?php echo $row['NamaProduk']; ?></div>
                        <img src="https://bwipjs-api.metafloor.com/?bcid=code128&text=<?php echo $row['KodeBarang']; ?>&scale=2&height=8&includetext=false" style="height: 22px; width: auto; display: block; margin-bottom: 2px;">
                        <div style="font-size: 8px; color: #64748b; font-family: monospace; letter-spacing: 1px;"><?php echo $row['KodeBarang']; ?></div>
                    </td>
                    <td class="text-center fw-bold" style="color: #b45309;"><?php echo $row['Kadar']; ?></td>
                    <td class="text-center fw-bold"><?php echo $row['BeratGram']; ?>g</td>
                    <td class="text-end fw-bold" style="padding-right: 10px;">Rp <?php echo number_format($row['HargaSatuanSaatItu'], 0, ',', '.'); ?></td>
                </tr>
                <?php endwhile; ?>

                <?php 
                $dbOngkos = isset($header['TotalOngkos']) ? $header['TotalOngkos'] : 0;
                $dbDiskon = isset($header['TotalDiskon']) ? $header['TotalDiskon'] : 0;
                $showRincian = ($dbOngkos > 0 || $dbDiskon > 0);
                ?>

                <?php if($showRincian): ?>
                    <tr>
                        <td colspan="4" class="text-end" style="padding-top: 10px; padding-right: 15px; border-bottom: none; font-size: 10px; color: #64748b;">Subtotal Emas</td>
                        <td class="text-end fw-bold" style="padding-top: 10px; padding-right: 10px; border-bottom: none; font-size: 11px;">Rp <?php echo number_format($subtotalEmas, 0, ',', '.'); ?></td>
                    </tr>
                    <?php if($dbOngkos > 0): ?>
                    <tr>
                        <td colspan="4" class="text-end" style="padding-right: 15px; border-bottom: none; font-size: 10px; color: #64748b;">Biaya Pembuatan</td>
                        <td class="text-end fw-bold" style="padding-right: 10px; border-bottom: none; font-size: 11px; color: #0d6efd;">Rp <?php echo number_format($dbOngkos, 0, ',', '.'); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if($dbDiskon > 0): ?>
                    <tr>
                        <td colspan="4" class="text-end" style="padding-right: 15px; border-bottom: none; font-size: 10px; color: #64748b;">Potongan Harga</td>
                        <td class="text-end fw-bold" style="padding-right: 10px; border-bottom: none; font-size: 11px; color: #ef4444;">- Rp <?php echo number_format($dbDiskon, 0, ',', '.'); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td colspan="4" class="text-end" style="padding-right: 15px; border-top: 1px dashed #94a3b8;"><b>TOTAL BAYAR</b></td>
                        <td class="text-end text-primary" style="padding-right: 10px; border-top: 1px dashed #94a3b8; font-size: 14px;"><b>Rp <?php echo number_format($header['TotalTransaksi'], 0, ',', '.'); ?></b></td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-end" style="padding-top: 12px; padding-right: 15px; border-bottom: none;"><b>TOTAL BAYAR</b></td>
                        <td class="text-end text-primary" style="padding-top: 12px; padding-right: 10px; border-bottom: none; font-size: 14px;"><b>Rp <?php echo number_format($header['TotalTransaksi'], 0, ',', '.'); ?></b></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <table class="ttd-table">
            <tr>
                <td width="60%" valign="top" style="padding-right: 20px;">
                    <strong style="color: #0f172a;">Catatan Penting:</strong>
                    <ul style="margin: 4px 0 0 0; padding-left: 14px; color: #475569; line-height: 1.4;">
                        <li>Surat ini adalah bukti sah kepemilikan dan jaminan buyback.</li>
                        <li>Harap dibawa saat menjual kembali. Tanpa surat, dikenakan potongan biaya administrasi.</li>
                        <li>Barang rusak/cacat/putus akan mempengaruhi harga jual kembali.</li>
                    </ul>
                </td>
                <td width="40%" valign="top">
                    <table width="100%" style="text-align: center;">
                        <tr>
                            <td width="50%">
                                <span style="color: #64748b;">Hormat Kami,</span>
                                <br><br><br><br>
                                <div style="border-bottom: 1px solid #000; width: 80%; margin: 0 auto; margin-bottom: 2px;"></div>
                                <span style="font-weight: bold; text-transform: uppercase;"><?php echo $header['NamaKaryawan']; ?></span>
                            </td>
                            <td width="50%">
                                <span style="color: #64748b;">Penerima,</span>
                                <br><br><br><br>
                                <div style="border-bottom: 1px solid #000; width: 80%; margin: 0 auto; margin-bottom: 2px;"></div>
                                <span style="font-weight: bold; text-transform: uppercase;"><?php echo $header['NamaPelanggan']; ?></span>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

    </body>
    </html>
    <?php
    $html = ob_get_clean();

    $options = new \Dompdf\Options();
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'Helvetica');

    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A5', 'landscape');
    $dompdf->render();

    if (isset($idTransaksi)) {
        return $dompdf->output();
    } else {
        $dompdf->stream("Surat_Emas_TRX-".$header['TransaksiID'].".pdf", array("Attachment" => 0));
        exit; 
    }
}

// =========================================================================
// BLOK 2: RENDER UI WEB BUNGKUSAN 
// =========================================================================
include '../../layouts/header.php'; 
include '../../layouts/sidebar.php'; 
?>

<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = { corePlugins: { preflight: false, visibility: false } }
</script>

<style>
    .collapse { visibility: visible !important; }
    .collapse:not(.show) { display: none !important; }
    .collapsing { visibility: visible !important; }

    /* MENGHILANGKAN UNDERLINE PADA TOMBOL AKSI */
    a { text-decoration: none !important; }

    body { 
        font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", "Helvetica Neue", Helvetica, Arial, sans-serif;
        background-color: #f1f5f9; 
        color: #0f172a;
        -webkit-font-smoothing: antialiased;
    }
    
    .pdf-container {
        width: 100%; 
        margin: 0;
        background: #ffffff; border-radius: 20px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.03);
        padding: 24px 32px; display: flex; flex-direction: column;
        height: calc(100vh - 64px); 
    }

    .iframe-wrapper {
        flex: 1; border-radius: 12px; overflow: hidden;
        border: 1px solid #e2e8f0; background: #cbd5e1; 
        box-shadow: inset 0 2px 10px rgba(0,0,0,0.05);
    }
</style>

<div class="main-content" style="padding: 32px 40px;">
    
    <div class="pdf-container">
        
        <div class="flex justify-between items-center mb-5 pb-4 border-b border-slate-100">
            <div>
                <h2 class="text-[1.5rem] font-bold text-slate-800 mb-1 flex items-center gap-2" style="letter-spacing: -0.5px;">
                    <i class="bi bi-file-earmark-pdf-fill text-red-500"></i> Pratinjau Surat Emas
                </h2>
                <p class="text-[0.9rem] font-medium text-slate-500 mb-0">Surat Bukti Kepemilikan untuk Transaksi #TRX-<?php echo $id; ?></p>
            </div>
            
            <div class="flex gap-3">
                <a href="surat_aktif.php" class="no-underline bg-white hover:bg-slate-50 text-slate-600 px-5 py-2.5 rounded-xl font-bold text-[0.85rem] transition-colors flex items-center gap-2 shadow-sm" style="border: 1px solid #cbd5e1 !important;">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
                <a href="surat_emas.php?id=<?php echo $id; ?>&action=pdf" target="_blank" class="no-underline bg-[#3b82f6] hover:bg-[#2563eb] text-white px-5 py-2.5 rounded-xl font-bold text-[0.85rem] transition-colors shadow-md flex items-center gap-2">
                    <i class="bi bi-printer-fill"></i> Cetak / Unduh Dokumen
                </a>
            </div>
        </div>

        <div class="iframe-wrapper relative">
            <div class="absolute inset-0 flex flex-col items-center justify-center text-slate-500 z-0">
                <div class="animate-spin rounded-full h-8 w-8 border-4 border-slate-300 border-t-[#3b82f6] mb-3"></div>
                <span class="text-xs font-bold uppercase tracking-widest">Merender Dokumen...</span>
            </div>
            <iframe src="surat_emas.php?id=<?php echo $id; ?>&action=pdf#view=FitH" width="100%" height="100%" frameborder="0" style="display: block;" class="relative z-10 bg-white"></iframe>
        </div>
        
    </div>

</div>

<?php include '../../layouts/footer.php'; ?>