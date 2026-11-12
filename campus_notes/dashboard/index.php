<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$u  = $_SESSION['user'];
$db = db();

// Statistik upload & download milik user
$stmt = $db->prepare('SELECT COUNT(*) AS total_upload, COALESCE(SUM(jumlah_download),0) AS total_download FROM catatan WHERE id_user = ?');
$stmt->bind_param('i', $u['id_user']);
$stmt->execute();
$statistik = $stmt->get_result()->fetch_assoc();

// Poin terkini dari database
$poin = $db->query("SELECT poin_reputasi FROM users WHERE id_user = {$u['id_user']}")->fetch_assoc()['poin_reputasi'];

// Total upvote yang diterima user
$totalUpvote = $db->query("SELECT COALESCE(SUM(jumlah_upvote),0) AS total FROM catatan WHERE id_user = {$u['id_user']}")->fetch_assoc()['total'];

// 5 catatan terbaru milik user
$stmt2 = $db->prepare('SELECT * FROM catatan WHERE id_user = ? ORDER BY tanggal_upload DESC LIMIT 5');
$stmt2->bind_param('i', $u['id_user']);
$stmt2->execute();
$catatanTerbaru = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="app-wrapper">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="app-content">

    <!-- Header halaman -->
    <div class="page-header">
      <h5>👋 Halo, <?= htmlspecialchars($u['nama']) ?>!</h5>
      <p>Selamat datang kembali. Berikut ringkasan aktivitasmu.</p>
    </div>

    <!-- Stat cards: 4 angka penting -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-lg-3">
        <div class="stat-card stat-blue shadow-sm">
          <div class="stat-number"><?= $statistik['total_upload'] ?></div>
          <div class="stat-label"><i class="bi bi-file-earmark-arrow-up me-1"></i>Catatan Diupload</div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="stat-card stat-cyan shadow-sm">
          <div class="stat-number"><?= $statistik['total_download'] ?></div>
          <div class="stat-label"><i class="bi bi-download me-1"></i>Total Download</div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="stat-card stat-amber shadow-sm">
          <div class="stat-number"><?= $poin ?></div>
          <div class="stat-label"><i class="bi bi-star-fill me-1"></i>Poin Reputasi</div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="stat-card stat-green shadow-sm">
          <div class="stat-number"><?= $totalUpvote ?></div>
          <div class="stat-label"><i class="bi bi-hand-thumbs-up me-1"></i>Total Upvote</div>
        </div>
      </div>
    </div>

    <!-- Quick actions -->
    <div class="row g-3 mb-4">
      <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100" style="background:linear-gradient(135deg,#eff6ff,#e0f2fe)">
          <div class="d-flex align-items-center gap-3">
            <div style="width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#2563eb,#06b6d4);display:flex;align-items:center;justify-content:center;flex-shrink:0">
              <i class="bi bi-cloud-upload text-white fs-5"></i>
            </div>
            <div>
              <div class="fw-bold" style="color:#1e40af">Upload Catatan Baru</div>
              <div class="small text-muted">Berbagi & dapat +10 poin</div>
            </div>
            <a href="<?= BASE_URL ?>/notes/tambah.php" class="btn btn-brand btn-sm ms-auto">Upload</a>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100" style="background:linear-gradient(135deg,#f0fdf4,#dcfce7)">
          <div class="d-flex align-items-center gap-3">
            <div style="width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#10b981,#059669);display:flex;align-items:center;justify-content:center;flex-shrink:0">
              <i class="bi bi-journals text-white fs-5"></i>
            </div>
            <div>
              <div class="fw-bold" style="color:#065f46">Jelajahi Catatan</div>
              <div class="small text-muted">Temukan catatan dari mahasiswa lain</div>
            </div>
            <a href="<?= BASE_URL ?>/notes/index.php" class="btn btn-sm btn-success ms-auto">Lihat</a>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabel catatan terbaru -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
      <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center py-3 px-4">
        <span class="fw-bold">📋 Catatan Terbaru Milikku</span>
        <a href="<?= BASE_URL ?>/notes/index.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
      </div>

      <?php if ($catatanTerbaru): ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead style="background:#f8faff">
              <tr>
                <th class="ps-4 text-muted small fw-semibold">Judul</th>
                <th class="text-muted small fw-semibold">Mata Kuliah</th>
                <th class="text-muted small fw-semibold">Download</th>
                <th class="text-muted small fw-semibold">Upvote</th>
                <th class="text-muted small fw-semibold">Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($catatanTerbaru as $c): ?>
                <tr>
                  <td class="ps-4 fw-semibold"><?= htmlspecialchars($c['judul']) ?></td>
                  <td><span class="badge" style="background:#dbeafe;color:#1d4ed8"><?= htmlspecialchars($c['mata_kuliah']) ?></span></td>
                  <td><i class="bi bi-download text-muted me-1"></i><?= $c['jumlah_download'] ?></td>
                  <td><i class="bi bi-hand-thumbs-up text-muted me-1"></i><?= $c['jumlah_upvote'] ?></td>
                  <td>
                    <?php if ($c['status'] === 'aktif'): ?>
                      <span class="badge" style="background:#dcfce7;color:#15803d">● Aktif</span>
                    <?php else: ?>
                      <span class="badge" style="background:#fee2e2;color:#dc2626">● Ditangguhkan</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <a href="<?= BASE_URL ?>/notes/detail.php?id=<?= $c['id_catatan'] ?>" class="btn btn-sm btn-outline-primary me-1">Detail</a>
                    <a href="<?= BASE_URL ?>/notes/edit.php?id=<?= $c['id_catatan'] ?>"   class="btn btn-sm btn-outline-secondary">Edit</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <!-- Empty state: tampilan ramah kalau belum ada catatan -->
        <div class="text-center py-5">
          <div style="font-size:3rem">📂</div>
          <p class="text-muted mt-2 mb-3">Kamu belum punya catatan.</p>
          <a href="<?= BASE_URL ?>/notes/tambah.php" class="btn btn-brand">
            <i class="bi bi-upload me-1"></i>Upload Catatan Pertama
          </a>
        </div>
      <?php endif; ?>
    </div>

  </div><!-- /.app-content -->
</div><!-- /.app-wrapper -->

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
