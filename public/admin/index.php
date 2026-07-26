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

// Website Visit Statistics (Timezone: Asia/Jakarta)
$tz = new DateTimeZone('Asia/Jakarta');
$todayDate = (new DateTime('now', $tz))->format('Y-m-d');

try {
    // Today's Visits & Today's Unique Visitors
    $todayStmt = $db->prepare("
        SELECT COALESCE(SUM(page_views), 0) as today_views, COUNT(*) as today_uniques 
        FROM website_visits 
        WHERE visit_date = :today
    ");
    $todayStmt->execute([':today' => $todayDate]);
    $todayStats = $todayStmt->fetch();
    $todayViews = (int)($todayStats['today_views'] ?? 0);
    $todayUniques = (int)($todayStats['today_uniques'] ?? 0);

    // Total Visits & Total Unique Visitors
    $totalViews = (int)$db->query("SELECT COALESCE(SUM(page_views), 0) FROM website_visits")->fetchColumn();
    $totalUniques = (int)$db->query("SELECT COUNT(DISTINCT visitor_hash) FROM website_visits")->fetchColumn();

    // Last 7 Days Visits Summary Table
    $recentVisitsStmt = $db->prepare("
        SELECT visit_date, SUM(page_views) as total_views, COUNT(*) as unique_visitors
        FROM website_visits
        WHERE visit_date >= DATE_SUB(:today, INTERVAL 6 DAY)
        GROUP BY visit_date
        ORDER BY visit_date DESC
    ");
    $recentVisitsStmt->execute([':today' => $todayDate]);
    $recentVisits = $recentVisitsStmt->fetchAll();
} catch (\PDOException $e) {
    $todayViews = 0;
    $todayUniques = 0;
    $totalViews = 0;
    $totalUniques = 0;
    $recentVisits = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin - <?= SITE_NAME ?></title>
  <link rel="icon" type="image/png" href="/assets/image/favicon.png?v=<?= file_exists(PUBLIC_DIR . '/assets/image/favicon.png') ? filemtime(PUBLIC_DIR . '/assets/image/favicon.png') : time() ?>">
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

    <!-- Product Stats Grid -->
    <div class="admin-stats-grid">
      <div class="stat-card">
        <div class="stat-label">Total Produk</div>
        <div class="stat-value"><?= number_format($totalProducts, 0, ',', '.') ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Kategori</div>
        <div class="stat-value"><?= number_format($totalCategories, 0, ',', '.') ?></div>
      </div>
      <div class="stat-card" style="border-left: 4px solid var(--stock-green-text);">
        <div class="stat-label" style="color: var(--stock-green-text);">Stok Tersedia (>0)</div>
        <div class="stat-value" style="color: var(--stock-green-text);"><?= number_format($stockAvailable, 0, ',', '.') ?></div>
      </div>
      <div class="stat-card" style="border-left: 4px solid var(--stock-red-text);">
        <div class="stat-label" style="color: var(--stock-red-text);">Stok Habis (0)</div>
        <div class="stat-value" style="color: var(--stock-red-text);"><?= number_format($stockOut, 0, ',', '.') ?></div>
      </div>
    </div>

    <!-- Visit Statistics Header & Grid -->
    <div style="margin-top: 2rem; margin-bottom: 1.25rem;">
      <h2 style="font-size: 1.2rem; font-weight: 800; color: var(--text-main);">Statistik Kunjungan Website</h2>
      <p style="font-size: 0.85rem; color: var(--text-muted);">Lalu lintas pengunjung terenkripsi &amp; real-time (Timezone: Asia/Jakarta)</p>
    </div>

    <!-- Visit Stats Grid -->
    <div class="admin-stats-grid" style="margin-bottom: 1.5rem;">
      <div class="stat-card">
        <div class="stat-label">Kunjungan Hari Ini</div>
        <div class="stat-value"><?= number_format($todayViews, 0, ',', '.') ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Pengunjung Unik Hari Ini</div>
        <div class="stat-value"><?= number_format($todayUniques, 0, ',', '.') ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Total Kunjungan</div>
        <div class="stat-value"><?= number_format($totalViews, 0, ',', '.') ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Total Pengunjung Unik</div>
        <div class="stat-value"><?= number_format($totalUniques, 0, ',', '.') ?></div>
      </div>
    </div>

    <!-- Last 7 Days Visits Summary Table -->
    <div class="card-panel" style="margin-bottom: 2rem;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h3 style="font-size: 1rem; font-weight: 700; color: var(--text-main);">Lalu Lintas 7 Hari Terakhir</h3>
        <span class="product-stock-badge badge-stock-available" style="position: static; font-size: 0.75rem;">Realtime (WIB)</span>
      </div>

      <?php if (!empty($recentVisits)): ?>
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>Total Kunjungan (Page Views)</th>
                <th>Pengunjung Unik</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentVisits as $visit): ?>
                <?php 
                  $dt = new DateTime($visit['visit_date'], $tz);
                  $formattedDate = $dt->format('d M Y');
                  $isToday = ($visit['visit_date'] === $todayDate);
                ?>
                <tr>
                  <td>
                    <strong><?= $formattedDate ?></strong>
                    <?php if ($isToday): ?>
                      <span class="product-stock-badge badge-stock-available" style="position: static; margin-left: 0.5rem; font-size: 0.7rem;">Hari Ini</span>
                    <?php endif; ?>
                  </td>
                  <td><?= number_format((int)$visit['total_views'], 0, ',', '.') ?> views</td>
                  <td><?= number_format((int)$visit['unique_visitors'], 0, ',', '.') ?> pengunjung</td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div style="text-align: center; padding: 2rem 1rem; color: var(--text-muted);">
          <p style="font-size: 0.9rem; font-weight: 600;">Belum Ada Data Kunjungan</p>
          <p style="font-size: 0.8rem; margin-top: 0.25rem;">Data statistik akan otomatis tercatat ketika ada pengunjung yang membuka katalog publik.</p>
        </div>
      <?php endif; ?>
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

