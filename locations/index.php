<?php
/**
 * Manajemen Lokasi Penyimpanan Barang
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

requireAuth();

$pageTitle = 'Lokasi Penyimpanan';
$breadcrumbTitle = 'Lokasi';
$pdo = getDbConnection();

// Proses CRUD (Admin & Petugas)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!canEdit()) {
        http_response_code(403);
        setFlash('danger', 'Peninjau tidak memiliki izin untuk memodifikasi lokasi.');
        header('Location: ' . url('locations/index.php'));
        exit;
    }

    if (!verify_csrf_token()) {
        setFlash('danger', 'Validasi token keamanan gagal.');
        header('Location: ' . url('locations/index.php'));
        exit;
    }

    $action = $_POST['action'] ?? '';

    // Tambah Lokasi
    if ($action === 'create') {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $name = trim($_POST['name'] ?? '');
        $building = trim($_POST['building'] ?? '');
        $floor = trim($_POST['floor'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($code) || empty($name)) {
            setFlash('danger', 'Kode dan nama lokasi wajib diisi.');
        } else {
            $stmt = $pdo->prepare("SELECT id FROM locations WHERE code = ?");
            $stmt->execute([$code]);
            if ($stmt->fetch()) {
                setFlash('danger', "Kode lokasi '{$code}' sudah ada dalam sistem.");
            } else {
                $stmt = $pdo->prepare("INSERT INTO locations (code, name, building, floor, description, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$code, $name, $building, $floor, $description]);
                setFlash('success', "Lokasi '{$name}' berhasil ditambahkan.");
            }
        }
        header('Location: ' . url('locations/index.php'));
        exit;
    }

    // Edit Lokasi
    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $name = trim($_POST['name'] ?? '');
        $building = trim($_POST['building'] ?? '');
        $floor = trim($_POST['floor'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($id <= 0 || empty($code) || empty($name)) {
            setFlash('danger', 'Data lokasi tidak lengkap.');
        } else {
            $stmt = $pdo->prepare("SELECT id FROM locations WHERE code = ? AND id != ?");
            $stmt->execute([$code, $id]);
            if ($stmt->fetch()) {
                setFlash('danger', "Kode lokasi '{$code}' sudah digunakan oleh lokasi lain.");
            } else {
                $stmt = $pdo->prepare("UPDATE locations SET code = ?, name = ?, building = ?, floor = ?, description = ? WHERE id = ?");
                $stmt->execute([$code, $name, $building, $floor, $description, $id]);
                setFlash('success', "Lokasi '{$name}' berhasil diperbarui.");
            }
        }
        header('Location: ' . url('locations/index.php'));
        exit;
    }

    // Hapus Lokasi
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM items WHERE location_id = ?");
        $stmt->execute([$id]);
        $itemCount = $stmt->fetchColumn();

        if ($itemCount > 0) {
            setFlash('danger', "Lokasi tidak dapat dihapus karena masih digunakan sebagai tempat penyimpanan {$itemCount} barang.");
        } else {
            $stmt = $pdo->prepare("DELETE FROM locations WHERE id = ?");
            $stmt->execute([$id]);
            setFlash('success', 'Lokasi berhasil dihapus.');
        }
        header('Location: ' . url('locations/index.php'));
        exit;
    }
}

// Ambil data lokasi beserta relasi barang
$stmt = $pdo->query("
    SELECT l.*, COUNT(i.id) as total_items, COALESCE(SUM(i.quantity), 0) as total_units
    FROM locations l
    LEFT JOIN items i ON l.id = i.location_id
    GROUP BY l.id
    ORDER BY l.id ASC
");
$locations = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-header">
    <div class="page-title">
        <h1>Manajemen Lokasi Penyimpanan</h1>
        <p>Pengelolaan ruangan, gedung, dan pos penempatan aset inventaris</p>
    </div>
    <div class="page-actions">
        <?php if (canEdit()): ?>
            <button type="button" class="btn btn-primary" onclick="openCreateModal()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>Tambah Lokasi</span>
            </button>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Daftar Lokasi (<?= count($locations) ?> Titik)</h2>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Kode</th>
                    <th>Nama Lokasi / Ruangan</th>
                    <th>Gedung & Lantai</th>
                    <th>Deskripsi</th>
                    <th>Jumlah Barang</th>
                    <th>Total Unit</th>
                    <?php if (canEdit()): ?>
                        <th style="width: 140px;">Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($locations)): ?>
                    <tr><td colspan="<?= canEdit() ? 8 : 7 ?>" style="text-align: center; color: var(--color-text-subtle); padding: 24px;">Belum ada data lokasi.</td></tr>
                <?php else: ?>
                    <?php foreach ($locations as $index => $loc): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><span class="table-code"><?= e($loc['code']) ?></span></td>
                            <td><strong><?= e($loc['name']) ?></strong></td>
                            <td>
                                <div><?= e($loc['building'] ?: '-') ?></div>
                                <div style="font-size: 11.5px; color: var(--color-text-subtle);"><?= e($loc['floor'] ?: '-') ?></div>
                            </td>
                            <td style="color: var(--color-text-muted); max-width: 250px;"><?= e($loc['description'] ?: '-') ?></td>
                            <td><span class="badge badge-emerald"><?= formatNumber($loc['total_items']) ?> Item</span></td>
                            <td><strong><?= formatNumber($loc['total_units']) ?></strong> Unit</td>
                            <?php if (canEdit()): ?>
                                <td>
                                    <div class="table-actions">
                                        <button type="button" class="btn btn-secondary btn-sm" onclick='openEditModal(<?= json_encode($loc, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' title="Edit">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                            <span>Edit</span>
                                        </button>
                                        <form method="POST" action="" style="display:inline;" onsubmit="return confirmDelete(event, 'Yakin ingin menghapus lokasi <?= e($loc['name']) ?>?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $loc['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Hapus" <?= ($loc['total_items'] > 0) ? 'disabled style="opacity:0.4; cursor:not-allowed;"' : '' ?>>
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
<!-- Modal Tambah / Edit Lokasi -->
<div id="locationModal" class="modal-overlay">
    <div class="modal-dialog">
        <form method="POST" action="" id="locationForm">
            <?= csrf_field() ?>
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id" id="locationId" value="">

            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Tambah Lokasi Baru</h3>
                <button type="button" class="btn-close-modal" onclick="closeModal('locationModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="code">Kode Lokasi <span class="required">*</span></label>
                    <input type="text" id="code" name="code" class="form-control" placeholder="Contoh: LOK-SRV" required maxlength="20">
                </div>

                <div class="form-group">
                    <label class="form-label" for="name">Nama Lokasi / Ruangan <span class="required">*</span></label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="Contoh: Ruang Server & IT" required maxlength="100">
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="building">Nama Gedung</label>
                        <input type="text" id="building" name="building" class="form-control" placeholder="Contoh: Gedung A">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="floor">Lantai</label>
                        <input type="text" id="floor" name="floor" class="form-control" placeholder="Contoh: Lantai 2">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">Deskripsi / Catatan Tambahan</label>
                    <textarea id="description" name="description" class="form-control" placeholder="Akses ruangan, penanggung jawab, dll..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('locationModal')">Batal</button>
                <button type="submit" class="btn btn-primary" id="btnSaveLocation">Simpan Lokasi</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('formAction').value = 'create';
    document.getElementById('locationId').value = '';
    document.getElementById('modalTitle').textContent = 'Tambah Lokasi Baru';
    document.getElementById('code').value = '';
    document.getElementById('name').value = '';
    document.getElementById('building').value = '';
    document.getElementById('floor').value = '';
    document.getElementById('description').value = '';
    openModal('locationModal');
}

function openEditModal(loc) {
    document.getElementById('formAction').value = 'update';
    document.getElementById('locationId').value = loc.id;
    document.getElementById('modalTitle').textContent = 'Edit Lokasi: ' + loc.name;
    document.getElementById('code').value = loc.code;
    document.getElementById('name').value = loc.name;
    document.getElementById('building').value = loc.building || '';
    document.getElementById('floor').value = loc.floor || '';
    document.getElementById('description').value = loc.description || '';
    openModal('locationModal');
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

