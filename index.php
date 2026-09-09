<?php
session_start();

// Jika belum login, lempar ke halaman login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Jika sudah login, langsung arahkan ke Dashboard Utama
header("Location: dashboard.php");
exit;
?>