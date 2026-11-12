<?php
$page_title = 'Detail Catatan';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$id  = (int)($_GET['id'] ?? 0);
$uid = $_SESSION['user']['id_user'];
$db  = db();

$stmt = $db->prepare('SELECT c.*,u.nama AS nama_user,u.badge,u.foto_profil FROM catatan c JOIN users u ON c.id_user=u.id_user WHERE c.id_catatan=? AND c.status="aktif"');
$stmt->bind_param('i', $id);
$stmt->execute();
$note = $stmt->get_result()->fetch_assoc();
if (!$note) { echo '<div class="container mt-5"><div class="alert alert-danger">Catatan tidak ditemukan.</div></div>'; require_once __DIR__.'/../includes/footer.php'; exit; }

// Cek apakah sudah upvote
$cekVote = $db->prepare('SELECT id_vote FROM upvotes WHERE id_user=? AND id_catatan=?');
$cekVote->bind_param('ii', $uid, $id);
$cekVote->execute();
$sudahVote = $cekVote->get_result()->num_rows > 0;

// Cek sudah report
$cekRep = $db->prepare('SELECT id_report FROM reports WHERE id_user=? AND id_catatan=?');
$cekRep->bind_param('ii', $uid, $id);
$cekRep->execute();
$sudahReport = $cekRep->get_result()->num_rows > 0;

