<?php
session_start();
include '../../config/database.php';

// Cek apakah ada kiriman data dari form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_SESSION['keranjang'])) {

    // 1. Ambil Data dari Form
    $kasirID = $_SESSION['user_id'] ?? 1; 
    $namaPel = $_POST['nama_pelanggan'];
    $hpPel   = $_POST['no_hp'];
    $emailPel= $_POST['email_pelanggan'];
    $alamat  = '-'; 
    
    // PERBAIKAN ERROR MENGHITUNG KOSONG: Paksa inputan menjadi integer (angka asli). Jika dikosongkan, jadikan 0.
    $totalOngkos = isset($_POST['total_ongkos']) && $_POST['total_ongkos'] !== '' ? (int)$_POST['total_ongkos'] : 0;
    $diskon      = isset($_POST['diskon']) && $_POST['diskon'] !== '' ? (int)$_POST['diskon'] : 0;
    
    $metodeBayar = $_POST['metode_bayar'];
    
    // Hitung Total Transaksi
    $subtotalEmas = 0;
    foreach ($_SESSION['keranjang'] as $item) {
        $subtotalEmas += $item['HargaTotal'];
    }
    
    // RUMUS BARU: Subtotal Emas + Ongkos Bikin - Potongan (Tawar)
    $grandTotal = $subtotalEmas + $totalOngkos - $diskon;
    
    // Cegah Grand Total menjadi minus (jika kasir salah ketik diskon terlalu besar)
    if ($grandTotal < 0) {
        $grandTotal = 0;
    }

    // --- MULAI TRANSAKSI DATABASE ---
    mysqli_begin_transaction($koneksi);

    try {
        // A. SIMPAN / UPDATE DATA PELANGGAN
        $cekPel = mysqli_query($koneksi, "SELECT PelangganID FROM pelanggan WHERE NoHP = '$hpPel' LIMIT 1");
        if (mysqli_num_rows($cekPel) > 0) {
            $pelangganID = mysqli_fetch_assoc($cekPel)['PelangganID'];
            // Update email dan nama terbaru
            mysqli_query($koneksi, "UPDATE pelanggan SET Email='$emailPel', NamaPelanggan='$namaPel' WHERE PelangganID='$pelangganID'");
        } else {
            mysqli_query($koneksi, "INSERT INTO pelanggan (NamaPelanggan, NoHP, Email, Alamat) VALUES ('$namaPel', '$hpPel', '$emailPel', '$alamat')");
            $pelangganID = mysqli_insert_id($koneksi);
        }

        // B. SIMPAN HEADER TRANSAKSI (DENGAN ONGKOS DAN DISKON)
        $tgl = date('Y-m-d H:i:s');
        $queryHeader = "INSERT INTO transaksi (PelangganID, KaryawanID, TanggalWaktu, TipeTransaksi, TotalOngkos, TotalDiskon, TotalTransaksi) 
                        VALUES ('$pelangganID', '$kasirID', '$tgl', 'Penjualan', '$totalOngkos', '$diskon', '$grandTotal')";
        
        if (!mysqli_query($koneksi, $queryHeader)) {
            // Tambahkan mysqli_error agar jika gagal, pesan error detail dari database muncul
            throw new Exception("Gagal simpan header transaksi: " . mysqli_error($koneksi));
        }
        $transaksiID = mysqli_insert_id($koneksi);

        // C. SIMPAN DETAIL BARANG & UPDATE STOK
        foreach ($_SESSION['keranjang'] as $item) {
            $barangID = $item['BarangID'];
            $hargaSatuan = $item['HargaTotal']; 
            
            // Insert Detail
            $queryDetail = "INSERT INTO detail_transaksi_barang (TransaksiID, BarangID, HargaSatuanSaatItu, Ongkos) 
                            VALUES ('$transaksiID', '$barangID', '$hargaSatuan', 0)";
            mysqli_query($koneksi, $queryDetail);

            // Update Stok jadi 'Terjual'
            $queryUpdateStok = "UPDATE barang_stok SET Status = 'Terjual' WHERE BarangID = '$barangID'";
            mysqli_query($koneksi, $queryUpdateStok);
        }

        // D. SIMPAN PEMBAYARAN
        $queryBayar = "INSERT INTO pembayaran (TransaksiID, MetodeID, JumlahBayar) 
                       VALUES ('$transaksiID', '$metodeBayar', '$grandTotal')";
        mysqli_query($koneksi, $queryBayar);

        // --- COMMIT TRANSAKSI (Simpan Permanen) ---
        mysqli_commit($koneksi);


        // ============================================================
        // E. PROSES KIRIM EMAIL (DIAKTIFKAN)
        // ============================================================
        $pesanStatus = "Transaksi Berhasil! Nota siap dicetak.";

        if (!empty($emailPel)) {
            // Panggil Library Mailer
            include '../../library/mailer.php';
            
            // Jalankan fungsi kirim
            $kirim = kirimSuratEmas($emailPel, $transaksiID, $namaPel);
            
            if ($kirim) {
                $pesanStatus .= "\\n\\n[INFO] Surat Emas Digital SUKSES terkirim ke email pelanggan.";
            } else {
                $pesanStatus .= "\\n\\n[WARNING] Gagal mengirim email. Cek koneksi internet atau pengaturan SMTP.";
            }
        }
        // ============================================================


        // Kosongkan Keranjang
        unset($_SESSION['keranjang']);

        // Redirect ke Cetak Nota
        echo "<script>
            alert('$pesanStatus');
            window.location = 'cetak_nota.php?id=$transaksiID';
        </script>";

    } catch (Exception $e) {
        // Kalau ada error database, batalkan semua
        mysqli_rollback($koneksi);
        echo "Gagal Memproses Transaksi: " . $e->getMessage();
        echo "<br><br><a href='input_jual.php' style='padding: 10px 20px; background: #0d6efd; color: white; text-decoration: none; border-radius: 5px;'>Kembali ke Kasir</a>";
    }

} else {
    header("Location: input_jual.php");
}
?>