<?php
// public/admin/background.php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/csrf.php';
require_once __DIR__ . '/../../app/helpers.php';
requireAdmin();

$bgPath = PUBLIC_DIR . '/assets/images/bg.png';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        setFlash('error', 'Gagal upload: Sesi atau token keamanan (CSRF) tidak valid. Silakan muat ulang halaman dan coba lagi.');
    } elseif (!isset($_FILES['background'])) {
        setFlash('error', 'Gagal upload: Tidak ada data file yang dikirimkan.');
    } else {
        $file = $_FILES['background'];
        $errorCode = $file['error'];

        if ($errorCode !== UPLOAD_ERR_OK) {
            switch ($errorCode) {
                case UPLOAD_ERR_INI_SIZE:
                    $maxIni = ini_get('upload_max_filesize');
                    setFlash('error', "Gagal upload: Ukuran file melebihi batas konfigurasi PHP server ({$maxIni}).");
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    setFlash('error', 'Gagal upload: Ukuran file melebihi batas maksimum form HTML.');
                    break;
                case UPLOAD_ERR_PARTIAL:
                    setFlash('error', 'Gagal upload: File hanya terunggah sebagian. Silakan periksa koneksi dan coba lagi.');
                    break;
                case UPLOAD_ERR_NO_FILE:
                    setFlash('error', 'Gagal upload: Belum ada file gambar yang dipilih.');
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    setFlash('error', 'Gagal upload: Folder sementara (tmp) server tidak ditemukan.');
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    setFlash('error', 'Gagal upload: Server gagal menulis file ke disk simpan (Permission Error).');
                    break;
                case UPLOAD_ERR_EXTENSION:
                    setFlash('error', 'Gagal upload: Unggahan dihentikan oleh ekstensi PHP server.');
                    break;
                default:
                    setFlash('error', "Gagal upload: Terjadi kesalahan sistem saat mengunggah file (Kode error: {$errorCode}).");
                    break;
            }
        } else {
            $tmp = $file['tmp_name'];
            $fileSize = $file['size'];
            $maxBytes = MAX_UPLOAD_SIZE; // 2 MB

            if (!is_uploaded_file($tmp)) {
                setFlash('error', 'Gagal upload: File temporary yang diunggah tidak valid.');
            } elseif ($fileSize > $maxBytes) {
                $sizeMb = round($fileSize / (1024 * 1024), 2);
                setFlash('error', "Gagal upload: Ukuran file ({$sizeMb} MB) melebihi batas maksimum 2 MB.");
            } else {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $tmp);
                finfo_close($finfo);

                $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
                if (!in_array($mime, $allowedMimes)) {
                    setFlash('error', "Gagal upload: Format file '{$mime}' tidak diizinkan. Hanya file gambar JPG, PNG, atau WebP yang diperbolehkan.");
                } else {
                    $imageInfo = @getimagesize($tmp);
                    if ($imageInfo === false) {
                        setFlash('error', 'Gagal upload: File terdeteksi rusak atau bukan file gambar yang valid.');
                    } else {
                        $imagesDir = PUBLIC_DIR . '/assets/images';
                        if (!is_dir($imagesDir)) {
                            @mkdir($imagesDir, 0755, true);
                        }

                        if (!is_writable($imagesDir)) {
                            setFlash('error', 'Gagal upload: Folder tujuan (`public/assets/images/`) tidak memiliki izin tulis (Permission Denied).');
                        } else {
                            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                            $dest = $imagesDir . '/bg.' . $ext;

                            if (move_uploaded_file($tmp, $dest)) {
                                if ($dest !== $bgPath && file_exists($bgPath)) {
                                    @unlink($bgPath);
                                }
                                if ($dest !== $bgPath) {
                                    @rename($dest, $bgPath);
                                }
                                setFlash('success', 'Gambar background berhasil diperbarui!');
                            } else {
                                setFlash('error', 'Gagal upload: Gagal memindahkan file yang diunggah ke folder tujuan.');
                            }
                        }
                    }
                }
            }
        }
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

