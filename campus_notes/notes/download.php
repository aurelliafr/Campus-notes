<?php
// Load config tanpa output HTML
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
requireLogin();

$id  = (int)($_GET['id'] ?? 0);
$uid = $_SESSION['user']['id_user'];
$db  = db();

$stmt = $db->prepare('SELECT * FROM catatan WHERE id_catatan=? AND status="aktif"');
$stmt->bind_param('i', $id);
$stmt->execute();
$note = $stmt->get_result()->fetch_assoc();
if (!$note) die('File tidak ditemukan.');

// Cek batas download harian (max 5/hari)
$user = $db->query("SELECT * FROM users WHERE id_user=$uid")->fetch_assoc();
$today = date('Y-m-d');
if ($user['terakhir_download'] !== $today) {
    $db->query("UPDATE users SET download_hari_ini=0, terakhir_download='$today' WHERE id_user=$uid");
    $user['download_hari_ini'] = 0;
}
if ($user['download_hari_ini'] >= 5 && $user['bebas_delay_hingga'] < $today) {
    echo '<div class="container mt-5"><div class="alert alert-warning">Batas download harian (5x) tercapai. Coba lagi besok atau upload catatan untuk mendapat bebas delay.</div></div>';
    require_once __DIR__.'/../includes/footer.php'; exit;
}

// Update statistik
$db->query("UPDATE catatan SET jumlah_download=jumlah_download+1 WHERE id_catatan=$id");
$db->query("UPDATE users SET download_hari_ini=download_hari_ini+1 WHERE id_user=$uid");
// +2 poin untuk uploader
if ($note['id_user'] != $uid) {
    $db->query("UPDATE users SET poin_reputasi=poin_reputasi+2 WHERE id_user={$note['id_user']}");
}

$path = UPLOAD_DIR . $note['nama_file_enkripsi'];
if (!file_exists($path)) die('File tidak ditemukan di server.');

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="'.rawurlencode($note['nama_file_asli']).'"');
header('Content-Length: '.filesize($path));
header('Cache-Control: no-cache');
readfile($path);
exit;