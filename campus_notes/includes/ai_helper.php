<?php
require_once __DIR__ . '/../config/ai_config.php';

// ============================================================
// HELPER: Tentukan apakah file ini bisa dianalisis AI
// ============================================================
function aiTipeDidukung(string $ekstensi): bool {
    $didukung = ['pdf','jpg','jpeg','png','webp','gif','doc','docx','ppt','pptx','xls','xlsx','txt'];
    return in_array(strtolower($ekstensi), $didukung);
}

function aiApakahGambar(string $ekstensi): bool {
    return in_array(strtolower($ekstensi), ['jpg','jpeg','png','webp','gif']);
}

// ============================================================
// HELPER: Kirim request ke Groq (text mode)
// ============================================================
function aiKirimTeks(string $systemPrompt, string $userContent, string $model = null): array {
    if (!defined('AI_API_KEY') || AI_API_KEY === 'GANTI_DENGAN_API_KEY_ANDA') {
        return ['ok' => false, 'data' => null, 'error' => 'API key AI belum dikonfigurasi. Hubungi admin web.'];
    }

    $payload = [
        'model'       => $model ?? AI_MODEL_TEXT,
        'temperature' => 0.3,
        'max_tokens'  => 2048,
        'messages'    => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user',   'content' => $userContent],
        ],
    ];

    return aiKirimRequest($payload);
}

// ============================================================
// HELPER: Kirim request ke Groq (vision mode, untuk gambar)
// ============================================================
function aiKirimGambar(string $systemPrompt, string $imagePath, string $promptTeks): array {
    if (!defined('AI_API_KEY') || AI_API_KEY === 'GANTI_DENGAN_API_KEY_ANDA') {
        return ['ok' => false, 'data' => null, 'error' => 'API key AI belum dikonfigurasi. Hubungi admin web.'];
    }

    $ekstensi  = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
    $mimeTypes = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif'];
    $mimeType  = $mimeTypes[$ekstensi] ?? 'image/jpeg';

    $imageData = base64_encode(file_get_contents($imagePath));

    $payload = [
        'model'       => AI_MODEL_VISION,
        'temperature' => 0.3,
        'max_tokens'  => 2048,
        'messages'    => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => [
                ['type' => 'text',       'text' => $promptTeks],
                ['type' => 'image_url',  'image_url' => ['url' => "data:{$mimeType};base64,{$imageData}"]],
            ]],
        ],
    ];

    return aiKirimRequest($payload);
}

// ============================================================
// HELPER: Eksekusi curl ke Groq API
// ============================================================
function aiKirimRequest(array $payload): array {
    $ch = curl_init(AI_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . AI_API_KEY,
        ],
        CURLOPT_TIMEOUT => 45,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        return ['ok' => false, 'data' => null, 'error' => 'Gagal menghubungi server AI: ' . $curlErr];
    }

    $body = json_decode($response, true);

    if ($httpCode !== 200) {
        $msg = $body['error']['message'] ?? 'HTTP ' . $httpCode;
        if ($httpCode === 429) $msg = 'Batas pemakaian gratis AI menit ini sudah tercapai. Coba lagi sebentar.';
        return ['ok' => false, 'data' => null, 'error' => 'API AI menolak permintaan: ' . $msg];
    }

    $teksJawaban = $body['choices'][0]['message']['content'] ?? '';
    if (!$teksJawaban) {
        return ['ok' => false, 'data' => null, 'error' => 'AI tidak mengembalikan jawaban.'];
    }

    // Bersihkan code fence kalau ada
    $bersih = trim(preg_replace('/^```(?:json)?|```$/im', '', $teksJawaban));
    $json   = json_decode(trim($bersih), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['ok' => false, 'data' => null, 'error' => 'Gagal membaca format jawaban AI.'];
    }

    return ['ok' => true, 'data' => $json, 'error' => null];
}

