<?php
$u = $_SESSION['user'] ?? null;
requireLogin();

$halamanAktif = basename($_SERVER['PHP_SELF']);
$folderAktif  = basename(dirname($_SERVER['PHP_SELF']));

function isActive(string $halaman = '', string $folder = ''): string {
    global $halamanAktif, $folderAktif;
    if ($folder && $halaman) return ($folderAktif === $folder && $halamanAktif === $halaman) ? 'active' : '';
    if ($folder)             return ($folderAktif === $folder && $halamanAktif === 'index.php') ? 'active' : '';
    if ($halaman)            return ($halamanAktif === $halaman) ? 'active' : '';
    return '';
}
?>

<!-- ── SIDEBAR DESKTOP ──────────────────────────────────── -->
<nav class="sidebar">

  <a class="sidebar-brand" href="<?= BASE_URL ?>">
    <div class="sidebar-logo-icon"><i class="bi bi-book-half"></i></div>
    Campus Notes
  </a>

  <div class="sidebar-user">
    <?= avatarHtml($u['nama'], $u['foto_profil'], 38, '13px') ?>
    <div class="sidebar-user-info">
      <div class="sidebar-user-name"><?= htmlspecialchars($u['nama']) ?></div>
      <div class="sidebar-user-badge"><?= badgeIcon($u['badge']) ?> <?= $u['badge'] ?> · <?= $u['poin_reputasi'] ?> poin</div>
    </div>
  </div>

  <div class="sidebar-section-label">Menu</div>
  <ul class="nav flex-column">
    <li class="nav-item">
      <a class="nav-link <?= isActive('index.php', 'dashboard') ?>" href="<?= BASE_URL ?>/dashboard/index.php">
        <i class="bi bi-grid-1x2"></i> Dashboard
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= isActive('index.php', 'notes') ?>" href="<?= BASE_URL ?>/notes/index.php">
        <i class="bi bi-journals"></i> Daftar Catatan
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= isActive('tambah.php') ?>" href="<?= BASE_URL ?>/notes/tambah.php">
        <i class="bi bi-cloud-upload"></i> Upload Catatan
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= isActive('leaderboard.php') ?>" href="<?= BASE_URL ?>/leaderboard.php">
        <i class="bi bi-trophy"></i> Leaderboard
      </a>
    </li>
  </ul>

  <div class="sidebar-section-label">Akun</div>
  <ul class="nav flex-column">
    <li class="nav-item">
      <a class="nav-link <?= isActive('profil.php') ?>" href="<?= BASE_URL ?>/profil.php">
        <i class="bi bi-person-circle"></i> Profil Saya
      </a>
    </li>
    <?php if ($u['role'] === 'admin'): ?>
    <li class="nav-item">
      <a class="nav-link <?= isActive('index.php', 'admin') ?>" href="<?= BASE_URL ?>/admin/index.php">
        <i class="bi bi-shield-check"></i> Admin Panel
      </a>
    </li>
    <?php endif; ?>
  </ul>

  <div class="sidebar-logout mt-auto">
    <a href="<?= BASE_URL ?>/auth/logout.php" class="nav-link text-danger">
      <i class="bi bi-box-arrow-right"></i> Logout
    </a>
  </div>

</nav>

<!-- ── OFFCANVAS (Mobile) ───────────────────────────── -->
<div class="offcanvas offcanvas-start" id="sidebarMobile" style="width:260px">
  <div class="offcanvas-header border-bottom">
    <div class="d-flex align-items-center gap-2">
      <div class="sidebar-logo-icon"><i class="bi bi-book-half"></i></div>
      <span class="fw-bold" style="color:#2563eb">Campus Notes</span>
    </div>
    <button class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-3">
    <div class="sidebar-user mb-3">
      <?= avatarHtml($u['nama'], $u['foto_profil'], 38, '13px') ?>
      <div class="sidebar-user-info">
        <div class="sidebar-user-name"><?= htmlspecialchars($u['nama']) ?></div>
        <div class="sidebar-user-badge"><?= badgeIcon($u['badge']) ?> <?= $u['badge'] ?></div>
      </div>
    </div>
    <ul class="nav flex-column gap-1">
      <li><a class="nav-link <?= isActive('index.php', 'dashboard') ?>" href="<?= BASE_URL ?>/dashboard/index.php"><i class="bi bi-grid-1x2"></i> Dashboard</a></li>
      <li><a class="nav-link <?= isActive('index.php', 'notes') ?>"    href="<?= BASE_URL ?>/notes/index.php"><i class="bi bi-journals"></i> Daftar Catatan</a></li>
      <li><a class="nav-link <?= isActive('tambah.php') ?>"            href="<?= BASE_URL ?>/notes/tambah.php"><i class="bi bi-cloud-upload"></i> Upload Catatan</a></li>
      <li><a class="nav-link <?= isActive('leaderboard.php') ?>"       href="<?= BASE_URL ?>/leaderboard.php"><i class="bi bi-trophy"></i> Leaderboard</a></li>
      <li><a class="nav-link <?= isActive('profil.php') ?>"            href="<?= BASE_URL ?>/profil.php"><i class="bi bi-person-circle"></i> Profil</a></li>
      <?php if ($u['role'] === 'admin'): ?>
      <li><a class="nav-link <?= isActive('index.php', 'admin') ?>"    href="<?= BASE_URL ?>/admin/index.php"><i class="bi bi-shield-check"></i> Admin Panel</a></li>
      <?php endif; ?>
      <li class="mt-2 pt-2 border-top">
        <a class="nav-link text-danger" href="<?= BASE_URL ?>/auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
      </li>
    </ul>
  </div>
</div>
