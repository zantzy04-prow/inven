<?php
// ============================================================
//  admin/assets/edit.php — Edit School Asset Details
//  Inventaris Sekolah | PHP Native + MySQL
// ============================================================

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';

// Guard: Hanya admin
requireAdmin();

$assetId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($assetId <= 0) {
    header('Location: ' . APP_URL . '/admin/assets/index.php');
    exit;
}

// Fetch asset record
$stmt = $pdo->prepare("SELECT * FROM assets WHERE id = ? AND is_active = 1");
$stmt->execute([$assetId]);
$asset = $stmt->fetch();

if (!$asset) {
    header('Location: ' . APP_URL . '/admin/assets/index.php');
    exit;
}

$pageTitle = 'Edit Aset: ' . htmlspecialchars($asset['kode_aset']);
$activeNav = 'assets';

$errorMsg = '';

// Fetch labs and types for dropdowns
$labs = $pdo->query("SELECT id, nama, kode_lab FROM labs WHERE is_active = 1 ORDER BY nama ASC")->fetchAll();
$types = $pdo->query("SELECT id, nama, kode FROM asset_types ORDER BY nama ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kodeAset = trim($_POST['kode_aset'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $typeId = (int)($_POST['asset_type_id'] ?? 0);
    $labId = (int)($_POST['lab_id'] ?? 0);
    $merk = trim($_POST['merk'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $sn = trim($_POST['serial_number'] ?? '');
    $kondisi = $_POST['kondisi'] ?? 'baik';
    $tahunBeli = (int)($_POST['tahun_beli'] ?? date('Y'));
    $keterangan = trim($_POST['keterangan'] ?? '');
    $csrfToken = $_POST['csrf_token'] ?? '';

    // Validasi
    if (!verifyCsrf($csrfToken)) {
        $errorMsg = 'Token CSRF tidak valid. Silakan coba lagi.';
    } elseif (empty($kodeAset) || empty($nama) || $typeId <= 0 || $labId <= 0) {
        $errorMsg = 'Kode Aset, Nama Aset, Tipe, dan Lokasi Lab wajib diisi.';
    } else {
        try {
            // Cek keunikan kode_aset secara global (abaikan id saat ini)
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM assets WHERE kode_aset = ? AND id != ? AND is_active = 1");
            $stmtCheck->execute([$kodeAset, $assetId]);
            if ($stmtCheck->fetchColumn() > 0) {
                $errorMsg = "Kode Aset '{$kodeAset}' sudah terdaftar pada aset lain. Gunakan kode unik.";
            } else {
                $pdo->beginTransaction();

                $kondisiLama = $asset['kondisi'];

                // Update asset
                $stmtUpdate = $pdo->prepare("
                    UPDATE assets 
                    SET lab_id = ?, asset_type_id = ?, kode_aset = ?, nama = ?, merk = ?, model = ?, serial_number = ?, kondisi = ?, keterangan = ?, tahun_beli = ?
                    WHERE id = ?
                ");
                $stmtUpdate->execute([$labId, $typeId, $kodeAset, $nama, $merk, $model, $sn, $kondisi, $keterangan, $tahunBeli, $assetId]);
                
                // Cek log kondisi jika berubah
                if ($kondisiLama !== $kondisi) {
                    $stmtLog = $pdo->prepare("
                        INSERT INTO kondisi_logs (asset_id, kondisi_lama, kondisi_baru, catatan, diubah_oleh)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $currentAdminId = (int)currentUser()['id'];
                    $stmtLog->execute([$assetId, $kondisiLama, $kondisi, $keterangan ?: 'Diubah via form edit aset.', $currentAdminId]);
                }

                $pdo->commit();
                
                header('Location: ' . APP_URL . '/admin/assets/index.php?msg=updated');
                exit;
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errorMsg = 'Terjadi kesalahan sistem: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="topbar">
  <div class="breadcrumb">
    <svg width="15" height="15" viewBox="0 0 15 15" fill="none" style="opacity: 0.75;">
      <path d="M1.5 6.5L7.5 1.5l6 5V13.5h-4V10h-4v3.5h-4V6.5z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/>
    </svg>
    <a href="<?= APP_URL ?>/index.php">Beranda</a>
    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" style="margin: 0 4px; opacity: 0.5;">
      <path d="M4.5 9l3-3-3-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <a href="<?= APP_URL ?>/admin/index.php">Dashboard Admin</a>
    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" style="margin: 0 4px; opacity: 0.5;">
      <path d="M4.5 9l3-3-3-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <a href="<?= APP_URL ?>/admin/assets/index.php">Kelola Aset</a>
    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" style="margin: 0 4px; opacity: 0.5;">
      <path d="M4.5 9l3-3-3-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <b>Edit Aset</b>
  </div>
</div>

<div class="content" style="max-width: 650px;">

  <div class="page-heading">
    <h1>Ubah Informasi Aset</h1>
    <p>Edit detail spesifikasi dan kondisi aset sekolah. Jika Anda mengubah laboratorium penempatan, posisi koordinat akan otomatis diatur ulang di ruangan baru.</p>
  </div>

  <div class="editor-panel">
    <div class="editor-panel-head">
      <span class="editor-panel-title">Form Data Aset: <?= htmlspecialchars($asset['kode_aset']) ?></span>
    </div>
    <div class="editor-panel-body">
      <?php if (!empty($errorMsg)): ?>
        <div class="login-error-alert" style="margin-bottom:16px;">
          <?= htmlspecialchars($errorMsg) ?>
        </div>
      <?php endif; ?>

      <form action="" method="POST" style="display:flex; flex-direction:column; gap:16px;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
          <div class="form-group">
            <label class="form-label" for="kode_aset">Kode Aset (Unik)</label>
            <input type="text" id="kode_aset" name="kode_aset" class="form-control" value="<?= htmlspecialchars($asset['kode_aset']) ?>" required autofocus>
          </div>

          <div class="form-group">
            <label class="form-label" for="nama">Nama Komponen</label>
            <input type="text" id="nama" name="nama" class="form-control" value="<?= htmlspecialchars($asset['nama']) ?>" required>
          </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
          <div class="form-group">
            <label class="form-label" for="asset_type_id">Jenis Komponen</label>
            <select name="asset_type_id" id="asset_type_id" class="form-control" required>
              <option value="">Pilih Jenis</option>
              <?php foreach ($types as $t): ?>
                <option value="<?= $t['id'] ?>" <?= (int)$asset['asset_type_id'] === (int)$t['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($t['nama']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label" for="lab_id">Laboratorium Penempatan</label>
            <select name="lab_id" id="lab_id" class="form-control" required>
              <option value="">Pilih Laboratorium</option>
              <?php foreach ($labs as $l): ?>
                <option value="<?= $l['id'] ?>" <?= (int)$asset['lab_id'] === (int)$l['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($l['nama']) ?> (<?= htmlspecialchars($l['kode_lab']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
          <div class="form-group">
            <label class="form-label" for="merk">Merk</label>
            <input type="text" id="merk" name="merk" class="form-control" value="<?= htmlspecialchars($asset['merk'] ?? '') ?>">
          </div>

          <div class="form-group">
            <label class="form-label" for="model">Model / Seri</label>
            <input type="text" id="model" name="model" class="form-control" value="<?= htmlspecialchars($asset['model'] ?? '') ?>">
          </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
          <div class="form-group">
            <label class="form-label" for="serial_number">Nomor Serial (S/N)</label>
            <input type="text" id="serial_number" name="serial_number" class="form-control" value="<?= htmlspecialchars($asset['serial_number'] ?? '') ?>">
          </div>

          <div class="form-group">
            <label class="form-label" for="tahun_beli">Tahun Pembelian</label>
            <input type="number" id="tahun_beli" name="tahun_beli" class="form-control" min="2000" max="2100" value="<?= $asset['tahun_beli'] ?>" required>
          </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr; gap:16px;">
          <div class="form-group">
            <label class="form-label" for="kondisi">Kondisi Aset</label>
            <select name="kondisi" id="kondisi" class="form-control" required>
              <option value="baik" <?= $asset['kondisi'] === 'baik' ? 'selected' : '' ?>>Baik (Siap Pakai)</option>
              <option value="maintenance" <?= $asset['kondisi'] === 'maintenance' ? 'selected' : '' ?>>Dalam Pemeliharaan (Maintenance)</option>
              <option value="rusak" <?= $asset['kondisi'] === 'rusak' ? 'selected' : '' ?>>Rusak (Butuh Perbaikan)</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="keterangan">Keterangan Tambahan / Catatan Kerusakan</label>
          <textarea id="keterangan" name="keterangan" class="form-control" rows="3"><?= htmlspecialchars($asset['keterangan'] ?? '') ?></textarea>
        </div>

        <div style="border-top:1px solid var(--border); margin:8px 0;"></div>

        <button type="submit" class="btn btn-dark" style="justify-content:center; padding:12px; font-size:14px; font-weight:600;">
          Simpan Perubahan
        </button>
        <a href="<?= APP_URL ?>/admin/assets/index.php" class="btn" style="justify-content:center; padding:12px; font-size:14px;">
          Batalkan
        </a>
      </form>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
