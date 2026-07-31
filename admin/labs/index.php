<?php
// ============================================================
//  admin/labs/index.php — List & Manage Laboratories
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
        try {
            $pdo->beginTransaction();
            
            // Soft delete lab
            $stmtDelLab = $pdo->prepare("UPDATE labs SET is_active = 0 WHERE id = ?");
            $stmtDelLab->execute([$deleteId]);
            
            // Soft delete assets inside the lab
            $stmtDelAssets = $pdo->prepare("UPDATE assets SET is_active = 0 WHERE lab_id = ?");
            $stmtDelAssets->execute([$deleteId]);
            
            $pdo->commit();
            header('Location: ' . APP_URL . '/admin/labs/index.php?msg=deleted');
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            header('Location: ' . APP_URL . '/admin/labs/index.php?msg=error');
            exit;
        }
    }
}

$pageTitle = 'Kelola Laboratorium';
$activeNav = 'labs';

// Fetch all active labs with stats
$stmtLabs = $pdo->query("
    SELECT l.*, 
           (SELECT COUNT(*) FROM assets WHERE lab_id = l.id AND is_active = 1) AS total_assets
    FROM labs l
    WHERE l.is_active = 1
    ORDER BY l.nama ASC
");
$labs = $stmtLabs->fetchAll();

$msg = $_GET['msg'] ?? '';
$alertMsg = '';
$alertClass = '';

if ($msg === 'deleted') {
    $alertMsg = 'Laboratorium beserta seluruh aset di dalamnya berhasil dihapus!';
    $alertClass = 'login-success-alert';
} elseif ($msg === 'error') {
    $alertMsg = 'Gagal menghapus laboratorium. Terjadi kesalahan database.';
    $alertClass = 'login-error-alert';
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
    <b>Kelola Laboratorium</b>
  </div>
  <div class="topbar-right">
    <a href="<?= APP_URL ?>/admin/labs/create.php" class="btn btn-dark">
      <svg width="12" height="12" viewBox="0 0 12 12" fill="none" style="margin-right: 4px;">
        <path d="M6 1v10M1 6h10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
      </svg>
      Tambah Lab Baru
    </a>
  </div>
</div>

<div class="content">

  <div class="page-heading">
    <h1>Manajemen Laboratorium</h1>
    <p>Lihat daftar laboratorium terdaftar, jumlah inventaris, ukuran grid ruangan, dan kelola tata letak denah secara visual.</p>
  </div>

  <?php if (!empty($alertMsg)): ?>
    <div class="<?= $alertClass ?>" style="margin-bottom: 20px;">
      <?= htmlspecialchars($alertMsg) ?>
    </div>
  <?php endif; ?>

  <div class="lab-list" style="background: var(--bg-surface); padding: 10px 0; box-shadow: var(--shadow-sm); overflow-x: auto;">
    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
      <thead>
        <tr style="border-bottom: 1px solid var(--border); color: var(--text-muted); font-weight: 600;">
          <th style="padding: 14px 20px;">Nama Lab</th>
          <th style="padding: 14px 20px;">Kode Lab</th>
          <th style="padding: 14px 20px;">Lantai</th>
          <th style="padding: 14px 20px;">Kapasitas</th>
          <th style="padding: 14px 20px;">Dimensi Grid</th>
          <th style="padding: 14px 20px;">Jumlah Aset</th>
          <th style="padding: 14px 20px; text-align: right;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($labs)): ?>
          <tr>
            <td colspan="7" style="padding: 40px; text-align: center; color: var(--text-muted);">
              Belum ada data laboratorium terdaftar.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($labs as $lab): ?>
            <tr style="border-bottom: 1px solid var(--bg-subtle);" class="panel-item-row-tr">
              <td style="padding: 14px 20px; font-weight: 600; color: var(--text);"><?= htmlspecialchars($lab['nama']) ?></td>
              <td style="padding: 14px 20px; color: var(--text-muted);"><?= htmlspecialchars($lab['kode_lab']) ?></td>
              <td style="padding: 14px 20px; color: var(--text-muted);">Lantai <?= $lab['lantai'] ?></td>
              <td style="padding: 14px 20px; color: var(--text-muted);"><?= $lab['kapasitas'] ?> siswa</td>
              <td style="padding: 14px 20px; font-weight: 500; color: var(--text);"><?= $lab['grid_cols'] ?> &times; <?= $lab['grid_rows'] ?></td>
              <td style="padding: 14px 20px;">
                <span class="badge badge-baik" style="background:#e0f2fe; color:#0369a1; border-color:#bae6fd; font-weight:600;">
                  <?= $lab['total_assets'] ?> unit
                </span>
              </td>
              <td style="padding: 14px 20px; text-align: right; display: flex; gap: 6px; justify-content: flex-end;">
                <a href="<?= APP_URL ?>/admin/labs/edit.php?id=<?= $lab['id'] ?>" class="btn" style="padding: 6px 12px; font-size: 12px;">
                  ✏️ Edit Layout
                </a>
                <a href="<?= APP_URL ?>/admin/labs/index.php?action=delete&id=<?= $lab['id'] ?>" class="btn" style="padding: 6px 12px; font-size: 12px; background:#fee2e2; border-color:#fca5a5; color:var(--red);" onclick="return confirm('Apakah Anda yakin ingin menghapus laboratorium ini beserta seluruh asetnya secara permanen?')">
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
