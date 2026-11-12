<?php
$page_title = 'Edit Catatan';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$id  = (int)($_GET['id'] ?? 0);
$uid = $_SESSION['user']['id_user'];
$db  = db();

// Ambil data catatan, pastikan milik user sendiri
$stmt = $db->prepare('SELECT * FROM catatan WHERE id_catatan=? AND id_user=?');
$stmt->bind_param('ii', $id, $uid);
$stmt->execute();
$note = $stmt->get_result()->fetch_assoc();
if (!$note) {
    echo '<div class="container mt-5"><div class="alert alert-danger">Catatan tidak ditemukan atau bukan milikmu.</div></div>';
    require_once __DIR__ . '/../includes/footer.php'; exit;
}

$matkul_list = [
    'Pemrograman Web','Machine Learning','Pengantar Big Data',
    'UI/UX','Keamanan Jaringan','Pemrograman IoT',
    'Analisis dan Perancangan Sistem Informasi (APSI)','Pengantar Multimedia'
];

$err = []; $success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul  = trim($_POST['judul'] ?? '');
    $matkul = $_POST['mata_kuliah'] ?? '';
    $tahun  = $_POST['tahun_kuliah'] ?? '';
    $desk   = trim($_POST['deskripsi'] ?? '');

    if (!$judul)  $err[] = 'Judul wajib diisi.';
    if (!$matkul) $err[] = 'Mata kuliah wajib dipilih.';
    if (!$tahun)  $err[] = 'Tahun kuliah wajib diisi.';

    // Jika ada file baru diunggah
    $namaAsli    = $note['nama_file_asli'];
    $namaEnkripsi = $note['nama_file_enkripsi'];
    $ekstensi    = $note['ekstensi'];
    $ukuran      = $note['ukuran_file'];

    if (!empty($_FILES['file']['name'])) {
        $file    = $_FILES['file'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf'];

        if (!in_array($ext, $allowed))         $err[] = 'Format file tidak didukung.';
        if ($file['size'] > 500*1024*1024)      $err[] = 'Ukuran file melebihi 500 MB.';
        if ($file['error'] !== UPLOAD_ERR_OK)  $err[] = 'Terjadi kesalahan upload.';

        if (!$err) {
            // Hapus file lama
            $oldPath = UPLOAD_DIR . $note['nama_file_enkripsi'];
            if (file_exists($oldPath)) unlink($oldPath);

            $namaAsli     = $file['name'];
            $namaEnkripsi = bin2hex(random_bytes(16)) . '.' . $ext;
            $ekstensi     = $ext;
            $ukuran       = $file['size'];
            move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $namaEnkripsi);
        }
    }

    if (!$err) {
        $upd = $db->prepare('
            UPDATE catatan SET judul=?,deskripsi=?,mata_kuliah=?,tahun_kuliah=?,
                nama_file_asli=?,nama_file_enkripsi=?,ekstensi=?,ukuran_file=?
            WHERE id_catatan=? AND id_user=?
        ');
        $upd->bind_param('sssisssiii',
            $judul, $desk, $matkul, $tahun,
            $namaAsli, $namaEnkripsi, $ekstensi, $ukuran,
            $id, $uid
        );
        $upd->execute();
        $success = true;
        // Refresh data
        $stmt->execute();
        $note = $db->prepare('SELECT * FROM catatan WHERE id_catatan=?');
        $note->bind_param('i', $id);
        $note->execute();
        $note = $note->get_result()->fetch_assoc();
    }
}
?>
<div class="app-wrapper">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="app-content">
      <h5 class="fw-bold mb-4"><i class="bi bi-pencil me-2"></i>Edit Catatan</h5>

      <?php if ($success): ?>
        <div class="alert alert-success">Catatan berhasil diperbarui!
          <a href="<?= BASE_URL ?>/notes/detail.php?id=<?= $id ?>" class="alert-link ms-2">Lihat detail</a>
        </div>
      <?php endif; ?>
      <?php if ($err): ?>
        <div class="alert alert-danger"><ul class="mb-0 ps-3">
          <?php foreach($err as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
        </ul></div>
      <?php endif; ?>

      <div class="card border-0 shadow-sm rounded-4 p-4" style="max-width:600px">
        <form method="POST" enctype="multipart/form-data">
          <div class="mb-3">
            <label class="form-label fw-semibold">Judul Catatan</label>
            <input name="judul" class="form-control"
                   value="<?= htmlspecialchars($_POST['judul'] ?? $note['judul']) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Mata Kuliah</label>
            <select name="mata_kuliah" class="form-select" required>
              <?php foreach($matkul_list as $mk): ?>
                <option value="<?= $mk ?>"
                  <?= ($_POST['mata_kuliah'] ?? $note['mata_kuliah'])===$mk ? 'selected':'' ?>>
                  <?= $mk ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Tahun Kuliah</label>
            <select name="tahun_kuliah" class="form-select" required>
              <?php for($y=date('Y'); $y>=2018; $y--): ?>
                <option value="<?= $y ?>"
                  <?= ($_POST['tahun_kuliah'] ?? $note['tahun_kuliah'])==$y ? 'selected':'' ?>>
                  <?= $y ?>
                </option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Deskripsi</label>
            <textarea name="deskripsi" class="form-control" rows="3"><?=
              htmlspecialchars($_POST['deskripsi'] ?? $note['deskripsi'] ?? '')
            ?></textarea>
          </div>
          <div class="mb-4">
            <label class="form-label fw-semibold">Ganti File <span class="text-muted fw-normal">(opsional)</span></label>
            <div class="alert alert-light border small mb-2">
              <i class="bi bi-file-earmark me-1"></i>
              File saat ini: <strong><?= htmlspecialchars($note['nama_file_asli']) ?></strong>
              (<?= formatSize($note['ukuran_file']) ?>)
            </div>
            <input name="file" type="file" class="form-control" accept=".pdf">
            <div class="form-text">Kosongkan jika tidak ingin mengganti file.</div>
          </div>
          <div class="d-flex gap-2">
            <button class="btn btn-primary fw-semibold"><i class="bi bi-save me-1"></i>Simpan Perubahan</button>
            <a href="<?= BASE_URL ?>/notes/detail.php?id=<?= $id ?>" class="btn btn-outline-secondary">Batal</a>
          </div>
        </form>
      </div>
</div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>