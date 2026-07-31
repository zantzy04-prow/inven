<?php
// ============================================================
//  admin/assets/index.php — List & Manage School Assets
//  Inventaris Sekolah | PHP Native + MySQL
// ============================================================

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';

// Guard: Hanya admin
requireAdmin();

// Handle Delete Action
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $deleteId = (int)($_GET['id'] ?? 0);
    if ($deleteId > 0) {
        $stmtDel = $pdo->prepare("UPDATE assets SET is_active = 0 WHERE id = ?");
        $stmtDel->execute([$deleteId]);
        header('Location: ' . APP_URL . '/admin/assets/index.php?msg=deleted');
        exit;
    }
}

$pageTitle = 'Kelola Aset Sekolah';
$activeNav = 'assets';

// Fetch filter parameters
$filterLab = isset($_GET['lab_id']) ? (int)$_GET['lab_id'] : 0;
$filterType = isset($_GET['type_id']) ? (int)$_GET['type_id'] : 0;
$filterKondisi = isset($_GET['kondisi']) ? trim($_GET['kondisi']) : '';

// Query dynamic labs and types for filter dropdowns
$labs = $pdo->query("SELECT id, nama, kode_lab FROM labs WHERE is_active = 1 ORDER BY nama ASC")->fetchAll();
$types = $pdo->query("SELECT id, nama, kode FROM asset_types ORDER BY nama ASC")->fetchAll();

// Build dynamic WHERE clauses
$where = ["a.is_active = 1"];
$params = [];

if ($filterLab > 0) {
    $where[] = "a.lab_id = ?";
    $params[] = $filterLab;
}
if ($filterType > 0) {
    $where[] = "a.asset_type_id = ?";
    $params[] = $filterType;
}
if (!empty($filterKondisi)) {
    $where[] = "a.kondisi = ?";
    $params[] = $filterKondisi;
}

$whereClause = implode(" AND ", $where);

