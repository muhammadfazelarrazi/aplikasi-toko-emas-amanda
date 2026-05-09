<?php 
session_start();
include '../../config/database.php'; 

// Panggil DOMPDF
require_once __DIR__ . '/../../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

// Cek Login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../modules/auth/login.php");
    exit;
}

// Filter Periode
$tgl_awal = isset($_GET['awal']) ? $_GET['awal'] : date('Y-m-01');
$tgl_akhir = isset($_GET['akhir']) ? $_GET['akhir'] : date('Y-m-d');
$action = isset($_GET['action']) ? $_GET['action'] : '';

// --- QUERY BARANG MASUK ---
$queryMasuk = "SELECT bs.*, pk.NamaProduk, pk.Kadar, s.NamaSupplier 
               FROM barang_stok bs
               JOIN produk_katalog pk ON bs.ProdukKatalogID = pk.ProdukKatalogID
               LEFT JOIN supplier s ON bs.SupplierID = s.SupplierID
               WHERE DATE(bs.TanggalMasuk) BETWEEN '$tgl_awal' AND '$tgl_akhir'
               ORDER BY bs.TanggalMasuk DESC";
$resMasuk = mysqli_query($koneksi, $queryMasuk);
$dataMasuk = [];
while($row = mysqli_fetch_assoc($resMasuk)) { $dataMasuk[] = $row; }

// --- QUERY BARANG KELUAR ---
$queryKeluar = "SELECT t.TanggalWaktu, t.TransaksiID, bs.KodeBarang, bs.BeratGram, pk.NamaProduk, pk.Kadar
                FROM detail_transaksi_barang dt
                JOIN transaksi t ON dt.TransaksiID = t.TransaksiID
                JOIN barang_stok bs ON dt.BarangID = bs.BarangID
                JOIN produk_katalog pk ON bs.ProdukKatalogID = pk.ProdukKatalogID
                WHERE t.TipeTransaksi = 'Penjualan' 
                AND DATE(t.TanggalWaktu) BETWEEN '$tgl_awal' AND '$tgl_akhir'
                ORDER BY t.TanggalWaktu DESC";
$resKeluar = mysqli_query($koneksi, $queryKeluar);
$dataKeluar = [];
while($row = mysqli_fetch_assoc($resKeluar)) { $dataKeluar[] = $row; }

