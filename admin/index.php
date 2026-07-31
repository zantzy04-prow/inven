<?php
// ============================================================
//  admin/index.php — Admin Dashboard Overview
//  Inventaris Sekolah | PHP Native + MySQL
// ============================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Proteksi halaman admin: hanya admin yang boleh masuk
requireAdmin();

$pageTitle = 'Dashboard Admin';
$activeNav = 'dashboard';

// Fetch summary metrics
$stmtStats = $pdo->query("
    SELECT 
        (SELECT COUNT(*) FROM labs WHERE is_active = 1) AS total_labs,
        (SELECT COUNT(*) FROM assets WHERE is_active = 1) AS total_assets,
        (SELECT COUNT(*) FROM assets WHERE kondisi = 'baik' AND is_active = 1) AS kondisi_baik,
        (SELECT COUNT(*) FROM assets WHERE kondisi = 'maintenance' AND is_active = 1) AS kondisi_maintenance,
        (SELECT COUNT(*) FROM assets WHERE kondisi = 'rusak' AND is_active = 1) AS kondisi_rusak
");
$stats = $stmtStats->fetch();

// Fetch 8 latest condition logs with asset & user details
$stmtLogs = $pdo->query("
    SELECT 
        kl.id,
        kl.kondisi_lama,
        kl.kondisi_baru,
        kl.catatan,
        kl.created_at,
        a.kode_aset,
        a.nama AS nama_aset,
        u.nama_lengkap AS nama_admin
    FROM kondisi_logs kl
    JOIN assets a ON a.id = kl.asset_id
    JOIN users u ON u.id = kl.diubah_oleh
    ORDER BY kl.created_at DESC
    LIMIT 8
");
$recentLogs = $stmtLogs->fetchAll();

require_once __DIR__ . '/../includes/header.php';
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
    <b>Dashboard Admin</b>
  </div>
  <div class="topbar-right">
    <div class="page-header-pre" style="margin-bottom: 0; background: #e0f2fe; color: #0284c7; border-color: #bae6fd;">
      <span class="pulse-dot" style="background: #0284c7; box-shadow: 0 0 0 0 rgba(2, 132, 199, 0.4);"></span>
      <span>Sesi Admin Aktif</span>
    </div>
  </div>
</div>

<div class="content">

  <div class="page-heading">
    <h1>Selamat Datang, <?= htmlspecialchars(currentUser()['nama']) ?>!</h1>
    <p>Gunakan panel kontrol ini untuk memantau status fisik lab, mengelola entri aset baru, dan melacak riwayat log perbaikan secara terpusat.</p>
  </div>

  <?php
    $totalKondisi = $stats['kondisi_baik'] + $stats['kondisi_maintenance'] + $stats['kondisi_rusak'];
    $pBaik  = $totalKondisi > 0 ? round($stats['kondisi_baik']        / $totalKondisi * 100) : 0;
    $pRusak = $totalKondisi > 0 ? round($stats['kondisi_rusak']       / $totalKondisi * 100) : 0;
    $pMaint = $totalKondisi > 0 ? round($stats['kondisi_maintenance'] / $totalKondisi * 100) : 0;
  ?>

  <!-- Overview block: 3 kolom sejajar (Meniru Beranda agar Senada) -->
  <div class="overview-block">

    <!-- Kolom 1: Hero total aset -->
    <div class="overview-col overview-col-hero">
      <span class="ov-eyebrow">Total Inventaris</span>
      <div>
        <div class="ov-hero-num"><?= $stats['total_assets'] ?></div>
        <div class="ov-hero-lbl">aset terdaftar di semua lab</div>
      </div>
    </div>

    <div class="overview-divider"></div>

    <!-- Kolom 2: Lab + PC -->
    <div class="overview-col overview-col-stats">
      <span class="ov-eyebrow">Infrastruktur</span>
      <div class="ov-stat-pair">
        <div class="ov-stat">
          <span class="ov-stat-num"><?= $stats['total_labs'] ?></span>
          <span class="ov-stat-lbl">Laboratorium aktif</span>
        </div>
        <div class="ov-stat">
          <span class="ov-stat-num" style="color: var(--amber);"><?= $stats['kondisi_maintenance'] + $stats['kondisi_rusak'] ?></span>
          <span class="ov-stat-lbl">Aset perlu perhatian</span>
        </div>
      </div>
    </div>

    <div class="overview-divider"></div>

    <!-- Kolom 3: Kondisi (donut) -->
    <div class="overview-col overview-col-health">
      <div class="health-label">Kondisi Aset Global</div>

      <?php
        // Donut geometry: r=40, circumference = 2*PI*40 ≈ 251.33
        $C = 2 * M_PI * 40;
        $lenBaik  = $C * $pBaik  / 100;
        $lenMaint = $C * $pMaint / 100;
        $lenRusak = $C * $pRusak / 100;
        // offset kumulatif (mulai dari atas, searah jarum jam)
        $offBaik  = 0;
        $offMaint = $lenBaik;
        $offRusak = $lenBaik + $lenMaint;
      ?>

      <div class="health-body">
        <!-- Donut -->
        <div class="health-donut">
          <svg width="92" height="92" viewBox="0 0 92 92">
            <circle class="donut-track" cx="46" cy="46" r="40" fill="none" stroke-width="10"/>
            <circle class="donut-seg donut-green" cx="46" cy="46" r="40" fill="none" stroke-width="10"
                    stroke-dasharray="<?= $lenBaik ?> <?= $C ?>"
                    stroke-dashoffset="<?= -$offBaik ?>" stroke-linecap="butt"/>
            <circle class="donut-seg donut-amber" cx="46" cy="46" r="40" fill="none" stroke-width="10"
                    stroke-dasharray="<?= $lenMaint ?> <?= $C ?>"
                    stroke-dashoffset="<?= -$offMaint ?>" stroke-linecap="butt"/>
            <circle class="donut-seg donut-red" cx="46" cy="46" r="40" fill="none" stroke-width="10"
                    stroke-dasharray="<?= $lenRusak ?> <?= $C ?>"
                    stroke-dashoffset="<?= -$offRusak ?>" stroke-linecap="butt"/>
          </svg>
          <div class="health-donut-center">
            <span class="health-donut-num"><?= $pBaik ?>%</span>
            <span class="health-donut-lbl">Baik</span>
          </div>
        </div>

        <!-- Legend -->
        <div class="health-legend">
          <div class="legend-item dot-green">
            <span class="legend-lbl">Baik</span>
            <span class="legend-val"><?= $stats['kondisi_baik'] ?></span>
          </div>
          <div class="legend-item dot-amber">
            <span class="legend-lbl">Servis</span>
            <span class="legend-val"><?= $stats['kondisi_maintenance'] ?></span>
          </div>
          <div class="legend-item dot-red">
            <span class="legend-lbl">Rusak</span>
            <span class="legend-val"><?= $stats['kondisi_rusak'] ?></span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Dashboard Main Layout -->
  <div class="dashboard-grid" style="grid-template-columns: 1fr 280px; gap: 24px;">
    
    <!-- Kolom Kiri: Tabel Aktivitas Terbaru -->
    <div class="dashboard-main">
      <div class="list-header">
        <span class="list-title">Log Perubahan Kondisi Terkini</span>
      </div>
      
      <div class="lab-list" style="background: var(--bg-surface); padding: 10px 0; box-shadow: var(--shadow-sm); overflow-x: auto;">
        <?php if (empty($recentLogs)): ?>
          <div style="padding: 40px; text-align: center; color: var(--text-muted); font-size: 13.5px;">
            Belum ada log riwayat pemeliharaan terekam dalam sistem.
          </div>
        <?php else: ?>
          <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
            <thead>
              <tr style="border-bottom: 1px solid var(--border); color: var(--text-muted); font-weight: 600;">
                <th style="padding: 12px 18px;">Waktu & Tanggal</th>
                <th style="padding: 12px 18px;">Kode Aset</th>
                <th style="padding: 12px 18px;">Status Transisi</th>
                <th style="padding: 12px 18px;">Catatan Kerusakan / Servis</th>
                <th style="padding: 12px 18px; text-align: right;">Petugas</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentLogs as $log): 
                $dateFormatted = date('d M Y H:i', strtotime($log['created_at']));
                $classKondisi = kondisiBadge($log['kondisi_baru']);
                $labelKondisi = kondisiLabel($log['kondisi_baru']);
                $oldKondisi = kondisiLabel($log['kondisi_lama']);
              ?>
                <tr style="border-bottom: 1px solid var(--bg-subtle);" class="panel-item-row-tr">
                  <td style="padding: 14px 18px; color: var(--text-muted); white-space: nowrap;"><?= $dateFormatted ?></td>
                  <td style="padding: 14px 18px; font-weight: 600; color: var(--text);"><?= htmlspecialchars($log['kode_aset']) ?></td>
                  <td style="padding: 14px 18px; white-space: nowrap;">
                    <span style="font-size: 11px; opacity:0.65;"><?= $oldKondisi ?> &rarr;</span> 
                    <span class="badge <?= $classKondisi ?>"><?= $labelKondisi ?></span>
                  </td>
                  <td style="padding: 14px 18px; max-width: 250px; color: var(--text); line-height: 1.4; word-wrap: break-word;">
                    <?= htmlspecialchars($log['catatan'] ?: 'Tidak ada catatan.') ?>
                  </td>
                  <td style="padding: 14px 18px; text-align: right; color: var(--text-muted); white-space: nowrap; font-weight: 500;">
                    <?= htmlspecialchars($log['nama_admin']) ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>

    <!-- Kolom Kanan: Quick Actions -->
    <aside class="dashboard-aside">
      <div class="panel">
        <div class="panel-head">
          <span class="panel-title">Tindakan Cepat Admin</span>
        </div>
        <div style="padding: 18px; display: flex; flex-direction: column; gap: 10px;">
          <a href="<?= APP_URL ?>/admin/labs/index.php" class="btn btn-dark" style="justify-content: center; padding: 10px 14px; font-size: 13px;">
            Kelola Laboratorium
          </a>
          <a href="<?= APP_URL ?>/admin/assets/index.php" class="btn btn-dark" style="justify-content: center; padding: 10px 14px; font-size: 13px; background: #334155; border-color: #334155;">
            Kelola Data Aset
          </a>
          <div style="border-top: 1px solid var(--border); margin: 8px 0; padding-top: 12px;"></div>
          <a href="<?= APP_URL ?>/admin/labs/create.php" class="btn" style="justify-content: flex-start; padding: 8px 12px; gap: 8px;">
            <svg width="14" height="14" viewBox="0 0 15 15" fill="none" style="flex-shrink:0; color: var(--text-muted);">
              <rect x="1" y="3" width="13" height="9" rx="1.5" stroke="currentColor" stroke-width="1.3"/>
              <path d="M4.5 12v1.5M10.5 12v1.5M3 13.5h9" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
              <path d="M7.5 5.5v4M5.5 7.5h4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
            </svg>
            Tambah Lab Baru
          </a>
          <a href="<?= APP_URL ?>/admin/assets/create.php" class="btn" style="justify-content: flex-start; padding: 8px 12px; gap: 8px;">
            <svg width="14" height="14" viewBox="0 0 15 15" fill="none" style="flex-shrink:0; color: var(--text-muted);">
              <rect x="1" y="1" width="13" height="13" rx="1.5" stroke="currentColor" stroke-width="1.3"/>
              <path d="M4 4h7M4 7h7M4 10h4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
              <path d="M10.5 8.5v4M8.5 10.5h4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
            </svg>
            Tambah Aset Baru
          </a>
        </div>
      </div>
    </aside>

  </div>

</div>

<style>
/* CSS minor tambahan untuk baris tabel di dashboard admin */
.panel-item-row-tr {
  transition: background-color 200ms ease;
}
.panel-item-row-tr:hover {
  background-color: #fafaf9;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
