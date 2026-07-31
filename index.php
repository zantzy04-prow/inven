<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Beranda';
$activeNav = 'home';

$stmt = $pdo->query("
    SELECT
        l.id, l.kode_lab, l.nama, l.deskripsi, l.lantai, l.kapasitas,
        COALESCE(s.total_aset,0)          AS total_aset,
        COALESCE(s.total_pc,0)            AS total_pc,
        COALESCE(s.kondisi_baik,0)        AS kondisi_baik,
        COALESCE(s.kondisi_rusak,0)       AS kondisi_rusak,
        COALESCE(s.kondisi_maintenance,0) AS kondisi_maintenance
    FROM labs l
    LEFT JOIN v_lab_summary s ON s.lab_id = l.id
    WHERE l.is_active = 1
    ORDER BY l.kode_lab ASC
");
$labs = $stmt->fetchAll();

$totals = [
    'labs'        => count($labs),
    'pc'          => array_sum(array_column($labs, 'total_pc')),
    'aset'        => array_sum(array_column($labs, 'total_aset')),
    'baik'        => array_sum(array_column($labs, 'kondisi_baik')),
    'rusak'       => array_sum(array_column($labs, 'kondisi_rusak')),
    'maintenance' => array_sum(array_column($labs, 'kondisi_maintenance')),
];

// Lab yang perlu perhatian (ada rusak / maintenance), urut dari paling parah
$attention = array_filter($labs, fn($l) => $l['kondisi_rusak'] > 0 || $l['kondisi_maintenance'] > 0);
usort($attention, fn($a, $b) =>
    ($b['kondisi_rusak'] * 2 + $b['kondisi_maintenance'])
    <=> ($a['kondisi_rusak'] * 2 + $a['kondisi_maintenance'])
);
$attention = array_slice($attention, 0, 4);

require_once __DIR__ . '/includes/header.php';
?>

<div class="topbar">
  <div class="breadcrumb">
    <svg width="15" height="15" viewBox="0 0 15 15" fill="none" style="opacity: 0.75;">
      <path d="M1.5 6.5L7.5 1.5l6 5V13.5h-4V10h-4v3.5h-4V6.5z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/>
    </svg>
    <b>Beranda</b>
  </div>
  <?php if (isAdmin()): ?>
  <div class="topbar-right">
    <a href="<?= APP_URL ?>/admin/labs/create.php" class="btn btn-dark">
      <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
        <path d="M6 1v10M1 6h10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
      </svg>
      Tambah Lab
    </a>
  </div>
  <?php endif; ?>
</div>

<div class="content">

  <div class="page-heading">
    <div class="page-header-pre">
      <span class="pulse-dot"></span>
      <span>Status Sistem: Live</span>
    </div>
    <h1>Inventaris Laboratorium</h1>
    <p>Kelola dan pantau seluruh fasilitas sekolah secara interaktif. Pilih laboratorium di bawah untuk memetakan denah ruangan 2D secara real-time.</p>
  </div>

  <?php
    $totalKondisi = $totals['baik'] + $totals['rusak'] + $totals['maintenance'];
    $pBaik  = $totalKondisi > 0 ? round($totals['baik']        / $totalKondisi * 100) : 0;
    $pRusak = $totalKondisi > 0 ? round($totals['rusak']       / $totalKondisi * 100) : 0;
    $pMaint = $totalKondisi > 0 ? round($totals['maintenance'] / $totalKondisi * 100) : 0;
  ?>

  <!-- Overview block: 3 kolom sejajar -->
  <div class="overview-block">

    <!-- Kolom 1: Hero total aset -->
    <div class="overview-col overview-col-hero">
      <span class="ov-eyebrow">Total Inventaris</span>
      <div>
        <div class="ov-hero-num"><?= $totals['aset'] ?></div>
        <div class="ov-hero-lbl">aset terdaftar di semua lab</div>
      </div>
    </div>

    <div class="overview-divider"></div>

    <!-- Kolom 2: Lab + PC -->
    <div class="overview-col overview-col-stats">
      <span class="ov-eyebrow">Infrastruktur</span>
      <div class="ov-stat-pair">
        <div class="ov-stat">
          <span class="ov-stat-num"><?= $totals['labs'] ?></span>
          <span class="ov-stat-lbl">Laboratorium aktif</span>
        </div>
        <div class="ov-stat">
          <span class="ov-stat-num"><?= $totals['pc'] ?></span>
          <span class="ov-stat-lbl">Unit PC terpasang</span>
        </div>
      </div>
    </div>

    <div class="overview-divider"></div>

    <!-- Kolom 3: Kondisi (donut) -->
    <div class="overview-col overview-col-health">
      <div class="health-label">Kondisi Aset</div>

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
            <span class="donut-pct"><?= $pBaik ?>%</span>
            <span class="donut-pct-lbl">Baik</span>
          </div>
        </div>

        <!-- Breakdown -->
        <div class="health-breakdown">
          <div class="hb-item">
            <span class="hb-dot green"></span>
            <span class="hb-item-label">Baik</span>
            <span class="hb-item-val"><?= $totals['baik'] ?></span>
          </div>
          <div class="hb-item">
            <span class="hb-dot amber"></span>
            <span class="hb-item-label">Maintenance</span>
            <span class="hb-item-val"><?= $totals['maintenance'] ?></span>
          </div>
          <div class="hb-item">
            <span class="hb-dot red"></span>
            <span class="hb-item-label">Rusak</span>
            <span class="hb-item-val"><?= $totals['rusak'] ?></span>
          </div>
        </div>
      </div>
    </div>

  </div>

  <!-- Bawah: 2 kolom (list + panel) -->
  <div class="dashboard-grid">

    <!-- Kolom kiri: daftar lab -->
    <div class="dashboard-main">
      <div class="list-header">
        <span class="list-title">Daftar Lab</span>
      </div>

      <?php if (empty($labs)): ?>
      <div class="empty">Belum ada laboratorium. Tambahkan melalui panel admin.</div>
      <?php else: ?>
      <div class="lab-list">
        <?php foreach ($labs as $i => $lab):

          if ($lab['kondisi_rusak'] > 0) {
            $statusClass = 'dot-red';
            $statusText  = $lab['kondisi_rusak'] . ' aset rusak';
          } elseif ($lab['kondisi_maintenance'] > 0) {
            $statusClass = 'dot-amber';
            $statusText  = $lab['kondisi_maintenance'] . ' maintenance';
          } else {
            $statusClass = 'dot-green';
            $statusText  = 'Semua baik';
          }

        ?>
        <a class="lab-row"
           href="<?= APP_URL ?>/lab.php?id=<?= $lab['id'] ?>"
           aria-label="Lihat denah <?= htmlspecialchars($lab['nama']) ?>">

          <div class="lab-row-left">
            <div class="lab-index"><?= $i + 1 ?></div>
            <div class="lab-info">
              <div class="lab-name"><?= htmlspecialchars($lab['nama']) ?></div>
              <div class="lab-desc">
                Lantai <?= $lab['lantai'] ?> &middot; <?= $lab['kapasitas'] ?> siswa
                <?= $lab['deskripsi'] ? ' &middot; ' . htmlspecialchars($lab['deskripsi']) : '' ?>
              </div>
            </div>
          </div>

          <div class="lab-row-right">
            <div class="lab-pc-count">
              <span class="num"><?= $lab['total_pc'] ?></span>
              <span class="lbl">PC</span>
            </div>
            <div class="lab-status">
              <span class="status-dot <?= $statusClass ?>"><?= $statusText ?></span>
            </div>
            <div class="lab-arrow">
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
          </div>

        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Kolom kanan: panel perlu perhatian -->
    <aside class="dashboard-aside">
      <div class="panel">
        <div class="panel-head">
          <span class="panel-title">Perlu Perhatian</span>
          <?php if (!empty($attention)): ?>
          <span class="panel-badge"><?= count($attention) ?></span>
          <?php endif; ?>
        </div>

        <?php if (empty($attention)): ?>
        <div class="panel-empty">
          <div class="panel-empty-icon">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
              <path d="M6 10l3 3 5-6" stroke="var(--green)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
              <circle cx="10" cy="10" r="8.5" stroke="var(--green)" stroke-width="1.4" opacity=".4"/>
            </svg>
          </div>
          <p>Semua aset dalam kondisi baik.</p>
        </div>
        <?php else: ?>
        <div class="panel-list">
          <?php foreach ($attention as $a):
            $rusak = (int)$a['kondisi_rusak'];
            $maint = (int)$a['kondisi_maintenance'];
          ?>
          <a class="panel-item" href="<?= APP_URL ?>/lab.php?id=<?= $a['id'] ?>">
            <div class="panel-item-top">
              <span class="panel-item-name"><?= htmlspecialchars($a['nama']) ?></span>
              <span class="panel-item-kode"><?= htmlspecialchars($a['kode_lab']) ?></span>
            </div>
            <div class="panel-item-tags">
              <?php if ($rusak > 0): ?>
              <span class="tag tag-red"><?= $rusak ?> rusak</span>
              <?php endif; ?>
              <?php if ($maint > 0): ?>
              <span class="tag tag-amber"><?= $maint ?> maintenance</span>
              <?php endif; ?>
            </div>
          </a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </aside>

  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
