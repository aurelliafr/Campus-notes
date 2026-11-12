<?php
$page_title = 'Masuk';
require_once __DIR__ . '/../includes/header.php';

// Kalau sudah login, langsung ke dashboard
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/dashboard/index.php');
    exit;
}

$pesanError = '';

// Proses form login saat tombol "Masuk" ditekan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Cari user berdasarkan email
    $db   = db();
    $stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        // Login berhasil — perbarui badge sesuai poin terkini
        $badgeBaru = getBadge($user['poin_reputasi']);
        if ($badgeBaru !== $user['badge']) {
            $db->query("UPDATE users SET badge='$badgeBaru' WHERE id_user={$user['id_user']}");
            $user['badge'] = $badgeBaru;
        }

        // Simpan data user ke session
        $_SESSION['user'] = [
            'id_user'       => $user['id_user'],
            'nama'          => $user['nama'],
            'nim'           => $user['nim'],
            'email'         => $user['email'],
            'role'          => $user['role'],
            'badge'         => $user['badge'],
            'foto_profil'   => $user['foto_profil'],
            'poin_reputasi' => $user['poin_reputasi'],
        ];

        header('Location: ' . BASE_URL . '/dashboard/index.php');
        exit;
    } else {
        $pesanError = 'Email atau password salah.';
    }
}
?>

<div class="min-vh-100 d-flex align-items-center justify-content-center" style="background:linear-gradient(135deg,#dbeafe,#cffafe)">
  <div class="card border-0 shadow rounded-4 p-4" style="width:100%;max-width:400px">

    <!-- Judul -->
    <div class="text-center mb-4">
      <div class="mb-2" style="font-size:2.5rem">📚</div>
      <h4 class="fw-bold">Masuk ke Campus Notes</h4>
      <p class="text-muted small mb-0">Selamat datang kembali!</p>
    </div>

    <!-- Pesan sukses setelah registrasi -->
    <?php if (isset($_GET['registered'])): ?>
      <div class="alert alert-success small">Registrasi berhasil! Silakan login.</div>
    <?php endif; ?>

    <!-- Pesan error login -->
    <?php if ($pesanError): ?>
      <div class="alert alert-danger small"><?= htmlspecialchars($pesanError) ?></div>
    <?php endif; ?>

    <!-- Form login -->
    <form method="POST">
      <div class="mb-3">
        <label class="form-label fw-semibold">Email</label>
        <input name="email" type="email" class="form-control" placeholder="email@kampus.ac.id" required>
      </div>
      <div class="mb-4">
        <label class="form-label fw-semibold">Password</label>
        <input name="password" type="password" class="form-control" placeholder="••••••••" required>
      </div>
      <button class="btn btn-primary w-100 fw-semibold py-2"
              style="background:linear-gradient(90deg,#2563eb,#06b6d4);border:none">
        Masuk
      </button>
    </form>

    <p class="text-center small mt-3 mb-0">
      Belum punya akun? <a href="<?= BASE_URL ?>/auth/register.php" class="text-primary fw-semibold">Daftar</a>
    </p>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
