<?php
// 1. Matikan peringatan Deprecated agar tidak bocor ke PDF
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', 0);

// 2. Aktifkan Output Buffering (Vakum Pembersih)
ob_start(); 

session_start();
include '../../config/database.php';

// 3. Panggil DomPDF dari folder library
require '../../library/dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// 4. Tangkap Parameter Tanggal
$tgl_mulai = isset($_GET['mulai']) ? $_GET['mulai'] : date('Y-m-d');
$tgl_selesai = isset($_GET['selesai']) ? $_GET['selesai'] : date('Y-m-d');

$format_mulai = date('d F Y', strtotime($tgl_mulai));
$format_selesai = date('d F Y', strtotime($tgl_selesai));
$teks_periode = ($tgl_mulai == $tgl_selesai) ? $format_mulai : "$format_mulai s/d $format_selesai";

// 5. Query Data
$query = "SELECT t.*, p.NamaPelanggan, k.NamaKaryawan 
          FROM transaksi t
          LEFT JOIN pelanggan p ON t.PelangganID = p.PelangganID
          JOIN karyawan k ON t.KaryawanID = k.KaryawanID
          WHERE DATE(t.TanggalWaktu) BETWEEN '$tgl_mulai' AND '$tgl_selesai'
          ORDER BY t.TanggalWaktu DESC";
$result = mysqli_query($koneksi, $query);

// 6. Logika Pemisahan Data
$dataPenjualan = [];
$dataBuyback = [];
$totalPenjualan = 0;
$totalBuyback = 0;

if($result && mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
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

// 7. HTML Laporan
$html = '
<!DOCTYPE html>
<html>
<head>
    <title>Laporan Keuangan Amanda</title>
    <style>
        @page { margin: 25px 35px; }
        body { font-family: "Helvetica", sans-serif; font-size: 9pt; color: #333; }
        table { width: 100%; border-collapse: collapse; }
        .kop-table { border-bottom: 3px solid #0d6efd; padding-bottom: 10px; margin-bottom: 15px; }
        .data-table th { background-color: #f2f2f2; border: 1px solid #999; padding: 6px; font-weight: bold; }
        .data-table td { border: 1px solid #999; padding: 6px; }
        .section-title { font-size: 11pt; font-weight: bold; margin: 15px 0 5px 0; color: #0d6efd; border-bottom: 1px solid #0d6efd; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
    </style>
</head>
<body>

    <table class="kop-table">
        <tr>
            <td style="text-align: center;">
                <span style="color: #0d6efd; font-size: 20pt; font-weight: bold;">TOKO EMAS AMANDA</span><br>
                <span style="font-size: 9pt; color: #555;">Jl. Ps. Pancasila, Lengkongsari, Kec. Tawang, Kota Tasikmalaya</span><br>
                <span style="font-size: 9pt; color: #555;">WA: 0812-3456-7890 | Periode Laporan: '.$format_mulai.'</span>
            </td>
        </tr>
    </table>

    <div class="section-title">A. Transaksi Penjualan</div>
    <table class="data-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">Nota</th>
                <th width="20%">Waktu</th>
                <th width="30%">Pelanggan</th>
                <th width="30%" class="text-right">Total (Rp)</th>
            </tr>
        </thead>
        <tbody>';

$noJ = 1;
if(count($dataPenjualan) > 0) {
    foreach($dataPenjualan as $j) {
        $html .= '<tr>
            <td class="text-center">'.$noJ++.'</td>
            <td class="text-center text-bold">#'.$j['TransaksiID'].'</td>
            <td class="text-center">'.date('d/m/y H:i', strtotime($j['TanggalWaktu'])).'</td>
            <td>'.$j['NamaPelanggan'].'</td>
            <td class="text-right text-bold">'.number_format($j['TotalTransaksi'], 0, ',', '.').'</td>
        </tr>';
    }
} else {
    $html .= '<tr><td colspan="5" class="text-center">Tidak ada data penjualan.</td></tr>';
}
$html .= '</tbody>
        <tfoot>
            <tr style="background-color: #e8f5e9;">
                <td colspan="4" class="text-right text-bold">TOTAL PENJUALAN</td>
                <td class="text-right text-bold">Rp '.number_format($totalPenjualan, 0, ',', '.').'</td>
            </tr>
        </tfoot>
    </table>

    <div class="section-title" style="color:#dc3545; border-bottom-color:#dc3545;">B. Transaksi Buyback</div>
    <table class="data-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">Nota</th>
                <th width="20%">Waktu</th>
                <th width="30%">Pelanggan</th>
                <th width="30%" class="text-right">Total (Rp)</th>
            </tr>
        </thead>
        <tbody>';

$noB = 1;
if(count($dataBuyback) > 0) {
    foreach($dataBuyback as $b) {
        $html .= '<tr>
            <td class="text-center">'.$noB++.'</td>
            <td class="text-center text-bold" style="color:#dc3545;">#'.$b['TransaksiID'].'</td>
            <td class="text-center">'.date('d/m/y H:i', strtotime($b['TanggalWaktu'])).'</td>
            <td>'.$b['NamaPelanggan'].'</td>
            <td class="text-right text-bold">'.number_format($b['TotalTransaksi'], 0, ',', '.').'</td>
        </tr>';
    }
} else {
    $html .= '<tr><td colspan="5" class="text-center">Tidak ada data buyback.</td></tr>';
}
$html .= '</tbody>
        <tfoot>
            <tr style="background-color: #ffebee;">
                <td colspan="4" class="text-right text-bold">TOTAL BUYBACK</td>
                <td class="text-right text-bold">Rp '.number_format($totalBuyback, 0, ',', '.').'</td>
            </tr>
        </tfoot>
    </table>

    <table width="100%" style="border: 2px solid #333; margin-top: 20px;">
        <tr style="background-color: #f8f9fa;">
            <td width="70%" style="padding: 12px; text-align: right; font-weight: bold; border-right: 1px solid #333;">GRAND TOTAL OMZET BERSIH</td>
            <td width="30%" style="padding: 12px; text-align: right; font-weight: bold; font-size: 12pt; color: #0d6efd;">Rp '.number_format($grandTotal, 0, ',', '.').'</td>
        </tr>
    </table>

    <div style="font-size: 8pt; color: #999; text-align: right; margin-top: 15px; font-style: italic;">
        Dicetak otomatis oleh Sistem Emas Amanda pada '.date('d/m/Y H:i').'
    </div>

</body>
</html>';

// 8. Inisialisasi & Render
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Helvetica');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// 9. Bersihkan Buffer & Stream
ob_end_clean(); 
$dompdf->stream("Laporan_Keuangan_Amanda.pdf", array("Attachment" => 0));