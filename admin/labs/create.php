<?php
// ============================================================
//  admin/labs/create.php — Create New Laboratory
//  Inventaris Sekolah | PHP Native + MySQL
// ============================================================

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';

// Guard: Hanya admin
requireAdmin();

$pageTitle = 'Tambah Laboratorium Baru';
$activeNav = 'labs';

$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $kodeLab = trim($_POST['kode_lab'] ?? '');
    $lantai = (int)($_POST['lantai'] ?? 1);
    $kapasitas = (int)($_POST['kapasitas'] ?? 30);
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $csrfToken = $_POST['csrf_token'] ?? '';

    // Validasi
    if (!verifyCsrf($csrfToken)) {
        $errorMsg = 'Token CSRF tidak valid. Silakan coba lagi.';
    } elseif (empty($nama) || empty($kodeLab)) {
        $errorMsg = 'Nama Laboratorium dan Kode Lab wajib diisi.';
    } else {
        try {
            // Cek keunikan kode_lab
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM labs WHERE kode_lab = ? AND is_active = 1");
            $stmtCheck->execute([$kodeLab]);
            if ($stmtCheck->fetchColumn() > 0) {
                $errorMsg = "Kode Lab '{$kodeLab}' sudah terdaftar. Gunakan kode unik lain.";
            } else {
                // Insert lab baru dengan default grid size 8x6
                $stmtInsert = $pdo->prepare("
                    INSERT INTO labs (kode_lab, nama, deskripsi, lantai, kapasitas, grid_cols, grid_rows)
                    VALUES (?, ?, ?, ?, ?, 8, 6)
                ");
                $stmtInsert->execute([$kodeLab, $nama, $deskripsi, $lantai, $kapasitas]);
                $newLabId = (int)$pdo->lastInsertId();

                // Redirect langsung ke editor tata letak agar admin bisa mendesain ruangan
                header('Location: ' . APP_URL . '/admin/labs/edit.php?id=' . $newLabId . '&created=1');
                exit;
            }
        } catch (PDOException $e) {
            $errorMsg = 'Kesalahan database saat menyimpan: ' . $e->getMessage();
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
    <b>Tambah Lab Baru</b>
  </div>
</div>

<div class="content" style="max-width: 600px;">

  <div class="page-heading">
    <h1>Buat Laboratorium Baru</h1>
    <p>Setelah mengisi data awal di bawah, Anda akan otomatis diarahkan ke Editor Tata Letak 2D tampak atas untuk mendesain posisi meja dan komputer secara visual.</p>
  </div>

  <div class="editor-panel">
    <div class="editor-panel-head">
      <span class="editor-panel-title">Form Data Laboratorium</span>
    </div>
    <div class="editor-panel-body">
      <?php if (!empty($errorMsg)): ?>
        <div class="login-error-alert" style="margin-bottom:16px;">
          <?= htmlspecialchars($errorMsg) ?>
        </div>
      <?php endif; ?>

      <form action="" method="POST" style="display:flex; flex-direction:column; gap:16px;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">

        <div class="form-group">
          <label class="form-label" for="nama">Nama Laboratorium</label>
          <input type="text" id="nama" name="nama" class="form-control" placeholder="Contoh: Lab Komputer G" value="<?= isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : '' ?>" required autofocus>
        </div>

        <div class="form-group">
          <label class="form-label" for="kode_lab">Kode Lab (Unik)</label>
          <input type="text" id="kode_lab" name="kode_lab" class="form-control" placeholder="Contoh: LAB-G" value="<?= isset($_POST['kode_lab']) ? htmlspecialchars($_POST['kode_lab']) : '' ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="lantai">Lantai</label>
          <input type="number" id="lantai" name="lantai" class="form-control" min="1" max="10" value="<?= isset($_POST['lantai']) ? (int)$_POST['lantai'] : 1 ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="kapasitas">Kapasitas Siswa</label>
          <input type="number" id="kapasitas" name="kapasitas" class="form-control" min="1" max="120" value="<?= isset($_POST['kapasitas']) ? (int)$_POST['kapasitas'] : 30 ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="deskripsi">Deskripsi Singkat</label>
          <textarea id="deskripsi" name="deskripsi" class="form-control" rows="3" placeholder="Tuliskan keterangan detail laboratorium di sini..."><?= isset($_POST['deskripsi']) ? htmlspecialchars($_POST['deskripsi']) : '' ?></textarea>
        </div>

        <div style="border-top:1px solid var(--border); margin:8px 0;"></div>

        <button type="submit" class="btn btn-dark" style="justify-content:center; padding:12px; font-size:14px; font-weight:600;">
          Lanjut ke Desain Layout 2D &rarr;
        </button>
        <a href="<?= APP_URL ?>/admin/index.php" class="btn" style="justify-content:center; padding:12px; font-size:14px;">
          Batalkan
        </a>
      </form>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
