<?php
/**
 * Pencatatan Transaksi Barang Keluar
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

requireRole(['admin', 'petugas']);

$pageTitle = 'Catat Barang Keluar';
$breadcrumbTitle = 'Barang Keluar';
$pdo = getDbConnection();

$selectedItemId = (int)($_GET['item_id'] ?? 0);
$error = '';
$quantity = 1;
$recipient = '';
$notes = '';
$transactionDate = date('Y-m-d\TH:i');

// Ambil semua barang
$items = $pdo->query("SELECT id, item_code, name, quantity, unit, status FROM items ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $error = 'Validasi token keamanan gagal.';
    } else {
        $selectedItemId = (int)($_POST['item_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 0);
        $recipient = trim($_POST['recipient'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $transactionDateInput = $_POST['transaction_date'] ?? date('Y-m-d H:i:s');

        if ($selectedItemId <= 0) {
            $error = 'Silakan pilih barang yang dikeluarkan.';
        } elseif ($quantity <= 0) {
            $error = 'Jumlah barang keluar harus lebih besar dari 0.';
        } else {
            // Ambil data barang terkini dengan row lock
            $stmtItem = $pdo->prepare("SELECT * FROM items WHERE id = ? FOR UPDATE");

            try {
                $pdo->beginTransaction();
                $stmtItem->execute([$selectedItemId]);
                $item = $stmtItem->fetch();

                if (!$item) {
                    throw new Exception("Barang tidak ditemukan.");
                }

                $currentStock = (int)$item['quantity'];

                // VALIDASI KRUSIAL: Stok tidak boleh menjadi negatif
                if ($quantity > $currentStock) {
                    $pdo->rollBack();
                    $error = "Stok tidak mencukupi! Stok saat ini: {$currentStock} {$item['unit']}, namun permintaan keluar adalah {$quantity} {$item['unit']}. Sistem tidak mengizinkan saldo stok bernilai negatif.";
                } else {
                    $newStock = $currentStock - $quantity;
                    $trxCode = generateTransactionCode('Keluar');

                    // 1. Simpan Transaksi Keluar
                    $stmtTrx = $pdo->prepare("
                        INSERT INTO item_transactions 
                        (transaction_code, item_id, transaction_type, quantity, stock_before, stock_after, transaction_date, user_id, recipient_or_supplier, notes, created_at)
                        VALUES (?, ?, 'Keluar', ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmtTrx->execute([
                        $trxCode,
                        $selectedItemId,
                        $quantity,
                        $currentStock,
                        $newStock,
                        date('Y-m-d H:i:s', strtotime($transactionDateInput)),
                        currentUserId(),
                        $recipient,
                        $notes
                    ]);

                    // 2. Perbarui Stok Barang & Status jika Habis
                    $newStatus = ($newStock === 0) ? 'Habis' : $item['status'];
                    $updateItemStmt = $pdo->prepare("
                        UPDATE items 
                        SET quantity = ?, status = ?, updated_at = NOW() 
                        WHERE id = ?
                    ");
                    $updateItemStmt->execute([$newStock, $newStatus, $selectedItemId]);

                    // 3. Catat ke Riwayat Aktivitas
                    logActivity(
                        'BARANG_KELUAR',
                        $selectedItemId,
                        $item['item_code'],
                        $item['name'],
                        "Barang keluar sebanyak {$quantity} {$item['unit']} kepada '{$recipient}'. Stok berkurang: {$currentStock} -> {$newStock} {$item['unit']}.",
                        ['stock' => $currentStock],
                        ['stock' => $newStock]
                    );

                    $pdo->commit();

                    setFlash('success', "Transaksi barang keluar berhasil dicatat. Sisa stok {$item['name']} sekarang: {$newStock} {$item['unit']}.");
                    header('Location: ' . url('items/detail.php?code=' . urlencode($item['item_code'])));
                    exit;
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Gagal mencatat transaksi barang keluar: ' . $e->getMessage();
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
        <h1>Formulir Pencatatan Barang Keluar</h1>
        <p>Catat distribusi, pemakaian divisi, atau peminjaman unit dengan validasi stok ketat</p>
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
    <div class="card-header" style="background-color: var(--color-warning-bg);">
        <h2 class="card-title" style="color: var(--color-warning);">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline></svg>
            <span>Informasi Barang Keluar (-OUT)</span>
        </h2>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <?= csrf_field() ?>

            <div class="form-group">
                <label class="form-label" for="item_id">Pilih Barang yang Dikeluarkan <span class="required">*</span></label>
                <select id="item_id" name="item_id" class="form-select" required onchange="updateOutStockInfo()">
                    <option value="">-- Pilih Barang --</option>
                    <?php foreach ($items as $itm): ?>
                        <option value="<?= $itm['id'] ?>" data-stock="<?= $itm['quantity'] ?>" data-unit="<?= e($itm['unit']) ?>" <?= $selectedItemId == $itm['id'] ? 'selected' : '' ?>>
                            <?= e($itm['item_code']) ?> &mdash; <?= e($itm['name']) ?> (Stok: <?= formatNumber($itm['quantity']) ?> <?= e($itm['unit']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <div id="outStockNotice" style="margin-top: 6px; font-size: 12px; font-weight: 600;"></div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="quantity">Jumlah Keluar <span class="required">*</span></label>
                    <input type="number" id="quantity" name="quantity" class="form-control" min="1" value="<?= e((string)$quantity) ?>" required>
                    <span class="form-hint" id="maxHint">Jumlah tidak boleh melebihi sisa stok yang tersedia.</span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="transaction_date">Tanggal & Waktu Transaksi <span class="required">*</span></label>
                    <input type="datetime-local" id="transaction_date" name="transaction_date" class="form-control" value="<?= e($transactionDate) ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="recipient">Penerima / Divisi Pemakai / Tujuan Pengeluaran <span class="required">*</span></label>
                <input type="text" id="recipient" name="recipient" class="form-control" placeholder="Contoh: Divisi Keuangan, Lab Komputer B, Bpk. Ahmad..." value="<?= e($recipient) ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="notes">Keterangan / Keperluan Pemakaian</label>
                <textarea id="notes" name="notes" class="form-control" placeholder="Tujuan pemakaian, nomor surat permohonan, atau durasi pinjam..."><?= e($notes) ?></textarea>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 28px;">
                <button type="submit" class="btn btn-primary" id="btnSubmitOut">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                    <span>Simpan Transaksi Keluar</span>
                </button>
                <a href="<?= url('transactions/index.php') ?>" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<script>
function updateOutStockInfo() {
    const sel = document.getElementById('item_id');
    const opt = sel.options[sel.selectedIndex];
    const notice = document.getElementById('outStockNotice');
    const qtyInput = document.getElementById('quantity');
    const maxHint = document.getElementById('maxHint');
    const btnSubmit = document.getElementById('btnSubmitOut');

    if (opt && opt.dataset.stock !== undefined) {
        const stock = parseInt(opt.dataset.stock, 10);
        qtyInput.max = stock;
        if (stock === 0) {
            notice.style.color = 'var(--color-danger)';
            notice.textContent = `Peringatan: Stok barang ini habis (0 ${opt.dataset.unit}). Tidak dapat melakukan transaksi keluar.`;
            btnSubmit.disabled = true;
        } else {
            notice.style.color = 'var(--color-primary)';
            notice.textContent = `Stok tersedia: ${stock} ${opt.dataset.unit}. Sisa setelah transaksi tidak boleh bernilai negatif.`;
            maxHint.textContent = `Maksimal jumlah yang dapat dikeluarkan: ${stock} ${opt.dataset.unit}.`;
            btnSubmit.disabled = false;
        }
    } else {
        notice.textContent = '';
        btnSubmit.disabled = false;
    }
}
document.addEventListener('DOMContentLoaded', updateOutStockInfo);
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

