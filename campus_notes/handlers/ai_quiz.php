<?php
require_once __DIR__ . '/../includes/bootstrap_json.php';
require_once __DIR__ . '/../includes/ai_helper.php';
requireLogin();

header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$db = db();

$stmt = $db->prepare('SELECT * FROM catatan WHERE id_catatan=? AND status="aktif"');
$stmt->bind_param('i', $id);
$stmt->execute();
$note = $stmt->get_result()->fetch_assoc();

if (!$note) {
    echo json_encode(['ok' => false, 'error' => 'Catatan tidak ditemukan.']);
    exit;
}

$ekstensi = strtolower($note['ekstensi']);

if (!aiTipeDidukung($ekstensi)) {
    echo json_encode(['ok' => false, 'error' => 'Tipe file ini tidak didukung untuk analisis AI. AI hanya bisa menganalisis PDF, gambar, dan dokumen teks.']);
    exit;
}

// Cek cache
$cek = $db->prepare('SELECT data_soal FROM ai_kuis WHERE id_catatan=?');
$cek->bind_param('i', $id);
$cek->execute();
$cached = $cek->get_result()->fetch_assoc();

if ($cached) {
    echo json_encode([
        'ok'     => true,
        'cached' => true,
        'soal'   => json_decode($cached['data_soal'], true) ?: [],
    ]);
    exit;
}

$path  = UPLOAD_DIR . $note['nama_file_enkripsi'];
$hasil = aiBuatKuis($path, 5, $ekstensi);

if (!$hasil['ok']) {
    echo json_encode(['ok' => false, 'error' => $hasil['error']]);
    exit;
}

$soal = $hasil['data']['soal'];

$ins = $db->prepare('INSERT INTO ai_kuis (id_catatan, data_soal, jumlah_soal) VALUES (?,?,?)');
$soalJson = json_encode($soal, JSON_UNESCAPED_UNICODE);
$jumlah   = count($soal);
$ins->bind_param('isi', $id, $soalJson, $jumlah);
$ins->execute();

echo json_encode([
    'ok'     => true,
    'cached' => false,
    'soal'   => $soal,
]);
