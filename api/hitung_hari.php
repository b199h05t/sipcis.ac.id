<?php
header('Content-Type: application/json');

if (isset($_GET['tgl_mulai']) && isset($_GET['tgl_selesai'])) {
    $start = new DateTime($_GET['tgl_mulai']);
    $end = new DateTime($_GET['tgl_selesai']);

    if ($start > $end) {
        echo json_encode(['success' => false, 'message' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.']);
        exit;
    }

    $end->modify('+1 day'); // Memasukkan hari terakhir ke rentang
    $interval = new DateInterval('P1D');
    $daterange = new DatePeriod($start, $interval, $end);

    $workingDays = 0;
    foreach ($daterange as $date) {
        // 6 = Sabtu, 7 = Minggu
        if ($date->format('N') < 6) {
            $workingDays++;
        }
    }

    echo json_encode(['success' => true, 'total_hari' => $workingDays]);
} else {
    echo json_encode(['success' => false, 'message' => 'Parameter tanggal tidak lengkap.']);
}