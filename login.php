<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: approval/index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="stylesheet" href="assets/css/style.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SIP-CIS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-card">
        <h2>SIP-CIS Login</h2>
        <p>Sistem Informasi Pencatatan Cuti, Izin, & Sakit</p>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
        <?php endif; ?>

        <form action="auth.php" method="POST">
            <div class="form-group">
                <label for="email">Email Kampus</label>
                <input type="email" name="email" id="email" required placeholder="nama@univ.ac.id">
            </div>
            <div class="form-group">
    <label for="password">Password</label>
    <div class="password-wrapper">
        <input type="password" name="password" id="password" required placeholder="••••••••">
        <button type="button" class="btn-toggle-password" onclick="toggleLoginPassword(this)">👁️</button>
    </div>
</div>

<script>
function toggleLoginPassword(btn) {
    const inputField = document.getElementById('password');
    if (inputField.type === 'password') {
        inputField.type = 'text';
        btn.innerText = '🙈';
    } else {
        inputField.type = 'password';
        btn.innerText = '👁️';
    }
}
</script>
            <button type="submit" class="btn-primary">Masuk ke Sistem</button>
        </form>
    </div>
</body>
</html>