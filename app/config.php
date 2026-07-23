<?php
// app/config.php

define('ROOT_DIR', dirname(__DIR__));
define('APP_DIR', __DIR__);
define('PUBLIC_DIR', ROOT_DIR . '/public');
define('UPLOADS_DIR', PUBLIC_DIR . '/uploads/products');

define('SITE_NAME', 'Katalog Produk');
define('ITEMS_PER_PAGE', 12);

// Stock threshold definitions
define('STOCK_THRESHOLD_LOW', 5);

// Allowed Upload MIME types & extensions
define('ALLOWED_MIME_TYPES', [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp'
]);
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // 2 MB

// ─── Database Configuration ──────────────────────────────────────────────────
// Load local config (not committed to git)
$_localConfigPath = APP_DIR . '/config.local.php';

if (!file_exists($_localConfigPath)) {
    // Safe error message — no paths, passwords, or internal details exposed
    http_response_code(503);
    die(
        '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8">' .
        '<title>Konfigurasi Tidak Ditemukan</title>' .
        '<style>body{font-family:sans-serif;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0;background:#f8fafc;}' .
        '.box{background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:2rem;max-width:480px;text-align:center;}' .
        'h1{font-size:1.25rem;color:#dc2626;margin-bottom:0.75rem;}p{color:#64748b;font-size:0.9rem;line-height:1.6;}</style></head>' .
        '<body><div class="box"><h1>Konfigurasi Database Tidak Ditemukan</h1>' .
        '<p>File konfigurasi database belum tersedia. Salin <code>app/config.example.php</code> ke <code>app/config.local.php</code> dan isi dengan kredensial database Anda.</p>' .
        '</div></body></html>'
    );
}

$_dbConfig = require $_localConfigPath;

define('DB_HOST',    $_dbConfig['database']['host']     ?? '127.0.0.1');
define('DB_PORT',    (int)($_dbConfig['database']['port'] ?? 3306));
define('DB_NAME',    $_dbConfig['database']['name']     ?? 'katalog_store');
define('DB_USER',    $_dbConfig['database']['username'] ?? 'root');
define('DB_PASS',    $_dbConfig['database']['password'] ?? '');
define('DB_CHARSET', $_dbConfig['database']['charset']  ?? 'utf8mb4');

unset($_localConfigPath, $_dbConfig);
