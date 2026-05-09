<?php 
// 1. Matikan error notis agar tidak merusak binary PDF
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', 0);

session_start();
include '../../config/database.php'; 

// Panggil DOMPDF via Composer Autoload (Standar Sistem Kita)
require_once __DIR__ . '/../../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

// Cek Login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../modules/auth/login.php");
    exit;
}

// Filter Tanggal (Default: Hari ini)
$tgl_mulai = isset($_GET['mulai']) ? $_GET['mulai'] : date('Y-m-d');
$tgl_selesai = isset($_GET['selesai']) ? $_GET['selesai'] : date('Y-m-d');
$action = isset($_GET['action']) ? $_GET['action'] : '';

// --- QUERY DATA LAPORAN ---
$query = "SELECT t.*, p.NamaPelanggan, k.NamaKaryawan 
          FROM transaksi t
          LEFT JOIN pelanggan p ON t.PelangganID = p.PelangganID
          JOIN karyawan k ON t.KaryawanID = k.KaryawanID
          WHERE DATE(t.TanggalWaktu) BETWEEN '$tgl_mulai' AND '$tgl_selesai'
          ORDER BY t.TanggalWaktu DESC";
$result = mysqli_query($koneksi, $query);

// Pemisahan Data untuk mempermudah perhitungan (Dashboard & PDF)
$dataTransaksi = [];
$dataPenjualan = [];
$dataBuyback = [];
$totalPenjualan = 0;
$totalBuyback = 0;

if($result && mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
        $dataTransaksi[] = $row;
        if($row['TipeTransaksi'] == 'Penjualan') {
            $dataPenjualan[] = $row;
            $totalPenjualan += (float)$row['TotalTransaksi'];
        } else {
            $dataBuyback[] = $row;
            $totalBuyback += (float)$row['TotalTransaksi'];
        }
    }
}
$grandTotal = $totalPenjualan - $totalBuyback;

