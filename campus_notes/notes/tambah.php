<?php
$page_title = 'Upload Catatan';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$daftarMatkul = [
    'Pemrograman Web', 'Machine Learning', 'Pengantar Big Data',
    'UI/UX', 'Keamanan Jaringan', 'Pemrograman IoT',
    'Analisis dan Perancangan Sistem Informasi (APSI)', 'Pengantar Multimedia'
];

// Definisi tipe file yang diizinkan
$tipeIzin = [
    'pdf'    => ['pdf'],
    'gambar' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
    'video'  => ['mp4', 'webm', 'mov', 'avi'],
    'doc'    => ['doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt'],
];
$semuaEkstensi = array_merge(...array_values($tipeIzin));

function getTipeFile(string $ekstensi): string {
    $map = [
        'pdf'  => 'pdf',
        'jpg'  => 'gambar', 'jpeg' => 'gambar', 'png' => 'gambar', 'webp' => 'gambar', 'gif' => 'gambar',
        'mp4'  => 'video',  'webm' => 'video',  'mov' => 'video',  'avi'  => 'video',
        'doc'  => 'doc',    'docx' => 'doc',    'ppt' => 'doc',    'pptx' => 'doc',
        'xls'  => 'doc',    'xlsx' => 'doc',    'txt' => 'doc',
    ];
    return $map[$ekstensi] ?? 'doc';
}

$daftarError = [];
$berhasil    = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul     = trim($_POST['judul'] ?? '');
    $matkul    = $_POST['mata_kuliah'] ?? '';
    $tahun     = $_POST['tahun_kuliah'] ?? '';
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $uid       = $_SESSION['user']['id_user'];

    if (!$judul)  $daftarError[] = 'Judul wajib diisi.';
    if (!$matkul) $daftarError[] = 'Mata kuliah wajib dipilih.';
    if (!$tahun)  $daftarError[] = 'Tahun kuliah wajib diisi.';

    if (empty($_FILES['file']['name'])) {
        $daftarError[] = 'File wajib diunggah.';
    } else {
        $file     = $_FILES['file'];
        $ekstensi = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $maks500MB = 500 * 1024 * 1024;

        if (!in_array($ekstensi, $semuaEkstensi)) {
            $daftarError[] = 'Tipe file tidak diizinkan. Gunakan: PDF, gambar (JPG/PNG/WEBP), video (MP4/WEBM), atau dokumen (DOC/DOCX/PPT/PPTX/XLS/XLSX/TXT).';
        }
        if ($file['size'] > $maks500MB)       $daftarError[] = 'Ukuran file melebihi 500 MB.';
        if ($file['error'] !== UPLOAD_ERR_OK) $daftarError[] = 'Terjadi kesalahan saat upload.';
    }

    if (!$daftarError) {
        $tipeFile    = getTipeFile($ekstensi);
        $namaAsli    = $file['name'];
        $namaRahasia = bin2hex(random_bytes(16)) . '.' . $ekstensi;
        $tujuan      = UPLOAD_DIR . $namaRahasia;

        if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

        if (move_uploaded_file($file['tmp_name'], $tujuan)) {
            $db   = db();
            $stmt = $db->prepare('
                INSERT INTO catatan
                    (id_user, judul, deskripsi, mata_kuliah, tahun_kuliah,
                     nama_file_asli, nama_file_enkripsi, ekstensi, tipe_file, ukuran_file)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->bind_param('issssssssi',
                $uid, $judul, $deskripsi, $matkul, $tahun,
                $namaAsli, $namaRahasia, $ekstensi, $tipeFile, $file['size']
            );
            $stmt->execute();

            $poinBaru  = $_SESSION['user']['poin_reputasi'] + 10;
            $badgeBaru = getBadge($poinBaru);
            $db->query("UPDATE users SET poin_reputasi = $poinBaru, badge = '$badgeBaru' WHERE id_user = $uid");
            $_SESSION['user']['poin_reputasi'] = $poinBaru;
            $_SESSION['user']['badge']         = $badgeBaru;

            $berhasil = true;
        } else {
            $daftarError[] = 'Gagal menyimpan file ke server.';
        }
    }
}
?>

<div class="app-wrapper">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="app-content">
    <h5 class="fw-bold mb-4"><i class="bi bi-cloud-upload me-2"></i>Upload Catatan</h5>

    <?php if ($berhasil): ?>
      <div class="alert alert-success">
        🎉 Catatan berhasil diupload! Kamu mendapat <strong>+10 poin</strong>.
        <a href="<?= BASE_URL ?>/notes/index.php" class="alert-link ms-2">Lihat daftar catatan</a>
      </div>
    <?php endif; ?>

    <?php if ($daftarError): ?>
      <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
          <?php foreach ($daftarError as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4 p-4" style="max-width:600px">
      <form method="POST" enctype="multipart/form-data">

        <div class="mb-3">
          <label class="form-label fw-semibold">Judul Catatan</label>
          <input name="judul" class="form-control" placeholder="Contoh: Ringkasan Materi Pertemuan 5"
                 value="<?= htmlspecialchars($_POST['judul'] ?? '') ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Mata Kuliah</label>
          <select name="mata_kuliah" class="form-select" required>
            <option value="">-- Pilih Mata Kuliah --</option>
            <?php foreach ($daftarMatkul as $mk): ?>
              <option value="<?= $mk ?>" <?= ($_POST['mata_kuliah'] ?? '') === $mk ? 'selected' : '' ?>><?= $mk ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Tahun Kuliah</label>
          <select name="tahun_kuliah" class="form-select" required>
            <option value="">-- Pilih Tahun --</option>
            <?php for ($t = date('Y'); $t >= 2018; $t--): ?>
              <option value="<?= $t ?>" <?= (int)($_POST['tahun_kuliah'] ?? 0) === $t ? 'selected' : '' ?>><?= $t ?></option>
            <?php endfor; ?>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Deskripsi <span class="text-muted fw-normal">(opsional)</span></label>
          <textarea name="deskripsi" class="form-control" rows="3"
                    placeholder="Jelaskan singkat isi catatan ini..."><?= htmlspecialchars($_POST['deskripsi'] ?? '') ?></textarea>
        </div>

        <div class="mb-4">
          <label class="form-label fw-semibold">File Catatan <span class="text-muted fw-normal">(maks 500 MB)</span></label>
          <!-- Info tipe file yang diizinkan -->
          <div class="d-flex flex-wrap gap-2 mb-2">
            <span class="badge bg-danger-subtle text-danger-emphasis"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</span>
            <span class="badge bg-success-subtle text-success-emphasis"><i class="bi bi-image me-1"></i>Gambar (JPG, PNG, WEBP)</span>
            <span class="badge bg-primary-subtle text-primary-emphasis"><i class="bi bi-camera-video me-1"></i>Video (MP4, WEBM)</span>
            <span class="badge bg-warning-subtle text-warning-emphasis"><i class="bi bi-file-earmark-word me-1"></i>Dokumen (DOC, DOCX, PPT, PPTX, XLS, XLSX, TXT)</span>
          </div>
          <input name="file" type="file" class="form-control"
                 accept=".pdf,.jpg,.jpeg,.png,.webp,.gif,.mp4,.webm,.mov,.avi,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt"
                 required>
        </div>

        <button class="btn w-100 fw-semibold py-2 text-white btn-brand">
          <i class="bi bi-upload me-2"></i>Upload Sekarang
        </button>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
