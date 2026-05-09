<?php
session_start();
session_destroy();

// Arahkan kembali ke file login (Pakai .php lagi)
header("Location: login.php");
exit;
?>