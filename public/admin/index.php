<?php
// public/admin/index.php

require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/database.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/helpers.php';

requireAdmin();
$db = getDb();
$flash = getFlash();

// Metric counts
$totalProducts = (int)$db->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalCategories = (int)$db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$stockAvailable = (int)$db->query("SELECT COUNT(*) FROM products WHERE quantity > 0")->fetchColumn();
$stockOut = (int)$db->query("SELECT COUNT(*) FROM products WHERE quantity <= 0")->fetchColumn();

// Recent 5 products
$recentProducts = $db->query("
    SELECT p.id, p.name, p.brand, p.price, p.quantity, c.name as category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    ORDER BY p.id DESC
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin - <?= SITE_NAME ?></title>
  <link rel="stylesheet" href="/assets/css/style.css">
  <script src="/assets/js/app.js" defer></script>
    <script src="/assets/js/admin.js" defer></script>
</head>
<body>

<?php renderAdminHeader('overview'); ?>

  <main class="main-wrapper">
    <?php if ($flash): ?>
      <div class="alert alert-<?= sanitize($flash['type']) ?>">
        <?= sanitize($flash['message']) ?>
      </div>
    <?php endif; ?>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
      <div>
        <h1 style="font-size: 1.35rem; font-weight: 800;">Ringkasan Katalog</h1>
        <p style="font-size: 0.85rem; color: var(--text-muted);">Selamat datang, <?= sanitize($_SESSION['admin_name']) ?></p>
      </div>
      <div style="display: flex; gap: 0.5rem;">
        <a href="/admin/products/create.php" class="btn-primary">+ Tambah Produk</a>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="admin-stats-grid">
      <div class="stat-card">
        <div class="stat-label">Total Produk</div>
        <div class="stat-value"><?= $totalProducts ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Kategori</div>
        <div class="stat-value"><?= $totalCategories ?></div>
      </div>
      <div class="stat-card" style="border-left: 4px solid var(--stock-green-text);">
        <div class="stat-label" style="color: var(--stock-green-text);">Stok Tersedia (>0)</div>
        <div class="stat-value" style="color: var(--stock-green-text);"><?= $stockAvailable ?></div>
      </div>
      <div class="stat-card" style="border-left: 4px solid var(--stock-red-text);">
        <div class="stat-label" style="color: var(--stock-red-text);">Stok Habis (0)</div>
        <div class="stat-value" style="color: var(--stock-red-text);"><?= $stockOut ?></div>
      </div>
    </div>

    <!-- Recent Products Table -->
    <div class="card-panel">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2 style="font-size: 1rem; font-weight: 700;">Produk Terbaru Added</h2>
        <a href="/admin/products/" style="font-size: 0.85rem; color: var(--accent-primary); font-weight: 600;">Kelola Semua Produk &rarr;</a>
      </div>

      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nama Produk</th>
              <th>Merek</th>
              <th>Kategori</th>
              <th>Harga</th>
              <th>Stok</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentProducts as $prod): ?>
              <?php $stock = calculateStockStatus((int)$prod['quantity']); ?>
              <tr>
                <td>#<?= $prod['id'] ?></td>
                <td><strong><?= sanitize($prod['name']) ?></strong></td>
                <td><?= sanitize($prod['brand']) ?></td>
                <td><?= sanitize($prod['category_name']) ?></td>
                <td><?= formatRupiah($prod['price']) ?></td>
                <td><?= (int)$prod['quantity'] ?></td>
                <td>
                  <span class="product-stock-badge <?= $stock['class'] ?>" style="position: static;">
                    <?= sanitize($stock['label']) ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>

</body>
</html>
