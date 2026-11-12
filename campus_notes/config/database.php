<?php
// Cegah file ini dimuat dua kali
if (defined('DB_HOST')) return;

// ============================================================
// KONFIGURASI DATABASE & APLIKASI
// Ubah nilai di bawah ini sesuai dengan setting server kamu
// ============================================================
define('DB_HOST', 'localhost');       // Alamat server database
define('DB_USER', 'root');            // Username database
define('DB_PASS', '');                // Password database
define('DB_NAME', 'campus_notes');    // Nama database

// URL dasar aplikasi — WAJIB diubah saat hosting!
// Contoh lokal  : 'http://localhost/campus_notes'
// Contoh hosting: 'https://namadomain.com'
define('BASE_URL', 'http://localhost/campus_notes');

// Folder penyimpanan file PDF yang diupload mahasiswa
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/notes/');


// ============================================================
// FUNGSI KONEKSI DATABASE
// Menggunakan singleton — koneksi hanya dibuat sekali
// ============================================================
function db(): mysqli {
    static $koneksi = null;

    if ($koneksi === null) {
        $koneksi = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        // Hentikan aplikasi kalau koneksi gagal
        if ($koneksi->connect_error) {
            die('Koneksi database gagal: ' . $koneksi->connect_error);
        }

        // Gunakan encoding UTF-8 agar karakter Indonesia tampil benar
        $koneksi->set_charset('utf8mb4');
    }

    return $koneksi;
}


// ============================================================
// FUNGSI BADGE
// ============================================================

// Tentukan badge berdasarkan jumlah poin
function getBadge(int $poin): string {
    if ($poin >= 300) return 'Master';
    if ($poin >= 150) return 'Ahli';
    if ($poin >= 50)  return 'Kontributor';
    return 'Pemula';
}

// Emoji ikon untuk setiap badge
function badgeIcon(string $badge): string {
    return match($badge) {
        'Master'      => '👑',
        'Ahli'        => '⭐',
        'Kontributor' => '📘',
        default       => '🌱',
    };
}


// ============================================================
// FUNGSI UTILITAS
// ============================================================

// Ubah ukuran file dari bytes ke format yang mudah dibaca
function formatSize(int $bytes): string {
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}

// Cek apakah user sudah login
function isLoggedIn(): bool {
    return isset($_SESSION['user']);
}

// Paksa user login — kalau belum, redirect ke halaman login
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

// Paksa user admin — kalau bukan admin, redirect ke beranda
function requireAdmin(): void {
    requireLogin();
    if ($_SESSION['user']['role'] !== 'admin') {
        header('Location: ' . BASE_URL);
        exit;
    }
}

// Tampilkan avatar user: foto jika ada, inisial nama jika tidak
function avatarHtml(string $nama, string $fotoProfil, int $ukuran = 40, string $fontSize = '14px'): string {
    $pakaiInisial = ($fotoProfil === 'default.png' || empty($fotoProfil));

    if (!$pakaiInisial) {
        // Tampilkan foto profil
        return '<img src="' . BASE_URL . '/assets/images/' . htmlspecialchars($fotoProfil) . '"
                     width="' . $ukuran . '" height="' . $ukuran . '"
                     style="width:' . $ukuran . 'px;height:' . $ukuran . 'px;object-fit:cover;border-radius:50%"
                     onerror="this.style.display=\'none\'">';
    }

    // Buat inisial dari nama (maks 2 huruf)
    $kata    = explode(' ', trim($nama));
    $inisial = strtoupper(substr($kata[0], 0, 1));
    if (count($kata) > 1) {
        $inisial .= strtoupper(substr(end($kata), 0, 1));
    }

    // Tampilkan kotak bulat dengan inisial
    return '<div style="
        width:' . $ukuran . 'px; height:' . $ukuran . 'px; border-radius:50%;
        background: linear-gradient(135deg, #2563eb, #06b6d4);
        color:#fff;
        display:flex; align-items:center; justify-content:center;
        font-size:' . $fontSize . '; font-weight:700;
        flex-shrink:0; user-select:none;
        margin: 0;
    ">' . htmlspecialchars($inisial) . '</div>';
}
