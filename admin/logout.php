<?php
// ============================================================
//  admin/logout.php — Admin Logout Action
//  Inventaris Sekolah | PHP Native + MySQL
// ============================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Hancurkan session
destroySession();

// Redirect ke halaman login admin
header('Location: ' . APP_URL . '/admin/login.php');
exit;