// =========================================================================
// BLOK 1: RENDER DOMPDF (JIKA ACTION = 'pdf')
// =========================================================================
if ($action == 'pdf') {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Laporan Mutasi Stok</title>
        <style>
            @page { margin: 30px 40px; }
            body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #1e293b; font-size: 10px; margin: 0; padding: 0; }
            
            /* HEADER */
            .header-table { width: 100%; border-bottom: 2px solid #3b82f6; padding-bottom: 10px; margin-bottom: 20px; }
            .header-table td { vertical-align: top; }
            .company-name { font-size: 20px; font-weight: bold; color: #3b82f6; margin: 0 0 4px 0; }
            .company-address { font-size: 10px; color: #475569; margin: 0; }
            
            .report-title { font-size: 16px; font-weight: bold; text-align: right; margin: 0 0 5px 0; color: #0f172a; }
            .report-period { font-size: 10px; text-align: right; margin: 0; color: #64748b; }

            /* SECTION TITLES */
            .section-title { font-size: 12px; font-weight: bold; margin-bottom: 8px; margin-top: 20px; padding-bottom: 4px; border-bottom: 1px solid #cbd5e1; }
            .title-masuk { color: #059669; }
            .title-keluar { color: #e11d48; }

            /* TABLES */
            .data-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
            .data-table th { background-color: #f1f5f9; color: #475569; font-weight: bold; text-transform: uppercase; padding: 6px; border: 1px solid #cbd5e1; text-align: left; }
            .data-table td { padding: 6px; border: 1px solid #e2e8f0; vertical-align: middle; }
            .text-right { text-align: right; }
            .text-center { text-align: center; }
            .fw-bold { font-weight: bold; }
        </style>
    </head>
    <body>
        <table class="header-table">
            <tr>
                <td width="60%">
                    <h1 class="company-name">TOKO EMAS AMANDA</h1>
                    <p class="company-address">Jl. Ps. Pancasila, Lengkongsari, Kec. Tawang, Tasikmalaya 46111</p>
                    <p class="company-address">WA: 0812-3456-7890 | Email: cs@tokoamanda.com</p>
                </td>
                <td width="40%">
                    <h2 class="report-title">LAPORAN MUTASI STOK</h2>
                    <p class="report-period">Periode: <?php echo date('d M Y', strtotime($tgl_awal)); ?> s/d <?php echo date('d M Y', strtotime($tgl_akhir)); ?></p>
                </td>
            </tr>
        </table>

        <div class="section-title title-masuk">BARANG MASUK (INBOUND)</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th width="5%" class="text-center">No</th>
                    <th width="15%">Tanggal Masuk</th>
                    <th width="45%">Spesifikasi Barang</th>
                    <th width="20%">Sumber Perolehan</th>
                    <th width="15%" class="text-right">Berat Aktual</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($dataMasuk) > 0): ?>
                    <?php $no=1; $totBeratMasuk=0; foreach($dataMasuk as $in): $totBeratMasuk += $in['BeratGram']; ?>
                    <tr>
                        <td class="text-center"><?php echo $no++; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($in['TanggalMasuk'])); ?></td>
                        <td>
                            <strong style="font-size: 11px;"><?php echo $in['KodeBarang']; ?></strong><br>
                            <span style="color: #64748b;"><?php echo $in['NamaProduk']; ?> (Kadar <?php echo $in['Kadar']; ?>)</span>
                        </td>
                        <td><?php echo ($in['AsalBarang'] == 'Supplier') ? 'Supplier: ' . $in['NamaSupplier'] : 'Pelanggan (Buyback)'; ?></td>
                        <td class="text-right fw-bold"><?php echo $in['BeratGram']; ?>g</td>
                    </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td colspan="4" class="text-right fw-bold" style="background-color: #f8fafc;">Total Berat Masuk</td>
                        <td class="text-right fw-bold" style="background-color: #ecfdf5; color: #059669;"><?php echo $totBeratMasuk; ?>g</td>
                    </tr>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center" style="color: #94a3b8;">Tidak ada data barang masuk pada periode ini.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="section-title title-keluar">BARANG KELUAR (OUTBOUND)</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th width="5%" class="text-center">No</th>
                    <th width="15%">Tanggal Keluar</th>
                    <th width="45%">Spesifikasi Barang</th>
                    <th width="20%">No. Referensi (TRX)</th>
                    <th width="15%" class="text-right">Berat Aktual</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($dataKeluar) > 0): ?>
                    <?php $no=1; $totBeratKeluar=0; foreach($dataKeluar as $out): $totBeratKeluar += $out['BeratGram']; ?>
                    <tr>
                        <td class="text-center"><?php echo $no++; ?></td>
                        <td>
                            <?php echo date('d/m/Y', strtotime($out['TanggalWaktu'])); ?><br>
                            <span style="color: #64748b; font-size: 9px;"><?php echo date('H:i', strtotime($out['TanggalWaktu'])); ?> WIB</span>
                        </td>
                        <td>
                            <strong style="font-size: 11px;"><?php echo $out['KodeBarang']; ?></strong><br>
                            <span style="color: #64748b;"><?php echo $out['NamaProduk']; ?> (Kadar <?php echo $out['Kadar']; ?>)</span>
                        </td>
                        <td>#TRX-<?php echo $out['TransaksiID']; ?></td>
                        <td class="text-right fw-bold"><?php echo $out['BeratGram']; ?>g</td>
                    </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td colspan="4" class="text-right fw-bold" style="background-color: #f8fafc;">Total Berat Keluar</td>
                        <td class="text-right fw-bold" style="background-color: #fff1f2; color: #e11d48;"><?php echo $totBeratKeluar; ?>g</td>
                    </tr>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center" style="color: #94a3b8;">Tidak ada data barang keluar pada periode ini.</td></tr>
                <?php endif; ?>
            </tbody>
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
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("Laporan_Mutasi_Stok.pdf", array("Attachment" => 0));
    exit; 
}

// =========================================================================
// BLOK 2: RENDER UI WEB PREVIEW PDF (JIKA ACTION = 'preview')
// =========================================================================
if ($action == 'preview') {
    include '../../layouts/header.php'; 
    include '../../layouts/sidebar.php'; 
    ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { corePlugins: { preflight: false, visibility: false } }</script>
    <style>
        .collapse { visibility: visible !important; }
        .collapse:not(.show) { display: none !important; }
        .collapsing { visibility: visible !important; }
        a { text-decoration: none !important; }
        body { font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", "Helvetica Neue", Helvetica, Arial, sans-serif; background-color: #f1f5f9; color: #0f172a; -webkit-font-smoothing: antialiased; }
        
        .pdf-container { width: 100%; margin: 0; background: #ffffff; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.03); padding: 24px 32px; display: flex; flex-direction: column; height: calc(100vh - 64px); }
        .iframe-wrapper { flex: 1; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; background: #cbd5e1; box-shadow: inset 0 2px 10px rgba(0,0,0,0.05); }
    </style>

    <div class="main-content" style="padding: 32px 40px;">
        <div class="pdf-container">
            <div class="flex justify-between items-center mb-5 pb-4 border-b border-slate-100">
                <div>
                    <h2 class="text-[1.5rem] font-bold text-slate-800 mb-1 flex items-center gap-2" style="letter-spacing: -0.5px;">
                        <i class="bi bi-file-earmark-pdf-fill text-red-500"></i> Pratinjau Laporan Mutasi
                    </h2>
                    <p class="text-[0.9rem] font-medium text-slate-500 mb-0">Periode: <?php echo date('d M Y', strtotime($tgl_awal)); ?> s/d <?php echo date('d M Y', strtotime($tgl_akhir)); ?></p>
                </div>
                <div class="flex gap-3">
                    <a href="mutasi.php?awal=<?php echo $tgl_awal; ?>&akhir=<?php echo $tgl_akhir; ?>" class="bg-white hover:bg-slate-50 text-slate-600 px-5 py-2.5 rounded-xl font-bold text-[0.85rem] transition-colors flex items-center gap-2 shadow-sm border border-slate-200">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                    <a href="mutasi.php?action=pdf&awal=<?php echo $tgl_awal; ?>&akhir=<?php echo $tgl_akhir; ?>" target="_blank" class="bg-[#3b82f6] hover:bg-[#2563eb] text-white px-5 py-2.5 rounded-xl font-bold text-[0.85rem] transition-colors shadow-md flex items-center gap-2">
                        <i class="bi bi-printer-fill"></i> Cetak / Unduh Dokumen
                    </a>
                </div>
            </div>
            <div class="iframe-wrapper relative">
                <div class="absolute inset-0 flex flex-col items-center justify-center text-slate-500 z-0">
                    <div class="animate-spin rounded-full h-8 w-8 border-4 border-slate-300 border-t-[#3b82f6] mb-3"></div>
                    <span class="text-xs font-bold uppercase tracking-widest">Merender Dokumen...</span>
                </div>
                <iframe src="mutasi.php?action=pdf&awal=<?php echo $tgl_awal; ?>&akhir=<?php echo $tgl_akhir; ?>#view=FitH" width="100%" height="100%" frameborder="0" style="display: block;" class="relative z-10 bg-white"></iframe>
            </div>
        </div>
    </div>
    <?php include '../../layouts/footer.php'; exit; 
}

// =========================================================================
// BLOK 3: RENDER UI WEB STANDAR (DASHBOARD)
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
    a { text-decoration: none !important; }

    body { 
        font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", "Helvetica Neue", Helvetica, Arial, sans-serif;
        background-color: #f8fafc; color: #0f172a; -webkit-font-smoothing: antialiased; letter-spacing: -0.2px;
    }
    
    input, button, select {
        border: none !important; outline: none !important; box-shadow: none !important; background: transparent;
        -webkit-appearance: none; -moz-appearance: none; appearance: none;
    }
    input:focus, button:focus, select:focus { outline: none !important; box-shadow: none !important; }
    input[type="date"]::-webkit-calendar-picker-indicator { cursor: pointer; opacity: 0.6; }

    .table-scroll::-webkit-scrollbar { height: 6px; width: 6px; }
    .table-scroll::-webkit-scrollbar-track { background: transparent; }
    .table-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>

<div class="main-content" style="padding: 32px 40px 16px 40px;">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h2 class="text-[1.75rem] font-bold text-slate-800 mb-1 flex items-center gap-2" style="letter-spacing: -0.5px;">
                <i class="bi bi-arrow-left-right text-[#3b82f6]"></i> Laporan Mutasi Stok
            </h2>
            <p class="text-[0.95rem] font-medium text-slate-500 mb-0">Laporan historis pergerakan keluar-masuk barang berdasarkan periode.</p>
        </div>
        
        <a href="mutasi.php?action=preview&awal=<?php echo $tgl_awal; ?>&akhir=<?php echo $tgl_akhir; ?>" class="bg-white hover:bg-slate-50 text-slate-600 px-5 py-2.5 rounded-xl font-bold text-[13px] transition-colors flex items-center gap-2 shadow-sm border border-slate-200 cursor-pointer">
            <i class="bi bi-printer-fill text-[#3b82f6]"></i> Cetak Laporan
        </a>
    </div>

    <div class="w-full mb-6">
        <form method="GET" class="flex flex-wrap items-center gap-3 m-0">
            
            <div class="flex items-center bg-white rounded-xl shadow-sm overflow-hidden" style="border: 1px solid #cbd5e1 !important;">
                <div class="px-4 py-2.5 bg-slate-50 border-r border-slate-200 flex items-center gap-2">
                    <i class="bi bi-calendar3 text-slate-400"></i>
                    <span class="text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Periode</span>
                </div>
                <input type="date" name="awal" value="<?php echo $tgl_awal; ?>" class="px-4 py-2.5 text-[13px] font-bold text-slate-700 focus:bg-blue-50 transition-colors cursor-pointer" required title="Tanggal Awal">
                <span class="px-2 text-[12px] font-bold text-slate-400">s/d</span>
                <input type="date" name="akhir" value="<?php echo $tgl_akhir; ?>" class="px-4 py-2.5 text-[13px] font-bold text-slate-700 focus:bg-blue-50 transition-colors cursor-pointer" required title="Tanggal Akhir">
            </div>

            <button type="submit" class="bg-[#3b82f6] hover:bg-[#2563eb] text-white px-6 py-2.5 rounded-xl font-bold text-[13px] transition-colors shadow-md cursor-pointer">
                Tampilkan Data
            </button>

        </form>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <div>
            <div class="bg-white rounded-[20px] shadow-sm border border-solid border-slate-100 overflow-hidden h-full flex flex-col" style="box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);">
                
                <div class="px-5 py-3.5 border-b border-emerald-100 bg-emerald-50/30 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 flex-shrink-0">
                        <i class="bi bi-box-arrow-in-down-left text-lg font-bold"></i>
                    </div>
                    <div>
                        <h3 class="text-[14px] font-extrabold text-emerald-700 mb-0 leading-tight">Barang Masuk (Inbound)</h3>
                        <p class="text-[11px] font-semibold text-emerald-600/70 mb-0">Stok baru dari Supplier atau hasil Buyback.</p>
                    </div>
                </div>

                <div class="flex-1 overflow-x-auto table-scroll p-0">
                    <table class="w-full text-left border-collapse whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="py-3 px-4 text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Tanggal Masuk</th>
                                <th class="py-3 px-4 text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Spesifikasi Barang</th>
                                <th class="py-3 px-4 text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Sumber Perolehan</th>
                                <th class="py-3 px-4 text-[11px] font-extrabold text-slate-500 uppercase tracking-wider text-right">Berat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if(count($dataMasuk) > 0): ?>
                                <?php foreach($dataMasuk as $in): ?>
                                <tr class="hover:bg-emerald-50/30 transition-colors">
                                    <td class="py-3 px-4">
                                        <span class="text-[13px] font-bold text-slate-700"><?php echo date('d M Y', strtotime($in['TanggalMasuk'])); ?></span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex flex-col gap-0.5">
                                            <span class="text-[13px] font-extrabold text-slate-800"><?php echo $in['NamaProduk']; ?></span>
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-[11px] font-mono font-semibold text-slate-400"><?php echo $in['KodeBarang']; ?></span>
                                                <span class="text-[9px] font-bold bg-amber-50 text-amber-600 px-1.5 rounded border border-amber-200"><?php echo $in['Kadar']; ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <?php if($in['AsalBarang'] == 'Supplier'): ?>
                                            <div class="flex flex-col items-start">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 text-blue-600 border border-blue-200 mb-1">Supplier</span>
                                                <span class="text-[11px] font-semibold text-slate-500 max-w-[120px] truncate" title="<?php echo $in['NamaSupplier']; ?>"><?php echo $in['NamaSupplier']; ?></span>
                                            </div>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-600 border border-amber-200">
                                                Pelanggan (Buyback)
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <span class="text-[14px] font-black text-slate-800"><?php echo $in['BeratGram']; ?>g</span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="py-12 text-center">
                                        <div class="flex flex-col items-center justify-center text-slate-400">
                                            <i class="bi bi-box-seam text-4xl mb-2 opacity-20"></i>
                                            <h6 class="text-[13px] font-bold text-slate-500 mb-0">Tidak ada barang masuk.</h6>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div>
            <div class="bg-white rounded-[20px] shadow-sm border border-solid border-slate-100 overflow-hidden h-full flex flex-col" style="box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);">
                
                <div class="px-5 py-3.5 border-b border-rose-100 bg-rose-50/30 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-rose-100 flex items-center justify-center text-rose-600 flex-shrink-0">
                        <i class="bi bi-box-arrow-up-right text-lg font-bold"></i>
                    </div>
                    <div>
                        <h3 class="text-[14px] font-extrabold text-rose-700 mb-0 leading-tight">Barang Keluar (Outbound)</h3>
                        <p class="text-[11px] font-semibold text-rose-600/70 mb-0">Stok yang telah terjual ke pelanggan.</p>
                    </div>
                </div>

                <div class="flex-1 overflow-x-auto table-scroll p-0">
                    <table class="w-full text-left border-collapse whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="py-3 px-4 text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Tanggal Keluar</th>
                                <th class="py-3 px-4 text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Spesifikasi Barang</th>
                                <th class="py-3 px-4 text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">No. Referensi</th>
                                <th class="py-3 px-4 text-[11px] font-extrabold text-slate-500 uppercase tracking-wider text-right">Berat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if(count($dataKeluar) > 0): ?>
                                <?php foreach($dataKeluar as $out): ?>
                                <tr class="hover:bg-rose-50/30 transition-colors">
                                    <td class="py-3 px-4">
                                        <div class="flex flex-col">
                                            <span class="text-[13px] font-bold text-slate-700"><?php echo date('d M Y', strtotime($out['TanggalWaktu'])); ?></span>
                                            <span class="text-[11px] font-semibold text-slate-400"><?php echo date('H:i', strtotime($out['TanggalWaktu'])); ?> WIB</span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex flex-col gap-0.5">
                                            <span class="text-[13px] font-extrabold text-slate-800"><?php echo $out['NamaProduk']; ?></span>
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-[11px] font-mono font-semibold text-slate-400"><?php echo $out['KodeBarang']; ?></span>
                                                <span class="text-[9px] font-bold bg-amber-50 text-amber-600 px-1.5 rounded border border-amber-200"><?php echo $out['Kadar']; ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <a href="../transaksi/cetak_nota.php?id=<?php echo $out['TransaksiID']; ?>" target="_blank" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-bold bg-slate-100 text-slate-600 hover:bg-[#3b82f6] hover:text-white transition-colors border border-slate-200 cursor-pointer" title="Lihat Struk Penjualan">
                                            <i class="bi bi-link-45deg"></i> #TRX-<?php echo $out['TransaksiID']; ?>
                                        </a>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <span class="text-[14px] font-black text-slate-800"><?php echo $out['BeratGram']; ?>g</span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="py-12 text-center">
                                        <div class="flex flex-col items-center justify-center text-slate-400">
                                            <i class="bi bi-cart-x text-4xl mb-2 opacity-20"></i>
                                            <h6 class="text-[13px] font-bold text-slate-500 mb-0">Tidak ada barang terjual.</h6>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include '../../layouts/footer.php'; ?>