<?php
// public/admin/categories/index.php

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/database.php';
require_once __DIR__ . '/../../../app/auth.php';
require_once __DIR__ . '/../../../app/csrf.php';
require_once __DIR__ . '/../../../app/helpers.php';

requireAdmin();
$db = getDb();
$flash = getFlash();

$categories = $db->query("
    SELECT c.id, c.name, c.slug, c.is_active, c.created_at,
           COUNT(p.id) as product_count
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id
    GROUP BY c.id
    ORDER BY c.name ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Kategori - <?= SITE_NAME ?></title>
  <link rel="stylesheet" href="/assets/css/style.css">
  <script src="/assets/js/app.js" defer></script>
<script src="/assets/js/admin.js" defer></script>
</head>
<body>

<?php renderAdminHeader('categories'); ?>

  <main class="main-wrapper">
    <?php if ($flash): ?>
      <div class="alert alert-<?= sanitize($flash['type']) ?>">
        <?= sanitize($flash['message']) ?>
      </div>
    <?php endif; ?>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
      <div>
        <h1 style="font-size: 1.35rem; font-weight: 800;">Kelola Kategori</h1>
        <p style="font-size: 0.85rem; color: var(--text-muted);">Daftar seluruh kategori produk dalam sistem</p>
      </div>
      <a href="/admin/categories/create.php" class="btn-primary" id="btn-add-category">+ Tambah Kategori</a>
    </div>

    <div class="card-panel">
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nama Kategori</th>
              <th>Slug</th>
              <th>Jumlah Produk</th>
              <th>Status Active</th>
              <th style="text-align: right;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($categories as $cat): ?>
              <tr>
                <td>#<?= $cat['id'] ?></td>
                <td><strong><?= sanitize($cat['name']) ?></strong></td>
                <td><code><?= sanitize($cat['slug']) ?></code></td>
                <td><?= (int)$cat['product_count'] ?> produk</td>
                <td>
                  <?php if ($cat['is_active']): ?>
                    <span class="product-stock-badge badge-stock-available" style="position: static;">Aktif</span>
                  <?php else: ?>
                    <span class="product-stock-badge badge-stock-out" style="position: static;">Non-Aktif</span>
                  <?php endif; ?>
                </td>
                <td style="text-align: right; white-space: nowrap;">
                  <a href="/admin/categories/edit.php?id=<?= $cat['id'] ?>" class="btn-secondary" style="padding: 0.25rem 0.625rem; min-height: 36px; font-size: 0.8rem;">Edit</a>
                  
                  <form method="POST" action="/admin/categories/delete.php" class="form-confirm-delete" data-confirm="Hapus kategori '<?= sanitize($cat['name']) ?>'? Semua produk dalam kategori ini juga akan terhapus!" style="display: inline-block;">
                    <?= getCsrfInput() ?>
                    <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                    <button type="submit" class="btn-danger" style="padding: 0.25rem 0.625rem; min-height: 36px; font-size: 0.8rem;">Hapus</button>
                  </form>
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
