<?php
// app/config.php

define('ROOT_DIR', dirname(__DIR__));
define('APP_DIR', __DIR__);
define('PUBLIC_DIR', ROOT_DIR . '/public');
define('UPLOADS_DIR', PUBLIC_DIR . '/uploads/products');
define('DB_PATH', ROOT_DIR . '/database/catalog.sqlite');

define('SITE_NAME', 'Katalog Produk');
define('ITEMS_PER_PAGE', 12);

// Stock threshold definitions
define('STOCK_THRESHOLD_LOW', 5);

// Allowed Upload MIME types & extensions
define('ALLOWED_MIME_TYPES', [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp'
]);
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // 2 MB
