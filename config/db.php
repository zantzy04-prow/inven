<?php
// ============================================================
//  config/db.php — Database Connection
//  Inventaris Sekolah | PHP Native + MySQL
//  Railway: Baca dari environment variables otomatis
// ============================================================

// ── Deteksi environment Railway vs lokal ────────────────────
// Railway menyediakan MYSQL_URL atau variabel individual
$isRailway = isset($_ENV['RAILWAY_ENVIRONMENT']) || isset($_SERVER['RAILWAY_ENVIRONMENT']);

if ($isRailway) {
    // Railway: gunakan environment variables yang disediakan otomatis
    define('DB_HOST',    $_ENV['MYSQLHOST']    ?? $_ENV['DB_HOST']    ?? 'localhost');
    define('DB_USER',    $_ENV['MYSQLUSER']    ?? $_ENV['DB_USER']    ?? 'root');
    define('DB_PASS',    $_ENV['MYSQLPASSWORD']?? $_ENV['DB_PASS']    ?? '');
    define('DB_NAME',    $_ENV['MYSQLDATABASE']?? $_ENV['DB_NAME']    ?? 'inventaris_sekolah');
    define('DB_PORT',    $_ENV['MYSQLPORT']    ?? $_ENV['DB_PORT']    ?? '3306');
    define('APP_URL',    $_ENV['APP_URL']      ?? 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
} else {
    // Lokal XAMPP
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'inventaris_sekolah');
    define('DB_PORT', '3306');
    define('APP_URL', 'http://localhost/inventaris');
}

define('DB_CHARSET', 'utf8mb4');
define('APP_NAME',   'Inventaris Lab');

// ── Connect ─────────────────────────────────────────────────
$pdo = null;

try {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
    );

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode([
        'error'   => true,
        'message' => 'Koneksi database gagal. Pastikan database aktif.',
        // Hapus baris 'detail' saat sudah di production
        'detail'  => $e->getMessage()
    ]));
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