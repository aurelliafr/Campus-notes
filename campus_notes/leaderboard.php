<?php
$page_title = 'Leaderboard';
require_once __DIR__ . '/includes/header.php';
requireLogin();

$db = db();

// Ambil 50 mahasiswa dengan poin tertinggi, plus hitung total catatan mereka
$semuaUser = $db->query("
    SELECT
        nama, nim, badge, poin_reputasi,
        (SELECT COUNT(*) FROM catatan WHERE id_user = u.id_user) AS total_catatan
    FROM users u
    WHERE role = 'mahasiswa'
    ORDER BY poin_reputasi DESC
    LIMIT 50
")->fetch_all(MYSQLI_ASSOC);

// Cari posisi (rank) user yang sedang login
$rankSaya = null;
foreach ($semuaUser as $index => $u) {
    if ($u['nim'] === $_SESSION['user']['nim']) {
        $rankSaya = $index + 1;
        break;
    }
}
?>

<div class="app-wrapper">
  <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

  <div class="app-content">
      <h5 class="fw-bold mb-4"><i class="bi bi-trophy me-2 text-warning"></i>Leaderboard</h5>

      <!-- Info posisi user saat ini -->
      <?php if ($rankSaya): ?>
        <div class="alert border-0 mb-4 text-white fw-semibold"
             style="background:linear-gradient(90deg,#2563eb,#06b6d4)">
          🏅 Kamu berada di posisi <strong>#<?= $rankSaya ?></strong> dari <?= count($semuaUser) ?> mahasiswa!
        </div>
      <?php endif; ?>

      <!-- Tabel peringkat -->
      <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <table class="table table-hover align-middle mb-0">
          <thead style="background:linear-gradient(90deg,#2563eb,#06b6d4)">
            <tr class="text-white">
              <th>Rank</th>
              <th>Nama</th>
              <th>Badge</th>
              <th>Catatan</th>
              <th>Poin</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($semuaUser as $index => $u): ?>
              <?php
                $rank   = $index + 1;
                $adalah = ($u['nim'] === $_SESSION['user']['nim']); // apakah ini user kita?
              ?>
              <tr class="<?= $adalah ? 'fw-semibold' : '' ?>"
                  style="<?= $adalah ? 'background:#eff6ff' : '' ?>">

                <!-- Ikon medali untuk top 3, nomor biasa untuk selebihnya -->
                <td class="text-center fw-bold">
                  <?= $rank === 1 ? '🥇' : ($rank === 2 ? '🥈' : ($rank === 3 ? '🥉' : "#$rank")) ?>
                </td>

                <td>
                  <?= htmlspecialchars($u['nama']) ?>
                  <?php if ($adalah): ?>
                    <span class="badge ms-1 text-white" style="background:#2563eb">Kamu</span>
                  <?php endif; ?>
                </td>

                <td>
                  <span class="badge bg-warning text-dark">
                    <?= badgeIcon($u['badge']) . ' ' . $u['badge'] ?>
                  </span>
                </td>

                <td><?= $u['total_catatan'] ?></td>

                <td class="fw-bold" style="color:#2563eb"><?= $u['poin_reputasi'] ?> pts</td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