// ============================================================
// HELPER: Extract teks dari PDF (PHP native, tanpa tool luar)
// ============================================================
function aiExtractPdfText(string $pdfPath): array {
    $content = file_get_contents($pdfPath);
    $teks    = '';

    if (preg_match_all('/BT[\s\S]*?ET/m', $content, $btBlocks)) {
        foreach ($btBlocks[0] as $block) {
            if (preg_match_all('/\(([^)]*)\)\s*T[jJ]/', $block, $strings)) {
                foreach ($strings[1] as $str) {
                    $str   = str_replace(['\\n','\\r','\\t'], ["\n","\r","\t"], $str);
                    $str   = str_replace(['\\(','\\)','\\\\'], ['(',')',' \\'], $str);
                    $teks .= $str . ' ';
                }
            }
            if (preg_match_all('/\[([^\]]*)\]\s*TJ/', $block, $arrays)) {
                foreach ($arrays[1] as $arr) {
                    if (preg_match_all('/\(([^)]*)\)/', $arr, $arrStrings)) {
                        foreach ($arrStrings[1] as $str) $teks .= $str . ' ';
                    }
                }
            }
        }
    }

    // Fallback: extract dari stream
    if (strlen(trim($teks)) < 50 && preg_match_all('/stream([\s\S]*?)endstream/', $content, $streams)) {
        foreach ($streams[1] as $stream) {
            $decompressed = @gzuncompress(ltrim($stream));
            if ($decompressed !== false) $stream = $decompressed;
            preg_match_all('/[^\x00-\x08\x0b\x0c\x0e-\x1f\x7f-\xff]{4,}/', $stream, $printable);
            foreach ($printable[0] as $chunk) {
                if (preg_match('/^[\x21-\x7e]+$/', $chunk)) $teks .= $chunk . ' ';
            }
        }
    }

    $teks = trim(preg_replace('/\s+/', ' ', $teks));
    if (strlen($teks) < 20) {
        return ['ok' => false, 'teks' => null, 'error' => 'PDF tidak mengandung teks yang bisa dibaca (mungkin file scan/gambar).'];
    }

    return ['ok' => true, 'teks' => mb_substr($teks, 0, 12000), 'error' => null];
}

// ============================================================
// HELPER: Extract teks dari file .txt / .doc sederhana
// ============================================================
function aiExtractDocText(string $filePath, string $ekstensi): array {
    if ($ekstensi === 'txt') {
        $teks = file_get_contents($filePath);
        if (!$teks) return ['ok' => false, 'teks' => null, 'error' => 'Gagal membaca file teks.'];
        return ['ok' => true, 'teks' => mb_substr(trim($teks), 0, 12000), 'error' => null];
    }

    // Untuk docx/pptx/xlsx: itu sebetulnya file zip yang berisi XML
    if (in_array($ekstensi, ['docx', 'pptx', 'xlsx'])) {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            return ['ok' => false, 'teks' => null, 'error' => 'Gagal membuka file dokumen.'];
        }

        $teks = '';
        // docx: word/document.xml, pptx: ppt/slides/slide*.xml, xlsx: xl/sharedStrings.xml
        $xmlFiles = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match('/(document\.xml|slide\d*\.xml|sharedStrings\.xml)$/', $name)) {
                $xmlFiles[] = $name;
            }
        }

        foreach ($xmlFiles as $xmlFile) {
            $xml  = $zip->getFromName($xmlFile);
            $xml  = preg_replace('/<[^>]+>/', ' ', $xml); // strip XML tags
            $teks .= ' ' . html_entity_decode(strip_tags($xml));
        }
        $zip->close();

        $teks = trim(preg_replace('/\s+/', ' ', $teks));
        if (strlen($teks) < 10) {
            return ['ok' => false, 'teks' => null, 'error' => 'Dokumen tidak mengandung teks yang bisa dibaca.'];
        }
        return ['ok' => true, 'teks' => mb_substr($teks, 0, 12000), 'error' => null];
    }

    // doc/ppt/xls lama (binary) — coba extract string printable
    $raw  = file_get_contents($filePath);
    preg_match_all('/[a-zA-Z][a-zA-Z0-9\s,.\-:;!?()\'"]{10,}/', $raw, $matches);
    $teks = implode(' ', $matches[0]);
    $teks = trim(preg_replace('/\s+/', ' ', $teks));

    if (strlen($teks) < 20) {
        return ['ok' => false, 'teks' => null, 'error' => 'Format dokumen ini tidak bisa dibaca langsung. Coba convert ke PDF atau DOCX terlebih dahulu.'];
    }
    return ['ok' => true, 'teks' => mb_substr($teks, 0, 12000), 'error' => null];
}

