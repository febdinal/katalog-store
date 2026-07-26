<?php
// public/admin/products/edit.php

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/database.php';
require_once __DIR__ . '/../../../app/auth.php';
require_once __DIR__ . '/../../../app/csrf.php';
require_once __DIR__ . '/../../../app/helpers.php';
require_once __DIR__ . '/../../../app/validation.php';

requireAdmin();
$db = getDb();

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) {
    redirect('/admin/products/');
}

$stmt = $db->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    setFlash('error', 'Produk tidak ditemukan.');
    redirect('/admin/products/');
}

$categories = $db->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

$errors = [];
$name = $product['name'];
$brand = $product['brand'];
$categoryId = $product['category_id'];
$price = $product['price'];
$originalPrice = $product['original_price'];
$quantity = $product['quantity'];
$isActive = (int)$product['is_active'];
$currentImagePath = $product['image_path'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $errors['csrf'] = 'Token CSRF tidak valid.';
    } else {
        $name = $_POST['name'] ?? '';
        $brand = $_POST['brand'] ?? '';
        $categoryId = $_POST['category_id'] ?? '';
        $price = $_POST['price'] ?? '';
        $originalPrice = $_POST['original_price'] ?? '';
        $quantity = $_POST['quantity'] ?? '';
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        $validation = validateProduct($_POST);

        if (!$validation['valid']) {
            $errors = $validation['errors'];
        } else {
            // Handle optional image replacement
            $uploadResult = handleProductImageUpload($_FILES['image'] ?? [], $currentImagePath);
            if (!$uploadResult['success']) {
                $errors['image'] = $uploadResult['error'];
            } else {
                $imagePath = $uploadResult['path'];

                $updateStmt = $db->prepare("
                    UPDATE products 
                    SET category_id = :category_id,
                        name = :name,
                        brand = :brand,
                        price = :price,
                        original_price = :original_price,
                        quantity = :quantity,
                        image_path = :image_path,
                        is_active = :is_active,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id
                ");

                $updateStmt->execute([
                    ':category_id' => $validation['data']['category_id'],
                    ':name' => $validation['data']['name'],
                    ':brand' => $validation['data']['brand'],
                    ':price' => $validation['data']['price'],
                    ':original_price' => $validation['data']['original_price'],
                    ':quantity' => $validation['data']['quantity'],
                    ':image_path' => $imagePath,
                    ':is_active' => $validation['data']['is_active'],
                    ':id' => $id
                ]);

                setFlash('success', 'Produk berhasil diperbarui.');
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
  <title>Edit Produk - <?= SITE_NAME ?></title>
  <link rel="icon" type="image/png" href="/assets/image/favicon.png?v=<?= file_exists(PUBLIC_DIR . '/assets/image/favicon.png') ? filemtime(PUBLIC_DIR . '/assets/image/favicon.png') : time() ?>">
  <link rel="stylesheet" href="/assets/css/style.css">
  <script src="/assets/js/app.js" defer></script>
  <script src="/assets/js/admin.js" defer></script>
</head>
<body>

<?php renderAdminHeader('products'); ?>

  <main class="main-wrapper" style="max-width: 720px;">
    <div style="margin-bottom: 1rem;">
      <a href="/admin/products/" style="font-size: 0.85rem; color: var(--text-muted);">&larr; Kembali ke Daftar Produk</a>
      <h1 style="font-size: 1.35rem; font-weight: 800; margin-top: 0.25rem;">Edit Produk #<?= $product['id'] ?></h1>
    </div>

    <?php if (isset($errors['csrf'])): ?>
      <div class="alert alert-error"><?= sanitize($errors['csrf']) ?></div>
    <?php endif; ?>

    <div class="card-panel">
      <form method="POST" action="/admin/products/edit.php?id=<?= $product['id'] ?>" enctype="multipart/form-data">
        <?= getCsrfInput() ?>

        <div class="form-group">
          <label for="name" class="form-label">Nama Produk *</label>
          <input type="text" name="name" id="name" class="form-control" value="<?= sanitize($name) ?>" required>
          <?php if (isset($errors['name'])): ?>
            <div class="form-error"><?= sanitize($errors['name']) ?></div>
          <?php endif; ?>
        </div>

        <div class="filter-grid" style="margin-bottom: 0;">
          <div class="form-group">
            <label for="brand" class="form-label">Merek *</label>
            <input type="text" name="brand" id="brand" class="form-control" value="<?= sanitize($brand) ?>" required>
            <?php if (isset($errors['brand'])): ?>
              <div class="form-error"><?= sanitize($errors['brand']) ?></div>
            <?php endif; ?>
          </div>

          <div class="form-group">
            <label for="category_id" class="form-label">Kategori *</label>
            <select name="category_id" id="category_id" class="form-control" required>
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
            <label for="price" class="form-label">Harga Jual (Rupiah) *</label>
            <input type="number" step="1000" min="0" name="price" id="price" class="form-control" value="<?= sanitize((string)$price) ?>" required>
            <?php if (isset($errors['price'])): ?>
              <div class="form-error"><?= sanitize($errors['price']) ?></div>
            <?php endif; ?>
          </div>

          <div class="form-group">
            <label for="original_price" class="form-label">Harga Coret / Original (Optional)</label>
            <input type="number" step="1000" min="0" name="original_price" id="original_price" class="form-control" value="<?= sanitize((string)$originalPrice) ?>" placeholder="Contoh: 450000">
            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">
              Isi jika produk diskon (akan ditampilkan dicoret).
            </div>
            <?php if (isset($errors['original_price'])): ?>
              <div class="form-error"><?= sanitize($errors['original_price']) ?></div>
            <?php endif; ?>
          </div>
        </div>

        <div class="filter-grid" style="margin-bottom: 0; margin-top: 0.5rem;">
          <div class="form-group">
            <label for="quantity" class="form-label">Quantity (Stok) *</label>
            <input type="number" min="0" name="quantity" id="quantity" class="form-control" value="<?= sanitize((string)$quantity) ?>" required>
            <?php if (isset($errors['quantity'])): ?>
              <div class="form-error"><?= sanitize($errors['quantity']) ?></div>
            <?php endif; ?>
          </div>
        </div>

        <div class="form-group" style="margin-top: 1rem;">
          <label for="image" class="form-label">Ganti Foto Produk (Kosongkan jika tidak ingin mengubah)</label>
          <input type="file" name="image" id="image" class="form-control" accept="image/jpeg,image/png,image/webp">
          <?php if (isset($errors['image'])): ?>
            <div class="form-error"><?= sanitize($errors['image']) ?></div>
          <?php endif; ?>

          <div id="image-preview-container" style="margin-top: 0.75rem;">
            <?php if ($currentImagePath): ?>
              <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.25rem;">Foto saat ini:</div>
              <img src="<?= sanitize(getProductImageUrl($currentImagePath)) ?>" alt="Current Image" style="max-height: 140px; border-radius: 8px; border: 1px solid #E2E8F0; object-fit: cover;">
            <?php endif; ?>
          </div>
        </div>

        <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem; margin-top: 1.25rem;">
          <input type="checkbox" name="is_active" id="is_active" value="1" <?= $isActive ? 'checked' : '' ?> style="width: 18px; height: 18px;">
          <label for="is_active" class="form-label" style="margin-bottom: 0; cursor: pointer;">Tampilkan di Katalog Publik</label>
        </div>

        <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
          <button type="submit" class="btn-primary">Perbarui Produk</button>
          <a href="/admin/products/" class="btn-secondary">Batal</a>
        </div>
      </form>
    </div>
  </main>

</body>
</html>
