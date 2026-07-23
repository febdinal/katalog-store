<?php
// database/migrate_sqlite_to_mysql.php
// Script migrasi data 1 kali dari SQLite (database/catalog.sqlite) ke MySQL.
// Jalankan dengan: php database/migrate_sqlite_to_mysql.php

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/database.php';

$sqlitePath = __DIR__ . '/catalog.sqlite';

if (!file_exists($sqlitePath)) {
    echo "[ABORT] File SQLite lama ({$sqlitePath}) tidak ditemukan. Tidak ada data yang perlu dimigrasikan.\n";
    exit(0);
}

echo "=== MEMULAI MIGRASI DATA dari SQLite ke MySQL ===\n\n";

try {
    $sqlitePdo = new PDO('sqlite:' . $sqlitePath);
    $sqlitePdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sqlitePdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "[ERROR] Gagal membuka database SQLite: " . $e->getMessage() . "\n";
    exit(1);
}

$mysqlPdo = getDb();

// 1. Hitung record awal di SQLite
$sqliteAdminsCount = (int)$sqlitePdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
$sqliteCatCount    = (int)$sqlitePdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$sqliteProdCount   = (int)$sqlitePdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

echo "Jumlah Data di SQLite:\n";
echo "  - Admins     : {$sqliteAdminsCount}\n";
echo "  - Categories : {$sqliteCatCount}\n";
echo "  - Products   : {$sqliteProdCount}\n\n";

// Ensure MySQL schema exists
$sql = file_get_contents(__DIR__ . '/schema-mysql.sql');
$sql = preg_replace('/^--.*$/m', '', $sql);
$sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
$statements = array_filter(array_map('trim', explode(';', $sql)), fn($s) => !empty($s));
foreach ($statements as $stmt) {
    $mysqlPdo->exec($stmt);
}

// Start Transaction
$mysqlPdo->beginTransaction();

try {
    // 2. Migrasi Admins
    echo "1. Migrasi Admins...\n";
    $admins = $sqlitePdo->query("SELECT * FROM admins")->fetchAll();
    $adminStmt = $mysqlPdo->prepare("
        INSERT INTO admins (id, name, email, password, created_at)
        VALUES (:id, :name, :email, :password, :created_at)
        ON DUPLICATE KEY UPDATE name = VALUES(name), password = VALUES(password)
    ");

    $migratedAdmins = 0;
    foreach ($admins as $admin) {
        $pwd = $admin['password'];
        if (!str_starts_with($pwd, '$2y$') && !str_starts_with($pwd, '$2a$')) {
            $pwd = password_hash($pwd, PASSWORD_BCRYPT);
        }

        $adminStmt->execute([
            ':id'         => $admin['id'],
            ':name'       => $admin['name'],
            ':email'      => $admin['email'],
            ':password'   => $pwd,
            ':created_at' => $admin['created_at'] ?? date('Y-m-d H:i:s'),
        ]);
        $migratedAdmins++;
    }
    echo "   Dapat diproses: {$migratedAdmins} admins.\n";

    // 3. Migrasi Categories
    echo "2. Migrasi Categories...\n";
    $categories = $sqlitePdo->query("SELECT * FROM categories")->fetchAll();
    $catStmt = $mysqlPdo->prepare("
        INSERT INTO categories (id, name, slug, is_active, created_at, updated_at)
        VALUES (:id, :name, :slug, :is_active, :created_at, :updated_at)
        ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            is_active = VALUES(is_active),
            updated_at = VALUES(updated_at)
    ");

    $migratedCats = 0;
    foreach ($categories as $cat) {
        $catStmt->execute([
            ':id'         => $cat['id'],
            ':name'       => $cat['name'],
            ':slug'       => $cat['slug'],
            ':is_active'  => $cat['is_active'],
            ':created_at' => $cat['created_at'] ?? date('Y-m-d H:i:s'),
            ':updated_at' => $cat['updated_at'] ?? date('Y-m-d H:i:s'),
        ]);
        $migratedCats++;
    }
    echo "   Dapat diproses: {$migratedCats} categories.\n";

    // 4. Migrasi Products
    echo "3. Migrasi Products...\n";
    $products = $sqlitePdo->query("SELECT * FROM products")->fetchAll();
    $prodStmt = $mysqlPdo->prepare("
        INSERT INTO products (id, category_id, name, brand, price, quantity, image_path, is_active, created_at, updated_at)
        VALUES (:id, :category_id, :name, :brand, :price, :quantity, :image_path, :is_active, :created_at, :updated_at)
        ON DUPLICATE KEY UPDATE
            category_id = VALUES(category_id),
            name = VALUES(name),
            brand = VALUES(brand),
            price = VALUES(price),
            quantity = VALUES(quantity),
            image_path = VALUES(image_path),
            is_active = VALUES(is_active),
            updated_at = VALUES(updated_at)
    ");

    $migratedProds = 0;
    foreach ($products as $prod) {
        $prodStmt->execute([
            ':id'          => $prod['id'],
            ':category_id' => $prod['category_id'],
            ':name'        => $prod['name'],
            ':brand'       => $prod['brand'],
            ':price'       => $prod['price'],
            ':quantity'    => $prod['quantity'],
            ':image_path'  => $prod['image_path'],
            ':is_active'   => $prod['is_active'],
            ':created_at'  => $prod['created_at'] ?? date('Y-m-d H:i:s'),
            ':updated_at'  => $prod['updated_at'] ?? date('Y-m-d H:i:s'),
        ]);
        $migratedProds++;
    }
    echo "   Dapat diproses: {$migratedProds} products.\n";

    $mysqlPdo->commit();
    echo "\n[SUCCESS] Transaksi migrasi berhasil di-commit ke MySQL!\n\n";

} catch (Exception $e) {
    $mysqlPdo->rollBack();
    echo "\n[ERROR] Migrasi gagal dan di-rollback: " . $e->getMessage() . "\n";
    exit(1);
}

// 5. Validasi Record Setelah Migrasi
$mysqlAdminsCount = (int)$mysqlPdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
$mysqlCatCount    = (int)$mysqlPdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$mysqlProdCount   = (int)$mysqlPdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

echo "=== VERIFIKASI JUMLAH RECORD ===\n";
echo "Admins     - SQLite: {$sqliteAdminsCount} | MySQL: {$mysqlAdminsCount} " . ($mysqlAdminsCount >= $sqliteAdminsCount ? "✓ OK" : "✗ MISMATCH") . "\n";
echo "Categories - SQLite: {$sqliteCatCount} | MySQL: {$mysqlCatCount} " . ($mysqlCatCount >= $sqliteCatCount ? "✓ OK" : "✗ MISMATCH") . "\n";
echo "Products   - SQLite: {$sqliteProdCount} | MySQL: {$mysqlProdCount} " . ($mysqlProdCount >= $sqliteProdCount ? "✓ OK" : "✗ MISMATCH") . "\n\n";

echo "File SQLite lama tetap dipertahankan di: {$sqlitePath}\n";
echo "Migrasi selesai dengan sukses!\n";
