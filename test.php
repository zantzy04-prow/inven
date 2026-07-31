<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>1. PHP Jalan!</h1>";

if (extension_loaded('pdo_mysql')) {
    echo "<p style='color:green;'>2. Ekstensi pdo_mysql TERPASANG!</p>";
} else {
    echo "<p style='color:red;'>2. ERROR: Ekstensi pdo_mysql TIDAK TERPASANG di Railway!</p>";
}

try {
    $host = 'mysql.railway.internal';
    $user = 'root';
    $pass = 'imwkrkesSQsUTfpBCMTGcayvdInfDtrS';
    $db   = 'railway';
    
    $pdo = new PDO("mysql:host=$host;port=3306;dbname=$db;charset=utf8mb4", $user, $pass);
    echo "<h2 style='color:green;'>3. KONEKSI DATABASE BERHASIL COK!</h2>";
} catch (Exception $e) {
    echo "<h2 style='color:red;'>3. KONEKSI GAGAL: " . $e->getMessage() . "</h2>";
}
