<?php
// ============================================================
//  admin/assets/create.php — Create New Asset
//  Inventaris Sekolah | PHP Native + MySQL
// ============================================================

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';

// Guard: Hanya admin
requireAdmin();

$pageTitle = 'Tambah Aset Baru';
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
            // Cek keunikan kode_aset secara global
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM assets WHERE kode_aset = ? AND is_active = 1");
            $stmtCheck->execute([$kodeAset]);
            if ($stmtCheck->fetchColumn() > 0) {
                $errorMsg = "Kode Aset '{$kodeAset}' sudah terdaftar. Gunakan kode unik lain.";
            } else {
                // Cari posisi koordinat yang kosong di lab tersebut
                // Kita mulai dari (0,0) lalu coba cari yang kosong
                $stmtCheckPos = $pdo->prepare("SELECT posisi_x, posisi_y FROM assets WHERE lab_id = ? AND is_active = 1");
                $stmtCheckPos->execute([$labId]);
                $occupied = [];
                foreach ($stmtCheckPos->fetchAll() as $pos) {
                    $occupied[$pos['posisi_y']][$pos['posisi_x']] = true;
                }

                // Cari sel kosong pertama
                $posX = 0;
                $posY = 0;
                $found = false;
                
                // Ambil ukuran grid lab
                $stmtLab = $pdo->prepare("SELECT grid_cols, grid_rows FROM labs WHERE id = ?");
                $stmtLab->execute([$labId]);
                $labGrid = $stmtLab->fetch();
                $cols = (int)($labGrid['grid_cols'] ?? 8);
                $rows = (int)($labGrid['grid_rows'] ?? 6);

                for ($y = 0; $y < $rows; $y++) {
                    for ($x = 0; $x < $cols; $x++) {
                        if (!isset($occupied[$y][$x])) {
                            $posX = $x;
                            $posY = $y;
                            $found = true;
                            break 2;
                        }
                    }
                }

                $pdo->beginTransaction();

                // Insert asset
                $stmtInsert = $pdo->prepare("
                    INSERT INTO assets (lab_id, asset_type_id, kode_aset, nama, merk, model, serial_number, kondisi, keterangan, posisi_x, posisi_y, rotasi, tahun_beli)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?)
                ");
                $stmtInsert->execute([$labId, $typeId, $kodeAset, $nama, $merk, $model, $sn, $kondisi, $keterangan, $posX, $posY, $tahunBeli]);
                
                $newAssetId = (int)$pdo->lastInsertId();

                // Insert log kondisi awal
                $stmtLog = $pdo->prepare("
                    INSERT INTO kondisi_logs (asset_id, kondisi_lama, kondisi_baru, catatan, diubah_oleh)
                    VALUES (?, 'baik', ?, ?, ?)
                ");
                $currentAdminId = (int)currentUser()['id'];
                $stmtLog->execute([$newAssetId, $kondisi, 'Pendaftaran aset awal.', $currentAdminId]);

                $pdo->commit();
                
                header('Location: ' . APP_URL . '/admin/assets/index.php?msg=created');
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
    <b>Tambah Aset Baru</b>
  </div>
</div>

<div class="content" style="max-width: 650px;">

  <div class="page-heading">
    <h1>Registrasi Aset Baru</h1>
    <p>Daftarkan perangkat sekolah baru ke database. Aset akan diletakkan di grid kosong pertama pada laboratorium yang dipilih.</p>
  </div>

  <div class="editor-panel">
    <div class="editor-panel-head">
      <span class="editor-panel-title">Form Data Aset</span>
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
            <input type="text" id="kode_aset" name="kode_aset" class="form-control" placeholder="Contoh: PC-E26" value="<?= isset($_POST['kode_aset']) ? htmlspecialchars($_POST['kode_aset']) : '' ?>" required autofocus>
          </div>

          <div class="form-group">
            <label class="form-label" for="nama">Nama Komponen</label>
            <input type="text" id="nama" name="nama" class="form-control" placeholder="Contoh: PC Workstation Core i7" value="<?= isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : '' ?>" required>
          </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
          <div class="form-group">
            <label class="form-label" for="asset_type_id">Jenis Komponen</label>
            <select name="asset_type_id" id="asset_type_id" class="form-control" required>
              <option value="">Pilih Jenis</option>
              <?php foreach ($types as $t): ?>
                <option value="<?= $t['id'] ?>" <?= isset($_POST['asset_type_id']) && (int)$_POST['asset_type_id'] === (int)$t['id'] ? 'selected' : '' ?>>
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
                <option value="<?= $l['id'] ?>" <?= isset($_POST['lab_id']) && (int)$_POST['lab_id'] === (int)$l['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($l['nama']) ?> (<?= htmlspecialchars($l['kode_lab']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
          <div class="form-group">
            <label class="form-label" for="merk">Merk</label>
            <input type="text" id="merk" name="merk" class="form-control" placeholder="Contoh: ASUS, LG, Panasonic" value="<?= isset($_POST['merk']) ? htmlspecialchars($_POST['merk']) : '' ?>">
          </div>

          <div class="form-group">
            <label class="form-label" for="model">Model / Seri</label>
            <input type="text" id="model" name="model" class="form-control" placeholder="Contoh: ExpertBook B1400" value="<?= isset($_POST['model']) ? htmlspecialchars($_POST['model']) : '' ?>">
          </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
          <div class="form-group">
            <label class="form-label" for="serial_number">Nomor Serial (S/N)</label>
            <input type="text" id="serial_number" name="serial_number" class="form-control" placeholder="Nomor seri manufaktur..." value="<?= isset($_POST['serial_number']) ? htmlspecialchars($_POST['serial_number']) : '' ?>">
          </div>

          <div class="form-group">
            <label class="form-label" for="tahun_beli">Tahun Pembelian</label>
            <input type="number" id="tahun_beli" name="tahun_beli" class="form-control" min="2000" max="2100" value="<?= isset($_POST['tahun_beli']) ? (int)$_POST['tahun_beli'] : date('Y') ?>" required>
          </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr; gap:16px;">
          <div class="form-group">
            <label class="form-label" for="kondisi">Kondisi Awal</label>
            <select name="kondisi" id="kondisi" class="form-control" required>
              <option value="baik" <?= isset($_POST['kondisi']) && $_POST['kondisi'] === 'baik' ? 'selected' : '' ?>>Baik (Siap Pakai)</option>
              <option value="maintenance" <?= isset($_POST['kondisi']) && $_POST['kondisi'] === 'maintenance' ? 'selected' : '' ?>>Dalam Pemeliharaan (Maintenance)</option>
              <option value="rusak" <?= isset($_POST['kondisi']) && $_POST['kondisi'] === 'rusak' ? 'selected' : '' ?>>Rusak (Butuh Perbaikan)</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="keterangan">Keterangan Tambahan / Catatan Kerusakan</label>
          <textarea id="keterangan" name="keterangan" class="form-control" rows="3" placeholder="Tuliskan catatan detail aset di sini jika ada..."><?= isset($_POST['keterangan']) ? htmlspecialchars($_POST['keterangan']) : '' ?></textarea>
        </div>

        <div style="border-top:1px solid var(--border); margin:8px 0;"></div>

        <button type="submit" class="btn btn-dark" style="justify-content:center; padding:12px; font-size:14px; font-weight:600;">
          Simpan Data Aset
        </button>
        <a href="<?= APP_URL ?>/admin/assets/index.php" class="btn" style="justify-content:center; padding:12px; font-size:14px;">
          Batalkan
        </a>
      </form>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
