<?php
$page_title = 'Preview';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$id   = (int)($_GET['id'] ?? 0);
$db   = db();
$stmt = $db->prepare('SELECT * FROM catatan WHERE id_catatan=? AND status="aktif"');
$stmt->bind_param('i', $id);
$stmt->execute();
$note = $stmt->get_result()->fetch_assoc();
if (!$note) die('Catatan tidak ditemukan.');

$fileUrl = BASE_URL.'/assets/uploads/notes/'.$note['nama_file_enkripsi'];
?>
<div class="container py-4">
  <h5 class="fw-bold mb-3"><?= htmlspecialchars($note['judul']) ?></h5>
  <?php if (in_array($note['ekstensi'], ['jpg','jpeg','png'])): ?>
    <img src="<?= $fileUrl ?>" class="img-fluid rounded shadow" alt="Preview">
  <?php else: ?>
    <iframe src="<?= $fileUrl ?>" width="100%" height="700" class="border rounded shadow"></iframe>
  <?php endif; ?>
  <div class="mt-3">
    <a href="<?= BASE_URL ?>/notes/detail.php?id=<?= $id ?>" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>