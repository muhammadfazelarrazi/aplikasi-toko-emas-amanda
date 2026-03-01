<?php
// Konfigurasi Database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "toko_emas_amanda";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Koneksi Database Gagal: " . mysqli_connect_error());
}

// ==================================================================
// SETTING ZONA WAKTU INDONESIA (WIB / GMT+7)
// ==================================================================

// 1. Atur Waktu di PHP (Agar fungsi date() benar)
date_default_timezone_set('Asia/Jakarta');

// 2. Atur Waktu di MySQL (Agar fungsi NOW() atau CURRENT_TIMESTAMP benar)
mysqli_query($koneksi, "SET time_zone = '+07:00'");

// ==================================================================

// DEFINISI BASE URL
// Sesuaikan dengan nama folder di htdocs kamu
$base_url = "http://localhost/toko_emas_amanda/"; 
?>