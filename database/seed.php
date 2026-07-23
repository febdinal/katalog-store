<?php
// database/seed.php

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/database.php';
require_once __DIR__ . '/../app/helpers.php';

echo "Initializing database schema...\n";

$db = getDb();
$schemaSql = file_get_contents(__DIR__ . '/schema.sql');
$db->exec($schemaSql);

echo "Seeding Admin Account...\n";
$adminPassword = password_hash('password', PASSWORD_BCRYPT);
$stmt = $db->prepare("INSERT INTO admins (name, email, password) VALUES (:name, :email, :password)");
$stmt->execute([
    ':name' => 'Administrator Katalog',
    ':email' => 'admin@katalog.test',
    ':password' => $adminPassword
]);

echo "Seeding Categories...\n";
$categories = [
    ['name' => 'Elektronik', 'slug' => 'elektronik'],
    ['name' => 'Pakaian', 'slug' => 'pakaian'],
    ['name' => 'Aksesoris', 'slug' => 'aksesoris'],
    ['name' => 'Peralatan Rumah', 'slug' => 'peralatan-rumah'],
    ['name' => 'Sepatu', 'slug' => 'sepatu']
];

$categoryIds = [];
$catStmt = $db->prepare("INSERT INTO categories (name, slug, is_active) VALUES (:name, :slug, 1)");
foreach ($categories as $cat) {
    $catStmt->execute([':name' => $cat['name'], ':slug' => $cat['slug']]);
    $categoryIds[$cat['slug']] = $db->lastInsertId();
}

echo "Seeding 20 Products...\n";
$products = [
    // Elektronik
    ['category' => 'elektronik', 'name' => 'Earphone Wireless TWS Pro 5.3', 'brand' => 'SoundCore', 'price' => 349000, 'quantity' => 12],
    ['category' => 'elektronik', 'name' => 'Smartwatch Sport Fit HD Display', 'brand' => 'Amazfit', 'price' => 799000, 'quantity' => 3],
    ['category' => 'elektronik', 'name' => 'Powerbank Fast Charge 20000mAh', 'brand' => 'Anker', 'price' => 450000, 'quantity' => 0],
    ['category' => 'elektronik', 'name' => 'Speaker Bluetooth Portable Bass', 'brand' => 'JBL', 'price' => 625000, 'quantity' => 15],

    // Pakaian
    ['category' => 'pakaian', 'name' => 'Kaos Oversized Cotton Combed 24s', 'brand' => 'Uniqlo', 'price' => 149000, 'quantity' => 25],
    ['category' => 'pakaian', 'name' => 'Jaket Denim Vintage Classic', 'brand' => 'Levi\'s', 'price' => 599000, 'quantity' => 2],
    ['category' => 'pakaian', 'name' => 'Kemeja Flannel Slim Fit Premium', 'brand' => 'Erigo', 'price' => 210000, 'quantity' => 8],
    ['category' => 'pakaian', 'name' => 'Sweater Hoodie Minimalis Heavyweight', 'brand' => 'H&M', 'price' => 329000, 'quantity' => 0],

    // Aksesoris
    ['category' => 'aksesoris', 'name' => 'Kacamata Polarized UV400 Protection', 'brand' => 'Ray-Ban', 'price' => 850000, 'quantity' => 4],
    ['category' => 'aksesoris', 'name' => 'Dompet Kulit Asli Bifold RFID', 'brand' => 'Eiger', 'price' => 275000, 'quantity' => 18],
    ['category' => 'aksesoris', 'name' => 'Jam Tangan Analogi Stainless Steel', 'brand' => 'Casio', 'price' => 950000, 'quantity' => 1],
    ['category' => 'aksesoris', 'name' => 'Ransel Laptop 15.6 Inci Water Resistant', 'brand' => 'Tigernu', 'price' => 389000, 'quantity' => 10],

    // Peralatan Rumah
    ['category' => 'peralatan-rumah', 'name' => 'Tumbler Stainless Vacuum 750ml', 'brand' => 'Hydro Flask', 'price' => 299000, 'quantity' => 7],
    ['category' => 'peralatan-rumah', 'name' => 'Lampu Meja LED Bedside Touch Control', 'brand' => 'Xiaomi', 'price' => 185000, 'quantity' => 0],
    ['category' => 'peralatan-rumah', 'name' => 'Diffuser Essential Oil Ambient RGB', 'brand' => 'Muji', 'price' => 240000, 'quantity' => 5],
    ['category' => 'peralatan-rumah', 'name' => 'Air Purifier HEPA Compact Room', 'brand' => 'Philips', 'price' => 1290000, 'quantity' => 9],

    // Sepatu
    ['category' => 'sepatu', 'name' => 'Sepatu Sneakers Canvas Low Top', 'brand' => 'Ventela', 'price' => 235000, 'quantity' => 14],
    ['category' => 'sepatu', 'name' => 'Sepatu Lari Cushion Light', 'brand' => 'Ortuseight', 'price' => 429000, 'quantity' => 3],
    ['category' => 'sepatu', 'name' => 'Sepatu Casual Slip On Breathable', 'brand' => 'Compass', 'price' => 380000, 'quantity' => 0],
    ['category' => 'sepatu', 'name' => 'Sandal Gunung Tough Grip Outdoor', 'brand' => 'Eiger', 'price' => 199000, 'quantity' => 20]
];

$prodStmt = $db->prepare("
    INSERT INTO products (category_id, name, brand, price, quantity, image_path, is_active)
    VALUES (:category_id, :name, :brand, :price, :quantity, :image_path, 1)
");

foreach ($products as $prod) {
    $catId = $categoryIds[$prod['category']];
    $prodStmt->execute([
        ':category_id' => $catId,
        ':name' => $prod['name'],
        ':brand' => $prod['brand'],
        ':price' => $prod['price'],
        ':quantity' => $prod['quantity'],
        ':image_path' => null // Default placeholder
    ]);
}

echo "Database seeded successfully!\n";
echo "==========================================\n";
echo "ADMIN DEVELOPMENT CREDENTIALS:\n";
echo "Email    : admin@katalog.test\n";
echo "Password : password\n";
echo "[WARNING] Pastikan untuk mengganti password admin sebelum dideploy ke lingkungan produksi!\n";
echo "==========================================\n";
