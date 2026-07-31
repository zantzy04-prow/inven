<?php
// ============================================================
//  admin/assets/update_kondisi.php — Update Asset Condition Log
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

// Fetch asset and joins
$stmt = $pdo->prepare("
    SELECT a.*, l.nama AS nama_lab, l.kode_lab, at.nama AS nama_tipe
    FROM assets a
    JOIN labs l ON l.id = a.lab_id
    JOIN asset_types at ON at.id = a.asset_type_id
    WHERE a.id = ? AND a.is_active = 1
");
$stmt->execute([$assetId]);
$asset = $stmt->fetch();

if (!$asset) {
    header('Location: ' . APP_URL . '/admin/assets/index.php');
    exit;
}

$pageTitle = 'Perbarui Kondisi Aset';
$activeNav = 'assets';

$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kondisiBaru = $_POST['kondisi_baru'] ?? '';
    $catatan = trim($_POST['catatan'] ?? '');
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!verifyCsrf($csrfToken)) {
        $errorMsg = 'Token CSRF tidak valid. Silakan coba lagi.';
    } elseif (empty($kondisiBaru) || empty($catatan)) {
        $errorMsg = 'Kondisi Baru dan Catatan wajib diisi.';
    } else {
        try {
            $pdo->beginTransaction();

            $kondisiLama = $asset['kondisi'];

            // 1. Update kondisi aset
            $stmtUpdate = $pdo->prepare("UPDATE assets SET kondisi = ?, keterangan = ? WHERE id = ?");
            $stmtUpdate->execute([$kondisiBaru, $catatan, $assetId]);

            // 2. Insert into kondisi_logs
            $stmtLog = $pdo->prepare("
                INSERT INTO kondisi_logs (asset_id, kondisi_lama, kondisi_baru, catatan, diubah_oleh)
                VALUES (?, ?, ?, ?, ?)
            ");
            $currentAdminId = (int)currentUser()['id'];
            $stmtLog->execute([$assetId, $kondisiLama, $kondisiBaru, $catatan, $currentAdminId]);

            $pdo->commit();
            header('Location: ' . APP_URL . '/admin/assets/index.php?msg=updated');
            exit;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errorMsg = 'Gagal menyimpan data: ' . $e->getMessage();
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
    <b>Perbarui Kondisi</b>
  </div>
</div>

<div class="content" style="max-width: 600px;">

  <div class="page-heading">
    <h1>Perbarui Status Kondisi Aset</h1>
    <p>Ubah status kelayakan fisik barang dan berikan catatan pemeliharaan/kerusakan untuk dicatat ke dalam log history sistem secara transparan.</p>
  </div>

  <div class="editor-panel">
    <div class="editor-panel-head">
      <span class="editor-panel-title">Pelaporan Pemeliharaan: <?= htmlspecialchars($asset['kode_aset']) ?></span>
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
            <label class="form-label">Kode Aset</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($asset['kode_aset']) ?>" disabled style="background:#f1f5f9; font-weight:600;">
          </div>

          <div class="form-group">
            <label class="form-label">Nama Komponen</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($asset['nama']) ?>" disabled style="background:#f1f5f9; font-weight:600;">
          </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
          <div class="form-group">
            <label class="form-label">Jenis & Lokasi</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($asset['nama_tipe']) ?> @ <?= htmlspecialchars($asset['nama_lab']) ?>" disabled style="background:#f1f5f9;">
          </div>

          <div class="form-group">
            <label class="form-label">Kondisi Saat Ini</label>
            <input type="text" class="form-control" value="<?= strtoupper($asset['kondisi']) ?>" disabled style="background:#f1f5f9; font-weight:600; color:var(--text-muted);">
          </div>
        </div>

        <div style="border-top:1px solid var(--border); margin:6px 0;"></div>

        <div class="form-group">
          <label class="form-label" for="kondisi_baru">Pilih Kondisi Baru</label>
          <select name="kondisi_baru" id="kondisi_baru" class="form-control" required autofocus>
            <option value="">Pilih Status Kelayakan...</option>
            <option value="baik" <?= $asset['kondisi'] === 'baik' ? 'selected' : '' ?>>Baik (Siap Pakai)</option>
            <option value="maintenance" <?= $asset['kondisi'] === 'maintenance' ? 'selected' : '' ?>>Dalam Pemeliharaan (Maintenance)</option>
            <option value="rusak" <?= $asset['kondisi'] === 'rusak' ? 'selected' : '' ?>>Rusak (Butuh Perbaikan)</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="catatan">Catatan / Alasan Perubahan Kondisi</label>
          <textarea id="catatan" name="catatan" class="form-control" rows="3" placeholder="Contoh: Kipas berbunyi bising dan berputar lambat, diganti dinamo baru oleh teknisi." required></textarea>
        </div>

        <div style="border-top:1px solid var(--border); margin:8px 0;"></div>

        <button type="submit" class="btn btn-dark" style="justify-content:center; padding:12px; font-size:14px; font-weight:600;">
          Simpan Log & Perbarui Status
        </button>
        <a href="<?= APP_URL ?>/admin/assets/index.php" class="btn" style="justify-content:center; padding:12px; font-size:14px;">
          Batalkan
        </a>
      </form>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
