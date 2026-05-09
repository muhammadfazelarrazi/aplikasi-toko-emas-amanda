<?php
// Konfigurasi Database
$host = "localhost";
$user = "fazn3461_fazel";
$pass = "Fxzl_130601";
$db   = "fazn3461_amanda";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Koneksi Database Gagal: " . mysqli_connect_error());
}

// ==================================================================
// SETTING ZONA WAKTU INDONESIA (WIB / GMT+7)
// ==================================================================
date_default_timezone_set('Asia/Jakarta');
mysqli_query($koneksi, "SET time_zone = '+07:00'");

// ==================================================================
// DEFINISI BASE URL PINTAR (AUTO-DETECT LOKAL VS HOSTING)
// ==================================================================
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' || 
             isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? "https://" : "http://";

$domain = $_SERVER['HTTP_HOST'];

// LOGIKA OTOMATIS BARU: 
if ($domain == 'localhost' || $domain == '127.0.0.1') {
    // Jika di laptop/lokal
    $base_url = $protocol . $domain . "/toko_emas_amanda/"; 
} else {
    // Jika di hosting Rumahweb, masuk ke folder AMANDA
    $base_url = $protocol . $domain . "/AMANDA/"; 
}
?>