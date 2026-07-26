<?php
// public/admin/login.php

require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/database.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/csrf.php';
require_once __DIR__ . '/../../app/helpers.php';

initSession();

if (isLoggedIn()) {
    redirect('/admin/index.php');
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Sesi tidak valid. Silakan coba lagi.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Email dan password wajib diisi.';
        } elseif (loginAdmin($email, $password)) {
            setFlash('success', 'Selamat datang kembali, ' . $_SESSION['admin_name'] . '!');
            redirect('/admin/index.php');
        } else {
            $error = 'Email atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Admin - <?= SITE_NAME ?></title>
  <link rel="icon" type="image/png" href="/assets/image/favicon.png?v=<?= file_exists(PUBLIC_DIR . '/assets/image/favicon.png') ? filemtime(PUBLIC_DIR . '/assets/image/favicon.png') : time() ?>">
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body style="justify-content: center; align-items: center; padding: 1.5rem;">

  <div class="card-panel" style="width: 100%; max-width: 400px;">
    <div style="text-align: center; margin-bottom: 1.5rem;">
      <a href="/" class="brand-logo" style="justify-content: center; margin-bottom: 0.5rem;">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
          <line x1="3" y1="6" x2="21" y2="6"></line>
        </svg>
        <span>Admin Panel</span>
      </a>
      <p style="font-size: 0.85rem; color: var(--text-muted);">Masuk untuk mengelola katalog produk</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error">
        <?= sanitize($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="/admin/login.php" id="admin-login-form">
      <?= getCsrfInput() ?>

      <div class="form-group">
        <label for="email" class="form-label">Email Admin</label>
        <input type="email" name="email" id="email" class="form-control" value="<?= sanitize($email) ?>" placeholder="email" required autofocus>
      </div>

      <div class="form-group">
        <label for="password" class="form-label">Password</label>
        <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
      </div>

      <button type="submit" class="btn-primary" style="width: 100%; margin-top: 0.5rem;" id="btn-login-submit">
        Masuk Admin
      </button>
    </form>

    <div style="text-align: center; margin-top: 1.25rem;">
      <a href="/" style="font-size: 0.85rem; color: var(--text-muted); text-decoration: underline;">&larr; Kembali ke Katalog Utama</a>
    </div>
  </div>

</body>
</html>
