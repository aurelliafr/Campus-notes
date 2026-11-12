<?php
// Mulai session kalau belum jalan
if (session_status() === PHP_SESSION_NONE) session_start();

// Muat config database
$configPath = __DIR__ . '/../config/database.php';
if (file_exists($configPath) && !defined('DB_HOST')) {
    require_once $configPath;
}

$page_title = $page_title ?? 'Campus Notes Sharing';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($page_title) ?> – Campus Notes Sharing</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<?php if (isLoggedIn()): ?>
<!-- Topbar hanya muncul di mobile (d-lg-none), di desktop tersembunyi -->
<div class="topbar d-flex d-lg-none">
  <div class="sidebar-logo-icon"><i class="bi bi-book-half"></i></div>
  <span class="topbar-brand">Campus Notes</span>
  <button class="btn btn-sm btn-outline-secondary ms-auto"
          data-bs-toggle="offcanvas" data-bs-target="#sidebarMobile">
    <i class="bi bi-list fs-5"></i>
  </button>
</div>
<?php endif; ?>
