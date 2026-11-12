<?php
/**
 * Edit Data Barang
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

requireRole(['admin', 'petugas']);

$pdo = getDbConnection();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    setFlash('danger', 'Data barang tidak ditemukan.');
    header('Location: ' . url('items/index.php'));
    exit;
}

$pageTitle = 'Edit Barang: ' . $item['name'];
$breadcrumbTitle = 'Edit Barang';
$error = '';

$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
$locations = $pdo->query("SELECT id, name FROM locations ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $error = 'Validasi token keamanan gagal.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $locationId = (int)($_POST['location_id'] ?? 0);
        $unit = trim($_POST['unit'] ?? 'Unit');
        $condition = $_POST['item_condition'] ?? 'Baik';
        $status = $_POST['status'] ?? 'Tersedia';
        $entryDate = $_POST['entry_date'] ?? $item['entry_date'];
        $description = trim($_POST['description'] ?? '');

        if (empty($name) || $categoryId <= 0 || $locationId <= 0) {
            $error = 'Nama barang, kategori, dan lokasi penyimpanan wajib diisi.';
        } else {
            try {
                $oldData = [
                    'name' => $item['name'],
                    'category_id' => $item['category_id'],
                    'location_id' => $item['location_id'],
                    'unit' => $item['unit'],
                    'item_condition' => $item['item_condition'],
                    'status' => $item['status'],
                    'entry_date' => $item['entry_date'],
                    'description' => $item['description']
                ];

                $newData = [
                    'name' => $name,
                    'category_id' => $categoryId,
                    'location_id' => $locationId,
                    'unit' => $unit,
                    'item_condition' => $condition,
                    'status' => $status,
                    'entry_date' => $entryDate,
                    'description' => $description
                ];

                // Update database
                $updateStmt = $pdo->prepare("
                    UPDATE items 
                    SET name = ?, category_id = ?, location_id = ?, unit = ?, item_condition = ?, status = ?, entry_date = ?, description = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $updateStmt->execute([
                    $name,
                    $categoryId,
                    $locationId,
                    $unit,
                    $condition,
                    $status,
                    $entryDate,
                    $description,
                    $id
                ]);

                // Identifikasi jenis perubahan untuk audit log yang jelas
                $changes = [];
                if ($oldData['item_condition'] !== $condition) {
                    $changes[] = "kondisi diubah dari '{$oldData['item_condition']}' menjadi '{$condition}'";
                    logActivity('UBAH_KONDISI', $id, $item['item_code'], $name, "Perubahan kondisi barang: {$oldData['item_condition']} -> {$condition}", ['condition' => $oldData['item_condition']], ['condition' => $condition]);
                }
                if ($oldData['location_id'] != $locationId) {
                    $changes[] = "lokasi penyimpanan dipindahkan";
                    logActivity('UBAH_LOKASI', $id, $item['item_code'], $name, "Perubahan lokasi penyimpanan barang.", ['location_id' => $oldData['location_id']], ['location_id' => $locationId]);
                }
                if ($oldData['status'] !== $status) {
                    $changes[] = "status ketersediaan diubah dari '{$oldData['status']}' menjadi '{$status}'";
                    logActivity('UBAH_STATUS', $id, $item['item_code'], $name, "Perubahan status ketersediaan: {$oldData['status']} -> {$status}", ['status' => $oldData['status']], ['status' => $status]);
                }

                $summaryLog = !empty($changes) ? implode(', ', $changes) : 'Memperbarui data detail barang.';
                logActivity('UBAH_BARANG', $id, $item['item_code'], $name, $summaryLog, $oldData, $newData);

                setFlash('success', "Data barang '{$name}' ({$item['item_code']}) berhasil diperbarui.");
                header('Location: ' . url('items/detail.php?code=' . urlencode($item['item_code'])));
                exit;
            } catch (Exception $e) {
                $error = 'Gagal menyimpan perubahan: ' . $e->getMessage();
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
        <h1>Edit Data Barang</h1>
        <p>Perbarui informasi atribut, lokasi, dan kondisi barang <strong><?= e($item['item_code']) ?></strong></p>
    </div>
    <div class="page-actions">
        <a href="<?= url('items/detail.php?code=' . urlencode($item['item_code'])) ?>" class="btn btn-secondary">
            &larr; Batal & Kembali ke Detail
        </a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Perbarui Data Barang</h2>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <?= csrf_field() ?>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Kode Barang (Terkunci)</label>
                    <input type="text" class="form-control" value="<?= e($item['item_code']) ?>" disabled>
                    <span class="form-hint">Kode barang terikat dengan QR Code unik dan tidak dapat diubah.</span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="name">Nama Barang <span class="required">*</span></label>
                    <input type="text" id="name" name="name" class="form-control" value="<?= e($item['name']) ?>" required>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="category_id">Kategori Barang <span class="required">*</span></label>
                    <select id="category_id" name="category_id" class="form-select" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $item['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="location_id">Lokasi Penyimpanan <span class="required">*</span></label>
                    <select id="location_id" name="location_id" class="form-select" required>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?= $loc['id'] ?>" <?= $item['location_id'] == $loc['id'] ? 'selected' : '' ?>><?= e($loc['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Jumlah Stok Saat Ini</label>
                    <input type="text" class="form-control" value="<?= formatNumber($item['quantity']) ?> <?= e($item['unit']) ?>" disabled>
                    <span class="form-hint">Untuk mengubah jumlah stok, gunakan menu <strong>Barang Masuk / Keluar</strong>.</span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="unit">Satuan Barang <span class="required">*</span></label>
                    <input type="text" id="unit" name="unit" class="form-control" value="<?= e($item['unit']) ?>" required>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="item_condition">Kondisi Barang <span class="required">*</span></label>
                    <select id="item_condition" name="item_condition" class="form-select" required>
                        <option value="Baik" <?= $item['item_condition'] === 'Baik' ? 'selected' : '' ?>>Baik (Siap Pakai)</option>
                        <option value="Rusak Ringan" <?= $item['item_condition'] === 'Rusak Ringan' ? 'selected' : '' ?>>Rusak Ringan</option>
                        <option value="Rusak Berat" <?= $item['item_condition'] === 'Rusak Berat' ? 'selected' : '' ?>>Rusak Berat</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="status">Status Ketersediaan <span class="required">*</span></label>
                    <select id="status" name="status" class="form-select" required>
                        <option value="Tersedia" <?= $item['status'] === 'Tersedia' ? 'selected' : '' ?>>Tersedia</option>
                        <option value="Dipinjam" <?= $item['status'] === 'Dipinjam' ? 'selected' : '' ?>>Dipinjam</option>
                        <option value="Habis" <?= $item['status'] === 'Habis' ? 'selected' : '' ?>>Habis</option>
                        <option value="Dalam Perbaikan" <?= $item['status'] === 'Dalam Perbaikan' ? 'selected' : '' ?>>Dalam Perbaikan</option>
                        <option value="Afkir" <?= $item['status'] === 'Afkir' ? 'selected' : '' ?>>Afkir</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="entry_date">Tanggal Pendataan <span class="required">*</span></label>
                <input type="date" id="entry_date" name="entry_date" class="form-control" value="<?= e($item['entry_date']) ?>" required style="max-width: 280px;">
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Keterangan / Spesifikasi Tambahan</label>
                <textarea id="description" name="description" class="form-control"><?= e($item['description']) ?></textarea>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 28px;">
                <button type="submit" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                    <span>Simpan Perubahan</span>
                </button>
                <a href="<?= url('items/detail.php?code=' . urlencode($item['item_code'])) ?>" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