// Fetch assets with joins
$stmtAssets = $pdo->prepare("
    SELECT a.*, l.nama AS nama_lab, l.kode_lab, at.nama AS nama_tipe, at.kode AS tipe_kode, at.icon AS tipe_icon
    FROM assets a
    JOIN labs l ON l.id = a.lab_id
    JOIN asset_types at ON at.id = a.asset_type_id
    WHERE {$whereClause}
    ORDER BY a.kode_aset ASC
");
$stmtAssets->execute($params);
$assets = $stmtAssets->fetchAll();

$msg = $_GET['msg'] ?? '';
$alertMsg = '';
$alertClass = '';

if ($msg === 'deleted') {
    $alertMsg = 'Aset berhasil dinonaktifkan / dihapus dari sistem.';
    $alertClass = 'login-success-alert';
} elseif ($msg === 'created') {
    $alertMsg = 'Aset baru berhasil didaftarkan!';
    $alertClass = 'login-success-alert';
} elseif ($msg === 'updated') {
    $alertMsg = 'Detail aset berhasil diperbarui!';
    $alertClass = 'login-success-alert';
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
    <b>Kelola Aset</b>
  </div>
  <div class="topbar-right">
    <a href="<?= APP_URL ?>/admin/assets/create.php" class="btn btn-dark">
      <svg width="12" height="12" viewBox="0 0 12 12" fill="none" style="margin-right: 4px;">
        <path d="M6 1v10M1 6h10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
      </svg>
      Tambah Aset Baru
    </a>
  </div>
</div>

<div class="content">

  <div class="page-heading">
    <h1>Manajemen Aset Sekolah</h1>
    <p>Pantau seluruh perangkat sekolah, filter berdasarkan ruangan/tipe, dan perbarui log status pemeliharaan secara berkala.</p>
  </div>

  <?php if (!empty($alertMsg)): ?>
    <div class="<?= $alertClass ?>" style="margin-bottom: 20px;">
      <?= htmlspecialchars($alertMsg) ?>
    </div>
  <?php endif; ?>

  <!-- Filters Row -->
  <div class="panel" style="margin-bottom: 20px; border-color: var(--border);">
    <div class="panel-body" style="padding: 16px;">
      <form method="GET" action="" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) 120px; gap: 14px; align-items: end;">
        
        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label" for="lab_id" style="font-size: 11px;">Ruang Laboratorium</label>
          <select name="lab_id" id="lab_id" class="form-control">
            <option value="0">Semua Ruangan</option>
            <?php foreach ($labs as $l): ?>
              <option value="<?= $l['id'] ?>" <?= $filterLab === (int)$l['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($l['nama']) ?> (<?= htmlspecialchars($l['kode_lab']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label" for="type_id" style="font-size: 11px;">Jenis Komponen</label>
          <select name="type_id" id="type_id" class="form-control">
            <option value="0">Semua Jenis</option>
            <?php foreach ($types as $t): ?>
              <option value="<?= $t['id'] ?>" <?= $filterType === (int)$t['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($t['nama']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label" for="kondisi" style="font-size: 11px;">Status Kondisi</label>
          <select name="kondisi" id="kondisi" class="form-control">
            <option value="">Semua Kondisi</option>
            <option value="baik" <?= $filterKondisi === 'baik' ? 'selected' : '' ?>>Baik</option>
            <option value="maintenance" <?= $filterKondisi === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
            <option value="rusak" <?= $filterKondisi === 'rusak' ? 'selected' : '' ?>>Rusak</option>
          </select>
        </div>

        <div style="display:flex; gap:6px;">
          <button type="submit" class="btn btn-dark" style="justify-content:center; padding:10px; width:100%;">
            Cari
          </button>
          <?php if ($filterLab > 0 || $filterType > 0 || !empty($filterKondisi)): ?>
            <a href="<?= APP_URL ?>/admin/assets/index.php" class="btn" style="justify-content:center; padding:10px;" title="Reset Filter">
              🔄
            </a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <!-- Assets Table -->
  <div class="lab-list" style="background: var(--bg-surface); padding: 10px 0; box-shadow: var(--shadow-sm); overflow-x: auto;">
    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
      <thead>
        <tr style="border-bottom: 1px solid var(--border); color: var(--text-muted); font-weight: 600;">
          <th style="padding: 14px 20px;">Kode Aset</th>
          <th style="padding: 14px 20px;">Nama Aset</th>
          <th style="padding: 14px 20px;">Tipe</th>
          <th style="padding: 14px 20px;">Lokasi</th>
          <th style="padding: 14px 20px;">Merk / Model</th>
          <th style="padding: 14px 20px;">No Serial</th>
          <th style="padding: 14px 20px;">Kondisi</th>
          <th style="padding: 14px 20px; text-align: right;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($assets)): ?>
          <tr>
            <td colspan="8" style="padding: 40px; text-align: center; color: var(--text-muted);">
              Tidak ada data aset sekolah yang cocok dengan kriteria filter.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($assets as $asset): ?>
            <?php
              $condClass = 'badge-baik';
              $condText = 'Baik';
              if ($asset['kondisi'] === 'rusak') {
                  $condClass = 'badge-rusak';
                  $condText = 'Rusak';
              } elseif ($asset['kondisi'] === 'maintenance') {
                  $condClass = 'badge-maint';
                  $condText = 'Maintenance';
              }
            ?>
            <tr style="border-bottom: 1px solid var(--bg-subtle);" class="panel-item-row-tr">
              <td style="padding: 14px 20px; font-weight: 600; color: var(--text);"><?= htmlspecialchars($asset['kode_aset']) ?></td>
              <td style="padding: 14px 20px; font-weight: 500; color: var(--text);"><?= htmlspecialchars($asset['nama']) ?></td>
              <td style="padding: 14px 20px; color: var(--text-muted);">
                <span style="margin-right: 4px;"><?= htmlspecialchars($asset['tipe_icon']) ?></span>
                <?= htmlspecialchars($asset['nama_tipe']) ?>
              </td>
              <td style="padding: 14px 20px; color: var(--text-muted);"><?= htmlspecialchars($asset['nama_lab']) ?></td>
              <td style="padding: 14px 20px; color: var(--text-muted);">
                <?= htmlspecialchars($asset['merk'] ?: '−') ?> <?= htmlspecialchars($asset['model'] ? ' / ' . $asset['model'] : '') ?>
              </td>
              <td style="padding: 14px 20px; color: var(--text-muted); font-family: monospace; font-size:12px;">
                <?= htmlspecialchars($asset['serial_number'] ?: '−') ?>
              </td>
              <td style="padding: 14px 20px;">
                <span class="badge <?= $condClass ?>"><?= $condText ?></span>
              </td>
              <td style="padding: 14px 20px; text-align: right; display: flex; gap: 6px; justify-content: flex-end;">
                <a href="<?= APP_URL ?>/admin/assets/update_kondisi.php?id=<?= $asset['id'] ?>" class="btn" style="padding: 6px 10px; font-size: 11px;" title="Update Status Kondisi">
                  🔧 Status
                </a>
                <a href="<?= APP_URL ?>/admin/assets/edit.php?id=<?= $asset['id'] ?>" class="btn" style="padding: 6px 10px; font-size: 11px;">
                  ✏️ Edit
                </a>
                <a href="<?= APP_URL ?>/admin/assets/index.php?action=delete&id=<?= $asset['id'] ?>" class="btn" style="padding: 6px 10px; font-size: 11px; background:#fee2e2; border-color:#fca5a5; color:var(--red);" onclick="return confirm('Apakah Anda yakin ingin menghapus data aset ini?')">
                  🗑️ Hapus
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>

<style>
.panel-item-row-tr {
  transition: background-color 200ms ease;
}
.panel-item-row-tr:hover {
  background-color: #fafaf9;
}
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
