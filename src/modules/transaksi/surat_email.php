<?php
// Cukup lempar pelanggan langsung ke pembuat PDF!
if (isset($_GET['token'])) {
    header("Location: surat_emas.php?token=" . $_GET['token']);
    exit;
} else {
    die("Akses ditolak: Token tidak ditemukan.");
}
?>