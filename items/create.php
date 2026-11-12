<?php
/**
 * Tambah Data Barang Baru
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

requireRole(['admin', 'petugas']);

$pageTitle = 'Tambah Barang Baru';
$breadcrumbTitle = 'Tambah Barang';
$pdo = getDbConnection();

$error = '';
$itemCode = generateItemCode();
$name = '';
$categoryId = '';
$locationId = '';
$quantity = 1;
$unit = 'Unit';
$condition = 'Baik';
$status = 'Tersedia';
$entryDate = date('Y-m-d');
$description = '';

// Ambil Kategori & Lokasi
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
$locations = $pdo->query("SELECT id, name FROM locations ORDER BY name ASC")->fetchAll();

if (empty($categories)) {
    setFlash('warning', 'Harap tambahkan minimal satu Kategori terlebih dahulu sebelum mendaftarkan barang.');
    header('Location: ' . url('categories/index.php'));
    exit;
}
if (empty($locations)) {
    setFlash('warning', 'Harap tambahkan minimal satu Lokasi terlebih dahulu sebelum mendaftarkan barang.');
    header('Location: ' . url('locations/index.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $error = 'Validasi keamanan (CSRF) gagal. Silakan ulangi pengisian formulir.';
    } else {
        $itemCode = strtoupper(trim($_POST['item_code'] ?? ''));
        $name = trim($_POST['name'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $locationId = (int)($_POST['location_id'] ?? 0);
        $quantity = max(0, (int)($_POST['quantity'] ?? 0));
        $unit = trim($_POST['unit'] ?? 'Unit');
        $condition = $_POST['item_condition'] ?? 'Baik';
        $status = $_POST['status'] ?? 'Tersedia';
        $entryDate = $_POST['entry_date'] ?? date('Y-m-d');
        $description = trim($_POST['description'] ?? '');

        // Validasi
        if (empty($itemCode) || empty($name) || $categoryId <= 0 || $locationId <= 0) {
            $error = 'Kode barang, nama barang, kategori, dan lokasi penyimpanan wajib diisi.';
        } else {
            // Cek keunikan kode barang
            $stmt = $pdo->prepare("SELECT id FROM items WHERE item_code = ?");
            $stmt->execute([$itemCode]);
            if ($stmt->fetch()) {
                $error = "Kode barang '{$itemCode}' sudah terdaftar. Silakan gunakan kode lain atau generate otomatis.";
            } else {
                try {
                    $pdo->beginTransaction();

                    // Simpan data barang
                    $stmt = $pdo->prepare("
                        INSERT INTO items 
                        (item_code, name, category_id, location_id, quantity, unit, item_condition, status, entry_date, description, qr_code_text, created_by, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([
                        $itemCode,
                        $name,
                        $categoryId,
                        $locationId,
                        $quantity,
                        $unit,
                        $condition,
                        $status,
                        $entryDate,
                        $description,
                        $itemCode,
                        currentUserId()
                    ]);
                    $newItemId = (int)$pdo->lastInsertId();

                    // Jika ada stok awal > 0, catat sebagai transaksi masuk awal
                    if ($quantity > 0) {
                        $trxCode = generateTransactionCode('Masuk');
                        $stmtTrx = $pdo->prepare("
                            INSERT INTO item_transactions 
                            (transaction_code, item_id, transaction_type, quantity, stock_before, stock_after, transaction_date, user_id, recipient_or_supplier, notes, created_at)
                            VALUES (?, ?, 'Masuk', ?, 0, ?, NOW(), ?, 'Stok Awal Pendataan', 'Pencatatan saldo awal saat barang didaftarkan', NOW())
                        ");
                        $stmtTrx->execute([
                            $trxCode,
                            $newItemId,
                            $quantity,
                            $quantity,
                            currentUserId()
                        ]);
                    }

                    // Catat ke riwayat aktivitas
                    logActivity(
                        'TAMBAH_BARANG',
                        $newItemId,
                        $itemCode,
                        $name,
                        "Mendaftarkan barang baru '{$name}' dengan jumlah {$quantity} {$unit} dan kondisi {$condition}.",
                        null,
                        [
                            'item_code' => $itemCode,
                            'name' => $name,
                            'category_id' => $categoryId,
                            'location_id' => $locationId,
                            'quantity' => $quantity,
                            'unit' => $unit,
                            'item_condition' => $condition,
                            'status' => $status
                        ]
                    );

                    $pdo->commit();

                    setFlash('success', "Barang '{$name}' ({$itemCode}) berhasil didaftarkan bersama kode QR unik.");
                    header('Location: ' . url('items/detail.php?code=' . urlencode($itemCode)));
                    exit;
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = 'Terjadi kesalahan sistem saat menyimpan data: ' . $e->getMessage();
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
        <h1>Tambah Barang Baru</h1>
        <p>Formulir registrasi aset dan pembuatan kode unik otomatis</p>
    </div>
    <div class="page-actions">
        <a href="<?= url('items/index.php') ?>" class="btn btn-secondary">
            &larr; Kembali ke Daftar Barang
        </a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Formulir Data Barang</h2>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <?= csrf_field() ?>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="item_code">Kode Barang (Otomatis & Unik) <span class="required">*</span></label>
                    <input type="text" id="item_code" name="item_code" class="form-control" value="<?= e($itemCode) ?>" required>
                    <span class="form-hint">Dibuat otomatis oleh sistem. Dapat disesuaikan jika memiliki format nomor aset khusus.</span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="name">Nama Barang <span class="required">*</span></label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="Contoh: Laptop Dell Latitude 5420 i7" value="<?= e($name) ?>" required>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="category_id">Kategori Barang <span class="required">*</span></label>
                    <select id="category_id" name="category_id" class="form-select" required>
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="location_id">Lokasi Penyimpanan <span class="required">*</span></label>
                    <select id="location_id" name="location_id" class="form-select" required>
                        <option value="">-- Pilih Lokasi Penyimpanan --</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?= $loc['id'] ?>" <?= $locationId == $loc['id'] ? 'selected' : '' ?>><?= e($loc['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="quantity">Jumlah / Saldo Awal <span class="required">*</span></label>
                    <input type="number" id="quantity" name="quantity" class="form-control" min="0" value="<?= e((string)$quantity) ?>" required>
                    <span class="form-hint">Akan dicatat otomatis sebagai mutasi stok awal.</span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="unit">Satuan Barang <span class="required">*</span></label>
                    <input type="text" id="unit" name="unit" class="form-control" placeholder="Contoh: Unit, Pcs, Buah, Set, Box" value="<?= e($unit) ?>" required>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="item_condition">Kondisi Barang <span class="required">*</span></label>
                    <select id="item_condition" name="item_condition" class="form-select" required>
                        <option value="Baik" <?= $condition === 'Baik' ? 'selected' : '' ?>>Baik (Siap Pakai)</option>
                        <option value="Rusak Ringan" <?= $condition === 'Rusak Ringan' ? 'selected' : '' ?>>Rusak Ringan</option>
                        <option value="Rusak Berat" <?= $condition === 'Rusak Berat' ? 'selected' : '' ?>>Rusak Berat</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="status">Status Ketersediaan <span class="required">*</span></label>
                    <select id="status" name="status" class="form-select" required>
                        <option value="Tersedia" <?= $status === 'Tersedia' ? 'selected' : '' ?>>Tersedia</option>
                        <option value="Dipinjam" <?= $status === 'Dipinjam' ? 'selected' : '' ?>>Dipinjam</option>
                        <option value="Habis" <?= $status === 'Habis' ? 'selected' : '' ?>>Habis</option>
                        <option value="Dalam Perbaikan" <?= $status === 'Dalam Perbaikan' ? 'selected' : '' ?>>Dalam Perbaikan</option>
                        <option value="Afkir" <?= $status === 'Afkir' ? 'selected' : '' ?>>Afkir</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="entry_date">Tanggal Pendataan <span class="required">*</span></label>
                <input type="date" id="entry_date" name="entry_date" class="form-control" value="<?= e($entryDate) ?>" required style="max-width: 280px;">
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Keterangan / Spesifikasi Tambahan</label>
                <textarea id="description" name="description" class="form-control" placeholder="Nomor seri, merk, spesifikasi teknis, atau keterangan lain..."><?= e($description) ?></textarea>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 28px;">
                <button type="submit" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                    <span>Simpan & Buat QR Code</span>
                </button>
                <a href="<?= url('items/index.php') ?>" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

