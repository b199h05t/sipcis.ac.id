<?php
require_once 'config/database.php';

// Password baru yang ingin diberikan ke pengguna
$password_baru = 'passwordBaru123';
$user_id = 4; // ID pengguna yang ingin diubah

// Buat hash baru dari password tersebut
$password_hash = password_hash($password_baru, PASSWORD_BCRYPT);

// Update ke database (Perbaikan pada tanda ->)
$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
$stmt->execute([$password_hash, $user_id]);

echo "Password berhasil diperbarui. Password baru pengguna: " . htmlspecialchars($password_baru);
?>