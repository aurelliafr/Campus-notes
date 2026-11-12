<?php
if (defined('AI_API_KEY')) return;

// ============================================================
// KONFIGURASI AI (Groq API - GRATIS & CEPAT)
// ============================================================
// 1. Buka https://console.groq.com/keys
// 2. Login/daftar pakai akun Google
// 3. Klik "Create API Key", beri nama bebas
// 4. Copy key-nya (format: gsk_...), paste di bawah
//
// PENTING saat hosting: ganti isi string di bawah dengan key kamu
// Jangan upload file ini ke GitHub kalau sudah diisi!
// ============================================================

define('AI_API_KEY',      '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');
define('AI_MODEL_TEXT',   'llama-3.3-70b-versatile');
define('AI_MODEL_VISION', 'meta-llama/llama-4-scout-17b-16e-instruct');
define('AI_API_URL',      'https://api.groq.com/openai/v1/chat/completions');
define('AI_MAX_FILE_MB',  20);
