<?php
// ============================================================
//  admin/labs/edit.php — Interactive Room Layout Editor
//  Inventaris Sekolah | PHP Native + MySQL
// ============================================================

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';

// Proteksi: Hanya admin
requireAdmin();

$labId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($labId <= 0) {
    header('Location: ' . APP_URL . '/admin/labs/index.php');
    exit;
}

// Fetch lab data
$stmt = $pdo->prepare("SELECT * FROM labs WHERE id = ? AND is_active = 1");
$stmt->execute([$labId]);
$lab = $stmt->fetch();

if (!$lab) {
    header('Location: ' . APP_URL . '/admin/labs/index.php');
    exit;
}

$pageTitle = 'Edit Tata Letak ' . htmlspecialchars($lab['nama']);
$activeNav = 'labs';

// Fetch all active assets in this lab
$stmtAssets = $pdo->prepare("
    SELECT a.*, at.kode AS tipe_kode, at.icon AS tipe_icon 
    FROM assets a
    JOIN asset_types at ON at.id = a.asset_type_id
    WHERE a.lab_id = ? AND a.is_active = 1
");
$stmtAssets->execute([$labId]);
$assetsList = $stmtAssets->fetchAll();

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
    <a href="<?= APP_URL ?>/admin/labs/index.php">Kelola Lab</a>
    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" style="margin: 0 4px; opacity: 0.5;">
      <path d="M4.5 9l3-3-3-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <b>Edit Tata Letak</b>
  </div>
</div>

<div class="content">

  <div class="page-heading">
    <h1>Editor Tata Letak Denah: <?= htmlspecialchars($lab['nama']) ?></h1>
    <p>Atur ukuran ruangan dan seret komponen ke grid. Perubahan kode aset terupdate secara real-time. Drag item ke tong sampah untuk menghapusnya.</p>
  </div>

  <!-- 3-Panel Layout (Figma/Photoshop Style) -->
  <div class="editor-container">

    <!-- Panel Kiri: Informasi Lab & Toolbox -->
    <div style="display:flex; flex-direction:column; gap:20px;">
      <!-- Lab Form -->
      <div class="editor-panel">
        <div class="editor-panel-head">
          <span class="editor-panel-title">Informasi & Ukuran Lab</span>
        </div>
        <div class="editor-panel-body" style="padding: 16px;">
          <form id="labForm" style="display:flex; flex-direction:column; gap:12px;">
            <div class="form-group">
              <label class="form-label" for="lab_nama">Nama Laboratorium</label>
              <input type="text" id="lab_nama" class="form-control" value="<?= htmlspecialchars($lab['nama']) ?>" required>
            </div>
            <div class="form-group">
              <label class="form-label" for="lab_kode">Kode Lab</label>
              <input type="text" id="lab_kode" class="form-control" value="<?= htmlspecialchars($lab['kode_lab']) ?>" required>
            </div>
            <div class="form-group">
              <label class="form-label" for="lab_lantai">Lantai</label>
              <input type="number" id="lab_lantai" class="form-control" value="<?= $lab['lantai'] ?>" min="1" required>
            </div>
            <div class="form-group">
              <label class="form-label" for="lab_kapasitas">Kapasitas Siswa</label>
              <input type="number" id="lab_kapasitas" class="form-control" value="<?= $lab['kapasitas'] ?>" min="1" required>
            </div>
            <div class="form-group">
              <label class="form-label" for="lab_desc">Deskripsi</label>
              <textarea id="lab_desc" class="form-control" rows="2" style="resize:vertical;"><?= htmlspecialchars($lab['deskripsi']) ?></textarea>
            </div>
            <div style="border-top:1px solid var(--border); margin: 6px 0;"></div>
            
            <!-- Grid Size Controls -->
            <div class="form-group">
              <label class="form-label">Lebar Ruangan (Grid Kolom)</label>
              <div style="display:flex; gap:6px; align-items:center;">
                <button type="button" class="btn" onclick="adjustGridSize('cols', -1)" style="padding: 8px; font-weight:bold;">−</button>
                <input type="number" id="grid_cols" class="form-control" value="<?= $lab['grid_cols'] ?>" min="6" max="24" readonly style="text-align:center; font-weight:600;">
                <button type="button" class="btn" onclick="adjustGridSize('cols', 1)" style="padding: 8px; font-weight:bold;">+</button>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Panjang Ruangan (Grid Baris)</label>
              <div style="display:flex; gap:6px; align-items:center;">
                <button type="button" class="btn" onclick="adjustGridSize('rows', -1)" style="padding: 8px; font-weight:bold;">−</button>
                <input type="number" id="grid_rows" class="form-control" value="<?= $lab['grid_rows'] ?>" min="6" max="24" readonly style="text-align:center; font-weight:600;">
                <button type="button" class="btn" onclick="adjustGridSize('rows', 1)" style="padding: 8px; font-weight:bold;">+</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Panel Tengah: Canvas Editor Grid (Responsive Aspect Ratio) & Save Actions -->
    <div style="display:flex; flex-direction:column; align-items:center; gap:20px; width:100%;">
      <div class="editor-canvas-panel" style="width:100%;">
        <div class="editor-room-wall" style="width: 100%; max-width: 600px;">
          <div class="editor-grid" id="editor_grid" style="--grid-cols: <?= $lab['grid_cols'] ?>; --grid-rows: <?= $lab['grid_rows'] ?>;">
            <!-- Dynamic JS Cells -->
          </div>
        </div>
      </div>
       <!-- Action Buttons Row (Simpan, 3D, & Tong Sampah) -->
      <div style="display: flex; gap: 12px; align-items: center; width: 100%; max-width: 600px; flex-wrap: wrap;">
        <button type="button" class="btn btn-dark" onclick="saveLayout()" style="flex: 1.4; padding: 12px; font-size: 13px; font-weight: 600; justify-content: center; box-shadow: var(--shadow-md);">
          <svg width="15" height="15" viewBox="0 0 15 15" fill="none" style="margin-right: 6px;">
            <path d="M2.5 1.5h8l3 3v9a1 1 0 01-1 1h-10a1 1 0 01-1-1v-11a1 1 0 011-1z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
            <path d="M4.5 1.5v4h6v-4M4.5 13.5v-4h6v4" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
          </svg>
          Simpan Tata Letak
        </button>

        <button type="button" class="btn" onclick="open3DModal()" style="flex: 1.1; padding: 12px; font-size: 13px; font-weight: 600; justify-content: center; background: #0f172a; color:#fff; border-color:#1e293b; display:flex; align-items:center; gap:6px; box-shadow: var(--shadow-md);">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
          Pratinjau 3D
        </button>
        
        <!-- Trash Can Drop Zone -->
        <div id="trash_can" class="editor-trash-zone" style="flex: 1.1; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 10px; border: 2px dashed #fca5a5; border-radius: 8px; background: #fff5f5; color: var(--red); cursor: pointer; transition: all 200ms ease; font-weight: 600; font-size: 12px;"
             ondragover="allowTrashDrop(event)" ondragenter="enterTrash(event)" ondragleave="leaveTrash(event)" ondrop="dropOnTrash(event)">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="3 6 5 6 21 6"></polyline>
            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
            <line x1="10" y1="11" x2="10" y2="17"></line>
            <line x1="14" y1="11" x2="14" y2="17"></line>
          </svg>
          <span>Drop Hapus</span>
        </div>
      </div>
    </div></div>

    <!-- Panel Kanan: Toolbox & Inspector Terpisah -->
    <div style="display:flex; flex-direction:column; gap:20px;">
      <!-- Toolbox Panel -->
      <div class="editor-panel">
        <div class="editor-panel-head">
          <span class="editor-panel-title">Toolbox Komponen</span>
        </div>
        <div class="editor-panel-body" style="padding: 16px;">
          <div class="toolbox-list">
            <div class="toolbox-item" draggable="true" ondragstart="onSidebarDragStart(event, 'PC')">
              <div class="toolbox-item-icon">
                <svg viewBox="0 0 80 80" fill="none">
                  <rect x="12" y="32" width="56" height="32" rx="4" fill="#f5efe6" stroke="#d0c9bc" stroke-width="1.5"/>
                  <rect x="28" y="66" width="24" height="10" rx="3" fill="#e2e8f0" stroke="#cbd5e1" stroke-width="1.5"/>
                  <rect x="20" y="22" width="40" height="7" rx="3" fill="var(--green)" opacity="0.3"/>
                  <rect x="22" y="24" width="36" height="3" rx="1" fill="#475569"/>
                </svg>
              </div>
              <span class="toolbox-item-lbl">Komputer</span>
            </div>

            <div class="toolbox-item" draggable="true" ondragstart="onSidebarDragStart(event, 'AC')">
              <div class="toolbox-item-icon">
                <svg viewBox="0 0 80 80" fill="none">
                  <rect x="2" y="15" width="12" height="50" rx="2" fill="#ffffff" stroke="#cbd5e1" stroke-width="1.5"/>
                  <path d="M18,25 L32,25 M18,40 L32,40 M18,55 L32,55" stroke="#38bdf8" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
              </div>
              <span class="toolbox-item-lbl">AC</span>
            </div>

            <div class="toolbox-item" draggable="true" ondragstart="onSidebarDragStart(event, 'PRJ')">
              <div class="toolbox-item-icon">
                <svg viewBox="0 0 80 80" fill="none">
                  <polygon points="40,24 15,0 65,0" fill="#38bdf8" opacity="0.15"/>
                  <rect x="24" y="28" width="32" height="24" rx="4" fill="#ffffff" stroke="#475569" stroke-width="1.5"/>
                  <rect x="34" y="22" width="12" height="6" rx="1" fill="#334155" stroke="#1e293b" stroke-width="1"/>
                </svg>
              </div>
              <span class="toolbox-item-lbl">Proyektor</span>
            </div>

            <div class="toolbox-item" draggable="true" ondragstart="onSidebarDragStart(event, 'TV')">
              <div class="toolbox-item-icon">
                <svg viewBox="0 0 80 80" fill="none">
                  <rect x="2" y="10" width="10" height="60" rx="2" fill="#0f172a" stroke="#334155" stroke-width="2"/>
                  <rect x="4" y="12" width="6" height="56" rx="1" fill="#1e293b"/>
                </svg>
              </div>
              <span class="toolbox-item-lbl">Smart TV</span>
            </div>

            <div class="toolbox-item" draggable="true" ondragstart="onSidebarDragStart(event, 'DESK')">
              <div class="toolbox-item-icon">
                <svg viewBox="0 0 80 80" fill="none">
                  <rect x="12" y="24" width="56" height="40" rx="4" fill="#f5efe6" stroke="#d0c9bc" stroke-width="1.5"/>
                </svg>
              </div>
              <span class="toolbox-item-lbl">Meja</span>
            </div>

            <div class="toolbox-item" draggable="true" ondragstart="onSidebarDragStart(event, 'CHR')">
              <div class="toolbox-item-icon">
                <svg viewBox="0 0 80 80" fill="none">
                  <rect x="24" y="24" width="32" height="32" rx="6" fill="#e2e8f0" stroke="#cbd5e1" stroke-width="1.5"/>
                  <rect x="28" y="18" width="24" height="6" rx="2" fill="#475569" stroke="#334155" stroke-width="1"/>
                </svg>
              </div>
              <span class="toolbox-item-lbl">Kursi</span>
            </div>

            <div class="toolbox-item" draggable="true" ondragstart="onSidebarDragStart(event, 'WBD')">
              <div class="toolbox-item-icon">
                <svg viewBox="0 0 80 80" fill="none">
                  <rect x="4" y="24" width="72" height="10" rx="1" fill="#ffffff" stroke="#475569" stroke-width="2"/>
                  <line x1="8" y1="29" x2="72" y2="29" stroke="#cbd5e1" stroke-width="1" stroke-dasharray="2,2"/>
                </svg>
              </div>
              <span class="toolbox-item-lbl">Papan Tulis</span>
            </div>

            <div class="toolbox-item" draggable="true" ondragstart="onSidebarDragStart(event, 'FAN')">
              <div class="toolbox-item-icon">
                <svg viewBox="0 0 80 80" fill="none">
                  <circle cx="40" cy="40" r="18" fill="none" stroke="#64748b" stroke-width="1.5"/>
                  <circle cx="40" cy="40" r="4" fill="#475569"/>
                  <path d="M 40,40 L 40,22 Q 43,26 40,40 Z" fill="#94a3b8"/>
                  <path d="M 40,40 L 56,49 Q 51,46 40,40 Z" fill="#94a3b8"/>
                  <path d="M 40,40 L 24,49 Q 29,46 40,40 Z" fill="#94a3b8"/>
                </svg>
              </div>
              <span class="toolbox-item-lbl">Kipas Angin</span>
            </div>

            <div class="toolbox-item" draggable="true" ondragstart="onSidebarDragStart(event, 'CAB')">
              <div class="toolbox-item-icon">
                <svg viewBox="0 0 80 80" fill="none">
                  <rect x="12" y="20" width="56" height="40" rx="3" fill="#f5efe6" stroke="#a19785" stroke-width="2"/>
                  <line x1="40" y1="20" x2="40" y2="60" stroke="#a19785" stroke-width="1.5"/>
                  <circle cx="36" cy="40" r="2" fill="#475569"/>
                  <circle cx="44" cy="40" r="2" fill="#475569"/>
                </svg>
              </div>
              <span class="toolbox-item-lbl">Lemari</span>
            </div>

            <div class="toolbox-item" draggable="true" ondragstart="onSidebarDragStart(event, 'TDK')">
              <div class="toolbox-item-icon">
                <svg viewBox="0 0 80 80" fill="none">
                  <rect x="8" y="20" width="64" height="40" rx="4" fill="#e7ddcc" stroke="#a19785" stroke-width="2.5"/>
                  <rect x="14" y="24" width="16" height="32" rx="2" fill="none" stroke="#a19785" stroke-width="1"/>
                  <circle cx="22" cy="52" r="1.5" fill="#475569"/>
                </svg>
              </div>
              <span class="toolbox-item-lbl">Meja Guru</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Dedicated Inspector Panel (Figma style) -->
      <div class="editor-panel">
        <div class="editor-panel-head">
          <span class="editor-panel-title" id="inspector_title">Inspector Komponen</span>
        </div>
        <div class="editor-panel-body" style="padding: 16px;" id="inspector_content">
          <div class="inspector-empty">
            Silakan klik salah satu komponen di dalam grid denah untuk mengubah informasi detail, merotasi, atau menghapusnya.
          </div>
        </div>
      </div>
    </div>

  </div>

</div>

<!-- Toast Notification Container -->
<div id="toast_container" class="toast-container"></div>

<!-- State management & Drag-and-drop JS -->
<script>
// JSON-serialized active assets list from PHP
let assets = <?= json_encode($assetsList) ?>;

// Clean professional Toast notification function
function showToast(msg, type = 'success') {
    const container = document.getElementById('toast_container');
    if (!container) return;
    
    const toast = document.createElement('div');
    toast.className = `toast-item ${type}`;
    
    let iconSVG = '';
    if (type === 'success') {
        iconSVG = `
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--green)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="20 6 9 17 4 12"></polyline>
        </svg>`;
    } else if (type === 'error') {
        iconSVG = `
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--red)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="15" y1="9" x2="9" y2="15"></line>
          <line x1="9" y1="9" x2="15" y2="15"></line>
        </svg>`;
    } else {
        iconSVG = `
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="16" x2="12" y2="12"></line>
          <line x1="12" y1="8" x2="12.01" y2="8"></line>
        </svg>`;
    }
    
    toast.innerHTML = `
      ${iconSVG}
      <span class="toast-msg">${msg}</span>
    `;
    
    container.appendChild(toast);
    
    // Trigger animation
    setTimeout(() => toast.classList.add('show'), 10);
    
    // Auto dismiss after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Config state
let gridCols = <?= $lab['grid_cols'] ?>;
let gridRows = <?= $lab['grid_rows'] ?>;
let pintuPosisi = '<?= htmlspecialchars($lab['pintu_posisi'] ?? 'kiri-bawah') ?>';
let selectedAssetId = null;
let dragSource = null;

// Helper: Tipe ID mapping
const typeIdMap = {
    'PC': 1,
    'MSE': 2,
    'KB': 3,
    'TV': 4,
    'PRJ': 5,
    'AC': 6,
    'DESK': 7,
    'CHR': 8,
    'WBD': 9,
    'FAN': 10,
    'CAB': 11,
    'TDK': 12
};

// Get door coordinate mapping based on pintuPosisi preset
function getDoorCellCoords() {
    if (pintuPosisi === 'kiri-atas') return { x: 0, y: 0 };
    if (pintuPosisi === 'kanan-atas') return { x: gridCols - 1, y: 0 };
    if (pintuPosisi === 'kanan-bawah') return { x: gridCols - 1, y: gridRows - 1 };
    if (pintuPosisi === 'tengah-bawah') return { x: Math.floor(gridCols / 2), y: gridRows - 1 };
    return { x: 0, y: gridRows - 1 }; // default kiri-bawah
}

// Sugest next code automatically
function suggestNextCode(type) {
    let maxNum = 0;
    assets.forEach(a => {
        if (a.tipe_kode === type) {
            let match = a.kode_aset.match(/\d+/);
            if (match) {
                let num = parseInt(match[0]);
                if (num > maxNum) maxNum = num;
            }
        }
    });
    let nextNum = maxNum + 1;
    let suffix = String(nextNum).padStart(2, '0');
    let labCodeSuffix = '<?= htmlspecialchars($lab['kode_lab']) ?>'.replace('LAB-', '');
    return `${type}-${labCodeSuffix}${suffix}`;
}

function getComponentDefaultName(type) {
    return {
        'PC': 'Personal Computer',
        'TV': 'Smart TV / Monitor Dinding',
        'AC': 'Air Conditioner',
        'PRJ': 'Proyektor',
        'DESK': 'Meja Standalone',
        'CHR': 'Kursi Standalone',
        'WBD': 'Papan Tulis / Whiteboard',
        'FAN': 'Kipas Angin',
        'CAB': 'Lemari Aset / Kabinet',
        'TDK': 'Meja Guru'
    }[type] || 'Aset Baru';
}

// Generate SVG code dynamically (no label text inside SVG wrapper to prevent rotating labels)
function getAssetSVG(type) {
    if (type === 'PC') {
        return `
        <svg viewBox="0 0 80 80" style="width:100%; height:100%;">
            <rect class="svg-desk" x="12" y="32" width="56" height="32" rx="4" />
            <rect class="svg-chair" x="28" y="66" width="24" height="10" rx="3" />
            <rect class="svg-monitor-glow" x="20" y="22" width="40" height="7" rx="3" style="fill: var(--green); opacity: 0.2;" />
            <rect class="svg-monitor" x="22" y="24" width="36" height="3" rx="1" style="fill: #475569;" />
            <rect x="36" y="27" width="8" height="2" fill="#475569" />
        </svg>`;
    } else if (type === 'TV') {
        return `
        <svg viewBox="0 0 80 80" style="width:100%; height:100%;">
            <rect class="svg-tv-unit" x="2" y="10" width="10" height="60" rx="2" style="fill:#0f172a; stroke:#334155;" />
            <rect class="svg-tv-screen" x="4" y="12" width="6" height="56" rx="1" style="fill:#1e293b;" />
            <rect class="svg-tv-glow" x="12" y="12" width="15" height="56" rx="4" style="fill:#38bdf8; opacity:0.15;" />
        </svg>`;
    } else if (type === 'AC') {
        return `
        <svg viewBox="0 0 80 80" style="width:100%; height:100%;">
            <rect class="svg-ac-unit" x="2" y="15" width="12" height="50" rx="2" style="fill:#ffffff; stroke:#cbd5e1; stroke-width:1.5;" />
            <path class="svg-ac-flow" d="M 18,25 L 32,25 M 18,40 L 32,40 M 18,55 L 32,55" style="stroke:#38bdf8; stroke-width:1.5; stroke-linecap:round; opacity:0.6;" />
        </svg>`;
    } else if (type === 'PRJ') {
        return `
        <svg viewBox="0 0 80 80" style="width:100%; height:100%;">
            <polygon class="svg-proj-beam" points="40,24 15,0 65,0" style="fill:#38bdf8; opacity:0.15;" />
            <rect class="svg-proj-unit" x="24" y="28" width="32" height="24" rx="4" style="fill:#ffffff; stroke:#475569; stroke-width:1.5;" />
            <rect x="34" y="22" width="12" height="6" rx="1" fill="#334155" stroke="#1e293b" stroke-width="1" />
            <ellipse cx="40" cy="22" rx="5" ry="1.2" fill="#38bdf8" />
        </svg>`;
    } else if (type === 'DESK') {
        return `
        <svg viewBox="0 0 80 80" style="width:100%; height:100%;">
            <rect class="svg-desk" x="12" y="24" width="56" height="40" rx="4" />
        </svg>`;
    } else if (type === 'CHR') {
        return `
        <svg viewBox="0 0 80 80" style="width:100%; height:100%;">
            <rect class="svg-chair" x="24" y="24" width="32" height="32" rx="6" />
            <rect x="28" y="18" width="24" height="6" rx="2" fill="#475569" stroke="#334155" stroke-width="1" />
        </svg>`;
    } else if (type === 'WBD') {
        return `
        <svg viewBox="0 0 80 80" style="width:100%; height:100%;">
            <rect x="4" y="24" width="72" height="10" rx="1" fill="#ffffff" stroke="#475569" stroke-width="2" />
            <line x1="8" y1="29" x2="72" y2="29" stroke="#cbd5e1" stroke-width="1" stroke-dasharray="2,2" />
        </svg>`;
    } else if (type === 'FAN') {
        return `
        <svg viewBox="0 0 80 80" style="width:100%; height:100%;">
            <circle cx="40" cy="40" r="18" fill="none" stroke="#64748b" stroke-width="1.5" />
            <circle cx="40" cy="40" r="4" fill="#475569" />
            <path d="M 40,40 L 40,22 Q 43,26 40,40 Z" fill="#94a3b8" />
            <path d="M 40,40 L 56,49 Q 51,46 40,40 Z" fill="#94a3b8" />
            <path d="M 40,40 L 24,49 Q 29,46 40,40 Z" fill="#94a3b8" />
        </svg>`;
    } else if (type === 'CAB') {
        return `
        <svg viewBox="0 0 80 80" style="width:100%; height:100%;">
            <rect x="12" y="20" width="56" height="40" rx="3" fill="#f5efe6" stroke="#a19785" stroke-width="2" />
            <line x1="40" y1="20" x2="40" y2="60" stroke="#a19785" stroke-width="1.5" />
            <circle cx="36" cy="40" r="2" fill="#475569" />
            <circle cx="44" cy="40" r="2" fill="#475569" />
        </svg>`;
    } else if (type === 'TDK') {
        return `
        <svg viewBox="0 0 80 80" style="width:100%; height:100%;">
            <rect x="8" y="20" width="64" height="40" rx="4" fill="#e7ddcc" stroke="#a19785" stroke-width="2.5" />
            <rect x="14" y="24" width="16" height="32" rx="2" fill="none" stroke="#a19785" stroke-width="1" />
            <circle cx="22" cy="52" r="1.5" fill="#475569" />
        </svg>`;
    } else {
        return `<span style="font-size:18px;">📦</span>`;
    }
}

// Render grid layout canvas
function renderGrid() {
    const gridContainer = document.getElementById('editor_grid');
    gridContainer.innerHTML = '';
    
    // Set custom CSS variables for responsive size
    gridContainer.style.setProperty('--grid-cols', gridCols);
    gridContainer.style.setProperty('--grid-rows', gridRows);
    
    // Calculate aspect ratio and bounds to scale the wall and grid without overflow scrollbars
    const wall = document.querySelector('.editor-room-wall');
    const W_max = 520;
    const H_max = 420;
    const aspect = gridCols / gridRows;
    let w, h;
    
    if (W_max / H_max > aspect) {
        // height is the constraint
        h = H_max;
        w = h * aspect;
    } else {
        // width is the constraint
        w = W_max;
        h = w / aspect;
    }
    
    // Apply dimensions to wall
    wall.style.width = Math.floor(w) + 'px';
    wall.style.height = Math.floor(h) + 'px';
    
    // Subtract padding (16px padding on left/right and top/bottom = 32px)
    const innerW = w - 32;
    const innerH = h - 32;
    gridContainer.style.width = Math.floor(innerW) + 'px';
    gridContainer.style.height = Math.floor(innerH) + 'px';
    
    const doorCoords = getDoorCellCoords();
    
    for (let y = 0; y < gridRows; y++) {
        for (let x = 0; x < gridCols; x++) {
            const cell = document.createElement('div');
            cell.className = 'editor-cell';
            cell.setAttribute('data-x', x);
            cell.setAttribute('data-y', y);
            
            const isDoorCell = (x === doorCoords.x && y === doorCoords.y);
            const asset = assets.find(a => parseInt(a.posisi_x) === x && parseInt(a.posisi_y) === y);
            
            if (isDoorCell) {
                cell.classList.add('occupied', 'door-cell');
                cell.setAttribute('draggable', 'true');
                
                let doorSVG = '';
                if (pintuPosisi === 'kiri-bawah') {
                    doorSVG = `
                    <svg viewBox="0 0 80 80" style="width:100%; height:100%; pointer-events:none;">
                      <path d="M 15,15 A 65,65 0 0,1 80,80" fill="none" stroke="var(--red)" stroke-width="3.5" stroke-dasharray="3,3" />
                      <line x1="15" y1="80" x2="15" y2="15" stroke="var(--red)" stroke-width="5" />
                    </svg>`;
                } else if (pintuPosisi === 'kanan-bawah') {
                    doorSVG = `
                    <svg viewBox="0 0 80 80" style="width:100%; height:100%; pointer-events:none;">
                      <path d="M 65,15 A 65,65 0 0,0 0,80" fill="none" stroke="var(--red)" stroke-width="3.5" stroke-dasharray="3,3" />
                      <line x1="65" y1="80" x2="65" y2="15" stroke="var(--red)" stroke-width="5" />
                    </svg>`;
                } else if (pintuPosisi === 'kiri-atas') {
                    doorSVG = `
                    <svg viewBox="0 0 80 80" style="width:100%; height:100%; pointer-events:none;">
                      <path d="M 15,65 A 65,65 0 0,0 80,0" fill="none" stroke="var(--red)" stroke-width="3.5" stroke-dasharray="3,3" />
                      <line x1="15" y1="0" x2="15" y2="65" stroke="var(--red)" stroke-width="5" />
                    </svg>`;
                } else if (pintuPosisi === 'kanan-atas') {
                    doorSVG = `
                    <svg viewBox="0 0 80 80" style="width:100%; height:100%; pointer-events:none;">
                      <path d="M 65,65 A 65,65 0 0,1 0,0" fill="none" stroke="var(--red)" stroke-width="3.5" stroke-dasharray="3,3" />
                      <line x1="65" y1="0" x2="65" y2="65" stroke="var(--red)" stroke-width="5" />
                    </svg>`;
                } else if (pintuPosisi === 'tengah-bawah') {
                    doorSVG = `
                    <svg viewBox="0 0 80 80" style="width:100%; height:100%; pointer-events:none;">
                      <path d="M 15,15 A 65,65 0 0,1 80,80" fill="none" stroke="var(--red)" stroke-width="3.5" stroke-dasharray="3,3" />
                      <line x1="15" y1="80" x2="15" y2="15" stroke="var(--red)" stroke-width="5" />
                    </svg>`;
                }
                
                cell.innerHTML = `
                ${doorSVG}
                <span class="editor-cell-label" style="color:var(--red); font-weight:bold; font-size: 8px;">🚪 PINTU</span>
                `;
                
                cell.ondragstart = (e) => {
                    dragSource = { type: 'door' };
                    cell.style.opacity = '0.5';
                };
                cell.ondragend = () => {
                    cell.style.opacity = '1';
                };
                
                cell.onclick = (e) => {
                    e.stopPropagation();
                    selectedAssetId = null;
                    document.getElementById('inspector_title').innerText = 'Inspector: Pintu Masuk';
                    document.getElementById('inspector_content').innerHTML = `
                    <div class="inspector-panel">
                      <p style="font-size:13px; color:var(--text-muted); line-height:1.5;">
                        Ini adalah pintu masuk laboratorium. Anda dapat **menyeret (drag) pintu ini** langsung dari sel ini ke dinding tepi ruangan lainnya untuk memindahkan posisi arah masuk secara visual.
                      </p>
                      <div class="form-group">
                        <label class="form-label">Posisi Saat Ini</label>
                        <input type="text" class="form-control" value="${pintuPosisi.toUpperCase()}" disabled style="background:#f1f5f9; font-weight:600;">
                      </div>
                    </div>`;
                    
                    document.querySelectorAll('.editor-cell').forEach(c => c.classList.remove('selected'));
                    cell.classList.add('selected');
                };
                
                if (selectedAssetId === null) {
                    // if door was clicked, keep selected class
                    cell.classList.add('selected');
                }
            } else if (asset) {
                cell.classList.add('occupied');
                
                // Render SVG inside a div wrapper for smooth CSS transitions and rotations
                const rot = asset.rotasi || 0;
                cell.innerHTML = `
                <button type="button" class="cell-hover-rotate-btn" title="Rotasi 90°" onclick="event.stopPropagation(); rotateAsset('${asset.id}')">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:12px; height:12px; display:block;">
                    <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                  </svg>
                </button>
                <div class="asset-icon-wrapper" style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; transform: rotate(${rot}deg); transform-origin: center; transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);">
                  ${getAssetSVG(asset.tipe_kode)}
                </div>
                <span class="editor-cell-label">${asset.kode_aset}</span>`;
                
                cell.setAttribute('draggable', 'true');
                
                if (asset.id === selectedAssetId) {
                    cell.classList.add('selected');
                }
                
                cell.onclick = (e) => {
                    e.stopPropagation();
                    selectAsset(asset.id);
                };
                
                // Drag start
                cell.ondragstart = (e) => {
                    dragSource = { type: 'grid', id: asset.id, x: x, y: y };
                    cell.style.opacity = '0.4';
                };
                
                cell.ondragend = () => {
                    cell.style.opacity = '1';
                };
            } else {
                // Empty cell placeholder
                cell.innerHTML = '<span style="opacity: 0.15; font-size: 6px;">●</span>';
                
                // Dragover, Drop handlers
                cell.ondragover = (e) => e.preventDefault();
                cell.ondrop = (e) => {
                    e.preventDefault();
                    onDropOnCell(x, y);
                };
            }
            
            gridContainer.appendChild(cell);
        }
    }
}

// Drag & drop handlers
function onSidebarDragStart(e, componentType) {
    dragSource = { type: 'sidebar', component: componentType };
}

function onDropOnCell(targetX, targetY) {
    if (!dragSource) return;
    
    if (dragSource.type === 'door') {
        // Calculate closest door preset
        if (targetY === 0) {
            pintuPosisi = (targetX < gridCols / 2) ? 'kiri-atas' : 'kanan-atas';
        } else if (targetY === gridRows - 1) {
            const mid = Math.floor(gridCols / 2);
            if (Math.abs(targetX - mid) <= 1) {
                pintuPosisi = 'tengah-bawah';
            } else if (targetX < gridCols / 2) {
                pintuPosisi = 'kiri-bawah';
            } else {
                pintuPosisi = 'kanan-bawah';
            }
        } else {
            if (targetX < gridCols / 2) {
                pintuPosisi = (targetY < gridRows / 2) ? 'kiri-atas' : 'kiri-bawah';
            } else {
                pintuPosisi = (targetY < gridRows / 2) ? 'kanan-atas' : 'kanan-bawah';
            }
        }
        
        // Clear any assets on the door's target preset coordinate
        const doorCoords = getDoorCellCoords();
        assets = assets.filter(a => !(parseInt(a.posisi_x) === doorCoords.x && parseInt(a.posisi_y) === doorCoords.y));
        
        selectedAssetId = null;
        renderGrid();
        
        document.getElementById('inspector_title').innerText = 'Inspector: Pintu Masuk';
        document.getElementById('inspector_content').innerHTML = `
        <div class="inspector-panel">
          <p style="font-size:13px; color:var(--text-muted); line-height:1.5;">
            Ini adalah pintu masuk laboratorium. Anda dapat **menyeret (drag) pintu ini** langsung dari sel ini ke dinding tepi ruangan lainnya untuk memindahkan posisi arah masuk secara visual.
          </p>
          <div class="form-group">
            <label class="form-label">Posisi Saat Ini</label>
            <input type="text" class="form-control" value="${pintuPosisi.toUpperCase()}" disabled style="background:#f1f5f9; font-weight:600;">
          </div>
        </div>`;
        
        dragSource = null;
        return;
    }
    
    // Cegah penumpukan (sudah ada asset)
    const occupant = assets.find(a => parseInt(a.posisi_x) === targetX && parseInt(a.posisi_y) === targetY);
    if (occupant) {
        showToast("Koordinat cell sudah ditempati komponen lain!", "error");
        return;
    }
    
    if (dragSource.type === 'sidebar') {
        const type = dragSource.component;
        const nextCode = suggestNextCode(type);
        const newAsset = {
            id: 'temp_' + Date.now() + '_' + Math.floor(Math.random() * 1000),
            lab_id: <?= $labId ?>,
            tipe_kode: type,
            kode_aset: nextCode,
            nama: getComponentDefaultName(type),
            merk: '',
            model: '',
            serial_number: '',
            kondisi: 'baik',
            keterangan: '',
            rotasi: 0,
            posisi_x: targetX,
            posisi_y: targetY
        };
        assets.push(newAsset);
        selectedAssetId = newAsset.id;
        renderGrid();
        showInspector(newAsset);
    } else if (dragSource.type === 'grid') {
        const asset = assets.find(a => a.id == dragSource.id);
        if (asset) {
            asset.posisi_x = targetX;
            asset.posisi_y = targetY;
            selectedAssetId = asset.id;
            renderGrid();
            showInspector(asset);
        }
    }
    
    dragSource = null;
}

// Select asset and show detail in sidebar inspector
function selectAsset(id) {
    selectedAssetId = id;
    
    // Refresh selection highlights
    document.querySelectorAll('.editor-cell').forEach(c => c.classList.remove('selected'));
    const asset = assets.find(a => a.id == id);
    if (asset) {
        const cell = document.querySelector(`.editor-cell[data-x="${asset.posisi_x}"][data-y="${asset.posisi_y}"]`);
        if (cell) cell.classList.add('selected');
        showInspector(asset);
    }
}

// Inspector Form Binding (Photoshop style)
function showInspector(asset) {
    document.getElementById('inspector_title').innerText = 'Inspector: ' + asset.kode_aset;
    
    const inspector = document.getElementById('inspector_content');
    inspector.innerHTML = `
    <div class="inspector-panel">
      <div class="form-group">
        <label class="form-label">Tipe Komponen</label>
        <input type="text" class="form-control" value="${asset.tipe_kode}" disabled style="background:#f1f5f9; cursor:not-allowed;">
      </div>
      <div class="form-group">
        <label class="form-label">Kode Aset (Rename Bebas)</label>
        <input type="text" id="insp_kode" class="form-control" value="${asset.kode_aset || ''}" required>
      </div>
      <div class="form-group">
        <label class="form-label">Nama Komponen</label>
        <input type="text" id="insp_nama" class="form-control" value="${asset.nama || ''}" required>
      </div>
      <div class="form-group">
        <label class="form-label">Merk</label>
        <input type="text" id="insp_merk" class="form-control" value="${asset.merk || ''}">
      </div>
      <div class="form-group">
        <label class="form-label">Model / Seri</label>
        <input type="text" id="insp_model" class="form-control" value="${asset.model || ''}">
      </div>
      <div class="form-group">
        <label class="form-label">Nomor Serial</label>
        <input type="text" id="insp_sn" class="form-control" value="${asset.serial_number || ''}">
      </div>
      <div class="form-group">
        <label class="form-label">Kondisi</label>
        <select id="insp_kondisi" class="form-control">
          <option value="baik" ${asset.kondisi === 'baik' ? 'selected' : ''}>Baik</option>
          <option value="maintenance" ${asset.kondisi === 'maintenance' ? 'selected' : ''}>Maintenance</option>
          <option value="rusak" ${asset.kondisi === 'rusak' ? 'selected' : ''}>Rusak</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Catatan Pemeliharaan</label>
        <textarea id="insp_ket" class="form-control" rows="2" style="resize:vertical;">${asset.keterangan || ''}</textarea>
      </div>
      
      <!-- Rotate & Delete Row -->
      <div style="display:flex; gap:8px; margin-top:8px;">
        <button type="button" class="btn" style="flex:1.2; justify-content:center; padding:10px; font-weight:600; border-color:#bae6fd; background:#e0f2fe; color:#0369a1;" onclick="rotateAsset('${asset.id}')">
          🔄 Putar 90°
        </button>
        <button type="button" class="btn" style="flex:1; background:#fee2e2; border-color:#fca5a5; color:var(--red); font-weight:600; justify-content:center; padding:10px;" onclick="deletePlacedAsset('${asset.id}')">
          Hapus
        </button>
      </div>
    </div>`;
    
    // Dynamic inputs binding
    document.getElementById('insp_kode').oninput = (e) => {
        asset.kode_aset = e.target.value;
        updateCellLabel(asset.id, e.target.value);
        document.getElementById('inspector_title').innerText = 'Inspector: ' + e.target.value;
    };
    document.getElementById('insp_nama').oninput = (e) => { asset.nama = e.target.value; };
    document.getElementById('insp_merk').oninput = (e) => { asset.merk = e.target.value; };
    document.getElementById('insp_model').oninput = (e) => { asset.model = e.target.value; };
    document.getElementById('insp_sn').oninput = (e) => { asset.serial_number = e.target.value; };
    document.getElementById('insp_kondisi').onchange = (e) => { asset.kondisi = e.target.value; };
    document.getElementById('insp_ket').oninput = (e) => { asset.keterangan = e.target.value; };
}

// Update text label on cell dynamically without grid redraw
function updateCellLabel(id, text) {
    const asset = assets.find(a => a.id == id);
    if (asset) {
        const cell = document.querySelector(`.editor-cell[data-x="${asset.posisi_x}"][data-y="${asset.posisi_y}"]`);
        if (cell) {
            const labelSpan = cell.querySelector('.editor-cell-label');
            if (labelSpan) labelSpan.innerText = text;
        }
    }
}

// Rotate asset 90 degrees
function rotateAsset(id) {
    const asset = assets.find(a => a.id == id);
    if (asset) {
        asset.rotasi = ((parseInt(asset.rotasi) || 0) + 90) % 360;
        renderGrid();
        if (selectedAssetId == id) {
            showInspector(asset);
        }
        showToast(`Rotasi ${asset.kode_aset} diubah ke ${asset.rotasi}°`, 'info');
    }
}

// Delete component
function deletePlacedAsset(id) {
    if (confirm("Apakah Anda yakin ingin menghapus komponen ini dari grid denah?")) {
        assets = assets.filter(a => a.id != id);
        selectedAssetId = null;
        document.getElementById('inspector_title').innerText = 'Inspector Komponen';
        document.getElementById('inspector_content').innerHTML = `
        <div class="inspector-empty">
          Silakan klik salah satu komponen di dalam grid denah untuk mengubah informasi detail, merotasi, atau menghapusnya.
        </div>`;
        renderGrid();
    }
}

// Trash Can Drop zone event handlers
function allowTrashDrop(e) {
    e.preventDefault();
}
function enterTrash(e) {
    document.getElementById('trash_can').style.background = '#fee2e2';
    document.getElementById('trash_can').style.borderColor = 'var(--red)';
    document.getElementById('trash_can').style.transform = 'scale(1.02)';
}
function leaveTrash() {
    document.getElementById('trash_can').style.background = '#fff5f5';
    document.getElementById('trash_can').style.borderColor = '#fca5a5';
    document.getElementById('trash_can').style.transform = 'scale(1)';
}
function dropOnTrash(e) {
    e.preventDefault();
    leaveTrash();
    if (dragSource && dragSource.type === 'grid') {
        const id = dragSource.id;
        const asset = assets.find(a => a.id == id);
        const label = asset ? asset.kode_aset : 'Komponen';
        
        // Delete directly without confirmation dialog
        assets = assets.filter(a => a.id != id);
        selectedAssetId = null;
        
        document.getElementById('inspector_title').innerText = 'Inspector Komponen';
        document.getElementById('inspector_content').innerHTML = `
        <div class="inspector-empty">
          Silakan klik salah satu komponen di dalam grid denah untuk mengubah informasi detail, merotasi, atau menghapusnya.
        </div>`;
        
        renderGrid();
        showToast(`${label} berhasil dihapus dari denah.`, 'info');
    }
    dragSource = null;
}

// Resize dimensions of grid
function adjustGridSize(dimension, delta) {
    if (dimension === 'cols') {
        let newVal = gridCols + delta;
        if (newVal < 6 || newVal > 24) return;
        
        // Cek jika mempersempit grid memotong asset yang terpasang di luar batas baru
        if (delta < 0) {
            let outside = assets.some(a => parseInt(a.posisi_x) >= newVal);
            if (outside) {
                if (!confirm("Ada komponen terpasang di luar batas ukuran kolom baru. Mempersempit grid akan mengabaikan/menghapus komponen tersebut. Lanjutkan?")) {
                    return;
                }
                assets = assets.filter(a => parseInt(a.posisi_x) < newVal);
            }
        }
        gridCols = newVal;
        document.getElementById('grid_cols').value = newVal;
    } else if (dimension === 'rows') {
        let newVal = gridRows + delta;
        if (newVal < 6 || newVal > 24) return;
        
        // Cek jika memotong
        if (delta < 0) {
            let outside = assets.some(a => parseInt(a.posisi_y) >= newVal);
            if (outside) {
                if (!confirm("Ada komponen terpasang di luar batas ukuran baris baru. Mempersempit grid akan mengabaikan/menghapus komponen tersebut. Lanjutkan?")) {
                    return;
                }
                assets = assets.filter(a => parseInt(a.posisi_y) < newVal);
            }
        }
        gridRows = newVal;
        document.getElementById('grid_rows').value = newVal;
    }
    renderGrid();
}

// Save layout via AJAX Fetch
function saveLayout() {
    const payload = {
        csrf_token: '<?= csrfToken() ?>',
        id: <?= $labId ?>,
        nama: document.getElementById('lab_nama').value,
        kode_lab: document.getElementById('lab_kode').value,
        lantai: parseInt(document.getElementById('lab_lantai').value),
        kapasitas: parseInt(document.getElementById('lab_kapasitas').value),
        pintu_posisi: pintuPosisi,
        deskripsi: document.getElementById('lab_desc').value,
        grid_cols: gridCols,
        grid_rows: gridRows,
        assets: assets
    };
    
    if (!payload.nama || !payload.kode_lab) {
        showToast("Nama dan Kode Lab wajib diisi!", "error");
        return;
    }
    
    // Show saving status
    const btn = document.querySelector('button[onclick="saveLayout()"]');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerText = 'Menyimpan...';
    
    fetch('<?= APP_URL ?>/admin/labs/save_layout.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
    })
    .then(response => response.json().then(data => ({ status: response.status, body: data })))
    .then(res => {
        if (res.status !== 200 || res.body.error) {
            throw new Error(res.body.message || 'Terjadi kesalahan sistem.');
        }
        showToast("Tata letak berhasil disimpan!", "success");
        setTimeout(() => {
            window.location.href = '<?= APP_URL ?>/admin/labs/edit.php?id=<?= $labId ?>&saved=1';
        }, 1000);
    })
    .catch(err => {
        showToast("Gagal menyimpan: " + err.message, "error");
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}

// Initialize on page load
window.onload = () => {
    renderGrid();
    
    // Check if redirected with saved parameter
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('saved') === '1') {
        showToast("Tata letak berhasil disimpan!", "success");
        // Clean query string
        window.history.replaceState({}, document.title, window.location.pathname + '?id=<?= $labId ?>');
    }
    
    // Prevent default window drops
    window.addEventListener("dragover", function(e) {
        e.preventDefault();
    }, false);
    window.addEventListener("drop", function(e) {
        e.preventDefault();
    }, false);
// 3D Preview Modal Controller and Engine
let modalThreeScene = null;
let modalThreeRenderer = null;
let modalThreeControls = null;

function open3DModal() {
    document.getElementById('modal_3d').style.display = 'flex';
    document.getElementById('modal_3d_loading').style.display = 'flex';

    // Bersihkan canvas sebelumnya
    const container = document.getElementById('modal_3d_viewport');
    container.innerHTML = '';

    loadThreeJS(() => {
        // Tunggu browser paint modal dulu, baru init renderer
        // (tanpa ini clientWidth/clientHeight = 0 → renderer 0×0 → layar hitam)
        requestAnimationFrame(() => {
            requestAnimationFrame(() => initModalThreeScene());
        });
    });
}

function close3DModal() {
    document.getElementById('modal_3d').style.display = 'none';
    if (modalThreeRenderer) {
        modalThreeRenderer.dispose();
    }
}

function loadThreeJS(callback) {
    if (window.THREE) { callback(); return; }

    const LOCAL_THREE = '<?= APP_URL ?>/assets/js/three.min.js';
    const LOCAL_ORBIT = '<?= APP_URL ?>/assets/js/OrbitControls.js';
    const CDN_THREE   = 'https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js';
    const CDN_ORBIT   = 'https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js';

    function loadScript(primary, fallback, onDone) {
        const s = document.createElement('script');
        s.src = primary;
        s.onload = onDone;
        s.onerror = () => {
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

function initModalThreeScene() {
    const viewport = document.getElementById('modal_3d_viewport');
    const loading  = document.getElementById('modal_3d_loading');

    let width  = viewport.clientWidth;
    let height = viewport.clientHeight;
    if (!width || !height) {
        const parent = viewport.parentElement;
        width  = parent ? parent.clientWidth  : 860;
        height = parent ? parent.clientHeight : 500;
    }
    if (!width)  width  = 860;
    if (!height) height = 500;

    try {
    // ─── 1. Scene ────────────────────────────────────────────────────────────
    const scene = new THREE.Scene();
    scene.background = new THREE.Color(0x0d1424);
    scene.fog = new THREE.Fog(0x0d1424, 40, 100);

    // ─── 2. Camera ───────────────────────────────────────────────────────────
    const camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 500);
    camera.position.set(0, 22, 28);

    // ─── 3. Renderer (pixel ratio capped for low-end GPU safety) ─────────────
    const renderer = new THREE.WebGLRenderer({ antialias: true, powerPreference: 'default' });
    renderer.setSize(width, height);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.5));
    renderer.shadowMap.enabled = false;
    viewport.appendChild(renderer.domElement);
    modalThreeRenderer = renderer;

    // ─── 4. OrbitControls ────────────────────────────────────────────────────
    const controls = new THREE.OrbitControls(camera, renderer.domElement);
    controls.enableDamping  = true;
    controls.dampingFactor  = 0.08;
    controls.maxPolarAngle  = Math.PI / 2 - 0.04;
    controls.minDistance    = 4;
    controls.maxDistance    = 65;
    modalThreeControls = controls;

    // ─── 5. Lighting ─────────────────────────────────────────────────────────
    const hemi = new THREE.HemisphereLight(0xd0e8ff, 0x3d2b00, 0.55);
    scene.add(hemi);
    const dirMain = new THREE.DirectionalLight(0xfff4e0, 0.65);
    dirMain.position.set(12, 28, 18);
    scene.add(dirMain);
    const dirFill = new THREE.DirectionalLight(0xcce8ff, 0.25);
    dirFill.position.set(-10, 15, -12);
    scene.add(dirFill);
    
    // 6. Room dimensions
    const scale  = 3.5;
    const roomW  = gridCols * scale;
    const roomD  = gridRows * scale;
    const wallH  = 4.2;

    // ─── Material cache ────────────────────────────────────────────────────────
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
    function box(w, h, d) { return new THREE.BoxGeometry(w, h, d); }
    function cyl(rt, rb, h, seg) { return new THREE.CylinderGeometry(rt, rb, h, seg || 8); }

    // ─── 7. Floor ─────────────────────────────────────────────────────────────
    const floor = new THREE.Mesh(new THREE.BoxGeometry(roomW, 0.12, roomD), new THREE.MeshLambertMaterial({ color: 0x1a2436 }));
    floor.position.y = -0.06;
    scene.add(floor);

    const gridHelper = new THREE.GridHelper(Math.max(roomW, roomD), Math.max(gridCols, gridRows) * 2, 0x2d4a6a, 0x1e3354);
    gridHelper.position.y = 0.01;
    scene.add(gridHelper);


    // ─── 8. Walls ─────────────────────────────────────────────────────────────
    const wallMat = mat(0x38bdf8, { transparent: true, opacity: 0.14, depthWrite: false });
    [[roomW, wallH, 0.15, 0, wallH/2, -roomD/2],[0.15, wallH, roomD, -roomW/2, wallH/2, 0],[0.15, wallH, roomD, roomW/2, wallH/2, 0],[roomW, wallH, 0.15, 0, wallH/2, roomD/2]].forEach(([w,h,d,x,y,z]) => {
        const m = new THREE.Mesh(new THREE.BoxGeometry(w,h,d), wallMat);
        m.position.set(x,y,z);
        scene.add(m);
    });

    const skirtMat = mat(0x1e3a5a);
    [[roomW,0.18,0.08,0,0.09,-roomD/2],[roomW,0.18,0.08,0,0.09,roomD/2],[0.08,0.18,roomD,-roomW/2,0.09,0],[0.08,0.18,roomD,roomW/2,0.09,0]].forEach(([w,h,d,x,y,z]) => {
        const sk = new THREE.Mesh(new THREE.BoxGeometry(w, h, d), skirtMat);
        sk.position.set(x, y, z);
        scene.add(sk);
    });

    // ─── 9. Asset models ──────────────────────────────────────────────────────
    function addBox(g, color, px, py, pz, rx, ry, rz) {
        const m = new THREE.Mesh(g, mat(color));
        m.position.set(px, py, pz);
        if (rx) m.rotation.x = rx;
        if (ry) m.rotation.y = ry;
        if (rz) m.rotation.z = rz;
        return m;
    }

    function buildChair(grp, ox, oz, facingBack) {
        const cg = new THREE.Group();
        const seatH = 0.76;
        const legMat = mat(0x1e293b);

        cg.add(addBox(box(1.05, 0.14, 1.05), 0x334155, 0, seatH, 0));
        [[-0.4,-0.4],[0.4,-0.4],[-0.4,0.4],[0.4,0.4]].forEach(([lx,lz]) => {
            const leg = new THREE.Mesh(cyl(0.065, 0.065, seatH - 0.07), legMat);
            leg.position.set(lx, (seatH - 0.07) / 2, lz);
            cg.add(leg);
        });

        const backDir = facingBack ? -0.46 : 0.46;
        cg.add(addBox(box(1.05, 0.72, 0.09), 0x2d3f52, 0, seatH + 0.44, backDir));
        [-0.38, 0.38].forEach(bx => {
            const post = new THREE.Mesh(cyl(0.04, 0.04, 0.48), legMat);
            post.position.set(bx, seatH + 0.08, backDir);
            cg.add(post);
        });
        [-0.53, 0.53].forEach(ax => {
            cg.add(addBox(box(0.09, 0.08, 0.72), 0x1e293b, ax, seatH + 0.3, 0));
        });

        cg.position.set(ox, 0, oz);
        grp.add(cg);
    }

    function buildDesk(grp, w, d, color, legColor, drawers) {
        const dg = new THREE.Group();
        const topH = 1.22;
        dg.add(addBox(box(w, 0.1, d), color, 0, topH, 0));
        const lm = mat(legColor || color);
        const lh = topH - 0.05;
        [[w/2-0.1,d/2-0.1],[-(w/2-0.1),d/2-0.1],[w/2-0.1,-(d/2-0.1)],[-(w/2-0.1),-(d/2-0.1)]].forEach(([lx,lz]) => {
            const leg = new THREE.Mesh(cyl(0.06, 0.06, lh), lm);
            leg.position.set(lx, lh/2, lz);
            dg.add(leg);
        });
        if (drawers) {
            dg.add(addBox(box(0.5, 0.55, d * 0.7), legColor || 0x5a3a1a, w/2 - 0.28, topH - 0.35, 0));
            const hm = new THREE.Mesh(cyl(0.025, 0.025, 0.28), mat(0x94a3b8));
            hm.rotation.x = Math.PI / 2;
            hm.position.set(w/2 - 0.02, topH - 0.35, 0.2);
            dg.add(hm);
        }
        grp.add(dg);
        return dg;
    }

    // 8. Render assets from live JS array state!
    assets.forEach(asset => {
        const ax  = parseInt(asset.posisi_x);
        const ay  = parseInt(asset.posisi_y);
        const rot = parseInt(asset.rotasi || 0);

        const px = (ax - gridCols / 2 + 0.5) * scale;
        const pz = (ay - gridRows / 2 + 0.5) * scale;

        const grp = new THREE.Group();
        grp.position.set(px, 0, pz);
        grp.rotation.y = -THREE.MathUtils.degToRad(rot);

        const type = asset.tipe_kode;

        if (type === 'PC') {
            buildDesk(grp, 3.0, 1.85, 0x7c4d18, 0x5a3510, false);
            grp.add(addBox(box(1.74, 1.08, 0.06), 0x111827, 0, 2.42, -0.42));
            const scrMat = new THREE.MeshLambertMaterial({ color: 0x0c4a6e, emissive: 0x0369a1, emissiveIntensity: 0.25 });
            const scr = new THREE.Mesh(box(1.62, 0.96, 0.04), scrMat);
            scr.position.set(0, 2.42, -0.39);
            grp.add(scr);
            grp.add(addBox(box(0.12, 0.42, 0.12), 0x374151, 0, 2.0, -0.4));
            grp.add(addBox(box(0.52, 0.06, 0.32), 0x374151, 0, 1.78, -0.4));
            grp.add(addBox(box(0.44, 0.88, 0.8), 0x1f2937, -1.1, 1.66, -0.32));
            const ledMat = new THREE.MeshLambertMaterial({ color: 0x22c55e, emissive: 0x16a34a, emissiveIntensity: 1 });
            const led = new THREE.Mesh(cyl(0.022, 0.022, 0.02, 6), ledMat);
            led.rotation.x = Math.PI / 2;
            led.position.set(-1.1, 2.09, -0.71);
            grp.add(led);
            grp.add(addBox(box(1.32, 0.05, 0.46), 0xf0f0f0, 0, 1.27, 0.22));
            grp.add(addBox(box(0.24, 0.05, 0.35), 0xe2e8f0, 0.85, 1.27, 0.18));
            buildChair(grp, 0, 1.08, false);

        } else if (type === 'CHR') {
            buildChair(grp, 0, 0, true);

        } else if (type === 'DESK') {
            buildDesk(grp, 3.1, 2.1, 0xa16207, 0x78440a, false);

        } else if (type === 'TDK') {
            buildDesk(grp, 3.7, 2.3, 0x92400e, 0x6b2d0a, true);
            grp.add(addBox(box(3.7, 0.68, 0.06), 0x78350f, 0, 0.64, -1.12));
            grp.add(addBox(box(1.0, 0.06, 0.22), 0xd4a853, 0, 1.27, -0.95));

        } else if (type === 'TV') {
            grp.add(addBox(box(1.1, 0.12, 0.55), 0x0f172a, -1.4, 0.95, 0));
            const tvNeck = new THREE.Mesh(cyl(0.07, 0.11, 0.82), mat(0x1e293b));
            tvNeck.position.set(-1.4, 1.4, 0);
            grp.add(tvNeck);
            grp.add(addBox(box(0.1, 1.94, 3.18), 0x111827, -1.4, 2.86, 0));
            const tvScrMat = new THREE.MeshLambertMaterial({ color: 0x0a2540, emissive: 0x0c3a6e, emissiveIntensity: 0.2 });
            const tvScr = new THREE.Mesh(box(0.04, 1.82, 3.04), tvScrMat);
            tvScr.position.set(-1.36, 2.86, 0);
            grp.add(tvScr);
            grp.add(addBox(box(0.06, 0.14, 2.2), 0x1e293b, -1.36, 2.0, 0));
            const tvBtn = new THREE.Mesh(cyl(0.035, 0.035, 0.03, 8), mat(0xe2e8f0));
            tvBtn.rotation.z = Math.PI / 2;
            tvBtn.position.set(-1.36, 2.86, 1.44);
            grp.add(tvBtn);

        } else if (type === 'AC') {
            grp.add(addBox(box(2.6, 0.68, 0.55), 0xf1f5f9, 0, 3.35, -1.25));
            for (let i = -2; i <= 2; i++) {
                grp.add(addBox(box(2.52, 0.04, 0.36), 0xd1d5db, 0, 3.14, -1.25 + i * 0.04));
            }
            grp.add(addBox(box(2.52, 0.05, 0.18), 0xd1d5db, 0, 3.24, -1.06));
            grp.add(addBox(box(0.7, 0.1, 0.06), 0xc0c8d8, -0.85, 3.56, -1.01));
            const acLed = new THREE.Mesh(cyl(0.02, 0.02, 0.02, 6), mat(0x22c55e, { emissive: 0x16a34a, emissiveIntensity: 1 }));
            acLed.rotation.z = Math.PI / 2;
            acLed.position.set(1.18, 3.54, -1.01);
            grp.add(acLed);

        } else if (type === 'PRJ') {
            grp.add(addBox(box(1.1, 0.46, 0.88), 0xe2e8f0, 0, 3.63, 0));
            const lens = new THREE.Mesh(cyl(0.16, 0.14, 0.28, 10), mat(0x374151));
            lens.rotation.z = Math.PI / 2;
            lens.position.set(0.55, 3.63, 0.04);
            grp.add(lens);
            const lensGlass = new THREE.Mesh(cyl(0.1, 0.1, 0.04, 10), new THREE.MeshLambertMaterial({ color: 0x1d4ed8, emissive: 0x3b82f6, emissiveIntensity: 0.5 }));
            lensGlass.rotation.z = Math.PI / 2;
            lensGlass.position.set(0.7, 3.63, 0.04);
            grp.add(lensGlass);
            const arm = new THREE.Mesh(cyl(0.04, 0.04, 0.55), mat(0x6b7280));
            arm.position.set(0, 3.38, 0);
            grp.add(arm);
            grp.add(addBox(box(0.28, 0.06, 0.28), 0x4b5563, 0, 3.13, 0));

        } else if (type === 'WBD') {
            grp.add(addBox(box(4.28, 2.32, 0.1), 0x475569, 0, 2.26, -1.32));
            grp.add(addBox(box(4.08, 2.08, 0.06), 0xf9fafb, 0, 2.26, -1.27));
            grp.add(addBox(box(4.08, 0.12, 0.22), 0x374151, 0, 1.26, -1.2));
            grp.add(addBox(box(0.36, 0.14, 0.2), 0xe5e7eb, 0.6, 1.37, -1.14));
            [[-1.98,-0.94],[1.98,-0.94],[-1.98,0.94],[1.98,0.94]].forEach(([bx,by]) => {
                const bolt = new THREE.Mesh(cyl(0.04,0.04,0.05,6), mat(0x94a3b8));
                bolt.rotation.z = Math.PI/2;
                bolt.position.set(bx, by + 2.26, -1.27);
                grp.add(bolt);
            });

        } else if (type === 'FAN') {
            const fanY = 3.8;
            const rod = new THREE.Mesh(cyl(0.04, 0.04, 0.55), mat(0x6b7280));
            rod.position.set(0, fanY + 0.3, 0);
            grp.add(rod);
            const canopy = new THREE.Mesh(cyl(0.26, 0.18, 0.22, 12), mat(0xd1d5db));
            canopy.position.set(0, fanY + 0.02, 0);
            grp.add(canopy);
            const hub = new THREE.Mesh(cyl(0.18, 0.18, 0.28, 12), mat(0x9ca3af));
            hub.position.set(0, fanY - 0.1, 0);
            grp.add(hub);
            [0, 90, 180, 270].forEach(deg => {
                const blade = new THREE.Mesh(box(1.55, 0.04, 0.36), mat(0xc8a96e));
                blade.position.set(0.77, fanY - 0.18, 0);
                blade.rotation.x = 0.12;
                const bg = new THREE.Group();
                bg.add(blade);
                bg.rotation.y = THREE.MathUtils.degToRad(deg);
                grp.add(bg);
            });
            const bowl = new THREE.Mesh(cyl(0.22, 0.12, 0.18, 10), new THREE.MeshLambertMaterial({ color: 0xfff9c4, emissive: 0xfef08a, emissiveIntensity: 0.4, transparent: true, opacity: 0.9 }));
            bowl.position.set(0, fanY - 0.28, 0);
            grp.add(bowl);

        } else if (type === 'CAB') {
            grp.add(addBox(box(2.5, 3.0, 1.5), 0xb45309, 0, 1.5, 0));
            grp.add(addBox(box(0.06, 2.88, 1.46), 0x92400e, 0, 1.5, 0));
            grp.add(addBox(box(2.5, 0.08, 1.5), 0x78350f, 0, 2.96, 0));
            [-0.55, 0.55].forEach(hx => {
                const handle = new THREE.Mesh(cyl(0.035, 0.035, 0.42), mat(0xd4a853));
                handle.rotation.x = Math.PI / 2;
                handle.position.set(hx, 1.5, 0.77);
                grp.add(handle);
            });
            [[-1.0,-0.6],[1.0,-0.6],[-1.0,0.6],[1.0,0.6]].forEach(([fx,fz]) => {
                grp.add(addBox(box(0.18,0.18,0.18), 0x451a03, fx, 0.09, fz));
            });

        } else {
            grp.add(addBox(box(1.4, 1.4, 1.4), 0x475569, 0, 0.7, 0));
        }

        scene.add(grp);
    });

    // ─── 10. Door — detailed ─────────────────────────────────────────────────
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
    dgrp.add(addBox(box(0.12, 2.82, 1.98), 0x7c3400, 0, 1.41, 0));
    const doorSlab = new THREE.Group();
    doorSlab.add(addBox(box(0.09, 2.7, 1.82), 0x9a3e08, 0.91, 1.35, 0));
    doorSlab.rotation.y = Math.PI / 4.5;
    dgrp.add(doorSlab);
    const dHandle = new THREE.Mesh(cyl(0.03, 0.03, 0.38), mat(0xd4a853));
    dHandle.rotation.z = Math.PI / 2;
    dHandle.position.set(0.06, 1.32, -0.72);
    dgrp.add(dHandle);
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

    // Tampilkan scene langsung setelah dibangun (tanpa setTimeout)
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
        loading.innerHTML = '<div style="color:#f87171; font-family:monospace; font-size:12px; padding:20px; text-align:center;">⚠ Gagal memuat 3D:<br>' + err.message + '</div>';
        console.error('[3D Error]', err);
    }
}
</script>

<!-- 3D Preview Modal Overlay -->
<div id="modal_3d" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(15,23,42,0.85); backdrop-filter:blur(8px); z-index:9999; align-items:center; justify-content:center; padding:20px;">
  <div style="width:100%; max-width:900px; height:80vh; background:#1e293b; border-radius:16px; overflow:hidden; border:1px solid #334155; display:flex; flex-direction:column; box-shadow:var(--shadow-2xl);">
    <div style="padding:16px 20px; background:#0f172a; border-bottom:1px solid #334155; display:flex; justify-content:space-between; align-items:center;">
      <div style="display:flex; align-items:center; gap:8px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
        <span style="font-family:'DM Sans', sans-serif; font-weight:700; color:#fff; font-size:14px; letter-spacing:0.02em;">PRATINJAU INTERAKTIF 3D (LENGKAP)</span>
      </div>
      <button type="button" class="btn" onclick="close3DModal()" style="padding:6px 12px; font-size:12px; font-weight:600; background:#f43f5e; color:#fff; border-color:#f43f5e;">
        Tutup
      </button>
    </div>
    <div style="flex:1; position:relative; background:#0f172a;">
      <div id="modal_3d_loading" style="position:absolute; top:0; left:0; width:100%; height:100%; display:flex; flex-direction:column; align-items:center; justify-content:center; color:#fff; z-index:30; gap:12px;">
        <div style="width:28px; height:28px; border:3px solid rgba(255,255,255,0.1); border-top:3px solid #38bdf8; border-radius:50%; animation: spin 0.8s linear infinite;"></div>
        <span style="font-size:12px; font-weight:600; font-family:'DM Sans'; color:#94a3b8; letter-spacing:0.05em;">MEMUAT PREVIEW 3D...</span>
      </div>
      <div id="modal_3d_viewport" style="width:100%; height:100%;"></div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
