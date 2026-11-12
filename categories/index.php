<?php
/**
 * Manajemen Kategori Barang
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

requireAuth();

$pageTitle = 'Kategori Barang';
$breadcrumbTitle = 'Kategori';
$pdo = getDbConnection();
$error = '';
$success = '';

// Proses CRUD (Hanya Admin dan Petugas)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!canEdit()) {
        http_response_code(403);
        setFlash('danger', 'Peninjau tidak memiliki izin untuk memodifikasi kategori.');
        header('Location: ' . url('categories/index.php'));
        exit;
    }

    if (!verify_csrf_token()) {
        setFlash('danger', 'Validasi token keamanan gagal.');
        header('Location: ' . url('categories/index.php'));
        exit;
    }

    $action = $_POST['action'] ?? '';

    // Tambah Kategori
    if ($action === 'create') {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($code) || empty($name)) {
            setFlash('danger', 'Kode dan nama kategori wajib diisi.');
        } else {
            // Cek keunikan kode
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE code = ?");
            $stmt->execute([$code]);
            if ($stmt->fetch()) {
                setFlash('danger', "Kode kategori '{$code}' sudah ada dalam sistem.");
            } else {
                $stmt = $pdo->prepare("INSERT INTO categories (code, name, description, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$code, $name, $description]);
                setFlash('success', "Kategori '{$name}' berhasil ditambahkan.");
            }
        }
        header('Location: ' . url('categories/index.php'));
        exit;
    }

    // Edit Kategori
    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($id <= 0 || empty($code) || empty($name)) {
            setFlash('danger', 'Data kategori tidak lengkap.');
        } else {
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE code = ? AND id != ?");
            $stmt->execute([$code, $id]);
            if ($stmt->fetch()) {
                setFlash('danger', "Kode kategori '{$code}' sudah digunakan oleh kategori lain.");
            } else {
                $stmt = $pdo->prepare("UPDATE categories SET code = ?, name = ?, description = ? WHERE id = ?");
                $stmt->execute([$code, $name, $description, $id]);
                setFlash('success', "Kategori '{$name}' berhasil diperbarui.");
            }
        }
        header('Location: ' . url('categories/index.php'));
        exit;
    }

    // Hapus Kategori
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        // Cek apakah ada barang yang menggunakan kategori ini
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM items WHERE category_id = ?");
        $stmt->execute([$id]);
        $itemCount = $stmt->fetchColumn();

        if ($itemCount > 0) {
            setFlash('danger', "Kategori tidak dapat dihapus karena masih digunakan oleh {$itemCount} barang inventaris.");
        } else {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            setFlash('success', 'Kategori berhasil dihapus.');
        }
        header('Location: ' . url('categories/index.php'));
        exit;
    }
}

// Ambil daftar kategori beserta jumlah item
$stmt = $pdo->query("
    SELECT c.*, COUNT(i.id) as total_items, COALESCE(SUM(i.quantity), 0) as total_units
    FROM categories c
    LEFT JOIN items i ON c.id = i.category_id
    GROUP BY c.id
    ORDER BY c.id ASC
");
$categories = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-header">
    <div class="page-title">
        <h1>Manajemen Kategori Barang</h1>
        <p>Klasifikasi dan pengelompokan jenis aset inventaris</p>
    </div>
    <div class="page-actions">
        <?php if (canEdit()): ?>
            <button type="button" class="btn btn-primary" onclick="openCreateModal()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>Tambah Kategori</span>
            </button>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Daftar Kategori (<?= count($categories) ?> Data)</h2>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Kode</th>
                    <th>Nama Kategori</th>
                    <th>Deskripsi</th>
                    <th>Jumlah Barang</th>
                    <th>Total Unit</th>
                    <?php if (canEdit()): ?>
                        <th style="width: 140px;">Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr><td colspan="<?= canEdit() ? 7 : 6 ?>" style="text-align: center; color: var(--color-text-subtle); padding: 24px;">Belum ada data kategori.</td></tr>
                <?php else: ?>
                    <?php foreach ($categories as $index => $cat): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><span class="table-code"><?= e($cat['code']) ?></span></td>
                            <td><strong><?= e($cat['name']) ?></strong></td>
                            <td style="color: var(--color-text-muted); max-width: 300px;"><?= e($cat['description'] ?: '-') ?></td>
                            <td><span class="badge badge-emerald"><?= formatNumber($cat['total_items']) ?> Item</span></td>
                            <td><strong><?= formatNumber($cat['total_units']) ?></strong> Unit</td>
                            <?php if (canEdit()): ?>
                                <td>
                                    <div class="table-actions">
                                        <button type="button" class="btn btn-secondary btn-sm" onclick='openEditModal(<?= json_encode($cat, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' title="Edit">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                            <span>Edit</span>
                                        </button>
                                        <form method="POST" action="" style="display:inline;" onsubmit="return confirmDelete(event, 'Yakin ingin menghapus kategori <?= e($cat['name']) ?>?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Hapus" <?= ($cat['total_items'] > 0) ? 'disabled style="opacity:0.4; cursor:not-allowed;"' : '' ?>>
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (canEdit()): ?>
<!-- Modal Tambah / Edit Kategori -->
<div id="categoryModal" class="modal-overlay">
    <div class="modal-dialog">
        <form method="POST" action="" id="categoryForm">
            <?= csrf_field() ?>
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id" id="categoryId" value="">

            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Tambah Kategori Baru</h3>
                <button type="button" class="btn-close-modal" onclick="closeModal('categoryModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="code">Kode Kategori <span class="required">*</span></label>
                    <input type="text" id="code" name="code" class="form-control" placeholder="Contoh: CAT-ELK" required maxlength="20">
                    <span class="form-hint">Gunakan kode unik singkat huruf besar.</span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="name">Nama Kategori <span class="required">*</span></label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="Contoh: Elektronik & Audio" required maxlength="100">
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">Deskripsi / Keterangan</label>
                    <textarea id="description" name="description" class="form-control" placeholder="Penjelasan isi kelompok kategori ini..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('categoryModal')">Batal</button>
                <button type="submit" class="btn btn-primary" id="btnSaveCategory">Simpan Kategori</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('formAction').value = 'create';
    document.getElementById('categoryId').value = '';
    document.getElementById('modalTitle').textContent = 'Tambah Kategori Baru';
    document.getElementById('code').value = '';
    document.getElementById('name').value = '';
    document.getElementById('description').value = '';
    openModal('categoryModal');
}

function openEditModal(cat) {
    document.getElementById('formAction').value = 'update';
    document.getElementById('categoryId').value = cat.id;
    document.getElementById('modalTitle').textContent = 'Edit Kategori: ' + cat.name;
    document.getElementById('code').value = cat.code;
    document.getElementById('name').value = cat.name;
    document.getElementById('description').value = cat.description || '';
    openModal('categoryModal');
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

