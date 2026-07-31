<?php
// ============================================================
//  admin/login.php — Admin Login Screen
//  Inventaris Sekolah | PHP Native + MySQL
// ============================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Jika user sudah login, arahkan ke dashboard admin
if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/admin/index.php');
    exit;
}

$errorMsg = '';
$errParam = isset($_GET['err']) ? $_GET['err'] : '';

// Handle parameter error dari guard
if ($errParam === 'forbidden') {
    $errorMsg = 'Akses ditolak. Silakan login sebagai administrator.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $csrfToken = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';

    // Validate CSRF
    if (!verifyCsrf($csrfToken)) {
        $errorMsg = 'Token CSRF tidak valid. Silakan coba lagi.';
    } elseif (empty($username) || empty($password)) {
        $errorMsg = 'Username dan password wajib diisi.';
    } else {
        try {
            // Query user
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            // Verifikasi password (default password dummy = "password" Laravel bcrypt)
            if ($user && password_verify($password, $user['password_hash'])) {
                // Set Session
                setUserSession($user);
                
                // Redirect berdasarkan role
                if ($user['role'] === 'admin') {
                    header('Location: ' . APP_URL . '/admin/index.php');
                } else {
                    // Viewer login, redirect ke halaman utama
                    header('Location: ' . APP_URL . '/index.php');
                }
                exit;
            } else {
                $errorMsg = 'Username atau password salah.';
            }
        } catch (PDOException $e) {
            $errorMsg = 'Kesalahan sistem database. Silakan coba lagi nanti.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Admin — <?= APP_NAME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body class="login-body">

  <div class="login-card">
    <div class="login-header">
      <div class="login-brand-icon">
        <!-- SVG Brand Icon -->
        <svg width="22" height="22" viewBox="0 0 15 15" fill="none">
          <rect x=".8" y="2" width="13.4" height="9" rx="1.3" stroke="white" stroke-width="1.3"/>
          <path d="M4.5 11v1.8M10.5 11v1.8M3 12.8h9" stroke="white" stroke-width="1.3" stroke-linecap="round"/>
          <rect x="2.5" y="3.8" width="2" height="1.6" rx=".3" fill="white" opacity=".75"/>
          <rect x="6.5" y="3.8" width="2" height="1.6" rx=".3" fill="white" opacity=".75"/>
          <rect x="10.5" y="3.8" width="2" height="1.6" rx=".3" fill="white" opacity=".75"/>
        </svg>
      </div>
      <h1 class="login-title">Masuk ke Sistem</h1>
      <p class="login-subtitle">Otentikasi Dasbor Inventaris Sekolah</p>
    </div>

    <form class="login-form" action="" method="POST">
      <!-- CSRF Token -->
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()); ?>">

      <?php if (!empty($errorMsg)): ?>
        <div class="login-error-alert">
          <?= htmlspecialchars($errorMsg) ?>
        </div>
      <?php endif; ?>

      <div class="form-group">
        <label for="username" class="form-label">Username</label>
        <input type="text" 
               id="username" 
               name="username" 
               class="form-control" 
               placeholder="Masukkan username" 
               value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
               required 
               autofocus>
      </div>

      <div class="form-group">
        <label for="password" class="form-label">Password</label>
        <input type="password" 
               id="password" 
               name="password" 
               class="form-control" 
               placeholder="Masukkan password" 
               required>
      </div>

      <button type="submit" class="btn btn-dark" style="width: 100%; justify-content: center; padding: 10px; font-size: 14px; margin-top: 8px;">
        Masuk
      </button>
      
      <a href="<?= APP_URL ?>/index.php" class="btn" style="width: 100%; justify-content: center; padding: 10px; font-size: 14px;">
        Kembali ke Beranda
      </a>
    </form>
  </div>

</body>
</html>
