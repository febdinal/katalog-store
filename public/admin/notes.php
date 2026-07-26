<?php
// public/admin/notes.php

require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/database.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/csrf.php';
require_once __DIR__ . '/../../app/helpers.php';

requireAdmin();
$db = getDb();
$flash = getFlash();

$announcementEnabled = getSetting('announcement_modal_enabled', '1');
$announcementTitle = getSetting('announcement_modal_title', 'Catatan & Informasi Toko');
$announcementContent = getSetting('announcement_modal_content', "Selamat datang di Salma Store!\n\nKami menyediakan berbagai produk berkualitas dengan harga terbaik. Silakan pilih produk pilihan Anda dan klik tombol WhatsApp untuk memesan.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        setFlash('error', 'Token CSRF tidak valid. Silakan coba lagi.');
    } else {
        $announcementEnabled = isset($_POST['announcement_modal_enabled']) ? '1' : '0';
        $announcementTitle = trim($_POST['announcement_modal_title'] ?? '');
        $announcementContent = trim($_POST['announcement_modal_content'] ?? '');

        setSetting('announcement_modal_enabled', $announcementEnabled);
        setSetting('announcement_modal_title', $announcementTitle);
        setSetting('announcement_modal_content', $announcementContent);

        setFlash('success', 'Pengaturan Catatan Modal berhasil disimpan.');
        redirect('/admin/notes.php');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pengaturan Catatan Modal - <?= SITE_NAME ?></title>
  <link rel="icon" type="image/png" href="/assets/image/favicon.png?v=<?= file_exists(PUBLIC_DIR . '/assets/image/favicon.png') ? filemtime(PUBLIC_DIR . '/assets/image/favicon.png') : time() ?>">
  <link rel="stylesheet" href="/assets/css/style.css">
  <script src="/assets/js/app.js" defer></script>
  <script src="/assets/js/admin.js" defer></script>
</head>
<body>

<?php renderAdminHeader('notes'); ?>

  <main class="main-wrapper" style="max-width: 720px;">
    <?php if ($flash): ?>
      <div class="alert alert-<?= sanitize($flash['type']) ?>">
        <?= sanitize($flash['message']) ?>
      </div>
    <?php endif; ?>

    <div style="margin-bottom: 1.25rem;">
      <h1 style="font-size: 1.35rem; font-weight: 800;">Kelola Catatan Modal (Popup)</h1>
      <p style="font-size: 0.85rem; color: var(--text-muted);">Atur judul dan isi pesan modal glassmorphism yang tampil saat pengunjung pertama kali membuka toko.</p>
    </div>

    <div class="card-panel">
      <form method="POST" action="/admin/notes.php">
        <?= getCsrfInput() ?>

        <div class="form-group" style="margin-bottom: 1.25rem;">
          <label class="form-label" style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
            <input type="checkbox" name="announcement_modal_enabled" value="1" <?= $announcementEnabled === '1' ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: var(--accent-primary);">
            <span style="font-weight: 700;">Aktifkan Catatan Modal saat masuk website</span>
          </label>
        </div>

        <div class="form-group">
          <label for="announcement_modal_title" class="form-label">Judul Modal *</label>
          <input type="text" name="announcement_modal_title" id="announcement_modal_title" class="form-control" value="<?= sanitize($announcementTitle) ?>" placeholder="Contoh: Catatan & Informasi Toko" required>
        </div>

        <div class="form-group">
          <label for="announcement_modal_content" class="form-label">Isi Catatan / Pengumuman *</label>
          <textarea name="announcement_modal_content" id="announcement_modal_content" class="form-control" rows="6" placeholder="Tuliskan catatan toko atau promo terkini..." required><?= sanitize($announcementContent) ?></textarea>
          <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">
            Catatan ini akan ditampilkan dalam dialog modal dengan desain glassmorphic yang indah.
          </div>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1.5rem;">
          <button type="submit" class="btn-primary">Simpan Pengaturan</button>
        </div>
      </form>
    </div>
  </main>

</body>
</html>
