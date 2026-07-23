<?php
// app/helpers.php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

function sanitize(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function formatRupiah($number): string {
    return 'Rp ' . number_format((float)$number, 0, ',', '.');
}

function calculateStockStatus(int $quantity): array {
    if ($quantity <= 0) {
        return [
            'code' => 'out',
            'label' => 'Habis',
            'class' => 'badge-stock-out'
        ];
    } else {
        return [
            'code' => 'available',
            'label' => 'Tersedia',
            'class' => 'badge-stock-available'
        ];
    }
}

function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a' : $text;
}

function redirect(string $url): void {
    header("Location: {$url}");
    exit;
}

function setFlash(string $type, string $message): void {
    initSession();
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlash(): ?array {
    initSession();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function getProductImageUrl(?string $imagePath): string {
    if ($imagePath && file_exists(PUBLIC_DIR . '/' . ltrim($imagePath, '/'))) {
        return '/' . ltrim($imagePath, '/');
    }
    return '/assets/images/placeholder.svg';
}

function renderAdminHeader(string $activeNav = ''): void {
    $overviewActive = ($activeNav === 'overview') ? ' active' : '';
    $productsActive = ($activeNav === 'products') ? ' active' : '';
    $categoriesActive = ($activeNav === 'categories') ? ' active' : '';
    $backgroundActive = ($activeNav === 'background') ? ' active' : '';
    
    echo <<<HTML
  <!-- Admin Header -->
  <header class="site-header admin-header">
    <div class="header-container admin-header-container">
      <button type="button" class="admin-menu-toggle" aria-label="Buka menu admin" aria-expanded="false" aria-controls="admin-drawer">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="3" y1="6" x2="21" y2="6"></line>
          <line x1="3" y1="12" x2="21" y2="12"></line>
          <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
      </button>
      <a href="/admin/index.php" class="brand-logo admin-brand-logo">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <rect x="3" y="3" width="7" height="7"></rect>
          <rect x="14" y="3" width="7" height="7"></rect>
          <rect x="14" y="14" width="7" height="7"></rect>
          <rect x="3" y="14" width="7" height="7"></rect>
        </svg>
        <span>Admin Dashboard</span>
      </a>
      <nav class="admin-desktop-nav" aria-label="Navigasi utama admin">
        <a href="/admin/index.php" class="admin-nav-item{$overviewActive}">Overview</a>
        <a href="/admin/products/" class="admin-nav-item{$productsActive}">Produk</a>
        <a href="/admin/categories/" class="admin-nav-item{$categoriesActive}">Kategori</a>
        <a href="/admin/background.php" class="admin-nav-item{$backgroundActive}">Background</a>
        <a href="/" target="_blank" rel="noopener noreferrer" class="admin-nav-item">Lihat Web</a>
        <a href="/admin/logout.php" class="admin-nav-item logout-link">Keluar</a>
      </nav>
    </div>
  </header>

  <!-- Mobile Navigation Drawer -->
  <aside id="admin-drawer" class="admin-drawer" aria-label="Navigasi admin" aria-hidden="true">
    <div class="admin-drawer-header">
      <a href="/admin/index.php" class="brand-logo admin-brand-logo">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <rect x="3" y="3" width="7" height="7"></rect>
          <rect x="14" y="3" width="7" height="7"></rect>
          <rect x="14" y="14" width="7" height="7"></rect>
          <rect x="3" y="14" width="7" height="7"></rect>
        </svg>
        <span>Admin Dashboard</span>
      </a>
      <button type="button" class="admin-drawer-close" aria-label="Tutup menu admin">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>
    <nav class="admin-drawer-nav">
      <a href="/admin/index.php" class="admin-drawer-item{$overviewActive}">Overview</a>
      <a href="/admin/products/" class="admin-drawer-item{$productsActive}">Produk</a>
      <a href="/admin/categories/" class="admin-drawer-item{$categoriesActive}">Kategori</a>
      <a href="/admin/background.php" class="admin-drawer-item{$backgroundActive}">Background</a>
      <a href="/" target="_blank" rel="noopener noreferrer" class="admin-drawer-item">Lihat Web</a>
      <a href="/admin/logout.php" class="admin-drawer-item logout-link">Keluar</a>
    </nav>
  </aside>
  <div class="admin-drawer-backdrop" aria-hidden="true"></div>
HTML;
}

