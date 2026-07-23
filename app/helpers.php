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
