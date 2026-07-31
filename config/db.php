<?php
// ============================================================
//   config/db.php — CONFIG & DATABASE CONNECTION
// ============================================================

// TAMPILKAN ERROR UNTUK DEBUGGING
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// TIMEZONE & APP CONFIG
date_default_timezone_set('Asia/Jakarta');

if (!defined('APP_URL')) {
    define('APP_URL', getenv('APP_URL') ?: '');
}

if (!defined('APP_NAME')) {
    define('APP_NAME', 'Inventaris Lab');
}

// DATABASE CREDENTIALS (Prioritas Environment Variable Railway)
define('DB_HOST',    getenv('MYSQLHOST')     ?: 'mysql.railway.internal');
define('DB_USER',    getenv('MYSQLUSER')     ?: 'root');
define('DB_PASS',    getenv('MYSQLPASSWORD') ?: 'imwkrkesSQsUTfpBCMTGcayvdInfDtrS');
define('DB_NAME',    getenv('MYSQLDATABASE') ?: 'railway');
define('DB_PORT',    getenv('MYSQLPORT')     ?: '3306');
define('DB_CHARSET', 'utf8mb4');

// ── Connect ─────────────────────────────────────────────────
$pdo = null;

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

} catch (PDOException $e) {
    die("KONEKSI DB GAGAL: " . $e->getMessage());
}

// ── Helper: format rupiah ────────────────────────────────────
function rupiah(float $angka): string {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// ── Helper: kondisi badge class ─────────────────────────────
function kondisiBadge(string $kondisi): string {
    return match($kondisi) {
        'baik'        => 'badge-baik',
        'rusak'       => 'badge-rusak',
        'maintenance' => 'badge-maintenance',
        default       => 'badge-baik',
    };
}

// ── Helper: kondisi label ───────────────────────────────────
function kondisiLabel(string $kondisi): string {
    return match($kondisi) {
        'baik'        => 'Baik',
        'rusak'       => 'Rusak',
        'maintenance' => 'Maintenance',
        default       => 'Tidak Diketahui',
    };
}

// ── Helper: asset icon SVG ──────────────────────────────────
function getAssetIcon(string $kode): string {
    return match(strtoupper($kode)) {
        'PC'  => '<svg class="icon-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>',
        'MSE' => '<svg class="icon-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="7"/><line x1="12" y1="2" x2="12" y2="6"/></svg>',
        'KB'  => '<svg class="icon-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2" ry="2"/><line x1="6" y1="8" x2="6.01" y2="8"/><line x1="10" y1="8" x2="10.01" y2="8"/><line x1="14" y1="8" x2="14.01" y2="8"/><line x1="18" y1="8" x2="18.01" y2="8"/><line x1="6" y1="12" x2="6.01" y2="12"/><line x1="10" y1="12" x2="10.01" y2="12"/><line x1="14" y1="12" x2="14.01" y2="12"/><line x1="18" y1="12" x2="18.01" y2="12"/><line x1="7" y1="16" x2="17" y2="16"/></svg>',
        'TV'  => '<svg class="icon-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="15" rx="2"/><polyline points="17 21 12 18 7 21"/></svg>',
        'PRJ' => '<svg class="icon-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 17H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-1"/><circle cx="12" cy="10" r="3"/><path d="M12 13v4"/></svg>',
        'AC'  => '<svg class="icon-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="8" rx="1"/><path d="M6 14v4M10 14v2M14 14v2M18 14v4M4 11v1M20 11v1"/></svg>',
        'DESK'=> '<svg class="icon-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="3" rx="1"/><path d="M5 10v7M19 10v7M9 10v7"/></svg>',
        'CHR' => '<svg class="icon-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 20v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/><rect x="8" y="6" width="8" height="8" rx="1"/></svg>',
        'TDK' => '<svg class="icon-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="4" rx="1"/><path d="M4 10v7M20 10v7M12 10v4"/></svg>',
        'WBD' => '<svg class="icon-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="1"/><path d="M8 21h8M12 17v4"/></svg>',
        'FAN' => '<svg class="icon-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a4 4 0 0 1 4 4c0 2-2 4-4 4s-4-2-4-4a4 4 0 0 1 4-4z"/><circle cx="12" cy="12" r="2"/><path d="M12 14a4 4 0 0 1 4 4c0 2-2 4-4 4s-4-2-4-4a4 4 0 0 1 4-4z"/></svg>',
        'CAB' => '<svg class="icon-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="2"/><line x1="2" y1="12" x2="22" y2="12"/><circle cx="9" cy="7" r="1"/><circle cx="9" cy="17" r="1"/></svg>',
        default => '<svg class="icon-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>'
    };
}
