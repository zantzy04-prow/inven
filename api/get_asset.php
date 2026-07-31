<?php
// ============================================================
//  api/get_asset.php — API Endpoint for Asset Details
//  Inventaris Sekolah | PHP Native + MySQL
// ============================================================

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Cek apakah ID parameter tersedia
$assetId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($assetId <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Parameter ID aset tidak valid.'
    ]);
    exit;
}

try {
    // 1. Ambil detail data aset utama menggunakan view v_asset_detail
    $stmt = $pdo->prepare("
        SELECT * FROM v_asset_detail 
        WHERE id = ? AND is_active = 1
    ");
    $stmt->execute([$assetId]);
    $asset = $stmt->fetch();

    if (!$asset) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Aset tidak ditemukan atau sudah tidak aktif.'
        ]);
        exit;
    }

    // Format harga ke Rupiah menggunakan helper rupiah() dari db.php
    $asset['harga_formatted'] = rupiah((float)$asset['harga']);

    // 2. Ambil spesifikasi dinamis aset
    $stmtSpecs = $pdo->prepare("
        SELECT spec_key, spec_value 
        FROM asset_specs 
        WHERE asset_id = ? 
        ORDER BY urutan ASC
    ");
    $stmtSpecs->execute([$assetId]);
    $specs = $stmtSpecs->fetchAll();

    // 3. Ambil riwayat log kondisi aset
    $stmtLogs = $pdo->prepare("
        SELECT 
            kl.kondisi_lama, 
            kl.kondisi_baru, 
            kl.catatan, 
            kl.created_at,
            u.nama_lengkap AS diubah_oleh_nama
        FROM kondisi_logs kl
        JOIN users u ON u.id = kl.diubah_oleh
        WHERE kl.asset_id = ?
        ORDER BY kl.created_at DESC
    ");
    $stmtLogs->execute([$assetId]);
    $logs = $stmtLogs->fetchAll();

    // Format log tanggal
    foreach ($logs as &$log) {
        $log['tanggal_formatted'] = date('d M Y H:i', strtotime($log['created_at']));
    }

    // Mengembalikan data lengkap
    echo json_encode([
        'success' => true,
        'data'    => [
            'asset' => $asset,
            'specs' => $specs,
            'logs'  => $logs
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Gagal mengambil data dari database.',
        'detail'  => $e->getMessage() // disembunyikan saat production
    ]);
}
