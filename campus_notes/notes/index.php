<?php
$page_title = 'Daftar Catatan';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$db = db();

$daftarMatkul = [
    'Pemrograman Web', 'Machine Learning', 'Pengantar Big Data',
    'UI/UX', 'Keamanan Jaringan', 'Pemrograman IoT',
    'Analisis dan Perancangan Sistem Informasi (APSI)', 'Pengantar Multimedia'
];

$filterMatkul = $_GET['matkul'] ?? '';
$kataCari     = trim($_GET['q'] ?? '');

$kondisi = "WHERE c.status = 'aktif'";
$params  = [];
$tipe    = '';

if ($filterMatkul) {
    $kondisi .= ' AND c.mata_kuliah = ?';
    $tipe    .= 's';
    $params[] = $filterMatkul;
}
if ($kataCari) {
    $kondisi .= ' AND (c.judul LIKE ? OR c.deskripsi LIKE ?)';
    $tipe    .= 'ss';
    $params[] = "%$kataCari%";
    $params[] = "%$kataCari%";
}

$sql  = "SELECT c.*, u.nama AS nama_user, u.badge
         FROM catatan c
         JOIN users u ON c.id_user = u.id_user
         $kondisi
         ORDER BY c.tanggal_upload DESC";

$stmt = $db->prepare($sql);
if ($tipe) $stmt->bind_param($tipe, ...$params);
$stmt->execute();
$semuaCatatan = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Warna aksen per mata kuliah supaya kartu lebih colorful
$warnaMatkul = [
    'Pemrograman Web'    => ['#dbeafe','#2563eb'],
    'Machine Learning'   => ['#fce7f3','#db2777'],
    'Pengantar Big Data' => ['#e0e7ff','#4f46e5'],
    'UI/UX'             => ['#d1fae5','#059669'],
    'Keamanan Jaringan' => ['#fee2e2','#dc2626'],
    'Pemrograman IoT'   => ['#fef3c7','#d97706'],
    'Analisis dan Perancangan Sistem Informasi (APSI)' => ['#cffafe','#0e7490'],
    'Pengantar Multimedia' => ['#ede9fe','#7c3aed'],
];
?>

<div class="app-wrapper">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="app-content">

    <div class="page-header">
      <h5><i class="bi bi-journals me-2"></i>Daftar Catatan</h5>
      <p><?= count($semuaCatatan) ?> catatan ditemukan<?= $filterMatkul ? ' untuk ' . htmlspecialchars($filterMatkul) : '' ?></p>
    </div>

    <!-- Filter & pencarian -->
    <form class="card border-0 shadow-sm rounded-4 p-3 mb-4" method="GET">
      <div class="row g-2 align-items-center">
        <div class="col-md-5">
          <div class="input-group">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input name="q" class="form-control border-start-0 ps-0" placeholder="Cari judul atau deskripsi..."
                   value="<?= htmlspecialchars($kataCari) ?>">
          </div>
        </div>
        <div class="col-md-4">
          <select name="matkul" class="form-select">
            <option value="">Semua Mata Kuliah</option>
            <?php foreach ($daftarMatkul as $mk): ?>
              <option value="<?= $mk ?>" <?= $filterMatkul === $mk ? 'selected' : '' ?>><?= $mk ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
          <button class="btn btn-brand w-100"><i class="bi bi-search me-1"></i>Cari</button>
          <a href="<?= BASE_URL ?>/notes/index.php" class="btn btn-outline-secondary">Reset</a>
        </div>
      </div>
    </form>

    <!-- Grid kartu catatan -->
    <?php if ($semuaCatatan): ?>
      <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
        <?php foreach ($semuaCatatan as $n):
          // Ambil warna untuk matkul ini (fallback ke biru default)
          [$bg, $aksen] = $warnaMatkul[$n['mata_kuliah']] ?? ['#dbeafe','#2563eb'];
        ?>
          <div class="col">
            <div class="card h-100 border-0 shadow-sm rounded-4 card-hover">

              <!-- Strip warna atas sebagai aksen matkul -->
              <div style="height:5px;background:<?= $aksen ?>;border-radius:16px 16px 0 0"></div>

              <div class="card-body px-4 py-3">
                <!-- Badge matkul -->
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <span class="badge rounded-pill fw-semibold" style="background:<?= $bg ?>;color:<?= $aksen ?>">
                    <?= htmlspecialchars($n['mata_kuliah']) ?>
                  </span>
                  <small class="text-muted"><?= date('d M Y', strtotime($n['tanggal_upload'])) ?></small>
                </div>

                <!-- Judul -->
                <h6 class="fw-bold mb-1" style="font-size:15px"><?= htmlspecialchars($n['judul']) ?></h6>

                <!-- Deskripsi -->
                <p class="text-muted small mb-3" style="min-height:34px">
                  <?= htmlspecialchars(mb_strimwidth($n['deskripsi'] ?? '-', 0, 85, '...')) ?>
                </p>

                <!-- Uploader -->
                <div class="d-flex align-items-center gap-2 mb-3">
                  <i class="bi bi-person-circle text-muted"></i>
                  <span class="small fw-semibold"><?= htmlspecialchars($n['nama_user']) ?></span>
                  <span class="badge ms-1" style="background:#fef3c7;color:#92400e;font-size:10px">
                    <?= badgeIcon($n['badge']) ?> <?= $n['badge'] ?>
                  </span>
                </div>

                <!-- Statistik -->
                <div class="d-flex gap-3">
                  <span class="small text-muted"><i class="bi bi-download me-1"></i><?= $n['jumlah_download'] ?></span>
                  <span class="small text-muted"><i class="bi bi-hand-thumbs-up me-1"></i><?= $n['jumlah_upvote'] ?></span>
                  <?php
                  $tipeN = $n['tipe_file'] ?? 'pdf';
                  $ikonTipe  = ['pdf' => 'bi-file-earmark-pdf text-danger', 'gambar' => 'bi-image text-success', 'video' => 'bi-camera-video text-primary', 'doc' => 'bi-file-earmark-word text-warning'];
                  $ikonClass = $ikonTipe[$tipeN] ?? 'bi-file-earmark text-secondary';
                  ?>
                  <span class="small ms-auto <?= str_contains($ikonClass, 'text-') ? explode(' ', $ikonClass)[1] : 'text-secondary' ?>">
                    <i class="bi <?= explode(' ', $ikonClass)[0] ?> me-1"></i><?= strtoupper($n['ekstensi']) ?>
                  </span>
                </div>
              </div>

              <div class="card-footer bg-transparent border-0 px-4 pb-3">
                <a href="<?= BASE_URL ?>/notes/detail.php?id=<?= $n['id_catatan'] ?>"
                   class="btn btn-brand btn-sm w-100">
                  <i class="bi bi-eye me-1"></i>Lihat Detail
                </a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <!-- Empty state -->
      <div class="text-center py-5">
        <div style="font-size:3.5rem">🔍</div>
        <p class="text-muted mt-2 mb-3">
          <?= ($kataCari || $filterMatkul) ? 'Tidak ada catatan yang cocok dengan filter.' : 'Belum ada catatan di sini.' ?>
        </p>
        <?php if ($kataCari || $filterMatkul): ?>
          <a href="<?= BASE_URL ?>/notes/index.php" class="btn btn-outline-secondary">Hapus Filter</a>
        <?php else: ?>
          <a href="<?= BASE_URL ?>/notes/tambah.php" class="btn btn-brand">
            <i class="bi bi-upload me-1"></i>Upload Catatan Pertama
          </a>
        <?php endif; ?>
      </div>
    <?php endif; ?>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
