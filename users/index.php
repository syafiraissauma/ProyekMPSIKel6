<?php
/**
 * Manajemen Pengguna Sistem (Khusus Administrator)
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

requireRole('admin');

$pageTitle = 'Manajemen Pengguna';
$breadcrumbTitle = 'Pengguna';
$pdo = getDbConnection();
$currentUserId = currentUserId();

// Proses CRUD Pengguna
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        setFlash('danger', 'Validasi token keamanan gagal.');
        header('Location: ' . url('users/index.php'));
        exit;
    }

    $action = $_POST['action'] ?? '';

    // Tambah Pengguna Baru
    if ($action === 'create') {
        $username = strtolower(trim($_POST['username'] ?? ''));
        $fullName = trim($_POST['full_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $phone = trim($_POST['phone'] ?? '');
        $roleId = (int)($_POST['role_id'] ?? 0);
        $password = $_POST['password'] ?? '';
        $status = $_POST['status'] ?? 'active';

        if (empty($username) || empty($fullName) || empty($email) || empty($password) || $roleId <= 0) {
            setFlash('danger', 'Semua kolom bertanda bintang (*) wajib diisi.');
        } elseif (strlen($password) < 6) {
            setFlash('danger', 'Password minimal harus 6 karakter.');
        } else {
            // Cek keunikan username & email
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $checkStmt->execute([$username, $email]);
            if ($checkStmt->fetch()) {
                setFlash('danger', "Username '{$username}' atau email '{$email}' sudah terdaftar.");
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $insertStmt = $pdo->prepare("
                    INSERT INTO users (username, full_name, email, phone, role_id, password, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $insertStmt->execute([$username, $fullName, $email, $phone, $roleId, $hash, $status]);
                setFlash('success', "Pengguna baru '{$fullName}' (@{$username}) berhasil ditambahkan.");
            }
        }
        header('Location: ' . url('users/index.php'));
        exit;
    }

    // Edit Pengguna
    if ($action === 'update') {
        $userId = (int)($_POST['id'] ?? 0);
        $fullName = trim($_POST['full_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $phone = trim($_POST['phone'] ?? '');
        $roleId = (int)($_POST['role_id'] ?? 0);
        $password = $_POST['password'] ?? '';
        $status = $_POST['status'] ?? 'active';

        if ($userId <= 0 || empty($fullName) || empty($email) || $roleId <= 0) {
            setFlash('danger', 'Data pengguna tidak lengkap.');
        } else {
            // Cek duplikasi email pada user lain
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $checkStmt->execute([$email, $userId]);
            if ($checkStmt->fetch()) {
                setFlash('danger', "Email '{$email}' sudah digunakan oleh akun lain.");
            } else {
                if (!empty($password)) {
                    if (strlen($password) < 6) {
                        setFlash('danger', 'Password baru minimal 6 karakter.');
                        header('Location: ' . url('users/index.php'));
                        exit;
                    }
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $updateStmt = $pdo->prepare("
                        UPDATE users 
                        SET full_name = ?, email = ?, phone = ?, role_id = ?, password = ?, status = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $updateStmt->execute([$fullName, $email, $phone, $roleId, $hash, $status, $userId]);
                } else {
                    $updateStmt = $pdo->prepare("
                        UPDATE users 
                        SET full_name = ?, email = ?, phone = ?, role_id = ?, status = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $updateStmt->execute([$fullName, $email, $phone, $roleId, $status, $userId]);
                }
                setFlash('success', "Data pengguna '{$fullName}' berhasil diperbarui.");
            }
        }
        header('Location: ' . url('users/index.php'));
        exit;
    }

    // Hapus Pengguna
    if ($action === 'delete') {
        $userId = (int)($_POST['id'] ?? 0);

        if ($userId === $currentUserId) {
            setFlash('danger', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif.');
        } else {
            // Cek apakah admin terakhir
            $adminCount = $pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name = 'admin'")->fetchColumn();
            $targetIsAdmin = $pdo->query("SELECT r.name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = {$userId}")->fetchColumn();

            if ($targetIsAdmin === 'admin' && $adminCount <= 1) {
                setFlash('danger', 'Tidak dapat menghapus Administrator terakhir di sistem.');
            } else {
                $delStmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $delStmt->execute([$userId]);
                setFlash('success', 'Akun pengguna berhasil dihapus.');
            }
        }
        header('Location: ' . url('users/index.php'));
        exit;
    }
}

// Ambil data users
$stmt = $pdo->query("
    SELECT u.*, r.name as role_name, r.display_name as role_display 
    FROM users u
    JOIN roles r ON u.role_id = r.id
    ORDER BY u.id ASC
");
$users = $stmt->fetchAll();

// Ambil roles
$roles = $pdo->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-header">
    <div class="page-title">
        <h1>Manajemen Pengguna Sistem</h1>
        <p>Kelola akun, peran otorisasi, dan hak akses pengguna aplikasi inventaris</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" onclick="openCreateUserModal()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
            <span>Tambah Pengguna Baru</span>
        </button>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Daftar Pengguna Terdaftar (<?= count($users) ?> Akun)</h2>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 40px;">No</th>
                    <th>Pengguna</th>
                    <th>Username</th>
                    <th>Email & Telepon</th>
                    <th>Hak Akses / Peran</th>
                    <th>Status</th>
                    <th>Tgl Bergabung</th>
                    <th style="width: 140px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $idx => $u): ?>
                    <tr>
                        <td><?= $idx + 1 ?></td>
                        <td>
                            <div style="font-weight: 600;"><?= e($u['full_name']) ?></div>
                            <?php if ($u['id'] == $currentUserId): ?>
                                <span style="font-size: 10.5px; color: var(--color-primary); font-weight: 600;">(Akun Anda Saat Ini)</span>
                            <?php endif; ?>
                        </td>
                        <td><code>@<?= e($u['username']) ?></code></td>
                        <td>
                            <div><?= e($u['email']) ?></div>
                            <div style="font-size: 11px; color: var(--color-text-subtle);"><?= e($u['phone'] ?: '-') ?></div>
                        </td>
                        <td><?= getRoleBadge($u['role_name']) ?></td>
                        <td>
                            <?php if ($u['status'] === 'active'): ?>
                                <span class="badge badge-success"><span class="dot dot-success"></span>Aktif</span>
                            <?php else: ?>
                                <span class="badge badge-danger"><span class="dot dot-danger"></span>Non-Aktif</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size: 12px; color: var(--color-text-muted);"><?= formatDate($u['created_at'], 'short') ?></td>
                        <td>
                            <div class="table-actions" style="justify-content: center;">
                                <button type="button" class="btn btn-secondary btn-sm" onclick='openEditUserModal(<?= json_encode($u, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' title="Edit Pengguna">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    <span>Edit</span>
                                </button>
                                <?php if ($u['id'] != $currentUserId): ?>
                                    <form method="POST" action="" style="display:inline;" onsubmit="return confirmDelete(event, 'Yakin ingin menghapus pengguna <?= e($u['full_name']) ?>?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" title="Hapus">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah / Edit Pengguna -->
<div id="userModal" class="modal-overlay">
    <div class="modal-dialog">
        <form method="POST" action="" id="userForm">
            <?= csrf_field() ?>
            <input type="hidden" name="action" id="userFormAction" value="create">
            <input type="hidden" name="id" id="targetUserId" value="">

            <div class="modal-header">
                <h3 class="modal-title" id="userModalTitle">Tambah Pengguna Baru</h3>
                <button type="button" class="btn-close-modal" onclick="closeModal('userModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="username">Username <span class="required">*</span></label>
                        <input type="text" id="username" name="username" class="form-control" placeholder="Contoh: petugas2" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="role_id">Peran / Hak Akses <span class="required">*</span></label>
                        <select id="role_id" name="role_id" class="form-select" required>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= $r['id'] ?>"><?= e($r['display_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="full_name">Nama Lengkap <span class="required">*</span></label>
                    <input type="text" id="full_name" name="full_name" class="form-control" placeholder="Nama lengkap petugas..." required>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="email">Email <span class="required">*</span></label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="email@instansi.local" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="phone">No. Telepon / WhatsApp</label>
                        <input type="text" id="phone" name="phone" class="form-control" placeholder="08xxxxxxxxxx">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Kata Sandi <span id="passReq" class="required">*</span></label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Minimal 6 karakter">
                    <span class="form-hint" id="passHint">Wajib diisi untuk pengguna baru.</span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="status">Status Akun <span class="required">*</span></label>
                    <select id="status" name="status" class="form-select" required>
                        <option value="active">Aktif</option>
                        <option value="inactive">Non-Aktif (Diblokir)</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('userModal')">Batal</button>
                <button type="submit" class="btn btn-primary" id="btnSaveUser">Simpan Pengguna</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateUserModal() {
    document.getElementById('userFormAction').value = 'create';
    document.getElementById('targetUserId').value = '';
    document.getElementById('userModalTitle').textContent = 'Tambah Pengguna Baru';
    document.getElementById('username').value = '';
    document.getElementById('username').disabled = false;
    document.getElementById('full_name').value = '';
    document.getElementById('email').value = '';
    document.getElementById('phone').value = '';
    document.getElementById('password').value = '';
    document.getElementById('password').required = true;
    document.getElementById('passReq').style.display = 'inline';
    document.getElementById('passHint').textContent = 'Wajib diisi (minimal 6 karakter).';
    document.getElementById('status').value = 'active';
    openModal('userModal');
}

function openEditUserModal(u) {
    document.getElementById('userFormAction').value = 'update';
    document.getElementById('targetUserId').value = u.id;
    document.getElementById('userModalTitle').textContent = 'Edit Pengguna: ' + u.full_name;
    document.getElementById('username').value = u.username;
    document.getElementById('username').disabled = true; // username tidak boleh diubah
    document.getElementById('full_name').value = u.full_name;
    document.getElementById('email').value = u.email;
    document.getElementById('phone').value = u.phone || '';
    document.getElementById('role_id').value = u.role_id;
    document.getElementById('password').value = '';
    document.getElementById('password').required = false;
    document.getElementById('passReq').style.display = 'none';
    document.getElementById('passHint').textContent = 'Kosongkan jika tidak ingin mengubah kata sandi.';
    document.getElementById('status').value = u.status;
    openModal('userModal');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

