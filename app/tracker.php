<?php
// app/tracker.php
// Module pencatatan statistik kunjungan website (privacy-friendly)

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

function trackVisit(): void {
    try {
        // 1. Hanya catat request dengan method GET
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method !== 'GET') {
            return;
        }

        // 2. Abaikan request AJAX / Fetch internal
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return;
        }

        // 3. Abaikan halaman admin
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        if (strpos($requestUri, '/admin') === 0 || strpos($scriptName, '/admin') === 0) {
            return;
        }

        // 4. Abaikan file aset (CSS, JS, gambar, audio, font, favicon, json, xml)
        $path = parse_url($requestUri, PHP_URL_PATH) ?? '/';
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $ignoredExtensions = [
            'css', 'js', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico',
            'mp3', 'wav', 'ogg', 'woff', 'woff2', 'ttf', 'eot', 'json', 'xml', 'map'
        ];
        if (!empty($extension) && in_array($extension, $ignoredExtensions, true)) {
            return;
        }

        // 5. Abaikan bot dan crawler umum berdasarkan User-Agent
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (empty($userAgent) || preg_match('~(bot|crawl|spider|slurp|facebookexternalhit|bingbot|googlebot|yandex|baidu|duckduckbot|lighthouse|monitoring|curl|wget|python|php)~i', $userAgent)) {
            return;
        }

        // 6. Abaikan jika pengguna adalah admin yang sedang login
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!empty($_SESSION['admin_id'])) {
            return;
        }

        // 7. Pengenal Pengunjung Pertama (Cookie 1 Tahun)
        $cookieName = 'ks_v_id';
        $cookieVal = $_COOKIE[$cookieName] ?? null;

        if (empty($cookieVal) || strlen($cookieVal) < 16) {
            $rawId = bin2hex(random_bytes(32));
            $cookieVal = $rawId;
            $expires = time() + (365 * 86400); // 1 Tahun
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? 80) == 443);

            if (PHP_VERSION_ID >= 70300) {
                setcookie($cookieName, $rawId, [
                    'expires'  => $expires,
                    'path'     => '/',
                    'domain'   => '',
                    'secure'   => $isHttps,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
            } else {
                setcookie($cookieName, $rawId, $expires, '/; samesite=Lax', '', $isHttps, true);
            }
        }

        // 8. Hash SHA-256 dari pengenal pengunjung (Privacy-Friendly)
        if (!empty($cookieVal)) {
            $visitorHash = hash('sha256', $cookieVal);
        } else {
            $visitorHash = hash('sha256', session_id() ?: ('anon_' . ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0')));
        }

        // 9. Timezone Asia/Jakarta (WIB)
        $tz = new DateTimeZone('Asia/Jakarta');
        $now = new DateTime('now', $tz);
        $visitDate = $now->format('Y-m-d');
        $currentTimestamp = $now->format('Y-m-d H:i:s');

        $db = getDb();

        // Query pencatatan kunjungan (INSERT ON DUPLICATE KEY UPDATE)
        $sql = "INSERT INTO website_visits (visitor_hash, visit_date, page_views, first_visited_at, last_visited_at)
                VALUES (:vhash, :vdate, 1, :first_at, :last_at)
                ON DUPLICATE KEY UPDATE 
                    page_views = page_views + 1,
                    last_visited_at = VALUES(last_visited_at)";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':vhash'    => $visitorHash,
                ':vdate'    => $visitDate,
                ':first_at' => $currentTimestamp,
                ':last_at'  => $currentTimestamp
            ]);
        } catch (\PDOException $pe) {
            // Otomatis buat tabel jika belum tersedia
            $db->exec("CREATE TABLE IF NOT EXISTS website_visits (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                visitor_hash CHAR(64) NOT NULL,
                visit_date DATE NOT NULL,
                page_views INT UNSIGNED NOT NULL DEFAULT 1,
                first_visited_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                last_visited_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_visitor_date (visitor_hash, visit_date),
                KEY idx_visit_date (visit_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':vhash'    => $visitorHash,
                ':vdate'    => $visitDate,
                ':first_at' => $currentTimestamp,
                ':last_at'  => $currentTimestamp
            ]);
        }
    } catch (\Throwable $e) {
        // Catat error secara aman di log tanpa menggagalkan halaman publik
        error_log('Visit Tracker Failure: ' . $e->getMessage());
    }
}
