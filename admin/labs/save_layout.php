<?php
// ============================================================
//  admin/labs/save_layout.php — API Save Room Layout
//  Inventaris Sekolah | PHP Native + MySQL
// ============================================================

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

// Proteksi API: Hanya admin yang diperbolehkan
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => true, 'message' => 'Akses ditolak.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => true, 'message' => 'Metode HTTP tidak diizinkan.']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => true, 'message' => 'Payload JSON tidak valid.']);
    exit;
}

$csrfToken = $input['csrf_token'] ?? '';
if (!verifyCsrf($csrfToken)) {
    http_response_code(400);
    echo json_encode(['error' => true, 'message' => 'Token CSRF kadaluarsa atau tidak valid.']);
    exit;
}

$labId = (int)($input['id'] ?? 0);
$nama = trim($input['nama'] ?? '');
$kodeLab = trim($input['kode_lab'] ?? '');
$lantai = (int)($input['lantai'] ?? 1);
$kapasitas = (int)($input['kapasitas'] ?? 30);
$deskripsi = trim($input['deskripsi'] ?? '');
$gridCols = max(6, (int)($input['grid_cols'] ?? 8));
$gridRows = max(6, (int)($input['grid_rows'] ?? 6));
$pintuPosisi = trim($input['pintu_posisi'] ?? 'kiri-bawah');
$assetsPayload = $input['assets'] ?? [];

if ($labId <= 0 || empty($nama) || empty($kodeLab)) {
    http_response_code(400);
    echo json_encode(['error' => true, 'message' => 'Informasi laboratorium wajib diisi lengkap.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Update data laboratorium
    $stmtLab = $pdo->prepare("
        UPDATE labs 
        SET nama = ?, kode_lab = ?, lantai = ?, kapasitas = ?, deskripsi = ?, grid_cols = ?, grid_rows = ?, pintu_posisi = ?
        WHERE id = ? AND is_active = 1
    ");
    $stmtLab->execute([$nama, $kodeLab, $lantai, $kapasitas, $deskripsi, $gridCols, $gridRows, $pintuPosisi, $labId]);

    // Ambil daftar aset aktif di DB sebelum update
    $stmtCurrent = $pdo->prepare("SELECT id, kondisi FROM assets WHERE lab_id = ? AND is_active = 1");
    $stmtCurrent->execute([$labId]);
    $dbAssets = $stmtCurrent->fetchAll();
    $dbAssetMap = [];
    foreach ($dbAssets as $dbA) {
        $dbAssetMap[(int)$dbA['id']] = $dbA['kondisi'];
    }

    $activeAssetIds = [];

    // Map tipe kode ke tipe ID
    $stmtTypes = $pdo->query("SELECT id, kode FROM asset_types");
    $typeMap = [];
    foreach ($stmtTypes->fetchAll() as $t) {
        $typeMap[$t['kode']] = (int)$t['id'];
    }

    $currentAdminId = (int)currentUser()['id'];

    // 2. Simpan / Update Aset
    foreach ($assetsPayload as $asset) {
        $id = $asset['id'] ?? '';
        $tipeKode = $asset['tipe_kode'] ?? '';
        $kodeAset = trim($asset['kode_aset'] ?? '');
        $namaAset = trim($asset['nama'] ?? '');
        $merk = trim($asset['merk'] ?? '');
        $model = trim($asset['model'] ?? '');
        $serialNumber = trim($asset['serial_number'] ?? '');
        $kondisi = $asset['kondisi'] ?? 'baik';
        $keterangan = trim($asset['keterangan'] ?? '');
        $posX = (int)($asset['posisi_x'] ?? 0);
        $posY = (int)($asset['posisi_y'] ?? 0);
        $rotasi = (int)($asset['rotasi'] ?? 0);

        if (empty($kodeAset) || empty($namaAset) || empty($tipeKode)) {
            continue; // Skip data tidak valid
        }

        $typeId = $typeMap[$tipeKode] ?? null;
        if (!$typeId) {
            continue; // Tipe tidak dikenal
        }

        // Cek jika ID numerik (aset lama)
        if (is_numeric($id)) {
            $dbId = (int)$id;
            $activeAssetIds[] = $dbId;

            // Dapatkan kondisi lama
            $kondisiLama = $dbAssetMap[$dbId] ?? 'baik';

            // Update aset
            $stmtUpdate = $pdo->prepare("
                UPDATE assets 
                SET kode_aset = ?, nama = ?, merk = ?, model = ?, serial_number = ?, kondisi = ?, keterangan = ?, posisi_x = ?, posisi_y = ?, rotasi = ?
                WHERE id = ? AND lab_id = ?
            ");
            $stmtUpdate->execute([$kodeAset, $namaAset, $merk, $model, $serialNumber, $kondisi, $keterangan, $posX, $posY, $rotasi, $dbId, $labId]);

            // Jika kondisi berubah, masukkan ke kondisi_logs
            if ($kondisiLama !== $kondisi) {
                $stmtLog = $pdo->prepare("
                    INSERT INTO kondisi_logs (asset_id, kondisi_lama, kondisi_baru, catatan, diubah_oleh)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmtLog->execute([$dbId, $kondisiLama, $kondisi, $keterangan ?: 'Diubah via layout editor.', $currentAdminId]);
            }

        } else {
            // Aset Baru (temp_xxx)
            // Cek keunikan kode_aset baru secara global
            $stmtCheckUnique = $pdo->prepare("SELECT COUNT(*) FROM assets WHERE kode_aset = ? AND is_active = 1");
            $stmtCheckUnique->execute([$kodeAset]);
            if ($stmtCheckUnique->fetchColumn() > 0) {
                throw new Exception("Kode aset '{$kodeAset}' sudah terdaftar di sistem. Gunakan kode lain.");
            }

            // Insert baru
            $stmtInsert = $pdo->prepare("
                INSERT INTO assets (lab_id, asset_type_id, kode_aset, nama, merk, model, serial_number, kondisi, keterangan, posisi_x, posisi_y, rotasi, tahun_beli)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            // default tahun_beli = tahun sekarang
            $tahunBeli = date('Y');
            $stmtInsert->execute([$labId, $typeId, $kodeAset, $namaAset, $merk, $model, $serialNumber, $kondisi, $keterangan, $posX, $posY, $rotasi, $tahunBeli]);
            
            $newId = (int)$pdo->lastInsertId();
            $activeAssetIds[] = $newId;

            // Buat log kondisi awal
            $stmtLog = $pdo->prepare("
                INSERT INTO kondisi_logs (asset_id, kondisi_lama, kondisi_baru, catatan, diubah_oleh)
                VALUES (?, 'baik', ?, ?, ?)
            ");
            $stmtLog->execute([$newId, $kondisi, 'Inisialisasi aset baru via tata letak editor.', $currentAdminId]);
        }
    }

    // 3. Deaktivasi aset yang tidak dikirim di payload (terhapus)
    foreach ($dbAssetMap as $dbId => $k) {
        if (!in_array($dbId, $activeAssetIds)) {
            $stmtDelete = $pdo->prepare("UPDATE assets SET is_active = 0 WHERE id = ?");
            $stmtDelete->execute([$dbId]);
        }
    }

    $pdo->commit();
    echo json_encode(['error' => false, 'message' => 'Tata letak dan data laboratorium berhasil disimpan!']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
}
