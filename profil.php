<?php
session_start();
require_once 'config/database.php';

// Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id   = $_SESSION['user_id'];
$role_id   = $_SESSION['role_id'];
$role_name = $_SESSION['role_name'];

$pesan_sukses = '';
$pesan_error  = '';

// PROSES UPDATE DATA PROFIL & PASSWORD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // A. Proses Ubah Nama Lengkap / Profile
    if ($action === 'update_profile') {
        $nama_baru  = trim($_POST['nama']);
        $email_baru = trim($_POST['email']);

        if (!empty($nama_baru) && !empty($email_baru)) {
            try {
                $stmt_check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt_check->execute([$email_baru, $user_id]);

                if ($stmt_check->rowCount() > 0) {
                    $pesan_error = "Email sudah digunakan oleh akun lain!";
                } else {
                    $stmt_update = $pdo->prepare("UPDATE users SET nama = ?, email = ? WHERE id = ?");
                    $stmt_update->execute([$nama_baru, $email_baru, $user_id]);

                    $_SESSION['nama'] = $nama_baru;
                    $pesan_sukses = "Data profil berhasil diperbarui!";
                }
            } catch (PDOException $e) {
                $pesan_error = "Gagal memperbarui profil: " . $e->getMessage();
            }
        } else {
            $pesan_error = "Nama dan email tidak boleh kosong.";
        }
    }

    // B. Proses Ubah Password
    if ($action === 'update_password') {
        $password_lama   = $_POST['password_lama'];
        $password_baru   = $_POST['password_baru'];
        $konfirmasi_pass = $_POST['konfirmasi_password'];

        if (empty($password_lama) || empty($password_baru) || empty($konfirmasi_pass)) {
            $pesan_error = "Semua bidang password wajib diisi!";
        } elseif ($password_baru !== $konfirmasi_pass) {
            $pesan_error = "Konfirmasi password baru tidak cocok!";
        } elseif (strlen($password_baru) < 6) {
            $pesan_error = "Password baru minimal harus 6 karakter.";
        } else {
            $stmt_pass = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt_pass->execute([$user_id]);
            $hash_lama = $stmt_pass->fetchColumn();

            if (password_verify($password_lama, $hash_lama)) {
                $hash_baru = password_hash($password_baru, PASSWORD_BCRYPT);
                $stmt_change = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt_change->execute([$hash_baru, $user_id]);

                $pesan_sukses = "Password berhasil diubah! Gunakan password baru saat login berikutnya.";
            } else {
                $pesan_error = "Password lama Anda salah!";
            }
        }
    }
}

// Ambil data profil terbaru
$stmt_user = $pdo->prepare("
    SELECT u.*, j.nama_jabatan, r.nama_role 
    FROM users u 
    JOIN jabatan j ON u.id_jabatan = j.id 
    JOIN roles r ON u.role_id = r.id 
    WHERE u.id = ?
");
$stmt_user->execute([$user_id]);
$user = $stmt_user->fetch();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Akun & Profil - SIP-CIS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .profile-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        @media (max-width: 768px) { .profile-grid { grid-template-columns: 1fr; } }
        .form-card { background: #ffffff; padding: 25px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; }
        .form-card h4 { margin-top: 0; margin-bottom: 16px; color: #0f172a; font-size: 16px; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <div class="brand">
                <h2>SIP-CIS</h2>
                <p>STMIK Bina Patria</p>
            </div>
            <ul class="nav-menu">
                <li><a href="dashboard.php">📌 Dashboard</a></li>
                <?php if ($role_id == 1): ?>
                    <li><a href="karyawan/form_pengajuan.php">📝 Ajukan Cuti/Izin</a></li>
                <?php else: ?>
                    <li><a href="approval/index.php">⏳ Verifikasi Approval</a></li>
                <?php endif; ?>
                <li class="active"><a href="profil.php">⚙️ Pengaturan Profil</a></li>
                <li><a href="logout.php" class="logout-link">🚪 Keluar (Logout)</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="topbar">
                <h3>Pengaturan Akun & Keamanan ⚙️</h3>
                <p>Kelola data profil dan ubah kata sandi akun Anda</p>
            </div>

            <?php if (!empty($pesan_sukses)): ?>
                <div class="alert alert-success" style="background-color: #dcfce7; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
                    ✅ <?= html_escape($pesan_sukses); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($pesan_error)): ?>
                <div class="alert alert-danger" style="background-color: #fee2e2; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
                    ⚠️ <?= html_escape($pesan_error); ?>
                </div>
            <?php endif; ?>

            <div class="profile-grid">
                <!-- Form Ubah Data Profil -->
                <div class="form-card">
                    <h4>👤 Data Identitas & Username</h4>
                    <form action="profil.php" method="POST">
                        <input type="hidden" name="action" value="update_profile">

                        <div class="form-group">
                            <label>NIP (Nomor Induk Pegawai)</label>
                            <input type="text" value="<?= html_escape($user['nip']); ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label>Jabatan & Role Access</label>
                            <input type="text" value="<?= html_escape($user['nama_jabatan']); ?> (Role: <?= html_escape($user['nama_role']); ?>)" readonly>
                        </div>

                        <div class="form-group">
                            <label for="nama">Nama Lengkap / Username</label>
                            <input type="text" name="nama" id="nama" value="<?= html_escape($user['nama']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Kampus</label>
                            <input type="email" name="email" id="email" value="<?= html_escape($user['email']); ?>" required>
                        </div>

                        <button type="submit" class="btn-primary" style="margin-top: 10px;">Simpan Perubahan Profil</button>
                    </form>
                </div>

                <!-- Form Ubah Password dengan Fitur Show/Hide -->
                <div class="form-card">
                    <h4>🔒 Ubah Kata Sandi / Password</h4>
                    <form action="profil.php" method="POST">
                        <input type="hidden" name="action" value="update_password">

                        <div class="form-group">
                            <label for="password_lama">Password Saat Ini</label>
                            <div class="password-wrapper">
                                <input type="password" name="password_lama" id="password_lama" required placeholder="••••••••">
                                <button type="button" class="btn-toggle-password" onclick="togglePasswordVisibility('password_lama', this)">
                                    👁️
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password_baru">Password Baru (Min. 6 Karakter)</label>
                            <div class="password-wrapper">
                                <input type="password" name="password_baru" id="password_baru" required placeholder="••••••••">
                                <button type="button" class="btn-toggle-password" onclick="togglePasswordVisibility('password_baru', this)">
                                    👁️
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="konfirmasi_password">Konfirmasi Password Baru</label>
                            <div class="password-wrapper">
                                <input type="password" name="konfirmasi_password" id="konfirmasi_password" required placeholder="••••••••">
                                <button type="button" class="btn-toggle-password" onclick="togglePasswordVisibility('konfirmasi_password', this)">
                                    👁️
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn-primary" style="background-color: #0284c7; margin-top: 10px;">Ganti Password</button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <!-- Script JavaScript untuk Toggle Show/Hide Password -->
    <script>
        function togglePasswordVisibility(inputId, btn) {
            const inputField = document.getElementById(inputId);
            if (inputField.type === 'password') {
                inputField.type = 'text';
                btn.innerText = '🙈'; // Icon saat password terlihat (Hide)
            } else {
                inputField.type = 'password';
                btn.innerText = '👁️'; // Icon saat password tersembunyi (Show)
            }
        }
    </script>
</body>
</html>