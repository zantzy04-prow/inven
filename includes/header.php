<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$pageTitle = $pageTitle ?? APP_NAME;
$activeNav = $activeNav ?? 'home';
$user      = isLoggedIn() ? currentUser() : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle) ?> — <?= APP_NAME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css" />
</head>
<body>

<aside class="sidebar">
  <!-- Brand -->
  <div class="sidebar-brand">
    <div class="brand-icon">
      <svg width="15" height="15" viewBox="0 0 15 15" fill="none">
        <rect x=".8" y="2" width="13.4" height="9" rx="1.3" stroke="white" stroke-width="1.3"/>
        <path d="M4.5 11v1.8M10.5 11v1.8M3 12.8h9" stroke="white" stroke-width="1.3" stroke-linecap="round"/>
        <rect x="2.5" y="3.8" width="2" height="1.6" rx=".3" fill="white" opacity=".75"/>
        <rect x="6.5" y="3.8" width="2" height="1.6" rx=".3" fill="white" opacity=".75"/>
        <rect x="10.5" y="3.8" width="2" height="1.6" rx=".3" fill="white" opacity=".75"/>
        <rect x="2.5" y="6.6" width="2" height="1.6" rx=".3" fill="white" opacity=".3"/>
        <rect x="6.5" y="6.6" width="2" height="1.6" rx=".3" fill="white" opacity=".3"/>
        <rect x="10.5" y="6.6" width="2" height="1.6" rx=".3" fill="white" opacity=".3"/>
      </svg>
    </div>
    <div>
      <div class="brand-name"><?= APP_NAME ?></div>
      <div class="brand-sub">SMKS Poncol Jakarta</div>
    </div>
  </div>

  <!-- Nav -->
  <nav class="sidebar-nav">
    <div class="nav-label">Menu</div>
    <a href="<?= APP_URL ?>/index.php" class="nav-item <?= $activeNav==='home' ? 'active':'' ?>">
      <svg width="15" height="15" viewBox="0 0 15 15" fill="none">
        <path d="M1.5 6.5L7.5 1.5l6 5V13.5h-4V10h-4v3.5h-4V6.5z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/>
      </svg>
      Beranda
    </a>
    <a href="<?= APP_URL ?>/index.php" class="nav-item <?= $activeNav==='labs' ? 'active':'' ?>">
      <svg width="15" height="15" viewBox="0 0 15 15" fill="none">
        <rect x=".8" y="2.5" width="13.4" height="9" rx="1.3" stroke="currentColor" stroke-width="1.3"/>
        <path d="M4.5 11.5V13M10.5 11.5V13M3 13h9" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
      </svg>
      Laboratorium
    </a>

    <?php if (isLoggedIn()): ?>
    <div class="nav-label" style="margin-top:8px">Admin</div>
    <a href="<?= APP_URL ?>/admin/index.php" class="nav-item <?= $activeNav==='dashboard' ? 'active':'' ?>">
      <svg width="15" height="15" viewBox="0 0 15 15" fill="none">
        <rect x="1" y="1" width="5.5" height="5.5" rx="1" stroke="currentColor" stroke-width="1.3"/>
        <rect x="8.5" y="1" width="5.5" height="5.5" rx="1" stroke="currentColor" stroke-width="1.3"/>
        <rect x="1" y="8.5" width="5.5" height="5.5" rx="1" stroke="currentColor" stroke-width="1.3"/>
        <rect x="8.5" y="8.5" width="5.5" height="5.5" rx="1" stroke="currentColor" stroke-width="1.3"/>
      </svg>
      Dashboard
    </a>
    <a href="<?= APP_URL ?>/admin/assets/index.php" class="nav-item <?= $activeNav==='assets' ? 'active':'' ?>">
      <svg width="15" height="15" viewBox="0 0 15 15" fill="none">
        <rect x="1" y="1" width="13" height="13" rx="1.5" stroke="currentColor" stroke-width="1.3"/>
        <path d="M4 5h7M4 7.5h7M4 10h4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
      </svg>
      Data Aset
    </a>
    <?php endif; ?>
  </nav>

  <!-- Bottom -->
  <div class="sidebar-bottom">
    <?php if ($user): ?>
    <div class="nav-item" style="cursor:default">
      <svg width="15" height="15" viewBox="0 0 15 15" fill="none">
        <circle cx="7.5" cy="5" r="2.5" stroke="currentColor" stroke-width="1.3"/>
        <path d="M2 13c0-3 2.5-4.5 5.5-4.5S13 10 13 13" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
      </svg>
      <span style="font-size:13px; color:var(--text);"><?= htmlspecialchars($user['nama']) ?></span>
    </div>
    <a href="<?= APP_URL ?>/admin/logout.php" class="nav-item" style="color:var(--red)">
      <svg width="15" height="15" viewBox="0 0 15 15" fill="none">
        <path d="M5.5 1.5H2a1 1 0 00-1 1v10a1 1 0 001 1h3.5M10 10.5l3-3-3-3M13 7.5H6" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      Keluar
    </a>
    <?php else: ?>
    <a href="<?= APP_URL ?>/admin/login.php" class="nav-item">
      <svg width="15" height="15" viewBox="0 0 15 15" fill="none">
        <path d="M9.5 1.5H13a1 1 0 011 1v10a1 1 0 01-1 1H9.5M6 10.5l3-3-3-3M9 7.5H1.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      Login Admin
    </a>
    <?php endif; ?>
  </div>
</aside>

<div class="page-wrapper">