// Komentar
$kom = $db->prepare('SELECT k.*,u.nama,u.foto_profil,u.badge FROM komentar k JOIN users u ON k.id_user=u.id_user WHERE k.id_catatan=? ORDER BY k.dibuat_pada DESC');
$kom->bind_param('i', $id);
$kom->execute();
$komentar = $kom->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle POST: komentar
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['komentar'])) {
    $isi = trim($_POST['komentar']);
    if ($isi) {
        $ins = $db->prepare('INSERT INTO komentar (id_catatan,id_user,isi_komentar) VALUES (?,?,?)');
        $ins->bind_param('iis', $id, $uid, $isi);
        $ins->execute();
    }
    header("Location: {$_SERVER['REQUEST_URI']}"); exit;
}
?>
<div class="app-wrapper">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="app-content">
      <div class="row g-4">
        <!-- Info catatan -->
        <div class="col-lg-8">
          <?php
          // Warna per mata kuliah — sama persis dengan notes/index.php
          $warnaMatkul = [
              'Pemrograman Web'    => ['#dbeafe','#2563eb'],
              'Machine Learning'   => ['#fce7f3','#db2777'],
              'Pengantar Big Data' => ['#e0e7ff','#4f46e5'],
              'UI/UX'              => ['#d1fae5','#059669'],
              'Keamanan Jaringan'  => ['#fee2e2','#dc2626'],
              'Pemrograman IoT'    => ['#fef3c7','#d97706'],
              'Analisis dan Perancangan Sistem Informasi (APSI)' => ['#cffafe','#0e7490'],
              'Pengantar Multimedia' => ['#ede9fe','#7c3aed'],
          ];
          [$bgMatkul, $aksenMatkul] = $warnaMatkul[$note['mata_kuliah']] ?? ['#dbeafe','#2563eb'];
          ?>
          <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
            <!-- Strip warna atas sesuai matkul -->
            <div style="height:5px;background:<?= $aksenMatkul ?>;border-radius:12px;margin:-24px -24px 16px -24px"></div>
            <span class="badge rounded-pill fw-semibold mb-3"
                  style="background:<?= $bgMatkul ?>;color:<?= $aksenMatkul ?>;font-size:13px;padding:7px 14px">
              <?= htmlspecialchars($note['mata_kuliah']) ?>
            </span>
            <h4 class="fw-bold"><?= htmlspecialchars($note['judul']) ?></h4>
            <p class="text-muted"><?= nl2br(htmlspecialchars($note['deskripsi'] ?? '')) ?></p>
            <hr>
            <div class="row text-center g-2 mb-3">
              <div class="col"><div class="fw-bold"><?= $note['jumlah_download'] ?></div><div class="text-muted small">Download</div></div>
              <div class="col"><div class="fw-bold"><?= $note['jumlah_upvote'] ?></div><div class="text-muted small">Upvote</div></div>
              <div class="col"><div class="fw-bold"><?= $note['tahun_kuliah'] ?></div><div class="text-muted small">Tahun</div></div>
              <div class="col"><div class="fw-bold"><?= formatSize($note['ukuran_file']) ?></div><div class="text-muted small">Ukuran</div></div>
            </div>
            <!-- Preview file sesuai tipe -->
            <?php
            $ekstensi = strtolower($note['ekstensi']);
            $fileUrl  = BASE_URL . '/assets/uploads/notes/' . htmlspecialchars($note['nama_file_enkripsi']);
            $tipeFile = $note['tipe_file'] ?? 'pdf';
            ?>
            <?php if ($tipeFile === 'gambar'): ?>
            <div class="mb-3 text-center">
              <img src="<?= $fileUrl ?>" class="img-fluid rounded-3 shadow-sm" style="max-height:400px;object-fit:contain"
                   alt="<?= htmlspecialchars($note['judul']) ?>">
            </div>
            <?php elseif ($tipeFile === 'video'): ?>
            <div class="mb-3">
              <video controls class="w-100 rounded-3 shadow-sm" style="max-height:400px">
                <source src="<?= $fileUrl ?>" type="video/<?= $ekstensi === 'webm' ? 'webm' : 'mp4' ?>">
                Browser kamu tidak mendukung video player. <a href="<?= $fileUrl ?>">Download video</a>
              </video>
            </div>
            <?php elseif ($tipeFile === 'doc'): ?>
            <div class="alert alert-light border mb-3">
              <i class="bi bi-file-earmark-word me-2 text-primary"></i>
              File dokumen (<strong><?= strtoupper($ekstensi) ?></strong>) — download untuk membukanya.
            </div>
            <?php endif; ?>

            <!-- Tombol Aksi -->
            <div class="d-flex flex-wrap gap-2">
              <a href="<?= BASE_URL ?>/notes/preview.php?id=<?= $id ?>" class="btn btn-outline-secondary" target="_blank">
                <i class="bi bi-eye me-1"></i>Preview
              </a>
              <a href="<?= BASE_URL ?>/notes/download.php?id=<?= $id ?>" class="btn btn-success">
                <i class="bi bi-download me-1"></i>Download
              </a>
              <?php if (!$sudahVote): ?>
              <a href="<?= BASE_URL ?>/handlers/upvote.php?id=<?= $id ?>" class="btn btn-outline-primary">
                <i class="bi bi-hand-thumbs-up me-1"></i>Upvote
              </a>
              <?php else: ?>
              <button class="btn btn-primary" disabled><i class="bi bi-hand-thumbs-up-fill me-1"></i>Diupvote</button>
              <?php endif; ?>
              <?php if (!$sudahReport && $note['id_user'] != $uid): ?>
              <a href="<?= BASE_URL ?>/handlers/report.php?id=<?= $id ?>" class="btn btn-outline-danger"
                 onclick="return confirm('Laporkan catatan ini?')">
                <i class="bi bi-flag me-1"></i>Laporkan
              </a>
              <?php endif; ?>
              <?php if ($note['id_user'] == $uid): ?>
              <a href="<?= BASE_URL ?>/notes/edit.php?id=<?= $id ?>" class="btn btn-outline-secondary">
                <i class="bi bi-pencil me-1"></i>Edit
              </a>
              <a href="<?= BASE_URL ?>/notes/hapus.php?id=<?= $id ?>" class="btn btn-outline-danger"
                 onclick="return confirm('Hapus catatan ini?')">
                <i class="bi bi-trash me-1"></i>Hapus
              </a>
              <?php endif; ?>
            </div>
          </div>

          <!-- AI Tools -->
          <div class="card border-0 shadow-sm rounded-4 p-4 mb-4" id="ai-tools-card">
            <h6 class="fw-bold mb-3"><i class="bi bi-stars me-2 text-primary"></i>Asisten AI</h6>
            <p class="text-muted small mb-3">Biar AI bantu kamu pahami catatan ini lebih cepat.</p>

            <?php
            $ekstensiNote = strtolower($note['ekstensi']);
            $tipeNoteFile = $note['tipe_file'] ?? 'pdf';
            $aiDidukung   = in_array($ekstensiNote, ['pdf','jpg','jpeg','png','webp','gif','doc','docx','ppt','pptx','xls','xlsx','txt']);
            ?>
            <?php if ($tipeNoteFile === 'video'): ?>
              <div class="alert alert-secondary small mb-0"><i class="bi bi-camera-video me-1"></i>Fitur AI tidak tersedia untuk file video.</div>
            <?php elseif (!$aiDidukung): ?>
              <div class="alert alert-secondary small mb-0">Fitur AI tidak tersedia untuk tipe file ini.</div>
            <?php else: ?>
            <div class="d-flex flex-wrap gap-2 mb-3">
              <button class="btn btn-outline-primary btn-sm" id="btn-ai-ringkasan" onclick="loadAiRingkasan(<?= $id ?>)">
                <i class="bi bi-file-text me-1"></i>Buat Ringkasan AI
              </button>
              <button class="btn btn-outline-primary btn-sm" id="btn-ai-kuis" onclick="loadAiKuis(<?= $id ?>)">
                <i class="bi bi-patch-question me-1"></i>Buat Kuis AI
              </button>
            </div>

            <div id="ai-ringkasan-box" class="d-none">
              <hr>
              <h6 class="fw-bold small text-primary"><i class="bi bi-file-text me-1"></i>Ringkasan</h6>
              <p id="ai-ringkasan-text" class="small"></p>
              <ul id="ai-ringkasan-poin" class="small ps-4 mb-0"></ul>
            </div>

            <div id="ai-kuis-box" class="d-none">
              <hr>
              <h6 class="fw-bold small text-primary mb-3"><i class="bi bi-patch-question me-1"></i>Kuis Latihan</h6>
              <div id="ai-kuis-list"></div>
              <div id="ai-kuis-hasil" class="alert alert-info small d-none mt-3"></div>
            </div>

            <div id="ai-loading" class="text-center text-muted small d-none py-3">
              <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
              Sedang menganalisis file dengan AI, mohon tunggu...
            </div>
            <div id="ai-error" class="alert alert-danger small d-none mt-3 mb-0"></div>
            <?php endif; ?>
          </div>

          <!-- Komentar -->
          <div class="card border-0 shadow-sm rounded-4 p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-chat-dots me-2"></i>Komentar (<?= count($komentar) ?>)</h6>

            <form method="POST" class="mb-4">
              <div class="input-group">
                <input name="komentar" class="form-control" placeholder="Tulis komentar..." required>
                <button class="btn btn-primary">Kirim</button>
              </div>
            </form>
            <?php foreach($komentar as $k): ?>
            <div class="d-flex gap-3 mb-3">
              <?= avatarHtml($k['nama'], $k['foto_profil'], 36, '13px') ?>
              <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2">
                  <strong class="small"><?= htmlspecialchars($k['nama']) ?></strong>
                  <span class="badge bg-warning text-dark"><?= badgeIcon($k['badge']).' '.$k['badge'] ?></span>
                  <span class="text-muted small ms-auto"><?= date('d M Y H:i', strtotime($k['dibuat_pada'])) ?></span>
                </div>
                <p class="mb-0 small mt-1"><?= nl2br(htmlspecialchars($k['isi_komentar'])) ?></p>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Info uploader -->
        <div class="col-lg-4">
          <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
            <div class="d-flex justify-content-center mb-3">
              <?= avatarHtml($note['nama_user'], $note['foto_profil'], 72, '26px') ?>
            </div>
            <div class="fw-bold"><?= htmlspecialchars($note['nama_user']) ?></div>
            <span class="badge bg-warning text-dark mt-1"><?= badgeIcon($note['badge']).' '.$note['badge'] ?></span>
            <hr>
            <div class="text-muted small">Diupload: <?= date('d M Y', strtotime($note['tanggal_upload'])) ?></div>
          </div>
        </div>
      </div>
  </div>
