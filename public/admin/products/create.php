<?php
// public/admin/products/create.php

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/database.php';
require_once __DIR__ . '/../../../app/auth.php';
require_once __DIR__ . '/../../../app/csrf.php';
require_once __DIR__ . '/../../../app/helpers.php';
require_once __DIR__ . '/../../../app/validation.php';

requireAdmin();
$db = getDb();

$categories = $db->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

$errors = [];
$name = '';
$brand = '';
$categoryId = '';
$price = '';
$quantity = '';
$isActive = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $errors['csrf'] = 'Token CSRF tidak valid. Silakan coba lagi.';
    } else {
        $name = $_POST['name'] ?? '';
        $brand = $_POST['brand'] ?? '';
        $categoryId = $_POST['category_id'] ?? '';
        $price = $_POST['price'] ?? '';
        $quantity = $_POST['quantity'] ?? '';
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        $validation = validateProduct($_POST);

        if (!$validation['valid']) {
            $errors = $validation['errors'];
        } else {
            // Handle image upload
            $uploadResult = handleProductImageUpload($_FILES['image'] ?? []);
            if (!$uploadResult['success']) {
                $errors['image'] = $uploadResult['error'];
            } else {
                $imagePath = $uploadResult['path'];

                $stmt = $db->prepare("
                    INSERT INTO products (category_id, name, brand, price, quantity, image_path, is_active)
                    VALUES (:category_id, :name, :brand, :price, :quantity, :image_path, :is_active)
                ");

                $stmt->execute([
                    ':category_id' => $validation['data']['category_id'],
                    ':name' => $validation['data']['name'],
                    ':brand' => $validation['data']['brand'],
                    ':price' => $validation['data']['price'],
                    ':quantity' => $validation['data']['quantity'],
                    ':image_path' => $imagePath,
                    ':is_active' => $validation['data']['is_active']
                ]);

                setFlash('success', 'Produk baru berhasil ditambahkan.');
                redirect('/admin/products/');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tambah Produk - <?= SITE_NAME ?></title>
  <link rel="stylesheet" href="/assets/css/style.css">
  <script src="/assets/js/app.js" defer></script>
</head>
<body>

  <header class="site-header admin-header">
    <div class="header-container">
      <a href="/admin/index.php" class="brand-logo">
        <span>Admin Dashboard</span>
      </a>
      <nav class="admin-nav">
        <a href="/admin/index.php" class="admin-nav-item">Overview</a>
        <a href="/admin/products/" class="admin-nav-item active">Produk</a>
        <a href="/admin/categories/" class="admin-nav-item">Kategori</a>
        <a href="/admin/logout.php" class="admin-nav-item" style="color: #FCA5A5;">Keluar</a>
      </nav>
    </div>
  </header>

  <main class="main-wrapper" style="max-width: 720px;">
    <div style="margin-bottom: 1rem;">
      <a href="/admin/products/" style="font-size: 0.85rem; color: var(--text-muted);">&larr; Kembali ke Daftar Produk</a>
      <h1 style="font-size: 1.35rem; font-weight: 800; margin-top: 0.25rem;">Tambah Produk Baru</h1>
    </div>

    <?php if (isset($errors['csrf'])): ?>
      <div class="alert alert-error"><?= sanitize($errors['csrf']) ?></div>
    <?php endif; ?>

    <div class="card-panel">
      <form method="POST" action="/admin/products/create.php" enctype="multipart/form-data">
        <?= getCsrfInput() ?>

        <div class="form-group">
          <label for="name" class="form-label">Nama Produk * (Maks 150 Karakter)</label>
          <input type="text" name="name" id="name" class="form-control" value="<?= sanitize($name) ?>" placeholder="Contoh: Earphone Wireless TWS Pro" required>
          <?php if (isset($errors['name'])): ?>
            <div class="form-error"><?= sanitize($errors['name']) ?></div>
          <?php endif; ?>
        </div>

        <div class="filter-grid" style="margin-bottom: 0;">
          <div class="form-group">
            <label for="brand" class="form-label">Merek * (Maks 100 Karakter)</label>
            <input type="text" name="brand" id="brand" class="form-control" value="<?= sanitize($brand) ?>" placeholder="Contoh: Anker" required>
            <?php if (isset($errors['brand'])): ?>
              <div class="form-error"><?= sanitize($errors['brand']) ?></div>
            <?php endif; ?>
          </div>

          <div class="form-group">
            <label for="category_id" class="form-label">Kategori *</label>
            <select name="category_id" id="category_id" class="form-control" required>
              <option value="">-- Pilih Kategori --</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= (string)$categoryId === (string)$cat['id'] ? 'selected' : '' ?>>
                  <?= sanitize($cat['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php if (isset($errors['category_id'])): ?>
              <div class="form-error"><?= sanitize($errors['category_id']) ?></div>
            <?php endif; ?>
          </div>
        </div>

        <div class="filter-grid" style="margin-bottom: 0;">
          <div class="form-group">
            <label for="price" class="form-label">Harga (Rupiah) *</label>
            <input type="number" step="1000" min="0" name="price" id="price" class="form-control" value="<?= sanitize($price) ?>" placeholder="350000" required>
            <?php if (isset($errors['price'])): ?>
              <div class="form-error"><?= sanitize($errors['price']) ?></div>
            <?php endif; ?>
          </div>

          <div class="form-group">
            <label for="quantity" class="form-label">Quantity (Stok) *</label>
            <input type="number" min="0" name="quantity" id="quantity" class="form-control" value="<?= sanitize($quantity) ?>" placeholder="10" required>
            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">
              0 = Habis, 1-5 = Stok Menipis, >5 = Tersedia
            </div>
            <?php if (isset($errors['quantity'])): ?>
              <div class="form-error"><?= sanitize($errors['quantity']) ?></div>
            <?php endif; ?>
          </div>
        </div>

        <div class="form-group" style="margin-top: 1rem;">
          <label for="image" class="form-label">Foto Produk (JPG, PNG, WebP - Maks 2MB)</label>
          <input type="file" name="image" id="image" class="form-control" accept="image/jpeg,image/png,image/webp">
          <?php if (isset($errors['image'])): ?>
            <div class="form-error"><?= sanitize($errors['image']) ?></div>
          <?php endif; ?>
          <div id="image-preview-container" style="margin-top: 0.5rem;"></div>
        </div>

        <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem; margin-top: 1.25rem;">
          <input type="checkbox" name="is_active" id="is_active" value="1" <?= $isActive ? 'checked' : '' ?> style="width: 18px; height: 18px;">
          <label for="is_active" class="form-label" style="margin-bottom: 0; cursor: pointer;">Tampilkan di Katalog Publik</label>
        </div>

        <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
          <button type="submit" class="btn-primary">Simpan Produk</button>
          <a href="/admin/products/" class="btn-secondary">Batal</a>
        </div>
      </form>
    </div>
  </main>

</body>
</html>
