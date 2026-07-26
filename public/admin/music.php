<?php
// public/admin/music.php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/csrf.php';
require_once __DIR__ . '/../../app/helpers.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        setFlash('error', 'Gagal menyimpan: Sesi atau token keamanan (CSRF) tidak valid.');
    } else {
        $musicUrl = trim($_POST['bg_music_url'] ?? '');
        $musicEnabled = isset($_POST['bg_music_enabled']) ? '1' : '0';
        $musicAutoplay = isset($_POST['bg_music_autoplay']) ? '1' : '0';

        if (!empty($musicUrl) && !filter_var($musicUrl, FILTER_VALIDATE_URL)) {
            setFlash('error', 'URL Musik tidak valid. Pastikan format URL diawali dengan http:// atau https://');
        } else {
            setSetting('bg_music_url', $musicUrl);
            setSetting('bg_music_enabled', $musicEnabled);
            setSetting('bg_music_autoplay', $musicAutoplay);

            setFlash('success', 'Pengaturan background music berhasil diperbarui!');
        }
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

$flash = getFlash();
$musicUrl = getSetting('bg_music_url', 'https://cdn.pixabay.com/download/audio/2022/05/27/audio_1808fbf07a.mp3?filename=lofi-study-112191.mp3');
$musicEnabled = getSetting('bg_music_enabled', '1');
$musicAutoplay = getSetting('bg_music_autoplay', '1');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Musik Background - Admin</title>
    <link rel="icon" type="image/png" href="/assets/image/favicon.png?v=<?= file_exists(PUBLIC_DIR . '/assets/image/favicon.png') ? filemtime(PUBLIC_DIR . '/assets/image/favicon.png') : time() ?>">
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="/assets/js/app.js" defer></script>
    <script src="/assets/js/admin.js" defer></script>
</head>
<body>
<?php renderAdminHeader('music'); ?>

    <main class="main-wrapper" style="max-width:800px; margin:2rem auto; padding:1rem;">
        <?php if ($flash): ?>
            <div class="alert alert-<?= sanitize($flash['type']) ?>" role="alert">
                <?= sanitize($flash['message']) ?>
            </div>
        <?php endif; ?>

        <div class="glass-upload-card">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.5); padding-bottom: 0.75rem;">
                <div>
                    <h1 style="font-size:1.35rem; font-weight:800; color: var(--text-main);">Pengaturan Background Music Website</h1>
                    <p style="font-size:0.85rem; color: var(--text-muted);">Atur file audio MP3 dari cloud link untuk diputar secara otomatis / manual di halaman website</p>
                </div>
                <span class="product-stock-badge badge-stock-available" style="position: static; font-size: 0.75rem;">MP3 Cloud Link</span>
            </div>

            <form method="post" id="music-setting-form">
                <?= getCsrfInput() ?>

                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label class="form-label" for="bg_music_url" style="font-weight: 700; color: var(--text-main);">
                        Link Direct File Audio MP3 (Cloud Link)
                    </label>
                    <input type="url" 
                           name="bg_music_url" 
                           id="bg_music_url" 
                           class="form-control" 
                           placeholder="https://example.com/audio/music.mp3" 
                           value="<?= sanitize($musicUrl) ?>" 
                           required />
                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.35rem;">
                        💡 Masukkan direct link file <code>.mp3</code> dari cloud/CDN (misal Google Drive Direct Link, Pixabay Audio, Dropbox Direct, AWS S3, dll).
                    </p>
                </div>

                <!-- Preview Player -->
                <div style="margin-bottom: 1.5rem; background: rgba(255,255,255,0.6); padding: 1rem; border-radius: var(--radius-md); border: 1px solid rgba(255,255,255,0.7);">
                    <p style="font-size: 0.85rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.35rem;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--accent-primary);">
                            <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                            <path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path>
                        </svg>
                        Uji Coba Audio (Live Preview):
                    </p>
                    <audio id="audio-preview" controls style="width: 100%; height: 40px; border-radius: 8px;">
                        <source id="audio-source" src="<?= sanitize($musicUrl) ?>" type="audio/mpeg">
                        Browser Anda tidak mendukung elemen pemutar audio.
                    </audio>
                </div>

                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label style="display: flex; align-items: center; gap: 0.6rem; cursor: pointer; font-size: 0.9rem; font-weight: 600; color: var(--text-main);">
                        <input type="checkbox" name="bg_music_enabled" value="1" <?= $musicEnabled === '1' ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: var(--accent-primary);">
                        Aktifkan Fitur Background Music pada Website
                    </label>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label style="display: flex; align-items: center; gap: 0.6rem; cursor: pointer; font-size: 0.9rem; font-weight: 600; color: var(--text-main);">
                        <input type="checkbox" name="bg_music_autoplay" value="1" <?= $musicAutoplay === '1' ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: var(--accent-primary);">
                        Putar Otomatis saat Pengunjung Pertama Kali Berinteraksi (Autoplay)
                    </label>
                </div>

                <div style="display: flex; gap: 0.75rem; justify-content: flex-end; align-items: center;">
                    <a href="/admin/index.php" class="btn-secondary">Batal</a>
                    <button type="submit" class="btn-primary" id="btn-submit-music">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        Simpan Pengaturan Musik
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const musicInput = document.getElementById('bg_music_url');
        const audioPreview = document.getElementById('audio-preview');

        if (musicInput && audioPreview) {
            musicInput.addEventListener('change', function() {
                const url = this.value.trim();
                if (url) {
                    audioPreview.src = url;
                    audioPreview.load();
                }
            });
        }
    });
    </script>
</body>
</html>
