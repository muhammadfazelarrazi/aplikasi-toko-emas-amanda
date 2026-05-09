<?php
session_start();
include '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_SESSION['keranjang'])) {

    $isAjax = isset($_POST['ajax']) && $_POST['ajax'] == '1';

    $kasirID = $_SESSION['user_id'] ?? 1; 
    $namaPel = $_POST['nama_pelanggan'];
    $hpPel   = $_POST['no_hp'];
    $emailPel= $_POST['email_pelanggan'];
    $alamat  = '-'; 
    
    $totalOngkos = isset($_POST['total_ongkos']) && $_POST['total_ongkos'] !== '' ? (int)$_POST['total_ongkos'] : 0;
    $diskon      = isset($_POST['diskon']) && $_POST['diskon'] !== '' ? (int)$_POST['diskon'] : 0;
    $metodeBayar = $_POST['metode_bayar'];
    
    $subtotalEmas = 0;
    foreach ($_SESSION['keranjang'] as $item) {
        $subtotalEmas += $item['HargaTotal'];
    }
    
    $grandTotal = $subtotalEmas + $totalOngkos - $diskon;
    if ($grandTotal < 0) { $grandTotal = 0; }

    mysqli_begin_transaction($koneksi);

    try {
        // A. DATA PELANGGAN
        $cekPel = mysqli_query($koneksi, "SELECT PelangganID FROM pelanggan WHERE NoHP = '$hpPel' LIMIT 1");
        if (mysqli_num_rows($cekPel) > 0) {
            $pelangganID = mysqli_fetch_assoc($cekPel)['PelangganID'];
            mysqli_query($koneksi, "UPDATE pelanggan SET Email='$emailPel', NamaPelanggan='$namaPel' WHERE PelangganID='$pelangganID'");
        } else {
            mysqli_query($koneksi, "INSERT INTO pelanggan (NamaPelanggan, NoHP, Email, Alamat) VALUES ('$namaPel', '$hpPel', '$emailPel', '$alamat')");
            $pelangganID = mysqli_insert_id($koneksi);
        }

        // B. HEADER TRANSAKSI
        $tgl = date('Y-m-d H:i:s');
        $queryHeader = "INSERT INTO transaksi (PelangganID, KaryawanID, TanggalWaktu, TipeTransaksi, TotalOngkos, TotalDiskon, TotalTransaksi) 
                        VALUES ('$pelangganID', '$kasirID', '$tgl', 'Penjualan', '$totalOngkos', '$diskon', '$grandTotal')";
        
        if (!mysqli_query($koneksi, $queryHeader)) { throw new Exception("Gagal simpan header"); }
        $transaksiID = mysqli_insert_id($koneksi);

        // C. DETAIL BARANG
        foreach ($_SESSION['keranjang'] as $item) {
            $barangID = $item['BarangID'];
            $hargaSatuan = $item['HargaTotal']; 
            mysqli_query($koneksi, "INSERT INTO detail_transaksi_barang (TransaksiID, BarangID, HargaSatuanSaatItu, Ongkos) VALUES ('$transaksiID', '$barangID', '$hargaSatuan', 0)");
            mysqli_query($koneksi, "UPDATE barang_stok SET Status = 'Terjual' WHERE BarangID = '$barangID'");
        }

        // D. PEMBAYARAN
        mysqli_query($koneksi, "INSERT INTO pembayaran (TransaksiID, MetodeID, JumlahBayar) VALUES ('$transaksiID', '$metodeBayar', '$grandTotal')");

        // COMMIT TRANSAKSI
        mysqli_commit($koneksi);

        // E. KIRIM EMAIL (Opsional)
        $kirim = false;
        if (!empty($emailPel)) {
            include '../../library/mailer.php';
            $kirim = kirimSuratEmas($emailPel, $transaksiID, $namaPel);
        }

        // =========================================================================
        // F. BUILD WUJUD NOTA THERMAL HTML UNTUK DIKIRIM KE MODAL
        // =========================================================================
        $qKasir = mysqli_query($koneksi, "SELECT NamaKaryawan FROM karyawan WHERE KaryawanID = '$kasirID'");
        $namaKasir = ($qKasir && mysqli_num_rows($qKasir) > 0) ? mysqli_fetch_assoc($qKasir)['NamaKaryawan'] : 'Super Admin';
        $tglFormat = date('d/m/Y H:i', strtotime($tgl));

        $notaHtml = '<div style="font-family: \'Courier New\', Courier, monospace; font-size: 12px; color: #000; width: 100%; line-height: 1.2;">';
        $notaHtml .= '<div style="text-align: center;"><h2 style="margin: 0; font-size: 16px;">TOKO MAS AMANDA</h2><p style="margin: 5px 0 0 0;">Jl. Ps. Pancasila, Lengkongsari<br>Tasikmalaya 46111</p><p style="margin: 0 0 5px 0;">Telp: 0812-3456-7890</p></div>';
        $notaHtml .= '<div style="border-bottom: 1px dashed #000; margin: 8px 0;"></div>';
        
        $notaHtml .= '<table style="width: 100%; border-collapse: collapse; font-size: 12px;">';
        $notaHtml .= '<tr><td style="padding: 2px 0;">No. Nota</td><td style="padding: 2px 0; text-align: right; font-weight: bold;">#TRX-'.$transaksiID.'</td></tr>';
        $notaHtml .= '<tr><td style="padding: 2px 0;">Tanggal</td><td style="padding: 2px 0; text-align: right;">'.$tglFormat.'</td></tr>';
        $notaHtml .= '<tr><td style="padding: 2px 0;">Kasir</td><td style="padding: 2px 0; text-align: right;">'.$namaKasir.'</td></tr>';
        $notaHtml .= '<tr><td style="padding: 2px 0;">Pelanggan</td><td style="padding: 2px 0; text-align: right;">'.$namaPel.'</td></tr>';
        $notaHtml .= '</table>';
        
        $notaHtml .= '<div style="border-bottom: 1px dashed #000; margin: 8px 0;"></div>';
        
        $notaHtml .= '<table style="width: 100%; border-collapse: collapse; font-size: 12px;">';
        foreach ($_SESSION['keranjang'] as $item) {
            $notaHtml .= '<tr><td colspan="2" style="padding: 2px 0; font-weight: bold;">'.$item['Nama'].' ('.$item['Kadar'].')</td></tr>';
            $notaHtml .= '<tr><td style="padding: 2px 0;">SN: '.$item['Kode'].' - '.$item['Berat'].'g</td><td style="padding: 2px 0; text-align: right;">Rp '.number_format($item['HargaTotal'], 0, ',', '.').'</td></tr>';
        }
        $notaHtml .= '</table>';
        
        $notaHtml .= '<div style="border-bottom: 1px dashed #000; margin: 8px 0;"></div>';
        
        $notaHtml .= '<table style="width: 100%; border-collapse: collapse; font-size: 12px;">';
        $notaHtml .= '<tr><td style="padding: 2px 0;">Subtotal Emas</td><td style="padding: 2px 0; text-align: right;">Rp '.number_format($subtotalEmas, 0, ',', '.').'</td></tr>';
        if($totalOngkos > 0) { $notaHtml .= '<tr><td style="padding: 2px 0;">Ongkos Bikin</td><td style="padding: 2px 0; text-align: right;">Rp '.number_format($totalOngkos, 0, ',', '.').'</td></tr>'; }
        if($diskon > 0) { $notaHtml .= '<tr><td style="padding: 2px 0;">Potongan Harga</td><td style="padding: 2px 0; text-align: right;">(Rp '.number_format($diskon, 0, ',', '.').')</td></tr>'; }
        $notaHtml .= '<tr><td style="padding: 6px 0 2px 0; font-weight: bold; font-size: 14px;">GRAND TOTAL</td><td style="padding: 6px 0 2px 0; text-align: right; font-weight: bold; font-size: 14px;">Rp '.number_format($grandTotal, 0, ',', '.').'</td></tr>';
        $notaHtml .= '</table>';
        
        $notaHtml .= '<div style="border-bottom: 1px dashed #000; margin: 8px 0;"></div>';
        $notaHtml .= '<div style="text-align: center;"><p style="margin: 5px 0;">Terima Kasih atas Kunjungan Anda</p><p style="font-size: 10px; margin: 5px 0;">Barang yang sudah dibeli dapat dijual kembali dengan potongan harga sesuai ketentuan toko.</p>';
        if (!empty($emailPel) && $kirim) { $notaHtml .= '<p style="font-size: 10px; margin-top: 5px; color: #0d6efd;">** Surat Emas Digital Dikirim ke Email **</p>'; }
        $notaHtml .= '</div></div>';

        // Hapus Keranjang setelah nota dirakit
        unset($_SESSION['keranjang']);

        if ($isAjax) {
            echo json_encode(['status' => 'success', 'receipt_html' => $notaHtml]);
            exit;
        }

    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        if ($isAjax) {
            echo json_encode(['status' => 'error', 'message' => "Gagal Memproses: " . $e->getMessage()]);
            exit;
        }
    }
}
?>