<?php
// public/admin/products/index.php

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/database.php';
require_once __DIR__ . '/../../../app/auth.php';
require_once __DIR__ . '/../../../app/csrf.php';
require_once __DIR__ . '/../../../app/helpers.php';

requireAdmin();
$db = getDb();
$flash = getFlash();

// Search & Filter parameters
$search = trim($_GET['search'] ?? '');
$selectedCategory = filter_var($_GET['category'] ?? null, FILTER_VALIDATE_INT);
$stockStatus = trim($_GET['stock'] ?? '');

// Fetch categories for filter dropdown
$categories = $db->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();

$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(p.name LIKE :search OR p.brand LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if ($selectedCategory && $selectedCategory > 0) {
    $whereConditions[] = "p.category_id = :category_id";
    $params[':category_id'] = $selectedCategory;
}

if ($stockStatus === 'out') {
    $whereConditions[] = "p.quantity <= 0";
} elseif ($stockStatus === 'available') {
    $whereConditions[] = "p.quantity > 0";
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

$productsSql = "
    SELECT p.id, p.name, p.brand, p.price, p.quantity, p.image_path, p.is_active,
           c.name as category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    {$whereClause}
    ORDER BY p.id DESC
";

$stmt = $db->prepare($productsSql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Produk - <?= SITE_NAME ?></title>
  <link rel="stylesheet" href="/assets/css/style.css">
  <script src="/assets/js/app.js" defer></script>
<script src="/assets/js/admin.js" defer></script>
</head>
<body>

<?php renderAdminHeader('products'); ?>

  <main class="main-wrapper">
    <?php if ($flash): ?>
      <div class="alert alert-<?= sanitize($flash['type']) ?>">
        <?= sanitize($flash['message']) ?>
      </div>
    <?php endif; ?>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
      <div>
        <h1 style="font-size: 1.35rem; font-weight: 800;">Kelola Produk</h1>
        <p style="font-size: 0.85rem; color: var(--text-muted);">Kelola katalog, harga, foto, dan stok produk</p>
      </div>
      <a href="/admin/products/create.php" class="btn-primary" id="btn-add-product">+ Tambah Produk</a>
    </div>

    <!-- Filter & Search Form -->
    <div class="card-panel" style="margin-bottom: 1rem; padding: 1rem;">
      <form method="GET" action="/admin/products/" class="filter-grid" id="product-filter-form">
        <div>
          <label for="search" class="form-label">Cari Nama / Merek</label>
          <input type="text" name="search" id="search" class="form-control" value="<?= sanitize($search) ?>" placeholder="Kata kunci...">
        </div>

        <div>
          <label for="category" class="form-label">Kategori</label>
          <select name="category" id="category" class="form-control">
            <option value="">Semua Kategori</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= $selectedCategory === (int)$cat['id'] ? 'selected' : '' ?>>
                <?= sanitize($cat['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label for="stock" class="form-label">Kondisi Stok</label>
          <select name="stock" id="stock" class="form-control">
            <option value="">Semua Stok</option>
            <option value="available" <?= $stockStatus === 'available' ? 'selected' : '' ?>>Tersedia (>0)</option>
            <option value="out" <?= $stockStatus === 'out' ? 'selected' : '' ?>>Habis (0)</option>
          </select>
        </div>

        <div style="grid-column: 1 / -1; display: flex; gap: 0.5rem; justify-content: flex-end;">
          <button type="submit" class="btn-primary" style="min-height: 40px; padding: 0.375rem 1rem;">Filter Produk</button>
          <a href="/admin/products/" class="btn-secondary" style="min-height: 40px; padding: 0.375rem 1rem;">Reset Filter</a>
        </div>
      </form>
    </div>

    <!-- Product Table -->
    <div class="card-panel">
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Foto</th>
              <th>Nama & Merek</th>
              <th>Kategori</th>
              <th>Harga</th>
              <th>Stok</th>
              <th>Status Stok</th>
              <th>Visibilitas</th>
              <th style="text-align: right;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($products)): ?>
              <?php foreach ($products as $prod): ?>
                <?php 
                  $stock = calculateStockStatus((int)$prod['quantity']); 
                  $imgUrl = getProductImageUrl($prod['image_path']);
                ?>
                <tr>
                  <td style="width: 50px;">
                    <img src="<?= sanitize($imgUrl) ?>" alt="Thumb" style="width: 44px; height: 44px; border-radius: 6px; object-fit: cover; background: #F1F5F9;">
                  </td>
                  <td>
                    <strong><?= sanitize($prod['name']) ?></strong>
                    <div style="font-size: 0.75rem; color: var(--text-muted);"><?= sanitize($prod['brand']) ?></div>
                  </td>
                  <td><?= sanitize($prod['category_name']) ?></td>
                  <td><strong><?= formatRupiah($prod['price']) ?></strong></td>
                  <td><?= (int)$prod['quantity'] ?> unit</td>
                  <td>
                    <span class="product-stock-badge <?= $stock['class'] ?>" style="position: static;">
                      <?= sanitize($stock['label']) ?>
                    </span>
                  </td>
                  <td>
                    <?php if ($prod['is_active']): ?>
                      <span style="color: var(--stock-green-text); font-weight: 600; font-size: 0.8rem;">Tampil</span>
                    <?php else: ?>
                      <span style="color: var(--text-muted); font-size: 0.8rem;">Sembunyi</span>
                    <?php endif; ?>
                  </td>
                  <td style="text-align: right; white-space: nowrap;">
                    <a href="/admin/products/edit.php?id=<?= $prod['id'] ?>" class="btn-secondary" style="padding: 0.25rem 0.625rem; min-height: 36px; font-size: 0.8rem;">Edit</a>
                    
                    <form method="POST" action="/admin/products/delete.php" class="form-confirm-delete" data-confirm="Hapus produk '<?= sanitize($prod['name']) ?>'?" style="display: inline-block;">
                      <?= getCsrfInput() ?>
                      <input type="hidden" name="id" value="<?= $prod['id'] ?>">
                      <button type="submit" class="btn-danger" style="padding: 0.25rem 0.625rem; min-height: 36px; font-size: 0.8rem;">Hapus</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="8" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                  Tidak ada produk yang memenuhi kriteria pencarian/filter.
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>

</body>
</html>
