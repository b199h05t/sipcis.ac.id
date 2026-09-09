<?php
session_start();
require_once '../config/database.php';

// Pastikan pengguna sudah login dan merupakan Karyawan
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id     = $_SESSION['user_id'];
    $jenis_id    = (int)$_POST['jenis_id'];
    $tgl_mulai   = $_POST['tgl_mulai'];
    $tgl_selesai = $_POST['tgl_selesai'];
    $total_hari  = (int)$_POST['total_hari'];
    $alasan      = trim($_POST['alasan']);
    $file_bukti  = null;

    // 1. Validasi Sisa Cuti jika memilih jenis Cuti Tahunan (ID 1)
    if ($jenis_id === 1) {
        $stmt_check = $pdo->prepare("SELECT sisa_cuti FROM users WHERE id = ?");
        $stmt_check->execute([$user_id]);
        $sisa_cuti = $stmt_check->fetchColumn();

        if ($total_hari > $sisa_cuti) {
            echo "<script>alert('Pengajuan gagal! Jumlah hari cuti ($total_hari hari) melebihi sisa cuti Anda ($sisa_cuti hari).'); window.history.back();</script>";
            exit;
        }
    }

    // 2. Proses Upload File Bukti (Khusus Sakit/Izin jika ada)
    if (isset($_FILES['file_bukti']) && $_FILES['file_bukti']['error'] === UPLOAD_ERR_OK) {
        $file_tmp    = $_FILES['file_bukti']['tmp_name'];
        $file_name   = $_FILES['file_bukti']['name'];
        $file_ext    = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];

        if (in_array($file_ext, $allowed_ext)) {
            // Rename file agar unik: TIMESTAMP_USERID_FILENAME
            $new_file_name = time() . '_' . $user_id . '.' . $file_ext;
            $upload_dir    = '../uploads/';

            // Buat folder uploads jika belum ada
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                $file_bukti = $new_file_name;
            }
        } else {
            echo "<script>alert('Format file tidak didukung! Gunakan PDF, JPG, JPEG, atau PNG.'); window.history.back();</script>";
            exit;
        }
    }

    // 3. Simpan data pengajuan ke database
    try {
        $stmt = $pdo->prepare("INSERT INTO pengajuan (user_id, jenis_id, tgl_mulai, tgl_selesai, total_hari, alasan, file_bukti) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $jenis_id, $tgl_mulai, $tgl_selesai, $total_hari, $alasan, $file_bukti]);

        echo "<script>alert('Pengajuan berhasil dikirim! Menunggu konfirmasi KaBag Kepegawaian.'); window.location.href='form_pengajuan.php';</script>";
    } catch (PDOException $e) {
        die("Gagal menyimpan pengajuan: " . $e->getMessage());
    }
} else {
    header("Location: form_pengajuan.php");
    exit;
}