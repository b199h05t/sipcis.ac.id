<?php
session_start();
require_once '../config/database.php';

// Memastikan hanya Approver (Role ID: 2, 3, 4) yang bisa mengakses
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] == 1) {
    header("Location: ../login.php");
    exit;
}

$role_id   = $_SESSION['role_id'];
$role_name = $_SESSION['role_name'];

// Filter kueri berdasarkan hirarki approval
if ($role_id == 2) {
    // KaBag Kepegawaian melihat pengajuan dengan status_kabag Pending
    $sql = "SELECT p.*, u.nama, u.nip, j.nama_jenis FROM pengajuan p 
            JOIN users u ON p.user_id = u.id 
            JOIN jenis_pengajuan j ON p.jenis_id = j.id 
            WHERE p.status_kabag = 'Pending' ORDER BY p.created_at ASC";
} elseif ($role_id == 3) {
    // Wakil Ketua 2 melihat pengajuan yang disetujui KaBag tetapi WK2 masih Pending
    $sql = "SELECT p.*, u.nama, u.nip, j.nama_jenis FROM pengajuan p 
            JOIN users u ON p.user_id = u.id 
            JOIN jenis_pengajuan j ON p.jenis_id = j.id 
            WHERE p.status_kabag = 'Disetujui' AND p.status_wk2 = 'Pending' ORDER BY p.created_at ASC";
} elseif ($role_id == 4) {
    // Ketua melihat pengajuan yang disetujui WK2 tetapi Ketua masih Pending
    $sql = "SELECT p.*, u.nama, u.nip, j.nama_jenis FROM pengajuan p 
            JOIN users u ON p.user_id = u.id 
            JOIN jenis_pengajuan j ON p.jenis_id = j.id 
            WHERE p.status_wk2 = 'Disetujui' AND p.status_ketua = 'Pending' ORDER BY p.created_at ASC";
}

$stmt = $pdo->query($sql);
$pengajuan_list = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Approval - SIP-CIS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
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
                <li><a href="../dashboard.php">📌 Dashboard</a></li>
                <li class="active"><a href="index.php">⏳ Verifikasi Approval</a></li>
                <li><a href="../profil.php">⚙️ Pengaturan Profil</a></li>
                <li><a href="../logout.php" class="logout-link">🚪 Keluar (Logout)</a></li>
            </ul>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <div class="content-section">
                <div class="section-header">
                    <h3>Verifikasi Pengajuan (Akses: <span style="color: #2563eb;"><?= html_escape($role_name); ?></span>)</h3>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Pemohon</th>
                                <th>Jenis</th>
                                <th>Mulai - Selesai</th>
                                <th>Total</th>
                                <th>Alasan</th>
                                <th>Bukti</th>
                                <th>Aksi Approval</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pengajuan_list)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 30px; color: #64748b;">
                                        🎉 Tidak ada antrean pengajuan yang perlu diverifikasi saat ini.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pengajuan_list as $row): ?>
                                    <tr>
                                        <td>
                                            <strong><?= html_escape($row['nama']); ?></strong><br>
                                            <small class="text-muted"><?= html_escape($row['nip']); ?></small>
                                        </td>
                                        <td><span class="badge badge-pending"><?= html_escape($row['nama_jenis']); ?></span></td>
                                        <td><?= date('d/m/Y', strtotime($row['tgl_mulai'])); ?> s/d <?= date('d/m/Y', strtotime($row['tgl_selesai'])); ?></td>
                                        <td><strong><?= $row['total_hari']; ?> Hari</strong></td>
                                        <td><?= html_escape($row['alasan']); ?></td>
                                        <td>
                                            <?php if ($row['file_bukti']): ?>
                                                <a href="../uploads/<?= html_escape($row['file_bukti']); ?>" target="_blank" class="btn-link">📄 Lihat File</a>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form action="process_approval.php" method="POST" class="approval-form">
                                                <input type="hidden" name="pengajuan_id" value="<?= $row['id']; ?>">
                                                <input type="text" name="catatan" class="input-catatan" placeholder="Catatan opsional...">
                                                <div class="btn-group">
                                                    <button type="submit" name="action" value="setuju" class="btn-action btn-success">Setujui</button>
                                                    <button type="submit" name="action" value="tolak" class="btn-action btn-danger">Tolak</button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>