</div>

<script>
const AI_BASE_URL = <?= json_encode(BASE_URL) ?>;

function aiShowLoading(show) {
  document.getElementById('ai-loading').classList.toggle('d-none', !show);
  document.getElementById('ai-error').classList.add('d-none');
}

function aiShowError(msg) {
  const box = document.getElementById('ai-error');
  box.textContent = msg;
  box.classList.remove('d-none');
}

async function loadAiRingkasan(id) {
  const btn = document.getElementById('btn-ai-ringkasan');
  btn.disabled = true;
  aiShowLoading(true);
  try {
    const res = await fetch(`${AI_BASE_URL}/handlers/ai_summary.php?id=${id}`);
    const json = await res.json();
    aiShowLoading(false);
    btn.disabled = false;

    if (!json.ok) {
      aiShowError(json.error || 'Gagal membuat ringkasan.');
      return;
    }

    document.getElementById('ai-ringkasan-text').textContent = json.ringkasan;
    const ul = document.getElementById('ai-ringkasan-poin');
    ul.innerHTML = '';
    (json.poin_penting || []).forEach(p => {
      const li = document.createElement('li');
      li.textContent = p;
      ul.appendChild(li);
    });
    document.getElementById('ai-ringkasan-box').classList.remove('d-none');
    btn.innerHTML = '<i class="bi bi-check2 me-1"></i>Ringkasan Siap';
  } catch (e) {
    aiShowLoading(false);
    btn.disabled = false;
    aiShowError('Terjadi kesalahan koneksi ke server AI.');
  }
}

