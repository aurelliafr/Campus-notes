<?php
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$id  = (int)($_GET['id'] ?? 0);
$uid = $_SESSION['user']['id_user'];
$db  = db();

$stmt = $db->prepare('SELECT * FROM catatan WHERE id_catatan=? AND id_user=?');
$stmt->bind_param('ii', $id, $uid);
$stmt->execute();
$note = $stmt->get_result()->fetch_assoc();

if (!$note) {
    header('Location: ' . BASE_URL . '/notes/index.php');
    exit;
}

// Hapus file fisik
$path = UPLOAD_DIR . $note['nama_file_enkripsi'];
if (file_exists($path)) unlink($path);

// Hapus dari database (cascade hapus komentar, upvote, report)
$del = $db->prepare('DELETE FROM catatan WHERE id_catatan=? AND id_user=?');
$del->bind_param('ii', $id, $uid);
$del->execute();

// Kurangi poin (-10)
$poinBaru = max(0, $_SESSION['user']['poin_reputasi'] - 10);
$db->query("UPDATE users SET poin_reputasi=$poinBaru WHERE id_user=$uid");
$_SESSION['user']['poin_reputasi'] = $poinBaru;
$_SESSION['user']['badge'] = getBadge($poinBaru);

header('Location: ' . BASE_URL . '/dashboard/index.php?deleted=1');
exit;