<?php
session_start();
include '../../config/database.php';

// Cek apakah token ada di URL
if (!isset($_GET['token'])) {
    die("Akses ditolak: Token tidak valid atau hilang.");
}

$token = $_GET['token'];

// 1. DECRYPT TOKEN
$dataToken = base64_decode($token);
list($idTransaksi, $timestamp) = explode('|', $dataToken);

// 2. VALIDASI DATA
if (!is_numeric($idTransaksi) || empty($idTransaksi)) {
    die("Akses ditolak: Data transaksi tidak dikenali.");
}

// AMBIL DATA TRANSAKSI
$queryHeader = "SELECT t.*, p.NamaPelanggan, p.Alamat, k.NamaKaryawan 
                FROM transaksi t
                JOIN pelanggan p ON t.PelangganID = p.PelangganID
                JOIN karyawan k ON t.KaryawanID = k.KaryawanID
                WHERE t.TransaksiID = '$idTransaksi'";
$resHeader = mysqli_query($koneksi, $queryHeader);
$header = mysqli_fetch_assoc($resHeader);

if (!$header) {
    die("Surat tidak ditemukan untuk ID tersebut.");
}

// AMBIL DATA BARANG
$queryDetail = "SELECT dt.*, bs.KodeBarang, bs.BeratGram, pk.NamaProduk, pk.Kadar 
                FROM detail_transaksi_barang dt
                JOIN barang_stok bs ON dt.BarangID = bs.BarangID
                JOIN produk_katalog pk ON bs.ProdukKatalogID = pk.ProdukKatalogID
                WHERE dt.TransaksiID = '$idTransaksi'";
$resDetail = mysqli_query($koneksi, $queryDetail);

// Gunakan tampilan surat_emas.php yang sudah ada untuk menampilkan data
// Kita hanya perlu include filenya dengan data yang sudah siap
include 'surat_emas.php';
?>