async function loadAiKuis(id) {
  const btn = document.getElementById('btn-ai-kuis');
  btn.disabled = true;
  aiShowLoading(true);
  try {
    const res = await fetch(`${AI_BASE_URL}/handlers/ai_quiz.php?id=${id}`);
    const json = await res.json();
    aiShowLoading(false);
    btn.disabled = false;

    if (!json.ok) {
      aiShowError(json.error || 'Gagal membuat kuis.');
      return;
    }

    renderKuis(json.soal || []);
    document.getElementById('ai-kuis-box').classList.remove('d-none');
    btn.innerHTML = '<i class="bi bi-check2 me-1"></i>Kuis Siap';
  } catch (e) {
    aiShowLoading(false);
    btn.disabled = false;
    aiShowError('Terjadi kesalahan koneksi ke server AI.');
  }
}

function renderKuis(soalList) {
  const container = document.getElementById('ai-kuis-list');
  container.innerHTML = '';

  soalList.forEach((soal, idx) => {
    const wrap = document.createElement('div');
    wrap.className = 'mb-4 pb-3 border-bottom';
    wrap.dataset.jawabanBenar = soal.jawaban_benar;
    wrap.dataset.penjelasan = soal.penjelasan || '';

    const pertanyaan = document.createElement('p');
    pertanyaan.className = 'fw-semibold small mb-2';
    pertanyaan.textContent = `${idx + 1}. ${soal.pertanyaan}`;
    wrap.appendChild(pertanyaan);

    Object.entries(soal.pilihan || {}).forEach(([huruf, teks]) => {
      const id = `kuis-${idx}-${huruf}`;
      const div = document.createElement('div');
      div.className = 'form-check';
      div.innerHTML = `
        <input class="form-check-input" type="radio" name="kuis-${idx}" id="${id}" value="${huruf}">
        <label class="form-check-label small" for="${id}">${huruf}. ${teks}</label>
      `;
      wrap.appendChild(div);
    });

    const feedback = document.createElement('div');
    feedback.className = 'small mt-2 fw-semibold d-none';
    feedback.id = `kuis-feedback-${idx}`;
    wrap.appendChild(feedback);

    wrap.querySelectorAll('input[type=radio]').forEach(input => {
      input.addEventListener('change', () => cekJawabanKuis(idx, wrap));
    });

    container.appendChild(wrap);
  });

  const btnSelesai = document.createElement('button');
  btnSelesai.className = 'btn btn-primary btn-sm mt-2';
  btnSelesai.textContent = 'Lihat Skor';
  btnSelesai.onclick = () => hitungSkorKuis(soalList.length);
  container.appendChild(btnSelesai);
}

function cekJawabanKuis(idx, wrap) {
  const dipilih = wrap.querySelector('input[type=radio]:checked');
  if (!dipilih) return;
  const benar = wrap.dataset.jawabanBenar;
  const feedback = document.getElementById(`kuis-feedback-${idx}`);
  feedback.classList.remove('d-none');

  if (dipilih.value === benar) {
    feedback.className = 'small mt-2 fw-semibold text-success';
    feedback.textContent = `✓ Benar! ${wrap.dataset.penjelasan}`;
  } else {
    feedback.className = 'small mt-2 fw-semibold text-danger';
    feedback.textContent = `✗ Kurang tepat. Jawaban benar: ${benar}. ${wrap.dataset.penjelasan}`;
  }
}

function hitungSkorKuis(total) {
  let benar = 0;
  document.querySelectorAll('#ai-kuis-list > div[data-jawaban-benar]').forEach(wrap => {
    const dipilih = wrap.querySelector('input[type=radio]:checked');
    if (dipilih && dipilih.value === wrap.dataset.jawabanBenar) benar++;
  });

  const hasilBox = document.getElementById('ai-kuis-hasil');
  hasilBox.classList.remove('d-none');
  hasilBox.textContent = `Skor kamu: ${benar} dari ${total} soal benar.`;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>