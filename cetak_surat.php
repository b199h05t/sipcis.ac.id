<?php
session_start();
require_once 'config/database.php';

$id = $_GET['id'] ?? 0;

// Ambil Data Pengajuan
$stmt = $pdo->prepare("
    SELECT p.*, u.nama, u.nip, j.nama_jenis, jb.nama_jabatan 
    FROM pengajuan p 
    JOIN users u ON p.user_id = u.id 
    JOIN jenis_pengajuan j ON p.jenis_id = j.id
    JOIN jabatan jb ON u.id_jabatan = jb.id
    WHERE p.id = ? AND p.status_final = 'Disetujui'
");
$stmt->execute([$id]);
$data = $stmt->fetch();

if (!$data) {
    die("Surat tidak ditemukan atau pengajuan belum disetujui secara final.");
}

// Ambil Log Tanda Tangan Digital QR Code dari masing-masing Approver
$stmt_log = $pdo->prepare("
    SELECT l.*, u.role_id 
    FROM log_approval l 
    JOIN users u ON l.approver_id = u.id 
    WHERE l.pengajuan_id = ? AND l.status = 'Disetujui'
");
$stmt_log->execute([$id]);
$logs = $stmt_log->fetchAll();

$ttd = ['kabag' => null, 'wk2' => null, 'ketua' => null];
foreach ($logs as $log) {
    if ($log['role_id'] == 2) $ttd['kabag'] = $log['qr_code'];
    if ($log['role_id'] == 3) $ttd['wk2'] = $log['qr_code'];
    if ($log['role_id'] == 4) $ttd['ketua'] = $log['qr_code'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Izin Cuti - <?= html_escape($data['nip']) ?></title>
    <style>
        body { font-family: 'Times New Roman', serif; margin: 40px; color: #000; }
        .header { text-align: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 20px; }
        .header h2 { margin: 0; font-size: 20px; }
        .header p { margin: 4px 0 0; font-size: 13px; }
        .content { margin-top: 20px; line-height: 1.6; }
        .table-data { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .table-data td { padding: 6px; vertical-align: top; }
        .ttd-table { width: 100%; margin-top: 40px; text-align: center; }
        .ttd-table td { width: 33%; vertical-align: bottom; padding: 10px; }
        .qr-img { width: 90px; height: 90px; margin: 8px 0; }
        .no-print { margin-bottom: 20px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <button onclick="window.print()" class="no-print" style="padding: 10px 20px; background: #2563eb; color: #fff; border: none; border-radius: 5px; cursor: pointer;">🖨️ Cetak / Simpan PDF</button>

    <div class="header">
        <h2>STMIK BINA PATRIA MAGELANG</h2>
        <p>Jl. Raden Patah No.11, Rejowinangun Utara, Magelang Tengah | Email: info@stmikbinapatria.ac.id</p>
    </div>

    <div class="content">
        <h3 style="text-align: center; text-decoration: underline; margin-bottom: 20px;">SURAT IZIN / CUTI KARYAWAN</h3>
        <p>Diberikan izin/cuti kepada karyawan berikut:</p>
        <table class="table-data">
            <tr><td width="180"><strong>Nama Lengkap</strong></td><td>: <?= html_escape($data['nama']) ?></td></tr>
            <tr><td><strong>NIP</strong></td><td>: <?= html_escape($data['nip']) ?></td></tr>
            <tr><td><strong>Jabatan</strong></td><td>: <?= html_escape($data['nama_jabatan']) ?></td></tr>
            <tr><td><strong>Jenis Permohonan</strong></td><td>: <?= html_escape($data['nama_jenis']) ?></td></tr>
            <tr><td><strong>Tanggal Pelaksanaan</strong></td><td>: <?= date('d-m-Y', strtotime($data['tgl_mulai'])) ?> s/d <?= date('d-m-Y', strtotime($data['tgl_selesai'])) ?> (<?= $data['total_hari'] ?> Hari)</td></tr>
            <tr><td><strong>Alasan Permohonan</strong></td><td>: <?= html_escape($data['alasan']) ?></td></tr>
        </table>
        <p>Demikian surat izin/cuti ini diterbitkan untuk dipergunakan sebagaimana mestinya.</p>
    </div>

    <!-- Tabel Tanda Tangan Digital QR Code -->
    <table class="ttd-table">
        <tr>
            <td>
                KaBag Kepegawaian<br>
                <?php if ($ttd['kabag']): ?>
                    <img src="<?= $ttd['kabag'] ?>" class="qr-img"><br>
                    <small>✓ Valid TTD Digital</small>
                <?php else: ?>
                    <br><br><br>( - )
                <?php endif; ?>
            </td>
            <td>
                Wakil Ketua 2<br>
                <?php if ($ttd['wk2']): ?>
                    <img src="<?= $ttd['wk2'] ?>" class="qr-img"><br>
                    <small>✓ Valid TTD Digital</small>
                <?php else: ?>
                    <br><br><br>( - )
                <?php endif; ?>
            </td>
            <td>
                Ketua STMIK<br>
                <?php if ($ttd['ketua']): ?>
                    <img src="<?= $ttd['ketua'] ?>" class="qr-img"><br>
                    <small>✓ Valid TTD Digital</small>
                <?php else: ?>
                    <br><br><br>( - )
                <?php endif; ?>
            </td>
        </tr>
    </table>
</body>
</html>