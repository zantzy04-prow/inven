<?php
// TAMPILKAN ERROR BILA ADA CRASH
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('Asia/Jakarta');

if (!defined('APP_URL')) {
    define('APP_URL', getenv('APP_URL') ?: '');
}
if (!defined('APP_NAME')) {
    define('APP_NAME', 'Inventaris Lab');
}

// AMBIL DARI ENV RAILWAY ATAU HARDCODE FALLBACK
define('DB_HOST',    getenv('MYSQLHOST')     ?: 'mysql.railway.internal');
define('DB_USER',    getenv('MYSQLUSER')     ?: 'root');
define('DB_PASS',    getenv('MYSQLPASSWORD') ?: 'imwkrkesSQsUTfpBCMTGcayvdInfDtrS');
define('DB_NAME',    getenv('MYSQLDATABASE') ?: 'railway');
define('DB_PORT',    getenv('MYSQLPORT')     ?: '3306');
define('DB_CHARSET', 'utf8mb4');

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

function rupiah(float $angka): string {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function kondisiBadge(string $kondisi): string {
    return match($kondisi) {
        'baik'        => 'badge-baik',
        'rusak'       => 'badge-rusak',
        'maintenance' => 'badge-maintenance',
        default       => 'badge-baik',
    };
}

function kondisiLabel(string $kondisi): string {
    return match($kondisi) {
        'baik'        => 'Baik',
        'rusak'       => 'Rusak',
        'maintenance' => 'Maintenance',
        default       => 'Tidak Diketahui',
    };
}
