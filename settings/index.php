<?php
/**
 * Pengaturan Sistem Inventaris
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

requireRole('admin');

$pageTitle = 'Pengaturan Sistem';
$breadcrumbTitle = 'Pengaturan';
$pdo = getDbConnection();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $error = 'Validasi token keamanan gagal.';
    } else {
        $appName = trim($_POST['app_name'] ?? 'SIM-INVENTARIS');
        $appTitle = trim($_POST['app_title'] ?? '');
        $institutionName = trim($_POST['institution_name'] ?? '');
        $institutionAddress = trim($_POST['institution_address'] ?? '');
        $institutionPhone = trim($_POST['institution_phone'] ?? '');
        $institutionEmail = trim($_POST['institution_email'] ?? '');
        $codePrefix = strtoupper(trim($_POST['item_code_prefix'] ?? 'BRG'));

        $settings = [
            'app_name' => $appName,
            'app_title' => $appTitle,
            'institution_name' => $institutionName,
            'institution_address' => $institutionAddress,
            'institution_phone' => $institutionPhone,
            'institution_email' => $institutionEmail,
            'item_code_prefix' => $codePrefix
        ];

        try {
            $stmt = $pdo->prepare("
                INSERT INTO settings (setting_key, setting_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            foreach ($settings as $key => $val) {
                $stmt->execute([$key, $val]);
            }
            $success = 'Pengaturan sistem berhasil disimpan.';
        } catch (Exception $e) {
            $error = 'Gagal menyimpan pengaturan: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-header">
    <div class="page-title">
        <h1>Pengaturan Sistem</h1>
        <p>Konfigurasi identitas instansi, kop laporan, dan format kode penomoran aset</p>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
    <!-- Form Pengaturan -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Identitas Aplikasi & Instansi</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <?= csrf_field() ?>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="app_name">Nama Singkat Aplikasi <span class="required">*</span></label>
                        <input type="text" id="app_name" name="app_name" class="form-control" value="<?= e(getSetting('app_name', 'SIM-INVENTARIS')) ?>" required>
                        <span class="form-hint">Ditampilkan di sudut kiri atas sidebar.</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="item_code_prefix">Awalan Kode Barang (Prefix) <span class="required">*</span></label>
                        <input type="text" id="item_code_prefix" name="item_code_prefix" class="form-control" value="<?= e(getSetting('item_code_prefix', 'BRG')) ?>" required maxlength="10">
                        <span class="form-hint">Contoh: BRG &rarr; BRG-2024-0001</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="app_title">Judul Lengkap Sistem</label>
                    <input type="text" id="app_title" name="app_title" class="form-control" value="<?= e(getSetting('app_title', 'Sistem Informasi Manajemen Inventaris Barang QR Code')) ?>">
                </div>

                <hr style="border: none; border-top: 1px solid var(--color-border); margin: 20px 0;">
                <h3 style="font-size: 15px; margin-bottom: 14px; color: var(--color-primary);">Data Instansi / Lembaga (Untuk Kop Laporan & Label QR)</h3>

                <div class="form-group">
                    <label class="form-label" for="institution_name">Nama Instansi / Lembaga / Perusahaan <span class="required">*</span></label>
                    <input type="text" id="institution_name" name="institution_name" class="form-control" value="<?= e(getSetting('institution_name', 'Institut Teknologi & Bisnis Kelompok 6')) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="institution_address">Alamat Kantor</label>
                    <input type="text" id="institution_address" name="institution_address" class="form-control" value="<?= e(getSetting('institution_address', 'Jl. Kampus Merdeka No. 6, Gedung MPSI')) ?>">
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="institution_phone">No. Telepon / Kontak</label>
                        <input type="text" id="institution_phone" name="institution_phone" class="form-control" value="<?= e(getSetting('institution_phone', '0812-3456-7890')) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="institution_email">Email Resmi</label>
                        <input type="email" id="institution_email" name="institution_email" class="form-control" value="<?= e(getSetting('institution_email', 'inventaris@kel6mpsi.ac.id')) ?>">
                    </div>
                </div>

                <div style="margin-top: 24px;">
                    <button type="submit" class="btn btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                        <span>Simpan Pengaturan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Informasi Lingkungan & Server -->
    <div>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Informasi Sistem</h2>
            </div>
            <div class="card-body" style="font-size: 12.5px; display: flex; flex-direction: column; gap: 10px;">
                <div><strong>PHP Version:</strong> <?= PHP_VERSION ?></div>
                <div><strong>Server OS:</strong> <?= PHP_OS ?> (Windows XAMPP)</div>
                <div><strong>Database:</strong> <?= DB_NAME ?> (MySQL/MariaDB)</div>
                <div><strong>Database Host:</strong> <?= DB_HOST ?>:<?= DB_PORT ?></div>
                <div><strong>Zona Waktu:</strong> <?= date_default_timezone_get() ?></div>
                <div><strong>Waktu Server:</strong> <?= date('d M Y, H:i:s') ?></div>
                <div><strong>Sistem QR Code:</strong> High-Res Vector SVG / Canvas Native</div>
            </div>
        </div>

        <div class="card" style="border-left: 4px solid var(--color-warning);">
            <div class="card-header">
                <h2 class="card-title" style="color: var(--color-warning);">Installer Database</h2>
            </div>
            <div class="card-body">
                <p style="font-size: 12.5px; margin-bottom: 12px;">Jika Anda perlu mereset atau menginisialisasi ulang database dan data contoh bawaan:</p>
                <a href="<?= url('setup_database.php') ?>" class="btn btn-secondary btn-sm" onclick="return confirm('Peringatan: Menjalankan installer akan mereset database ke kondisi awal. Lanjutkan?');">
                    Buka Installer Database Otomatis &rarr;
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

