<?php
// database/seed.php
// Seeder idempoten untuk MySQL.
// Aman dijalankan berulang — tidak akan menduplikasi admin, kategori, atau produk.
// Jalankan dengan: php database/seed.php

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/database.php';
require_once __DIR__ . '/../app/helpers.php';

$db = getDb();

function executeSqlFile(PDO $pdo, string $filePath): void {
    $sql = file_get_contents($filePath);
    // Remove single line comments
    $sql = preg_replace('/^--.*$/m', '', $sql);
    // Remove multi-line comments
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        fn($s) => !empty($s)
    );

    foreach ($statements as $stmt) {
        $pdo->exec($stmt);
    }
}

// ─── Schema ────────────────────────────────────────────────────────────────
echo "Menjalankan schema MySQL...\n";
executeSqlFile($db, __DIR__ . '/schema-mysql.sql');
echo "  Schema berhasil diterapkan.\n\n";

// ─── Admin ─────────────────────────────────────────────────────────────────
echo "Seeding Admin Account...\n";
$existingAdmin = $db->prepare("SELECT id FROM admins WHERE email = :email LIMIT 1");
$existingAdmin->execute([':email' => 'admin@katalog.test']);

if (!$existingAdmin->fetch()) {
    $adminPassword = password_hash('password', PASSWORD_BCRYPT);
    $stmt = $db->prepare("INSERT INTO admins (name, email, password) VALUES (:name, :email, :password)");
    $stmt->execute([
        ':name'     => 'Administrator Katalog',
        ':email'    => 'admin@katalog.test',
        ':password' => $adminPassword,
    ]);
    echo "  Admin baru dibuat.\n";
} else {
    echo "  Admin sudah ada — dilewati.\n";
}

// ─── Categories ────────────────────────────────────────────────────────────
echo "\nSeeding Categories...\n";
$categories = [
    ['name' => 'Elektronik',       'slug' => 'elektronik'],
    ['name' => 'Pakaian',          'slug' => 'pakaian'],
    ['name' => 'Aksesoris',        'slug' => 'aksesoris'],
    ['name' => 'Peralatan Rumah',  'slug' => 'peralatan-rumah'],
    ['name' => 'Sepatu',           'slug' => 'sepatu'],
];

$categoryIds = [];
$catCheck = $db->prepare("SELECT id FROM categories WHERE slug = :slug LIMIT 1");
$catInsert = $db->prepare("INSERT INTO categories (name, slug, is_active) VALUES (:name, :slug, 1)");

foreach ($categories as $cat) {
    $catCheck->execute([':slug' => $cat['slug']]);
    $existing = $catCheck->fetch();
    if ($existing) {
        $categoryIds[$cat['slug']] = (int)$existing['id'];
        echo "  Kategori '{$cat['name']}' sudah ada — dilewati.\n";
    } else {
        $catInsert->execute([':name' => $cat['name'], ':slug' => $cat['slug']]);
        $categoryIds[$cat['slug']] = (int)$db->lastInsertId();
        echo "  Kategori '{$cat['name']}' dibuat.\n";
    }
}

