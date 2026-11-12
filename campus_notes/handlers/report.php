<?php
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$id  = (int)($_GET['id'] ?? 0);
$uid = $_SESSION['user']['id_user'];
$db  = db();

$cek = $db->prepare('SELECT id_report FROM reports WHERE id_user=? AND id_catatan=?');
$cek->bind_param('ii', $uid, $id);
$cek->execute();

if ($cek->get_result()->num_rows === 0) {
    $alasan = trim($_GET['alasan'] ?? 'Tidak sesuai');
    $ins = $db->prepare('INSERT INTO reports (id_user,id_catatan,alasan) VALUES (?,?,?)');
    $ins->bind_param('iis', $uid, $id, $alasan);
    $ins->execute();
    // Update jumlah report
    $db->query("UPDATE catatan SET jumlah_report=jumlah_report+1 WHERE id_catatan=$id");
    // Auto tangguhkan jika >= 3 report
    $db->query("UPDATE catatan SET status='ditangguhkan' WHERE id_catatan=$id AND jumlah_report>=3");
}
header('Location: '.BASE_URL.'/notes/detail.php?id='.$id);
exit;