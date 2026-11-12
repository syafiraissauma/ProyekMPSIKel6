<?php
/**
 * User Profile & Password Update
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

requireAuth();

$pageTitle = 'Profil Pengguna & Keamanan';
$breadcrumbTitle = 'Profil';
$userSession = currentUser();
$pdo = getDbConnection();

// Ambil data user terkini dari database
$stmt = $pdo->prepare("
    SELECT u.*, r.display_name as role_display 
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    WHERE u.id = ?
");
$stmt->execute([$userSession['id']]);
$user = $stmt->fetch();

$error = '';
$success = '';

// Proses Update Profil / Ganti Password
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $error = 'Validasi token keamanan gagal.';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($fullName) || empty($email)) {
            $error = 'Nama lengkap dan email tidak boleh kosong.';
        } else {
            // Cek duplikasi email pada user lain
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user['id']]);
            if ($stmt->fetch()) {
                $error = 'Email tersebut sudah digunakan oleh pengguna lain.';
            } else {
                // Jika ingin mengganti password
                if (!empty($newPassword)) {
                    if (empty($currentPassword)) {
                        $error = 'Password saat ini harus diisi untuk konfirmasi perubahan password.';
                    } elseif (!password_verify($currentPassword, $user['password'])) {
                        $error = 'Password saat ini tidak cocok.';
                    } elseif (strlen($newPassword) < 6) {
                        $error = 'Password baru minimal harus 6 karakter.';
                    } elseif ($newPassword !== $confirmPassword) {
                        $error = 'Konfirmasi password baru tidak cocok.';
                    } else {
                        // Update profil dan password
                        $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
                        $stmt = $pdo->prepare("
                            UPDATE users 
                            SET full_name = ?, email = ?, phone = ?, password = ?, updated_at = NOW() 
                            WHERE id = ?
                        ");
                        $stmt->execute([$fullName, $email, $phone, $hashed, $user['id']]);
                        $success = 'Profil dan password berhasil diperbarui.';
                    }
                } else {
                    // Hanya update informasi profil
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET full_name = ?, email = ?, phone = ?, updated_at = NOW() 
                        WHERE id = ?
                    ");
                    $stmt->execute([$fullName, $email, $phone, $user['id']]);
                    $success = 'Informasi profil berhasil diperbarui.';
                }

                if (empty($error)) {
                    // Update sesi data user
                    $_SESSION['user']['full_name'] = $fullName;
                    $_SESSION['user']['email'] = $email;
                    
                    // Refresh data
                    $stmt = $pdo->prepare("SELECT u.*, r.display_name as role_display FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
                    $stmt->execute([$user['id']]);
                    $user = $stmt->fetch();
                }
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-header">
    <div class="page-title">
        <h1>Profil & Pengaturan Akun</h1>
        <p>Kelola data informasi akun dan kata sandi Anda</p>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
    <!-- Form Edit Profil -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Informasi Akun</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <?= csrf_field() ?>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" value="<?= e($user['username']) ?>" disabled>
                        <span class="form-hint">Username tidak dapat diubah.</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Peran / Hak Akses</label>
                        <input type="text" class="form-control" value="<?= e($user['role_display']) ?>" disabled>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="full_name">Nama Lengkap <span class="required">*</span></label>
                    <input type="text" id="full_name" name="full_name" class="form-control" value="<?= e($user['full_name']) ?>" required>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="email">Email <span class="required">*</span></label>
                        <input type="email" id="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="phone">No. Handphone / WhatsApp</label>
                        <input type="text" id="phone" name="phone" class="form-control" value="<?= e($user['phone']) ?>" placeholder="08xxxxxxxxxx">
                    </div>
                </div>

                <hr style="border: none; border-top: 1px solid var(--color-border); margin: 20px 0;">
                <h3 style="font-size: 15px; margin-bottom: 12px; color: var(--color-primary);">Ganti Kata Sandi (Opsional)</h3>
                <p style="font-size: 12.5px; margin-bottom: 16px;">Biarkan kolom kata sandi kosong jika Anda tidak ingin mengubahnya.</p>

                <div class="form-group">
                    <label class="form-label" for="current_password">Kata Sandi Saat Ini</label>
                    <input type="password" id="current_password" name="current_password" class="form-control" placeholder="Masukkan kata sandi lama">
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="new_password">Kata Sandi Baru</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Minimal 6 karakter">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirm_password">Konfirmasi Kata Sandi Baru</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Ulangi kata sandi baru">
                    </div>
                </div>

                <div style="margin-top: 24px;">
                    <button type="submit" class="btn btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Ringkasan Info User -->
    <div>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Ringkasan Akun</h2>
            </div>
            <div class="card-body" style="text-align: center;">
                <div style="width: 64px; height: 64px; border-radius: 50%; background: var(--color-primary-light); color: var(--color-primary); font-size: 24px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px; border: 2px solid var(--color-accent-soft);">
                    <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                </div>
                <h3 style="font-size: 16px; font-weight: 700;"><?= e($user['full_name']) ?></h3>
                <p style="margin-top: 2px; font-size: 13px;">@<?= e($user['username']) ?></p>
                <div style="margin: 10px 0;">
                    <?= getRoleBadge($user['role_name']) ?>
                </div>
                <hr style="border: none; border-top: 1px solid var(--color-border); margin: 16px 0;">
                <div style="text-align: left; font-size: 12.5px; display: flex; flex-direction: column; gap: 8px;">
                    <div><strong>Status:</strong> <span class="badge badge-success">Aktif</span></div>
                    <div><strong>Terdaftar Sejak:</strong> <?= formatDate($user['created_at'], 'short') ?></div>
                    <div><strong>Terakhir Diperbarui:</strong> <?= formatDate($user['updated_at']) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

