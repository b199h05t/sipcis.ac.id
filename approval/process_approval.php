<?php
session_start();
require_once '../config/database.php';

// Memastikan hanya Approver (Role ID: 2, 3, 4) yang bisa mengakses
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] == 1) {
    header("Location: ../login.php");
    exit;
}

// Fungsi Helper untuk Menampilkan Kartu Notifikasi Response CSS
function show_approval_response($type, $title, $message, $qr_url = null, $redirect_url = 'index.php') {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($title) ?> - SIP-CIS</title>
        <link rel="stylesheet" href="../assets/css/style.css">
        <style>
            body {
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                background-color: #f1f5f9;
                margin: 0;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }
            .response-card {
                background: #ffffff;
                padding: 35px 30px;
                border-radius: 16px;
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
                text-align: center;
                max-width: 440px;
                width: 90%;
            }
            .response-icon { font-size: 45px; margin-bottom: 10px; }
            .response-title { font-size: 20px; font-weight: 700; color: #0f172a; margin-bottom: 10px; }
            .response-message { font-size: 14px; color: #64748b; margin-bottom: 20px; line-height: 1.5; }
            .qr-container {
                background: #f8fafc;
                padding: 15px;
                border-radius: 12px;
                border: 1px dashed #cbd5e1;
                margin-bottom: 20px;
            }
            .qr-container img { width: 140px; height: 140px; }
            .qr-caption { font-size: 12px; color: #0284c7; font-weight: 600; margin-top: 8px; }
            .btn-redirect {
                display: block;
                width: 100%;
                padding: 12px 0;
                background-color: #2563eb;
                color: #ffffff;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                font-size: 14px;
                box-sizing: border-box;
            }
            .btn-redirect:hover { background-color: #1d4ed8; }
        </style>
    </head>
    <body>
        <div class="response-card">
            <div class="response-icon"><?= $type === 'success' ? '✅' : '⚠️' ?></div>
            <div class="response-title"><?= htmlspecialchars($title) ?></div>
            <div class="response-message"><?= htmlspecialchars($message) ?></div>

            <?php if ($qr_url): ?>
                <div class="qr-container">
                    <img src="<?= htmlspecialchars($qr_url) ?>" alt="Tanda Tangan Digital QR Code">
                    <div class="qr-caption">🔏 Tanda Tangan Digital Berhasil Diterbitkan</div>
                </div>
            <?php endif; ?>

            <a href="<?= $redirect_url ?>" class="btn-redirect">Kembali ke Antrean Approval</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['role_id'])) {
    $pengajuan_id = (int)$_POST['pengajuan_id'];
    $action       = $_POST['action'] === 'setuju' ? 'Disetujui' : 'Ditolak';
    $catatan      = trim($_POST['catatan']);
    $approver_id  = $_SESSION['user_id'];
    $role_id      = $_SESSION['role_id'];

    $pdo->beginTransaction();
    try {
        // 1. Ambil data pengajuan & user
        $stmt = $pdo->prepare("SELECT p.*, u.nama, u.nip FROM pengajuan p JOIN users u ON p.user_id = u.id WHERE p.id = ? FOR UPDATE");
        $stmt->execute([$pengajuan_id]);
        $pengajuan = $stmt->fetch();

        if (!$pengajuan) {
            throw new Exception("Data pengajuan tidak ditemukan.");
        }

        // 2. Buat Digital Signature Hash & URL QR Code
        $timestamp      = date('Y-m-d H:i:s');
        $signature_raw  = "SIP-CIS|ID:{$pengajuan_id}|APPROVER:{$approver_id}|ROLE:{$role_id}|STATUS:{$action}|TIME:{$timestamp}";
        $signature_hash = hash('sha256', $signature_raw);

        // Isi konten QR Code yang dapat dipindai (scanned)
        $qr_content = "VERIFIKASI TTD DIGITAL STMIK BINA PATRIA\n"
                    . "Status: {$action}\n"
                    . "Oleh: {$_SESSION['nama']} ({$_SESSION['role_name']})\n"
                    . "Pemohon: {$pengajuan['nama']} ({$pengajuan['nip']})\n"
                    . "Waktu: {$timestamp}\n"
                    . "Hash: " . substr($signature_hash, 0, 16) . "...";

        // Generate QR Code via Google Charts API
        $qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qr_content);

        // 3. Update status pengajuan berjenjang
        if ($role_id == 2) { // KaBag
            $status_final = ($action === 'Ditolak') ? 'Ditolak' : 'Pending';
            $update = $pdo->prepare("UPDATE pengajuan SET status_kabag = ?, status_final = ? WHERE id = ?");
            $update->execute([$action, $status_final, $pengajuan_id]);
            $pesan_sukses = "Pengajuan berhasil diproses oleh KaBag Kepegawaian.";

        } elseif ($role_id == 3) { // WK2
            $status_final = ($action === 'Ditolak') ? 'Ditolak' : 'Pending';
            $update = $pdo->prepare("UPDATE pengajuan SET status_wk2 = ?, status_final = ? WHERE id = ?");
            $update->execute([$action, $status_final, $pengajuan_id]);
            $pesan_sukses = "Pengajuan berhasil diproses oleh Wakil Ketua 2.";

        } elseif ($role_id == 4) { // Ketua
            $status_final = $action;
            $update = $pdo->prepare("UPDATE pengajuan SET status_ketua = ?, status_final = ? WHERE id = ?");
            $update->execute([$action, $status_final, $pengajuan_id]);

            if ($action === 'Disetujui' && $pengajuan['jenis_id'] == 1) {
                $deduct = $pdo->prepare("UPDATE users SET sisa_cuti = sisa_cuti - ? WHERE id = ?");
                $deduct->execute([$pengajuan['total_hari'], $pengajuan['user_id']]);
            }
            $pesan_sukses = "Pengajuan disetujui secara final oleh Ketua STMIK Bina Patria.";
        }

        // 4. Catat ke log_approval beserta QR Code & Signature Hash
        $log = $pdo->prepare("INSERT INTO log_approval (pengajuan_id, approver_id, status, catatan, qr_code, signature_hash) VALUES (?, ?, ?, ?, ?, ?)");
        $log->execute([$pengajuan_id, $approver_id, $action, $catatan, $qr_code_url, $signature_hash]);

        $pdo->commit();

        // Tampilkan konfirmasi beserta QR Code yang berhasil dibuat
        show_approval_response('success', 'Approval Berhasil', $pesan_sukses, $qr_code_url, 'index.php');

    } catch (Exception $e) {
        $pdo->rollBack();
        show_approval_response('error', 'Gagal Memproses Approval', $e->getMessage(), null, 'index.php');
    }
} else {
    header("Location: index.php");
    exit;
}