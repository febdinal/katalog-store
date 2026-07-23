<?php
// public/admin/categories/create.php

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/database.php';
require_once __DIR__ . '/../../../app/auth.php';
require_once __DIR__ . '/../../../app/csrf.php';
require_once __DIR__ . '/../../../app/helpers.php';
require_once __DIR__ . '/../../../app/validation.php';

requireAdmin();

$errors = [];
$name = '';
$slug = '';
$isActive = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $errors['csrf'] = 'Token CSRF tidak valid. Silakan coba lagi.';
    } else {
        $validation = validateCategory($_POST);
        $name = $_POST['name'] ?? '';
        $slug = $_POST['slug'] ?? '';
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($validation['valid']) {
            $db = getDb();
            $stmt = $db->prepare("INSERT INTO categories (name, slug, is_active) VALUES (:name, :slug, :is_active)");
            $stmt->execute([
                ':name' => $validation['data']['name'],
                ':slug' => $validation['data']['slug'],
                ':is_active' => $validation['data']['is_active']
            ]);

            setFlash('success', 'Kategori baru berhasil ditambahkan.');
            redirect('/admin/categories/');
        } else {
            $errors = $validation['errors'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tambah Kategori - <?= SITE_NAME ?></title>
  <link rel="stylesheet" href="/assets/css/style.css">
  <script src="/assets/js/app.js" defer></script>
  <script src="/assets/js/admin.js" defer></script>
</head>
<body>

<?php renderAdminHeader('categories'); ?>

  <main class="main-wrapper" style="max-width: 600px;">
    <div style="margin-bottom: 1rem;">
      <a href="/admin/categories/" style="font-size: 0.85rem; color: var(--text-muted);">&larr; Kembali ke Daftar Kategori</a>
      <h1 style="font-size: 1.35rem; font-weight: 800; margin-top: 0.25rem;">Tambah Kategori Baru</h1>
    </div>

    <?php if (isset($errors['csrf'])): ?>
      <div class="alert alert-error"><?= sanitize($errors['csrf']) ?></div>
    <?php endif; ?>

    <div class="card-panel">
      <form method="POST" action="/admin/categories/create.php">
        <?= getCsrfInput() ?>

        <div class="form-group">
          <label for="name" class="form-label">Nama Kategori *</label>
          <input type="text" name="name" id="name" class="form-control" value="<?= sanitize($name) ?>" placeholder="Contoh: Elektronik" required>
          <?php if (isset($errors['name'])): ?>
            <div class="form-error"><?= sanitize($errors['name']) ?></div>
          <?php endif; ?>
        </div>

        <div class="form-group">
          <label for="slug" class="form-label">URL Slug (Opsional - otomatis dari nama)</label>
          <input type="text" name="slug" id="slug" class="form-control" value="<?= sanitize($slug) ?>" placeholder="contoh-elektronik">
          <?php if (isset($errors['slug'])): ?>
            <div class="form-error"><?= sanitize($errors['slug']) ?></div>
          <?php endif; ?>
        </div>

        <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem; margin-top: 1.25rem;">
          <input type="checkbox" name="is_active" id="is_active" value="1" <?= $isActive ? 'checked' : '' ?> style="width: 18px; height: 18px;">
          <label for="is_active" class="form-label" style="margin-bottom: 0; cursor: pointer;">Kategori Aktif (Dapat dilihat pengunjung)</label>
        </div>

        <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
          <button type="submit" class="btn-primary">Simpan Kategori</button>
          <a href="/admin/categories/" class="btn-secondary">Batal</a>
        </div>
      </form>
    </div>
  </main>

</body>
</html>