// Format Teks Periode untuk PDF & Web
$format_mulai = date('d M Y', strtotime($tgl_mulai));
$format_selesai = date('d M Y', strtotime($tgl_selesai));
$teks_periode = ($tgl_mulai == $tgl_selesai) ? $format_mulai : "$format_mulai s/d $format_selesai";

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
        <title>Laporan Keuangan Amanda</title>
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
            .title-jual { color: #059669; border-bottom-color: #059669; }
            .title-beli { color: #e11d48; border-bottom-color: #e11d48; }

            /* TABLES */
            .data-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
            .data-table th { background-color: #f1f5f9; color: #475569; font-weight: bold; text-transform: uppercase; padding: 6px; border: 1px solid #cbd5e1; text-align: left; }
            .data-table td { padding: 6px; border: 1px solid #e2e8f0; vertical-align: middle; }
            .text-right { text-align: right; }
            .text-center { text-align: center; }
            .fw-bold { font-weight: bold; }
            
            .summary-table { width: 100%; border: 2px solid #0f172a; margin-top: 20px; border-collapse: collapse; }
            .summary-table td { padding: 10px; }
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
                    <h2 class="report-title">LAPORAN TRANSAKSI KEUANGAN</h2>
                    <p class="report-period">Periode: <?php echo $teks_periode; ?></p>
                </td>
            </tr>
        </table>

        <div class="section-title title-jual">A. TRANSAKSI PENJUALAN (KAS MASUK)</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th width="5%" class="text-center">No</th>
                    <th width="15%">No. Referensi</th>
                    <th width="20%">Tanggal & Waktu</th>
                    <th width="35%">Pelanggan</th>
                    <th width="25%" class="text-right">Nominal Transaksi (Rp)</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($dataPenjualan) > 0): $no=1; foreach($dataPenjualan as $j): ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td class="fw-bold">#TRX-<?php echo $j['TransaksiID']; ?></td>
                    <td><?php echo date('d/m/y H:i', strtotime($j['TanggalWaktu'])); ?></td>
                    <td><?php echo $j['NamaPelanggan'] ? $j['NamaPelanggan'] : 'Pelanggan Umum'; ?></td>
                    <td class="text-right fw-bold"><?php echo number_format($j['TotalTransaksi'], 0, ',', '.'); ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="5" class="text-center" style="color:#64748b;">Tidak ada data penjualan.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-right fw-bold" style="background-color: #ecfdf5;">TOTAL PENJUALAN</td>
                    <td class="text-right fw-bold" style="background-color: #ecfdf5; color: #059669; font-size: 11px;">Rp <?php echo number_format($totalPenjualan, 0, ',', '.'); ?></td>
                </tr>
            </tfoot>
        </table>

        <div class="section-title title-beli">B. TRANSAKSI BUYBACK (KAS KELUAR)</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th width="5%" class="text-center">No</th>
                    <th width="15%">No. Referensi</th>
                    <th width="20%">Tanggal & Waktu</th>
                    <th width="35%">Pelanggan</th>
                    <th width="25%" class="text-right">Nominal Transaksi (Rp)</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($dataBuyback) > 0): $no=1; foreach($dataBuyback as $b): ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td class="fw-bold" style="color:#e11d48;">#TRX-<?php echo $b['TransaksiID']; ?></td>
                    <td><?php echo date('d/m/y H:i', strtotime($b['TanggalWaktu'])); ?></td>
                    <td><?php echo $b['NamaPelanggan'] ? $b['NamaPelanggan'] : 'Pelanggan Umum'; ?></td>
                    <td class="text-right fw-bold"><?php echo number_format($b['TotalTransaksi'], 0, ',', '.'); ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="5" class="text-center" style="color:#64748b;">Tidak ada data buyback.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-right fw-bold" style="background-color: #fff1f2;">TOTAL BUYBACK</td>
                    <td class="text-right fw-bold" style="background-color: #fff1f2; color: #e11d48; font-size: 11px;">Rp <?php echo number_format($totalBuyback, 0, ',', '.'); ?></td>
                </tr>
            </tfoot>
        </table>

        <table class="summary-table">
            <tr style="background-color: #f8fafc;">
                <td width="70%" class="text-right fw-bold" style="border-right: 1px solid #cbd5e1;">GRAND TOTAL OMZET BERSIH</td>
                <td width="30%" class="text-right fw-bold" style="font-size: 14px; color: #3b82f6;">Rp <?php echo number_format($grandTotal, 0, ',', '.'); ?></td>
            </tr>
        </table>

        <div style="font-size: 8px; color: #94a3b8; text-align: right; margin-top: 15px; font-style: italic;">
            Dicetak otomatis oleh Sistem Manajemen Toko Emas pada <?php echo date('d M Y - H:i'); ?> WIB.
        </div>

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
    $dompdf->stream("Laporan_Keuangan_Amanda.pdf", array("Attachment" => 0));
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
                        <i class="bi bi-file-earmark-pdf-fill text-red-500"></i> Pratinjau Laporan Keuangan
                    </h2>
                    <p class="text-[0.9rem] font-medium text-slate-500 mb-0">Periode: <?php echo $teks_periode; ?></p>
                </div>
                <div class="flex gap-3">
                    <a href="harian.php?mulai=<?php echo $tgl_mulai; ?>&selesai=<?php echo $tgl_selesai; ?>" class="bg-white hover:bg-slate-50 text-slate-600 px-5 py-2.5 rounded-xl font-bold text-[14px] transition-colors flex items-center gap-2 shadow-sm border border-slate-200">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                    <a href="harian.php?action=pdf&mulai=<?php echo $tgl_mulai; ?>&selesai=<?php echo $tgl_selesai; ?>" target="_blank" class="bg-[#3b82f6] hover:bg-[#2563eb] text-white px-5 py-2.5 rounded-xl font-bold text-[14px] transition-colors shadow-md flex items-center gap-2">
                        <i class="bi bi-printer-fill"></i> Cetak / Unduh Dokumen
                    </a>
                </div>
            </div>
            <div class="iframe-wrapper relative">
                <div class="absolute inset-0 flex flex-col items-center justify-center text-slate-500 z-0">
                    <div class="animate-spin rounded-full h-8 w-8 border-4 border-slate-300 border-t-[#3b82f6] mb-3"></div>
                    <span class="text-[13px] font-bold uppercase tracking-widest">Merender Dokumen...</span>
                </div>
                <iframe src="harian.php?action=pdf&mulai=<?php echo $tgl_mulai; ?>&selesai=<?php echo $tgl_selesai; ?>#view=FitH" width="100%" height="100%" frameborder="0" style="display: block;" class="relative z-10 bg-white"></iframe>
            </div>
        </div>
    </div>
    <?php include '../../layouts/footer.php'; exit; 
}

// =========================================================================
// BLOK 3: RENDER UI WEB STANDAR (DASHBOARD LAPORAN)
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
                <i class="bi bi-wallet2 text-[#3b82f6]"></i> Laporan Transaksi
            </h2>
            <p class="text-[0.95rem] font-medium text-slate-500 mb-0">Rekapitulasi penjualan dan buyback per periode operasional.</p>
        </div>
        
        <a href="harian.php?action=preview&mulai=<?php echo $tgl_mulai; ?>&selesai=<?php echo $tgl_selesai; ?>" class="bg-white hover:bg-slate-50 text-slate-600 px-5 py-2.5 rounded-xl font-bold text-[14px] transition-colors flex items-center gap-2 shadow-sm border border-slate-200 cursor-pointer">
            <i class="bi bi-printer-fill text-red-500"></i> Cetak Laporan PDF
        </a>
    </div>

    <div class="w-full mb-6">
        <form method="GET" class="flex flex-wrap items-center gap-3 m-0">
            
            <div class="flex items-center bg-white rounded-xl shadow-sm overflow-hidden" style="border: 1px solid #cbd5e1 !important;">
                <div class="px-4 py-2.5 bg-slate-50 border-r border-slate-200 flex items-center gap-2">
                    <i class="bi bi-calendar3 text-slate-400"></i>
                    <span class="text-[12px] font-extrabold text-slate-500 uppercase tracking-wider">Periode</span>
                </div>
                <input type="date" name="mulai" value="<?php echo $tgl_mulai; ?>" class="px-4 py-2.5 text-[15px] font-bold text-slate-700 focus:bg-blue-50 transition-colors cursor-pointer" required title="Tanggal Awal">
                <span class="px-2 text-[14px] font-bold text-slate-400">s/d</span>
                <input type="date" name="selesai" value="<?php echo $tgl_selesai; ?>" class="px-4 py-2.5 text-[15px] font-bold text-slate-700 focus:bg-blue-50 transition-colors cursor-pointer" required title="Tanggal Akhir">
            </div>

            <button type="submit" class="bg-[#3b82f6] hover:bg-[#2563eb] text-white px-6 py-2.5 rounded-xl font-bold text-[15px] transition-colors shadow-md cursor-pointer">
                Filter Data
            </button>

        </form>
    </div>

    <div class="bg-white rounded-[20px] shadow-sm border border-solid border-slate-100 overflow-hidden mb-6" style="box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);">
        <div class="overflow-x-auto table-scroll">
            <table class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="py-4 px-5 text-[13px] font-extrabold text-slate-500 uppercase tracking-wider">No. Referensi</th>
                        <th class="py-4 px-4 text-[13px] font-extrabold text-slate-500 uppercase tracking-wider">Tanggal & Waktu</th>
                        <th class="py-4 px-4 text-[13px] font-extrabold text-slate-500 uppercase tracking-wider">Pelanggan</th>
                        <th class="py-4 px-4 text-[13px] font-extrabold text-slate-500 uppercase tracking-wider">Jenis Transaksi</th>
                        <th class="py-4 px-4 text-[13px] font-extrabold text-slate-500 uppercase tracking-wider">Petugas Kasir</th>
                        <th class="py-4 px-5 text-[13px] font-extrabold text-slate-500 uppercase tracking-wider text-right">Nominal Transaksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if(count($dataTransaksi) > 0): ?>
                        <?php foreach($dataTransaksi as $row): ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-4 px-5">
                                <span class="text-[15px] font-black <?php echo ($row['TipeTransaksi'] == 'Penjualan') ? 'text-[#3b82f6]' : 'text-rose-500'; ?>">
                                    #TRX-<?php echo $row['TransaksiID']; ?>
                                </span>
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex flex-col gap-0.5">
                                    <span class="text-[15px] font-bold text-slate-700"><?php echo date('d M Y', strtotime($row['TanggalWaktu'])); ?></span>
                                    <span class="text-[13px] font-semibold text-slate-400"><?php echo date('H:i', strtotime($row['TanggalWaktu'])); ?> WIB</span>
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                <?php if($row['NamaPelanggan']): ?>
                                    <span class="text-[15px] font-extrabold text-slate-800"><?php echo $row['NamaPelanggan']; ?></span>
                                <?php else: ?>
                                    <span class="text-[14px] font-bold italic text-slate-400">Pelanggan Umum</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-4">
                                <?php if($row['TipeTransaksi'] == 'Penjualan'): ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[13px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-200">
                                        <i class="bi bi-cart-check"></i> Penjualan
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[13px] font-bold bg-rose-50 text-rose-600 border border-rose-200">
                                        <i class="bi bi-arrow-down-left-square"></i> Buyback
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-4">
                                <span class="text-[14px] font-semibold text-slate-500"><?php echo $row['NamaKaryawan']; ?></span>
                            </td>
                            <td class="py-4 px-5 text-right">
                                <span class="text-[16px] font-black <?php echo ($row['TipeTransaksi'] == 'Penjualan') ? 'text-emerald-600' : 'text-rose-600'; ?>">
                                    <?php echo ($row['TipeTransaksi'] == 'Penjualan') ? '+' : '-'; ?> Rp <?php echo number_format($row['TotalTransaksi'], 0, ',', '.'); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="py-16 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <i class="bi bi-inbox text-5xl mb-4 opacity-20"></i>
                                    <h6 class="text-[17px] font-bold text-slate-500 mb-1">Tidak ada transaksi.</h6>
                                    <p class="text-[14px] font-medium mb-0">Belum ada pergerakan kas pada periode yang dipilih.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot class="bg-blue-50 border-t border-blue-100">
                    <tr>
                        <td colspan="5" class="py-5 px-4 text-right">
                            <span class="text-[14px] font-extrabold text-blue-800 uppercase tracking-widest">Grand Total Omzet Bersih</span>
                        </td>
                        <td class="py-5 px-5 text-right">
                            <span class="text-[22px] font-black text-[#3b82f6]">Rp <?php echo number_format($grandTotal, 0, ',', '.'); ?></span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>

<?php include '../../layouts/footer.php'; ?>