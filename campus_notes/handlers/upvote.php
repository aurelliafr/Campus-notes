<?php
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$id  = (int)($_GET['id'] ?? 0);
$uid = $_SESSION['user']['id_user'];
$db  = db();

$cek = $db->prepare('SELECT id_vote FROM upvotes WHERE id_user=? AND id_catatan=?');
$cek->bind_param('ii', $uid, $id);
$cek->execute();

if ($cek->get_result()->num_rows === 0) {
    $ins = $db->prepare('INSERT INTO upvotes (id_user,id_catatan) VALUES (?,?)');
    $ins->bind_param('ii', $uid, $id);
    $ins->execute();
    // +jumlah upvote di catatan
    $db->query("UPDATE catatan SET jumlah_upvote=jumlah_upvote+1 WHERE id_catatan=$id");
    // +5 poin untuk uploader
    $note = $db->query("SELECT id_user FROM catatan WHERE id_catatan=$id")->fetch_assoc();
    if ($note && $note['id_user'] != $uid) {
        $db->query("UPDATE users SET poin_reputasi=poin_reputasi+5 WHERE id_user={$note['id_user']}");
    }
}
header('Location: '.BASE_URL.'/notes/detail.php?id='.$id);
exit;