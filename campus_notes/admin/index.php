<?php
$page_title = 'Admin Panel';
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

$db  = db();
$tab = $_GET['tab'] ?? 'dashboard';

// ── Aksi Admin ──────────────────────────────────────────────
// Hapus user
if (isset($_GET['hapus_user'])) {
    $id = (int)$_GET['hapus_user'];
    $db->query("DELETE FROM users WHERE id_user=$id AND role='mahasiswa'");
    header('Location: ?tab=users&msg=deleted'); exit;
}
// Tangguhkan / Aktifkan catatan
if (isset($_GET['toggle_catatan'])) {
    $id   = (int)$_GET['toggle_catatan'];
    $curr = $db->query("SELECT status FROM catatan WHERE id_catatan=$id")->fetch_assoc()['status'];
    $new  = $curr === 'aktif' ? 'ditangguhkan' : 'aktif';
    $db->query("UPDATE catatan SET status='$new' WHERE id_catatan=$id");
    header('Location: ?tab=catatan&msg=updated'); exit;
}
// Hapus catatan
if (isset($_GET['hapus_catatan'])) {
    $id   = (int)$_GET['hapus_catatan'];
    $file = $db->query("SELECT nama_file_enkripsi FROM catatan WHERE id_catatan=$id")->fetch_assoc();
    if ($file) {
        $path = UPLOAD_DIR . $file['nama_file_enkripsi'];
        if (file_exists($path)) unlink($path);
    }
    $db->query("DELETE FROM catatan WHERE id_catatan=$id");
    header('Location: ?tab=catatan&msg=deleted'); exit;
}

