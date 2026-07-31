<?php
// ============================================================
//  lab.php — Visual Lab Grid Map & Asset Details Viewer
//  Inventaris Sekolah | PHP Native + MySQL
// ============================================================

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// Get lab ID from URL parameter
$labId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($labId <= 0) {
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

// Fetch lab metadata
$stmt = $pdo->prepare("SELECT * FROM labs WHERE id = ? AND is_active = 1");
$stmt->execute([$labId]);
$lab = $stmt->fetch();

if (!$lab) {
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

$pageTitle = 'Lab ' . htmlspecialchars($lab['nama']);
$activeNav = 'labs';

// Fetch all active assets in this lab
$stmtAssets = $pdo->prepare("
    SELECT * FROM v_asset_detail 
    WHERE lab_id = ? AND is_active = 1
");
$stmtAssets->execute([$labId]);
$assets = $stmtAssets->fetchAll();

// Group assets by coordinate (posisi_x, posisi_y)
$gridData = [];
$maxX = 0;
$maxY = 0;

foreach ($assets as $asset) {
    $x = (int)$asset['posisi_x'];
    $y = (int)$asset['posisi_y'];
    
    $gridData[$y][$x][] = $asset;
    
    if ($x > $maxX) $maxX = $x;
    if ($y > $maxY) $maxY = $y;
}

// Determine layout grid dimensions (minimum 6x6, fallbacks to max coordinates)
$gridCols = max(6, (int)$lab['grid_cols'], $maxX + 1);
$gridRows = max(6, (int)$lab['grid_rows'], $maxY + 1);

// Fetch summary counts for this specific lab
$stmtSummary = $pdo->prepare("SELECT * FROM v_lab_summary WHERE lab_id = ?");
$stmtSummary->execute([$labId]);
$summary = $stmtSummary->fetch();

require_once __DIR__ . '/includes/header.php';
?>

<div class="topbar">
  <div class="breadcrumb">
    <a href="<?= APP_URL ?>/index.php" style="display: inline-flex; align-items: center; gap: 6px;">
      <svg width="15" height="15" viewBox="0 0 15 15" fill="none" style="opacity: 0.75;">
        <path d="M1.5 6.5L7.5 1.5l6 5V13.5h-4V10h-4v3.5h-4V6.5z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/>
      </svg>
      Beranda
    </a>
    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" style="margin: 0 4px; opacity: 0.5;">
      <path d="M4.5 9l3-3-3-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <b><?= htmlspecialchars($lab['nama']) ?></b>
  </div>
  <?php if (isAdmin()): ?>
  <div class="topbar-right">
    <a href="<?= APP_URL ?>/admin/labs/edit.php?id=<?= $lab['id'] ?>" class="btn">
      <svg width="13" height="13" viewBox="0 0 15 15" fill="none">
        <path d="M11.5 1.5l2 2-9 9-2.5.5.5-2.5 9-9z" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      Kelola Lab
    </a>
  </div>
  <?php endif; ?>
</div>

<div class="content">

  <div class="page-heading">
    <div style="display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
      <div>
        <h1><?= htmlspecialchars($lab['nama']) ?></h1>
        <p><?= htmlspecialchars($lab['deskripsi'] ?: 'Tidak ada deskripsi laboratorium.') ?></p>
        <div class="lab-header-stats">
          <span class="lab-stat-badge">Lantai <?= $lab['lantai'] ?></span>
          <span class="lab-stat-badge">Kapasitas: <?= $lab['kapasitas'] ?> Siswa</span>
          <?php if ($lab['luas_m2']): ?>
            <span class="lab-stat-badge">Luas: <?= number_format($lab['luas_m2'], 1, ',', '.') ?> m²</span>
          <?php endif; ?>
          <span class="lab-stat-badge" style="background: #f0f7f4; color: var(--green); border-color: #dcfce7;">
            <?= $summary ? $summary['kondisi_baik'] : 0 ?> Baik
          </span>
          <?php if ($summary && $summary['kondisi_maintenance'] > 0): ?>
            <span class="lab-stat-badge" style="background: #fffbeb; color: var(--amber); border-color: #fef3c7;">
              <?= $summary['kondisi_maintenance'] ?> Maintenance
            </span>
          <?php endif; ?>
          <?php if ($summary && $summary['kondisi_rusak'] > 0): ?>
            <span class="lab-stat-badge" style="background: #fef2f2; color: var(--red); border-color: #fee2e2;">
              <?= $summary['kondisi_rusak'] ?> Rusak
            </span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <style>
  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }
  </style>

  <div class="dashboard-grid">
    <!-- Kolom Kiri: Peta Denah Ruangan -->
    <div class="dashboard-main">
      <div class="list-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
        <span class="list-title">Denah Tata Letak Ruangan</span>
        <div style="display:flex; gap:6px;">
          <button type="button" id="btn_view_2d" class="btn btn-dark" style="padding: 6px 14px; font-size:12px; font-weight:600; display:flex; align-items:center; gap:6px;" onclick="toggleViewMode('2d')">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="3" x2="9" y2="21"></line><line x1="15" y1="3" x2="15" y2="21"></line></svg>
            Mode 2D
          </button>
          <button type="button" id="btn_view_3d" class="btn" style="padding: 6px 14px; font-size:12px; font-weight:600; display:flex; align-items:center; gap:6px;" onclick="toggleViewMode('3d')">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
            Mode 3D
          </button>
        </div>
      </div>
      
      <div class="lab-grid-card" style="padding: 16px;">
        <div id="view_2d_container" class="lab-grid-scroll">
          <?php
          $svgWidth = $gridCols * 80;
          $svgHeight = $gridRows * 80;
          ?>
          <svg class="room-layout-svg" 
               viewBox="-30 -30 <?= $svgWidth + 60 ?> <?= $svgHeight + 60 ?>" 
               width="100%" 
               height="100%" 
               xmlns="http://www.w3.org/2000/svg">
            
            <defs>
              <linearGradient id="proj-gradient" x1="0%" y1="100%" x2="0%" y2="0%">
                <stop offset="0%" stop-color="#fef08a" stop-opacity="0" />
                <stop offset="100%" stop-color="#fef08a" stop-opacity="0.25" />
              </linearGradient>
            </defs>

            <!-- Grid Helper Lines (Blueprint Grid Style) -->
            <?php for ($i = 0; $i <= $gridCols; $i++): ?>
              <line x1="<?= $i * 80 ?>" y1="0" x2="<?= $i * 80 ?>" y2="<?= $svgHeight ?>" stroke="#f1f1ee" stroke-width="1" />
            <?php endfor; ?>
            <?php for ($j = 0; $j <= $gridRows; $j++): ?>
              <line x1="0" y1="<?= $j * 80 ?>" x2="<?= $svgWidth ?>" y2="<?= $j * 80 ?>" stroke="#f1f1ee" stroke-width="1" />
            <?php endfor; ?>

            <!-- Windows (Glass effect) on the side walls -->
            <!-- Left Windows -->
            <line class="room-window-glass" x1="0" y1="80" x2="0" y2="240" />
            <line class="room-window" x1="0" y1="80" x2="0" y2="240" />
            <line class="room-window-glass" x1="0" y1="320" x2="0" y2="440" />
            <line class="room-window" x1="0" y1="320" x2="0" y2="440" />
            
            <!-- Right Windows -->
            <line class="room-window-glass" x1="<?= $svgWidth ?>" y1="80" x2="<?= $svgWidth ?>" y2="240" />
            <line class="room-window" x1="<?= $svgWidth ?>" y1="80" x2="<?= $svgWidth ?>" y2="240" />
            <line class="room-window-glass" x1="<?= $svgWidth ?>" y1="320" x2="<?= $svgWidth ?>" y2="440" />
            <line class="room-window" x1="<?= $svgWidth ?>" y1="320" x2="<?= $svgWidth ?>" y2="440" />

            <!-- Projector Screen at Front Wall -->
            <?php $screenWidth = 200; $screenX = ($svgWidth / 2) - ($screenWidth / 2); ?>
            <rect x="<?= $screenX ?>" y="-4" width="<?= $screenWidth ?>" height="8" rx="2" fill="#334155" stroke="#475569" stroke-width="1.5" />
            <rect x="<?= $screenX + 4 ?>" y="2" width="<?= $screenWidth - 8 ?>" height="2" fill="#f8fafc" />

            <!-- Main Room Walls & Dynamic Door Position -->
            <?php
            $pintuPos = $lab['pintu_posisi'] ?? 'kiri-bawah';
            $wallD = "M 0,{$svgHeight} L 0,0 L {$svgWidth},0 L {$svgWidth},{$svgHeight} L 0,{$svgHeight}";
            $doorHTML = "";

            if ($pintuPos === 'kiri-bawah') {
                $wallD = "M 40,{$svgHeight} L 0,{$svgHeight} L 0,0 L {$svgWidth},0 L {$svgWidth},{$svgHeight} L 120,{$svgHeight}";
                $doorHTML = '
                <path class="room-door-swing" d="M 40,' . ($svgHeight - 65) . ' A 65,65 0 0,1 105,' . $svgHeight . '" />
                <line class="room-door-leaf" x1="40" y1="' . $svgHeight . '" x2="40" y2="' . ($svgHeight - 65) . '" />
                ';
            } elseif ($pintuPos === 'kanan-bawah') {
                $gapStart = $svgWidth - 120;
                $gapEnd = $svgWidth - 40;
                $wallD = "M {$gapEnd},{$svgHeight} L {$svgWidth},{$svgHeight} L {$svgWidth},0 L 0,0 L 0,{$svgHeight} L {$gapStart},{$svgHeight}";
                $doorHTML = '
                <path class="room-door-swing" d="M ' . $gapEnd . ',' . ($svgHeight - 65) . ' A 65,65 0 0,0 ' . ($svgWidth - 105) . ',' . $svgHeight . '" />
                <line class="room-door-leaf" x1="' . $gapEnd . '" y1="' . $svgHeight . '" x2="' . $gapEnd . '" y2="' . ($svgHeight - 65) . '" />
                ';
            } elseif ($pintuPos === 'kiri-atas') {
                $wallD = "M 0,0 L 40,0 M 120,0 L {$svgWidth},0 L {$svgWidth},{$svgHeight} L 0,{$svgHeight} L 0,0";
                $doorHTML = '
                <path class="room-door-swing" d="M 40,65 A 65,65 0 0,0 105,0" />
                <line class="room-door-leaf" x1="40" y1="0" x2="40" y2="65" />
                ';
            } elseif ($pintuPos === 'kanan-atas') {
                $gapStart = $svgWidth - 120;
                $gapEnd = $svgWidth - 40;
                $wallD = "M 0,0 L {$gapStart},0 M {$gapEnd},0 L {$svgWidth},0 L {$svgWidth},{$svgHeight} L 0,{$svgHeight} L 0,0";
                $doorHTML = '
                <path class="room-door-swing" d="M ' . $gapEnd . ',65 A 65,65 0 0,1 ' . ($svgWidth - 105) . ',0" />
                <line class="room-door-leaf" x1="' . $gapEnd . '" y1="0" x2="' . $gapEnd . '" y2="65" />
                ';
            } elseif ($pintuPos === 'tengah-bawah') {
                $mid = $svgWidth / 2;
                $gapStart = $mid - 40;
                $gapEnd = $mid + 40;
                $wallD = "M {$gapEnd},{$svgHeight} L {$svgWidth},{$svgHeight} L {$svgWidth},0 L 0,0 L 0,{$svgHeight} L {$gapStart},{$svgHeight}";
                $doorHTML = '
                <path class="room-door-swing" d="M ' . $gapStart . ',' . ($svgHeight - 65) . ' A 65,65 0 0,1 ' . ($mid + 25) . ',' . $svgHeight . '" />
                <line class="room-door-leaf" x1="' . $gapStart . '" y1="' . $svgHeight . '" x2="' . $gapStart . '" y2="' . ($svgHeight - 65) . '" />
                ';
            }
            ?>
            <path class="room-wall" d="<?= $wallD ?>" />
            <?= $doorHTML ?>

            <!-- Render Assets -->
            <?php
            for ($y = 0; $y < $gridRows; $y++) {
                for ($x = 0; $x < $gridCols; $x++) {
                    $assetsAtCell = isset($gridData[$y][$x]) ? $gridData[$y][$x] : [];
                    $tx = $x * 80;
                    $ty = $y * 80;
                    
                    if (!empty($assetsAtCell)) {
                        // Find primary asset (prefer PC, else first asset)
                        $primaryAsset = $assetsAtCell[0];
                        foreach ($assetsAtCell as $a) {
                            if ($a['tipe_kode'] === 'PC') {
                                $primaryAsset = $a;
                                break;
                            }
                        }
                        
                        // Determine coordinate status (worst condition takes priority)
                        $cellCond = 'baik';
                        foreach ($assetsAtCell as $a) {
                            if ($a['kondisi'] === 'rusak') {
                                $cellCond = 'rusak';
                                break;
                            } elseif ($a['kondisi'] === 'maintenance') {
                                $cellCond = 'maintenance';
                            }
                        }
                        
                        $cleanAssetsJson = json_encode($assetsAtCell, JSON_HEX_APOS | JSON_HEX_QUOT);
                        $label = htmlspecialchars($primaryAsset['kode_aset']);
                        $rotation = (int)($primaryAsset['rotasi'] ?? 0);
                        
                        // Main cell group (only translated)
                        echo '<g class="grid-cell-group grid-cell cond-' . $cellCond . '" 
                                 transform="translate(' . $tx . ', ' . $ty . ')"
                                 data-x="' . $x . '" 
                                 data-y="' . $y . '" 
                                 data-assets=\'' . $cleanAssetsJson . '\'>';
                        
                        // Rotated child group for graphics only
                        echo '<g transform="rotate(' . $rotation . ', 40, 40)">';
                        
                        $type = $primaryAsset['tipe_kode'];
                        if ($type === 'PC') {
                            // PC / Workstation set
                            echo '
                            <!-- Desk -->
                            <rect class="svg-desk" x="6" y="14" width="68" height="38" rx="3" />
                            <!-- Chair -->
                            <path class="svg-chair" d="M 26,62 C 26,55 54,55 54,62 L 49,72 C 49,72 31,72 31,72 Z" />
                            <!-- Keyboard -->
                            <rect x="23" y="37" width="34" height="6" rx="1" fill="#e2e8f0" stroke="#cbd5e1" stroke-width="0.5" />
                            <!-- Mouse -->
                            <rect x="61" y="39" width="4" height="6" rx="2" fill="#cbd5e1" />
                            <!-- Monitor Glow -->
                            <rect class="svg-monitor-glow" x="20" y="22" width="40" height="7" rx="3" />
                            <!-- Monitor Screen -->
                            <rect class="svg-monitor" x="22" y="24" width="36" height="3" rx="1" />
                            <!-- Monitor Stand -->
                            <rect x="36" y="27" width="8" height="2" fill="#475569" />
                            ';
                        } elseif ($type === 'TV') {
                            // TV Monitor
                            echo '
                            <rect class="svg-tv-unit" x="2" y="10" width="10" height="60" rx="2" />
                            <rect class="svg-tv-screen" x="4" y="12" width="6" height="56" rx="1" />
                            <rect class="svg-tv-glow" x="12" y="12" width="15" height="56" rx="4" />
                            ';
                        } elseif ($type === 'AC') {
                            // Air Conditioner
                            if ($x < $gridCols / 2) {
                                // Left side AC, blow right
                                echo '
                                <rect class="svg-ac-unit" x="2" y="15" width="12" height="50" rx="2" />
                                <path class="svg-ac-flow" d="M 18,25 L 32,25 M 18,40 L 32,40 M 18,55 L 32,55" />
                                ';
                            } else {
                                // Right side AC, blow left
                                echo '
                                <rect class="svg-ac-unit" x="66" y="15" width="12" height="50" rx="2" />
                                <path class="svg-ac-flow" d="M 62,25 L 48,25 M 62,40 L 48,40 M 62,55 L 48,55" />
                                ';
                            }
                        } elseif ($type === 'PRJ') {
                            // Projector
                            echo '
                            <!-- Light Cone Beam -->
                            <polygon class="svg-proj-beam" points="40,24 15,0 65,0" />
                            <!-- Projector Body (Rounded Rectangle) -->
                            <rect class="svg-proj-unit" x="24" y="28" width="32" height="24" rx="4" />
                            <!-- Projector Lens cylinder -->
                            <rect x="34" y="22" width="12" height="6" rx="1" fill="#334155" stroke="#1e293b" stroke-width="1" />
                            <!-- Lens Glass reflection -->
                            <ellipse cx="40" cy="22" rx="5" ry="1.2" fill="#38bdf8" />
                            <!-- Buttons on top surface -->
                            <circle cx="30" cy="34" r="1.2" fill="#cbd5e1" />
                            <circle cx="34" cy="34" r="1.2" fill="#cbd5e1" />
                            <circle cx="30" cy="38" r="1.2" fill="#cbd5e1" />
                            <!-- Vent lines detail -->
                            <line x1="48" y1="32" x2="48" y2="44" stroke="#cbd5e1" stroke-width="1" stroke-dasharray="2,2" />
                            ';
                        } elseif ($type === 'DESK') {
                            // Standalone Desk
                            echo '
                            <rect class="svg-desk" x="12" y="24" width="56" height="40" rx="4" />
                            ';
                        } elseif ($type === 'CHR') {
                            // Standalone Chair
                            echo '
                            <rect class="svg-chair" x="24" y="24" width="32" height="32" rx="6" />
                            <rect x="28" y="18" width="24" height="6" rx="2" fill="#475569" stroke="#334155" stroke-width="1" />
                            ';
                        } elseif ($type === 'WBD') {
                            // Whiteboard
                            echo '
                            <rect x="4" y="24" width="72" height="10" rx="1" fill="#ffffff" stroke="#475569" stroke-width="2" />
                            <line x1="8" y1="29" x2="72" y2="29" stroke="#cbd5e1" stroke-width="1" stroke-dasharray="2,2" />
                            ';
                        } elseif ($type === 'FAN') {
                            // Fan
                            echo '
                            <circle cx="40" cy="40" r="18" fill="none" stroke="#64748b" stroke-width="1.5" />
                            <circle cx="40" cy="40" r="4" fill="#475569" />
                            <path d="M 40,40 L 40,22 Q 43,26 40,40 Z" fill="#94a3b8" />
                            <path d="M 40,40 L 56,49 Q 51,46 40,40 Z" fill="#94a3b8" />
                            <path d="M 40,40 L 24,49 Q 29,46 40,40 Z" fill="#94a3b8" />
                            ';
                        } elseif ($type === 'CAB') {
                            // Cabinet
                            echo '
                            <rect x="12" y="20" width="56" height="40" rx="3" fill="#f5efe6" stroke="#a19785" stroke-width="2" />
                            <line x1="40" y1="20" x2="40" y2="60" stroke="#a19785" stroke-width="1.5" />
                            <circle cx="36" cy="40" r="2" fill="#475569" />
                            <circle cx="44" cy="40" r="2" fill="#475569" />
                            ';
                        } elseif ($type === 'TDK') {
                            // Teacher Desk
                            echo '
                            <rect x="8" y="20" width="64" height="40" rx="4" fill="#e7ddcc" stroke="#a19785" stroke-width="2.5" />
                            <rect x="14" y="24" width="16" height="32" rx="2" fill="none" stroke="#a19785" stroke-width="1" />
                            <circle cx="22" cy="52" r="1.5" fill="#475569" />
                            ';
                        } else {
                            // Default generic asset placeholder
                            echo '
                            <circle cx="40" cy="40" r="16" fill="var(--bg-subtle)" stroke="var(--border)" stroke-width="1.5" />
                            <text x="40" y="43" text-anchor="middle" font-size="14">' . htmlspecialchars($primaryAsset['tipe_icon']) . '</text>
                            ';
                        }
                        
                        echo '</g>'; // End of rotated group
                        
                        // Draw label outside the rotated group to ensure it stays horizontal
                        echo '<text x="40" y="66" text-anchor="middle" font-size="8" font-family="DM Sans" font-weight="600" fill="var(--text-muted)">' . $label . '</text>';
                        
                        echo '</g>'; // End of cell group
                    } else {
                        // Empty cell floor dot
                        echo '
                        <g title="Koordinat (' . $x . ', ' . $y . ')">
                            <circle cx="' . ($tx + 40) . '" cy="' . ($ty + 40) . '" r="2" fill="#cbd5e1" opacity="0.5" />
                        </g>';
                    }
                }
            }
            ?>
          </svg>
        </div>

        <!-- 3D View Container -->
        <div id="view_3d_container" style="display:none; width:100%; height:450px; position:relative; border-radius:12px; overflow:hidden; background:#0f172a; box-shadow:inset 0 0 20px rgba(0,0,0,0.6); border: 1px solid var(--border);">
          <div id="3d_loading" style="position:absolute; top:0; left:0; width:100%; height:100%; display:flex; flex-direction:column; align-items:center; justify-content:center; background:#0f172a; color:#fff; z-index:30; gap:12px; backdrop-filter: blur(4px);">
            <div style="width:28px; height:28px; border:3px solid rgba(255,255,255,0.1); border-top:3px solid #38bdf8; border-radius:50%; animation: spin 0.8s linear infinite;"></div>
            <span style="font-size:12px; font-weight:600; font-family:'DM Sans'; color:#94a3b8; letter-spacing:0.05em;">MEMUAT RENDER 3D...</span>
          </div>
          <div id="3d_viewport" style="width:100%; height:100%;"></div>
        </div>
      </div>
    </div>

    <!-- Kolom Kanan: Detail Panel Samping -->
    <aside class="dashboard-aside">
      <div class="panel" id="detail-panel" style="min-height: 380px;">
        <!-- Initial Placeholder State -->
        <div id="detail-placeholder" class="panel-empty" style="padding: 64px 20px;">
          <div class="panel-empty-icon" style="display: flex; justify-content: center; margin-bottom: 12px;">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color: var(--text-faint);">
              <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
              <circle cx="12" cy="10" r="3"/>
            </svg>
          </div>
          <p style="font-size: 13.5px; font-weight: 500; color: var(--text-muted); line-height: 1.5;">
            Pilih meja atau komponen lab pada denah untuk melihat informasi detail aset.
          </p>
        </div>

        <!-- Dynamic Content (hidden initially) -->
        <div id="detail-content" style="display: none;">
          <div class="detail-header">
            <div class="detail-title" id="cell-title">Meja / Koordinat</div>
            <div class="detail-subtitle" id="cell-subtitle">Total 0 Aset di area ini</div>
          </div>
          
          <div class="detail-body">
            <!-- List of items in the clicked cell -->
            <div class="spec-title">Aset Tersedia</div>
            <div class="asset-item-list" id="cell-asset-list">
              <!-- Rendered via JS -->
            </div>

            <!-- Specific asset details (specs and log history) -->
            <div id="selected-asset-details" style="display: none;">
              <!-- Main details -->
              <div class="spec-table-container">
                <table class="spec-table">
                  <tr>
                    <td class="label">Merk & Model</td>
                    <td class="value" id="det-merk-model">-</td>
                  </tr>
                  <tr>
                    <td class="label">Serial Number</td>
                    <td class="value" id="det-sn">-</td>
                  </tr>
                  <tr>
                    <td class="label">Tahun Pembelian</td>
                    <td class="value" id="det-tahun">-</td>
                  </tr>
                  <tr>
                    <td class="label">Harga Barang</td>
                    <td class="value" id="det-harga">-</td>
                  </tr>
                  <tr>
                    <td class="label">Status Kondisi</td>
                    <td class="value" id="det-status">-</td>
                  </tr>
                  <tr id="det-ket-row">
                    <td class="label">Keterangan</td>
                    <td class="value" id="det-keterangan">-</td>
                  </tr>
                </table>
              </div>

              <!-- Technical specs list -->
              <div class="asset-spec-card" id="specs-container">
                <div class="spec-title">Spesifikasi Teknis</div>
                <table class="spec-table" id="specs-table-rows">
                  <!-- Rendered via JS -->
                </table>
              </div>

              <!-- Maintenance log timeline -->
              <div class="asset-spec-card" id="logs-container">
                <div class="spec-title">Riwayat Pemeliharaan</div>
                <div class="timeline" id="logs-timeline">
                  <!-- Rendered via JS -->
                </div>
              </div>
            </div>
            
            <!-- Loading indicator for fetch operations -->
            <div id="details-loader" style="display: none; text-align: center; padding: 40px 0;">
              <span style="font-size: 13px; color: var(--text-muted);">Memuat detail aset...</span>
            </div>
          </div>
        </div>

      </div>
    </aside>
  </div>

</div>

<!-- Interactive JS Map Script -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  const cells = document.querySelectorAll('.grid-cell:not(.empty)');
  const detailPlaceholder = document.getElementById('detail-placeholder');
  const detailContent = document.getElementById('detail-content');
  const cellTitle = document.getElementById('cell-title');
  const cellSubtitle = document.getElementById('cell-subtitle');
  const cellAssetList = document.getElementById('cell-asset-list');
  
  const selectedDetails = document.getElementById('selected-asset-details');
  const detailsLoader = document.getElementById('details-loader');
  
  // Elements for asset detail table
  const detMerkModel = document.getElementById('det-merk-model');
  const detSn = document.getElementById('det-sn');
  const detTahun = document.getElementById('det-tahun');
  const detHarga = document.getElementById('det-harga');
  const detStatus = document.getElementById('det-status');
  const detKeterangan = document.getElementById('det-keterangan');
  const detKetRow = document.getElementById('det-ket-row');
  
  const specsContainer = document.getElementById('specs-container');
  const specsTableRows = document.getElementById('specs-table-rows');
  const logsContainer = document.getElementById('logs-container');
  const logsTimeline = document.getElementById('logs-timeline');

  // Helper function to return SVG string based on asset code type
  function getAssetIcon(kode) {
    const icons = {
      'PC': `<svg class="icon-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>`,
      'MSE': `<svg class="icon-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="7"/><line x1="12" y1="2" x2="12" y2="6"/></svg>`,
      'KB': `<svg class="icon-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2" ry="2"/><line x1="6" y1="8" x2="6.01" y2="8"/><line x1="10" y1="8" x2="10.01" y2="8"/><line x1="14" y1="8" x2="14.01" y2="8"/><line x1="18" y1="8" x2="18.01" y2="8"/><line x1="6" y1="12" x2="6.01" y2="12"/><line x1="10" y1="12" x2="10.01" y2="12"/><line x1="14" y1="12" x2="14.01" y2="12"/><line x1="18" y1="12" x2="18.01" y2="12"/><line x1="7" y1="16" x2="17" y2="16"/></svg>`,
      'TV': `<svg class="icon-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="15" rx="2"/><polyline points="17 21 12 18 7 21"/></svg>`,
      'PRJ': `<svg class="icon-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 17H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-1"/><circle cx="12" cy="10" r="3"/><path d="M12 13v4"/></svg>`,
      'AC': `<svg class="icon-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="8" rx="1"/><path d="M6 14v4M10 14v2M14 14v2M18 14v4M4 11v1M20 11v1"/></svg>`
    };
    return icons[kode.toUpperCase()] || `<svg class="icon-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>`;
  }

  // Listen to cell clicks
  cells.forEach(cell => {
    cell.addEventListener('click', () => {
      // Manage active classes
      cells.forEach(c => c.classList.remove('active'));
      cell.classList.add('active');
      
      const x = cell.dataset.x;
      const y = cell.dataset.y;
      const assets = JSON.parse(cell.dataset.assets);

      // Hide placeholder and show content area
      detailPlaceholder.style.display = 'none';
      detailContent.style.display = 'block';
      
      // Update cell title info
      cellTitle.textContent = `Area Meja (${x}, ${y})`;
      cellSubtitle.textContent = `Terdiri dari ${assets.length} aset`;
      
      // Render assets list in this cell
      cellAssetList.innerHTML = '';
      assets.forEach((asset, idx) => {
        const row = document.createElement('div');
        row.className = 'asset-item-row' + (idx === 0 ? ' active' : '');
        row.dataset.id = asset.id;
        row.innerHTML = `
          <div class="asset-item-left">
            <span class="asset-item-icon">${getAssetIcon(asset.tipe_kode)}</span>
            <div class="asset-item-info">
              <div class="asset-item-name">${asset.nama}</div>
              <div class="asset-item-code">${asset.kode_aset}</div>
            </div>
          </div>
          <span class="badge badge-${asset.kondisi}">${asset.kondisi}</span>
        `;
        
        row.addEventListener('click', (e) => {
          e.stopPropagation(); // prevent grid cell click trigger
          document.querySelectorAll('.asset-item-row').forEach(r => r.classList.remove('active'));
          row.classList.add('active');
          loadAssetDetails(asset.id);
        });
        
        cellAssetList.appendChild(row);
      });
      
      // Load details for first asset by default
      if (assets.length > 0) {
        loadAssetDetails(assets[0].id);
      }
    });
  });

  // Fetch asset details via API
  async function loadAssetDetails(assetId) {
    selectedDetails.style.display = 'none';
    detailsLoader.style.display = 'block';

    try {
      const response = await fetch(`<?= APP_URL ?>/api/get_asset.php?id=${assetId}`);
      if (!response.ok) throw new Error('Gagal memuat detail aset');
      
      const res = await response.json();
      if (!res.success) throw new Error(res.message || 'Gagal memuat detail aset');
      
      const data = res.data;
      const asset = data.asset;
      
      // Update main detail elements
      detMerkModel.textContent = `${asset.merk || '-'} ${asset.model || ''}`.trim() || '-';
      detSn.textContent = asset.serial_number || '-';
      detTahun.textContent = asset.tahun_beli || '-';
      detHarga.textContent = asset.harga_formatted || '-';
      
      // Condition Badge
      detStatus.innerHTML = `<span class="badge badge-${asset.kondisi}">${asset.kondisi}</span>`;
      
      // Keterangan row visibility
      if (asset.keterangan) {
        detKeterangan.textContent = asset.keterangan;
        detKetRow.style.display = '';
      } else {
        detKetRow.style.display = 'none';
      }
      
      // Render Specs Table
      specsTableRows.innerHTML = '';
      if (data.specs && data.specs.length > 0) {
        specsContainer.style.display = 'block';
        data.specs.forEach(spec => {
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td class="label">${spec.spec_key}</td>
            <td class="value">${spec.spec_value}</td>
          `;
          specsTableRows.appendChild(tr);
        });
      } else {
        specsContainer.style.display = 'none';
      }
      
      // Render Timeline logs
      logsTimeline.innerHTML = '';
      if (data.logs && data.logs.length > 0) {
        logsContainer.style.display = 'block';
        data.logs.forEach(log => {
          const item = document.createElement('div');
          item.className = 'timeline-item';
          
          let oldStatus = log.kondisi_lama.toUpperCase();
          let newStatus = log.kondisi_baru.toUpperCase();
          
          item.innerHTML = `
            <span class="timeline-dot ${log.kondisi_baru}"></span>
            <div class="timeline-content">
              <div class="timeline-header">
                <span class="timeline-status">${oldStatus} &rarr; ${newStatus}</span>
                <span class="timeline-date">${log.tanggal_formatted}</span>
              </div>
              <div class="timeline-desc">${log.catatan || 'Perubahan kondisi status.'}</div>
              <div class="timeline-author">Oleh: ${log.diubah_oleh_nama}</div>
            </div>
          `;
          logsTimeline.appendChild(item);
        });
      } else {
        logsContainer.style.display = 'none';
      }

      // Show details block
      detailsLoader.style.display = 'none';
      selectedDetails.style.display = 'block';

    } catch (err) {
      console.error(err);
      detailsLoader.style.display = 'none';
      alert('Gagal mengambil spesifikasi dan riwayat aset.');
    }
  }
});

// 3D View Config and Initializer
const assets3d = <?= json_encode($assets) ?>;
const gridCols = <?= $gridCols ?>;
const gridRows = <?= $gridRows ?>;
const pintuPosisi = '<?= $lab['pintu_posisi'] ?? 'kiri-bawah' ?>';
let isThreeInitialized = false;

function toggleViewMode(mode) {
    const v2d = document.getElementById('view_2d_container');
    const v3d = document.getElementById('view_3d_container');
    const btn2d = document.getElementById('btn_view_2d');
    const btn3d = document.getElementById('btn_view_3d');

    if (mode === '3d') {
        v2d.style.display = 'none';
        v3d.style.display = 'block';
        btn2d.className = 'btn';
        btn3d.className = 'btn btn-dark';

        if (!isThreeInitialized) {
            // Tunggu browser selesai paint container dulu, baru init 3D
            // (tanpa ini clientWidth/clientHeight = 0 → renderer 0×0 → layar hitam)
            loadThreeJS(() => {
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => initThreeScene());
                });
            });
        }
    } else {
        v2d.style.display = 'block';
        v3d.style.display = 'none';
        btn2d.className = 'btn btn-dark';
        btn3d.className = 'btn';
    }
}

function loadThreeJS(callback) {
    if (window.THREE) { callback(); return; }

    // Muat dari file lokal dahulu (instant, tidak butuh internet)
    const LOCAL_THREE = '<?= APP_URL ?>/assets/js/three.min.js';
    const LOCAL_ORBIT = '<?= APP_URL ?>/assets/js/OrbitControls.js';
    const CDN_THREE   = 'https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js';
    const CDN_ORBIT   = 'https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js';

    function loadScript(primary, fallback, onDone) {
        const s = document.createElement('script');
        s.src = primary;
        s.onload = onDone;
        s.onerror = () => {
            // Fallback ke CDN jika file lokal tidak ada
            const f = document.createElement('script');
            f.src = fallback;
            f.onload = onDone;
            f.onerror = () => console.error('Gagal memuat: ' + fallback);
            document.head.appendChild(f);
        };
        document.head.appendChild(s);
    }

    loadScript(LOCAL_THREE, CDN_THREE, () => {
        loadScript(LOCAL_ORBIT, CDN_ORBIT, callback);
    });
}

function initThreeScene() {
    isThreeInitialized = true;
    const viewport = document.getElementById('3d_viewport');
    const loading  = document.getElementById('3d_loading');

    // Keamanan: jika viewport belum punya ukuran, retry sekali lagi
    let width  = viewport.clientWidth;
    let height = viewport.clientHeight;
    if (!width || !height) {
        // Gunakan ukuran parent container sebagai fallback
        const parent = viewport.parentElement;
        width  = parent ? parent.clientWidth  : 800;
        height = parent ? parent.clientHeight : 450;
    }
    if (!width)  width  = 800;
    if (!height) height = 450;

    try {

    // ─── 1. Scene ────────────────────────────────────────────────────────────
    const scene = new THREE.Scene();
    scene.background = new THREE.Color(0x0d1424);
    scene.fog = new THREE.Fog(0x0d1424, 40, 100);

    // ─── 2. Camera ───────────────────────────────────────────────────────────
    const camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 500);
    camera.position.set(0, 22, 28);

    // ─── 3. Renderer ─────────────────────────────────────────────────────────
    const renderer = new THREE.WebGLRenderer({ antialias: true, powerPreference: 'default' });
    renderer.setSize(width, height);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.5));
    renderer.shadowMap.enabled = false;
    // TIDAK ada renderer.outputEncoding — tidak ada di Three.js r128
    viewport.appendChild(renderer.domElement);

    // ─── 4. OrbitControls ────────────────────────────────────────────────────
    const controls = new THREE.OrbitControls(camera, renderer.domElement);
    controls.enableDamping  = true;
    controls.dampingFactor  = 0.08;
    controls.maxPolarAngle  = Math.PI / 2 - 0.04;
    controls.minDistance    = 4;
    controls.maxDistance    = 65;

    // ─── 5. Lighting (rich but cheap) ─────────────────────────────────────────
    const hemi = new THREE.HemisphereLight(0xd0e8ff, 0x3d2b00, 0.55);
    scene.add(hemi);
    const dirMain = new THREE.DirectionalLight(0xfff4e0, 0.65);
    dirMain.position.set(12, 28, 18);
    scene.add(dirMain);
    const dirFill = new THREE.DirectionalLight(0xcce8ff, 0.25);
    dirFill.position.set(-10, 15, -12);
    scene.add(dirFill);
    
    // ─── 6. Room dimensions ───────────────────────────────────────────────────
    const scale  = 3.5;
    const roomW  = gridCols * scale;
    const roomD  = gridRows * scale;
    const wallH  = 4.2;

    // ─── Material cache (share identical GPU materials) ───────────────────────
    const _matCache = new Map();
    function mat(hex, opts = {}) {
        const key = hex + JSON.stringify(opts);
        if (_matCache.has(key)) return _matCache.get(key);
        const m = Object.keys(opts).length
            ? new THREE.MeshLambertMaterial({ color: hex, ...opts })
            : new THREE.MeshLambertMaterial({ color: hex });
        _matCache.set(key, m);
        return m;
    }
    // Shorthand box / cylinder helpers
    function box(w, h, d) { return new THREE.BoxGeometry(w, h, d); }
    function cyl(rt, rb, h, seg) { return new THREE.CylinderGeometry(rt, rb, h, seg || 8); }

    // ─── 7. Floor — tile-like dark surface with subtle warm tint ─────────────
    const floorMat = new THREE.MeshLambertMaterial({ color: 0x1a2436 });
    const floor = new THREE.Mesh(new THREE.BoxGeometry(roomW, 0.12, roomD), floorMat);
    floor.position.y = -0.06;
    scene.add(floor);

    // Thin tile-line grid overlay
    const gridHelper = new THREE.GridHelper(
        Math.max(roomW, roomD),
        Math.max(gridCols, gridRows) * 2,
        0x2d4a6a, 0x1e3354
    );
    gridHelper.position.y = 0.01;
    scene.add(gridHelper);


    // ─── 8. Walls — glass-blue semi-transparent ───────────────────────────────
    const wallMat = mat(0x38bdf8, { transparent: true, opacity: 0.14, depthWrite: false });
    const walls = [
        [roomW, wallH, 0.15,  0,        wallH/2, -roomD/2],
        [0.15,  wallH, roomD, -roomW/2, wallH/2,  0      ],
        [0.15,  wallH, roomD,  roomW/2, wallH/2,  0      ],
        [roomW, wallH, 0.15,  0,        wallH/2,  roomD/2],
    ];
    walls.forEach(([w, h, d, x, y, z]) => {
        const m = new THREE.Mesh(new THREE.BoxGeometry(w, h, d), wallMat);
        m.position.set(x, y, z);
        scene.add(m);
    });

    // Wall base skirting (solid, very thin strip)
    const skirtMat = mat(0x1e3a5a);
    [
        [roomW, 0.18, 0.08,  0,        0.09, -roomD/2],
        [roomW, 0.18, 0.08,  0,        0.09,  roomD/2],
        [0.08,  0.18, roomD, -roomW/2, 0.09,  0      ],
        [0.08,  0.18, roomD,  roomW/2, 0.09,  0      ],
    ].forEach(([w,h,d,x,y,z]) => {
        const sk = new THREE.Mesh(new THREE.BoxGeometry(w, h, d), skirtMat);
        sk.position.set(x, y, z);
        scene.add(sk);
    });

    // ─── 9. Asset 3D models ───────────────────────────────────────────────────
    function addBox(g, color, px, py, pz, rx, ry, rz) {
        const m = new THREE.Mesh(g, mat(color));
        m.position.set(px, py, pz);
        if (rx) m.rotation.x = rx;
        if (ry) m.rotation.y = ry;
        if (rz) m.rotation.z = rz;
        return m;
    }

    // ── Chair: seat + 4 cylinder legs + backrest (3 spindles) + armrests ─────
    function buildChair(grp, ox, oz, facingBack) {
        const cg  = new THREE.Group();
        const seatH = 0.76;
        const legR  = 0.065;
        const legMat = mat(0x1e293b);

        // Seat cushion
        cg.add(addBox(box(1.05, 0.14, 1.05), 0x334155, 0, seatH, 0));

        // 4 legs
        [[-0.4,-0.4],[0.4,-0.4],[-0.4,0.4],[0.4,0.4]].forEach(([lx,lz]) => {
            const leg = new THREE.Mesh(cyl(legR, legR, seatH - 0.07), legMat);
            leg.position.set(lx, (seatH - 0.07) / 2, lz);
            cg.add(leg);
        });

        // Backrest board
        const backDir = facingBack ? -0.46 : 0.46;
        cg.add(addBox(box(1.05, 0.72, 0.09), 0x2d3f52, 0, seatH + 0.44, backDir));

        // 2 back support posts
        [-0.38, 0.38].forEach(bx => {
            const post = new THREE.Mesh(cyl(0.04, 0.04, 0.48), legMat);
            post.position.set(bx, seatH + 0.08, backDir);
            cg.add(post);
        });

        // Armrests
        [-0.53, 0.53].forEach(ax => {
            cg.add(addBox(box(0.09, 0.08, 0.72), 0x1e293b, ax, seatH + 0.3, 0));
        });

        cg.position.set(ox, 0, oz);
        grp.add(cg);
    }

    // ── Desk with 4 legs + optional drawer ────────────────────────────────────
    function buildDesk(grp, w, d, color, legColor, drawers) {
        const dg = new THREE.Group();
        const topH = 1.22;

        // Tabletop
        dg.add(addBox(box(w, 0.1, d), color, 0, topH, 0));

        // 4 legs
        const lm = mat(legColor || color);
        const lh = topH - 0.05;
        [[w/2-0.1, d/2-0.1],[-(w/2-0.1), d/2-0.1],[w/2-0.1,-(d/2-0.1)],[-(w/2-0.1),-(d/2-0.1)]].forEach(([lx,lz])=>{
            const leg = new THREE.Mesh(cyl(0.06, 0.06, lh), lm);
            leg.position.set(lx, lh/2, lz);
            dg.add(leg);
        });

        if (drawers) {
            // Side drawer box
            dg.add(addBox(box(0.5, 0.55, d * 0.7), legColor || 0x5a3a1a, w/2 - 0.28, topH - 0.35, 0));
            // Drawer handle
            const hm = new THREE.Mesh(cyl(0.025, 0.025, 0.28), mat(0x94a3b8));
            hm.rotation.x = Math.PI / 2;
            hm.position.set(w/2 - 0.02, topH - 0.35, 0.2);
            dg.add(hm);
        }
        grp.add(dg);
        return dg;
    }

    assets3d.forEach(asset => {
        const ax  = parseInt(asset.posisi_x);
        const ay  = parseInt(asset.posisi_y);
        const rot = parseInt(asset.rotasi || 0);

        const px = (ax - gridCols / 2 + 0.5) * scale;
        const pz = (ay - gridRows / 2 + 0.5) * scale;

        const grp = new THREE.Group();
        grp.position.set(px, 0, pz);
        grp.rotation.y = -THREE.MathUtils.degToRad(rot);

        const type = asset.tipe_kode;

        // ── PC Station ──────────────────────────────────────────────────────
        if (type === 'PC') {
            buildDesk(grp, 3.0, 1.85, 0x7c4d18, 0x5a3510, false);

            // Monitor bezel (dark frame)
            grp.add(addBox(box(1.74, 1.08, 0.06), 0x111827, 0, 2.42, -0.42));
            // Screen face (slight glow)
            const scrMat = new THREE.MeshLambertMaterial({ color: 0x0c4a6e, emissive: 0x0369a1, emissiveIntensity: 0.25 });
            const scr = new THREE.Mesh(box(1.62, 0.96, 0.04), scrMat);
            scr.position.set(0, 2.42, -0.39);
            grp.add(scr);
            // Monitor neck
            grp.add(addBox(box(0.12, 0.42, 0.12), 0x374151, 0, 2.0, -0.4));
            // Monitor base plate
            grp.add(addBox(box(0.52, 0.06, 0.32), 0x374151, 0, 1.78, -0.4));

            // Tower / CPU box
            grp.add(addBox(box(0.44, 0.88, 0.8), 0x1f2937, -1.1, 1.66, -0.32));
            // Power LED dot
            const ledMat = new THREE.MeshLambertMaterial({ color: 0x22c55e, emissive: 0x16a34a, emissiveIntensity: 1 });
            const led = new THREE.Mesh(cyl(0.022, 0.022, 0.02, 6), ledMat);
            led.rotation.x = Math.PI / 2;
            led.position.set(-1.1, 2.09, -0.71);
            grp.add(led);

            // Keyboard
            grp.add(addBox(box(1.32, 0.05, 0.46), 0xf0f0f0, 0, 1.27, 0.22));
            // Mouse
            grp.add(addBox(box(0.24, 0.05, 0.35), 0xe2e8f0, 0.85, 1.27, 0.18));

            // Chair (behind desk, facing the screen)
            buildChair(grp, 0, 1.08, false);

        // ── Chair only ──────────────────────────────────────────────────────
        } else if (type === 'CHR') {
            buildChair(grp, 0, 0, true);

        // ── Desk only ───────────────────────────────────────────────────────
        } else if (type === 'DESK') {
            buildDesk(grp, 3.1, 2.1, 0xa16207, 0x78440a, false);

        // ── Teacher Desk ────────────────────────────────────────────────────
        } else if (type === 'TDK') {
            buildDesk(grp, 3.7, 2.3, 0x92400e, 0x6b2d0a, true);
            // Modesty panel (front panel hiding legs)
            grp.add(addBox(box(3.7, 0.68, 0.06), 0x78350f, 0, 0.64, -1.12));
            // Name plate holder strip
            grp.add(addBox(box(1.0, 0.06, 0.22), 0xd4a853, 0, 1.27, -0.95));

        // ── TV / Smart Screen ────────────────────────────────────────────────
        } else if (type === 'TV') {
            // TV stand base
            grp.add(addBox(box(1.1, 0.12, 0.55), 0x0f172a, -1.4, 0.95, 0));
            // Stand neck
            const tvNeck = new THREE.Mesh(cyl(0.07, 0.11, 0.82), mat(0x1e293b));
            tvNeck.position.set(-1.4, 1.4, 0);
            grp.add(tvNeck);
            // Screen bezel
            grp.add(addBox(box(0.1, 1.94, 3.18), 0x111827, -1.4, 2.86, 0));
            // Screen face emissive
            const tvScrMat = new THREE.MeshLambertMaterial({ color: 0x0a2540, emissive: 0x0c3a6e, emissiveIntensity: 0.2 });
            const tvScr = new THREE.Mesh(box(0.04, 1.82, 3.04), tvScrMat);
            tvScr.position.set(-1.36, 2.86, 0);
            grp.add(tvScr);
            // Bottom speaker grille strip
            grp.add(addBox(box(0.06, 0.14, 2.2), 0x1e293b, -1.36, 2.0, 0));
            // Power button dot
            const tvBtn = new THREE.Mesh(cyl(0.035, 0.035, 0.03, 8), mat(0xe2e8f0));
            tvBtn.rotation.z = Math.PI / 2;
            tvBtn.position.set(-1.36, 2.86, 1.44);
            grp.add(tvBtn);

        // ── Air Conditioner ──────────────────────────────────────────────────
        } else if (type === 'AC') {
            // Main body
            grp.add(addBox(box(2.6, 0.68, 0.55), 0xf1f5f9, 0, 3.35, -1.25));
            // Lower vent grille slots (5 thin bars)
            for (let i = -2; i <= 2; i++) {
                grp.add(addBox(box(2.52, 0.04, 0.36), 0xd1d5db, i * 0, 3.14, -1.25 + i * 0.04));
            }
            // Diagonal vent bar (direction indicator)
            grp.add(addBox(box(2.52, 0.05, 0.18), 0xd1d5db, 0, 3.24, -1.06));
            // Brand plate
            grp.add(addBox(box(0.7, 0.1, 0.06), 0xc0c8d8, -0.85, 3.56, -1.01));
            // Power LED
            const acLed = new THREE.Mesh(cyl(0.02, 0.02, 0.02, 6), mat(0x22c55e, { emissive: 0x16a34a, emissiveIntensity: 1 }));
            acLed.rotation.z = Math.PI / 2;
            acLed.position.set(1.18, 3.54, -1.01);
            grp.add(acLed);

        // ── Projector ────────────────────────────────────────────────────────
        } else if (type === 'PRJ') {
            // Main body
            grp.add(addBox(box(1.1, 0.46, 0.88), 0xe2e8f0, 0, 3.63, 0));
            // Lens barrel
            const lens = new THREE.Mesh(cyl(0.16, 0.14, 0.28, 10), mat(0x374151));
            lens.rotation.z = Math.PI / 2;
            lens.position.set(0.55, 3.63, 0.04);
            grp.add(lens);
            // Lens glass face (emissive)
            const lensGlass = new THREE.Mesh(cyl(0.1, 0.1, 0.04, 10), new THREE.MeshLambertMaterial({ color: 0x1d4ed8, emissive: 0x3b82f6, emissiveIntensity: 0.5 }));
            lensGlass.rotation.z = Math.PI / 2;
            lensGlass.position.set(0.7, 3.63, 0.04);
            grp.add(lensGlass);
            // Mounting arm
            const arm = new THREE.Mesh(cyl(0.04, 0.04, 0.55), mat(0x6b7280));
            arm.position.set(0, 3.38, 0);
            grp.add(arm);
            // Ceiling mount plate
            grp.add(addBox(box(0.28, 0.06, 0.28), 0x4b5563, 0, 3.13, 0));

        // ── Whiteboard ───────────────────────────────────────────────────────
        } else if (type === 'WBD') {
            // Outer frame
            grp.add(addBox(box(4.28, 2.32, 0.1), 0x475569, 0, 2.26, -1.32));
            // White writing surface
            grp.add(addBox(box(4.08, 2.08, 0.06), 0xf9fafb, 0, 2.26, -1.27));
            // Chalk/marker tray (long box at bottom)
            grp.add(addBox(box(4.08, 0.12, 0.22), 0x374151, 0, 1.26, -1.2));
            // Eraser block sitting on tray
            grp.add(addBox(box(0.36, 0.14, 0.2), 0xe5e7eb, 0.6, 1.37, -1.14));
            // 2 corner mounting bolts
            [[-1.98,-0.94],[1.98,-0.94],[-1.98,0.94],[1.98,0.94]].forEach(([bx,by])=>{
                const bolt = new THREE.Mesh(cyl(0.04,0.04,0.05,6), mat(0x94a3b8));
                bolt.rotation.z = Math.PI/2;
                bolt.position.set(bx, by + 2.26, -1.27);
                grp.add(bolt);
            });

        // ── Ceiling Fan ──────────────────────────────────────────────────────
        } else if (type === 'FAN') {
            const fanY = 3.8;
            // Ceiling rod / downrod
            const rod = new THREE.Mesh(cyl(0.04, 0.04, 0.55), mat(0x6b7280));
            rod.position.set(0, fanY + 0.3, 0);
            grp.add(rod);
            // Motor canopy (dome-shaped using short cylinder)
            const canopy = new THREE.Mesh(cyl(0.26, 0.18, 0.22, 12), mat(0xd1d5db));
            canopy.position.set(0, fanY + 0.02, 0);
            grp.add(canopy);
            // Motor hub
            const hub = new THREE.Mesh(cyl(0.18, 0.18, 0.28, 12), mat(0x9ca3af));
            hub.position.set(0, fanY - 0.1, 0);
            grp.add(hub);
            // 4 blades (angled slightly like real fan)
            [0, 90, 180, 270].forEach(deg => {
                const blade = new THREE.Mesh(box(1.55, 0.04, 0.36), mat(0xc8a96e));
                blade.position.set(0.77, fanY - 0.18, 0);
                blade.rotation.x = 0.12; // slight downward pitch
                const bladeGrp = new THREE.Group();
                bladeGrp.add(blade);
                bladeGrp.rotation.y = THREE.MathUtils.degToRad(deg);
                grp.add(bladeGrp);
            });
            // Light bowl at bottom of motor
            const bowl = new THREE.Mesh(cyl(0.22, 0.12, 0.18, 10), new THREE.MeshLambertMaterial({ color: 0xfff9c4, emissive: 0xfef08a, emissiveIntensity: 0.4, transparent: true, opacity: 0.9 }));
            bowl.position.set(0, fanY - 0.28, 0);
            grp.add(bowl);

        // ── Cabinet / Lemari ─────────────────────────────────────────────────
        } else if (type === 'CAB') {
            // Main body
            grp.add(addBox(box(2.5, 3.0, 1.5), 0xb45309, 0, 1.5, 0));
            // Centre divider
            grp.add(addBox(box(0.06, 2.88, 1.46), 0x92400e, 0, 1.5, 0));
            // Top shelf accent
            grp.add(addBox(box(2.5, 0.08, 1.5), 0x78350f, 0, 2.96, 0));
            // 2 door handles (left & right panel)
            [-0.55, 0.55].forEach(hx => {
                const handle = new THREE.Mesh(cyl(0.035, 0.035, 0.42), mat(0xd4a853));
                handle.rotation.x = Math.PI / 2;
                handle.position.set(hx, 1.5, 0.77);
                grp.add(handle);
            });
            // Feet (4 small blocks)
            [[-1.0,-0.6],[1.0,-0.6],[-1.0,0.6],[1.0,0.6]].forEach(([fx,fz])=>{
                grp.add(addBox(box(0.18,0.18,0.18), 0x451a03, fx, 0.09, fz));
            });

        // ── Generic fallback ─────────────────────────────────────────────────
        } else {
            grp.add(addBox(box(1.4, 1.4, 1.4), 0x475569, 0, 0.7, 0));
        }

        scene.add(grp);
    });

    // ─── 10. Door — frame + slab + handle + threshold ────────────────────────
    function getDoor3DCoords() {
        if (pintuPosisi === 'kiri-atas')    return { x: 0, y: 0 };
        if (pintuPosisi === 'kanan-atas')   return { x: gridCols - 1, y: 0 };
        if (pintuPosisi === 'kanan-bawah')  return { x: gridCols - 1, y: gridRows - 1 };
        if (pintuPosisi === 'tengah-bawah') return { x: Math.floor(gridCols / 2), y: gridRows - 1 };
        return { x: 0, y: gridRows - 1 };
    }
    const dc   = getDoor3DCoords();
    const dpx  = (dc.x - gridCols / 2 + 0.5) * scale;
    const dpz  = (dc.y - gridRows / 2 + 0.5) * scale;
    const dgrp = new THREE.Group();
    dgrp.position.set(dpx, 0, dpz);

    // Door frame
    dgrp.add(addBox(box(0.12, 2.82, 1.98), 0x7c3400, 0, 1.41, 0));
    // Door slab (rotated open ~40°)
    const doorSlab = new THREE.Group();
    doorSlab.add(addBox(box(0.09, 2.7, 1.82), 0x9a3e08, 0.91, 1.35, 0));
    doorSlab.rotation.y = Math.PI / 4.5;
    dgrp.add(doorSlab);
    // Handle
    const dHandle = new THREE.Mesh(cyl(0.03, 0.03, 0.38), mat(0xd4a853));
    dHandle.rotation.z = Math.PI / 2;
    dHandle.position.set(0.06, 1.32, -0.72);
    dgrp.add(dHandle);
    // Threshold strip
    dgrp.add(addBox(box(0.1, 0.07, 2.0), 0x5a3010, 0, 0.035, 0));
    scene.add(dgrp);

    // ─── 11. Demand Rendering loop ────────────────────────────────────────────
    let needsRender = true;
    controls.addEventListener('change', () => { needsRender = true; });

    function animate() {
        requestAnimationFrame(animate);
        if (needsRender) {
            controls.update();
            renderer.render(scene, camera);
            needsRender = false;
        }
    }

    // Langsung render & hilangkan loading setelah scene selesai dibangun
    loading.style.display = 'none';
    needsRender = true;
    animate();

    window.addEventListener('resize', () => {
        const w = viewport.clientWidth  || width;
        const h = viewport.clientHeight || height;
        camera.aspect = w / h;
        camera.updateProjectionMatrix();
        renderer.setSize(w, h);
        needsRender = true;
    });

    } catch (err) {
        // Tampilkan error ke user jika inisialisasi 3D gagal
        loading.innerHTML = '<div style="color:#f87171; font-family:monospace; font-size:12px; padding:20px; text-align:center;">⚠ Gagal memuat 3D:<br>' + err.message + '</div>';
        console.error('[3D Error]', err);
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