// ─── Products ──────────────────────────────────────────────────────────────
echo "\nSeeding Products...\n";
$products = [
    // Elektronik
    ['category' => 'elektronik',      'name' => 'Earphone Wireless TWS Pro 5.3',         'brand' => 'SoundCore',    'price' => 349000, 'quantity' => 12],
    ['category' => 'elektronik',      'name' => 'Smartwatch Sport Fit HD Display',        'brand' => 'Amazfit',      'price' => 799000, 'quantity' => 3],
    ['category' => 'elektronik',      'name' => 'Powerbank Fast Charge 20000mAh',         'brand' => 'Anker',        'price' => 450000, 'quantity' => 0],
    ['category' => 'elektronik',      'name' => 'Speaker Bluetooth Portable Bass',        'brand' => 'JBL',          'price' => 625000, 'quantity' => 15],
    // Pakaian
    ['category' => 'pakaian',         'name' => 'Kaos Oversized Cotton Combed 24s',       'brand' => 'Uniqlo',       'price' => 149000, 'quantity' => 25],
    ['category' => 'pakaian',         'name' => 'Jaket Denim Vintage Classic',            'brand' => "Levi's",       'price' => 599000, 'quantity' => 2],
    ['category' => 'pakaian',         'name' => 'Kemeja Flannel Slim Fit Premium',        'brand' => 'Erigo',        'price' => 210000, 'quantity' => 8],
    ['category' => 'pakaian',         'name' => 'Sweater Hoodie Minimalis Heavyweight',   'brand' => 'H&M',          'price' => 329000, 'quantity' => 0],
    // Aksesoris
    ['category' => 'aksesoris',       'name' => 'Kacamata Polarized UV400 Protection',   'brand' => 'Ray-Ban',      'price' => 850000, 'quantity' => 4],
    ['category' => 'aksesoris',       'name' => 'Dompet Kulit Asli Bifold RFID',         'brand' => 'Eiger',        'price' => 275000, 'quantity' => 18],
    ['category' => 'aksesoris',       'name' => 'Jam Tangan Analogi Stainless Steel',    'brand' => 'Casio',        'price' => 950000, 'quantity' => 1],
    ['category' => 'aksesoris',       'name' => 'Ransel Laptop 15.6 Inci Water Resistant','brand' => 'Tigernu',     'price' => 389000, 'quantity' => 10],
    // Peralatan Rumah
    ['category' => 'peralatan-rumah', 'name' => 'Tumbler Stainless Vacuum 750ml',        'brand' => 'Hydro Flask',  'price' => 299000, 'quantity' => 7],
    ['category' => 'peralatan-rumah', 'name' => 'Lampu Meja LED Bedside Touch Control',  'brand' => 'Xiaomi',       'price' => 185000, 'quantity' => 0],
    ['category' => 'peralatan-rumah', 'name' => 'Diffuser Essential Oil Ambient RGB',    'brand' => 'Muji',         'price' => 240000, 'quantity' => 5],
    ['category' => 'peralatan-rumah', 'name' => 'Air Purifier HEPA Compact Room',        'brand' => 'Philips',      'price' => 1290000,'quantity' => 9],
    // Sepatu
    ['category' => 'sepatu',          'name' => 'Sepatu Sneakers Canvas Low Top',         'brand' => 'Ventela',      'price' => 235000, 'quantity' => 14],
    ['category' => 'sepatu',          'name' => 'Sepatu Lari Cushion Light',              'brand' => 'Ortuseight',   'price' => 429000, 'quantity' => 3],
    ['category' => 'sepatu',          'name' => 'Sepatu Casual Slip On Breathable',       'brand' => 'Compass',      'price' => 380000, 'quantity' => 0],
    ['category' => 'sepatu',          'name' => 'Sandal Gunung Tough Grip Outdoor',       'brand' => 'Eiger',        'price' => 199000, 'quantity' => 20],
];

$prodCheck  = $db->prepare("SELECT id FROM products WHERE name = :name AND brand = :brand LIMIT 1");
$prodInsert = $db->prepare("
    INSERT INTO products (category_id, name, brand, price, quantity, image_path, is_active)
    VALUES (:category_id, :name, :brand, :price, :quantity, NULL, 1)
");

$inserted = 0;
$skipped  = 0;

foreach ($products as $prod) {
    $catId = $categoryIds[$prod['category']] ?? null;
    if (!$catId) {
        echo "  [WARN] Kategori '{$prod['category']}' tidak ditemukan untuk produk '{$prod['name']}'\n";
        continue;
    }

    $prodCheck->execute([':name' => $prod['name'], ':brand' => $prod['brand']]);
    if ($prodCheck->fetch()) {
        $skipped++;
        continue;
    }

    $prodInsert->execute([
        ':category_id' => $catId,
        ':name'        => $prod['name'],
        ':brand'       => $prod['brand'],
        ':price'       => $prod['price'],
        ':quantity'    => $prod['quantity'],
    ]);
    $inserted++;
}

echo "  Produk baru: {$inserted}, dilewati (sudah ada): {$skipped}\n";

echo "\n==========================================\n";
echo "Seeder selesai!\n";
echo "ADMIN DEVELOPMENT CREDENTIALS:\n";
echo "Email    : admin@katalog.test\n";
echo "Password : password\n";
echo "[WARNING] Ganti password admin sebelum deploy ke produksi!\n";
echo "==========================================\n";
