<?php
/**
 * Pencatatan Transaksi Barang Masuk
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

requireRole(['admin', 'petugas']);

$pageTitle = 'Catat Barang Masuk';
$breadcrumbTitle = 'Barang Masuk';
$pdo = getDbConnection();

$selectedItemId = (int)($_GET['item_id'] ?? 0);
$error = '';
$quantity = 1;
$supplier = '';
$notes = '';
$transactionDate = date('Y-m-d\TH:i');

// Ambil semua barang untuk dropdown
$items = $pdo->query("SELECT id, item_code, name, quantity, unit FROM items ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $error = 'Validasi token keamanan gagal.';
    } else {
        $selectedItemId = (int)($_POST['item_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 0);
        $supplier = trim($_POST['supplier'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $transactionDateInput = $_POST['transaction_date'] ?? date('Y-m-d H:i:s');

        if ($selectedItemId <= 0) {
            $error = 'Silakan pilih barang yang masuk.';
        } elseif ($quantity <= 0) {
            $error = 'Jumlah barang masuk harus lebih besar dari 0.';
        } else {
            // Ambil data barang terkini
            $stmtItem = $pdo->prepare("SELECT * FROM items WHERE id = ? FOR UPDATE");
            
            try {
                $pdo->beginTransaction();
                $stmtItem->execute([$selectedItemId]);
                $item = $stmtItem->fetch();

                if (!$item) {
                    throw new Exception("Barang tidak ditemukan.");
                }

                $currentStock = (int)$item['quantity'];
                $newStock = $currentStock + $quantity;
                $trxCode = generateTransactionCode('Masuk');

                // 1. Simpan Transaksi
                $stmtTrx = $pdo->prepare("
                    INSERT INTO item_transactions 
                    (transaction_code, item_id, transaction_type, quantity, stock_before, stock_after, transaction_date, user_id, recipient_or_supplier, notes, created_at)
                    VALUES (?, ?, 'Masuk', ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmtTrx->execute([
                    $trxCode,
                    $selectedItemId,
                    $quantity,
                    $currentStock,
                    $newStock,
                    date('Y-m-d H:i:s', strtotime($transactionDateInput)),
                    currentUserId(),
                    $supplier,
                    $notes
                ]);

                // 2. Perbarui Stok Barang
                // Jika sebelumnya status 'Habis', ubah kembali menjadi 'Tersedia'
                $newStatus = ($item['status'] === 'Habis') ? 'Tersedia' : $item['status'];
                $updateItemStmt = $pdo->prepare("
                    UPDATE items 
                    SET quantity = ?, status = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                $updateItemStmt->execute([$newStock, $newStatus, $selectedItemId]);

                // 3. Catat ke Riwayat Aktivitas
                logActivity(
                    'BARANG_MASUK',
                    $selectedItemId,
                    $item['item_code'],
                    $item['name'],
                    "Barang masuk sebanyak {$quantity} {$item['unit']} dari '{$supplier}'. Stok diperbarui: {$currentStock} -> {$newStock} {$item['unit']}.",
                    ['stock' => $currentStock],
                    ['stock' => $newStock]
                );

                $pdo->commit();

                setFlash('success', "Transaksi barang masuk berhasil dicatat. Stok {$item['name']} bertambah menjadi {$newStock} {$item['unit']}.");
                header('Location: ' . url('items/detail.php?code=' . urlencode($item['item_code'])));
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Gagal mencatat transaksi barang masuk: ' . $e->getMessage();
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
        <h1>Formulir Pencatatan Barang Masuk</h1>
        <p>Catat pengadaan, restock, atau penambahan unit inventaris ke dalam sistem</p>
    </div>
    <div class="page-actions">
        <a href="<?= url('transactions/index.php') ?>" class="btn btn-secondary">
            &larr; Kembali ke Daftar Transaksi
        </a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<div class="card" style="max-width: 780px;">
    <div class="card-header" style="background-color: var(--color-primary-light);">
        <h2 class="card-title" style="color: var(--color-primary-dark);">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>
            <span>Informasi Barang Masuk (+IN)</span>
        </h2>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <?= csrf_field() ?>

            <div class="form-group">
                <label class="form-label" for="item_id">Pilih Barang Inventaris <span class="required">*</span></label>
                <select id="item_id" name="item_id" class="form-select" required onchange="updateStockInfo()">
                    <option value="">-- Pilih Barang --</option>
                    <?php foreach ($items as $itm): ?>
                        <option value="<?= $itm['id'] ?>" data-stock="<?= $itm['quantity'] ?>" data-unit="<?= e($itm['unit']) ?>" <?= $selectedItemId == $itm['id'] ? 'selected' : '' ?>>
                            <?= e($itm['item_code']) ?> &mdash; <?= e($itm['name']) ?> (Stok Saat Ini: <?= formatNumber($itm['quantity']) ?> <?= e($itm['unit']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <div id="stockNotice" style="margin-top: 6px; font-size: 12px; color: var(--color-primary); font-weight: 500;"></div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="quantity">Jumlah Masuk <span class="required">*</span></label>
                    <input type="number" id="quantity" name="quantity" class="form-control" min="1" value="<?= e((string)$quantity) ?>" required>
                    <span class="form-hint">Jumlah unit fisik yang ditambahkan ke stok.</span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="transaction_date">Tanggal & Waktu Transaksi <span class="required">*</span></label>
                    <input type="datetime-local" id="transaction_date" name="transaction_date" class="form-control" value="<?= e($transactionDate) ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="supplier">Asal Pengadaan / Nama Supplier / Pengirim</label>
                <input type="text" id="supplier" name="supplier" class="form-control" placeholder="Contoh: PT Mitra Informatika, Hibah Yayasan, Bagian Pengadaan..." value="<?= e($supplier) ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="notes">Keterangan / Catatan Transaksi</label>
                <textarea id="notes" name="notes" class="form-control" placeholder="Nomor faktur / surat jalan / nomor PO / alasan penambahan..."><?= e($notes) ?></textarea>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 28px;">
                <button type="submit" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                    <span>Simpan Transaksi Masuk</span>
                </button>
                <a href="<?= url('transactions/index.php') ?>" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<script>
function updateStockInfo() {
    const sel = document.getElementById('item_id');
    const opt = sel.options[sel.selectedIndex];
    const notice = document.getElementById('stockNotice');
    if (opt && opt.dataset.stock !== undefined) {
        notice.textContent = `Saldo saat ini: ${opt.dataset.stock} ${opt.dataset.unit}. Stok akan bertambah setelah transaksi disimpan.`;
    } else {
        notice.textContent = '';
    }
}
document.addEventListener('DOMContentLoaded', updateStockInfo);
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

