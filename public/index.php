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

// Background Music Settings
$bgMusicUrl = getSetting('bg_music_url', 'https://cdn.pixabay.com/download/audio/2022/05/27/audio_1808fbf07a.mp3?filename=lofi-study-112191.mp3');
$bgMusicEnabled = getSetting('bg_music_enabled', '1');
$bgMusicAutoplay = getSetting('bg_music_autoplay', '1');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= sanitize($currentCategoryName) ?> - <?= SITE_NAME ?></title>
  <meta name="description" content="Katalog produk retail pilihan dengan harga terbaik, stok realtime, dan transaksi cepat.">
  <link rel="stylesheet" href="/assets/css/style.css?v=<?= file_exists(PUBLIC_DIR . '/assets/css/style.css') ? filemtime(PUBLIC_DIR . '/assets/css/style.css') : time() ?>">
  <script src="/assets/js/app.js?v=<?= file_exists(PUBLIC_DIR . '/assets/js/app.js') ? filemtime(PUBLIC_DIR . '/assets/js/app.js') : time() ?>" defer></script>
</head>
<body>

  <!-- Site Header -->
  <header class="site-header">
    <div class="header-container">
  <a href="/" class="brand-logo" id="header-brand-link">
    <!-- Icon boneka teddy bear -->
    <svg
      class="brand-icon"
      width="30"
      height="30"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="1.8"
      stroke-linecap="round"
      stroke-linejoin="round"
      aria-hidden="true"
    >
      <!-- Telinga -->
      <circle cx="6.5" cy="5.5" r="2.5"></circle>
      <circle cx="17.5" cy="5.5" r="2.5"></circle>

      <!-- Kepala -->
      <circle cx="12" cy="9" r="6"></circle>

      <!-- Mata -->
      <circle cx="9.5" cy="8.5" r="0.65" fill="currentColor" stroke="none"></circle>
      <circle cx="14.5" cy="8.5" r="0.65" fill="currentColor" stroke="none"></circle>

      <!-- Moncong dan mulut -->
      <ellipse cx="12" cy="11.5" rx="2.2" ry="1.7"></ellipse>
      <circle cx="12" cy="11" r="0.55" fill="currentColor" stroke="none"></circle>
      <path d="M12 11.5v1"></path>
      <path d="M10.8 12.5c.7.7 1.7.7 2.4 0"></path>

      <!-- Badan -->
      <path d="M8 14.2C6.8 15.3 6.2 17 6.5 19c.3 2 2.3 3 5.5 3s5.2-1 5.5-3c.3-2-.3-3.7-1.5-4.8"></path>

      <!-- Tangan -->
      <path d="M7.2 16.2 4.5 18"></path>
      <path d="m16.8 16.2 2.7 1.8"></path>

      <!-- Kaki -->
      <path d="M9 21.5 8 23"></path>
      <path d="m15 21.5 1 1.5"></path>
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
            $isSoldOut = ((int)$prod['quantity'] <= 0);
            $stock = calculateStockStatus((int)$prod['quantity']);
            $imageUrl = getProductImageUrl($prod['image_path']);
            $message = rawurlencode('Saya ingin ' . $prod['name']);
            $whatsappUrl = 'https://wa.me/62895395806025?text=' . $message;
            $soldOutImagePath = PUBLIC_DIR . '/assets/image/soldout.png';
            $hasSoldOutImage = file_exists($soldOutImagePath);
          ?>
          <?php if ($isSoldOut): ?>
            <div class="product-card product-card-soldout" 
                 id="product-card-<?= $prod['id'] ?>"
                 aria-label="Produk <?= sanitize($prod['name']) ?> (Stok Habis)"
                 aria-disabled="true">
          <?php else: ?>
            <a href="<?= sanitize($whatsappUrl) ?>" 
               target="_blank" 
               rel="noopener noreferrer" 
               class="product-card" 
               id="product-card-<?= $prod['id'] ?>"
               aria-label="Tanyakan produk <?= sanitize($prod['name']) ?> melalui WhatsApp">
          <?php endif; ?>
            <div class="product-image-wrap">
              <img src="<?= sanitize($imageUrl) ?>" alt="<?= sanitize($prod['name']) ?>" loading="lazy">
              
              <?php if ($isSoldOut): ?>
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
              <p class="product-brand"><?= sanitize($prod['brand']) ?></p>
              <h4 class="product-title"><?= sanitize($prod['name']) ?></h4>
              
              <div class="product-footer">
                <span class="product-price"><?= formatRupiah($prod['price']) ?></span>
                <span class="product-qty">Stok: <?= (int)$prod['quantity'] ?></span>
              </div>
            </div>
          <?php if ($isSoldOut): ?>
            </div>
          <?php else: ?>
            </a>
          <?php endif; ?>
        <?php endforeach; ?>
      </section>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <nav class="pagination-wrapper" aria-label="Navigasi Halaman">
          <?php if ($page > 1): ?>
            <a href="/?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="page-item" aria-label="Halaman Sebelumnya">&laquo; Prev</a>
          <?php else: ?>
            <span class="page-item disabled" aria-disabled="true">&laquo; Prev</span>
          <?php endif; ?>

          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="/?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
               class="page-item <?= $i === $page ? 'active' : '' ?>" 
               <?= $i === $page ? 'aria-current="page"' : '' ?>>
              <?= $i ?>
            </a>
          <?php endfor; ?>

          <?php if ($page < $totalPages): ?>
            <a href="/?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="page-item" aria-label="Halaman Selanjutnya">Next &raquo;</a>
          <?php else: ?>
            <span class="page-item disabled" aria-disabled="true">Next &raquo;</span>
          <?php endif; ?>
        </nav>
      <?php endif; ?>

    <?php else: ?>
      <div class="empty-state">
        <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
        </svg>
        <h2 class="empty-state-title">Belum ada produk</h2>
        <p class="empty-state-text">Produk untuk kategori ini tidak ditemukan atau belum ditambahkan.</p>
        <?php if (!empty($selectedCategorySlug)): ?>
          <a href="/" class="btn-primary">Lihat Semua Produk</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>

  </main>

  <footer class="site-footer">
    <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. Made with ❤️ by febdinal 😎</p>
  </footer>

  <!-- Floating Background Music Player Button (Glassmorphism) -->
  <?php if ($bgMusicEnabled === '1' && !empty($bgMusicUrl)): ?>
    <audio id="bg-music-player" autoplay loop preload="auto" data-autoplay="<?= $bgMusicAutoplay ?>">
      <source src="<?= sanitize($bgMusicUrl) ?>" type="audio/mpeg">
    </audio>
    
    <button type="button" 
            class="floating-music-btn" 
            id="floating-music-btn" 
            aria-label="Putar / Hentikan Musik Latar" 
            title="Putar / Hentikan Musik Latar">
      <span class="music-disc-wrap">
        <!-- SVG Play Icon -->
        <svg class="music-icon icon-play" width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
          <polygon points="6 3 20 12 6 21 6 3"></polygon>
        </svg>
        <!-- SVG Pause Icon -->
        <svg class="music-icon icon-pause" width="22" height="22" viewBox="0 0 24 24" fill="currentColor" style="display: none;">
          <rect x="6" y="4" width="4" height="16" rx="1"></rect>
          <rect x="14" y="4" width="4" height="16" rx="1"></rect>
        </svg>
      </span>
      <span class="music-wave-ring" aria-hidden="true"></span>
    </button>
  <?php endif; ?>

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
