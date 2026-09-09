<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../login.php");
    exit;
}

// Ambil sisa cuti terbaru
$stmt = $pdo->prepare("SELECT sisa_cuti FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$sisa_cuti = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="stylesheet" href="../assets/css/style.css">
    <meta charset="UTF-8">
    <title>Form Pengajuan - SIP-CIS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <h2>Form Pengajuan Cuti / Izin / Sakit</h2>
        <div class="badge-info">Sisa Cuti Tahunan Anda: <strong><?= $sisa_cuti ?> Hari</strong></div>

        <form action="process_pengajuan.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="jenis_id">Jenis Pengajuan</label>
                <select name="jenis_id" id="jenis_id" required>
                    <option value="">-- Pilih Jenis --</option>
                    <option value="1">Cuti Tahunan</option>
                    <option value="2">Izin Alasan Important</option>
                    <option value="3">Sakit (Wajib Surat Dokter)</option>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="tgl_mulai">Tanggal Mulai</label>
                    <input type="date" name="tgl_mulai" id="tgl_mulai" required>
                </div>
                <div class="form-group">
                    <label for="tgl_selesai">Tanggal Selesai</label>
                    <input type="date" name="tgl_selesai" id="tgl_selesai" required>
                </div>
            </div>

            <div class="form-group">
                <label for="total_hari">Total Hari Kerja (Tanpa Akhir Pekan)</label>
                <input type="number" name="total_hari" id="total_hari" readonly placeholder="Otomatis terhitung">
            </div>

            <div class="form-group" id="file_bukti_group" style="display: none;">
                <label for="file_bukti">Dokumen Pendukung (PDF/JPG/PNG - Max 2MB)</label>
                <input type="file" name="file_bukti" id="file_bukti" accept=".pdf,.jpg,.jpeg,.png">
            </div>

            <div class="form-group">
                <label for="alasan">Alasan / Keterangan Lengkap</label>
                <textarea name="alasan" id="alasan" rows="4" required></textarea>
            </div>

            <button type="submit" class="btn-primary">Kirim Permohonan</button>
        </form>
    </div>
    <script src="../assets/js/script.js"></script>
</body>
</html>