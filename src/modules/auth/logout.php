<?php
session_start();
session_destroy();
// Arahkan kembali ke file login (sesuaikan path)
header("Location: login.php");
exit;
?>