<?php
$page_title = 'Profil';
require_once __DIR__ . '/includes/header.php';
requireLogin();

$uid = $_SESSION['user']['id_user'];
$db  = db();

// Ambil data user terkini
$user = $db->query("SELECT * FROM users WHERE id_user=$uid")->fetch_assoc();

// Statistik
$stats = $db->query("
    SELECT
        COUNT(*) AS total_upload,
        COALESCE(SUM(jumlah_download),0) AS total_didownload
    FROM catatan WHERE id_user=$uid
")->fetch_assoc();

$err     = [];
$success = '';
$tab     = $_GET['tab'] ?? 'profil';

// ── Handle Edit Profil ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['edit_profil'])) {
    $nama  = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    if (!$nama)                                      $err[] = 'Nama wajib diisi.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))  $err[] = 'Email tidak valid.';

    // Cek email duplikat (selain milik sendiri)
    $cek = $db->prepare('SELECT id_user FROM users WHERE email=? AND id_user!=?');
    $cek->bind_param('si', $email, $uid);
    $cek->execute();
    if ($cek->get_result()->num_rows > 0) $err[] = 'Email sudah digunakan akun lain.';

    // Upload foto profil
    $fotoProfil = $user['foto_profil'];
    if (!empty($_FILES['foto']['name'])) {
        $file    = $_FILES['foto'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png'];
        if (!in_array($ext, $allowed))        $err[] = 'Foto harus JPG atau PNG.';
        if ($file['size'] > 2*1024*1024)      $err[] = 'Foto maks 2 MB.';
        if ($file['error'] !== UPLOAD_ERR_OK) $err[] = 'Gagal upload foto.';

        if (!$err) {
            $imgDir = __DIR__ . '/assets/images/';
            // Hapus foto lama jika bukan default
            if ($fotoProfil !== 'default.png' && file_exists($imgDir.$fotoProfil)) {
                unlink($imgDir.$fotoProfil);
            }
            $fotoProfil = 'profil_' . $uid . '_' . time() . '.' . $ext;
            move_uploaded_file($file['tmp_name'], $imgDir.$fotoProfil);
        }
    }

    if (!$err) {
        $upd = $db->prepare('UPDATE users SET nama=?,email=?,foto_profil=? WHERE id_user=?');
        $upd->bind_param('sssi', $nama, $email, $fotoProfil, $uid);
        $upd->execute();
        // Perbarui session
        $_SESSION['user']['nama']       = $nama;
        $_SESSION['user']['email']      = $email;
        $_SESSION['user']['foto_profil'] = $fotoProfil;
        $user = $db->query("SELECT * FROM users WHERE id_user=$uid")->fetch_assoc();
        $success = 'Profil berhasil diperbarui!';
    }
    $tab = 'profil';
}

// ── Handle Ubah Password ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['ubah_password'])) {
    $lama  = $_POST['password_lama'] ?? '';
    $baru  = $_POST['password_baru'] ?? '';
    $konfirm = $_POST['konfirmasi'] ?? '';

    if (!password_verify($lama, $user['password'])) $err[] = 'Password lama salah.';
    if (strlen($baru) < 6)   $err[] = 'Password baru minimal 6 karakter.';
    if ($baru !== $konfirm)  $err[] = 'Konfirmasi password tidak cocok.';

    if (!$err) {
        $hash = password_hash($baru, PASSWORD_DEFAULT);
        $upd  = $db->prepare('UPDATE users SET password=? WHERE id_user=?');
        $upd->bind_param('si', $hash, $uid);
        $upd->execute();
        $success = 'Password berhasil diubah!';
    }
    $tab = 'password';
}
?>
<div class="app-wrapper">
  <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

  <div class="app-content">
      <h5 class="fw-bold mb-4"><i class="bi bi-person-circle me-2"></i>Profil Saya</h5>

      <!-- Info Profil Atas -->
      <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <div class="d-flex align-items-center gap-4 flex-wrap">
          <?= avatarHtml($user['nama'], $user['foto_profil'], 90, '32px') ?>
          <div>
            <h5 class="fw-bold mb-1"><?= htmlspecialchars($user['nama']) ?></h5>
            <div class="text-muted small mb-1"><i class="bi bi-credit-card me-1"></i><?= htmlspecialchars($user['nim']) ?></div>
            <div class="text-muted small mb-2"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($user['email']) ?></div>
            <span class="badge bg-warning text-dark fs-6"><?= badgeIcon($user['badge']).' '.$user['badge'] ?></span>
          </div>
          <div class="ms-auto d-flex gap-4 text-center">
            <div>
              <div class="fs-4 fw-bold text-primary"><?= $user['poin_reputasi'] ?></div>
              <div class="text-muted small">Poin</div>
            </div>
            <div>
              <div class="fs-4 fw-bold text-success"><?= $stats['total_upload'] ?></div>
              <div class="text-muted small">Upload</div>
            </div>
            <div>
              <div class="fs-4 fw-bold text-info"><?= $stats['total_didownload'] ?></div>
              <div class="text-muted small">Didownload</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Badge Progress -->
      <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <h6 class="fw-bold mb-3"><i class="bi bi-award me-2"></i>Progress Badge</h6>
        <div class="d-flex justify-content-between mb-1 small">
          <span>🌱 Pemula</span><span>📘 Kontributor</span><span>⭐ Ahli</span><span>👑 Master</span>
        </div>
        <?php
          $poin = $user['poin_reputasi'];
          $pct  = min(100, round($poin / 300 * 100));
        ?>
        <div class="progress rounded-pill" style="height:12px">
          <div class="progress-bar bg-warning" style="width:<?= $pct ?>%"></div>
        </div>
        <div class="text-muted small mt-1 text-end"><?= $poin ?> / 300 poin</div>
        <div class="row text-center mt-3 g-2">
          <?php $badges = [['🌱','Pemula','secondary',0],['📘','Kontributor','primary',50],['⭐','Ahli','warning',150],['👑','Master','danger',300]]; ?>
          <?php foreach($badges as [$icon,$name,$color,$min]): ?>
          <div class="col-3">
            <div class="badge bg-<?= $color ?> py-2 px-3 w-100 <?= $poin < $min ? 'opacity-25':'' ?>">
              <?= $icon ?> <?= $name ?>
            </div>
            <div class="text-muted small mt-1"><?= $min ?>+ poin</div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Tab Edit Profil / Password -->
      <div class="card border-0 shadow-sm rounded-4 p-4">
        <ul class="nav nav-tabs mb-4">
          <li class="nav-item">
            <a class="nav-link <?= $tab==='profil'?'active':'' ?>" href="?tab=profil">
              <i class="bi bi-person me-1"></i>Edit Profil
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $tab==='password'?'active':'' ?>" href="?tab=password">
              <i class="bi bi-lock me-1"></i>Ubah Password
            </a>
          </li>
        </ul>

        <?php if ($success): ?>
          <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($err): ?>
          <div class="alert alert-danger"><ul class="mb-0 ps-3">
            <?php foreach($err as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
          </ul></div>
        <?php endif; ?>

        <?php if ($tab==='profil'): ?>
        <!-- Form Edit Profil -->
        <form method="POST" enctype="multipart/form-data" style="max-width:480px">
          <div class="mb-3">
            <label class="form-label fw-semibold">Nama Lengkap</label>
            <input name="nama" class="form-control" value="<?= htmlspecialchars($user['nama']) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Email</label>
            <input name="email" type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">NIM</label>
            <input class="form-control" value="<?= htmlspecialchars($user['nim']) ?>" disabled>
            <div class="form-text">NIM tidak dapat diubah.</div>
          </div>
          <div class="mb-4">
            <label class="form-label fw-semibold">Foto Profil</label>
            <input name="foto" type="file" class="form-control" accept=".jpg,.jpeg,.png">
            <div class="form-text">JPG/PNG, maks 2 MB. Kosongkan jika tidak ingin mengganti.</div>
          </div>
          <button name="edit_profil" class="btn btn-primary fw-semibold">
            <i class="bi bi-save me-1"></i>Simpan Profil
          </button>
        </form>

        <?php else: ?>
        <!-- Form Ubah Password -->
        <form method="POST" style="max-width:480px">
          <div class="mb-3">
            <label class="form-label fw-semibold">Password Lama</label>
            <input name="password_lama" type="password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Password Baru</label>
            <input name="password_baru" type="password" class="form-control" required>
          </div>
          <div class="mb-4">
            <label class="form-label fw-semibold">Konfirmasi Password Baru</label>
            <input name="konfirmasi" type="password" class="form-control" required>
          </div>
          <button name="ubah_password" class="btn btn-primary fw-semibold">
            <i class="bi bi-lock me-1"></i>Ubah Password
          </button>
        </form>
        <?php endif; ?>
      </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>