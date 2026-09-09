<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Prepared statement untuk mencegah SQL Injection
    $stmt = $pdo->prepare("SELECT u.*, r.nama_role FROM users u JOIN roles r ON u.role_id = r.id WHERE u.email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Simpan variabel penting ke Session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['nip'] = $user['nip'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['role_name'] = $user['nama_role'];

        header("Location: index.php");
        exit;
    } else {
        header("Location: login.php?error=Email atau password salah.");
        exit;
    }
}