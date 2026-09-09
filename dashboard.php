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
$nama_user = $_SESSION['nama'];
$role_name = $_SESSION['role_name'];

// Ambil sisa cuti user aktif
$stmt_user = $pdo->prepare("SELECT sisa_cuti, nip, j.nama_jabatan FROM users u JOIN jabatan j ON u.id_jabatan = j.id WHERE u.id = ?");
$stmt_user->execute([$user_id]);
$data_user = $stmt_user->fetch();

// Ambilitastatistik berdasarkan Role
if ($role_id == 1) {
    // Statistik untuk Karyawan (Hanya melihat data sendiri)
    $stmt_stat = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status_final = 'Pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status_final = 'Disetujui' THEN 1 ELSE 0 END) as disetujui,
            SUM(CASE WHEN status_final = 'Ditolak' THEN 1 ELSE 0 END) as ditolak
        FROM pengajuan WHERE user_id = ?
    ");
    $stmt_stat->execute([$user_id]);
    $stat = $stmt_stat->fetch();

    // Histori Pengajuan Terbaru Karyawan
    $stmt_recent = $pdo->prepare("
        SELECT p.*, j.nama_jenis 
        FROM pengajuan p 
        JOIN jenis_pengajuan j ON p.jenis_id = j.id 
        WHERE p.user_id = ? 
        ORDER BY p.created_at DESC LIMIT 5
    ");
    $stmt_recent->execute([$user_id]);
    $recent_list = $stmt_recent->fetchAll();

} else {
    // Statistik Global untuk Pimpinan / Approver (KaBag, WK2, Ketua)
    $stmt_stat = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status_final = 'Pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status_final = 'Disetujui' THEN 1 ELSE 0 END) as disetujui,
            SUM(CASE WHEN status_final = 'Ditolak' THEN 1 ELSE 0 END) as ditolak
        FROM pengajuan
    ");
    $stat = $stmt_stat->fetch();

    // Pengajuan Terbaru Seluruh Karyawan
    $stmt_recent = $pdo->query("
        SELECT p.*, u.nama, u.nip, j.nama_jenis 
        FROM pengajuan p 
        JOIN users u ON p.user_id = u.id 
        JOIN jenis_pengajuan j ON p.jenis_id = j.id 
        ORDER BY p.created_at DESC LIMIT 5
    ");
    $recent_list = $stmt_recent->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SIP-CIS STMIK Bina Patria</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="brand">
                <h2>SIP-CIS</h2>
                <p>STMIK Bina Patria</p>
            </div>
            <ul class="nav-menu">
                <li class="active"><a href="dashboard.php">📌 Dashboard</a></li>
                <?php if ($role_id == 1): ?>
                    <li><a href="karyawan/form_pengajuan.php">📝 Ajukan Cuti/Izin</a></li>
                <?php else: ?>
                    <li><a href="approval/index.php">⏳ Verifikasi Approval</a></li>
                <?php endif; ?>
                <li><a href="profil.php">⚙️ Pengaturan Profil</a></li>
                <li><a href="logout.php" class="logout-link">🚪 Keluar (Logout)</a></li>
            </ul>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <!-- Header Topbar -->
            <header class="topbar">
                <div class="user-profile">
                    <h3>Selamat Datang, <?= html_escape($nama_user); ?> 👋</h3>
                    <p><?= html_escape($data_user['nama_jabatan']); ?> | Role: <strong><?= html_escape($role_name); ?></strong></p>
                </div>
            </header>

            <!-- Cards Statistik Overview -->
            <section class="stats-grid">
                <?php if ($role_id == 1): ?>
                <div class="card card-blue">
                    <div class="card-title">Sisa Cuti Tahunan</div>
                    <div class="card-value"><?= $data_user['sisa_cuti']; ?> <small>Hari</small></div>
                </div>
                <?php endif; ?>

                <div class="card card-purple">
                    <div class="card-title">Total Permohonan</div>
                    <div class="card-value"><?= $stat['total'] ?? 0; ?></div>
                </div>

                <div class="card card-yellow">
                    <div class="card-title">Menunggu Persetujuan</div>
                    <div class="card-value"><?= $stat['pending'] ?? 0; ?></div>
                </div>

                <div class="card card-green">
                    <div class="card-title">Telah Disetujui</div>
                    <div class="card-value"><?= $stat['disetujui'] ?? 0; ?></div>
                </div>

                <div class="card card-red">
                    <div class="card-title">Ditolak</div>
                    <div class="card-value"><?= $stat['ditolak'] ?? 0; ?></div>
                </div>
            </section>

            <!-- Tabel Aktivitas Terbaru -->
            <section class="content-section">
                <div class="section-header">
                    <h3>Riwayat Pengajuan Terbaru</h3>
                    <?php if ($role_id == 1): ?>
                        <a href="karyawan/form_pengajuan.php" class="btn-sm">+ Buat Pengajuan Baru</a>
                    <?php else: ?>
                        <a href="approval/index.php" class="btn-sm">Lihat Semua Antrean</a>
                    <?php endif; ?>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <?php if ($role_id != 1): ?>
                                    <th>Pemohon</th>
                                <?php endif; ?>
                                <th>Jenis</th>
                                <th>Tanggal Pelaksanaan</th>
                                <th>Durasi</th>
                                <th>Alasan</th>
                                <th>Status Final</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_list)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 20px;">Belum ada riwayat pengajuan.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recent_list as $row): ?>
                                    <tr>
                                        <?php if ($role_id != 1): ?>
                                            <td>
                                                <strong><?= html_escape($row['nama']); ?></strong><br>
                                                <small class="text-muted"><?= html_escape($row['nip']); ?></small>
                                            </td>
                                        <?php endif; ?>
                                        <td><span class="tag tag-jenis"><?= html_escape($row['nama_jenis']); ?></span></td>
                                        <td><?= date('d/m/Y', strtotime($row['tgl_mulai'])); ?> - <?= date('d/m/Y', strtotime($row['tgl_selesai'])); ?></td>
                                        <td><?= $row['total_hari']; ?> Hari</td>
                                        <td><?= html_escape($row['alasan']); ?></td>
                                        <td>
                                            <?php 
                                                $statusClass = 'badge-pending';
                                                if ($row['status_final'] == 'Disetujui') $statusClass = 'badge-success';
                                                elseif ($row['status_final'] == 'Ditolak') $statusClass = 'badge-danger';
                                            ?>
                                            <span class="badge <?= $statusClass; ?>"><?= $row['status_final']; ?></span>
                                        </td>
                                        <td>
                                            <?php if ($row['status_final'] == 'Disetujui'): ?>
                                                <a href="cetak_surat.php?id=<?= $row['id']; ?>" target="_blank" class="btn-link">📄 Surat</a>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</body>
</html>