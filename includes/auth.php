<?php
// ============================================================
//  includes/auth.php — Session & Auth Guard
// ============================================================

// Pastikan DB & Config ter-load jika auth dipanggil independen
require_once __DIR__ . '/../config/db.php';

if (!defined('APP_URL')) {
    define('APP_URL', getenv('APP_URL') ?: '');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Cek apakah user sudah login ─────────────────────────────
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// ── Cek apakah user adalah admin ────────────────────────────
function isAdmin(): bool {
    return isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'admin';
}

// ── Guard: redirect ke login jika belum login ───────────────
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/admin/login.php');
        exit;
    }
}

// ── Guard: redirect jika bukan admin ────────────────────────
function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . APP_URL . '/admin/login.php?err=forbidden');
        exit;
    }
}

// ── Ambil data user saat ini ─────────────────────────────────
function currentUser(): array {
    return [
        'id'       => $_SESSION['user_id']       ?? null,
        'username' => $_SESSION['user_username'] ?? '',
        'nama'     => $_SESSION['user_nama']     ?? '',
        'role'     => $_SESSION['user_role']     ?? '',
    ];
}

// ── Set session setelah login berhasil ───────────────────────
function setUserSession(array $user): void {
    $_SESSION['user_id']       = $user['id'];
    $_SESSION['user_username'] = $user['username'];
    $_SESSION['user_nama']     = $user['nama_lengkap'];
    $_SESSION['user_role']     = $user['role'];
    session_regenerate_id(true); // cegah session fixation
}

// ── Destroy session (logout) ─────────────────────────────────
function destroySession(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// ── CSRF Token ───────────────────────────────────────────────
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(string $token): bool {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}
