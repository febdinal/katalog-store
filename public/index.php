<?php
// public/index.php

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/database.php';
require_once __DIR__ . '/../app/helpers.php';

$db = getDb();

// Selected category filter
$selectedCategorySlug = trim($_GET['category'] ?? '');

// Pagination parameters
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT);
if ($page === false || $page < 1) {
    $page = 1;
}

// Fetch active categories
$catStmt = $db->query("SELECT id, name, slug FROM categories WHERE is_active = 1 ORDER BY name ASC");
$categories = $catStmt->fetchAll();

// Build products query
$whereClause = "WHERE p.is_active = 1 AND c.is_active = 1";
$params = [];

if (!empty($selectedCategorySlug)) {
    $whereClause .= " AND c.slug = :category_slug";
    $params[':category_slug'] = $selectedCategorySlug;
}

// Count total matching products
$countSql = "SELECT COUNT(*) FROM products p JOIN categories c ON p.category_id = c.id {$whereClause}";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalProducts = (int)$countStmt->fetchColumn();

$totalPages = (int)ceil($totalProducts / ITEMS_PER_PAGE);
if ($totalPages > 0 && $page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * ITEMS_PER_PAGE;

// Fetch products for current page
$productsSql = "
    SELECT p.id, p.name, p.brand, p.price, p.quantity, p.image_path, c.name as category_name, c.slug as category_slug
    FROM products p
    JOIN categories c ON p.category_id = c.id
    {$whereClause}
    ORDER BY p.id DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $db->prepare($productsSql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', ITEMS_PER_PAGE, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

// Find current category name
$currentCategoryName = 'Semua Produk';
if (!empty($selectedCategorySlug)) {
    foreach ($categories as $cat) {
        if ($cat['slug'] === $selectedCategorySlug) {
            $currentCategoryName = $cat['name'];
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= sanitize($currentCategoryName) ?> - <?= SITE_NAME ?></title>
  <meta name="description" content="Katalog produk retail pilihan dengan harga terbaik, stok realtime, dan transaksi cepat.">
  <link rel="stylesheet" href="/assets/css/style.css">
  <script src="/assets/js/app.js" defer></script>
</head>
<body>

  <!-- Site Header -->
  <header class="site-header">
    <div class="header-container">
      <a href="/" class="brand-logo" id="header-brand-link">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
          <line x1="3" y1="6" x2="21" y2="6"></line>
          <path d="M16 10a4 4 0 0 1-8 0"></path>
        </svg>
        <span>Salma Store</span>
      </a>
    </div>
  </header>

  <!-- Main Catalog Content -->
  <main class="main-wrapper">

    <!-- Horizontal Category Filter Bar -->
    <nav class="category-filter-wrapper" aria-label="Filter Kategori">
      <div class="category-filter-bar" id="category-filter-bar">
        <a href="/" class="category-pill <?= empty($selectedCategorySlug) ? 'active' : '' ?>">Semua</a>
        <?php foreach ($categories as $cat): ?>
          <a href="/?category=<?= urlencode($cat['slug']) ?>" 
             class="category-pill <?= $selectedCategorySlug === $cat['slug'] ? 'active' : '' ?>">
            <?= sanitize($cat['name']) ?>
          </a>
        <?php endforeach; ?>
      </div>
    </nav>

    <!-- Catalog Meta Bar -->
    <div class="catalog-meta-bar">
      <h1 class="catalog-title"><?= sanitize($currentCategoryName) ?></h1>
      <span class="catalog-count"><?= $totalProducts ?> produk</span>
    </div>

    <!-- Product Grid -->
    <?php if (!empty($products)): ?>
      <section class="product-grid" id="product-grid-container">
        <?php foreach ($products as $prod): ?>
          <?php 
            $stock = calculateStockStatus((int)$prod['quantity']);
            $imageUrl = getProductImageUrl($prod['image_path']);
            $message = rawurlencode('Saya ingin ' . $prod['name']);
            $whatsappUrl = 'https://wa.me/62895395806025?text=' . $message;
            $soldOutImagePath = PUBLIC_DIR . '/assets/image/soldout.png';
            $hasSoldOutImage = file_exists($soldOutImagePath);
          ?>
          <a href="<?= sanitize($whatsappUrl) ?>" 
             target="_blank" 
             rel="noopener noreferrer" 
             class="product-card" 
             id="product-card-<?= $prod['id'] ?>"
             aria-label="Tanyakan produk <?= sanitize($prod['name']) ?> melalui WhatsApp">
            <div class="product-image-wrap">
              <img src="<?= sanitize($imageUrl) ?>" alt="<?= sanitize($prod['name']) ?>" loading="lazy">
              
              <?php if ((int)$prod['quantity'] === 0): ?>
                <div class="soldout-overlay" aria-hidden="true">
                  <?php if ($hasSoldOutImage): ?>
                    <img src="/assets/image/soldout.png" alt="" class="soldout-img">
                  <?php else: ?>
                    <span class="soldout-text-fallback">SOLD OUT</span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>

              <span class="product-stock-badge <?= $stock['class'] ?>">
                <?= sanitize($stock['label']) ?>
              </span>
            </div>
            <div class="product-body">
              <span class="product-brand"><?= sanitize($prod['brand']) ?></span>
              <h2 class="product-name" title="<?= sanitize($prod['name']) ?>"><?= sanitize($prod['name']) ?></h2>
              <div class="product-footer">
                <span class="product-price"><?= formatRupiah($prod['price']) ?></span>
                <span class="product-qty">Stok: <?= (int)$prod['quantity'] ?></span>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </section>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <nav class="pagination-wrapper" aria-label="Navigasi Halaman">
          <?php if ($page > 1): ?>
            <a href="/?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="page-item" id="page-prev">&laquo; Prev</a>
          <?php else: ?>
            <span class="page-item disabled">&laquo; Prev</span>
          <?php endif; ?>

          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="/?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
               class="page-item <?= $i === $page ? 'active' : '' ?>">
              <?= $i ?>
            </a>
          <?php endfor; ?>

          <?php if ($page < $totalPages): ?>
            <a href="/?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="page-item" id="page-next">Next &raquo;</a>
          <?php else: ?>
            <span class="page-item disabled">Next &raquo;</span>
          <?php endif; ?>
        </nav>
      <?php endif; ?>

    <?php else: ?>
      <!-- Empty State -->
      <div class="empty-state" id="empty-state">
        <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          <line x1="8" y1="11" x2="14" y2="11"></line>
        </svg>
        <h2 class="empty-state-title">Produk tidak ditemukan</h2>
        <p class="empty-state-text">Tidak ada produk aktif dalam kategori ini saat ini.</p>
        <a href="/" class="btn-primary" id="btn-reset-filter">Tampilkan Semua Produk</a>
      </div>
    <?php endif; ?>

  </main>

  <footer class="site-footer">
    <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. Mobile-First Product Catalog.</p>
  </footer>

  <!-- Floating WhatsApp Button -->
  <a href="https://wa.me/62895395806025?text=Halo%2C%20saya%20ingin%20bertanya%20mengenai%20produk%20di%20katalog" 
     target="_blank" 
     rel="noopener noreferrer" 
     class="floating-wa-btn" 
     aria-label="Hubungi kami melalui WhatsApp"
     id="floating-wa-btn">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor">
      <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
    </svg>
  </a>

</body>
</html>