// ============================================================
// FUNGSI UTAMA: Generate ringkasan
// ============================================================
function aiBuatRingkasan(string $pdfPath, string $ekstensi = 'pdf'): array {
    if (!file_exists($pdfPath)) {
        return ['ok' => false, 'data' => null, 'error' => 'File tidak ditemukan di server.'];
    }
    if (filesize($pdfPath) > AI_MAX_FILE_MB * 1024 * 1024) {
        return ['ok' => false, 'data' => null, 'error' => 'File terlalu besar (maks ' . AI_MAX_FILE_MB . ' MB).'];
    }

    $system = 'Kamu adalah asisten belajar untuk mahasiswa. Buatkan ringkasan dari konten berikut. '
        . 'Balas HANYA dengan JSON valid, format: {"ringkasan":"3-6 kalimat ringkasan dalam Bahasa Indonesia","poin_penting":["poin 1","poin 2","poin 3"]}';

    // Gambar: pakai vision
    if (aiApakahGambar($ekstensi)) {
        $hasil = aiKirimGambar(
            $system,
            $pdfPath,
            'Ini adalah gambar catatan kuliah. Bacakan dan buatkan ringkasan dari isi gambar ini.'
        );
    } else {
        // PDF atau doc: extract teks dulu
        $extract = ($ekstensi === 'pdf')
            ? aiExtractPdfText($pdfPath)
            : aiExtractDocText($pdfPath, $ekstensi);
        if (!$extract['ok']) return ['ok' => false, 'data' => null, 'error' => $extract['error']];
        $hasil = aiKirimTeks($system, 'Ini teks catatan kuliah:' . "\n\n" . $extract['teks']);
    }

    if (!$hasil['ok']) return $hasil;
    $data = $hasil['data'];
    if (!isset($data['ringkasan']) || !isset($data['poin_penting'])) {
        return ['ok' => false, 'data' => null, 'error' => 'Format ringkasan dari AI tidak lengkap.'];
    }
    return ['ok' => true, 'data' => $data, 'error' => null];
}

// ============================================================
// FUNGSI UTAMA: Generate kuis
// ============================================================
function aiBuatKuis(string $pdfPath, int $jumlahSoal = 5, string $ekstensi = 'pdf'): array {
    if (!file_exists($pdfPath)) {
        return ['ok' => false, 'data' => null, 'error' => 'File tidak ditemukan di server.'];
    }
    if (filesize($pdfPath) > AI_MAX_FILE_MB * 1024 * 1024) {
        return ['ok' => false, 'data' => null, 'error' => 'File terlalu besar (maks ' . AI_MAX_FILE_MB . ' MB).'];
    }

    $system = "Kamu adalah asisten belajar untuk mahasiswa. Buatkan {$jumlahSoal} soal kuis pilihan ganda "
        . "berdasarkan konten yang diberikan. Soal HARUS berdasarkan isi konten, bukan pengetahuan umum. "
        . 'Balas HANYA dengan JSON valid, format: {"soal":[{"pertanyaan":"...","pilihan":{"A":"...","B":"...","C":"...","D":"..."},"jawaban_benar":"A","penjelasan":"..."}]} '
        . "Buat tepat {$jumlahSoal} soal dalam Bahasa Indonesia.";

    if (aiApakahGambar($ekstensi)) {
        $hasil = aiKirimGambar(
            $system,
            $pdfPath,
            "Ini adalah gambar catatan kuliah. Buatkan {$jumlahSoal} soal kuis pilihan ganda berdasarkan isi gambar ini."
        );
    } else {
        $extract = ($ekstensi === 'pdf')
            ? aiExtractPdfText($pdfPath)
            : aiExtractDocText($pdfPath, $ekstensi);
        if (!$extract['ok']) return ['ok' => false, 'data' => null, 'error' => $extract['error']];
        $hasil = aiKirimTeks($system, 'Ini teks catatan kuliah:' . "\n\n" . $extract['teks']);
    }

    if (!$hasil['ok']) return $hasil;
    $data = $hasil['data'];
    if (!isset($data['soal']) || !is_array($data['soal']) || count($data['soal']) === 0) {
        return ['ok' => false, 'data' => null, 'error' => 'Format kuis dari AI tidak lengkap.'];
    }
    return ['ok' => true, 'data' => $data, 'error' => null];
}
