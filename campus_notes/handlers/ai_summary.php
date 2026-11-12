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
$cek = $db->prepare('SELECT isi_ringkasan, poin_penting FROM ai_ringkasan WHERE id_catatan=?');
$cek->bind_param('i', $id);
$cek->execute();
$cached = $cek->get_result()->fetch_assoc();

if ($cached) {
    echo json_encode([
        'ok'           => true,
        'cached'       => true,
        'ringkasan'    => $cached['isi_ringkasan'],
        'poin_penting' => json_decode($cached['poin_penting'], true) ?: [],
    ]);
    exit;
}

$path  = UPLOAD_DIR . $note['nama_file_enkripsi'];
$hasil = aiBuatRingkasan($path, $ekstensi);

if (!$hasil['ok']) {
    echo json_encode(['ok' => false, 'error' => $hasil['error']]);
    exit;
}

$ringkasan   = $hasil['data']['ringkasan'];
$poinPenting = $hasil['data']['poin_penting'];

$ins = $db->prepare('INSERT INTO ai_ringkasan (id_catatan, isi_ringkasan, poin_penting) VALUES (?,?,?)');
$poinJson = json_encode($poinPenting, JSON_UNESCAPED_UNICODE);
$ins->bind_param('iss', $id, $ringkasan, $poinJson);
$ins->execute();

echo json_encode([
    'ok'           => true,
    'cached'       => false,
    'ringkasan'    => $ringkasan,
    'poin_penting' => $poinPenting,
]);
