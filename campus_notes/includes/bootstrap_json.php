<?php
// ===================================================
// Bootstrap KHUSUS untuk handler yang mengembalikan JSON
// (bukan halaman HTML). Berbeda dari includes/header.php
// yang juga mencetak <!DOCTYPE html> dkk — kalau dipakai
// di handler JSON, HTML itu akan ikut tercetak di depan
// JSON dan membuat response gagal di-parse oleh JS.
// ===================================================

if (session_status() === PHP_SESSION_NONE) session_start();

$configPath = __DIR__ . '/../config/database.php';
if (file_exists($configPath) && !defined('DB_HOST')) {
    require_once $configPath;
}
