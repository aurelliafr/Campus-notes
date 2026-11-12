<?php
$page_title = 'Daftar';
require_once __DIR__ . '/../includes/header.php';

// Kalau sudah login, langsung ke dashboard
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/dashboard/index.php');
    exit;
}

$daftarError = [];

// Proses form registrasi saat tombol "Daftar" ditekan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama      = trim($_POST['nama'] ?? '');
    $nim       = trim($_POST['nim']  ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi'] ?? '';

    // Validasi input
    if (!$nama)                                         $daftarError[] = 'Nama wajib diisi.';
    if (!$nim)                                          $daftarError[] = 'NIM wajib diisi.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))     $daftarError[] = 'Email tidak valid.';
    if (strlen($password) < 6)                          $daftarError[] = 'Password minimal 6 karakter.';
    if ($password !== $konfirmasi)                      $daftarError[] = 'Konfirmasi password tidak cocok.';

    if (!$daftarError) {
        $db = db();

        // Cek apakah NIM atau email sudah terdaftar
        $cek = $db->prepare('SELECT id_user FROM users WHERE nim = ? OR email = ?');
        $cek->bind_param('ss', $nim, $email);
        $cek->execute();

        if ($cek->get_result()->num_rows > 0) {
            $daftarError[] = 'NIM atau email sudah terdaftar.';
        } else {
            // Hash password sebelum disimpan (keamanan)
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $ins = $db->prepare('INSERT INTO users (nama, nim, email, password) VALUES (?, ?, ?, ?)');
            $ins->bind_param('ssss', $nama, $nim, $email, $passwordHash);
            $ins->execute();

            // Redirect ke halaman login dengan pesan sukses
            header('Location: ' . BASE_URL . '/auth/login.php?registered=1');
            exit;
        }
    }
}
?>

<div class="min-vh-100 d-flex align-items-center justify-content-center" style="background:linear-gradient(135deg,#dbeafe,#cffafe)">
  <div class="card border-0 shadow rounded-4 p-4" style="width:100%;max-width:440px">

    <!-- Judul -->
    <div class="text-center mb-4">
      <div class="mb-2" style="font-size:2.5rem">🎓</div>
      <h4 class="fw-bold">Daftar ke Campus Notes</h4>
      <p class="text-muted small mb-0">Buat akun dan mulai berbagi catatan</p>
    </div>

    <!-- Daftar error validasi -->
    <?php if ($daftarError): ?>
      <div class="alert alert-danger small">
        <ul class="mb-0 ps-3">
          <?php foreach ($daftarError as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <!-- Form registrasi -->
    <form method="POST">
      <div class="mb-3">
        <label class="form-label fw-semibold">Nama Lengkap</label>
        <input name="nama" class="form-control" placeholder="Nama kamu"
               value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">NIM</label>
        <input name="nim" class="form-control" placeholder="Nomor Induk Mahasiswa"
               value="<?= htmlspecialchars($_POST['nim'] ?? '') ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">Email</label>
        <input name="email" type="email" class="form-control" placeholder="email@kampus.ac.id"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">Password</label>
        <input name="password" type="password" class="form-control" placeholder="Minimal 6 karakter" required>
      </div>
      <div class="mb-4">
        <label class="form-label fw-semibold">Konfirmasi Password</label>
        <input name="konfirmasi" type="password" class="form-control" placeholder="Ulangi password" required>
      </div>
      <button class="btn w-100 fw-semibold py-2 text-white"
              style="background:linear-gradient(90deg,#2563eb,#06b6d4);border:none">
        Daftar Sekarang
      </button>
    </form>

    <p class="text-center small mt-3 mb-0">
      Sudah punya akun? <a href="<?= BASE_URL ?>/auth/login.php" class="text-primary fw-semibold">Masuk</a>
    </p>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