$flash = getFlash();
$bgVersion = file_exists($bgPath) ? filemtime($bgPath) : time();
$bgUrl = '/assets/images/bg.png?v=' . $bgVersion;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ubah Background - Admin</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="/assets/js/app.js" defer></script>
    <script src="/assets/js/admin.js" defer></script>
</head>
<body>
<?php renderAdminHeader('background'); ?>

    <main class="main-wrapper" style="max-width:800px; margin:2rem auto; padding:1rem;">
        <?php if ($flash): ?>
            <div class="alert alert-<?= sanitize($flash['type']) ?>" role="alert">
                <?= sanitize($flash['message']) ?>
            </div>
        <?php endif; ?>

        <div class="glass-upload-card">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.5); padding-bottom: 0.75rem;">
                <div>
                    <h1 style="font-size:1.35rem; font-weight:800; color: var(--text-main);">Ubah Background Halaman Publik</h1>
                    <p style="font-size:0.85rem; color: var(--text-muted);">Ganti gambar latar belakang website dengan efek glassmorphic</p>
                </div>
                <span class="product-stock-badge badge-stock-available" style="position: static; font-size: 0.75rem;">JPG, PNG, WebP</span>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <p style="font-size: 0.875rem; font-weight: 600; margin-bottom: 0.5rem; color: var(--text-main);">Background Saat Ini:</p>
                <div style="position: relative; border-radius: var(--radius-md); overflow: hidden; border: 1px solid rgba(255,255,255,0.6); box-shadow: 0 4px 14px rgba(0,0,0,0.08); background: rgba(0,0,0,0.03);">
                    <img src="<?= $bgUrl ?>" alt="Background Saat Ini" style="width:100%; max-height:260px; object-fit:cover; display:block;" />
                </div>
            </div>

            <form method="post" enctype="multipart/form-data" id="bg-upload-form">
                <?= getCsrfInput() ?>
                <div class="form-group">
                    <label class="form-label" for="background" style="font-weight: 700; color: var(--text-main);">
                        Pilih Gambar Background Baru
                    </label>
                    
                    <div class="glass-file-zone" id="drop-zone">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--accent-primary); margin-bottom: 0.5rem;">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                        <p style="font-size: 0.9rem; font-weight: 600; color: var(--text-main); margin-bottom: 0.25rem;" id="file-label-text">
                            Klik atau seret gambar ke sini untuk memilih file
                        </p>
                        <p style="font-size: 0.75rem; color: var(--text-muted);">
                            Format yang didukung: JPG, PNG, WebP (Maksimum 2 MB)
                        </p>
                        <input class="glass-file-input" type="file" name="background" id="background" accept="image/jpeg,image/png,image/webp" required />
                    </div>
                </div>

                <div style="display: flex; gap: 0.75rem; justify-content: flex-end; align-items: center;">
                    <a href="/admin/index.php" class="btn-secondary">Batal</a>
                    <button type="submit" class="btn-primary" id="btn-submit-bg">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        Simpan Background
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
      const fileInput = document.getElementById('background');
      const labelText = document.getElementById('file-label-text');
      const dropZone = document.getElementById('drop-zone');

      if (fileInput && labelText) {
        fileInput.addEventListener('change', function() {
          if (this.files && this.files.length > 0) {
            const file = this.files[0];
            const sizeMb = (file.size / (1024 * 1024)).toFixed(2);
            labelText.innerHTML = '📁 Terpilih: <strong>' + file.name + '</strong> (' + sizeMb + ' MB)';
            if (dropZone) {
              dropZone.style.borderColor = 'var(--accent-primary)';
              dropZone.style.background = 'rgba(239, 246, 255, 0.85)';
            }
          }
        });
      }
    });
    </script>
</body>
</html>

