<?php
// app/validation.php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

function validateCategory(array $input, ?int $id = null): array {
    $errors = [];
    $name = trim($input['name'] ?? '');
    $slug = trim($input['slug'] ?? '');
    $isActive = isset($input['is_active']) ? 1 : 0;

    if (empty($name)) {
        $errors['name'] = 'Nama kategori wajib diisi.';
    } elseif (mb_strlen($name) > 100) {
        $errors['name'] = 'Nama kategori maksimal 100 karakter.';
    }

    if (empty($slug)) {
        $slug = slugify($name);
    } else {
        $slug = slugify($slug);
    }

    $db = getDb();
    if ($id) {
        $stmt = $db->prepare("SELECT id FROM categories WHERE slug = :slug AND id != :id LIMIT 1");
        $stmt->execute([':slug' => $slug, ':id' => $id]);
    } else {
        $stmt = $db->prepare("SELECT id FROM categories WHERE slug = :slug LIMIT 1");
        $stmt->execute([':slug' => $slug]);
    }

    if ($stmt->fetch()) {
        $errors['slug'] = 'Slug kategori sudah digunakan.';
    }

    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'data' => [
            'name' => $name,
            'slug' => $slug,
            'is_active' => $isActive
        ]
    ];
}

function validateProduct(array $input): array {
    $errors = [];
    $name = trim($input['name'] ?? '');
    $brand = trim($input['brand'] ?? '');
    $categoryId = filter_var($input['category_id'] ?? null, FILTER_VALIDATE_INT);
    $price = filter_var($input['price'] ?? null, FILTER_VALIDATE_FLOAT);
    $quantity = filter_var($input['quantity'] ?? null, FILTER_VALIDATE_INT);
    $isActive = isset($input['is_active']) ? 1 : 0;

    if (empty($name)) {
        $errors['name'] = 'Nama produk wajib diisi.';
    } elseif (mb_strlen($name) > 150) {
        $errors['name'] = 'Nama produk maksimal 150 karakter.';
    }

    if (empty($brand)) {
        $errors['brand'] = 'Merek produk wajib diisi.';
    } elseif (mb_strlen($brand) > 100) {
        $errors['brand'] = 'Merek produk maksimal 100 karakter.';
    }

    if ($categoryId === false || $categoryId <= 0) {
        $errors['category_id'] = 'Kategori produk wajib dipilih.';
    } else {
        $db = getDb();
        $stmt = $db->prepare("SELECT id FROM categories WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $categoryId]);
        if (!$stmt->fetch()) {
            $errors['category_id'] = 'Kategori yang dipilih tidak valid.';
        }
    }

    if ($price === false || $price < 0) {
        $errors['price'] = 'Harga wajib diisi angka minimal 0.';
    }

    if ($quantity === false || $quantity < 0) {
        $errors['quantity'] = 'Quantity wajib diisi angka bulat minimal 0.';
    }

    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'data' => [
            'name' => $name,
            'brand' => $brand,
            'category_id' => $categoryId,
            'price' => $price,
            'quantity' => $quantity,
            'is_active' => $isActive
        ]
    ];
}

function handleProductImageUpload(array $file, ?string $currentImagePath = null): array {
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'path' => $currentImagePath];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Gagal mengunggah file gambar (Error code: ' . $file['error'] . ').'];
    }

    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['success' => false, 'error' => 'Ukuran file maksimal 2 MB.'];
    }

    if (!is_dir(UPLOADS_DIR)) {
        mkdir(UPLOADS_DIR, 0755, true);
    }

    // Verify MIME type strictly using finfo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!array_key_exists($mimeType, ALLOWED_MIME_TYPES)) {
        return ['success' => false, 'error' => 'Format file tidak diizinkan. Hanya JPG, PNG, dan WebP.'];
    }

    $extension = ALLOWED_MIME_TYPES[$mimeType];
    $randomName = bin2hex(random_bytes(16)) . '.' . $extension;
    $targetPath = UPLOADS_DIR . '/' . $randomName;
    $relativePath = 'uploads/products/' . $randomName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => false, 'error' => 'Gagal menyimpan gambar di server.'];
    }

    // Delete previous image if updating
    if ($currentImagePath && $currentImagePath !== $relativePath) {
        $oldFile = PUBLIC_DIR . '/' . ltrim($currentImagePath, '/');
        if (file_exists($oldFile) && is_file($oldFile)) {
            @unlink($oldFile);
        }
    }

    return ['success' => true, 'path' => $relativePath];
}
