<?php
// app/auth.php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

function initSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        session_set_cookie_params([
            'lifetime' => 86400, // 24 hours
            'path' => '/',
            'domain' => '',
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    }
}

function loginAdmin(string $email, string $password): bool {
    initSession();
    $db = getDb();
    $stmt = $db->prepare("SELECT * FROM admins WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        $_SESSION['admin_email'] = $admin['email'];
        return true;
    }
    return false;
}

function logoutAdmin(): void {
    initSession();
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

function isLoggedIn(): bool {
    initSession();
    return !empty($_SESSION['admin_id']);
}

function requireAdmin(): void {
    initSession();
    if (!isLoggedIn()) {
        header('Location: /admin/login.php');
        exit;
    }
}