// ── Data per Tab ────────────────────────────────────────────
$statTotal = $db->query("
    SELECT
        (SELECT COUNT(*) FROM users WHERE role='mahasiswa') AS total_user,
        (SELECT COUNT(*) FROM catatan) AS total_catatan,
        (SELECT COALESCE(SUM(jumlah_download),0) FROM catatan) AS total_download,
        (SELECT COUNT(*) FROM reports) AS total_report
")->fetch_assoc();

$users   = $tab==='users'   ? $db->query("SELECT *,(SELECT COUNT(*) FROM catatan WHERE id_user=u.id_user) AS jml_catatan FROM users u WHERE role='mahasiswa' ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC) : [];
$catatan = $tab==='catatan' ? $db->query("SELECT c.*,u.nama AS nama_user FROM catatan c JOIN users u ON c.id_user=u.id_user ORDER BY c.tanggal_upload DESC")->fetch_all(MYSQLI_ASSOC) : [];
$reports = $tab==='reports' ? $db->query("SELECT r.*,u.nama AS nama_pelapor,c.judul FROM reports r JOIN users u ON r.id_user=u.id_user JOIN catatan c ON r.id_catatan=c.id_catatan ORDER BY r.dibuat_pada DESC")->fetch_all(MYSQLI_ASSOC) : [];
?>
<div class="app-wrapper">

<!-- ================= Desktop Sidebar ================= -->

<div class="sidebar admin-sidebar d-none d-lg-flex flex-column p-3 bg-dark text-white shadow"
     style="min-height:100vh;width:240px;min-width:240px">

    <div class="fw-bold mb-4 text-center fs-4">
        <i class="bi bi-shield-check text-warning me-2"></i>
        Admin Panel
    </div>

    <ul class="nav flex-column gap-2">

        <li>
            <a class="nav-link <?= $tab=='dashboard'?'active':'' ?>"
               href="?tab=dashboard">
                <i class="bi bi-speedometer2"></i>
                Dashboard
            </a>
        </li>

        <li>
            <a class="nav-link <?= $tab=='users'?'active':'' ?>"
               href="?tab=users">
                <i class="bi bi-people"></i>
                Kelola User
            </a>
        </li>

        <li>
            <a class="nav-link <?= $tab=='catatan'?'active':'' ?>"
               href="?tab=catatan">
                <i class="bi bi-journals"></i>
                Kelola Catatan
            </a>
        </li>

        <li>
            <a class="nav-link <?= $tab=='reports'?'active':'' ?>"
               href="?tab=reports">

                <i class="bi bi-flag"></i>

                Reports

                <?php if($statTotal['total_report']>0): ?>
                <span class="badge bg-danger ms-auto">
                    <?= $statTotal['total_report'] ?>
                </span>
                <?php endif; ?>

            </a>
        </li>

    </ul>

    <div class="mt-auto">

        <hr class="border-secondary">

        <a href="<?= BASE_URL ?>"
           class="btn btn-outline-light w-100">

            <i class="bi bi-house"></i>

            Ke Website

        </a>

    </div>

</div>



<!-- ================= Mobile Sidebar ================= -->

<div class="offcanvas offcanvas-start bg-dark text-white"
     tabindex="-1"
     id="adminSidebar">

<div class="offcanvas-header">

<h5 class="offcanvas-title">

<i class="bi bi-shield-check text-warning me-2"></i>

Admin Panel

</h5>

<button
type="button"
class="btn-close btn-close-white"
data-bs-dismiss="offcanvas">

</button>

</div>

<div class="offcanvas-body">

<ul class="nav flex-column gap-2">

<li>

<a class="nav-link <?= $tab=='dashboard'?'active':'' ?>"
href="?tab=dashboard">

<i class="bi bi-speedometer2"></i>

Dashboard

</a>

</li>

<li>

<a class="nav-link <?= $tab=='users'?'active':'' ?>"
href="?tab=users">

<i class="bi bi-people"></i>

Kelola User

</a>

</li>

<li>

<a class="nav-link <?= $tab=='catatan'?'active':'' ?>"
href="?tab=catatan">

<i class="bi bi-journals"></i>

Kelola Catatan

</a>

</li>

<li>

<a class="nav-link <?= $tab=='reports'?'active':'' ?>"
href="?tab=reports">

<i class="bi bi-flag"></i>

Reports

<?php if($statTotal['total_report']>0): ?>

<span class="badge bg-danger ms-auto">

<?= $statTotal['total_report'] ?>

</span>

<?php endif; ?>

</a>

</li>

</ul>

<hr>

<a href="<?= BASE_URL ?>"
class="btn btn-outline-light w-100">

<i class="bi bi-house"></i>

Ke Website

</a>

</div>

</div>

<!-- ================= Content ================= -->
  <div class="app-content">

      <!-- Tombol Menu Admin (khusus HP) -->
      <div class="d-lg-none mb-3">
          <button class="btn btn-primary"
                  type="button"
                  data-bs-toggle="offcanvas"
                  data-bs-target="#adminSidebar">
              <i class="bi bi-list me-1"></i> Menu Admin
          </button>
      </div>
      <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">Aksi berhasil dilakukan.</div>
      <?php endif; ?>

      <?php if ($tab==='dashboard'): ?>
      <!-- ── Dashboard Admin ── -->
      <h5 class="fw-bold mb-4"><i class="bi bi-speedometer2 me-2"></i>Dashboard Admin</h5>
      <div class="row g-3 mb-4">
        <div class="col-sm-3">
          <div class="card border-0 shadow-sm rounded-4 text-center py-3 border-start border-4 border-primary">
            <div class="fs-1 fw-bold text-primary"><?= $statTotal['total_user'] ?></div>
            <div class="text-muted small">Total User</div>
          </div>
        </div>
        <div class="col-sm-3">
          <div class="card border-0 shadow-sm rounded-4 text-center py-3 border-start border-4 border-success">
            <div class="fs-1 fw-bold text-success"><?= $statTotal['total_catatan'] ?></div>
            <div class="text-muted small">Total Catatan</div>
          </div>
        </div>
        <div class="col-sm-3">
          <div class="card border-0 shadow-sm rounded-4 text-center py-3 border-start border-4 border-info">
            <div class="fs-1 fw-bold text-info"><?= $statTotal['total_download'] ?></div>
            <div class="text-muted small">Total Download</div>
          </div>
        </div>
        <div class="col-sm-3">
          <div class="card border-0 shadow-sm rounded-4 text-center py-3 border-start border-4 border-danger">
            <div class="fs-1 fw-bold text-danger"><?= $statTotal['total_report'] ?></div>
            <div class="text-muted small">Total Report</div>
          </div>
        </div>
      </div>

      <?php elseif ($tab==='users'): ?>
      <!-- ── Kelola User ── -->
      <h5 class="fw-bold mb-4"><i class="bi bi-people me-2"></i>Kelola User</h5>
      <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-dark">
            <tr><th>Nama</th><th>NIM</th><th>Email</th><th>Badge</th><th>Poin</th><th>Catatan</th><th>Daftar</th><th></th></tr>
          </thead>
          <tbody>
          <?php foreach($users as $u): ?>
            <tr>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <?= avatarHtml($u['nama'], $u['foto_profil'], 32, '12px') ?>
                  <?= htmlspecialchars($u['nama']) ?>
                </div>
              </td>
              <td class="small text-muted"><?= htmlspecialchars($u['nim']) ?></td>
              <td class="small"><?= htmlspecialchars($u['email']) ?></td>
              <td><span class="badge bg-warning text-dark"><?= badgeIcon($u['badge']).' '.$u['badge'] ?></span></td>
              <td><?= $u['poin_reputasi'] ?></td>
              <td><?= $u['jml_catatan'] ?></td>
              <td class="small text-muted"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
              <td>
                <a href="?tab=users&hapus_user=<?= $u['id_user'] ?>"
                   class="btn btn-sm btn-outline-danger"
                   onclick="return confirm('Hapus user <?= htmlspecialchars($u['nama']) ?>?')">
                  <i class="bi bi-trash"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <?php elseif ($tab==='catatan'): ?>
      <!-- ── Kelola Catatan ── -->
      <h5 class="fw-bold mb-4"><i class="bi bi-journals me-2"></i>Kelola Catatan</h5>
      <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-dark">
            <tr><th>Judul</th><th>Uploader</th><th>Matkul</th><th>Download</th><th>Report</th><th>Status</th><th></th></tr>
          </thead>
          <tbody>
          <?php foreach($catatan as $c): ?>
            <tr>
              <td>
                <a href="<?= BASE_URL ?>/notes/detail.php?id=<?= $c['id_catatan'] ?>" class="text-decoration-none fw-semibold">
                  <?= htmlspecialchars(mb_strimwidth($c['judul'],0,40,'...')) ?>
                </a>
              </td>
              <td class="small"><?= htmlspecialchars($c['nama_user']) ?></td>
              <td><span class="badge bg-secondary small"><?= htmlspecialchars($c['mata_kuliah']) ?></span></td>
              <td><?= $c['jumlah_download'] ?></td>
              <td>
                <span class="badge <?= $c['jumlah_report']>=3?'bg-danger':'bg-secondary' ?>">
                  <?= $c['jumlah_report'] ?>
                </span>
              </td>
              <td>
                <?php if($c['status']==='aktif'): ?>
                  <span class="badge bg-success">Aktif</span>
                <?php else: ?>
                  <span class="badge bg-danger">Ditangguhkan</span>
                <?php endif; ?>
              </td>
              <td class="d-flex gap-1">
                <a href="?tab=catatan&toggle_catatan=<?= $c['id_catatan'] ?>"
                   class="btn btn-sm <?= $c['status']==='aktif'?'btn-outline-warning':'btn-outline-success' ?>"
                   onclick="return confirm('<?= $c['status']==='aktif'?'Tangguhkan':'Aktifkan' ?> catatan ini?')">
                  <i class="bi bi-<?= $c['status']==='aktif'?'pause':'play' ?>"></i>
                </a>
                <a href="?tab=catatan&hapus_catatan=<?= $c['id_catatan'] ?>"
                   class="btn btn-sm btn-outline-danger"
                   onclick="return confirm('Hapus catatan ini permanen?')">
                  <i class="bi bi-trash"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <?php elseif ($tab==='reports'): ?>
      <!-- ── Reports ── -->
      <h5 class="fw-bold mb-4"><i class="bi bi-flag me-2"></i>Laporan Masuk</h5>
      <?php if ($reports): ?>
      <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-dark">
            <tr><th>Pelapor</th><th>Catatan</th><th>Alasan</th><th>Tanggal</th><th></th></tr>
          </thead>
          <tbody>
          <?php foreach($reports as $r): ?>
            <tr>
              <td class="small fw-semibold"><?= htmlspecialchars($r['nama_pelapor']) ?></td>
              <td>
                <a href="<?= BASE_URL ?>/notes/detail.php?id=<?= $r['id_catatan'] ?>" class="text-decoration-none small">
                  <?= htmlspecialchars(mb_strimwidth($r['judul'],0,40,'...')) ?>
                </a>
              </td>
              <td class="small text-muted"><?= htmlspecialchars($r['alasan'] ?? '-') ?></td>
              <td class="small text-muted"><?= date('d M Y H:i', strtotime($r['dibuat_pada'])) ?></td>
              <td>
                <a href="?tab=catatan&toggle_catatan=<?= $r['id_catatan'] ?>"
                   class="btn btn-sm btn-outline-warning">
                  <i class="bi bi-eye me-1"></i>Tinjau
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
        <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Tidak ada laporan masuk.</div>
      <?php endif; ?>

      <?php endif; ?>
</div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>