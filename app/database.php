<?php
// app/database.php

require_once __DIR__ . '/config.php';

function getDb(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // Safe error — do not expose DSN, credentials, or internal paths
            http_response_code(503);
            die(
                '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8">' .
                '<title>Koneksi Database Gagal</title>' .
                '<style>body{font-family:sans-serif;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0;background:#f8fafc;}' .
                '.box{background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:2rem;max-width:480px;text-align:center;}' .
                'h1{font-size:1.25rem;color:#dc2626;margin-bottom:0.75rem;}p{color:#64748b;font-size:0.9rem;line-height:1.6;}</style></head>' .
                '<body><div class="box"><h1>Koneksi Database Gagal</h1>' .
                '<p>Aplikasi tidak dapat terhubung ke database MySQL. Periksa konfigurasi di <code>app/config.local.php</code> dan pastikan server MySQL sedang berjalan.</p>' .
                '</div></body></html>'
            );
        }
    }
    return $pdo;
}
