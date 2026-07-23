<?php
// public/admin/products/delete.php

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
            // Fetch product image to clean up file
            $stmt = $db->prepare("SELECT image_path FROM products WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $id]);
            $product = $stmt->fetch();

            if ($product) {
                if ($product['image_path']) {
                    $file = PUBLIC_DIR . '/' . ltrim($product['image_path'], '/');
                    if (file_exists($file) && is_file($file)) {
                        @unlink($file);
                    }
                }

                $deleteStmt = $db->prepare("DELETE FROM products WHERE id = :id");
                $deleteStmt->execute([':id' => $id]);

                setFlash('success', 'Produk telah berhasil dihapus.');
            } else {
                setFlash('error', 'Produk tidak ditemukan.');
            }
        } else {
            setFlash('error', 'ID produk tidak valid.');
        }
    }
}

redirect('/admin/products/');
