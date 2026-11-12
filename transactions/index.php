<?php
/**
 * Transaksi Barang Masuk dan Keluar
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

requireAuth();

$pageTitle = 'Barang Masuk & Keluar';
$breadcrumbTitle = 'Transaksi';
$pdo = getDbConnection();

// Filter & Parameter
$type = trim($_GET['type'] ?? '');
$itemId = (int)($_GET['item_id'] ?? 0);
$startDate = trim($_GET['start_date'] ?? '');
$endDate = trim($_GET['end_date'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$conditions = [];
$params = [];

if ($type !== '' && in_array($type, ['Masuk', 'Keluar'])) {
    $conditions[] = "t.transaction_type = ?";
    $params[] = $type;
}

if ($itemId > 0) {
    $conditions[] = "t.item_id = ?";
    $params[] = $itemId;
}

if ($startDate !== '') {
    $conditions[] = "DATE(t.transaction_date) >= ?";
    $params[] = $startDate;
}

if ($endDate !== '') {
    $conditions[] = "DATE(t.transaction_date) <= ?";
    $params[] = $endDate;
}

$whereSql = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Hitung total untuk pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM item_transactions t {$whereSql}");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

// Query transaksi
$sql = "
    SELECT t.*, i.item_code, i.name as item_name, i.unit, u.full_name as officer_name, c.name as category_name
    FROM item_transactions t
    JOIN items i ON t.item_id = i.id
    JOIN categories c ON i.category_id = c.id
    LEFT JOIN users u ON t.user_id = u.id
    {$whereSql}
    ORDER BY t.transaction_date DESC, t.id DESC
    LIMIT {$perPage} OFFSET {$offset}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Ringkasan Total Masuk dan Keluar Keseluruhan
$totalInUnits = $pdo->query("SELECT COALESCE(SUM(quantity), 0) FROM item_transactions WHERE transaction_type = 'Masuk'")->fetchColumn();
$totalOutUnits = $pdo->query("SELECT COALESCE(SUM(quantity), 0) FROM item_transactions WHERE transaction_type = 'Keluar'")->fetchColumn();

// Daftar Item untuk dropdown filter
$itemList = $pdo->query("SELECT id, item_code, name FROM items ORDER BY name ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-header">
    <div class="page-title">
        <h1>Mutasi Barang Masuk & Keluar</h1>
        <p>Pencatatan pergerakan arus fisik barang inventaris dan pembaruan stok otomatis</p>
    </div>
    <div class="page-actions">
        <?php if (canEdit()): ?>
            <a href="<?= url('transactions/in.php') ?>" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>
                <span>Catat Barang Masuk</span>
            </a>
            <a href="<?= url('transactions/out.php') ?>" class="btn btn-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline></svg>
                <span>Catat Barang Keluar</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Kartu Ringkasan Cepat -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); margin-bottom: 20px;">
    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-label">Total Mutasi Masuk</span>
            <span class="stat-value" style="color: var(--color-success);">+<?= formatNumber($totalInUnits) ?> Unit</span>
            <span class="stat-subtext">Akumulasi pengadaan dan penambahan stok</span>
        </div>
        <div class="stat-icon success">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-label">Total Mutasi Keluar</span>
            <span class="stat-value" style="color: var(--color-warning);">-<?= formatNumber($totalOutUnits) ?> Unit</span>
            <span class="stat-subtext">Akumulasi distribusi, peminjaman, & pemakaian</span>
        </div>
        <div class="stat-icon warning">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline></svg>
        </div>
    </div>
</div>

<!-- Filter Transaksi -->
<div class="filter-card">
    <form method="GET" action="" class="filter-grid">
        <div>
            <label class="form-label" for="type">Jenis Transaksi</label>
            <select id="type" name="type" class="form-select">
                <option value="">-- Semua Jenis --</option>
                <option value="Masuk" <?= $type === 'Masuk' ? 'selected' : '' ?>>Barang Masuk (+IN)</option>
                <option value="Keluar" <?= $type === 'Keluar' ? 'selected' : '' ?>>Barang Keluar (-OUT)</option>
            </select>
        </div>

        <div>
            <label class="form-label" for="item_id">Barang</label>
            <select id="item_id" name="item_id" class="form-select">
                <option value="">-- Semua Barang --</option>
                <?php foreach ($itemList as $itm): ?>
                    <option value="<?= $itm['id'] ?>" <?= $itemId == $itm['id'] ? 'selected' : '' ?>><?= e($itm['item_code']) ?> - <?= e($itm['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="form-label" for="start_date">Dari Tanggal</label>
            <input type="date" id="start_date" name="start_date" class="form-control" value="<?= e($startDate) ?>">
        </div>

        <div>
            <label class="form-label" for="end_date">Sampai Tanggal</label>
            <input type="date" id="end_date" name="end_date" class="form-control" value="<?= e($endDate) ?>">
        </div>

        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary" style="flex: 1;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <span>Filter</span>
            </button>
            <?php if ($type !== '' || $itemId > 0 || $startDate !== '' || $endDate !== ''): ?>
                <a href="<?= url('transactions/index.php') ?>" class="btn btn-secondary" title="Reset Filter">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Tabel Transaksi -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Daftar Riwayat Transaksi (Menampilkan <?= count($transactions) ?> dari <?= formatNumber($totalRows) ?> data)</h2>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>No. Transaksi</th>
                    <th>Tanggal</th>
                    <th>Nama Barang</th>
                    <th>Jenis</th>
                    <th>Jumlah</th>
                    <th>Stok Sebelum &rarr; Sesudah</th>
                    <th>Pihak / Supplier / Penerima</th>
                    <th>Petugas</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr><td colspan="9" style="text-align: center; color: var(--color-text-subtle); padding: 32px;">Belum ada catatan transaksi yang sesuai dengan filter.</td></tr>
                <?php else: ?>
                    <?php foreach ($transactions as $t): ?>
                        <tr>
                            <td><span class="table-code" style="font-size: 11.5px;"><?= e($t['transaction_code']) ?></span></td>
                            <td style="font-size: 12px; color: var(--color-text-muted);"><?= formatDate($t['transaction_date']) ?></td>
                            <td>
                                <div><a href="<?= url('items/detail.php?code=' . urlencode($t['item_code'])) ?>" style="font-weight: 600; color: var(--color-text-main);"><?= e($t['item_name']) ?></a></div>
                                <div style="font-size: 11px; color: var(--color-text-subtle);"><?= e($t['item_code']) ?> &bull; <?= e($t['category_name']) ?></div>
                            </td>
                            <td><?= getTransactionTypeBadge($t['transaction_type']) ?></td>
                            <td>
                                <strong style="font-size: 14px; <?= $t['transaction_type'] === 'Masuk' ? 'color: var(--color-success);' : 'color: var(--color-warning);' ?>">
                                    <?= $t['transaction_type'] === 'Masuk' ? '+' : '-' ?><?= formatNumber($t['quantity']) ?>
                                </strong>
                                <span style="font-size: 11px; color: var(--color-text-subtle);"><?= e($t['unit']) ?></span>
                            </td>
                            <td style="font-size: 12.5px;">
                                <span style="color: var(--color-text-muted);"><?= formatNumber($t['stock_before']) ?></span>
                                &rarr;
                                <strong style="color: var(--color-primary);"><?= formatNumber($t['stock_after']) ?></strong>
                            </td>
                            <td><?= e($t['recipient_or_supplier'] ?: '-') ?></td>
                            <td style="font-size: 12px; font-weight: 500;"><?= e($t['officer_name'] ?? 'Petugas') ?></td>
                            <td style="font-size: 12px; color: var(--color-text-muted); max-width: 220px;"><?= e($t['notes'] ?: '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= renderPagination($page, $totalPages, url('transactions/index.php'), [
    'type' => $type,
    'item_id' => $itemId,
    'start_date' => $startDate,
    'end_date' => $endDate
]) ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

