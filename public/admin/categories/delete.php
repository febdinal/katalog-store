<?php
// public/admin/categories/delete.php

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/database.php';
require_once __DIR__ . '/../../../app/auth.php';
require_once __DIR__ . '/../../../app/csrf.php';
require_once __DIR__ . '/../../../app/helpers.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        setFlash('error', 'Token CSRF tidak valid.');
    } else {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id) {
            $db = getDb();
            // Fetch images of products in this category to clean up files
            $stmtImages = $db->prepare("SELECT image_path FROM products WHERE category_id = :id AND image_path IS NOT NULL");
            $stmtImages->execute([':id' => $id]);
            $images = $stmtImages->fetchAll(PDO::FETCH_COLUMN);

            foreach ($images as $img) {
                if ($img) {
                    $file = PUBLIC_DIR . '/' . ltrim($img, '/');
                    if (file_exists($file) && is_file($file)) {
                        @unlink($file);
                    }
                }
            }

            // Explicitly delete products in this category
            $deleteProductsStmt = $db->prepare("DELETE FROM products WHERE category_id = :id");
            $deleteProductsStmt->execute([':id' => $id]);

            $stmt = $db->prepare("DELETE FROM categories WHERE id = :id");
            $stmt->execute([':id' => $id]);

            setFlash('success', 'Kategori dan seluruh produk di dalamnya telah berhasil dihapus.');
        } else {
            setFlash('error', 'ID kategori tidak valid.');
        }
    }
}

redirect('/admin/categories/');
